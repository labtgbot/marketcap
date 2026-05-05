#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-market-data-gateway.md

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

assert_file api/market.php
assert_file "$doc"

assert_contains "$doc" '^# TONBANKCARD V2 Market Data Gateway$' 'the market data gateway title'
assert_contains "$doc" 'Issue: \[#14\]' 'the issue reference'
assert_contains "$doc" '/api/market/coins/markets' 'the coin markets endpoint'
assert_contains "$doc" '/api/market/search/trending' 'the trending endpoint'
assert_contains "$doc" 'without a CoinGecko API key' 'the default no-key behavior'
assert_contains "$doc" 'x-cg-demo-api-key' 'Demo API key header behavior'
assert_contains "$doc" 'x-cg-pro-api-key' 'Pro API key header behavior'
assert_contains "$doc" 'cache_age_seconds' 'freshness metadata'
assert_contains "$doc" 'provider_rate_limited' 'rate-limit error normalization'
assert_contains "$doc" 'provider_timeout' 'timeout error normalization'
assert_contains "$doc" 'invalid_symbol' 'invalid symbol error normalization'
assert_contains package.json '"test:market-gateway"' 'the market gateway npm script'
assert_contains package.json 'test:market-gateway' 'the aggregate market gateway check'
assert_contains README.md 'docs/v2-market-data-gateway\.md' 'the market gateway documentation link'
assert_contains dev/js/src/coingecko.js 'gatewayBaseUrl' 'browser gateway base URL usage'
assert_not_contains dev/js/src/coingecko.js 'https://api\.coingecko\.com/api/v3/' 'direct CoinGecko API base URL'
assert_not_contains dev/js/src/components/search-bar.js 'localstorage\.one' 'direct browser search data source'

