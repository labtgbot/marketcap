#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-telegram-session.md

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
assert_contains "$doc" '^# TONBANKCARD V2 Telegram Session Validation$' 'the Telegram session title'
assert_contains "$doc" '/api/telegram/session' 'the Telegram session endpoint'
assert_contains "$doc" 'Telegram.WebApp.initData' 'raw initData validation input'
assert_contains "$doc" 'HMAC-SHA-256' 'the Telegram signature validation algorithm'
assert_contains "$doc" 'Browser fallback' 'local browser fallback behavior'

php_check 'valid Telegram initData should create a trusted session with a minimal response' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_BOT_TOKEN='123456:telegram-session-test-token' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

function test_init_data( array $fields, string $bot_token ) {
    ksort( $fields, SORT_STRING );
    $data_check_string = implode(
        "\n",
        array_map(
            function ( $key ) use ( $fields ) {
                return $key . '=' . $fields[ $key ];
            },
            array_keys( $fields )
        )
    );
    $secret_key = hash_hmac( 'sha256', $bot_token, 'WebAppData', TRUE );
    $fields['hash'] = hash_hmac( 'sha256', $data_check_string, $secret_key );

    return http_build_query( $fields, '', '&', PHP_QUERY_RFC3986 );
}

$store_dir = sys_get_temp_dir() . '/tonbankcard-test-valid-session-' . getmypid();
if ( ! is_dir( $store_dir ) && ! mkdir( $store_dir, 0700, TRUE ) ) {
    fwrite( STDERR, "Could not create private session store directory\n" );
    exit( 1 );
}
chmod( $store_dir, 0700 );
$store = $store_dir . '/sessions.json';
@unlink( $store );
register_shutdown_function(
    function () use ( $store, $store_dir ) {
        @unlink( $store );
        @rmdir( $store_dir );
    }
);

$runtime = $GLOBALS['runtime_config'];
$config = $api;
$config['telegram_session']['local_session_store_path'] = $store;

$init_data = test_init_data(
    [
        'query_id'      => 'AAHdF6IQAAAAAN0XohDhrOrc',
        'user'          => json_encode(
            [
                'id'            => 1234567890123,
                'first_name'    => 'ShouldNotLeak',
                'username'      => 'should_not_leak',
                'language_code' => 'en',
                'is_premium'    => TRUE,
            ],
            JSON_UNESCAPED_SLASHES
        ),
        'auth_date'     => (string) time(),
        'start_param'   => 'portfolio',
        'chat_type'     => 'private',
        'chat_instance' => 'chat-instance-secret',
    ],
    $runtime['telegram']['bot_token']
);

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/telegram/session',
        'headers' => [
            'content-type' => 'application/json',
            'user-agent'   => 'TelegramSessionTest/1.0',
            'x-forwarded-for' => '127.0.0.1',
        ],
        'body'    => json_encode( [ 'initData' => $init_data ], JSON_UNESCAPED_SLASHES ),
    ],
    [],
    $runtime,
    $config
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 200 trusted session response, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( ! is_array( $payload ) || TRUE !== $payload['ok'] ) {
    fwrite( STDERR, "Trusted session response is not a success envelope\n" );
    exit( 1 );
}

$data = $payload['data'];
if ( 'telegram_validated' !== $data['session']['state'] || 'telegram_mini_app' !== $data['session']['surface'] ) {
    fwrite( STDERR, "Trusted session did not expose the expected state and surface\n" );
    exit( 1 );
}

if ( '1234567890123' !== $data['user']['telegram_user_id'] || 'en' !== $data['user']['language_code'] || TRUE !== $data['user']['is_premium'] ) {
    fwrite( STDERR, "Trusted session did not expose the minimum Telegram user fields\n" );
    exit( 1 );
}

if ( 'portfolio' !== $data['launch']['start_param'] || 'private' !== $data['launch']['chat_type'] || TRUE !== $data['launch']['chat_instance_present'] ) {
    fwrite( STDERR, "Trusted session did not expose the safe launch context\n" );
    exit( 1 );
}

if ( FALSE === strpos( json_encode( $response['headers'] ), 'HttpOnly' ) ) {
    fwrite( STDERR, "Trusted session response did not set an HttpOnly session cookie\n" );
    exit( 1 );
}

$body = $response['body'];
foreach ( [ 'ShouldNotLeak', 'should_not_leak', 'chat-instance-secret', 'query_id', 'hash=' ] as $leak ) {
    if ( FALSE !== strpos( $body, $leak ) ) {
        fwrite( STDERR, "Trusted session response leaked non-minimum Telegram data: $leak\n" );
        exit( 1 );
    }
}

if ( ! is_readable( $store ) ) {
    fwrite( STDERR, "Local session store was not written\n" );
    exit( 1 );
}
PHP

php_check 'tampered Telegram initData should be rejected' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_BOT_TOKEN='123456:telegram-session-test-token' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

function test_init_data( array $fields, string $bot_token ) {
    ksort( $fields, SORT_STRING );
    $data_check_string = implode(
        "\n",
        array_map(
            function ( $key ) use ( $fields ) {
                return $key . '=' . $fields[ $key ];
            },
            array_keys( $fields )
        )
    );
    $secret_key = hash_hmac( 'sha256', $bot_token, 'WebAppData', TRUE );
    $fields['hash'] = hash_hmac( 'sha256', $data_check_string, $secret_key );

    return http_build_query( $fields, '', '&', PHP_QUERY_RFC3986 );
}

