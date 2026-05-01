#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-ai-sentiment-ingestion.md

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

assert_file api/ai-sentiment.php
assert_file "$doc"

assert_contains "$doc" '^# TONBANKCARD V2 AI Sentiment Ingestion Pipeline$' 'the sentiment ingestion title'
assert_contains "$doc" 'Issue: \[#27\]' 'the issue reference'
assert_contains "$doc" '/api/ai/sentiment-inputs' 'the sentiment input endpoint'
assert_contains "$doc" 'market movement' 'market movement source'
assert_contains "$doc" 'volume spike' 'volume spike source'
assert_contains "$doc" 'trend ranking' 'trend ranking source'
assert_contains "$doc" 'watchlist concentration' 'watchlist concentration source'
assert_contains "$doc" 'curated TON ecosystem' 'curated TON ecosystem source'
assert_contains "$doc" 'source_refresh_intervals' 'source refresh interval metadata'
assert_contains "$doc" 'market_data_hash' 'hashed market data traceability'
assert_contains "$doc" 'prompt_version' 'prompt version traceability'
assert_contains "$doc" 'No copyrighted article text' 'copyrighted text storage prohibition'

assert_contains api/ai.php '/api/ai/sentiment-inputs' 'AI sentiment route registration'
assert_contains api/router.php '/api/ai/sentiment-inputs' 'API route index entry'
assert_contains config/api.php 'sentiment_inputs' 'sentiment input cache TTL'
assert_contains .env.example '^TONBANKCARD_SENTIMENT_CACHE_TTL_SECONDS=300$' 'sentiment cache TTL configuration'
assert_contains package.json '"test:ai-sentiment"' 'the AI sentiment npm script'
assert_contains package.json 'test:ai-sentiment' 'the aggregate AI sentiment check'
assert_contains README.md 'docs/v2-ai-sentiment-ingestion\.md' 'the sentiment ingestion documentation link'

php_check 'sentiment pipeline should build deterministic AI-ready context and cache it without leaking article text' \
    env -i PATH="$PATH" \
        TONBANKCARD_FEATURE_AI=true \
        GROQ_API_KEY='groq-secret-key' \
        UPSTASH_REDIS_REST_URL='https://redis.example' \
        UPSTASH_REDIS_REST_TOKEN='redis-secret-token' \
        TONBANKCARD_CACHE_ENABLED=true \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$store = [];
$market_calls = [];
$test_api = $api;
$test_api['redis']['transport'] = function ( $command ) use ( &$store ) {
    $op = strtoupper( (string) $command[0] );
    if ( 'GET' === $op ) {
        return [ 'ok' => TRUE, 'result' => isset( $store[ $command[1] ] ) ? $store[ $command[1] ] : null ];
    }
    if ( 'SET' === $op ) {
        $store[ $command[1] ] = (string) $command[2];
        return [ 'ok' => TRUE, 'result' => 'OK' ];
    }
    return [ 'ok' => TRUE, 'result' => null ];
};
$test_api['market_data']['transport'] = function ( $request ) use ( &$market_calls ) {
    $market_calls[] = $request['path'];
    if ( 'coins/markets' === $request['path'] ) {
        if ( FALSE !== strpos( isset( $request['query']['ids'] ) ? $request['query']['ids'] : '', 'bad id' ) ) {
            fwrite( STDERR, "Unsafe coin id was forwarded to market provider\n" );
            exit( 1 );
        }
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode(
                [
                    [
                        'id'                                   => 'toncoin',
                        'symbol'                               => 'ton',
                        'name'                                 => 'Toncoin',
                        'current_price'                        => 5.2,
                        'market_cap'                           => 12000000000,
                        'total_volume'                         => 2400000000,
                        'price_change_percentage_1h_in_currency' => 1.8,
                        'price_change_percentage_24h_in_currency' => 8.5,
                        'price_change_percentage_7d_in_currency' => 18.0,
                        'price_change_percentage_24h'          => 8.5,
                        'last_updated'                         => gmdate( 'c', time() - 45 ),
                    ],
                    [
                        'id'                                   => 'bitcoin',
                        'symbol'                               => 'btc',
                        'name'                                 => 'Bitcoin',
                        'current_price'                        => 64000,
                        'market_cap'                           => 1250000000000,
                        'total_volume'                         => 18000000000,
                        'price_change_percentage_1h_in_currency' => -0.1,
                        'price_change_percentage_24h_in_currency' => -1.2,
                        'price_change_percentage_7d_in_currency' => 2.0,
                        'price_change_percentage_24h'          => -1.2,
                        'last_updated'                         => gmdate( 'c', time() - 50 ),
                    ],
                ]
            ),
        ];
    }
    if ( 'global' === $request['path'] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode(
                [
                    'data' => [
                        'market_cap_change_percentage_24h_usd' => 1.7,
                        'updated_at'                           => time() - 120,
                    ],
                ]
            ),
        ];
    }
    if ( 'search/trending' === $request['path'] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode(
                [
                    'coins' => [
                        [ 'item' => [ 'id' => 'toncoin', 'name' => 'Toncoin', 'symbol' => 'TON', 'market_cap_rank' => 12 ] ],
                        [ 'item' => [ 'id' => 'bitcoin', 'name' => 'Bitcoin', 'symbol' => 'BTC', 'market_cap_rank' => 1 ] ],
                    ],
                ]
            ),
        ];
    }

    fwrite( STDERR, 'Unexpected market provider path: ' . $request['path'] . "\n" );
    exit( 1 );
};
$test_api['ai']['transport'] = function ( $request ) {
    $body = json_decode( $request['body'], TRUE );
    $prompt = json_encode( isset( $body['messages'] ) ? $body['messages'] : [] );
    if ( FALSE === strpos( $prompt, 'sentiment_pipeline_version' ) || FALSE === strpos( $prompt, 'watchlist_concentration' ) ) {
        fwrite( STDERR, "AI prompt was not fed the structured sentiment context\n" );
        exit( 1 );
    }

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
                                    'title'                   => 'TON sentiment context',
                                    'summary'                 => 'TON inputs are positive, with elevated volume and trend visibility.',
                                    'sentiment'               => 'bullish',
                                    'confidence'              => 0.76,
                                    'drivers'                 => [ 'Market movement and trend signals are positive.' ],
                                    'risks'                   => [ 'Inputs can change quickly.' ],
                                    'uncertainty'             => 'This uses deterministic market and watchlist inputs only.',
                                    'market_data_age_seconds' => 120,
                                    'not_financial_advice'    => TRUE,
                                ]
                            ),
                        ],
                    ],
                ],
                'usage' => [ 'prompt_tokens' => 120, 'completion_tokens' => 40, 'total_tokens' => 160 ],
            ]
        ),
    ];
};

