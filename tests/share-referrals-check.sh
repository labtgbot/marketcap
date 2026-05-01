#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-shareable-market-cards.md

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

    if ! grep -Eq -- "$pattern" "$file"; then
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
assert_contains "$doc" '^# TONBANKCARD V2 Shareable Market Cards and Referral Deep Links$' 'the share/referral documentation title'
assert_contains "$doc" 'Issue: \[#33\]' 'the issue reference'
assert_contains "$doc" 'coin price' 'coin price share cards'
assert_contains "$doc" 'market pulse' 'market pulse share cards'
assert_contains "$doc" 'watchlist snapshot' 'watchlist snapshot share cards'
assert_contains "$doc" 'TON ecosystem movers' 'TON ecosystem mover share cards'
assert_contains "$doc" 'alert wins' 'alert win share cards'
assert_contains "$doc" 'AI insight summaries' 'AI insight summary share cards'
assert_contains "$doc" 'startapp' 'Telegram startapp payloads'
assert_contains "$doc" 'route' 'route payload field'
assert_contains "$doc" 'campaign' 'campaign payload field'
assert_contains "$doc" 'inviter' 'inviter payload field'
assert_contains "$doc" 'context' 'context payload field'
assert_contains "$doc" 'Not financial advice' 'share-card disclaimer'
assert_contains "$doc" 'freshness' 'share-card freshness labels'
assert_contains "$doc" 'tests/share-referrals-check.sh' 'the regression test reference'

assert_contains README.md 'docs/v2-shareable-market-cards\.md' 'the share/referral documentation link'
assert_contains package.json '"test:share-referrals"' 'the share/referrals npm script'
assert_contains package.json 'test:share-referrals' 'the aggregate share/referrals check'

assert_file api/share.php
assert_contains api/router.php "require_once __DIR__ \. '/share\.php';" 'the share API include'
assert_contains api/router.php "'/api/share/resolve'" 'the share resolve route'
assert_contains api/router.php 'tonbankcard_api_share_is_request' 'the share API route matcher'
assert_contains api/router.php 'tonbankcard_api_share_handle' 'the share API handler'
assert_contains api/router.php 'tonbankcard_api_share_record_referral_attribution' 'referral attribution after Telegram session creation'
assert_contains api/share.php 'function tonbankcard_api_share_build_start_param' 'startapp builder'
assert_contains api/share.php 'function tonbankcard_api_share_parse_start_param' 'startapp parser'
assert_contains api/share.php 'function tonbankcard_api_share_record_referral_attribution' 'referral attribution recorder'
assert_contains api/share.php 'referral_campaigns' 'campaign persistence'
assert_contains api/share.php 'referral_attributions' 'attribution persistence'

assert_file database/migrations/0008_share_referral_attribution.up.sql
assert_file database/migrations/0008_share_referral_attribution.down.sql
assert_contains database/migrations/0008_share_referral_attribution.up.sql 'uniq_referral_attributions_referred_campaign' 'per-user-and-campaign attribution uniqueness'
assert_contains docs/v2-database-schema-and-migrations.md '0008_share_referral_attribution' 'the 0008 migration documentation'

assert_file dev/js/src/share.js
assert_file dev/js/src/components/share-card.js
assert_file templates/components/share-card.php
assert_contains dev/js/source.json '"share\.js"' 'the share service source'
assert_contains dev/js/source.json '"components/share-card\.js"' 'the share card component source'
assert_contains index.php "'share-card'" 'the share-card template registration'
assert_contains templates/components/share-card.php 'share-market-card' 'the share card component markup'
assert_contains templates/components/share-card.php 'Not financial advice' 'the visible card disclaimer'
assert_contains assets/css/style.css 'share-market-card' 'share card styles'

assert_contains dev/js/src/routes/currency.js 'coin_price' 'coin price share context'
assert_contains templates/routes/currency.php 'gc-share-card' 'coin detail share card'
assert_contains dev/js/src/routes/currencies.js 'market_pulse' 'market pulse share context'
assert_contains templates/routes/currencies.php 'shareMarketPulse' 'market pulse share action'
assert_contains dev/js/src/routes/watchlist.js 'watchlist_snapshot' 'watchlist share context'
assert_contains templates/routes/watchlist.php 'shareWatchlist' 'watchlist share action'
assert_contains dev/js/src/routes/ton.js 'ton_movers' 'TON mover share context'
assert_contains templates/routes/ton.php 'shareTonMovers' 'TON mover share action'
assert_contains dev/js/src/routes/alerts.js 'alert_win' 'alert win share context'
assert_contains templates/routes/alerts.php 'shareAlert' 'alert share action'
assert_contains dev/js/src/components/ai-insight-card.js 'ai_insight' 'AI insight share context'
assert_contains templates/components/ai-insight-card.php 'shareInsight' 'AI insight share action'

php_check 'share startapp payloads should round-trip, resolve routes, and reject unsafe routes' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
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

$payload = [
    'route'    => '/currency/toncoin',
    'campaign' => 'coin-price',
    'inviter'  => 'telegram:12345',
    'context'  => 'coin_price',
];

$start_param = tonbankcard_api_share_build_start_param( $payload );
assert_true( preg_match( '/^s_[A-Za-z0-9_-]{12,512}$/', $start_param ) === 1, 'Startapp payload should use a compact Telegram-safe envelope.' );

