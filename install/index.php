<?php
/**
 * TONBANKCARD automatic hosting installer.
 */

require_once __DIR__ . '/includes/installer.php';

$root_dir = tonbankcard_installer_root_dir();

if ( PHP_SESSION_ACTIVE !== session_status() ) {
    session_start();
}

if ( empty( $_SESSION['tonbankcard_installer_csrf'] ) ) {
    $_SESSION['tonbankcard_installer_csrf'] = tonbankcard_installer_generate_token();
}

$query = array_merge( $_GET, $_POST );
$installer_language = tonbankcard_installer_normalize_language(
    isset( $query['language'] )
        ? $query['language']
        : ( isset( $_SESSION['tonbankcard_installer_language'] ) ? $_SESSION['tonbankcard_installer_language'] : 'en' )
);
$_SESSION['tonbankcard_installer_language'] = $installer_language;
$lock_state = tonbankcard_installer_lock_state( $root_dir, $query, $installer_language );
$values = tonbankcard_installer_default_values( $root_dir );
if ( ! empty( $_SESSION['tonbankcard_installer_values'] ) && is_array( $_SESSION['tonbankcard_installer_values'] ) ) {
    $values = array_merge( $values, $_SESSION['tonbankcard_installer_values'] );
}

$messages = [];
$generated_env = '';
$database_result = null;
$migration_result = null;

if ( ! empty( $lock_state['allowed'] ) && 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' ) ) {
    $csrf = isset( $_POST['csrf'] ) ? (string) $_POST['csrf'] : '';
    if ( ! hash_equals( (string) $_SESSION['tonbankcard_installer_csrf'], $csrf ) ) {
        $messages[] = [
            'type' => 'error',
            'text' => tonbankcard_installer_translate( 'The installer form expired. Reload the page and try again.', $installer_language ),
        ];
    } else {
        $values = tonbankcard_installer_prepare_values( $_POST, $root_dir );
        $_SESSION['tonbankcard_installer_values'] = $values;
        $action = isset( $_POST['action'] ) ? (string) $_POST['action'] : 'preview_env';

        if ( 'test_database' === $action ) {
            $database_result = tonbankcard_installer_test_database( $values, $installer_language );
            $messages[] = [
                'type' => ! empty( $database_result['ok'] ) ? 'success' : 'error',
                'text' => $database_result['message'],
            ];
        } elseif ( 'run_migrations' === $action ) {
            try {
                $migration_result = tonbankcard_installer_apply_migrations( $values, $root_dir . '/database/migrations', $installer_language );
                $messages[] = [
                    'type' => 'success',
                    'text' => $migration_result['message'],
                ];
            } catch ( Throwable $error ) {
                tonbankcard_installer_log_error( 'database migrations failed', $error );
                $messages[] = [
                    'type' => 'error',
                    'text' => tonbankcard_installer_migration_failure_message( $installer_language ),
                ];
            }
        } elseif ( 'write_env' === $action ) {
            $validation_errors = tonbankcard_installer_validate_values( $values, $installer_language );
            if ( ! empty( $validation_errors ) ) {
                $generated_env = tonbankcard_installer_render_env( $values, $root_dir );
                foreach ( $validation_errors as $validation_error ) {
                    $messages[] = [
                        'type' => 'error',
                        'text' => $validation_error,
                    ];
                }
            } else {
                $write = tonbankcard_installer_write_env( $root_dir, $values, $installer_language );
                $generated_env = isset( $write['env'] ) ? $write['env'] : '';
                $messages[] = [
                    'type' => ! empty( $write['ok'] ) ? 'success' : 'error',
                    'text' => $write['message'],
                ];

                if ( ! empty( $_POST['run_migrations_after_write'] ) ) {
                    try {
                        $migration_result = tonbankcard_installer_apply_migrations( $values, $root_dir . '/database/migrations', $installer_language );
                        $messages[] = [
                            'type' => 'success',
                            'text' => $migration_result['message'],
                        ];
                    } catch ( Throwable $error ) {
                        tonbankcard_installer_log_error( 'database migrations failed after .env write', $error );
                        $messages[] = [
                            'type' => 'error',
                            'text' => tonbankcard_installer_migration_failure_message( $installer_language, TRUE ),
                        ];
                    }
                }

                if ( ! empty( $write['ok'] ) ) {
                    $_SESSION['tonbankcard_installer_values'] = [];
                }
            }
        } else {
            $generated_env = tonbankcard_installer_render_env( $values, $root_dir );
            $messages[] = [
                'type' => 'success',
                'text' => tonbankcard_installer_translate( 'Review the generated .env preview below before writing it to the server.', $installer_language ),
            ];
        }
    }
}

