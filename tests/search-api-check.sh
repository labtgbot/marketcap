#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-smart-search-api.md

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
        fail "$file does not contain $description"
    fi
}

assert_not_contains() {
    file=$1
    pattern=$2
    description=$3

    if [ ! -f "$file" ]; then
        fail "Cannot inspect missing file: $file"
        return
    fi

    if grep -Eq "$pattern" "$file"; then
        fail "$file still contains $description"
    fi
}

php_check() {
    description=$1
    shift

    if ! "$@"; then
        fail "$description"
    fi
}

assert_file api/search.php
assert_file api/search-refresh.php
assert_file "$doc"

assert_contains "$doc" '^# TONBANKCARD V2 Smart Search API$' 'the smart search API title'
assert_contains "$doc" 'Issue: \[#16\]' 'the issue reference'
assert_contains "$doc" '/api/search' 'the first-party search endpoint'
assert_contains "$doc" 'Redis' 'the Redis-backed index cache'
assert_contains "$doc" 'contract address' 'contract address matching behavior'
assert_contains "$doc" 'fuzzy' 'fuzzy matching behavior'
assert_contains "$doc" 'search_result_selected' 'search click analytics event'
assert_contains "$doc" 'api/search-refresh\.php' 'the scheduled refresh command'
assert_contains README.md 'docs/v2-smart-search-api\.md' 'the smart search documentation link'
assert_contains package.json '"test:search-api"' 'the search API npm script'
assert_contains api/router.php '/api/search' 'the search route registration'
assert_contains dev/js/src/components/search-bar.js '/api/search' 'the browser search endpoint'
assert_contains dev/js/src/components/search-bar.js 'search_result_selected' 'the search click analytics event'
assert_contains dev/js/src/initial.js 'GeckoClient\.analytics' 'the frontend analytics helper'
assert_not_contains dev/js/src/components/search-bar.js 'localstorage\.one' 'the legacy third-party search source'

php_check 'smart search should return BTC from the first-party endpoint with deep links and analytics metadata' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) {
    if ( 'coins/list' === $request['path'] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode(
                [
                    [ 'id' => 'bitcoin', 'symbol' => 'btc', 'name' => 'Bitcoin', 'platforms' => [] ],
                    [ 'id' => 'toncoin', 'symbol' => 'ton', 'name' => 'Toncoin', 'platforms' => [] ],
                    [ 'id' => 'tether', 'symbol' => 'usdt', 'name' => 'Tether', 'platforms' => [ 'the-open-network' => 'EQCxE6mUtQJKFnGfaROTKOt1lZbDiiX1kCixRv7Nw2Id_sDs' ] ],
                ]
            ),
        ];
    }
    if ( 'exchanges/list' === $request['path'] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode( [ [ 'id' => 'binance', 'name' => 'Binance' ] ] ),
        ];
    }
    if ( 'coins/categories/list' === $request['path'] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode( [ [ 'category_id' => 'layer-1', 'name' => 'Layer 1' ] ] ),
        ];
    }
    if ( 'search/trending' === $request['path'] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode( [ 'coins' => [ [ 'item' => [ 'id' => 'toncoin', 'symbol' => 'TON', 'name' => 'Toncoin', 'market_cap_rank' => 12 ] ] ], 'exchanges' => [] ] ),
        ];
    }

    fwrite( STDERR, 'Unexpected upstream search path: ' . $request['path'] . "\n" );
    exit( 1 );
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/search?q=BTC&limit=5&surface=web',
        'headers' => [ 'x-request-id' => 'search-btc' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 200 search response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( ! is_array( $payload ) || TRUE !== $payload['ok'] ) {
    fwrite( STDERR, "Search response is not a success envelope\n" );
    exit( 1 );
}

$results = $payload['data']['results'];
if ( 'bitcoin' !== $results[0]['id'] || 'coin' !== $results[0]['type'] ) {
    fwrite( STDERR, "BTC search did not rank Bitcoin first\n" );
    exit( 1 );
}
if ( '/currency/bitcoin' !== $results[0]['links']['web'] || '/app/coin/bitcoin' !== $results[0]['links']['telegram'] ) {
    fwrite( STDERR, "BTC search result is missing web and Telegram deep links\n" );
    exit( 1 );
}
if ( 'search_result_selected' !== $results[0]['analytics']['event_name'] || 'coin' !== $results[0]['analytics']['result_type'] ) {
    fwrite( STDERR, "BTC search result is missing analytics click metadata\n" );
    exit( 1 );
}
if ( '3-5' !== $payload['meta']['search']['query_length_bucket'] ) {
    fwrite( STDERR, "Search response did not bucket the query length safely\n" );
    exit( 1 );
}
PHP

php_check 'smart search should cover TON assets, contract address matching, fuzzy typos, empty search, and high-cardinality limits' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$coins = [];
for ( $i = 1; $i <= 420; $i++ ) {
    $coins[] = [
        'id'        => 'sample-coin-' . $i,
        'symbol'    => 'sc' . $i,
        'name'      => 'Sample Coin ' . $i,
        'platforms' => [],
    ];
}
$coins[] = [ 'id' => 'bitcoin', 'symbol' => 'btc', 'name' => 'Bitcoin', 'platforms' => [] ];
$coins[] = [ 'id' => 'toncoin', 'symbol' => 'ton', 'name' => 'Toncoin', 'platforms' => [] ];
$coins[] = [ 'id' => 'tether', 'symbol' => 'usdt', 'name' => 'Tether', 'platforms' => [ 'the-open-network' => 'EQCxE6mUtQJKFnGfaROTKOt1lZbDiiX1kCixRv7Nw2Id_sDs' ] ];

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) use ( $coins ) {
    if ( 'coins/list' === $request['path'] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode( $coins ),
        ];
    }
    if ( 'exchanges/list' === $request['path'] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode( [ [ 'id' => 'binance', 'name' => 'Binance' ], [ 'id' => 'okx', 'name' => 'OKX' ] ] ),
        ];
    }
    if ( 'coins/categories/list' === $request['path'] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode( [ [ 'category_id' => 'stablecoins', 'name' => 'Stablecoins' ] ] ),
        ];
    }
    if ( 'search/trending' === $request['path'] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode( [ 'coins' => [ [ 'item' => [ 'id' => 'toncoin', 'symbol' => 'TON', 'name' => 'Toncoin', 'market_cap_rank' => 12 ] ] ], 'exchanges' => [] ] ),
        ];
    }

    fwrite( STDERR, 'Unexpected upstream search path: ' . $request['path'] . "\n" );
    exit( 1 );
};

