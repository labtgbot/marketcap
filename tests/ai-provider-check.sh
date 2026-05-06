#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-ai-provider-foundation.md

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

assert_file api/ai.php
assert_file "$doc"

assert_contains "$doc" '^# TONBANKCARD V2 AI Provider Foundation$' 'the AI provider foundation title'
assert_contains "$doc" 'Issue: \[#17\]' 'the issue reference'
assert_contains "$doc" '/api/ai/insight' 'the AI insight endpoint'
assert_contains "$doc" 'Groq' 'the first AI provider'
assert_contains "$doc" 'structured JSON' 'structured output validation'
assert_contains "$doc" 'not financial advice' 'the required AI safety framing'
assert_contains "$doc" 'market_data_age_seconds' 'market data age citation'
assert_contains "$doc" 'uncertainty' 'uncertainty in generated insights'
assert_contains "$doc" 'cost counters' 'provider cost counters'
assert_contains "$doc" 'insight unavailable' 'the provider fallback behavior'
assert_contains "$doc" 'raw prompts' 'raw prompt logging prohibition'

assert_contains .env.example '^TONBANKCARD_AI_PROVIDER=groq$' 'the default AI provider'
assert_contains .env.example '^TONBANKCARD_AI_PROMPT_VERSION=v1$' 'the AI prompt version'
assert_contains .env.example '^TONBANKCARD_AI_ENABLED_FEATURES=summary,sentiment,insight$' 'AI enabled feature configuration'
assert_contains .env.example '^TONBANKCARD_AI_FALLBACK_BEHAVIOR=unavailable$' 'AI fallback behavior'
assert_contains .env.example '^GROQ_MODEL_ID=' 'the Groq model id'
assert_contains .env.example '^GROQ_BASE_URL=https://api\.groq\.com/openai/v1/$' 'the Groq API root'
assert_contains .env.example '^GROQ_TIMEOUT_SECONDS=10$' 'the Groq timeout'
assert_contains .env.example '^GROQ_RATE_LIMIT_WINDOW_SECONDS=60$' 'the Groq rate-limit window'
assert_contains .env.example '^GROQ_RATE_LIMIT_MAX_REQUESTS=20$' 'the Groq rate-limit ceiling'
assert_contains package.json '"test:ai-provider"' 'the AI provider npm script'
assert_contains package.json 'test:ai-provider' 'the aggregate AI provider check'
assert_contains README.md 'docs/v2-ai-provider-foundation\.md' 'the AI provider documentation link'

php_check 'runtime config should expose configurable Groq provider fields without a code change' \
    env -i PATH="$PATH" \
        TONBANKCARD_FEATURE_AI=true \
        GROQ_API_KEY='groq-secret-key' \
        GROQ_MODEL_ID='llama-3.3-70b-versatile' \
        GROQ_TIMEOUT_SECONDS=7 \
        GROQ_RATE_LIMIT_WINDOW_SECONDS=45 \
        GROQ_RATE_LIMIT_MAX_REQUESTS=9 \
        TONBANKCARD_AI_PROMPT_VERSION='v2-test' \
        TONBANKCARD_AI_ENABLED_FEATURES='summary,sentiment' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/functions.php';

$runtime = $GLOBALS['runtime_config'];

if ( 'groq' !== $runtime['ai']['provider'] ) {
    fwrite( STDERR, "Expected groq AI provider\n" );
    exit( 1 );
}
if ( 'llama-3.3-70b-versatile' !== $runtime['providers']['groq']['model_id'] ) {
    fwrite( STDERR, "Groq model id was not read from the environment\n" );
    exit( 1 );
}
if ( 7 !== $runtime['providers']['groq']['timeout_seconds'] ) {
    fwrite( STDERR, "Groq timeout was not read from the environment\n" );
    exit( 1 );
}
if ( 45 !== $runtime['providers']['groq']['rate_limit']['window_seconds'] || 9 !== $runtime['providers']['groq']['rate_limit']['max_requests'] ) {
    fwrite( STDERR, "Groq rate limit was not read from the environment\n" );
    exit( 1 );
}
if ( 'v2-test' !== $runtime['ai']['prompt_version'] ) {
    fwrite( STDERR, "AI prompt version was not read from the environment\n" );
    exit( 1 );
}
if ( [ 'summary', 'sentiment' ] !== $runtime['ai']['enabled_features'] ) {
    fwrite( STDERR, "AI enabled features were not parsed as a safe list\n" );
    exit( 1 );
}

