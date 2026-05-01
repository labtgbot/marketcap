#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-telegram-bot-companion.md

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

assert_file api/telegram-bot.php
assert_file "$doc"

assert_contains "$doc" '^# TONBANKCARD V2 Telegram Bot Companion$' 'the Telegram bot documentation title'
assert_contains "$doc" 'Issue: \[#36\]' 'the issue reference'
assert_contains "$doc" '/api/telegram/bot' 'the bot webhook endpoint'
assert_contains "$doc" '/start' 'the start command'
assert_contains "$doc" '/market' 'the market command'
assert_contains "$doc" '/watchlist' 'the watchlist command'
assert_contains "$doc" '/alerts' 'the alerts command'
assert_contains "$doc" '/settings' 'the settings command'
assert_contains "$doc" '/support' 'the support command'
assert_contains "$doc" 'inline mode' 'inline mode coverage'
assert_contains "$doc" 'chat_instance' 'group chat instance handling'
assert_contains "$doc" 'request_id' 'bot error request-id logging'
assert_contains "$doc" 'Russian' 'Russian bot copy'

assert_contains README.md 'docs/v2-telegram-bot-companion\.md' 'the Telegram bot documentation link'
assert_contains package.json '"test:telegram-bot"' 'the telegram bot npm script'
assert_contains package.json 'test:telegram-bot' 'the aggregate telegram bot check'
assert_contains .env.example '^TONBANKCARD_BOT_WEBHOOK_SECRET=' 'the bot webhook secret environment variable'
assert_contains config/runtime.php 'TONBANKCARD_BOT_WEBHOOK_SECRET' 'runtime bot webhook secret integration'
assert_contains config/api.php 'telegram_bot' 'bot API configuration'
assert_contains config/api.php 'X-Telegram-Bot-Api-Secret-Token' 'Telegram webhook secret CORS header'
assert_contains api/router.php "require_once __DIR__ \. '/telegram-bot\.php';" 'the Telegram bot API include'
assert_contains api/router.php "'/api/telegram/bot'" 'the bot webhook route index entry'
assert_contains api/router.php 'tonbankcard_api_telegram_bot_is_request' 'the bot route matcher'
assert_contains api/router.php 'tonbankcard_api_telegram_bot_handle' 'the bot route handler'
assert_contains api/router.php 'tonbankcard_api_telegram_group_context_payload' 'group-context session payload exposure'

php_check 'Telegram bot commands should return route-correct Mini App buttons and preserve referral starts' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_BOT_USERNAME='MarketCapBot' \
        TONBANKCARD_BOT_WEBHOOK_SECRET='webhook-secret' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

function assert_true( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, $message . PHP_EOL );
        exit( 1 );
    }
}