php_check 'market gateway should work by default without a CoinGecko API key' \
    env -i PATH="$PATH" \
        COINGECKO_API_PLAN=demo \
        COINGECKO_API_KEY= \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) {
    if ( 0 !== strpos( $request['url'], 'https://api.coingecko.com/api/v3/coins/markets?' ) ) {
        fwrite( STDERR, 'Unexpected public API URL: ' . $request['url'] . "\n" );
        exit( 1 );
    }
    if ( isset( $request['headers']['x-cg-demo-api-key'] ) || isset( $request['headers']['x-cg-pro-api-key'] ) ) {
        fwrite( STDERR, "Keyless request should not include a CoinGecko API key header\n" );
        exit( 1 );
    }
    if ( 'demo' !== $request['plan'] ) {
        fwrite( STDERR, 'Expected default demo/public plan, got ' . $request['plan'] . "\n" );
        exit( 1 );
    }

    return [
        'status'  => 200,
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => json_encode(
            [
                [
                    'id'           => 'toncoin',
                    'symbol'       => 'ton',
                    'last_updated' => '2026-04-30T20:00:00.000Z',
                ],
            ]
        ),
    ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/market/coins/markets?vs_currency=usd',
        'headers' => [ 'x-request-id' => 'market-keyless' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 200 keyless market response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( ! is_array( $payload ) || TRUE !== $payload['ok'] || 'toncoin' !== $payload['data'][0]['id'] ) {
    fwrite( STDERR, "Keyless market response did not preserve provider data\n" );
    exit( 1 );
}
if ( ! empty( $payload['meta']['provider']['credentialed'] ) || 'demo' !== $payload['meta']['provider']['plan'] ) {
    fwrite( STDERR, "Keyless market response should expose uncredentialed Demo/Public provider metadata\n" );
    exit( 1 );
}
PHP

php_check 'market gateway should proxy coin markets through the Demo API header and freshness envelope' \
    env -i PATH="$PATH" \
        COINGECKO_API_PLAN=demo \
        COINGECKO_API_KEY='demo-secret-key' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) {
    if ( 0 !== strpos( $request['url'], 'https://api.coingecko.com/api/v3/coins/markets?' ) ) {
        fwrite( STDERR, 'Unexpected Demo API URL: ' . $request['url'] . "\n" );
        exit( 1 );
    }
    if ( empty( $request['headers']['x-cg-demo-api-key'] ) || 'demo-secret-key' !== $request['headers']['x-cg-demo-api-key'] ) {
        fwrite( STDERR, "Demo API key header was not sent server-side\n" );
        exit( 1 );
    }
    if ( isset( $request['headers']['x-cg-pro-api-key'] ) ) {
        fwrite( STDERR, "Demo request should not include a Pro key header\n" );
        exit( 1 );
    }
    if ( isset( $request['query']['x_cg_demo_api_key'] ) ) {
        fwrite( STDERR, "Client-supplied Demo API key query parameter was forwarded\n" );
        exit( 1 );
    }
    if ( ! isset( $request['query']['vs_currency'] ) || 'usd' !== $request['query']['vs_currency'] ) {
        fwrite( STDERR, "Expected vs_currency query parameter to be forwarded\n" );
        exit( 1 );
    }

    return [
        'status'  => 200,
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => json_encode(
            [
                [
                    'id'           => 'bitcoin',
                    'symbol'       => 'btc',
                    'last_updated' => '2026-04-30T20:00:00.000Z',
                ],
            ]
        ),
    ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/market/coins/markets?vs_currency=usd&x_cg_demo_api_key=browser-secret',
        'headers' => [ 'x-request-id' => 'market-success' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 200 market response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

if ( FALSE !== strpos( $response['body'], 'demo-secret-key' ) || FALSE !== strpos( $response['body'], 'browser-secret' ) ) {
    fwrite( STDERR, "Market response leaked an API key\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( ! is_array( $payload ) || TRUE !== $payload['ok'] || 'bitcoin' !== $payload['data'][0]['id'] ) {
    fwrite( STDERR, "Market response did not preserve provider data in the success envelope\n" );
    exit( 1 );
}
if ( 'market-success' !== $payload['meta']['request_id'] ) {
    fwrite( STDERR, "Market response did not preserve request id\n" );
    exit( 1 );
}
if ( empty( $payload['meta']['provider']['credentialed'] ) || 'demo' !== $payload['meta']['provider']['plan'] ) {
    fwrite( STDERR, "Market response did not include safe Demo provider metadata\n" );
    exit( 1 );
}
if ( ! isset( $payload['meta']['freshness']['cache_age_seconds'] ) || empty( $payload['meta']['freshness']['last_updated_at'] ) ) {
    fwrite( STDERR, "Market response is missing freshness metadata\n" );
    exit( 1 );
}
PHP

php_check 'market gateway should use the Pro API root and header when configured' \
    env -i PATH="$PATH" \
        COINGECKO_API_PLAN=pro \
        COINGECKO_API_KEY='pro-secret-key' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) {
    if ( 'https://pro-api.coingecko.com/api/v3/global' !== $request['url'] ) {
        fwrite( STDERR, 'Unexpected Pro API URL: ' . $request['url'] . "\n" );
        exit( 1 );
    }
    if ( empty( $request['headers']['x-cg-pro-api-key'] ) || 'pro-secret-key' !== $request['headers']['x-cg-pro-api-key'] ) {
        fwrite( STDERR, "Pro API key header was not sent server-side\n" );
        exit( 1 );
    }
    if ( isset( $request['headers']['x-cg-demo-api-key'] ) ) {
        fwrite( STDERR, "Pro request should not include a Demo key header\n" );
        exit( 1 );
    }

    return [
        'status'  => 200,
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => '{"data":{"active_cryptocurrencies":12000},"updated_at":1777581511}',
    ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/market/global',
        'headers' => [],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 200 global response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( 'pro' !== $payload['meta']['provider']['plan'] ) {
    fwrite( STDERR, "Global response did not include Pro provider metadata\n" );
    exit( 1 );
}
if ( FALSE !== strpos( $response['body'], 'pro-secret-key' ) ) {
    fwrite( STDERR, "Global response leaked the Pro API key\n" );
    exit( 1 );
}
PHP

php_check 'market gateway should infer Pro API routing from CoinGecko Pro key prefixes' \
    env -i PATH="$PATH" \
        COINGECKO_API_PLAN=demo \
        COINGECKO_API_KEY='CG-pro-secret-key' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) {
    if ( 'https://pro-api.coingecko.com/api/v3/global' !== $request['url'] ) {
        fwrite( STDERR, 'Expected Pro API URL for CG-prefixed key, got: ' . $request['url'] . "\n" );
        exit( 1 );
    }
    if ( empty( $request['headers']['x-cg-pro-api-key'] ) || 'CG-pro-secret-key' !== $request['headers']['x-cg-pro-api-key'] ) {
        fwrite( STDERR, "CG-prefixed key was not sent with the Pro API header\n" );
        exit( 1 );
    }
    if ( isset( $request['headers']['x-cg-demo-api-key'] ) ) {
        fwrite( STDERR, "CG-prefixed key should not be sent with the Demo API header\n" );
        exit( 1 );
    }
    if ( 'pro' !== $request['plan'] ) {
        fwrite( STDERR, 'Expected inferred Pro plan, got ' . $request['plan'] . "\n" );
        exit( 1 );
    }

    return [
        'status'  => 200,
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => '{"data":{"active_cryptocurrencies":12000},"updated_at":1777581511}',
    ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/market/global',
        'headers' => [],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 200 global response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( ! is_array( $payload ) || 'pro' !== $payload['meta']['provider']['plan'] || empty( $payload['meta']['provider']['credentialed'] ) ) {
    fwrite( STDERR, "Response did not expose safe inferred Pro provider metadata\n" );
    exit( 1 );
}
PHP

php_check 'market gateway should normalize upstream rate limits' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) {
    return [
        'status'  => 429,
        'headers' => [ 'retry-after' => '30' ],
        'body'    => '{"status":{"error_code":429,"error_message":"rate limit"}}',
    ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/market/search/trending',
        'headers' => [],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 429 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 429 normalized response, got ' . $response['status'] . "\n" );
    exit( 1 );
}
if ( ! isset( $response['headers']['Retry-After'] ) || '30' !== $response['headers']['Retry-After'] ) {
    fwrite( STDERR, "Rate-limit response did not preserve Retry-After\n" );
    exit( 1 );
}
$payload = json_decode( $response['body'], TRUE );
if ( 'provider_rate_limited' !== $payload['error']['code'] ) {
    fwrite( STDERR, 'Expected provider_rate_limited error code, got ' . $payload['error']['code'] . "\n" );
    exit( 1 );
}
PHP

php_check 'market gateway should normalize provider timeouts' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) {
    return [
        'error'   => 'timeout',
        'message' => 'operation timed out',
        'status'  => 0,
    ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/market/coins/bitcoin/market_chart?vs_currency=usd&days=30',
        'headers' => [],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 504 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 504 timeout response, got ' . $response['status'] . "\n" );
    exit( 1 );
}
$payload = json_decode( $response['body'], TRUE );
if ( 'provider_timeout' !== $payload['error']['code'] ) {
    fwrite( STDERR, 'Expected provider_timeout error code, got ' . $payload['error']['code'] . "\n" );
    exit( 1 );
}
PHP

php_check 'market gateway should normalize invalid symbols without leaking provider details' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) {
    return [
        'status'  => 404,
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => '{"error":"coin not found"}',
    ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/market/coins/not-a-real-coin',
        'headers' => [],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 404 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 404 invalid symbol response, got ' . $response['status'] . "\n" );
    exit( 1 );
}
$payload = json_decode( $response['body'], TRUE );
if ( 'invalid_symbol' !== $payload['error']['code'] ) {
    fwrite( STDERR, 'Expected invalid_symbol error code, got ' . $payload['error']['code'] . "\n" );
    exit( 1 );
}
if ( 404 !== $payload['error']['details']['upstream_status'] ) {
    fwrite( STDERR, "Invalid symbol response is missing upstream status\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Market gateway check passed.'
