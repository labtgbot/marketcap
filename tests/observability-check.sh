#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-observability-operational-logging.md

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

assert_file api/observability.php
assert_file api/router.php
assert_file api/market.php
assert_file dev/js/src/observability.js
assert_file "$doc"

assert_contains "$doc" '^# TONBANKCARD V2 Observability And Operational Logging$' 'the observability title'
assert_contains "$doc" 'Issue: \[#18\]' 'the issue reference'
assert_contains "$doc" 'TONBANKCARD_OBSERVABILITY_LOG_LEVEL' 'the log level environment flag'
assert_contains "$doc" 'TONBANKCARD_VERBOSE_TRACING' 'the verbose tracing environment flag'
assert_contains "$doc" '/api/observability/client-error' 'the frontend error endpoint'
assert_contains "$doc" 'request_id' 'request id traceability'
assert_contains "$doc" 'queue failure' 'queue failure logging guidance'
assert_contains "$doc" 'bot delivery failure' 'bot delivery failure logging guidance'
assert_contains "$doc" 'Core Service Health Queries' 'documented health query section'
assert_contains "$doc" 'Common Failure Modes' 'operational runbook failure modes'
assert_contains README.md 'docs/v2-observability-operational-logging\.md' 'the observability documentation link'
assert_contains .env.example '^TONBANKCARD_OBSERVABILITY_LOG_LEVEL=warning$' 'default warning log level'
assert_contains .env.example '^TONBANKCARD_VERBOSE_TRACING=false$' 'default-off verbose tracing'
assert_contains .env.example '^TONBANKCARD_CLIENT_ERROR_REPORTING=true$' 'frontend error reporting flag'
assert_contains .env.example '^TONBANKCARD_ERROR_MONITORING_ENABLED=false$' 'default-off error monitoring'
assert_contains .env.example '^TONBANKCARD_ERROR_MONITORING_DSN=$' 'empty error monitoring DSN default'
assert_contains .env.example '^TONBANKCARD_ERROR_MONITORING_MIN_LEVEL=error$' 'error monitoring severity threshold'
assert_contains .env.example '^TONBANKCARD_UPTIME_MONITOR_ENABLED=false$' 'default-off uptime monitor'
assert_contains .env.example '^TONBANKCARD_UPTIME_MONITOR_TARGETS=/api/health,/api/ready$' 'uptime monitor health targets'
assert_file api/uptime-monitor.php
assert_contains "$doc" 'api/uptime-monitor\.php' 'the bundled uptime monitor entrypoint'
assert_contains "$doc" 'TONBANKCARD_UPTIME_MONITOR_ENABLED' 'the uptime monitor flag'
assert_contains docs/release-checklist.md 'TONBANKCARD_ERROR_MONITORING_ENABLED' 'error monitoring flag in the release checklist'
assert_contains docs/release-checklist.md '/api/ready' 'uptime monitor target in the release checklist'
assert_contains docs/release-checklist.md 'api/uptime-monitor\.php' 'the bundled uptime monitor in the release checklist'
assert_contains docs/release-checklist.md 'Monitoring owner' 'monitoring ownership in the production verification matrix'
assert_contains package.json '"test:observability"' 'the observability npm script'
assert_contains package.json 'test:observability' 'the aggregate observability check'
assert_contains dev/js/source.json 'observability\.js' 'the generated bundle source list entry'
assert_contains dev/js/src/coingecko.js 'instrumentAxiosInstance' 'frontend API request id and error instrumentation'

php_check 'failed market requests should log API and provider records with the same request id without leaking secrets' \
    env -i PATH="$PATH" \
        TONBANKCARD_OBSERVABILITY_LOG_LEVEL=warning \
        COINGECKO_API_KEY='server-secret-api-key' \
        TONBANKCARD_BOT_TOKEN='super-secret-bot-token' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$log_file = tempnam( sys_get_temp_dir(), 'tonbankcard-observability-' );
