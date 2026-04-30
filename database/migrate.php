#!/usr/bin/env php
<?php
/**
 * TONBANKCARD V2 database migration runner.
 */

if ( PHP_SAPI !== 'cli' ) {
    fwrite( STDERR, "Database migrations can only run from the command line.\n" );
    exit( 1 );
}

$root_dir = dirname( __DIR__ );
require_once $root_dir . '/config/runtime.php';
tonbankcard_load_env_file( $root_dir . '/.env' );

/**
 * @return string
 */
function database_migration_usage() {
    return implode(
        "\n",
        [
            'Usage: php database/migrate.php <command> [options]',
            '',
            'Commands:',
            '  dry-run   List migration files without connecting to the database.',
            '  status    Show applied and pending migrations.',
            '  up        Apply all pending migrations.',
            '  down      Revert applied migrations. Defaults to --step=1.',
            '',
            'Options:',
            '  --migrations-dir=PATH   Override the migration directory.',
            '  --step=N                Number of down migrations to revert.',
        ]
    ) . "\n";
}

/**
 * @param array $argv
 * @return array
 */
function database_migration_parse_args( array $argv ) {
    $command = isset( $argv[1] ) ? $argv[1] : 'help';
    $options = [
        'migrations_dir' => dirname( __DIR__ ) . '/database/migrations',
        'step'           => 1,
    ];

    foreach ( array_slice( $argv, 2 ) as $arg ) {
        if ( 0 === strpos( $arg, '--migrations-dir=' ) ) {
            $options['migrations_dir'] = substr( $arg, strlen( '--migrations-dir=' ) );
            continue;
        }

        if ( 0 === strpos( $arg, '--step=' ) ) {
            $options['step'] = max( 1, (int) substr( $arg, strlen( '--step=' ) ) );
            continue;
        }

        throw new InvalidArgumentException( 'Unknown option: ' . $arg );
    }

    return [ $command, $options ];
}

/**
 * @param string $dir
 * @return array
 */
function database_migration_up_files( string $dir ) {
    if ( ! is_dir( $dir ) ) {
        throw new RuntimeException( 'Migration directory does not exist: ' . $dir );
    }

    $files = glob( rtrim( $dir, '/' ) . '/*.up.sql' );
    if ( ! is_array( $files ) ) {
        $files = [];
    }

    sort( $files, SORT_NATURAL );

    $migrations = [];
    foreach ( $files as $file ) {
        $version = basename( $file, '.up.sql' );
        $migrations[ $version ] = $file;
    }

    return $migrations;
}

/**
 * @param string $version
 * @return string
 */
function database_migration_description( string $version ) {
    $description = preg_replace( '/^[0-9]+_?/', '', $version );
    $description = str_replace( '_', ' ', (string) $description );

    return trim( $description ) ?: $version;
}

/**
 * @return PDO
 */
