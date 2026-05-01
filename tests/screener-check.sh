#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-advanced-screener.md

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
assert_file api/screener.php
assert_file templates/routes/screener.php
assert_file dev/js/src/routes/screener.js
assert_file database/migrations/0006_screener_presets.up.sql
assert_file database/migrations/0006_screener_presets.down.sql

assert_contains "$doc" '^# TONBANKCARD V2 Advanced Screener$' 'the screener feature title'
assert_contains "$doc" 'Issue: \[#31\]' 'the issue reference'
assert_contains "$doc" '/api/screener/markets' 'the screener market endpoint'
assert_contains "$doc" '/api/screener/presets' 'the saved preset endpoint'
assert_contains "$doc" 'trusted Telegram sessions' 'trusted-session preset persistence'
assert_contains "$doc" 'CSV export' 'CSV export decision'

assert_contains README.md 'docs/v2-advanced-screener\.md' 'the advanced screener documentation link'
assert_contains package.json '"test:screener"' 'the screener npm script'
assert_contains package.json 'test:screener' 'the aggregate screener check'

assert_contains api/router.php '/api/screener/markets' 'the API route index entry'
assert_contains api/router.php 'tonbankcard_api_screener_is_request' 'the screener API router dispatch'
assert_contains dev/js/source.json '"routes/screener\.js"' 'the screener route source entry'
assert_contains dev/js/src/routes/screener.js 'presetSyncEnabled' 'trusted-session preset UI state'
assert_contains dev/js/src/routes/screener.js 'fetchScreenerResults' 'backend screener result loading'
assert_contains templates/routes/screener.php 'screener-filter-drawer' 'compact mobile filter drawer'
assert_contains templates/routes/screener.php 'screener-results-table' 'sortable screener result table'
assert_contains templates/routes/screener.php 'emptyFilterSummary' 'filter-specific empty state'
assert_contains assets/css/style.css 'screener-filter-grid' 'responsive screener filter layout'

assert_contains database/migrations/0006_screener_presets.up.sql 'CREATE TABLE IF NOT EXISTS `screener_presets`' 'the screener presets table'
assert_contains database/migrations/0006_screener_presets.up.sql 'uniq_screener_presets_user_name' 'the per-user preset name constraint'
assert_contains database/migrations/0006_screener_presets.down.sql 'DROP TABLE IF EXISTS `screener_presets`' 'screener presets rollback'