ini_set( 'error_log', $log_file );

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) {
    return [
        'error'   => 'timeout',
        'message' => 'Timeout while using api_key=server-secret-api-key',
        'status'  => 0,
        'headers' => [],
    ];
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/market/coins/markets?vs_currency=usd',
        'headers' => [ 'x-request-id' => 'trace-market-1' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

if ( 504 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 504 market timeout response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

$logs = file_get_contents( $log_file );
if ( FALSE === $logs || FALSE === strpos( $logs, 'trace-market-1' ) ) {
    fwrite( STDERR, "Observability logs did not include the request id\n" );
    exit( 1 );
}
foreach ( [ 'market.provider_response', 'api.request_completed', 'provider_timeout' ] as $needle ) {
    if ( FALSE === strpos( $logs, $needle ) ) {
        fwrite( STDERR, "Observability logs are missing $needle\n" );
        exit( 1 );
    }
}
foreach ( [ 'server-secret-api-key', 'super-secret-bot-token' ] as $secret ) {
    if ( FALSE !== strpos( $logs, $secret ) ) {
        fwrite( STDERR, "Observability logs leaked a secret value\n" );
        exit( 1 );
    }
}
PHP

php_check 'successful requests should respect the default warning level and verbose tracing opt-in' \
    env -i PATH="$PATH" \
        TONBANKCARD_OBSERVABILITY_LOG_LEVEL=warning \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$warning_log = tempnam( sys_get_temp_dir(), 'tonbankcard-observability-warning-' );
ini_set( 'error_log', $warning_log );
tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/health',
        'headers' => [ 'x-request-id' => 'quiet-success' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( '' !== trim( (string) file_get_contents( $warning_log ) ) ) {
    fwrite( STDERR, "Warning-level observability should not log successful health checks\n" );
    exit( 1 );
}

$debug_log = tempnam( sys_get_temp_dir(), 'tonbankcard-observability-debug-' );
ini_set( 'error_log', $debug_log );
$debug_api = $api;
$debug_api['observability']['verbose_tracing'] = TRUE;
tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/health',
        'headers' => [ 'x-request-id' => 'verbose-success' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $debug_api
);

$logs = file_get_contents( $debug_log );
if ( FALSE === $logs || FALSE === strpos( $logs, 'api.request_completed' ) || FALSE === strpos( $logs, 'verbose-success' ) ) {
    fwrite( STDERR, "Verbose tracing should log successful API completion with request id\n" );
    exit( 1 );
}
PHP

php_check 'frontend error ingestion should accept allowlisted fields and redact sensitive values' \
    env -i PATH="$PATH" \
        TONBANKCARD_OBSERVABILITY_LOG_LEVEL=warning \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$log_file = tempnam( sys_get_temp_dir(), 'tonbankcard-client-error-' );
ini_set( 'error_log', $log_file );

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/observability/client-error',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'ingest-1',
        ],
        'body'    => json_encode(
            [
                'type'            => 'api_error',
                'client_event_id' => 'client-event-1',
                'request_id'      => 'frontend-api-1',
                'message'         => 'Request failed with token=browser-secret-token',
                'source'          => 'https://marketcap.tonbankcard.com/currency/bitcoin?token=browser-secret-token',
                'route_path'      => 'token=route-secret-token',
                'api_path'        => '/api/market/coins/markets?x_cg_demo_api_key=browser-secret-key',
                'method'          => 'GET',
                'status'          => 502,
                'error_code'      => 'provider_unavailable',
                'unexpected'      => 'must be dropped',
            ]
        ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

if ( 202 !== $response['status'] ) {
    fwrite( STDERR, 'Expected 202 client error response, got ' . $response['status'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
if ( ! is_array( $payload ) || empty( $payload['data']['accepted'] ) ) {
    fwrite( STDERR, "Client error response did not acknowledge the event\n" );
    exit( 1 );
}

$logs = file_get_contents( $log_file );
foreach ( [ 'frontend.api_error', 'frontend-api-1', 'provider_unavailable' ] as $needle ) {
    if ( FALSE === strpos( $logs, $needle ) ) {
        fwrite( STDERR, "Client error logs are missing $needle\n" );
        exit( 1 );
    }
}
foreach ( [ 'browser-secret-token', 'browser-secret-key', 'route-secret-token', 'must be dropped' ] as $forbidden ) {
    if ( FALSE !== strpos( $logs, $forbidden ) ) {
        fwrite( STDERR, "Client error logs included unsafe data: $forbidden\n" );
        exit( 1 );
    }
}
PHP

php_check 'queue and bot delivery failure helpers should emit redacted operational logs' \
    env -i PATH="$PATH" \
        TONBANKCARD_OBSERVABILITY_LOG_LEVEL=warning \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/observability.php';

$log_file = tempnam( sys_get_temp_dir(), 'tonbankcard-queue-bot-' );
ini_set( 'error_log', $log_file );

tonbankcard_observability_log_queue_failure(
    $GLOBALS['runtime_config'],
    $api,
    'queue-request-1',
    'alert_delivery',
    'enqueue',
    [ 'job_id' => 'job-1', 'payload_token' => 'queue-secret-token' ]
);
tonbankcard_observability_log_bot_delivery_failure(
    $GLOBALS['runtime_config'],
    $api,
    'bot-request-1',
    'telegram',
    'sendMessage',
    [ 'telegram_user_id_hash' => 'hash-1', 'bot_token' => 'bot-secret-token' ]
);

$logs = file_get_contents( $log_file );
foreach ( [ 'queue.failure', 'bot.delivery_failure', 'queue-request-1', 'bot-request-1' ] as $needle ) {
    if ( FALSE === strpos( $logs, $needle ) ) {
        fwrite( STDERR, "Operational helper logs are missing $needle\n" );
        exit( 1 );
    }
}
foreach ( [ 'queue-secret-token', 'bot-secret-token' ] as $secret ) {
    if ( FALSE !== strpos( $logs, $secret ) ) {
        fwrite( STDERR, "Operational helper logs leaked a secret value\n" );
        exit( 1 );
    }
}
PHP

php_check 'error-aggregation forwarding stays disabled by default and only forwards severe redacted events when enabled' \
    env -i PATH="$PATH" \
        php <<'PHP'
<?php
require 'constants.php';
require __DIR__ . '/api/observability.php';

$captured = [];
$transport = function ( $request ) use ( &$captured ) {
    $captured[] = $request;
    return TRUE;
};

// Disabled by default: no DSN, no transport invocation even for critical events.
$runtime = [ 'observability' => [] ];
$config = [ 'observability' => [] ];
tonbankcard_observability_log( $runtime, $config, 'critical', 'forward.default', [ 'request_id' => 'default-1' ] );
if ( ! empty( $captured ) ) {
    fwrite( STDERR, "Forwarding must stay disabled when no DSN is configured\n" );
    exit( 1 );
}

$monitored = [
    'observability' => [
        'log_level'        => 'warning',
        'error_monitoring' => [
            'enabled'     => TRUE,
            'dsn'         => 'https://public-key@sentry.example.com/42',
            'min_level'   => 'error',
            'environment' => 'staging',
            'transport'   => $transport,
        ],
    ],
];

// Below-threshold warnings are logged locally but not forwarded.
tonbankcard_observability_log( $runtime, $monitored, 'warning', 'forward.warning', [ 'request_id' => 'warn-1' ] );
if ( ! empty( $captured ) ) {
    fwrite( STDERR, "Warnings below the min level must not be forwarded\n" );
    exit( 1 );
}

// Error-level events forward a Sentry-store request with redacted context only.
tonbankcard_observability_log(
    $monitored,
    $monitored,
    'error',
    'forward.error',
    [ 'request_id' => 'error-1', 'bot_token' => 'forward-secret-token', 'note' => 'token=inline-secret-token' ]
);

if ( 1 !== count( $captured ) ) {
    fwrite( STDERR, 'Expected exactly one forwarded request, got ' . count( $captured ) . "\n" );
    exit( 1 );
}

$request = $captured[0];
if ( 'sentry' !== $request['transport'] ) {
    fwrite( STDERR, "Sentry DSN should select the sentry transport\n" );
    exit( 1 );
}
if ( 'https://sentry.example.com/api/42/store/' !== $request['url'] ) {
    fwrite( STDERR, 'Unexpected Sentry store endpoint: ' . $request['url'] . "\n" );
    exit( 1 );
}
if ( FALSE === strpos( implode( "\n", $request['headers'] ), 'sentry_key=public-key' ) ) {
    fwrite( STDERR, "Sentry auth header must carry the public key\n" );
    exit( 1 );
}
foreach ( [ 'forward.error', 'error-1', 'staging' ] as $needle ) {
    if ( FALSE === strpos( $request['body'], $needle ) ) {
        fwrite( STDERR, "Forwarded payload is missing $needle\n" );
        exit( 1 );
    }
}
foreach ( [ 'forward-secret-token', 'inline-secret-token' ] as $secret ) {
    if ( FALSE !== strpos( $request['body'], $secret ) ) {
        fwrite( STDERR, "Forwarded payload leaked a secret value\n" );
        exit( 1 );
    }
}

// Plain webhook DSNs forward a generic JSON envelope.
$webhook_captured = [];
$webhook = [
    'observability' => [
        'log_level'        => 'warning',
        'error_monitoring' => [
            'enabled'   => TRUE,
            'dsn'       => 'https://hooks.example.com/ingest/123',
            'min_level' => 'error',
            'transport' => function ( $request ) use ( &$webhook_captured ) {
                $webhook_captured[] = $request;
                return TRUE;
            },
        ],
    ],
];
tonbankcard_observability_log( $webhook, $webhook, 'critical', 'forward.webhook', [ 'request_id' => 'hook-1' ] );
if ( 1 !== count( $webhook_captured ) || 'webhook' !== $webhook_captured[0]['transport'] ) {
    fwrite( STDERR, "Plain HTTP DSN should select the webhook transport\n" );
    exit( 1 );
}
if ( FALSE === strpos( $webhook_captured[0]['body'], 'hook-1' ) ) {
    fwrite( STDERR, "Webhook payload is missing the forwarded entry\n" );
    exit( 1 );
}
PHP

php_check 'uptime monitor stays disabled by default and pages Telegram only when a health probe fails' \
    env -i PATH="$PATH" \
        php <<'PHP'
<?php
require 'constants.php';
require __DIR__ . '/api/observability.php';

// Disabled by default: no probes, no alert.
$disabled = tonbankcard_observability_uptime_run(
    tonbankcard_observability_uptime_settings( [ 'observability' => [] ], [ 'observability' => [] ] )
);
if ( ! empty( $disabled['ran'] ) || 'disabled' !== $disabled['reason'] ) {
    fwrite( STDERR, "Uptime monitor must stay disabled without an explicit opt-in\n" );
    exit( 1 );
}

$base_config = function ( $probe, &$alerts ) {
    return [
        'observability' => [
            'uptime' => [
                'enabled'         => TRUE,
                'base_url'        => 'https://marketcap.example.com/',
                'targets'         => [ '/api/health', '/api/ready' ],
                'bot_token'       => 'uptime-secret-bot-token',
                'chat_id'         => '-1001234567890',
                'environment'     => 'production',
                'transport'       => $probe,
                'alert_transport' => function ( $request ) use ( &$alerts ) {
                    $alerts[] = $request;
                    return TRUE;
                },
            ],
        ],
    ];
};

// Healthy probes: no alert is sent.
$alerts = [];
$healthy = function ( $url, $timeout_ms, $target ) {
    return '/api/ready' === $target
        ? [ 'status' => 200, 'body' => json_encode( [ 'data' => [ 'ready' => TRUE ] ] ) ]
        : [ 'status' => 200, 'body' => json_encode( [ 'status' => 'ok' ] ) ];
};
$config = $base_config( $healthy, $alerts );
$summary = tonbankcard_observability_uptime_run( tonbankcard_observability_uptime_settings( $config, $config ) );
if ( 2 !== $summary['checked'] || ! empty( $summary['failures'] ) || ! empty( $summary['alerted'] ) || ! empty( $alerts ) ) {
    fwrite( STDERR, "Healthy probes must not page the alert channel\n" );
    exit( 1 );
}

// A non-200 health endpoint and a ready:false readiness endpoint both fail and page once.
$alerts = [];
$unhealthy = function ( $url, $timeout_ms, $target ) {
    return '/api/ready' === $target
        ? [ 'status' => 200, 'body' => json_encode( [ 'data' => [ 'ready' => FALSE ] ] ) ]
        : [ 'status' => 503, 'body' => '' ];
};
$config = $base_config( $unhealthy, $alerts );
$summary = tonbankcard_observability_uptime_run( tonbankcard_observability_uptime_settings( $config, $config ) );
if ( 2 !== count( $summary['failures'] ) || empty( $summary['alerted'] ) || 1 !== count( $alerts ) ) {
    fwrite( STDERR, 'Failing probes must page exactly one alert, got ' . count( $alerts ) . "\n" );
    exit( 1 );
}
if ( 'telegram' !== $alerts[0]['transport'] ) {
    fwrite( STDERR, "Uptime alert must use the telegram transport\n" );
    exit( 1 );
}
foreach ( [ 'status_503', 'not_ready', 'production' ] as $needle ) {
    if ( FALSE === strpos( $alerts[0]['body'], $needle ) ) {
        fwrite( STDERR, "Uptime alert body is missing $needle\n" );
        exit( 1 );
    }
}
// The bot token lives only in the API URL and must never reach the summary output.
if ( FALSE !== strpos( json_encode( $summary ), 'uptime-secret-bot-token' ) ) {
    fwrite( STDERR, "Uptime summary leaked the bot token\n" );
    exit( 1 );
}

// Failures without a configured Telegram channel are reported but never alert.
$alerts = [];
$config = $base_config( $unhealthy, $alerts );
$config['observability']['uptime']['chat_id'] = '';
$summary = tonbankcard_observability_uptime_run( tonbankcard_observability_uptime_settings( $config, $config ) );
if ( empty( $summary['failures'] ) || ! empty( $summary['alerted'] ) || ! empty( $alerts ) || ! empty( $summary['alert_configured'] ) ) {
    fwrite( STDERR, "Missing Telegram channel must disable paging without throwing\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Observability check passed.'