$request_body = [
    'insight_type'       => 'sentiment',
    'subject'            => 'TON ecosystem pulse',
    'coin_ids'           => [ 'toncoin', 'bitcoin', 'bad id with spaces' ],
    'watchlist_coin_ids' => [ 'toncoin', 'tether' ],
    'news_articles'      => [
        [ 'text' => 'copyrighted raw article text must never be copied' ],
    ],
];
$request = [
    'method'  => 'POST',
    'path'    => '/api/ai/sentiment-inputs',
    'headers' => [ 'content-type' => 'application/json', 'x-request-id' => 'sentiment-ready' ],
    'body'    => json_encode( $request_body ),
];

$first = tonbankcard_api_handle( $request, [], $GLOBALS['runtime_config'], $test_api );
if ( 200 !== $first['status'] ) {
    fwrite( STDERR, 'Expected 200 sentiment response, got ' . $first['status'] . "\n" );
    exit( 1 );
}
if ( FALSE !== strpos( $first['body'], 'copyrighted raw article text' ) || FALSE !== strpos( $first['body'], 'redis-secret-token' ) ) {
    fwrite( STDERR, "Sentiment response leaked prohibited text or Redis secrets\n" );
    exit( 1 );
}

$payload = json_decode( $first['body'], TRUE );
if ( ! is_array( $payload ) || TRUE !== $payload['ok'] ) {
    fwrite( STDERR, "Sentiment response did not use the success envelope\n" );
    exit( 1 );
}
$data = $payload['data'];
if ( 'ready' !== $data['status'] ) {
    fwrite( STDERR, 'Expected ready sentiment status, got ' . $data['status'] . "\n" );
    exit( 1 );
}
if ( 'bullish' !== $data['scores']['overall']['sentiment'] || $data['scores']['overall']['confidence'] < 0.65 ) {
    fwrite( STDERR, "Expected bullish high-confidence deterministic score\n" );
    exit( 1 );
}
foreach ( [ 'market_movement', 'volume_spike', 'global_market', 'trend_ranking', 'watchlist_concentration', 'curated_ton_ecosystem' ] as $source ) {
    if ( empty( $data['sources'][ $source ] ) || 'available' !== $data['sources'][ $source ]['status'] ) {
        fwrite( STDERR, "Expected available source: $source\n" );
        exit( 1 );
    }
    if ( $source !== $data['sources'][ $source ]['name'] ) {
        fwrite( STDERR, "Source metadata used the wrong name for $source\n" );
        exit( 1 );
    }
}
if ( 'sentiment' !== $data['ai_context']['insight_type'] || 'TON ecosystem pulse' !== $data['ai_context']['subject'] ) {
    fwrite( STDERR, "AI context did not preserve the requested insight type and subject\n" );
    exit( 1 );
}
if ( empty( $data['ai_context']['market_data']['sentiment_pipeline_version'] ) || empty( $data['trace']['market_data_hash'] ) || empty( $data['trace']['prompt_version'] ) ) {
    fwrite( STDERR, "Sentiment response is missing traceable prompt and market-data metadata\n" );
    exit( 1 );
}
if ( 'miss' !== $payload['meta']['cache']['cache_status'] ) {
    fwrite( STDERR, 'Expected first response cache miss, got ' . $payload['meta']['cache']['cache_status'] . "\n" );
    exit( 1 );
}
$provider_call_count = count( $market_calls );