$parsed = tonbankcard_api_share_parse_start_param( $start_param );
assert_true( ! empty( $parsed['ok'] ), 'Generated startapp payload should parse.' );
assert_true( '/currency/toncoin' === $parsed['payload']['route'], 'Route should round-trip.' );
assert_true( 'coin-price' === $parsed['payload']['campaign'], 'Campaign should round-trip.' );
assert_true( 'telegram:12345' === $parsed['payload']['inviter'], 'Inviter should round-trip.' );
assert_true( 'coin_price' === $parsed['payload']['context'], 'Context should round-trip.' );
assert_true( ! empty( $parsed['payload_hash'] ) && strlen( $parsed['payload_hash'] ) === 64, 'Parser should expose a payload hash.' );

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/share/resolve',
        'query'   => [ 'start_param' => $start_param ],
        'headers' => [],
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);
$body = json_decode( $response['body'], TRUE );
assert_true( 200 === $response['status'], 'Share resolve should accept generated payloads.' );
assert_true( '/currency/toncoin' === $body['data']['share']['route'], 'Share resolve should return the target route.' );

$unsafe = tonbankcard_api_share_build_start_param(
    [
        'route'    => 'https://evil.example/',
        'campaign' => 'coin-price',
        'inviter'  => 'telegram:12345',
        'context'  => 'coin_price',
    ]
);
$unsafe_result = tonbankcard_api_share_parse_start_param( $unsafe );
assert_true( empty( $unsafe_result['ok'] ) && 'invalid_share_route' === $unsafe_result['code'], 'Absolute external routes must be rejected.' );
PHP

php_check 'validated Telegram sessions should record referral attribution once per user and campaign in local storage' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_FEATURE_REFERRALS=true \
        TONBANKCARD_BOT_TOKEN='123456:share-referral-test-token' \
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

$store = sys_get_temp_dir() . '/tonbankcard-test-share-referrals.json';
@unlink( $store );

$runtime = $GLOBALS['runtime_config'];
$config = $api;
$config['telegram_session']['local_session_store_path'] = $store;

$start_param = tonbankcard_api_share_build_start_param(
    [
        'route'    => '/watchlist',
        'campaign' => 'watchlist-snapshot',
        'inviter'  => 'telegram:111',
        'context'  => 'watchlist_snapshot',
    ]
);

$init_data = test_init_data(
    [
        'query_id'    => 'AAHdF6IQAAAAAN0XohDhrOrc',
        'user'        => json_encode(
            [
                'id'            => 222,
                'first_name'    => 'ReferralUser',
                'language_code' => 'en',
            ],
            JSON_UNESCAPED_SLASHES
        ),
        'auth_date'   => (string) time(),
        'start_param' => $start_param,
        'chat_type'   => 'private',
    ],
    $runtime['telegram']['bot_token']
);

for ( $i = 0; $i < 2; $i++ ) {
    $response = tonbankcard_api_handle(
        [
            'method'  => 'POST',
            'path'    => '/api/telegram/session',
            'headers' => [
                'content-type' => 'application/json',
                'user-agent'   => 'ShareReferralTest/1.0',
            ],
            'body'    => json_encode( [ 'initData' => $init_data ], JSON_UNESCAPED_SLASHES ),
        ],
        [],
        $runtime,
        $config
    );

    assert_true( 200 === $response['status'], 'Expected Telegram session creation to succeed: ' . $response['body'] );
}

$store_payload = json_decode( (string) file_get_contents( $store ), TRUE );
assert_true( is_array( $store_payload ), 'Local session store should be readable JSON.' );
assert_true( isset( $store_payload['referral_campaigns']['watchlist-snapshot'] ), 'Campaign should be recorded in local referral storage.' );
assert_true( isset( $store_payload['referral_attributions'] ) && is_array( $store_payload['referral_attributions'] ), 'Referral attributions should be present.' );
assert_true( 1 === count( $store_payload['referral_attributions'] ), 'Referral attribution should be deduped per user and campaign.' );

$attribution = reset( $store_payload['referral_attributions'] );
assert_true( '222' === (string) $attribution['referred_telegram_user_id'], 'Referred Telegram user id should be recorded in local storage.' );
assert_true( '111' === (string) $attribution['inviter_telegram_user_id'], 'Inviter Telegram user id should be recorded in local storage.' );
assert_true( 'watchlist-snapshot' === $attribution['campaign_code'], 'Campaign code should be recorded.' );
assert_true( '/watchlist' === $attribution['landing_route'], 'Landing route should be recorded.' );
assert_true( 'watchlist_snapshot' === $attribution['context'], 'Share context should be recorded.' );

$self_start_param = tonbankcard_api_share_build_start_param(
    [
        'route'    => '/currency/toncoin',
        'campaign' => 'coin-price',
        'inviter'  => 'telegram:333',
        'context'  => 'coin_price',
    ]
);
$self_init_data = test_init_data(
    [
        'user'        => json_encode( [ 'id' => 333, 'language_code' => 'en' ], JSON_UNESCAPED_SLASHES ),
        'auth_date'   => (string) time(),
        'start_param' => $self_start_param,
    ],
    $runtime['telegram']['bot_token']
);
tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/telegram/session',
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => json_encode( [ 'initData' => $self_init_data ], JSON_UNESCAPED_SLASHES ),
    ],
    [],
    $runtime,
    $config
);
$after_self = json_decode( (string) file_get_contents( $store ), TRUE );
assert_true( 1 === count( $after_self['referral_attributions'] ), 'Self referrals should not create attribution rows.' );
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Share referrals check passed.'
