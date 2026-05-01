#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-watchlist-ux-persistence.md

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
        fail "$file does not include $description"
    fi
}

php_check() {
    description=$1
    shift

    if ! "$@"; then
        fail "$description"
    fi
}

assert_file "$doc"
assert_file api/router.php
assert_file dev/js/src/watchlist.js
assert_file dev/js/src/routes/watchlist.js
assert_file templates/routes/watchlist.php
assert_file database/migrations/0003_watchlist_tombstones.up.sql
assert_file database/migrations/0003_watchlist_tombstones.down.sql

assert_contains "$doc" '^# TONBANKCARD V2 Watchlist UX and Persistence$' 'the watchlist feature title'
assert_contains "$doc" 'Issue: \[#23\]' 'the issue reference'
assert_contains "$doc" 'localStorage' 'anonymous browser persistence'
assert_contains "$doc" 'Telegram CloudStorage' 'Telegram CloudStorage persistence'
assert_contains "$doc" 'MySQL' 'trusted session server sync'
assert_contains "$doc" 'conflict' 'sync conflict handling'
assert_contains "$doc" 'tests/watchlist-check\.sh' 'the watchlist test convention'

assert_contains README.md 'docs/v2-watchlist-ux-persistence\.md' 'the watchlist documentation link'
assert_contains package.json '"test:watchlist"' 'the watchlist npm script'
assert_contains package.json 'test:watchlist' 'the aggregate watchlist check'

assert_contains dev/js/source.json '"watchlist\.js"' 'the shared watchlist source entry'
assert_contains dev/js/src/watchlist.js 'CloudStorage' 'Telegram CloudStorage support'
assert_contains dev/js/src/watchlist.js 'watchlist_added' 'watchlist add analytics event'
assert_contains dev/js/src/watchlist.js 'watchlist_removed' 'watchlist remove analytics event'
assert_contains dev/js/src/watchlist.js 'memory' 'unavailable-storage memory fallback'
assert_contains dev/js/src/components/search-bar.js 'toggleWatchlist' 'search result watchlist control'
assert_contains dev/js/src/routes/markets.js 'toggleWatchlist' 'market row watchlist control'
assert_contains dev/js/src/routes/currency.js 'toggleWatchlist' 'coin detail watchlist control'
assert_contains dev/js/src/routes/watchlist.js 'sortKey' 'watchlist route sort control'
assert_contains templates/routes/watchlist.php 'No watched coins yet' 'first-use empty state'
assert_contains templates/routes/watchlist.php 'Market data is stale' 'stale data state'

assert_contains api/router.php '/api/watchlist' 'the watchlist API route'
assert_contains api/router.php 'uniq_watchlist_entries_watchlist_coin' 'server duplicate-prevention note'
assert_contains database/migrations/0001_v2_core_schema.up.sql 'uniq_watchlist_entries_watchlist_coin' 'database duplicate-prevention constraint'
assert_contains database/migrations/0003_watchlist_tombstones.up.sql 'watchlist_tombstones' 'watchlist removal tombstones'
assert_contains api/watchlist.php 'watchlist_tombstones' 'server-side removal tombstone handling'

php_check 'watchlist API should reject anonymous server sync without a trusted session' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/watchlist',
        'headers' => [ 'x-request-id' => 'watchlist-anonymous-test' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( 401 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 401 watchlist response, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( ! is_array( $payload ) || 'watchlist_session_required' !== $payload['error']['code'] ) {
    fwrite( STDERR, "Watchlist anonymous response did not use the expected error code\n" );
    exit( 1 );
}

if ( 'watchlist-anonymous-test' !== $payload['meta']['request_id'] ) {
    fwrite( STDERR, "Watchlist anonymous response did not preserve request id\n" );
    exit( 1 );
}
PHP

php_check 'watchlist entry normalization should deduplicate coins before server sync' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$entries = tonbankcard_api_watchlist_normalize_entries(
    [
        [ 'coin_id' => 'Bitcoin', 'symbol' => 'BTC' ],
        [ 'id' => 'bitcoin', 'symbol' => 'BTC2' ],
        [ 'coin_id' => 'bad id!', 'symbol' => 'BAD' ],
        [ 'coin_id' => 'toncoin', 'symbol' => 'TON' ],
    ]
);

if ( 2 !== count( $entries ) ) {
    fwrite( STDERR, 'Expected two normalized entries, got ' . count( $entries ) . "\n" );
    exit( 1 );
}

if ( 'bitcoin' !== $entries[0]['coin_id'] || 'btc' !== $entries[0]['symbol'] ) {
    fwrite( STDERR, "Duplicate bitcoin entry did not normalize deterministically\n" );
    exit( 1 );
}

if ( 'toncoin' !== $entries[1]['coin_id'] || 'ton' !== $entries[1]['symbol'] ) {
    fwrite( STDERR, "Toncoin entry did not normalize correctly\n" );
    exit( 1 );
}
PHP

php_check 'watchlist removal tombstones should normalize invalid and missing timestamps safely' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$removed = tonbankcard_api_watchlist_normalize_removed(
    [
        'Bitcoin' => '2026-01-02T03:04:05Z',
        'bad id!' => '2026-01-02T03:04:05Z',
        'toncoin' => '',
    ]
);

if ( ! isset( $removed['bitcoin'] ) || '2026-01-02T03:04:05Z' !== $removed['bitcoin'] ) {
    fwrite( STDERR, "Bitcoin tombstone timestamp was not normalized deterministically\n" );
    exit( 1 );
}

if ( isset( $removed['bad id!'] ) || 2 !== count( $removed ) ) {
    fwrite( STDERR, "Invalid tombstone coin ids were not filtered\n" );
    exit( 1 );
}

if ( ! isset( $removed['toncoin'] ) || ! preg_match( '/^20[0-9]{2}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/', $removed['toncoin'] ) ) {
    fwrite( STDERR, "Missing tombstone timestamps should fall back to current UTC time\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Watchlist check passed.'
