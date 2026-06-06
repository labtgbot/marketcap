#!/usr/bin/env sh
set -eu

failures=0
runner=database/migrate.php
installer=install/includes/installer.php

fail() {
    printf '%s\n' "FAIL: $1" >&2
    failures=$((failures + 1))
}

assert_contains() {
    file=$1
    pattern=$2
    description=$3

    if [ ! -f "$file" ]; then
        fail "Missing required file: $file"
        return
    fi

    if ! grep -Eq "$pattern" "$file"; then
        fail "$file does not include $description"
    fi
}

assert_not_contains() {
    file=$1
    pattern=$2
    description=$3

    if [ ! -f "$file" ]; then
        fail "Missing required file: $file"
        return
    fi

    if grep -Eq "$pattern" "$file"; then
        fail "$file still includes $description"
    fi
}

assert_contains "$runner" 'GET_LOCK' 'a MySQL advisory migration lock'
assert_contains "$runner" 'RELEASE_LOCK' 'advisory migration lock release'
assert_contains "$runner" 'information_schema\.COLUMNS' 'column existence guards for DDL'
assert_contains "$runner" 'information_schema\.STATISTICS' 'index existence guards for DDL'
assert_contains "$runner" 'database_migration_main' 'an includable migration main entrypoint'
assert_not_contains "$runner" 'preg_split.*;\s\*' 'the naive semicolon line splitter'

assert_contains "$installer" 'GET_LOCK' 'the shared advisory migration lock'
assert_contains "$installer" 'information_schema\.COLUMNS' 'installer column existence guards for DDL'
assert_contains "$installer" 'information_schema\.STATISTICS' 'installer index existence guards for DDL'
assert_not_contains "$installer" 'preg_split.*;\s\*' 'the naive installer semicolon line splitter'

assert_not_contains 'database/migrations/0007_smart_alerts.down.sql' "SET[[:space:]]+\`trigger_type\`[[:space:]]*=[[:space:]]*'percent_move'" 'a lossy trigger_type rewrite before shrinking the enum'
assert_contains 'database/migrations/0007_smart_alerts.down.sql' 'removed_trigger_type_guard' 'a rollback guard for Stage 4-only alert trigger types'
assert_contains 'database/migrations/0008_share_referral_attribution.down.sql' 'DELETE[[:space:]]+duplicate_attribution' 'duplicate referral attribution cleanup before restoring the old unique key'
assert_contains 'database/migrations/0008_share_referral_attribution.down.sql' 'keeper_attribution\.`attributed_at` < duplicate_attribution\.`attributed_at`' 'first-touch winner selection before referral attribution de-duplication'
assert_contains 'docs/v2-database-schema-and-migrations.md' '`0007_smart_alerts\.down\.sql` refuses to shrink' 'the smart-alert rollback safety note'
assert_contains 'docs/v2-database-schema-and-migrations.md' '`0008_share_referral_attribution\.down\.sql` restores the older one-attribution' 'the referral rollback de-duplication note'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

php <<'PHP'
<?php
require 'database/migrate.php';

class TonbankcardMigrationFakeStatement extends PDOStatement {
    private TonbankcardMigrationFakePdo $pdo;
    private string $sql;
    private array $params = [];

    public function __construct( TonbankcardMigrationFakePdo $pdo, string $sql ) {
        $this->pdo = $pdo;
        $this->sql = $sql;
    }

    public function execute( ?array $params = null ): bool {
        $this->params = $params ?: [];
        $this->pdo->prepared[] = [
            'sql'    => $this->sql,
            'params' => $this->params,
        ];

        return TRUE;
    }

