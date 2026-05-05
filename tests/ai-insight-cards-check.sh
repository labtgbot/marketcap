#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-ai-insight-cards.md
feedback_migration=database/migrations/0004_ai_feedback.up.sql

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

assert_file api/ai.php
assert_file dev/js/src/ai.js
assert_file dev/js/src/components/ai-insight-card.js
assert_file templates/components/ai-insight-card.php
assert_file "$doc"
assert_file "$feedback_migration"
assert_file database/migrations/0004_ai_feedback.down.sql

assert_contains "$doc" '^# TONBANKCARD V2 AI Insight Cards$' 'the AI insight cards title'
assert_contains "$doc" 'Issue: \[#28\]' 'the issue reference'
assert_contains "$doc" 'market pulse' 'the market pulse card'
assert_contains "$doc" 'coin detail' 'the coin detail card'
assert_contains "$doc" 'watchlist digest' 'the watchlist digest card'
assert_contains "$doc" 'TON ecosystem pulse' 'the TON ecosystem pulse card'
assert_contains "$doc" 'alert explanation' 'the alert explanation card'
assert_contains "$doc" 'never recommend buying, selling, or holding' 'the advice prohibition'
assert_contains "$doc" 'not financial advice' 'the disclaimer language'
assert_contains "$doc" 'confidence' 'confidence indicators'
assert_contains "$doc" 'source freshness' 'source freshness indicators'
assert_contains "$doc" 'helpful' 'helpful feedback'
assert_contains "$doc" 'stale' 'stale feedback'
assert_contains "$doc" 'wrong' 'wrong feedback'
assert_contains "$doc" 'unsafe' 'unsafe feedback'

assert_contains api/ai.php "'/api/ai/feedback'" 'the AI feedback endpoint'
assert_contains api/ai.php 'ton_ecosystem_pulse' 'the TON ecosystem insight type'
assert_contains api/ai.php 'ai_feedback' 'AI feedback persistence'
assert_contains "$feedback_migration" 'CREATE TABLE IF NOT EXISTS `ai_feedback`' 'the ai_feedback table'
assert_contains "$feedback_migration" "ENUM\\('helpful', 'stale', 'wrong', 'unsafe'\\)" 'feedback type validation in storage'
assert_contains "$feedback_migration" 'review_state' 'admin review state'
assert_contains .env.example '^TONBANKCARD_AI_FEEDBACK_STORE=' 'local AI feedback store configuration'
assert_contains views/app-scripts.php "'aiConfig'" 'frontend AI config'
assert_contains index.php "'ai-insight-card'" 'AI insight component registration'
assert_contains dev/js/source.json '"ai.js"' 'AI frontend service in the source bundle'
assert_contains dev/js/source.json '"components/ai-insight-card.js"' 'AI card component in the source bundle'
assert_contains templates/routes/currencies.php 'gc-ai-insight-card' 'market pulse AI card'
assert_contains templates/routes/currency.php 'coinInsightContext' 'coin detail AI card context'
assert_contains templates/routes/currency.php 'alertInsightContext' 'alert explanation AI card context'
assert_contains templates/routes/watchlist.php 'watchlistInsightContext' 'watchlist digest AI card context'
assert_contains templates/routes/ton.php 'tonInsightContext' 'TON ecosystem AI card context'
assert_contains templates/routes/ton-asset.php 'tonAssetInsightContext' 'TON asset detail AI card context'
assert_contains dev/js/src/routes/ton-asset.js 'tonAssetInsightContext' 'TON asset detail AI card computed property'
assert_contains package.json '"test:ai-insights"' 'the AI insight cards npm script'
assert_contains package.json 'test:ai-insights' 'the aggregate AI insight cards check'
assert_contains README.md 'docs/v2-ai-insight-cards\.md' 'the AI insight cards documentation link'

php_check 'TON ecosystem insight should validate and degrade cleanly while AI is disabled' \
    env -i PATH="$PATH" \
        TONBANKCARD_FEATURE_AI=false \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/ai/insight',
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => json_encode(
            [
                'insight_type'            => 'ton_ecosystem_pulse',
                'subject'                 => 'TON ecosystem',
                'market_data_age_seconds' => 0,
                'market_data'             => [ 'assets' => [ [ 'id' => 'toncoin' ] ] ],
            ]
        ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 200 fallback, got ' . $response['status'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( TRUE !== $payload['ok'] || 'insight unavailable' !== $payload['data']['status'] ) {
    fwrite( STDERR, "TON ecosystem insight did not degrade to unavailable\n" );
    exit( 1 );
}
if ( 'ai_disabled' !== $payload['data']['reason'] ) {
    fwrite( STDERR, "Disabled AI did not return the ai_disabled reason\n" );
    exit( 1 );
}
if ( FALSE !== strpos( $response['body'], 'unsupported_insight_type' ) ) {
    fwrite( STDERR, "TON ecosystem insight type was rejected as unsupported\n" );
    exit( 1 );
}
PHP

