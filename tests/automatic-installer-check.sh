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

assert_contains .env.example '^TONBANKCARD_INSTALLER_ENABLED=false$' 'the installer enabled flag'
assert_contains .env.example '^TONBANKCARD_INSTALLER_TOKEN=' 'the installer token'
assert_contains README.md 'docs/automatic-hosting-installer\.md' 'the automatic installer documentation link'
assert_contains README.md 'npm run test:automatic-installer' 'the automatic installer npm check'
assert_contains package.json '"test:automatic-installer"' 'the automatic installer npm script'
assert_contains package.json 'tests/automatic-installer-check\.sh' 'the automatic installer shell check'
assert_contains package.json 'test:automatic-installer' 'the aggregate automatic installer check'
assert_contains dev/php/router.php 'is_dir\( \$file \)' 'directory detection for installer routing'
assert_contains dev/php/router.php '\$file \. .*/index[.]php' 'directory index routing for the installer'

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
