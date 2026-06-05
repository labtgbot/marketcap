#!/usr/bin/env sh
set -eu

failures=0
doc=docs/automatic-hosting-installer.md

fail() {
    printf '%s\n' "FAIL: $1" >&2
    failures=$((failures + 1))
}

assert_file() {
    if [ ! -f "$1" ]; then
        fail "Missing required file: $1"
    fi
}

assert_contains() {
    file=$1
    pattern=$2
    description=$3

    if [ ! -f "$file" ]; then
        fail "Cannot inspect missing file: $file"
        return
    fi

    if ! grep -Eq "$pattern" "$file"; then
        fail "$file does not document $description"
    fi
}

php_check() {
    description=$1
    shift

    if ! "$@"; then
        fail "$description"
    fi
}

assert_file install/index.php
assert_file install/includes/installer.php
assert_file "$doc"
assert_file .env.example
assert_file README.md
assert_file package.json
assert_file dev/php/router.php
assert_file database/migrations/0001_v2_core_schema.up.sql

assert_contains "$doc" '^# TONBANKCARD Automatic Hosting Installer$' 'the automatic installer title'
assert_contains "$doc" 'Issue: \[#84\]' 'the issue reference'
assert_contains "$doc" '/install/' 'the browser installer route'
assert_contains "$doc" 'PHP 8\.1\+' 'the PHP 8.1+ requirement'
assert_contains "$doc" 'MySQL or MariaDB' 'the MySQL or MariaDB requirement'
assert_contains "$doc" 'Telegram Mini App' 'Telegram Mini App configuration'
assert_contains "$doc" '\.env' 'environment file generation'
assert_contains "$doc" 'database migrations' 'database migration execution'
assert_contains "$doc" 'TONBANKCARD_INSTALLER_ENABLED' 'installer lock guidance'
assert_contains "$doc" '## Language Selection' 'installer language selection guidance'
assert_contains "$doc" 'English and Russian' 'English and Russian installer support'
assert_contains "$doc" '## Field Filling Reference' 'field filling reference'
assert_contains "$doc" 'BotFather' 'Telegram BotFather field guidance'
assert_contains "$doc" 'UPSTASH_REDIS_REST_URL' 'Upstash Redis field guidance'
assert_contains "$doc" 'MYSQL_SSL_CA' 'MySQL TLS CA field guidance'
assert_contains "$doc" 'MYSQL_SSL_VERIFY_SERVER_CERT' 'MySQL TLS verification field guidance'
assert_contains "$doc" 'TONBANKCARD_FEATURE_ALERTS' 'feature flag field guidance'

assert_contains .env.example '^TONBANKCARD_INSTALLER_ENABLED=false$' 'the installer enabled flag'
assert_contains .env.example '^TONBANKCARD_INSTALLER_TOKEN=' 'the installer token'
assert_contains README.md 'docs/automatic-hosting-installer\.md' 'the automatic installer documentation link'
assert_contains README.md 'npm run test:automatic-installer' 'the automatic installer npm check'
assert_contains package.json '"test:automatic-installer"' 'the automatic installer npm script'
assert_contains package.json 'tests/automatic-installer-check\.sh' 'the automatic installer shell check'
assert_contains package.json 'test:automatic-installer' 'the aggregate automatic installer check'
assert_contains dev/php/router.php 'is_dir\( \$file \)' 'directory detection for installer routing'
assert_contains dev/php/router.php '\$file \. .*/index[.]php' 'directory index routing for the installer'
assert_contains install/index.php 'name="language"' 'installer language selector'
assert_contains install/includes/installer.php 'function tonbankcard_installer_supported_languages' 'supported installer language helper'
assert_contains install/includes/installer.php 'function tonbankcard_installer_translate' 'installer translation helper'

php_check 'installer should expose English and Russian language copy' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'install/includes/installer.php';

$languages = tonbankcard_installer_supported_languages();
if ( ! isset( $languages['en'], $languages['ru'] ) || 'Русский' !== $languages['ru'] ) {
    fwrite( STDERR, "Installer should support English and Russian language labels\n" );
    exit( 1 );
}

if ( 'en' !== tonbankcard_installer_normalize_language( 'de' ) ) {
    fwrite( STDERR, "Unsupported installer languages should fall back to English\n" );
    exit( 1 );
}

$groups = tonbankcard_installer_field_groups( 'ru' );
$runtime = reset( $groups );
if ( FALSE === strpos( $runtime['title'], 'Шаг 2' ) || FALSE === strpos( $runtime['description'], 'Telegram' ) ) {
    fwrite( STDERR, "Runtime field group should be translated into Russian\n" );
    exit( 1 );
}

$definitions = tonbankcard_installer_field_definitions();
foreach ( [ 'MYSQL_SSL_CA', 'MYSQL_SSL_VERIFY_SERVER_CERT', 'MYSQL_SSL_CERT', 'MYSQL_SSL_KEY', 'MYSQL_SSL_CAPATH', 'MYSQL_SSL_CIPHER' ] as $key ) {
    if ( ! isset( $definitions[ $key ] ) || 'database' !== $definitions[ $key ]['group'] ) {
        fwrite( STDERR, "Installer should expose $key in the database step\n" );
        exit( 1 );
    }
}

