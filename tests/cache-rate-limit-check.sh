#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-cache-rate-limit-coalescing.md

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

assert_file api/cache.php
assert_file "$doc"

assert_contains "$doc" '^# TONBANKCARD V2 Upstash Cache, Rate Limits, and Coalescing$' 'the cache/rate-limit title'
assert_contains "$doc" 'Issue: \[#15\]' 'the issue reference'
assert_contains "$doc" 'live_prices' 'live prices TTL'
assert_contains "$doc" 'global_stats' 'global stats TTL'
assert_contains "$doc" 'coin_metadata' 'coin metadata TTL'
assert_contains "$doc" 'charts' 'chart TTL'
assert_contains "$doc" 'search_index' 'search index TTL'
assert_contains "$doc" 'ai_summaries' 'AI summaries TTL'
assert_contains "$doc" 'ton_metadata' 'TON metadata TTL'
assert_contains "$doc" 'anonymous_web' 'anonymous web rate limit policy'
assert_contains "$doc" 'telegram_session' 'Telegram session rate limit policy'
assert_contains "$doc" 'admin_action' 'admin action rate limit policy'
assert_contains "$doc" 'cache_hit_rate' 'cache hit-rate metric'
assert_contains "$doc" 'stale_age_seconds' 'stale age metric'
assert_contains "$doc" 'rate_limit' 'rate-limit count metrics'

assert_contains package.json '"test:cache-rate-limit"' 'the cache/rate-limit npm script'
assert_contains README.md 'docs/v2-cache-rate-limit-coalescing\.md' 'the cache/rate-limit documentation link'
assert_contains config/api.php "'cache'" 'cache configuration'
assert_contains config/api.php "'redis'" 'Redis configuration'
assert_contains config/api.php 'anonymous_web' 'anonymous web rate limit configuration'
assert_contains .env.example '^TONBANKCARD_CACHE_ENABLED=' 'cache feature toggle'
assert_contains .env.example '^TONBANKCARD_RATE_LIMIT_ENABLED=' 'rate-limit feature toggle'

php_check 'market cache should serve miss then hit without leaking Upstash secrets and expose metrics' \
    env -i PATH="$PATH" \
        UPSTASH_REDIS_REST_URL='https://redis.example' \
        UPSTASH_REDIS_REST_TOKEN='redis-secret-token' \
        TONBANKCARD_CACHE_ENABLED=true \
        TONBANKCARD_RATE_LIMIT_ENABLED=false \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$store = [];
$provider_calls = 0;
$test_api = $api;
$test_api['redis']['transport'] = function ( $command ) use ( &$store ) {
    $op = strtoupper( (string) $command[0] );
    if ( 'GET' === $op ) {
        return [ 'ok' => TRUE, 'result' => isset( $store[ $command[1] ] ) ? $store[ $command[1] ] : null ];
    }
    if ( 'SET' === $op ) {
        if ( in_array( 'NX', $command, TRUE ) && array_key_exists( $command[1], $store ) ) {
            return [ 'ok' => TRUE, 'result' => null ];
        }
        $store[ $command[1] ] = (string) $command[2];
        return [ 'ok' => TRUE, 'result' => 'OK' ];
    }
    if ( 'INCR' === $op ) {
        $store[ $command[1] ] = (string) ( (int) ( isset( $store[ $command[1] ] ) ? $store[ $command[1] ] : 0 ) + 1 );
        return [ 'ok' => TRUE, 'result' => (int) $store[ $command[1] ] ];
    }
    if ( 'EXPIRE' === $op ) {
        return [ 'ok' => TRUE, 'result' => 1 ];
    }
    if ( 'MGET' === $op ) {
        $values = [];
        foreach ( array_slice( $command, 1 ) as $key ) {
            $values[] = isset( $store[ $key ] ) ? $store[ $key ] : null;
        }
        return [ 'ok' => TRUE, 'result' => $values ];
    }
    return [ 'ok' => TRUE, 'result' => null ];
};
$test_api['market_data']['transport'] = function ( $request ) use ( &$provider_calls ) {
    $provider_calls++;
    return [
        'status'  => 200,
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => '[{"id":"bitcoin","symbol":"btc","last_updated":"2026-04-30T20:00:00.000Z"}]',
    ];
};