$invalid = validate_runtime_config();
$payload = json_encode( $invalid );
if ( FALSE !== strpos( $payload, 'groq-secret-key' ) ) {
    fwrite( STDERR, "Runtime validation leaked a Groq secret\n" );
    exit( 1 );
}
PHP

php_check 'AI insight execution should use server-side Groq credentials and expose only schema-validated output' \
    env -i PATH="$PATH" \
        TONBANKCARD_FEATURE_AI=true \
        GROQ_API_KEY='groq-secret-key' \
        GROQ_MODEL_ID='llama-3.3-70b-versatile' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['ai']['transport'] = function ( $request ) {
    if ( 'POST' !== $request['method'] ) {
        fwrite( STDERR, "AI provider should be called with POST\n" );
        exit( 1 );
    }
    if ( 'https://api.groq.com/openai/v1/chat/completions' !== $request['url'] ) {
        fwrite( STDERR, 'Unexpected Groq URL: ' . $request['url'] . "\n" );
        exit( 1 );
    }
    if ( empty( $request['headers']['Authorization'] ) || 'Bearer groq-secret-key' !== $request['headers']['Authorization'] ) {
        fwrite( STDERR, "Groq API key was not sent server-side\n" );
        exit( 1 );
    }
    if ( array_key_exists( 'Groq-Beta', $request['headers'] ) ) {
        fwrite( STDERR, "Groq-Beta header must not be sent: it causes 400 errors from the Groq API\n" );
        exit( 1 );
    }

    $body = json_decode( $request['body'], TRUE );
    if ( 'llama-3.3-70b-versatile' !== $body['model'] ) {
        fwrite( STDERR, "Groq model id was not sent in the provider request\n" );
        exit( 1 );
    }
    if ( empty( $body['response_format']['type'] ) || 'json_object' !== $body['response_format']['type'] ) {
        fwrite( STDERR, "AI request did not ask the provider for JSON output\n" );
        exit( 1 );
    }
    if ( ! isset( $body['max_completion_tokens'] ) || $body['max_completion_tokens'] < 1024 ) {
        fwrite( STDERR, "max_completion_tokens must be at least 1024 to prevent JSON truncation that causes schema retries\n" );
        exit( 1 );
    }
    $prompt = json_encode( $body['messages'] );
    foreach ( [ 'not financial advice', 'market_data_age_seconds', 'uncertainty' ] as $required ) {
        if ( FALSE === strpos( $prompt, $required ) ) {
            fwrite( STDERR, "AI prompt is missing safety instruction: $required\n" );
            exit( 1 );
        }
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
                                    'title'                   => 'TON market context',
                                    'summary'                 => 'TON is moving higher while volume remains below the recent peak.',
                                    'sentiment'               => 'mixed',
                                    'confidence'              => 0.62,
                                    'drivers'                 => [ 'Price is positive on the day.', 'Volume is not confirming strongly.' ],
                                    'risks'                   => [ 'Short-term reversals remain possible.' ],
                                    'uncertainty'             => 'This is based on a narrow market snapshot and may change quickly.',
                                    'market_data_age_seconds' => 90,
                                    'not_financial_advice'    => TRUE,
                                ]
                            ),
                        ],
                    ],
                ],
                'usage'   => [
                    'prompt_tokens'     => 100,
                    'completion_tokens' => 42,
                    'total_tokens'      => 142,
                ],
            ]
        ),
    ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/ai/insight',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'ai-success',
        ],
        'body'    => json_encode(
            [
                'insight_type'            => 'market_summary',
                'subject'                 => 'TON market',
                'market_data_age_seconds' => 90,
                'market_data'             => [
                    'price_change_percentage_24h' => 1.8,
                    'total_volume'                => 12345678,
                ],
            ]
        ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 200 AI insight response, got ' . $response['status'] . "\n" );
    exit( 1 );
}
if ( FALSE !== strpos( $response['body'], 'groq-secret-key' ) ) {
    fwrite( STDERR, "AI response leaked the Groq API key\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( ! is_array( $payload ) || TRUE !== $payload['ok'] ) {
    fwrite( STDERR, "AI response did not use the success envelope\n" );
    exit( 1 );
}
if ( 'available' !== $payload['data']['status'] ) {
    fwrite( STDERR, "AI insight should be available after valid provider output\n" );
    exit( 1 );
}
if ( 'mixed' !== $payload['data']['insight']['sentiment'] || empty( $payload['data']['insight']['uncertainty'] ) ) {
    fwrite( STDERR, "AI insight did not preserve validated fields\n" );
    exit( 1 );
}
if ( TRUE !== $payload['data']['insight']['safety']['not_financial_advice'] ) {
    fwrite( STDERR, "AI insight did not include not-financial-advice safety framing\n" );
    exit( 1 );
}
if ( 90 !== $payload['data']['insight']['freshness']['market_data_age_seconds'] ) {
    fwrite( STDERR, "AI insight did not cite market data age\n" );
    exit( 1 );
}
if ( 142 !== $payload['meta']['cost']['total_tokens'] || 100 !== $payload['meta']['cost']['input_tokens'] || 42 !== $payload['meta']['cost']['output_tokens'] ) {
    fwrite( STDERR, "AI response did not expose safe cost counters\n" );
    exit( 1 );
}
if ( 'groq' !== $payload['meta']['provider']['name'] || empty( $payload['meta']['provider']['credentialed'] ) ) {
    fwrite( STDERR, "AI response did not expose safe provider metadata\n" );
    exit( 1 );
}
PHP

php_check 'AI provider errors should degrade to insight unavailable without leaking provider bodies' \
    env -i PATH="$PATH" \
        TONBANKCARD_FEATURE_AI=true \
        GROQ_API_KEY='groq-secret-key' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['ai']['transport'] = function ( $request ) {
    return [
        'status'  => 429,
        'headers' => [ 'retry-after' => '30' ],
        'body'    => '{"error":{"message":"provider said groq-secret-key raw prompt text"}}',
    ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/ai/insight',
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => json_encode(
            [
                'insight_type'            => 'coin_summary',
                'subject'                 => 'Toncoin',
                'market_data_age_seconds' => 15,
                'market_data'             => [ 'id' => 'the-open-network' ],
            ]
        ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected graceful 200 fallback, got ' . $response['status'] . "\n" );
    exit( 1 );
}
if ( FALSE !== strpos( $response['body'], 'groq-secret-key' ) || FALSE !== strpos( $response['body'], 'raw prompt text' ) ) {
    fwrite( STDERR, "AI fallback leaked a provider body or secret\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( TRUE !== $payload['ok'] || 'insight unavailable' !== $payload['data']['status'] ) {
    fwrite( STDERR, "AI provider error did not degrade to insight unavailable\n" );
    exit( 1 );
}
if ( 'provider_rate_limited' !== $payload['data']['reason'] ) {
    fwrite( STDERR, "AI provider rate limit was not normalized\n" );
    exit( 1 );
}
if ( 1 !== $payload['meta']['cost']['requests'] || 1 !== $payload['meta']['cost']['provider_failures'] ) {
    fwrite( STDERR, "AI fallback did not record provider failure counters\n" );
    exit( 1 );
}
PHP

php_check 'invalid AI output should be blocked before reaching users' \
    env -i PATH="$PATH" \
        TONBANKCARD_FEATURE_AI=true \
        GROQ_API_KEY='groq-secret-key' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['ai']['transport'] = function ( $request ) {
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
                                    'title'                   => 'Bad advice',
                                    'summary'                 => 'Buy TON now before it runs.',
                                    'sentiment'               => 'bullish',
                                    'confidence'              => 0.99,
                                    'drivers'                 => [ 'Momentum.' ],
                                    'risks'                   => [],
                                    'uncertainty'             => '',
                                    'market_data_age_seconds' => 10,
                                    'not_financial_advice'    => FALSE,
                                ]
                            ),
                        ],
                    ],
                ],
                'usage' => [ 'prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15 ],
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
                'insight_type'            => 'market_summary',
                'subject'                 => 'TON market',
                'market_data_age_seconds' => 10,
                'market_data'             => [ 'change' => 'up' ],
            ]
        ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