if ( 'Проверить базу данных' !== tonbankcard_installer_translate( 'Test database', 'ru' ) ) {
    fwrite( STDERR, "Installer action labels should be translated into Russian\n" );
    exit( 1 );
}
PHP

php_check 'installer should expose every .env.example key as a configurable value' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'install/includes/installer.php';

$fields = tonbankcard_installer_field_keys();
$missing = [];
$lines = file( '.env.example', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
foreach ( $lines as $line ) {
    $line = trim( $line );
    if ( '' === $line || '#' === $line[0] || FALSE === strpos( $line, '=' ) ) {
        continue;
    }

    $key = trim( substr( $line, 0, strpos( $line, '=' ) ) );
    if ( '' !== $key && ! in_array( $key, $fields, TRUE ) ) {
        $missing[] = $key;
    }
}

if ( ! empty( $missing ) ) {
    fwrite( STDERR, 'Installer is missing fields: ' . implode( ', ', $missing ) . "\n" );
    exit( 1 );
}
PHP

php_check 'installer should derive MySQL DSNs and render a locked production .env without leaking display secrets' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'install/includes/installer.php';

$values = tonbankcard_installer_default_values();
$values['TONBANKCARD_PROFILE'] = 'telegram';
$values['TONBANKCARD_PUBLIC_BASE_URL'] = 'https://marketcap.example.com/';
$values['TONBANKCARD_TELEGRAM_BASE_URL'] = 'https://miniapp.example.com/';
$values['TONBANKCARD_BOT_USERNAME'] = 'tonbankcard_bot';
$values['TONBANKCARD_BOT_TOKEN'] = '123456:telegram-secret';
$values['UPSTASH_REDIS_REST_URL'] = 'https://example.upstash.io';
$values['UPSTASH_REDIS_REST_TOKEN'] = 'upstash-secret';
$values['MYSQL_HOST'] = 'db.example.com';
$values['MYSQL_PORT'] = '3307';
$values['MYSQL_DATABASE'] = 'marketcap';
$values['MYSQL_USER'] = 'marketcap_user';
$values['MYSQL_PASSWORD'] = 'mysql secret with spaces';
$values['MYSQL_SSL_CA'] = '/etc/mysql/managed-ca.pem';
$values['MYSQL_SSL_VERIFY_SERVER_CERT'] = 'true';
$values['TONBANKCARD_FEATURE_ALERTS'] = 'true';
$values['TONBANKCARD_ALERT_WORKER_TOKEN'] = '';
$values['TONBANKCARD_SEARCH_REFRESH_TOKEN'] = '';

$prepared = tonbankcard_installer_prepare_values( $values );
$env = tonbankcard_installer_render_env( $prepared );

$expected = [
    'TONBANKCARD_PROFILE=telegram',
    'TONBANKCARD_PUBLIC_BASE_URL=https://marketcap.example.com/',
    'TONBANKCARD_TELEGRAM_BASE_URL=https://miniapp.example.com/',
    'TONBANKCARD_BOT_USERNAME=tonbankcard_bot',
    'MYSQL_DSN=mysql:host=db.example.com;port=3307;dbname=marketcap;charset=utf8mb4',
    'MYSQL_USER=marketcap_user',
    'MYSQL_PASSWORD="mysql secret with spaces"',
    'MYSQL_SSL_CA=/etc/mysql/managed-ca.pem',
    'MYSQL_SSL_VERIFY_SERVER_CERT=true',
    'TONBANKCARD_FEATURE_ALERTS=true',
    'TONBANKCARD_INSTALLER_ENABLED=false',
];

foreach ( $expected as $line ) {
    if ( FALSE === strpos( $env, $line ) ) {
        fwrite( STDERR, "Missing generated env line: $line\n" );
        exit( 1 );
    }
}

foreach ( [ 'TONBANKCARD_ALERT_WORKER_TOKEN=', 'TONBANKCARD_SEARCH_REFRESH_TOKEN=' ] as $prefix ) {
    if ( ! preg_match( '/^' . preg_quote( $prefix, '/' ) . '[A-Za-z0-9._:-]{24,}$/m', $env ) ) {
        fwrite( STDERR, "Expected generated token for $prefix\n" );
        exit( 1 );
    }
}

if ( tonbankcard_installer_display_value( 'TONBANKCARD_BOT_TOKEN', $prepared['TONBANKCARD_BOT_TOKEN'] ) !== 'configured' ) {
    fwrite( STDERR, "Secret display value should be redacted\n" );
    exit( 1 );
}

$validation_errors = tonbankcard_installer_validate_values( $prepared );
if ( ! empty( $validation_errors ) ) {
    fwrite( STDERR, 'Expected complete telegram installer values to validate, got: ' . implode( '; ', $validation_errors ) . "\n" );
    exit( 1 );
}

$invalid = $prepared;
$invalid['TONBANKCARD_PUBLIC_BASE_URL'] = '';
$validation_errors = tonbankcard_installer_validate_values( $invalid );
if ( empty( $validation_errors ) ) {
    fwrite( STDERR, "Expected installer validation to reject missing public URL\n" );
    exit( 1 );
}
PHP

php_check 'installer should neutralize newlines before writing .env values' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'install/includes/installer.php';

$root = sys_get_temp_dir() . '/tonbankcard-installer-env-injection-' . bin2hex( random_bytes( 4 ) );
mkdir( $root, 0700, TRUE );
register_shutdown_function(
    function () use ( $root ) {
        foreach ( [ '/.env', '/.env.example' ] as $file ) {
            if ( is_file( $root . $file ) ) {
                unlink( $root . $file );
            }
        }

        if ( is_dir( $root ) ) {
            rmdir( $root );
        }
    }
);
file_put_contents(
    $root . '/.env.example',
    implode(
        "\n",
        [
            'MYSQL_PASSWORD=',
            'TONBANKCARD_ADMIN_TOKEN=',
            'TONBANKCARD_INSTALLER_ENABLED=false',
            '',
        ]
    )
);

$write = tonbankcard_installer_write_env(
    $root,
    [
        'MYSQL_PASSWORD'          => "secret\r\nTONBANKCARD_ADMIN_TOKEN=attacker",
        'TONBANKCARD_ADMIN_TOKEN' => 'legitimate-token',
    ]
);

if ( empty( $write['ok'] ) ) {
    fwrite( STDERR, "Installer should write the temporary .env fixture\n" );
    exit( 1 );
}

$env = file_get_contents( $root . '/.env' );
if ( FALSE !== strpos( $env, "\rTONBANKCARD_ADMIN_TOKEN=attacker" ) || FALSE !== strpos( $env, "\nTONBANKCARD_ADMIN_TOKEN=attacker" ) ) {
    fwrite( STDERR, "Installer allowed newline injection to create an extra env assignment\n" );
    exit( 1 );
}

if ( 1 !== preg_match_all( '/^TONBANKCARD_ADMIN_TOKEN=/m', $env ) ) {
    fwrite( STDERR, "Installer should render exactly one TONBANKCARD_ADMIN_TOKEN assignment\n" );
    exit( 1 );
}

$parsed = tonbankcard_installer_parse_env_file( $root . '/.env' );
if ( ! isset( $parsed['TONBANKCARD_ADMIN_TOKEN'] ) || 'legitimate-token' !== $parsed['TONBANKCARD_ADMIN_TOKEN'] ) {
    fwrite( STDERR, "Installer parser should keep the legitimate admin token value\n" );
    exit( 1 );
}

if ( FALSE === strpos( $env, 'MYSQL_PASSWORD="secret\r\nTONBANKCARD_ADMIN_TOKEN=attacker"' ) ) {
    fwrite( STDERR, "Installer should preserve the submitted newline only as an escaped literal\n" );
    exit( 1 );
}
PHP

php_check 'installer lock should allow first run and require an explicit token after .env exists' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'install/includes/installer.php';

$root = sys_get_temp_dir() . '/tonbankcard-installer-' . bin2hex( random_bytes( 4 ) );
mkdir( $root, 0700, TRUE );

$state = tonbankcard_installer_lock_state( $root, [] );
if ( empty( $state['allowed'] ) || empty( $state['first_run'] ) ) {
    fwrite( STDERR, "Installer should be allowed before .env exists\n" );
    exit( 1 );
}

file_put_contents( $root . '/.env', "TONBANKCARD_INSTALLER_ENABLED=false\n" );
$state = tonbankcard_installer_lock_state( $root, [] );
if ( ! empty( $state['allowed'] ) || empty( $state['locked'] ) ) {
    fwrite( STDERR, "Installer should be locked when .env disables it\n" );
    exit( 1 );
}

file_put_contents( $root . '/.env', "TONBANKCARD_INSTALLER_ENABLED=true\nTONBANKCARD_INSTALLER_TOKEN=installer-token-12345\n" );
$state = tonbankcard_installer_lock_state( $root, [] );
if ( ! empty( $state['allowed'] ) || empty( $state['token_required'] ) ) {
    fwrite( STDERR, "Installer should require the configured token\n" );
    exit( 1 );
}

$state = tonbankcard_installer_lock_state( $root, [ 'token' => 'installer-token-12345' ] );
if ( empty( $state['allowed'] ) || empty( $state['token_valid'] ) ) {
    fwrite( STDERR, "Installer should allow a matching configured token\n" );
    exit( 1 );
}

unlink( $root . '/.env' );
rmdir( $root );
PHP

php_check 'installer should list migration files before applying them' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'install/includes/installer.php';

$plan = tonbankcard_installer_migration_plan( 'database/migrations', [] );
if ( count( $plan ) < 10 ) {
    fwrite( STDERR, 'Expected at least 10 migration files, got ' . count( $plan ) . "\n" );
    exit( 1 );
}

$first = reset( $plan );
if ( empty( $first['version'] ) || empty( $first['status'] ) || empty( $first['file'] ) ) {
    fwrite( STDERR, "Migration plan entries should include version, status, and file\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Automatic installer check passed.'