$request = [
    'method'  => 'GET',
    'path'    => '/api/market/coins/markets?vs_currency=usd',
    'headers' => [ 'x-request-id' => 'cache-test', 'x-forwarded-for' => '203.0.113.10' ],
    'body'    => '',
];

$first = tonbankcard_api_handle( $request, [], $GLOBALS['runtime_config'], $test_api );
$second = tonbankcard_api_handle( $request, [], $GLOBALS['runtime_config'], $test_api );

if ( 200 !== $first['status'] || 200 !== $second['status'] ) {
    fwrite( STDERR, "Expected cacheable market responses to succeed\n" );
    exit( 1 );
}
if ( 1 !== $provider_calls ) {
    fwrite( STDERR, 'Expected one provider call after miss+hit, got ' . $provider_calls . "\n" );
    exit( 1 );
}
if ( FALSE !== strpos( $first['body'] . $second['body'], 'redis-secret-token' ) ) {
    fwrite( STDERR, "Cache responses leaked the Upstash token\n" );
    exit( 1 );
}
$first_payload = json_decode( $first['body'], TRUE );
$second_payload = json_decode( $second['body'], TRUE );
if ( 'miss' !== $first_payload['meta']['freshness']['cache_status'] ) {
    fwrite( STDERR, 'Expected first response cache_status miss, got ' . $first_payload['meta']['freshness']['cache_status'] . "\n" );
    exit( 1 );
}
if ( 'hit' !== $second_payload['meta']['freshness']['cache_status'] ) {
    fwrite( STDERR, 'Expected second response cache_status hit, got ' . $second_payload['meta']['freshness']['cache_status'] . "\n" );
    exit( 1 );
}

$metrics = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/metrics',
        'headers' => [ 'x-request-id' => 'metrics-test' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);
if ( 200 !== $metrics['status'] ) {
    fwrite( STDERR, 'Expected 200 metrics response, got ' . $metrics['status'] . "\n" );
    exit( 1 );
}
$metrics_payload = json_decode( $metrics['body'], TRUE );
if ( ! isset( $metrics_payload['data']['cache']['cache_hit_rate'] ) || $metrics_payload['data']['cache']['hits'] < 1 || $metrics_payload['data']['cache']['misses'] < 1 ) {
    fwrite( STDERR, "Metrics response is missing cache hit-rate counters\n" );
    exit( 1 );
}
PHP

php_check 'market cache should serve stale data when upstream fallback is needed' \
    env -i PATH="$PATH" \
        UPSTASH_REDIS_REST_URL='https://redis.example' \
        UPSTASH_REDIS_REST_TOKEN='redis-secret-token' \
        TONBANKCARD_CACHE_ENABLED=true \
        TONBANKCARD_RATE_LIMIT_ENABLED=false \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$store = [];
$provider_ok = TRUE;
$test_api = $api;
$test_api['redis']['transport'] = function ( $command ) use ( &$store ) {
    $op = strtoupper( (string) $command[0] );
    if ( 'GET' === $op ) {
        return [ 'ok' => TRUE, 'result' => isset( $store[ $command[1] ] ) ? $store[ $command[1] ] : null ];
    }
    if ( 'SET' === $op ) {
        if ( in_array( 'NX', $command, TRUE ) && array_key_exists( $command[1], $store ) ) {
            return [ 'ok' => TRUE, 'result' => null ];
        }
        $store[ $command[1] ] = (string) $command[2];
        return [ 'ok' => TRUE, 'result' => 'OK' ];
    }
    if ( 'INCR' === $op ) {
        $store[ $command[1] ] = (string) ( (int) ( isset( $store[ $command[1] ] ) ? $store[ $command[1] ] : 0 ) + 1 );
        return [ 'ok' => TRUE, 'result' => (int) $store[ $command[1] ] ];
    }
    if ( 'EXPIRE' === $op ) {
        return [ 'ok' => TRUE, 'result' => 1 ];
    }
    return [ 'ok' => TRUE, 'result' => null ];
};
$test_api['market_data']['transport'] = function ( $request ) use ( &$provider_ok ) {
    if ( ! $provider_ok ) {
        return [ 'error' => 'timeout', 'status' => 0 ];
    }

    return [
        'status'  => 200,
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => '{"data":{"active_cryptocurrencies":12000},"updated_at":1777581511}',
    ];
};