function call_bot_update( array $update ) {
    return tonbankcard_api_handle(
        [
            'method'  => 'POST',
            'path'    => '/api/telegram/bot',
            'headers' => [
                'content-type' => 'application/json',
                'x-telegram-bot-api-secret-token' => 'webhook-secret',
                'x-request-id' => 'bot-command-test',
            ],
            'body'    => json_encode( $update, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
        ],
        [],
        $GLOBALS['runtime_config'],
        $GLOBALS['api']
    );
}

function response_payload( array $response ) {
    assert_true( 200 === $response['status'], 'Expected Telegram webhook response 200, got ' . $response['status'] . ': ' . $response['body'] );
    $payload = json_decode( $response['body'], TRUE );
    assert_true( is_array( $payload ), 'Telegram webhook response should be JSON.' );
    return $payload;
}

function first_button_url( array $payload ) {
    $url = $payload['reply_markup']['inline_keyboard'][0][0]['url'] ?? '';
    assert_true( '' !== $url, 'Bot command response should include a Mini App URL button.' );
    return $url;
}

function startapp_payload_from_url( string $url ) {
    $parts = parse_url( $url );
    parse_str( $parts['query'] ?? '', $query );
    assert_true( ! empty( $query['startapp'] ), 'Mini App URL should include startapp payload: ' . $url );
    $parsed = tonbankcard_api_share_parse_start_param( $query['startapp'] );
    assert_true( ! empty( $parsed['ok'] ), 'startapp payload should parse.' );
    return [ $query['startapp'], $parsed['payload'] ];
}

$market = response_payload(
    call_bot_update(
        [
            'update_id' => 1001,
            'message'   => [
                'message_id' => 10,
                'text'       => '/market',
                'chat'       => [ 'id' => 200, 'type' => 'private' ],
                'from'       => [ 'id' => 300, 'language_code' => 'en' ],
            ],
        ]
    )
);
assert_true( 'sendMessage' === $market['method'], 'Command webhook should return a sendMessage method.' );
list( , $market_payload ) = startapp_payload_from_url( first_button_url( $market ) );
assert_true( '/markets' === $market_payload['route'], '/market should open the markets route.' );
assert_true( 'bot-command' === $market_payload['campaign'], 'Bot command payload should identify the bot command campaign.' );

$settings = response_payload(
    call_bot_update(
        [
            'update_id' => 1002,
            'message'   => [
                'message_id' => 11,
                'text'       => '/settings',
                'chat'       => [ 'id' => 200, 'type' => 'private' ],
                'from'       => [ 'id' => 300, 'language_code' => 'en' ],
            ],
        ]
    )
);
list( , $settings_payload ) = startapp_payload_from_url( first_button_url( $settings ) );
assert_true( '/settings' === $settings_payload['route'], '/settings should open the settings route.' );

$start_param = tonbankcard_api_share_build_start_param(
    [
        'route'    => '/watchlist',
        'campaign' => 'watchlist-snapshot',
        'inviter'  => 'telegram:111',
        'context'  => 'watchlist_snapshot',
    ]
);
$start = response_payload(
    call_bot_update(
        [
            'update_id' => 1003,
            'message'   => [
                'message_id' => 12,
                'text'       => '/start ' . $start_param,
                'chat'       => [ 'id' => 200, 'type' => 'private' ],
                'from'       => [ 'id' => 222, 'language_code' => 'en' ],
            ],
        ]
    )
);
list( $returned_start_param, $referral_payload ) = startapp_payload_from_url( first_button_url( $start ) );
assert_true( $start_param === $returned_start_param, 'Referral-aware /start should preserve the original startapp payload.' );
assert_true( '/watchlist' === $referral_payload['route'], 'Referral-aware /start should open the shared route.' );

$group = response_payload(
    call_bot_update(
        [
            'update_id' => 1004,
            'message'   => [
                'message_id' => 13,
                'text'       => '/market',
                'chat'       => [ 'id' => -10055, 'type' => 'supergroup' ],
                'from'       => [ 'id' => 300, 'language_code' => 'en' ],
            ],
        ]
    )
);
list( , $group_payload ) = startapp_payload_from_url( first_button_url( $group ) );
assert_true( '/markets?context=group' === $group_payload['route'], 'Group /market should open group market context.' );
assert_true( 'telegram_group' === $group_payload['context'], 'Group command payload should be tagged as telegram_group.' );

$ru = response_payload(
    call_bot_update(
        [
            'update_id' => 1005,
            'message'   => [
                'message_id' => 14,
                'text'       => '/alerts',
                'chat'       => [ 'id' => 200, 'type' => 'private' ],
                'from'       => [ 'id' => 300, 'language_code' => 'ru' ],
            ],
        ]
    )
);
assert_true( FALSE !== strpos( $ru['text'], 'алерты' ), 'Russian users should receive Russian bot copy.' );
PHP

php_check 'Telegram inline mode should return useful market and coin cards' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_BOT_USERNAME='MarketCapBot' \
        TONBANKCARD_BOT_WEBHOOK_SECRET='webhook-secret' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

function assert_true( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, $message . PHP_EOL );
        exit( 1 );
    }
}

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/telegram/bot',
        'headers' => [
            'content-type' => 'application/json',
            'x-telegram-bot-api-secret-token' => 'webhook-secret',
            'x-request-id' => 'bot-inline-test',
        ],
        'body'    => json_encode(
            [
                'update_id'    => 2001,
                'inline_query' => [
                    'id'        => 'inline-query-1',
                    'query'     => 'ton',
                    'chat_type' => 'group',
                    'from'      => [ 'id' => 444, 'language_code' => 'en' ],
                ],
            ],
            JSON_UNESCAPED_SLASHES
        ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $GLOBALS['api']
);

assert_true( 200 === $response['status'], 'Inline query should return HTTP 200.' );
$payload = json_decode( $response['body'], TRUE );
assert_true( 'answerInlineQuery' === ( $payload['method'] ?? '' ), 'Inline query should return answerInlineQuery.' );
assert_true( 'inline-query-1' === ( $payload['inline_query_id'] ?? '' ), 'Inline query id should round-trip.' );
$results = $payload['results'] ?? [];
assert_true( count( $results ) >= 3, 'Inline query should return market and coin cards.' );