$system_checks = tonbankcard_installer_system_checks( $root_dir, $installer_language );
$migration_plan = tonbankcard_installer_migration_plan( $root_dir . '/database/migrations', [] );

/**
 * @param mixed $value
 * @return string
 */
function tonbankcard_installer_e( $value ) {
    return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

/**
 * @param string $text
 * @return string
 */
function tonbankcard_installer_ui_text( string $text ) {
    global $installer_language;
    return tonbankcard_installer_translate( $text, $installer_language );
}

/**
 * @param string $key
 * @param array $definition
 * @param array $values
 * @return void
 */
function tonbankcard_installer_render_field( string $key, array $definition, array $values ) {
    $value = array_key_exists( $key, $values ) ? (string) $values[ $key ] : '';
    $type = isset( $definition['type'] ) ? $definition['type'] : 'text';
    $label = isset( $definition['label'] ) ? $definition['label'] : $key;
    $help = isset( $definition['help'] ) ? $definition['help'] : '';
    $placeholder = isset( $definition['placeholder'] ) ? $definition['placeholder'] : '';
    ?>
    <label class="installer-field">
        <span class="installer-label"><?php echo tonbankcard_installer_e( $label ); ?></span>
        <span class="installer-key"><?php echo tonbankcard_installer_e( $key ); ?></span>
        <?php if ( 'select' === $type ) : ?>
            <select name="<?php echo tonbankcard_installer_e( $key ); ?>">
                <?php foreach ( $definition['options'] as $option ) : ?>
                    <option value="<?php echo tonbankcard_installer_e( $option ); ?>"<?php echo $value === $option ? ' selected' : ''; ?>>
                        <?php echo tonbankcard_installer_e( $option ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php elseif ( 'boolean' === $type ) : ?>
            <select name="<?php echo tonbankcard_installer_e( $key ); ?>">
                <?php foreach ( [ 'false', 'true' ] as $option ) : ?>
                    <option value="<?php echo tonbankcard_installer_e( $option ); ?>"<?php echo tonbankcard_installer_normalize_bool( $value ) === $option ? ' selected' : ''; ?>>
                        <?php echo tonbankcard_installer_e( $option ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else : ?>
            <input
                type="<?php echo tonbankcard_installer_e( $type ); ?>"
                name="<?php echo tonbankcard_installer_e( $key ); ?>"
                value="<?php echo tonbankcard_installer_e( $value ); ?>"
                placeholder="<?php echo tonbankcard_installer_e( $placeholder ); ?>"
            >
        <?php endif; ?>
        <?php if ( '' !== $help ) : ?>
            <span class="installer-help"><?php echo tonbankcard_installer_e( $help ); ?></span>
        <?php endif; ?>
    </label>
    <?php
}

?><!doctype html>
<html lang="<?php echo tonbankcard_installer_e( $installer_language ); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'TONBANKCARD Automatic Hosting Installer' ) ); ?></title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f5f7fb;
            --panel: #ffffff;
            --text: #182033;
            --muted: #65708a;
            --line: #d9deea;
            --brand: #1168d8;
            --brand-dark: #0b4f9f;
            --ok: #087f5b;
            --warn: #a15c00;
            --error: #b42318;
            --soft-ok: #e8f8f1;
            --soft-warn: #fff6df;
            --soft-error: #fee9e7;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-width: 320px;
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 15px;
            line-height: 1.5;
        }

        a {
            color: var(--brand-dark);
        }

        .installer-shell {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 28px 0 48px;
        }

        .installer-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 20px;
            align-items: end;
            padding: 20px 0 24px;
        }

        .installer-header h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.15;
            letter-spacing: 0;
        }

        .installer-header p {
            max-width: 760px;
            margin: 10px 0 0;
            color: var(--muted);
        }

        .installer-meta {
            display: grid;
            gap: 10px;
            justify-items: end;
        }

        .installer-pill {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 6px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            color: var(--muted);
            font-size: 13px;
            white-space: nowrap;
        }

        .installer-language {
            display: grid;
            gap: 6px;
            min-width: 180px;
        }

        .installer-language label {
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .installer-layout {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 20px;
            align-items: start;
        }

        .installer-nav {
            position: sticky;
            top: 16px;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
        }

        .installer-nav a {
            display: block;
            padding: 8px 0;
            color: var(--text);
            text-decoration: none;
            border-bottom: 1px solid #edf0f6;
            font-size: 14px;
        }

        .installer-nav a:last-child {
            border-bottom: 0;
        }

        .installer-main {
            display: grid;
            gap: 16px;
        }

        .installer-section {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            padding: 22px;
        }

        .installer-section h2 {
            margin: 0;
            font-size: 20px;
            line-height: 1.25;
            letter-spacing: 0;
        }

        .installer-section p {
            margin: 8px 0 0;
            color: var(--muted);
        }

        .installer-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .installer-field {
            display: grid;
            gap: 6px;
            align-content: start;
        }

        .installer-label {
            font-weight: 700;
        }

        .installer-key,
        .installer-help {
            color: var(--muted);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .installer-help {
            font-family: inherit;
        }

        input,
        select,
        textarea {
            width: 100%;
            min-height: 42px;
            border: 1px solid #bfc7d8;
            border-radius: 6px;
            background: #fff;
            color: var(--text);
            font: inherit;
            padding: 9px 11px;
        }

        textarea {
            min-height: 260px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 13px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: 3px solid rgba(17, 104, 216, 0.18);
            border-color: var(--brand);
        }

        .installer-checks,
        .installer-migrations,
        .installer-messages {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .installer-check,
        .installer-message,
        .installer-migration {
            display: grid;
            grid-template-columns: 96px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 12px;
            background: #fafbfe;
        }

        .installer-badge {
            display: inline-flex;
            justify-content: center;
            min-width: 74px;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .installer-badge.ok,
        .installer-badge.success {
            background: var(--soft-ok);
            color: var(--ok);
        }

        .installer-badge.warn {
            background: var(--soft-warn);
            color: var(--warn);
        }

        .installer-badge.fail,
        .installer-badge.error {
            background: var(--soft-error);
            color: var(--error);
        }

        .installer-actions {
            position: sticky;
            bottom: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-top: 18px;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 -8px 24px rgba(18, 31, 53, 0.08);
        }

        button {
            min-height: 42px;
            border: 1px solid var(--brand-dark);
            border-radius: 6px;
            background: var(--brand);
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-weight: 800;
            padding: 9px 14px;
        }

        button.secondary {
            background: #fff;
            color: var(--brand-dark);
        }

        .installer-checkbox {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            color: var(--muted);
        }

        .installer-checkbox input {
            width: 18px;
            min-height: 18px;
        }

        .installer-lock {
            border-color: #f1b8b4;
            background: #fff7f6;
        }

        @media (max-width: 860px) {
            .installer-header,
            .installer-layout,
            .installer-grid {
                grid-template-columns: 1fr;
            }

            .installer-meta {
                justify-items: start;
            }

            .installer-nav {
                position: static;
            }
        }

        @media (max-width: 560px) {
            .installer-shell {
                width: min(100% - 20px, 1180px);
                padding-top: 10px;
            }

            .installer-header h1 {
                font-size: 26px;
            }

            .installer-section {
                padding: 16px;
            }

            .installer-check,
            .installer-message,
            .installer-migration {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="installer-shell">
        <header class="installer-header">
            <div>
                <h1><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'TONBANKCARD Automatic Hosting Installer' ) ); ?></h1>
                <p><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Configure PHP hosting, MySQL or MariaDB, Telegram Mini App settings, providers, feature flags, worker tokens, and database migrations from one guarded setup screen.' ) ); ?></p>
            </div>
            <div class="installer-meta">
                <span class="installer-pill">PHP <?php echo tonbankcard_installer_e( PHP_VERSION ); ?></span>
                <form class="installer-language" method="get">
                    <label for="installer-language"><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Installer language' ) ); ?></label>
                    <select id="installer-language" name="language" onchange="this.form.submit()">
                        <?php foreach ( tonbankcard_installer_supported_languages() as $language_code => $language_label ) : ?>
                            <option value="<?php echo tonbankcard_installer_e( $language_code ); ?>"<?php echo $installer_language === $language_code ? ' selected' : ''; ?>>
                                <?php echo tonbankcard_installer_e( $language_label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ( ! empty( $_GET['token'] ) ) : ?>
                        <input type="hidden" name="token" value="<?php echo tonbankcard_installer_e( $_GET['token'] ); ?>">
                    <?php endif; ?>
                    <noscript><button class="secondary" type="submit"><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Apply language' ) ); ?></button></noscript>
                </form>
            </div>
        </header>

        <div class="installer-layout">
            <nav class="installer-nav" aria-label="<?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Installer steps' ) ); ?>">
                <a href="#readiness"><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Step 1. Readiness' ) ); ?></a>
                <?php foreach ( tonbankcard_installer_field_groups( $installer_language ) as $group_key => $group ) : ?>
                    <a href="#step-<?php echo tonbankcard_installer_e( $group_key ); ?>">
                        <?php echo tonbankcard_installer_e( $group['title'] ); ?>
                    </a>
                <?php endforeach; ?>
                <a href="#migrations"><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Step 8. Migrations' ) ); ?></a>
            </nav>

            <div class="installer-main">
                <?php if ( empty( $lock_state['allowed'] ) ) : ?>
                    <section class="installer-section installer-lock">
                        <h2><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Installer locked' ) ); ?></h2>
                        <p><?php echo tonbankcard_installer_e( $lock_state['message'] ); ?></p>
                        <p><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'To reopen it, edit .env, set TONBANKCARD_INSTALLER_ENABLED=true, set a strong TONBANKCARD_INSTALLER_TOKEN, and open /install/?token=your-token.' ) ); ?></p>
                    </section>
                <?php endif; ?>

                <?php if ( ! empty( $messages ) ) : ?>
                    <section class="installer-section">
                        <h2><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Installer messages' ) ); ?></h2>
                        <div class="installer-messages">
                            <?php foreach ( $messages as $message ) : ?>
                                <div class="installer-message">
                                    <span class="installer-badge <?php echo tonbankcard_installer_e( $message['type'] ); ?>"><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( $message['type'] ) ); ?></span>
                                    <span><?php echo tonbankcard_installer_e( $message['text'] ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="installer-section" id="readiness">
                    <h2><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Step 1. Readiness' ) ); ?></h2>
                    <p><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Confirm the host can run TONBANKCARD before writing configuration or applying migrations.' ) ); ?></p>
                    <div class="installer-checks">
                        <?php foreach ( $system_checks as $check ) : ?>
                            <div class="installer-check">
                                <span class="installer-badge <?php echo tonbankcard_installer_e( $check['status'] ); ?>"><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( $check['status'] ) ); ?></span>
                                <span><strong><?php echo tonbankcard_installer_e( $check['name'] ); ?></strong><br><?php echo tonbankcard_installer_e( $check['message'] ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php if ( ! empty( $lock_state['allowed'] ) ) : ?>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?php echo tonbankcard_installer_e( $_SESSION['tonbankcard_installer_csrf'] ); ?>">
                        <input type="hidden" name="language" value="<?php echo tonbankcard_installer_e( $installer_language ); ?>">
                        <?php if ( ! empty( $_GET['token'] ) ) : ?>
                            <input type="hidden" name="token" value="<?php echo tonbankcard_installer_e( $_GET['token'] ); ?>">
                        <?php endif; ?>

                        <?php foreach ( tonbankcard_installer_field_groups( $installer_language ) as $group_key => $group ) : ?>
                            <?php $id = 'step-' . $group_key; ?>
                            <section class="installer-section" id="<?php echo tonbankcard_installer_e( $id ); ?>">
                                <h2><?php echo tonbankcard_installer_e( $group['title'] ); ?></h2>
                                <p><?php echo tonbankcard_installer_e( $group['description'] ); ?></p>
                                <div class="installer-grid">
                                    <?php foreach ( $group['fields'] as $key => $definition ) : ?>
                                        <?php tonbankcard_installer_render_field( $key, $definition, $values ); ?>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>

                        <section class="installer-section" id="migrations">
                            <h2><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Step 8. Database migrations and final write' ) ); ?></h2>
                            <p><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Preview the migration list, test credentials, write the generated .env file, and optionally apply all pending migrations.' ) ); ?></p>
                            <div class="installer-migrations">
                                <?php foreach ( $migration_plan as $migration ) : ?>
                                    <div class="installer-migration">
                                        <span class="installer-badge <?php echo 'applied' === $migration['status'] ? 'ok' : 'warn'; ?>"><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( $migration['status'] ) ); ?></span>
                                        <span><strong><?php echo tonbankcard_installer_e( $migration['version'] ); ?></strong><br><?php echo tonbankcard_installer_e( basename( $migration['file'] ) ); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ( '' !== $generated_env ) : ?>
                                <p><strong><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Generated .env preview' ) ); ?></strong></p>
                                <textarea readonly><?php echo tonbankcard_installer_e( $generated_env ); ?></textarea>
                            <?php endif; ?>

                            <div class="installer-actions">
                                <button class="secondary" type="submit" name="action" value="test_database"><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Test database' ) ); ?></button>
                                <button class="secondary" type="submit" name="action" value="run_migrations"><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Run migrations' ) ); ?></button>
                                <button class="secondary" type="submit" name="action" value="preview_env"><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Preview .env' ) ); ?></button>
                                <button type="submit" name="action" value="write_env"><?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Write .env and lock installer' ) ); ?></button>
                                <label class="installer-checkbox">
                                    <input type="checkbox" name="run_migrations_after_write" value="1">
                                    <?php echo tonbankcard_installer_e( tonbankcard_installer_ui_text( 'Run migrations after writing .env' ) ); ?>
                                </label>
                            </div>
                        </section>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