$request = [
    'method'  => 'GET',
    'path'    => '/api/market/global',
    'headers' => [ 'x-request-id' => 'stale-seed' ],
    'body'    => '',
];
$seed = tonbankcard_api_handle( $request, [], $GLOBALS['runtime_config'], $test_api );
if ( 200 !== $seed['status'] ) {
    fwrite( STDERR, "Expected seed response to succeed\n" );
    exit( 1 );
}

$cache_key = null;
foreach ( array_keys( $store ) as $key ) {
    if ( FALSE !== strpos( $key, ':cache:' ) ) {
        $cache_key = $key;
        break;
    }
}
if ( null === $cache_key ) {
    fwrite( STDERR, "Expected cache entry to be written\n" );
    exit( 1 );
}
$entry = json_decode( $store[ $cache_key ], TRUE );
$entry['stored_at'] = time() - 120;
$entry['expires_at'] = time() - 60;
$entry['stale_until'] = time() + 3600;
$store[ $cache_key ] = json_encode( $entry );
$provider_ok = FALSE;

$fallback = tonbankcard_api_handle( $request, [], $GLOBALS['runtime_config'], $test_api );
if ( 200 !== $fallback['status'] ) {
    fwrite( STDERR, 'Expected stale fallback response to succeed, got ' . $fallback['status'] . "\n" );
    exit( 1 );
}
$payload = json_decode( $fallback['body'], TRUE );
if ( 'stale' !== $payload['meta']['freshness']['cache_status'] || empty( $payload['meta']['freshness']['upstream_fallback'] ) ) {
    fwrite( STDERR, "Expected stale upstream fallback metadata\n" );
    exit( 1 );
}
if ( empty( $payload['meta']['freshness']['stale_age_seconds'] ) ) {
    fwrite( STDERR, "Expected stale age metadata\n" );
    exit( 1 );
}
PHP

php_check 'rate limiter should classify identities, return clear 429 responses, and count blocked requests' \
    env -i PATH="$PATH" \
        UPSTASH_REDIS_REST_URL='https://redis.example' \
        UPSTASH_REDIS_REST_TOKEN='redis-secret-token' \
        TONBANKCARD_CACHE_ENABLED=false \
        TONBANKCARD_RATE_LIMIT_ENABLED=true \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$store = [];
$test_api = $api;
$test_api['rate_limit']['policies']['anonymous_web']['max_requests'] = 1;
$test_api['rate_limit']['policies']['anonymous_web']['window_seconds'] = 60;
$test_api['redis']['transport'] = function ( $command ) use ( &$store ) {
    $op = strtoupper( (string) $command[0] );
    if ( 'INCR' === $op ) {
        $store[ $command[1] ] = (string) ( (int) ( isset( $store[ $command[1] ] ) ? $store[ $command[1] ] : 0 ) + 1 );
        return [ 'ok' => TRUE, 'result' => (int) $store[ $command[1] ] ];
    }
    if ( 'EXPIRE' === $op ) {
        return [ 'ok' => TRUE, 'result' => 1 ];
    }
    if ( 'TTL' === $op ) {
        return [ 'ok' => TRUE, 'result' => 60 ];
    }
    if ( 'MGET' === $op ) {
        $values = [];
        foreach ( array_slice( $command, 1 ) as $key ) {
            $values[] = isset( $store[ $key ] ) ? $store[ $key ] : null;
        }
        return [ 'ok' => TRUE, 'result' => $values ];
    }
    return [ 'ok' => TRUE, 'result' => null ];
};