$titles = array_map(
    function ( $result ) {
        return $result['title'] ?? '';
    },
    $results
);
assert_true( in_array( 'Market overview', $titles, TRUE ), 'Inline results should include a market overview card.' );
assert_true( in_array( 'TON ecosystem', $titles, TRUE ), 'Inline results should include a TON ecosystem market card.' );
assert_true( in_array( 'Toncoin (TON)', $titles, TRUE ), 'Inline results should include a Toncoin card.' );

foreach ( $results as $result ) {
    assert_true( 'article' === ( $result['type'] ?? '' ), 'Inline result should be an article card.' );
    assert_true( ! empty( $result['reply_markup']['inline_keyboard'][0][0]['url'] ), 'Inline result should include a shareable Mini App URL.' );
    assert_true( FALSE !== strpos( $result['input_message_content']['message_text'] ?? '', 'Not financial advice' ), 'Inline message should include the market disclaimer.' );
}
PHP

php_check 'Telegram group Mini App sessions should expose only an isolated hashed group context' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_BOT_TOKEN='123456:telegram-bot-session-test-token' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

function assert_true( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, $message . PHP_EOL );
        exit( 1 );
    }
}

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

$store = sys_get_temp_dir() . '/tonbankcard-test-bot-group-session.json';
@unlink( $store );
$runtime = $GLOBALS['runtime_config'];
$config = $api;
$config['telegram_session']['local_session_store_path'] = $store;

$init_data = test_init_data(
    [
        'user'          => json_encode( [ 'id' => 555, 'language_code' => 'en' ], JSON_UNESCAPED_SLASHES ),
        'auth_date'     => (string) time(),
        'start_param'   => tonbankcard_api_share_build_start_param(
            [
                'route'    => '/markets?context=group',
                'campaign' => 'bot-command',
                'inviter'  => 'telegram:555',
                'context'  => 'telegram_group',
            ]
        ),
        'chat_type'     => 'supergroup',
        'chat_instance' => 'raw-group-instance-secret',
    ],
    $runtime['telegram']['bot_token']
);

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/telegram/session',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'bot-group-session-test',
        ],
        'body'    => json_encode( [ 'initData' => $init_data ], JSON_UNESCAPED_SLASHES ),
    ],
    [],
    $runtime,
    $config
);

assert_true( 200 === $response['status'], 'Group Telegram session should validate.' );
$payload = json_decode( $response['body'], TRUE );
$group = $payload['data']['launch']['group_context'] ?? [];
assert_true( TRUE === ( $group['available'] ?? FALSE ), 'Group launch should expose an available group context.' );
assert_true( 'telegram_group' === ( $group['scope'] ?? '' ), 'Group launch scope should be telegram_group.' );
assert_true( 'supergroup' === ( $group['chat_type'] ?? '' ), 'Group launch should expose the safe chat type.' );
assert_true( preg_match( '/^tggrp_[a-f0-9]{24}$/', $group['context_id'] ?? '' ) === 1, 'Group context id should be a bounded hash.' );
assert_true( TRUE === ( $group['personal_data_isolated'] ?? FALSE ), 'Group context should explicitly isolate personal data.' );
assert_true( FALSE === strpos( $response['body'], 'raw-group-instance-secret' ), 'Raw chat_instance must not leak in the response.' );
PHP

php_check 'Telegram bot webhook errors should be logged with request IDs' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_BOT_USERNAME='MarketCapBot' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

function assert_true( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, $message . PHP_EOL );
        exit( 1 );
    }
}

$log_file = tempnam( sys_get_temp_dir(), 'tonbankcard-bot-error-' );
ini_set( 'error_log', $log_file );

$runtime = $GLOBALS['runtime_config'];
$runtime['observability']['log_level'] = 'warning';
$config = $api;
$config['observability']['log_level'] = 'warning';

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/telegram/bot',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'bot-error-1',
        ],
        'body'    => json_encode( [ 'update_id' => 3001, 'message' => [ 'text' => '/market' ] ], JSON_UNESCAPED_SLASHES ),
    ],
    [],
    $runtime,
    $config
);

assert_true( 400 === $response['status'], 'Malformed bot update should return a 400 response.' );
$payload = json_decode( $response['body'], TRUE );
assert_true( 'telegram_bot_invalid_update' === ( $payload['error']['code'] ?? '' ), 'Malformed bot update should return telegram_bot_invalid_update.' );

$logs = file_get_contents( $log_file );
assert_true( FALSE !== strpos( $logs, 'bot-error-1' ), 'Bot error logs should include the request id.' );
assert_true( FALSE !== strpos( $logs, 'telegram_bot.webhook_error' ), 'Bot error logs should include the webhook error event.' );
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

echo "Telegram bot companion check passed."
