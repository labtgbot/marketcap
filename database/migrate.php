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

define( 'DATABASE_MIGRATION_LOCK_NAME', 'tonbankcard_migrations' );
define( 'DATABASE_MIGRATION_LOCK_TIMEOUT_SECONDS', 30 );

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
    $profile  = tonbankcard_runtime_profile();

    if ( '' === $dsn || '' === $user ) {
        throw new RuntimeException( 'Set MYSQL_DSN and MYSQL_USER before running database migrations. MYSQL_PASSWORD is read when present.' );
    }

    return new PDO(
        $dsn,
        $user,
        $password,
        tonbankcard_mysql_pdo_options(
            [ 'ssl' => tonbankcard_mysql_ssl_config_from_env( $profile ) ],
            $profile,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        )
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
    $statements = [];
    $buffer = '';
    $quote = null;
    $line_comment = FALSE;
    $block_comment = FALSE;
    $length = strlen( $sql );

    for ( $i = 0; $i < $length; $i++ ) {
        $char = $sql[ $i ];
        $next = ( $i + 1 < $length ) ? $sql[ $i + 1 ] : '';
        $buffer .= $char;

        if ( $line_comment ) {
            if ( "\n" === $char ) {
                $line_comment = FALSE;
            }
            continue;
        }

        if ( $block_comment ) {
            if ( '*' === $char && '/' === $next ) {
                $buffer .= $next;
                $i++;
                $block_comment = FALSE;
            }
            continue;
        }

        if ( null !== $quote ) {
            if ( ( "'" === $quote || '"' === $quote ) && '\\' === $char && '' !== $next ) {
                $buffer .= $next;
                $i++;
                continue;
            }

            if ( $quote === $char ) {
                if ( '' !== $next && $quote === $next ) {
                    $buffer .= $next;
                    $i++;
                    continue;
                }

                $quote = null;
            }
            continue;
        }

        if ( '-' === $char && '-' === $next && database_migration_is_dash_comment_start( $sql, $i ) ) {
            $buffer .= $next;
            $i++;
            $line_comment = TRUE;
            continue;
        }

        if ( '#' === $char ) {
            $line_comment = TRUE;
            continue;
        }

        if ( '/' === $char && '*' === $next ) {
            $buffer .= $next;
            $i++;
            $block_comment = TRUE;
            continue;
        }

        if ( "'" === $char || '"' === $char || '`' === $char ) {
            $quote = $char;
            continue;
        }

        if ( ';' === $char ) {
            $statement = trim( substr( $buffer, 0, -1 ) );
            if ( '' !== $statement ) {
                $statements[] = $statement;
            }
            $buffer = '';
        }
    }

    $statement = trim( $buffer );
    if ( '' !== $statement ) {
        $statements[] = $statement;
    }

    return $statements;
}

/**
 * @param string $sql
 * @param int $offset
 * @return bool
 */
function database_migration_is_dash_comment_start( string $sql, int $offset ) {
    $next_offset = $offset + 2;
    if ( ! isset( $sql[ $next_offset ] ) ) {
        return TRUE;
    }

    return ctype_space( $sql[ $next_offset ] );
}

/**
 * @param string $sql
 * @return string
 */
function database_migration_strip_leading_comments( string $sql ) {
    $offset = 0;
    $length = strlen( $sql );

    while ( $offset < $length ) {
        while ( $offset < $length && ctype_space( $sql[ $offset ] ) ) {
            $offset++;
        }

        if ( $offset >= $length ) {
            return '';
        }

        if ( '#' === $sql[ $offset ] ) {
            $line_end = strpos( $sql, "\n", $offset );
            if ( FALSE === $line_end ) {
                return '';
            }
            $offset = $line_end + 1;
            continue;
        }

        if ( '-' === $sql[ $offset ] && isset( $sql[ $offset + 1 ] ) && '-' === $sql[ $offset + 1 ] && database_migration_is_dash_comment_start( $sql, $offset ) ) {
            $line_end = strpos( $sql, "\n", $offset );
            if ( FALSE === $line_end ) {
                return '';
            }
            $offset = $line_end + 1;
            continue;
        }

        if ( '/' === $sql[ $offset ] && isset( $sql[ $offset + 1 ] ) && '*' === $sql[ $offset + 1 ] ) {
            $comment_end = strpos( $sql, '*/', $offset + 2 );
            if ( FALSE === $comment_end ) {
                return '';
            }
            $offset = $comment_end + 2;
            continue;
        }

        break;
    }

    return ltrim( substr( $sql, $offset ) );
}