$anonymous_request = [
    'method'  => 'GET',
    'path'    => '/api',
    'headers' => [
        'x-forwarded-for' => '203.0.113.20',
        'user-agent'      => 'RateLimitTest',
    ],
    'body'    => '',
];
$first = tonbankcard_api_handle( $anonymous_request, [], $GLOBALS['runtime_config'], $test_api );
$second = tonbankcard_api_handle( $anonymous_request, [], $GLOBALS['runtime_config'], $test_api );
if ( 200 !== $first['status'] ) {
    fwrite( STDERR, 'Expected first anonymous request to pass, got ' . $first['status'] . "\n" );
    exit( 1 );
}
if ( 429 !== $second['status'] ) {
    fwrite( STDERR, 'Expected second anonymous request to be rate-limited, got ' . $second['status'] . "\n" );
    exit( 1 );
}
$payload = json_decode( $second['body'], TRUE );
if ( 'rate_limited' !== $payload['error']['code'] || FALSE === strpos( $payload['error']['message'], 'Too many requests' ) ) {
    fwrite( STDERR, "Rate-limit response is not clear\n" );
    exit( 1 );
}
if ( ! isset( $second['headers']['Retry-After'] ) || ! isset( $second['headers']['X-RateLimit-Limit'] ) || '1' !== $second['headers']['X-RateLimit-Limit'] ) {
    fwrite( STDERR, "Rate-limit response is missing standard headers\n" );
    exit( 1 );
}
if ( FALSE !== strpos( $second['body'], 'redis-secret-token' ) ) {
    fwrite( STDERR, "Rate-limit response leaked the Upstash token\n" );
    exit( 1 );
}

$telegram_identity = tonbankcard_api_rate_limit_identity(
    [
        'path'    => '/api/telegram/session',
        'headers' => [ 'cookie' => 'tonbankcard_session=' . str_repeat( 'a', 64 ) ],
    ]
);
$admin_identity = tonbankcard_api_rate_limit_identity(
    [
        'path'    => '/api/admin/settings',
        'headers' => [ 'authorization' => 'Bearer admin-token' ],
    ]
);
if ( 'telegram_session' !== $telegram_identity['policy'] ) {
    fwrite( STDERR, 'Expected Telegram policy, got ' . $telegram_identity['policy'] . "\n" );
    exit( 1 );
}
if ( 'admin_action' !== $admin_identity['policy'] ) {
    fwrite( STDERR, 'Expected admin policy, got ' . $admin_identity['policy'] . "\n" );
    exit( 1 );
}

$metrics = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/metrics',
        'headers' => [ 'x-forwarded-for' => '203.0.113.21' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);
$metrics_payload = json_decode( $metrics['body'], TRUE );
if ( ! isset( $metrics_payload['data']['rate_limit']['blocked'] ) || $metrics_payload['data']['rate_limit']['blocked'] < 1 ) {
    fwrite( STDERR, "Metrics response is missing rate-limit blocked count\n" );
    exit( 1 );
}
PHP

php_check 'anonymous rate limiter should not mint a fresh bucket when User-Agent changes' \
    env -i PATH="$PATH" \
        UPSTASH_REDIS_REST_URL='https://redis.example' \
        UPSTASH_REDIS_REST_TOKEN='redis-secret-token' \
        TONBANKCARD_CACHE_ENABLED=false \
        TONBANKCARD_RATE_LIMIT_ENABLED=true \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$store = [];
$rate_limit_keys = [];
$test_api = $api;
$test_api['rate_limit']['policies']['anonymous_web']['max_requests'] = 1;
$test_api['rate_limit']['policies']['anonymous_web']['window_seconds'] = 60;
$test_api['redis']['transport'] = function ( $command ) use ( &$store, &$rate_limit_keys ) {
    $op = strtoupper( (string) $command[0] );
    if ( 'INCR' === $op ) {
        if ( FALSE !== strpos( $command[1], ':rate_limit:anonymous_web:' ) ) {
            $rate_limit_keys[ $command[1] ] = TRUE;
        }
        $store[ $command[1] ] = (string) ( (int) ( isset( $store[ $command[1] ] ) ? $store[ $command[1] ] : 0 ) + 1 );
        return [ 'ok' => TRUE, 'result' => (int) $store[ $command[1] ] ];
    }
    if ( 'EXPIRE' === $op ) {
        return [ 'ok' => TRUE, 'result' => 1 ];
    }
    if ( 'TTL' === $op ) {
        return [ 'ok' => TRUE, 'result' => 60 ];
    }
    return [ 'ok' => TRUE, 'result' => null ];
};

$request = [
    'method'  => 'GET',
    'path'    => '/api',
    'headers' => [
        'x-forwarded-for' => '203.0.113.30',
        'user-agent'      => 'RotatingUA/1',
    ],
    'body'    => '',
];
$first = tonbankcard_api_handle( $request, [], $GLOBALS['runtime_config'], $test_api );
$request['headers']['user-agent'] = 'RotatingUA/2';
$second = tonbankcard_api_handle( $request, [], $GLOBALS['runtime_config'], $test_api );