$init_data = test_init_data(
    [
        'user'      => json_encode( [ 'id' => 555, 'language_code' => 'en' ], JSON_UNESCAPED_SLASHES ),
        'auth_date' => (string) time(),
    ],
    $GLOBALS['runtime_config']['telegram']['bot_token']
);
$init_data = str_replace( '%22555%22', '%22666%22', $init_data );
$init_data = str_replace( '555', '666', $init_data );

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/telegram/session',
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => json_encode( [ 'initData' => $init_data ], JSON_UNESCAPED_SLASHES ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

$payload = json_decode( $response['body'], TRUE );
if ( 401 !== $response['status'] || 'invalid_telegram_init_data' !== $payload['error']['code'] ) {
    fwrite( STDERR, 'Expected invalid_telegram_init_data for tampered data, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}
PHP

php_check 'stale Telegram initData should be rejected' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_BOT_TOKEN='123456:telegram-session-test-token' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

function test_init_data( array $fields, string $bot_token ) {
    ksort( $fields, SORT_STRING );
    $data_check_string = implode(
        "\n",
        array_map(
            function ( $key ) use ( $fields ) {
                return $key . '=' . $fields[ $key ];
            },
            array_keys( $fields )
        )
    );
    $secret_key = hash_hmac( 'sha256', $bot_token, 'WebAppData', TRUE );
    $fields['hash'] = hash_hmac( 'sha256', $data_check_string, $secret_key );

    return http_build_query( $fields, '', '&', PHP_QUERY_RFC3986 );
}

$init_data = test_init_data(
    [
        'user'      => json_encode( [ 'id' => 555, 'language_code' => 'en' ], JSON_UNESCAPED_SLASHES ),
        'auth_date' => (string) ( time() - 90000 ),
    ],
    $GLOBALS['runtime_config']['telegram']['bot_token']
);

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/telegram/session',
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => json_encode( [ 'initData' => $init_data ], JSON_UNESCAPED_SLASHES ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

$payload = json_decode( $response['body'], TRUE );
if ( 401 !== $response['status'] || 'expired_telegram_init_data' !== $payload['error']['code'] ) {
    fwrite( STDERR, 'Expected expired_telegram_init_data for stale data, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}
PHP

php_check 'signed initData missing a Telegram user should be rejected' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_BOT_TOKEN='123456:telegram-session-test-token' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

function test_init_data( array $fields, string $bot_token ) {
    ksort( $fields, SORT_STRING );
    $data_check_string = implode(
        "\n",
        array_map(
            function ( $key ) use ( $fields ) {
                return $key . '=' . $fields[ $key ];
            },
            array_keys( $fields )
        )
    );
    $secret_key = hash_hmac( 'sha256', $bot_token, 'WebAppData', TRUE );
    $fields['hash'] = hash_hmac( 'sha256', $data_check_string, $secret_key );

    return http_build_query( $fields, '', '&', PHP_QUERY_RFC3986 );
}

$init_data = test_init_data(
    [ 'auth_date' => (string) time() ],
    $GLOBALS['runtime_config']['telegram']['bot_token']
);

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/telegram/session',
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => json_encode( [ 'initData' => $init_data ], JSON_UNESCAPED_SLASHES ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

$payload = json_decode( $response['body'], TRUE );
if ( 400 !== $response['status'] || 'missing_telegram_user' !== $payload['error']['code'] ) {
    fwrite( STDERR, 'Expected missing_telegram_user for signed data without user, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}
PHP

php_check 'local browser fallback should create an anonymous development session' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/telegram/session',
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => '{}',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

$payload = json_decode( $response['body'], TRUE );
if ( 200 !== $response['status'] || TRUE !== $payload['ok'] ) {
    fwrite( STDERR, 'Expected local fallback success, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}

if ( 'anonymous' !== $payload['data']['session']['state'] || 'local_browser' !== $payload['data']['session']['source'] || null !== $payload['data']['user'] ) {
    fwrite( STDERR, "Local fallback impersonated a Telegram user or returned the wrong state\n" );
    exit( 1 );
}
PHP

php_check 'production requests without initData should be rejected' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=production \
        TONBANKCARD_PUBLIC_BASE_URL='https://marketcap.tonbankcard.com/' \
        TONBANKCARD_BOT_USERNAME='tonbankcard_bot' \
        TONBANKCARD_BOT_TOKEN='123456:telegram-session-test-token' \
        UPSTASH_REDIS_REST_URL='https://example.upstash.io' \
        UPSTASH_REDIS_REST_TOKEN='upstash-secret-token' \
        MYSQL_DSN='mysql:host=127.0.0.1;dbname=marketcap;charset=utf8mb4' \
        MYSQL_USER='marketcap' \
        MYSQL_PASSWORD='mysql-secret-password' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/telegram/session',
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => '{}',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

$payload = json_decode( $response['body'], TRUE );
if ( 400 !== $response['status'] || 'missing_init_data' !== $payload['error']['code'] ) {
    fwrite( STDERR, 'Expected missing_init_data for production request without initData, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}
PHP

assert_contains package.json '"test:telegram-session"' 'the Telegram session npm script'
assert_contains package.json 'test:telegram-session' 'the aggregate Telegram session check'
assert_contains README.md 'docs/v2-telegram-session\.md' 'the Telegram session documentation link'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Telegram session check passed.'