/**
 * @param string $identifier
 * @return string
 */
function database_migration_unquote_identifier( string $identifier ) {
    $identifier = trim( $identifier );
    if ( strlen( $identifier ) >= 2 && '`' === $identifier[0] && '`' === $identifier[ strlen( $identifier ) - 1 ] ) {
        return str_replace( '``', '`', substr( $identifier, 1, -1 ) );
    }

    return trim( $identifier, "` \t\r\n" );
}

/**
 * @param string $sql
 * @return array
 */
function database_migration_split_sql_list( string $sql ) {
    $items = [];
    $buffer = '';
    $quote = null;
    $line_comment = FALSE;
    $block_comment = FALSE;
    $depth = 0;
    $length = strlen( $sql );

    for ( $i = 0; $i < $length; $i++ ) {
        $char = $sql[ $i ];
        $next = ( $i + 1 < $length ) ? $sql[ $i + 1 ] : '';
        $buffer .= $char;

        if ( $line_comment ) {
            if ( "\n" === $char ) {
                $line_comment = FALSE;
            }
            continue;
        }

        if ( $block_comment ) {
            if ( '*' === $char && '/' === $next ) {
                $buffer .= $next;
                $i++;
                $block_comment = FALSE;
            }
            continue;
        }

        if ( null !== $quote ) {
            if ( ( "'" === $quote || '"' === $quote ) && '\\' === $char && '' !== $next ) {
                $buffer .= $next;
                $i++;
                continue;
            }

            if ( $quote === $char ) {
                if ( '' !== $next && $quote === $next ) {
                    $buffer .= $next;
                    $i++;
                    continue;
                }

                $quote = null;
            }
            continue;
        }

        if ( '-' === $char && '-' === $next && database_migration_is_dash_comment_start( $sql, $i ) ) {
            $buffer .= $next;
            $i++;
            $line_comment = TRUE;
            continue;
        }

        if ( '#' === $char ) {
            $line_comment = TRUE;
            continue;
        }

        if ( '/' === $char && '*' === $next ) {
            $buffer .= $next;
            $i++;
            $block_comment = TRUE;
            continue;
        }

        if ( "'" === $char || '"' === $char || '`' === $char ) {
            $quote = $char;
            continue;
        }

        if ( '(' === $char ) {
            $depth++;
            continue;
        }

        if ( ')' === $char && $depth > 0 ) {
            $depth--;
            continue;
        }

        if ( ',' === $char && 0 === $depth ) {
            $item = trim( substr( $buffer, 0, -1 ) );
            if ( '' !== $item ) {
                $items[] = $item;
            }
            $buffer = '';
        }
    }

    $item = trim( $buffer );
    if ( '' !== $item ) {
        $items[] = $item;
    }

    return $items;
}

/**
 * @param string $statement
 * @return array|null
 */
function database_migration_parse_alter_table( string $statement ) {
    $normalized = database_migration_strip_leading_comments( $statement );
    if ( ! preg_match( '/\AALTER\s+TABLE\s+((?:`(?:``|[^`])+`)|[A-Za-z0-9_]+)\s+(.+)\z/is', $normalized, $matches ) ) {
        return null;
    }

    return [
        'table_sql' => $matches[1],
        'table'     => database_migration_unquote_identifier( $matches[1] ),
        'clauses'   => database_migration_split_sql_list( $matches[2] ),
    ];
}

/**
 * @param PDO $pdo
 * @param string $table
 * @param string $column
 * @return bool
 */
