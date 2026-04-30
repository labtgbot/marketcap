#!/usr/bin/env sh
set -eu

failures=0

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

assert_file .env.example
assert_file docs/runtime-configuration.md
assert_file config/runtime.php

assert_contains .env.example '^TONBANKCARD_PROFILE=local$' 'the local runtime profile'
assert_contains .env.example '^TONBANKCARD_PUBLIC_BASE_URL=' 'the public website base URL'
assert_contains .env.example '^TONBANKCARD_TELEGRAM_BASE_URL=' 'the Telegram Mini App base URL'
assert_contains .env.example '^TONBANKCARD_BOT_USERNAME=' 'the Telegram bot username'
assert_contains .env.example '^TONBANKCARD_BOT_TOKEN=' 'the Telegram bot token'
assert_contains .env.example '^COINGECKO_API_KEY=' 'the CoinGecko API key'
assert_contains .env.example '^GROQ_API_KEY=' 'the Groq API key'
assert_contains .env.example '^UPSTASH_REDIS_REST_URL=' 'the Upstash Redis REST URL'
assert_contains .env.example '^UPSTASH_REDIS_REST_TOKEN=' 'the Upstash Redis REST token'
assert_contains .env.example '^MYSQL_DSN=' 'the MySQL DSN'
assert_contains .env.example '^MYSQL_USER=' 'the MySQL user'
assert_contains .env.example '^MYSQL_PASSWORD=' 'the MySQL password'
assert_contains .env.example '^CHANGENOW_LINK_ID=' 'the ChangeNOW link id'
assert_contains .env.example '^TONBANKCARD_FEATURE_AI=false$' 'the AI feature flag'
assert_contains .env.example '^TONBANKCARD_FEATURE_ALERTS=false$' 'the alerts feature flag'
assert_contains .env.example '^TONBANKCARD_FEATURE_CHANGENOW=false$' 'the ChangeNOW feature flag'
assert_contains .env.example '^TONBANKCARD_FEATURE_TON_CONNECT=false$' 'the TON Connect feature flag'
assert_contains .env.example '^TONBANKCARD_FEATURE_REFERRALS=false$' 'the referrals feature flag'
assert_contains .env.example '^TONBANKCARD_FEATURE_GAMIFICATION=false$' 'the gamification feature flag'
assert_contains .env.example '^TONBANKCARD_FEATURE_PREMIUM=false$' 'the premium feature flag'

assert_contains docs/runtime-configuration.md 'local, staging, production, and telegram' 'all runtime profiles'
assert_contains docs/runtime-configuration.md 'Missing production values' 'production failure behavior'
assert_contains docs/runtime-configuration.md 'Secret values are never rendered' 'secret-safe validation behavior'
assert_contains docs/runtime-configuration.md 'TONBANKCARD_PUBLIC_BASE_URL' 'public website URL configuration'
assert_contains docs/runtime-configuration.md 'TONBANKCARD_TELEGRAM_BASE_URL' 'Telegram Mini App URL configuration'

php_check 'fresh checkout should default to a local development profile' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';

if ( ! defined( 'TONBANKCARD_PROFILE' ) ) {
    fwrite( STDERR, "TONBANKCARD_PROFILE is not defined\n" );
    exit( 1 );
}

if ( TONBANKCARD_PROFILE !== 'local' ) {
    fwrite( STDERR, 'Expected local profile, got ' . TONBANKCARD_PROFILE . "\n" );
    exit( 1 );
}

if ( GECKO_CLIENT_ENV !== 'development' ) {
    fwrite( STDERR, 'Expected development Gecko env for local profile, got ' . GECKO_CLIENT_ENV . "\n" );
    exit( 1 );
}

if ( GECKO_CLIENT_DISPLAY_ERRORS !== TRUE ) {
    fwrite( STDERR, "Local profile should display errors by default\n" );
    exit( 1 );
}
PHP

php_check 'production profile should report missing required values without leaking secrets' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=production \
        TONBANKCARD_BOT_TOKEN='super-secret-token' \
        MYSQL_PASSWORD='another-secret-value' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require GECKO_CLIENT_CONFIG_DIR . '/vuetify.php';
require GECKO_CLIENT_CONFIG_DIR . '/coingecko.php';
require __DIR__ . '/functions.php';

$invalid = validate_runtime_config();
$names = array_map(
    function ( $entry ) {
        return isset( $entry['config'] ) ? $entry['config'] : '';
    },
    $invalid
);

foreach ( [ 'TONBANKCARD_PUBLIC_BASE_URL', 'TONBANKCARD_BOT_USERNAME', 'COINGECKO_API_KEY', 'UPSTASH_REDIS_REST_URL', 'MYSQL_DSN' ] as $required ) {
    if ( ! in_array( $required, $names, TRUE ) ) {
        fwrite( STDERR, "Missing validation entry for $required\n" );
        exit( 1 );
    }
}

$payload = json_encode( $invalid );
foreach ( [ 'super-secret-token', 'another-secret-value' ] as $secret ) {
    if ( strpos( $payload, $secret ) !== FALSE ) {
        fwrite( STDERR, "Validation payload leaked a secret value\n" );
        exit( 1 );
    }
}
PHP

php_check 'telegram profile should keep public website and Mini App URLs separately configurable' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=telegram \
        TONBANKCARD_PUBLIC_BASE_URL='https://marketcap.tonbankcard.com/' \
        TONBANKCARD_TELEGRAM_BASE_URL='https://miniapp.tonbankcard.com/' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require __DIR__ . '/functions.php';

if ( base_url() !== 'https://miniapp.tonbankcard.com/' ) {
    fwrite( STDERR, 'Expected active Telegram base URL, got ' . base_url() . "\n" );
    exit( 1 );
}

if ( empty( $GLOBALS['runtime_config']['urls']['public'] ) || $GLOBALS['runtime_config']['urls']['public'] !== 'https://marketcap.tonbankcard.com/' ) {
    fwrite( STDERR, "Public website URL is not preserved alongside Telegram URL\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Runtime configuration check passed.'