function database_migration_connect() {
    $dsn      = trim( (string) tonbankcard_env( 'MYSQL_DSN', '' ) );
    $user     = (string) tonbankcard_env( 'MYSQL_USER', '' );
    $password = (string) tonbankcard_env( 'MYSQL_PASSWORD', '' );

    if ( '' === $dsn || '' === $user ) {
        throw new RuntimeException( 'Set MYSQL_DSN and MYSQL_USER before running database migrations. MYSQL_PASSWORD is read when present.' );
    }

    return new PDO(
        $dsn,
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

/**
 * @param PDO $pdo
 * @return void
 */
function database_migration_ensure_ledger( PDO $pdo ) {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `version` VARCHAR(191) NOT NULL,
            `description` VARCHAR(255) NOT NULL,
            `checksum` CHAR(64) NOT NULL,
            `applied_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            PRIMARY KEY (`version`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

/**
 * @param PDO $pdo
 * @return array
 */
function database_migration_applied( PDO $pdo ) {
    $rows = $pdo->query( 'SELECT version, checksum, applied_at FROM schema_migrations ORDER BY version' )->fetchAll();
    $applied = [];

    foreach ( $rows as $row ) {
        $applied[ $row['version'] ] = $row;
    }

    return $applied;
}

/**
 * @param string $sql
 * @return array
 */
function database_migration_sql_statements( string $sql ) {
    $parts = preg_split( '/;\s*(?:\r?\n|$)/', $sql );
    if ( ! is_array( $parts ) ) {
        return [];
    }

    $statements = [];
    foreach ( $parts as $part ) {
        $statement = trim( $part );
        if ( '' === $statement ) {
            continue;
        }
        $statements[] = $statement;
    }

    return $statements;
}

/**
 * @param PDO $pdo
 * @param string $file
 * @return void
 */
function database_migration_exec_file( PDO $pdo, string $file ) {
    $sql = file_get_contents( $file );
    if ( FALSE === $sql ) {
        throw new RuntimeException( 'Cannot read migration file: ' . $file );
    }

    foreach ( database_migration_sql_statements( $sql ) as $statement ) {
        $pdo->exec( $statement );
    }
}

/**
 * @param string $dir
 * @return int
 */
function database_migration_dry_run( string $dir ) {
    $migrations = database_migration_up_files( $dir );
    if ( empty( $migrations ) ) {
        echo "No migration files found.\n";
        return 0;
    }

    echo "Available migrations:\n";
    foreach ( $migrations as $version => $file ) {
        echo '- ' . $version . ' (' . $file . ")\n";
    }

    return 0;
}

/**
 * @param PDO $pdo
 * @param string $dir
 * @return int
 */
function database_migration_status( PDO $pdo, string $dir ) {
    $migrations = database_migration_up_files( $dir );
    database_migration_ensure_ledger( $pdo );
    $applied = database_migration_applied( $pdo );

    foreach ( $migrations as $version => $file ) {
        $status = isset( $applied[ $version ] ) ? 'applied' : 'pending';
        echo $status . ' ' . $version . ' ' . basename( $file ) . "\n";
    }

    return 0;
}

/**
 * @param PDO $pdo
 * @param string $dir
 * @return int
 */
function database_migration_up( PDO $pdo, string $dir ) {
    $migrations = database_migration_up_files( $dir );
    database_migration_ensure_ledger( $pdo );
    $applied = database_migration_applied( $pdo );
    $count = 0;

    foreach ( $migrations as $version => $file ) {
        if ( isset( $applied[ $version ] ) ) {
            continue;
        }

        database_migration_exec_file( $pdo, $file );

        $insert = $pdo->prepare(
            'INSERT INTO schema_migrations (version, description, checksum) VALUES (:version, :description, :checksum)'
        );
        $insert->execute(
            [
                ':version'     => $version,
                ':description' => database_migration_description( $version ),
                ':checksum'    => hash_file( 'sha256', $file ),
            ]
        );

        echo 'applied ' . $version . "\n";
        $count++;
    }

    if ( 0 === $count ) {
        echo "No pending migrations.\n";
    }

    return 0;
}

/**
 * @param PDO $pdo
 * @param string $dir
 * @param int $step
 * @return int
 */
function database_migration_down( PDO $pdo, string $dir, int $step ) {
    database_migration_ensure_ledger( $pdo );
    $applied = array_keys( database_migration_applied( $pdo ) );
    rsort( $applied, SORT_NATURAL );
    $selected = array_slice( $applied, 0, $step );

    if ( empty( $selected ) ) {
        echo "No applied migrations to revert.\n";
        return 0;
    }

    foreach ( $selected as $version ) {
        $file = rtrim( $dir, '/' ) . '/' . $version . '.down.sql';
        if ( ! is_readable( $file ) ) {
            throw new RuntimeException( 'Missing down migration file: ' . $file );
        }

        database_migration_exec_file( $pdo, $file );

        $delete = $pdo->prepare( 'DELETE FROM schema_migrations WHERE version = :version' );
        $delete->execute( [ ':version' => $version ] );

        echo 'reverted ' . $version . "\n";
    }

    return 0;
}

try {
    list( $command, $options ) = database_migration_parse_args( $argv );

    if ( in_array( $command, [ 'help', '--help', '-h' ], TRUE ) ) {
        echo database_migration_usage();
        exit( 0 );
    }

    if ( 'dry-run' === $command ) {
        exit( database_migration_dry_run( $options['migrations_dir'] ) );
    }

    if ( ! in_array( $command, [ 'status', 'up', 'down' ], TRUE ) ) {
        fwrite( STDERR, 'Unknown command: ' . $command . "\n" );
        fwrite( STDERR, database_migration_usage() );
        exit( 1 );
    }

    $pdo = database_migration_connect();

    if ( 'status' === $command ) {
        exit( database_migration_status( $pdo, $options['migrations_dir'] ) );
    }

    if ( 'up' === $command ) {
        exit( database_migration_up( $pdo, $options['migrations_dir'] ) );
    }

    if ( 'down' === $command ) {
        exit( database_migration_down( $pdo, $options['migrations_dir'], $options['step'] ) );
    }

    exit( 0 );
} catch ( Throwable $error ) {
    fwrite( STDERR, 'Migration failed: ' . $error->getMessage() . "\n" );
    exit( 1 );
}