if ( 200 !== $first['status'] ) {
    fwrite( STDERR, 'Expected first anonymous request to pass, got ' . $first['status'] . "\n" );
    exit( 1 );
}
if ( 429 !== $second['status'] ) {
    fwrite( STDERR, 'Expected User-Agent rotation to keep the same anonymous bucket, got ' . $second['status'] . "\n" );
    exit( 1 );
}
if ( 1 !== count( $rate_limit_keys ) ) {
    fwrite( STDERR, 'Expected one anonymous rate-limit bucket, got ' . count( $rate_limit_keys ) . "\n" );
    exit( 1 );
}
PHP

php_check 'auth-adjacent rate limits should not mint buckets from rotating invalid credentials' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$admin_a = tonbankcard_api_rate_limit_identity(
    [
        'path'    => '/api/admin/settings',
        'headers' => [
            'authorization'   => 'Bearer invalid-admin-token-a',
            'x-forwarded-for' => '203.0.113.50',
        ],
    ]
);
$admin_b = tonbankcard_api_rate_limit_identity(
    [
        'path'    => '/api/admin/settings',
        'headers' => [
            'authorization'   => 'Bearer invalid-admin-token-b',
            'x-forwarded-for' => '203.0.113.50',
        ],
    ]
);
$admin_other_ip = tonbankcard_api_rate_limit_identity(
    [
        'path'    => '/api/admin/settings',
        'headers' => [
            'authorization'   => 'Bearer invalid-admin-token-b',
            'x-forwarded-for' => '203.0.113.51',
        ],
    ]
);

if ( 'admin_action' !== $admin_a['policy'] || 'admin_action' !== $admin_b['policy'] ) {
    fwrite( STDERR, "Expected admin credentials to use admin_action policy\n" );
    exit( 1 );
}
if ( $admin_a['key'] !== $admin_b['key'] ) {
    fwrite( STDERR, "Rotating invalid admin credentials created separate rate-limit buckets\n" );
    exit( 1 );
}
if ( $admin_a['key'] === $admin_other_ip['key'] ) {
    fwrite( STDERR, "Admin pre-auth rate-limit bucket did not include the request IP\n" );
    exit( 1 );
}

$session_a = tonbankcard_api_rate_limit_identity(
    [
        'path'    => '/api/watchlist',
        'headers' => [
            'x-tonbankcard-session' => 'invalid-session-token-a',
            'x-forwarded-for'       => '203.0.113.60',
        ],
    ]
);
$session_b = tonbankcard_api_rate_limit_identity(
    [
        'path'    => '/api/watchlist',
        'headers' => [
            'x-tonbankcard-session' => 'invalid-session-token-b',
            'x-forwarded-for'       => '203.0.113.60',
        ],
    ]
);

if ( 'telegram_session' !== $session_a['policy'] || 'telegram_session' !== $session_b['policy'] ) {
    fwrite( STDERR, "Expected session credentials to use telegram_session policy\n" );
    exit( 1 );
}
if ( $session_a['key'] !== $session_b['key'] ) {
    fwrite( STDERR, "Rotating invalid session credentials created separate rate-limit buckets\n" );
    exit( 1 );
}
PHP

php_check 'rate limiter should fall back to bounded in-process enforcement when Redis is unavailable' \
    env -i PATH="$PATH" \
        UPSTASH_REDIS_REST_URL='https://redis.example' \
        UPSTASH_REDIS_REST_TOKEN='redis-secret-token' \
        TONBANKCARD_CACHE_ENABLED=false \
        TONBANKCARD_RATE_LIMIT_ENABLED=true \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['rate_limit']['policies']['anonymous_web']['max_requests'] = 1;
$test_api['rate_limit']['policies']['anonymous_web']['window_seconds'] = 60;
$test_api['redis']['transport'] = function ( $command ) {
    return [ 'ok' => FALSE, 'error' => 'redis_outage' ];
};