$payload = json_decode( $response['body'], TRUE );
if ( 200 !== $response['status'] || TRUE !== $payload['ok'] || 'insight unavailable' !== $payload['data']['status'] ) {
    fwrite( STDERR, "Invalid AI output was not converted to the safe fallback\n" );
    exit( 1 );
}
if ( 'schema_validation_failed' !== $payload['data']['reason'] ) {
    fwrite( STDERR, "Invalid AI output did not fail schema validation\n" );
    exit( 1 );
}
if ( FALSE !== strpos( $response['body'], 'Buy TON now' ) ) {
    fwrite( STDERR, "Invalid AI advice reached the user response\n" );
    exit( 1 );
}
PHP

php_check 'AI health check should expose safe provider metadata only' \
    env -i PATH="$PATH" \
        TONBANKCARD_FEATURE_AI=true \
        GROQ_API_KEY='super-secret-groq-key' \
        GROQ_MODEL_ID='llama-3.3-70b-versatile' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/health',
        'headers' => [],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected health 200, got ' . $response['status'] . "\n" );
    exit( 1 );
}
if ( FALSE !== strpos( $response['body'], 'super-secret-groq-key' ) ) {
    fwrite( STDERR, "AI health response leaked a Groq key\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
$groq = $payload['data']['checks']['upstream_providers']['checks']['groq'];
if ( 'configured' !== $groq['status'] || 'llama-3.3-70b-versatile' !== $groq['model_id'] ) {
    fwrite( STDERR, "AI health check did not expose safe Groq configuration metadata\n" );
    exit( 1 );
}
if ( empty( $groq['cost_counters']['tracked'] ) || empty( $groq['rate_limit']['max_requests'] ) ) {
    fwrite( STDERR, "AI health check is missing cost counter or rate-limit metadata\n" );
    exit( 1 );
}
PHP

php_check 'AI insight cache hit should return a stored insight without calling the provider' \
    env -i PATH="$PATH" \
        TONBANKCARD_FEATURE_AI=true \
        GROQ_API_KEY='groq-secret-key' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$provider_called = 0;

$test_api = $api;
$test_api['ai']['transport'] = function ( $request ) use ( &$provider_called ) {
    $provider_called++;
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
                                    'title'                   => 'Cached TON context',
                                    'summary'                 => 'TON data is unchanged.',
                                    'sentiment'               => 'neutral',
                                    'confidence'              => 0.55,
                                    'drivers'                 => [],
                                    'risks'                   => [],
                                    'uncertainty'             => 'Narrow snapshot.',
                                    'market_data_age_seconds' => 30,
                                    'not_financial_advice'    => TRUE,
                                ]
                            ),
                        ],
                    ],
                ],
                'usage' => [ 'prompt_tokens' => 80, 'completion_tokens' => 30, 'total_tokens' => 110 ],
            ]
        ),
    ];
};