php_check 'screener API should filter market opportunities through backend datasets' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$transport_calls = [];
$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) use ( &$transport_calls ) {
    $transport_calls[] = [
        'path'  => $request['path'],
        'query' => $request['query'],
    ];

    if ( 'coins/markets' === $request['path'] ) {
        $body = [
            [
                'id' => 'toncoin',
                'symbol' => 'ton',
                'name' => 'Toncoin',
                'market_cap_rank' => 18,
                'current_price' => 6.2,
                'market_cap' => 21000000000,
                'total_volume' => 650000000,
                'price_change_percentage_24h_in_currency' => 6.5,
                'price_change_percentage_7d_in_currency' => 11.2,
                'price_change_percentage_30d_in_currency' => 24.5,
                'last_updated' => '2026-05-01T08:00:00Z',
            ],
            [
                'id' => 'bitcoin',
                'symbol' => 'btc',
                'name' => 'Bitcoin',
                'market_cap_rank' => 1,
                'current_price' => 64000,
                'market_cap' => 1250000000000,
                'total_volume' => 22000000000,
                'price_change_percentage_24h_in_currency' => -1.1,
                'price_change_percentage_7d_in_currency' => 2.1,
                'price_change_percentage_30d_in_currency' => 8.0,
                'last_updated' => '2026-05-01T08:00:00Z',
            ],
            [
                'id' => 'tether',
                'symbol' => 'usdt',
                'name' => 'Tether',
                'market_cap_rank' => 4,
                'current_price' => 1,
                'market_cap' => 110000000000,
                'total_volume' => 60000000000,
                'price_change_percentage_24h_in_currency' => 0.01,
                'price_change_percentage_7d_in_currency' => 0.02,
                'price_change_percentage_30d_in_currency' => 0.03,
                'last_updated' => '2026-05-01T08:00:00Z',
            ],
        ];
        return [ 'status' => 200, 'headers' => [ 'content-type' => 'application/json' ], 'body' => json_encode( $body ) ];
    }

    if ( 'coins/toncoin/tickers' === $request['path'] ) {
        $body = [
            'tickers' => [
                [ 'market' => [ 'identifier' => 'binance', 'name' => 'Binance' ] ],
            ],
        ];
        return [ 'status' => 200, 'headers' => [ 'content-type' => 'application/json' ], 'body' => json_encode( $body ) ];
    }

    fwrite( STDERR, 'Unexpected provider path: ' . $request['path'] . "\n" );
    exit( 1 );
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/screener/markets?vs_currency=usd&market_cap_min=1000000000&volume_min=100000000&change_24h_min=1&rank_max=100&ton_tag=ton_ecosystem&sentiment_min=10&watchlist=watched&watchlist_ids=toncoin,bitcoin&exchange=binance&sort=total_volume&direction=desc',
        'headers' => [ 'x-request-id' => 'screener-filter-test' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected screener response 200, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( ! is_array( $payload ) || TRUE !== $payload['ok'] ) {
    fwrite( STDERR, "Screener response is not a success envelope\n" );
    exit( 1 );
}

$items = $payload['data']['items'];
if ( 1 !== count( $items ) || 'toncoin' !== $items[0]['id'] ) {
    fwrite( STDERR, "Screener filters did not reduce the dataset to watched Binance TON opportunities\n" );
    exit( 1 );
}

$item = $items[0];
if ( empty( $item['ton_asset']['verification_state'] ) || 'verified' !== $item['ton_asset']['verification_state'] ) {
    fwrite( STDERR, "Screener result is missing TON verification context\n" );
    exit( 1 );
}
if ( empty( $item['watchlist']['watched'] ) || empty( $item['exchange_availability']['matched'] ) ) {
    fwrite( STDERR, "Screener result is missing watchlist or exchange availability state\n" );
    exit( 1 );
}
if ( ! isset( $item['sentiment']['score'] ) || $item['sentiment']['score'] < 10 ) {
    fwrite( STDERR, "Screener result did not include a usable sentiment score\n" );
    exit( 1 );
}
if ( 3 !== $payload['data']['summary']['source_count'] || 1 !== $payload['data']['summary']['result_count'] ) {
    fwrite( STDERR, "Screener summary counts are incorrect\n" );
    exit( 1 );
}
if ( 'ton_ecosystem' !== $payload['data']['filters']['ton_tag'] || 'watched' !== $payload['data']['filters']['watchlist'] ) {
    fwrite( STDERR, "Screener response did not echo normalized filters\n" );
    exit( 1 );
}
if ( 'screener' !== $payload['meta']['route_group'] || 'screener-filter-test' !== $payload['meta']['request_id'] ) {
    fwrite( STDERR, "Screener metadata is missing route group or request id\n" );
    exit( 1 );
}
if ( empty( $transport_calls[0]['query']['price_change_percentage'] ) || '24h,7d,30d' !== $transport_calls[0]['query']['price_change_percentage'] ) {
    fwrite( STDERR, "Screener did not request 24h/7d/30d movement from the backend dataset\n" );
    exit( 1 );
}
PHP

php_check 'screener presets should require trusted Telegram sessions' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/screener/presets',
        'headers' => [ 'x-request-id' => 'screener-presets-anonymous' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( 401 !== $response['status'] && 503 !== $response['status'] ) {
    fwrite( STDERR, 'Expected anonymous presets response 401 or storage 503, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
$code = isset( $payload['error']['code'] ) ? $payload['error']['code'] : '';
if ( ! in_array( $code, [ 'screener_session_required', 'screener_storage_unavailable' ], TRUE ) ) {
    fwrite( STDERR, "Preset endpoint returned an unexpected error code: $code\n" );
    exit( 1 );
}
PHP

php_check 'screener presets should reject malformed preset ids before session lookup' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/screener/presets/not-a-preset',
        'headers' => [ 'x-request-id' => 'screener-preset-invalid-path' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( 404 !== $response['status'] ) {
    fwrite( STDERR, 'Expected malformed preset path 404, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
$code = isset( $payload['error']['code'] ) ? $payload['error']['code'] : '';
if ( 'screener_preset_not_found' !== $code ) {
    fwrite( STDERR, "Malformed preset path returned an unexpected error code: $code\n" );
    exit( 1 );
}
PHP

php_check 'screener presets should not persist transient watchlist snapshots' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$normalized = tonbankcard_api_screener_normalize_preset_payload(
    [
        'name' => 'Watched TON momentum',
        'filters' => [
            'watchlist' => 'watched',
            'watchlist_ids' => 'toncoin,bitcoin',
            'ids' => 'toncoin',
            'page' => 3,
            'per_page' => 250,
            'ton_tag' => 'ton_ecosystem',
        ],
    ]
);

if ( empty( $normalized['ok'] ) ) {
    fwrite( STDERR, "Preset payload should normalize successfully\n" );
    exit( 1 );
}

$filters = $normalized['preset']['filters'];
foreach ( [ 'watchlist_ids', 'ids', 'page', 'per_page' ] as $volatile_key ) {
    if ( array_key_exists( $volatile_key, $filters ) ) {
        fwrite( STDERR, "Preset payload persisted volatile filter: $volatile_key\n" );
        exit( 1 );
    }
}

if ( 'watched' !== $filters['watchlist'] || 'ton_ecosystem' !== $filters['ton_tag'] ) {
    fwrite( STDERR, "Preset payload did not preserve stable filters\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Advanced screener check passed.'