$second = tonbankcard_api_handle( $request, [], $GLOBALS['runtime_config'], $test_api );
$second_payload = json_decode( $second['body'], TRUE );
if ( 'hit' !== $second_payload['meta']['cache']['cache_status'] ) {
    fwrite( STDERR, 'Expected second response cache hit, got ' . $second_payload['meta']['cache']['cache_status'] . "\n" );
    exit( 1 );
}
if ( $provider_call_count !== count( $market_calls ) ) {
    fwrite( STDERR, "Sentiment cache hit made another provider request\n" );
    exit( 1 );
}

$insight = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/ai/insight',
        'headers' => [ 'content-type' => 'application/json', 'x-request-id' => 'sentiment-insight' ],
        'body'    => json_encode( $data['ai_context'] ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);
$insight_payload = json_decode( $insight['body'], TRUE );
if ( 200 !== $insight['status'] || TRUE !== $insight_payload['ok'] || 'available' !== $insight_payload['data']['status'] ) {
    fwrite( STDERR, "AI provider layer could not consume the sentiment context\n" );
    exit( 1 );
}
PHP

php_check 'sentiment pipeline should degrade deterministically when provider sources are missing or stale' \
    env -i PATH="$PATH" \
        TONBANKCARD_FEATURE_AI=false \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) {
    if ( 'coins/markets' === $request['path'] ) {
        return [ 'error' => 'timeout', 'status' => 0 ];
    }
    if ( 'global' === $request['path'] ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode(
                [
                    'data' => [
                        'market_cap_change_percentage_24h_usd' => -3.5,
                        'updated_at'                           => time() - 7200,
                    ],
                ]
            ),
        ];
    }
    if ( 'search/trending' === $request['path'] ) {
        return [ 'status' => 200, 'headers' => [ 'content-type' => 'application/json' ], 'body' => '{not-json' ];
    }

    return [ 'status' => 404, 'headers' => [], 'body' => '{}' ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/ai/sentiment-inputs',
        'headers' => [ 'content-type' => 'application/json', 'x-request-id' => 'sentiment-partial' ],
        'body'    => json_encode(
            [
                'insight_type'       => 'market_summary',
                'subject'            => 'Fallback market context',
                'coin_ids'           => [ 'toncoin' ],
                'watchlist_coin_ids' => [ 'toncoin' ],
            ]
        ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 200 partial sentiment response, got ' . $response['status'] . "\n" );
    exit( 1 );
}
$payload = json_decode( $response['body'], TRUE );
if ( TRUE !== $payload['ok'] || 'partial' !== $payload['data']['status'] ) {
    fwrite( STDERR, "Missing and stale sources should produce a partial success response\n" );
    exit( 1 );
}
if ( 'unavailable' !== $payload['data']['sources']['market_movement']['status'] ) {
    fwrite( STDERR, "Missing market movement source was not marked unavailable\n" );
    exit( 1 );
}
if ( 'stale' !== $payload['data']['sources']['global_market']['status'] ) {
    fwrite( STDERR, "Stale global market source was not marked stale\n" );
    exit( 1 );
}
if ( 'unavailable' !== $payload['data']['sources']['trend_ranking']['status'] ) {
    fwrite( STDERR, "Invalid trending source was not marked unavailable\n" );
    exit( 1 );
}
if ( $payload['data']['scores']['overall']['confidence'] >= 0.65 ) {
    fwrite( STDERR, "Partial source confidence should be reduced\n" );
    exit( 1 );
}
if ( empty( $payload['data']['ai_context']['market_data_age_seconds'] ) || empty( $payload['data']['source_refresh_intervals']['market_movement'] ) ) {
    fwrite( STDERR, "Partial response is missing AI context age or refresh interval metadata\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'AI sentiment ingestion check passed.'