function database_migration_column_exists( PDO $pdo, string $table, string $column ) {
    $statement = $pdo->prepare(
        'SELECT COUNT(*) AS found FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $statement->execute(
        [
            ':table'  => $table,
            ':column' => $column,
        ]
    );
    $row = $statement->fetch();

    return is_array( $row ) && isset( $row['found'] ) && (int) $row['found'] > 0;
}

/**
 * @param PDO $pdo
 * @param string $table
 * @param string $index
 * @return bool
 */
function database_migration_index_exists( PDO $pdo, string $table, string $index ) {
    $statement = $pdo->prepare(
        'SELECT COUNT(*) AS found FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index'
    );
    $statement->execute(
        [
            ':table' => $table,
            ':index' => $index,
        ]
    );
    $row = $statement->fetch();

    return is_array( $row ) && isset( $row['found'] ) && (int) $row['found'] > 0;
}

/**
 * @param PDO $pdo
 * @param string $table
 * @param string $clause
 * @return bool
 */
function database_migration_should_skip_alter_clause( PDO $pdo, string $table, string $clause ) {
    $normalized = database_migration_strip_leading_comments( $clause );

    if ( preg_match( '/\AADD\s+(?:UNIQUE\s+|FULLTEXT\s+|SPATIAL\s+)?(?:KEY|INDEX)\s+((?:`(?:``|[^`])+`)|[A-Za-z0-9_]+)/i', $normalized, $matches ) ) {
        return database_migration_index_exists( $pdo, $table, database_migration_unquote_identifier( $matches[1] ) );
    }

    if ( preg_match( '/\AADD\s+COLUMN\s+((?:`(?:``|[^`])+`)|[A-Za-z0-9_]+)/i', $normalized, $matches ) ) {
        return database_migration_column_exists( $pdo, $table, database_migration_unquote_identifier( $matches[1] ) );
    }

    if ( preg_match( '/\AADD\s+((?:`(?:``|[^`])+`)|[A-Za-z0-9_]+)/i', $normalized, $matches ) ) {
        $identifier = database_migration_unquote_identifier( $matches[1] );
        if ( '`' !== $matches[1][0] && in_array( strtoupper( $identifier ), [ 'UNIQUE', 'FULLTEXT', 'SPATIAL', 'KEY', 'INDEX', 'PRIMARY', 'CONSTRAINT', 'FOREIGN', 'CHECK' ], TRUE ) ) {
            return FALSE;
        }

        return database_migration_column_exists( $pdo, $table, $identifier );
    }

    if ( preg_match( '/\ADROP\s+(?:KEY|INDEX)\s+((?:`(?:``|[^`])+`)|[A-Za-z0-9_]+)/i', $normalized, $matches ) ) {
        return ! database_migration_index_exists( $pdo, $table, database_migration_unquote_identifier( $matches[1] ) );
    }

    if ( preg_match( '/\ADROP\s+COLUMN\s+((?:`(?:``|[^`])+`)|[A-Za-z0-9_]+)/i', $normalized, $matches ) ) {
        return ! database_migration_column_exists( $pdo, $table, database_migration_unquote_identifier( $matches[1] ) );
    }

    return FALSE;
}

/**
 * @param PDO $pdo
 * @param string $statement
 * @return bool
 */
function database_migration_exec_guarded_alter( PDO $pdo, string $statement ) {
    $alter = database_migration_parse_alter_table( $statement );
    if ( null === $alter ) {
        return FALSE;
    }

    $clauses = [];
    foreach ( $alter['clauses'] as $clause ) {
        if ( database_migration_should_skip_alter_clause( $pdo, $alter['table'], $clause ) ) {
            continue;
        }

        $clauses[] = $clause;
    }

    if ( empty( $clauses ) ) {
        return TRUE;
    }

    $pdo->exec( 'ALTER TABLE ' . $alter['table_sql'] . "\n  " . implode( ",\n  ", $clauses ) );

    return TRUE;
}

/**
 * @param PDO $pdo
 * @param string $statement
 * @return void
 */
function database_migration_exec_statement( PDO $pdo, string $statement ) {
    if ( database_migration_exec_guarded_alter( $pdo, $statement ) ) {
        return;
    }

    $pdo->exec( $statement );
}