$cases = [
    'usdt-ton' => '/api/search?q=USDT%20TON&limit=6',
    'contract' => '/api/search?q=EQCxE6mUtQJKFnGfaROTKOt1lZbDiiX1kCixRv7Nw2Id_sDs&limit=3',
    'typo' => '/api/search?q=bitcon&limit=3',
    'empty' => '/api/search?limit=5',
    'high-cardinality' => '/api/search?q=coin&limit=7',
];

$payloads = [];
foreach ( $cases as $name => $path ) {
    $response = tonbankcard_api_handle(
        [
            'method'  => 'GET',
            'path'    => $path,
            'headers' => [ 'x-request-id' => 'search-' . $name ],
            'body'    => '',
        ],
        [],
        $GLOBALS['runtime_config'],
        $test_api
    );
    if ( 200 !== $response['status'] ) {
        fwrite( STDERR, 'Expected 200 for ' . $name . ', got ' . $response['status'] . "\n" );
        exit( 1 );
    }
    $payloads[ $name ] = json_decode( $response['body'], TRUE );
}

if ( 'tether-usd-ton' !== $payloads['usdt-ton']['data']['results'][0]['id'] || 'ton_asset' !== $payloads['usdt-ton']['data']['results'][0]['type'] ) {
    fwrite( STDERR, "USDT TON did not rank the curated TON asset first\n" );
    exit( 1 );
}
if ( empty( $payloads['usdt-ton']['data']['results'][0]['contract_addresses'][0] ) || ! in_array( 'ton_ecosystem', $payloads['usdt-ton']['data']['results'][0]['tags'], TRUE ) ) {
    fwrite( STDERR, "USDT TON result is missing its contract address or TON tag\n" );
    exit( 1 );
}
if ( 'tether-usd-ton' !== $payloads['contract']['data']['results'][0]['id'] ) {
    fwrite( STDERR, "Contract address search did not rank the TON USDT asset first\n" );
    exit( 1 );
}
if ( 'bitcoin' !== $payloads['typo']['data']['results'][0]['id'] ) {
    fwrite( STDERR, "Fuzzy typo search did not rank Bitcoin first\n" );
    exit( 1 );
}
if ( empty( $payloads['empty']['data']['results'] ) || 'action' !== $payloads['empty']['data']['results'][0]['type'] ) {
    fwrite( STDERR, "Empty search did not return popular action results\n" );
    exit( 1 );
}
if ( 7 !== count( $payloads['high-cardinality']['data']['results'] ) ) {
    fwrite( STDERR, "High-cardinality search did not respect the requested limit\n" );
    exit( 1 );
}
PHP