    public function fetch( int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0 ): mixed {
        if ( FALSE !== strpos( $this->sql, 'GET_LOCK' ) ) {
            return [ 'acquired' => 1 ];
        }

        if ( FALSE !== strpos( $this->sql, 'RELEASE_LOCK' ) ) {
            return [ 'released' => 1 ];
        }

        if ( FALSE !== strpos( $this->sql, 'information_schema.COLUMNS' ) ) {
            $key = $this->params[':table'] . '.' . $this->params[':column'];
            return [ 'found' => isset( $this->pdo->columns[ $key ] ) ? 1 : 0 ];
        }

        if ( FALSE !== strpos( $this->sql, 'information_schema.STATISTICS' ) ) {
            $key = $this->params[':table'] . '.' . $this->params[':index'];
            return [ 'found' => isset( $this->pdo->indexes[ $key ] ) ? 1 : 0 ];
        }

        return FALSE;
    }

    public function fetchAll( int $mode = PDO::FETCH_DEFAULT, mixed ...$args ): array {
        return [];
    }
}

class TonbankcardMigrationFakePdo extends PDO {
    public array $columns = [];
    public array $indexes = [];
    public array $exec = [];
    public array $prepared = [];

    public function __construct() {
    }

    public function exec( string $statement ): int|false {
        $this->exec[] = $statement;
        return 0;
    }

    public function prepare( string $query, array $options = [] ): PDOStatement|false {
        return new TonbankcardMigrationFakeStatement( $this, $query );
    }

    public function query( string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs ): PDOStatement|false {
        return new TonbankcardMigrationFakeStatement( $this, $query );
    }
}

function tonbankcard_reliability_fail( string $message ): void {
    fwrite( STDERR, $message . "\n" );
    exit( 1 );
}

$sql = "INSERT INTO `example` (`value`) VALUES ('literal; semicolon');\n"
    . "/* comment with ; inside */\n"
    . "ALTER TABLE `example` ADD COLUMN `name` VARCHAR(80) NULL;\n"
    . "INSERT INTO `example` (`value`) VALUES (\"double; quoted\");\n";
$statements = database_migration_sql_statements( $sql );
if ( 3 !== count( $statements ) ) {
    tonbankcard_reliability_fail( 'SQL-aware splitter should ignore semicolons inside quoted strings and comments.' );
}

$dir = sys_get_temp_dir() . '/tonbankcard-migration-reliability-' . bin2hex( random_bytes( 4 ) );
mkdir( $dir, 0700, TRUE );
$file = $dir . '/0001_partial_recovery.up.sql';
file_put_contents(
    $file,
    "-- Migration: 0001_partial_recovery\n"
    . "ALTER TABLE `user_sessions`\n"
    . "  ADD COLUMN `telegram_chat_type` VARCHAR(32) NULL AFTER `chat_instance_hash`,\n"
    . "  ADD KEY `idx_user_sessions_telegram_chat_type` (`telegram_chat_type`, `last_seen_at`);\n"
);

$pdo = new TonbankcardMigrationFakePdo();
$pdo->columns['user_sessions.telegram_chat_type'] = TRUE;
$pdo->indexes['user_sessions.idx_user_sessions_telegram_chat_type'] = TRUE;

ob_start();
database_migration_up( $pdo, $dir );
ob_end_clean();

$executed_sql = implode( "\n", $pdo->exec );
if ( FALSE !== strpos( $executed_sql, 'ADD COLUMN `telegram_chat_type`' ) ) {
    tonbankcard_reliability_fail( 'Partial recovery should skip an already-applied ADD COLUMN clause.' );
}

if ( FALSE !== strpos( $executed_sql, 'ADD KEY `idx_user_sessions_telegram_chat_type`' ) ) {
    tonbankcard_reliability_fail( 'Partial recovery should skip an already-applied ADD KEY clause.' );
}

$prepared_sql = implode( "\n", array_column( $pdo->prepared, 'sql' ) );
if ( FALSE === strpos( $prepared_sql, 'GET_LOCK' ) || FALSE === strpos( $prepared_sql, 'RELEASE_LOCK' ) ) {
    tonbankcard_reliability_fail( 'Migration up should acquire and release the advisory lock.' );
}

unlink( $file );
rmdir( $dir );
PHP

printf '%s\n' 'Database migration reliability check passed.'