/**
 * @param PDO $pdo
 * @return void
 */
function database_migration_acquire_lock( PDO $pdo ) {
    $statement = $pdo->prepare( 'SELECT GET_LOCK(:lock_name, :timeout_seconds) AS acquired' );
    $statement->execute(
        [
            ':lock_name'       => DATABASE_MIGRATION_LOCK_NAME,
            ':timeout_seconds' => DATABASE_MIGRATION_LOCK_TIMEOUT_SECONDS,
        ]
    );
    $row = $statement->fetch();

    if ( ! is_array( $row ) || ! isset( $row['acquired'] ) || 1 !== (int) $row['acquired'] ) {
        throw new RuntimeException( 'Could not acquire migration lock: ' . DATABASE_MIGRATION_LOCK_NAME );
    }
}

/**
 * @param PDO $pdo
 * @return void
 */
function database_migration_release_lock( PDO $pdo ) {
    $statement = $pdo->prepare( 'SELECT RELEASE_LOCK(:lock_name) AS released' );
    $statement->execute( [ ':lock_name' => DATABASE_MIGRATION_LOCK_NAME ] );
}

/**
 * @param PDO $pdo
 * @param callable $callback
 * @return mixed
 */
function database_migration_with_lock( PDO $pdo, callable $callback ) {
    database_migration_acquire_lock( $pdo );

    try {
        return $callback();
    } finally {
        database_migration_release_lock( $pdo );
    }
}

/**
 * @param string $part
 * @return bool
 */
function database_migration_is_comment_only_statement( string $part ) {
    return '' === database_migration_strip_leading_comments( $part );
}

/**
 * @param array $parts
 * @return array
 */
function database_migration_filter_comment_only_statements( array $parts ) {
    $statements = [];
    foreach ( $parts as $part ) {
        $statement = trim( $part );
        if ( '' === $statement ) {
            continue;
        }

        if ( database_migration_is_comment_only_statement( $statement ) ) {
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

    foreach ( database_migration_filter_comment_only_statements( database_migration_sql_statements( $sql ) ) as $statement ) {
        database_migration_exec_statement( $pdo, $statement );
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
    return database_migration_with_lock(
        $pdo,
        function () use ( $pdo, $dir ) {
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
    );
}

/**
 * @param PDO $pdo
 * @param string $dir
 * @param int $step
 * @return int
 */
function database_migration_down( PDO $pdo, string $dir, int $step ) {
    return database_migration_with_lock(
        $pdo,
        function () use ( $pdo, $dir, $step ) {
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
    );
}

/**
 * @param array $argv
 * @return int
 */
function database_migration_main( array $argv ) {
    try {
        list( $command, $options ) = database_migration_parse_args( $argv );

        if ( in_array( $command, [ 'help', '--help', '-h' ], TRUE ) ) {
            echo database_migration_usage();
            return 0;
        }

        if ( 'dry-run' === $command ) {
            return database_migration_dry_run( $options['migrations_dir'] );
        }

        if ( ! in_array( $command, [ 'status', 'up', 'down' ], TRUE ) ) {
            fwrite( STDERR, 'Unknown command: ' . $command . "\n" );
            fwrite( STDERR, database_migration_usage() );
            return 1;
        }

        $pdo = database_migration_connect();

        if ( 'status' === $command ) {
            return database_migration_status( $pdo, $options['migrations_dir'] );
        }

        if ( 'up' === $command ) {
            return database_migration_up( $pdo, $options['migrations_dir'] );
        }

        if ( 'down' === $command ) {
            return database_migration_down( $pdo, $options['migrations_dir'], $options['step'] );
        }

        return 0;
    } catch ( Throwable $error ) {
        fwrite( STDERR, 'Migration failed: ' . $error->getMessage() . "\n" );
        return 1;
    }
}

if ( isset( $argv[0] ) && realpath( (string) $argv[0] ) === realpath( __FILE__ ) ) {
    exit( database_migration_main( $argv ) );
}
