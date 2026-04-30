#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-api-routing-layer.md

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

assert_file api/router.php
assert_file config/api.php
assert_file "$doc"

assert_contains "$doc" '^# TONBANKCARD V2 API Routing Layer$' 'the API routing layer title'
assert_contains "$doc" 'Issue: \[#12\]' 'the issue reference'
assert_contains "$doc" '/api/health' 'the health endpoint'
assert_contains "$doc" '/api/ready' 'the readiness endpoint'
assert_contains "$doc" 'request_id' 'the request id contract'
assert_contains "$doc" 'CORS' 'the CORS policy'
assert_contains "$doc" 'sessions, rate limits, validation, and audit logging' 'the middleware hooks'
assert_contains "$doc" 'secrets stay server-side' 'server-side secret handling'
assert_contains "$doc" 'tests/api-routing-check\.sh' 'the API test convention'

assert_contains package.json '"test:api"' 'the API routing npm script'
assert_contains package.json 'test:api' 'the aggregate API routing check'
assert_contains README.md 'docs/v2-api-routing-layer\.md' 'the API routing documentation link'
assert_contains dev/php/router.php '/api/' 'the local API route front-controller behavior'

php_check 'API health response should use the standard success envelope' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/health',
        'headers' => [ 'x-request-id' => 'test-request-1' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 200 health response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( ! is_array( $payload ) || TRUE !== $payload['ok'] ) {
    fwrite( STDERR, "Health response is not a success envelope\n" );
    exit( 1 );
}

if ( 'test-request-1' !== $payload['meta']['request_id'] ) {
    fwrite( STDERR, "Health response did not preserve the safe request id\n" );
    exit( 1 );
}

foreach ( [ 'app_boot', 'database', 'redis', 'upstream_providers' ] as $check ) {
    if ( ! isset( $payload['data']['checks'][ $check ] ) ) {
        fwrite( STDERR, "Health response is missing $check check\n" );
        exit( 1 );
    }
}
PHP

php_check 'API not found response should use the standard error envelope' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/missing',
        'headers' => [ 'x-request-id' => '<bad value>' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( 404 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 404 missing route response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( ! is_array( $payload ) || FALSE !== $payload['ok'] ) {
    fwrite( STDERR, "Missing route response is not an error envelope\n" );
    exit( 1 );
}

if ( 'not_found' !== $payload['error']['code'] ) {
    fwrite( STDERR, 'Expected not_found error code, got ' . $payload['error']['code'] . "\n" );
    exit( 1 );
}

if ( empty( $payload['meta']['request_id'] ) || '<bad value>' === $payload['meta']['request_id'] ) {
    fwrite( STDERR, "Missing route response did not generate a safe request id\n" );
    exit( 1 );
}
PHP

php_check 'API CORS preflight should return configured CORS headers without a body' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'OPTIONS',
        'path'    => '/api/health',
        'headers' => [
            'origin'                        => 'http://localhost:8888',
            'access-control-request-method' => 'GET',
        ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( 204 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 204 preflight response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

if ( '' !== $response['body'] ) {
    fwrite( STDERR, "Preflight response should not include a JSON body\n" );
    exit( 1 );
}

if ( ! isset( $response['headers']['Access-Control-Allow-Origin'] ) || 'http://localhost:8888' !== $response['headers']['Access-Control-Allow-Origin'] ) {
    fwrite( STDERR, "Preflight response did not include the allowed local origin\n" );
    exit( 1 );
}
PHP

php_check 'API invalid JSON should produce a safe validation error' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/health',
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => '{"bad"',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( 400 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 400 invalid JSON response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( 'invalid_json' !== $payload['error']['code'] ) {
    fwrite( STDERR, 'Expected invalid_json error code, got ' . $payload['error']['code'] . "\n" );
    exit( 1 );
}
PHP

php_check 'API unsupported request bodies should produce a media type error' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/health',
        'headers' => [ 'content-type' => 'text/plain' ],
        'body'    => 'bad',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( 415 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 415 unsupported body response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( 'unsupported_media_type' !== $payload['error']['code'] ) {
    fwrite( STDERR, 'Expected unsupported_media_type error code, got ' . $payload['error']['code'] . "\n" );
    exit( 1 );
}
PHP

php_check 'API readiness should fail safely without leaking configured secrets' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=production \
        TONBANKCARD_BOT_TOKEN='super-secret-bot-token' \
        UPSTASH_REDIS_REST_TOKEN='super-secret-redis-token' \
        MYSQL_PASSWORD='super-secret-db-password' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require GECKO_CLIENT_CONFIG_DIR . '/vuetify.php';
require GECKO_CLIENT_CONFIG_DIR . '/coingecko.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/api/router.php';

$invalid = array_merge(
    validate_constants(),
    validate_runtime_config(),
    validate_site_configs(),
    validate_vuetify_configs()
);

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/ready',
        'headers' => [ 'x-request-id' => 'readiness-check' ],
        'body'    => '',
    ],
    $invalid,
    $GLOBALS['runtime_config'],
    $api
);

if ( 503 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 503 readiness response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( FALSE !== $payload['ok'] || 'not_ready' !== $payload['error']['code'] ) {
    fwrite( STDERR, "Readiness response is not the expected error envelope\n" );
    exit( 1 );
}

foreach ( [ 'super-secret-bot-token', 'super-secret-redis-token', 'super-secret-db-password' ] as $secret ) {
    if ( FALSE !== strpos( $response['body'], $secret ) ) {
        fwrite( STDERR, "Readiness response leaked a secret value\n" );
        exit( 1 );
    }
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'API routing check passed.'