$request = [
    'method'  => 'GET',
    'path'    => '/api',
    'headers' => [
        'x-forwarded-for' => '203.0.113.40',
        'user-agent'      => 'RedisOutage',
    ],
    'body'    => '',
];
$first = tonbankcard_api_handle( $request, [], $GLOBALS['runtime_config'], $test_api );
$second = tonbankcard_api_handle( $request, [], $GLOBALS['runtime_config'], $test_api );

if ( 200 !== $first['status'] ) {
    fwrite( STDERR, 'Expected first Redis-outage request to pass under fallback limiter, got ' . $first['status'] . "\n" );
    exit( 1 );
}
if ( 429 !== $second['status'] ) {
    fwrite( STDERR, 'Expected Redis outage fallback limiter to block the second request, got ' . $second['status'] . "\n" );
    exit( 1 );
}
if ( ! isset( $second['headers']['Retry-After'] ) || ! isset( $second['headers']['X-RateLimit-Limit'] ) || '1' !== $second['headers']['X-RateLimit-Limit'] ) {
    fwrite( STDERR, "Fallback rate-limit response is missing standard headers\n" );
    exit( 1 );
}
PHP

php_check 'coalescing should wait for a duplicate market request instead of starting another provider call when cached data appears' \
    env -i PATH="$PATH" \
        UPSTASH_REDIS_REST_URL='https://redis.example' \
        UPSTASH_REDIS_REST_TOKEN='redis-secret-token' \
        TONBANKCARD_CACHE_ENABLED=true \
        TONBANKCARD_RATE_LIMIT_ENABLED=false \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$store = [];
$cache_gets = 0;
$provider_calls = 0;
$test_api = $api;
$test_api['cache']['coalesce']['wait_ms'] = 50;
$test_api['cache']['coalesce']['poll_ms'] = 1;
$test_api['redis']['transport'] = function ( $command ) use ( &$store, &$cache_gets ) {
    $op = strtoupper( (string) $command[0] );
    if ( 'GET' === $op ) {
        if ( FALSE !== strpos( $command[1], ':cache:' ) ) {
            $cache_gets++;
            if ( $cache_gets > 1 ) {
                return [
                    'ok'     => TRUE,
                    'result' => json_encode(
                        [
                            'stored_at'   => time(),
                            'expires_at'  => time() + 60,
                            'stale_until' => time() + 3600,
                            'data'        => [ [ 'id' => 'coalesced-bitcoin', 'last_updated' => '2026-04-30T20:00:00.000Z' ] ],
                            'upstream'    => [ 'status' => 200 ],
                        ]
                    ),
                ];
            }
        }
        return [ 'ok' => TRUE, 'result' => isset( $store[ $command[1] ] ) ? $store[ $command[1] ] : null ];
    }
    if ( 'SET' === $op && in_array( 'NX', $command, TRUE ) ) {
        return [ 'ok' => TRUE, 'result' => null ];
    }
    if ( 'INCR' === $op ) {
        $store[ $command[1] ] = (string) ( (int) ( isset( $store[ $command[1] ] ) ? $store[ $command[1] ] : 0 ) + 1 );
        return [ 'ok' => TRUE, 'result' => (int) $store[ $command[1] ] ];
    }
    if ( 'EXPIRE' === $op ) {
        return [ 'ok' => TRUE, 'result' => 1 ];
    }
    return [ 'ok' => TRUE, 'result' => null ];
};
$test_api['market_data']['transport'] = function ( $request ) use ( &$provider_calls ) {
    $provider_calls++;
    return [ 'status' => 200, 'headers' => [], 'body' => '[]' ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/market/coins/markets?vs_currency=usd',
        'headers' => [ 'x-request-id' => 'coalesce-test' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);
if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected coalesced response to succeed, got ' . $response['status'] . "\n" );
    exit( 1 );
}
$payload = json_decode( $response['body'], TRUE );
if ( 'coalesced-bitcoin' !== $payload['data'][0]['id'] || 'hit' !== $payload['meta']['freshness']['cache_status'] || empty( $payload['meta']['freshness']['coalesced'] ) ) {
    fwrite( STDERR, "Expected coalesced cache-hit response\n" );
    exit( 1 );
}
if ( 0 !== $provider_calls ) {
    fwrite( STDERR, 'Expected no duplicate provider call, got ' . $provider_calls . "\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Upstash cache, rate-limit, and coalescing check passed.'