php_check 'unsafe alert explanation output should be rejected before it reaches users' \
    env -i PATH="$PATH" \
        TONBANKCARD_FEATURE_AI=true \
        GROQ_API_KEY='groq-secret-key' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['ai']['transport'] = function () {
    return [
        'status'  => 200,
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => json_encode(
            [
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode(
                                [
                                    'title'                   => 'Alert action',
                                    'summary'                 => 'Hold TON until the price breaks higher.',
                                    'sentiment'               => 'bullish',
                                    'confidence'              => 0.8,
                                    'drivers'                 => [ 'Price is above the recent low.' ],
                                    'risks'                   => [ 'Liquidity can change quickly.' ],
                                    'uncertainty'             => 'A narrow data window may miss later reversals.',
                                    'market_data_age_seconds' => 12,
                                    'not_financial_advice'    => TRUE,
                                ]
                            ),
                        ],
                    ],
                ],
            ]
        ),
    ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/ai/insight',
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => json_encode(
            [
                'insight_type'            => 'alert_explanation',
                'subject'                 => 'Toncoin alert',
                'market_data_age_seconds' => 12,
                'market_data'             => [ 'price_change_percentage_24h' => 2.4 ],
            ]
        ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

$payload = json_decode( $response['body'], TRUE );
if ( 200 !== $response['status'] || TRUE !== $payload['ok'] || 'insight unavailable' !== $payload['data']['status'] ) {
    fwrite( STDERR, "Unsafe alert explanation did not degrade to unavailable\n" );
    exit( 1 );
}
if ( 'schema_validation_failed' !== $payload['data']['reason'] ) {
    fwrite( STDERR, "Unsafe output did not fail schema validation\n" );
    exit( 1 );
}
if ( FALSE !== strpos( $response['body'], 'Hold TON' ) ) {
    fwrite( STDERR, "Unsafe AI advice reached the user response\n" );
    exit( 1 );
}
PHP

php_check 'AI feedback should be validated and stored without raw insight subject text' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_AI_FEEDBACK_STORE="$(mktemp "${TMPDIR:-/tmp}/tonbankcard-ai-feedback.XXXXXX.json")" \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$store = getenv( 'TONBANKCARD_AI_FEEDBACK_STORE' );
@unlink( $store );

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/ai/feedback',
        'headers' => [
            'content-type' => 'application/json',
            'cookie'       => 'tonbankcard_session=' . str_repeat( 'a', 64 ),
        ],
        'body'    => json_encode(
            [
                'feedback_type'             => 'unsafe',
                'insight_type'              => 'market_summary',
                'insight_id'                => str_repeat( 'b', 64 ),
                'subject'                   => 'raw subject should not be stored',
                'provider'                  => 'groq',
                'model'                     => 'llama-3.3-70b-versatile',
                'prompt_version'            => 'v1',
                'source_route'              => 'market_pulse',
                'market_data_age_seconds'   => 44,
                'metadata'                  => [ 'card_title' => 'AI market summary' ],
            ],
            JSON_UNESCAPED_SLASHES
        ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected feedback 200, got ' . $response['status'] . "\n" . $response['body'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( TRUE !== $payload['ok'] || 'stored' !== $payload['data']['status'] ) {
    fwrite( STDERR, "AI feedback did not return a stored response\n" );
    exit( 1 );
}

$stored = json_decode( (string) file_get_contents( $store ), TRUE );
if ( empty( $stored['feedback'][0] ) || 'unsafe' !== $stored['feedback'][0]['feedback_type'] ) {
    fwrite( STDERR, "AI feedback was not written to the local store\n" );
    exit( 1 );
}
if ( 'market_summary' !== $stored['feedback'][0]['insight_type'] || str_repeat( 'b', 64 ) !== $stored['feedback'][0]['insight_id'] ) {
    fwrite( STDERR, "Stored AI feedback is missing insight identifiers\n" );
    exit( 1 );
}
if ( empty( $stored['feedback'][0]['subject_hash'] ) || isset( $stored['feedback'][0]['subject'] ) ) {
    fwrite( STDERR, "Stored AI feedback did not use a subject hash\n" );
    exit( 1 );
}
if ( FALSE !== strpos( json_encode( $stored ), 'raw subject should not be stored' ) ) {
    fwrite( STDERR, "Raw AI feedback subject was stored\n" );
    exit( 1 );
}
@unlink( $store );
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'AI insight cards check passed.'