$cached_entries = [];
$test_api['redis']['transport'] = function ( $command ) use ( &$cached_entries ) {
    $cmd = $command[0];
    $key = isset( $command[1] ) ? $command[1] : '';
    if ( 'SET' === $cmd ) {
        $cached_entries[ $key ] = isset( $command[2] ) ? $command[2] : '';
        return [ 'result' => 'OK' ];
    }
    if ( 'GET' === $cmd ) {
        return [ 'result' => isset( $cached_entries[ $key ] ) ? $cached_entries[ $key ] : null ];
    }
    return [ 'result' => null ];
};
$test_api['cache']['enabled'] = true;
$test_api['redis']['enabled'] = true;
$test_api['redis']['rest_url'] = 'https://redis.example.com';
$test_api['redis']['rest_token'] = 'test-token';

$payload = json_encode(
    [
        'insight_type'            => 'market_summary',
        'subject'                 => 'TON market',
        'market_data_age_seconds' => 30,
        'market_data'             => [ 'price_change_percentage_24h' => 0.5 ],
    ]
);

$first = tonbankcard_api_handle(
    [ 'method' => 'POST', 'path' => '/api/ai/insight', 'headers' => [ 'content-type' => 'application/json' ], 'body' => $payload ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

$second = tonbankcard_api_handle(
    [ 'method' => 'POST', 'path' => '/api/ai/insight', 'headers' => [ 'content-type' => 'application/json' ], 'body' => $payload ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 200 !== $first['status'] || 200 !== $second['status'] ) {
    fwrite( STDERR, "Both insight requests should return 200\n" );
    exit( 1 );
}
if ( 1 !== $provider_called ) {
    fwrite( STDERR, "Provider should be called exactly once; cache should serve the second request (got $provider_called calls)\n" );
    exit( 1 );
}
$second_payload = json_decode( $second['body'], TRUE );
if ( 'available' !== $second_payload['data']['status'] ) {
    fwrite( STDERR, "Cache hit should return available insight status\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'AI provider check passed.'