php_check 'smart search should read and write the Redis-backed index cache without leaking the token' \
    env -i PATH="$PATH" \
        UPSTASH_REDIS_REST_URL='https://search-cache.example.upstash.io' \
        UPSTASH_REDIS_REST_TOKEN='redis-secret-token' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$store = [];
$market_calls = 0;

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) use ( &$market_calls ) {
    $market_calls++;
    if ( 'coins/list' === $request['path'] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode( [ [ 'id' => 'toncoin', 'symbol' => 'ton', 'name' => 'Toncoin', 'platforms' => [] ] ] ),
        ];
    }
    if ( 'exchanges/list' === $request['path'] ) {
        return [ 'status' => 200, 'headers' => [], 'body' => '[]' ];
    }
    if ( 'coins/categories/list' === $request['path'] ) {
        return [ 'status' => 200, 'headers' => [], 'body' => '[]' ];
    }
    if ( 'search/trending' === $request['path'] ) {
        return [ 'status' => 200, 'headers' => [], 'body' => '{"coins":[],"exchanges":[]}' ];
    }

    fwrite( STDERR, 'Unexpected upstream search path: ' . $request['path'] . "\n" );
    exit( 1 );
};
$test_api['search']['redis_transport'] = function ( $request ) use ( &$store ) {
    if ( FALSE !== strpos( $request['body'], 'redis-secret-token' ) ) {
        fwrite( STDERR, "Redis token leaked into the command body\n" );
        exit( 1 );
    }
    $command = json_decode( $request['body'], TRUE );
    if ( ! is_array( $command ) ) {
        fwrite( STDERR, "Redis command was not encoded as JSON\n" );
        exit( 1 );
    }
    if ( 'GET' === $command[0] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode( [ 'result' => isset( $store[ $command[1] ] ) ? $store[ $command[1] ] : null ] ),
        ];
    }
    if ( 'SET' === $command[0] ) {
        $store[ $command[1] ] = $command[2];
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => '{"result":"OK"}',
        ];
    }

    fwrite( STDERR, 'Unexpected Redis command: ' . $command[0] . "\n" );
    exit( 1 );
};

$runtime = $GLOBALS['runtime_config'];
$runtime['providers']['upstash']['rest_url'] = 'https://search-cache.example.upstash.io';
$runtime['providers']['upstash']['rest_token'] = 'redis-secret-token';
$runtime['providers']['upstash']['token_configured'] = TRUE;

$first = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/search?q=ton',
        'headers' => [],
        'body'    => '',
    ],
    [],
    $runtime,
    $test_api
);
$second = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/search?q=ton',
        'headers' => [],
        'body'    => '',
    ],
    [],
    $runtime,
    $test_api
);

$first_payload = json_decode( $first['body'], TRUE );
$second_payload = json_decode( $second['body'], TRUE );
if ( empty( $first_payload['meta']['search']['cache_status'] ) || ! in_array( $first_payload['meta']['search']['cache_status'], [ 'refresh', 'miss' ], TRUE ) ) {
    fwrite( STDERR, "First search did not report a cache refresh or miss\n" );
    exit( 1 );
}
if ( 'hit' !== $second_payload['meta']['search']['cache_status'] ) {
    fwrite( STDERR, "Second search did not read from Redis cache\n" );
    exit( 1 );
}
if ( 4 !== $market_calls ) {
    fwrite( STDERR, 'Expected exactly one index refresh with four upstream calls, got ' . $market_calls . "\n" );
    exit( 1 );
}
if ( FALSE !== strpos( $second['body'], 'redis-secret-token' ) ) {
    fwrite( STDERR, "Search response leaked the Redis token\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Smart search API check passed.'
