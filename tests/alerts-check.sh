#!/usr/bin/env sh
set -eu

assert_file() {
  if [ ! -f "$1" ]; then
    echo "Missing expected file: $1" >&2
    exit 1
  fi
}

assert_contains() {
  file="$1"
  needle="$2"
  if ! grep -F "$needle" "$file" >/dev/null 2>&1; then
    echo "Expected $file to contain: $needle" >&2
    exit 1
  fi
}

assert_file docs/v2-smart-alerts.md
assert_contains docs/v2-smart-alerts.md "# TONBANKCARD V2 Smart Alerts"
assert_contains docs/v2-smart-alerts.md "Issue: [#32]"
assert_contains docs/v2-smart-alerts.md "/api/alerts"
assert_contains docs/v2-smart-alerts.md "/api/alerts/evaluate"
assert_contains docs/v2-smart-alerts.md "startapp"
assert_contains docs/v2-smart-alerts.md "quiet hours"
assert_contains docs/v2-smart-alerts.md "frequency caps"
assert_contains docs/v2-smart-alerts.md "TONBANKCARD_FEATURE_ALERTS=true"
assert_contains docs/v2-smart-alerts.md "tests/alerts-check.sh"

assert_contains README.md "docs/v2-smart-alerts.md"
assert_contains package.json "\"test:alerts\""
assert_contains package.json "tests/alerts-check.sh"

assert_file api/alerts.php
assert_contains api/router.php "require_once __DIR__ . '/alerts.php';"
assert_contains api/router.php "'/api/alerts'"
assert_contains api/router.php "tonbankcard_api_alerts_is_request"
assert_contains api/router.php "tonbankcard_api_alerts_handle"
assert_contains api/alerts.php "function tonbankcard_api_alerts_normalize_rule_payload"
assert_contains api/alerts.php "function tonbankcard_api_alerts_evaluate_rule"
assert_contains api/alerts.php "function tonbankcard_api_alerts_delivery_payload"
assert_contains api/alerts.php "rank_change"
assert_contains api/alerts.php "sentiment_change"
assert_contains api/alerts.php "ton_ecosystem"
assert_contains api/alerts.php "frequency_cap_seconds"
assert_contains api/alerts.php "max_deliveries_per_day"
assert_contains api/alerts.php "startapp"

assert_file database/migrations/0007_smart_alerts.up.sql
assert_file database/migrations/0007_smart_alerts.down.sql
assert_contains database/migrations/0007_smart_alerts.up.sql "rank_change"
assert_contains database/migrations/0007_smart_alerts.up.sql "sentiment_change"
assert_contains database/migrations/0007_smart_alerts.up.sql "ton_ecosystem"
assert_contains database/migrations/0007_smart_alerts.up.sql "frequency_cap_seconds"
assert_contains database/migrations/0007_smart_alerts.up.sql "max_deliveries_per_day"
assert_contains database/migrations/0007_smart_alerts.up.sql "deep_link_url"

assert_contains config/api.php "TONBANKCARD_ALERT_WORKER_TOKEN"
assert_contains config/api.php "alerts"
assert_contains .env.example "TONBANKCARD_ALERT_DEFAULT_FREQUENCY_CAP_SECONDS"

assert_file templates/routes/alerts.php
assert_file dev/js/src/alerts.js
assert_file dev/js/src/routes/alerts.js
assert_contains config/routes.php "'alerts'"
assert_contains config/routes.php "'/alerts'"
assert_contains config/routes.php "'/app/alert/:id'"
assert_contains config/routes-v2.php "'alerts'"
assert_contains dev/js/source.json "\"alerts.js\""
assert_contains dev/js/source.json "\"routes/alerts.js\""
assert_contains dev/js/src/routes/currency.js "alertDraft"
assert_contains dev/js/src/routes/currency.js "name: 'alerts'"
assert_contains templates/routes/alerts.php "alert-rule-form"
assert_contains templates/routes/alerts.php "alert-rule-card"
assert_contains assets/css/style.css "alert-rule-form"
assert_contains assets/css/style.css "alerts-rule-grid"

php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

function assert_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$request = [
    'method' => 'GET',
    'path' => '/api/alerts',
    'headers' => [],
];
$runtime = ['feature_flags' => ['alerts' => true]];
$config = ['alerts' => ['max_alerts_per_user' => 20]];
$response = tonbankcard_api_handle($request, [], $runtime, $config);
$body = json_decode($response['body'], true);
assert_true($response['status'] === 401, 'Anonymous alerts list must require a trusted session.');
assert_true(($body['error']['code'] ?? '') === 'alerts_session_required', 'Missing expected alerts_session_required error.');

$disabled = tonbankcard_api_handle($request, [], ['feature_flags' => ['alerts' => false]], $config);
$disabled_body = json_decode($disabled['body'], true);
assert_true($disabled['status'] === 503, 'Disabled alerts feature should return an unavailable API response.');
assert_true(($disabled_body['error']['code'] ?? '') === 'alerts_disabled', 'Missing expected alerts_disabled error.');

$settings = tonbankcard_api_alerts_settings(['bot_username' => 'MarketCapBot'], [
    'alerts' => [
        'default_frequency_cap_seconds' => 900,
        'max_alerts_per_user' => 20,
        'default_max_deliveries_per_day' => 6,
    ],
]);
$normalized = tonbankcard_api_alerts_normalize_rule_payload([
    'coin_id' => 'toncoin',
    'symbol' => 'TON',
    'trigger_type' => 'rank_change',
    'operator' => 'lte',
    'threshold' => 10,
    'quiet_start' => '22:00',
    'quiet_end' => '06:30',
    'timezone' => 'UTC',
], $settings);
assert_true($normalized['ok'] === true, 'Rank-change alerts should normalize successfully.');
assert_true($normalized['rule']['frequency_cap_seconds'] === 900, 'Default frequency cap was not applied.');
assert_true($normalized['rule']['max_deliveries_per_day'] === 6, 'Default daily delivery cap was not applied.');

$rule = [
    'id' => 42,
    'coin_id' => 'toncoin',
    'symbol' => 'TON',
    'trigger_type' => 'price_cross',
    'operator' => 'gte',
    'threshold_value' => 5.00,
    'status' => 'active',
    'frequency_cap_seconds' => 3600,
    'max_deliveries_per_day' => 4,
    'quiet_start' => null,
    'quiet_end' => null,
    'timezone' => 'UTC',
    'last_triggered_at' => '2026-05-01 09:00:00',
];
$event = tonbankcard_api_alerts_evaluate_rule($rule, ['current_price' => 5.25], $settings, strtotime('2026-05-01 11:01:00 UTC'));
assert_true($event['triggered'] === true, 'Price-cross alert should trigger above threshold.');
$blocked = tonbankcard_api_alerts_evaluate_rule($rule, ['current_price' => 5.25], $settings, strtotime('2026-05-01 09:30:00 UTC'));
assert_true($blocked['triggered'] === false && $blocked['reason'] === 'frequency_cap', 'Frequency cap should suppress repeated alerts.');
$duplicateRule = $rule;
$duplicateRule['last_triggered_at'] = null;
$duplicateRule['last_delivery_fingerprint'] = $event['fingerprint'];
$duplicate = tonbankcard_api_alerts_evaluate_rule($duplicateRule, ['current_price' => 5.25], $settings, strtotime('2026-05-01 11:02:00 UTC'));
assert_true($duplicate['triggered'] === false && $duplicate['reason'] === 'duplicate_fingerprint', 'Last delivery fingerprint should suppress duplicate alert content.');

$quiet = $rule;
$quiet['quiet_start'] = '22:00:00';
$quiet['quiet_end'] = '06:00:00';
$quiet['last_triggered_at'] = null;
$quietEvent = tonbankcard_api_alerts_evaluate_rule($quiet, ['current_price' => 5.25], $settings, strtotime('2026-05-01 23:30:00 UTC'));
assert_true($quietEvent['triggered'] === false && $quietEvent['reason'] === 'quiet_hours', 'Quiet hours should suppress alert delivery.');

$payload = tonbankcard_api_alerts_delivery_payload($rule, $event, $settings, true);
assert_true(strpos($payload['links']['mini_app_path'], '/app/alert/42') !== false, 'Test delivery should target alert detail route.');
assert_true(strpos($payload['links']['telegram_deep_link'], 'startapp=alert_42') !== false, 'Telegram delivery must include startapp alert deep link.');
assert_true(strpos($payload['text'], 'TON') !== false, 'Delivery text should identify the matching asset.');

// F4 (#187): the worker endpoint must fail closed when the token is unset.
$evaluate_request = [
    'method' => 'POST',
    'path' => '/api/alerts/evaluate',
    'headers' => [],
];
$worker_runtime = ['feature_flags' => ['alerts' => true]];

$unset = tonbankcard_api_handle($evaluate_request, [], $worker_runtime, ['alerts' => []]);
$unset_body = json_decode($unset['body'], true);
assert_true($unset['status'] === 503, 'Alert worker must fail closed (503) when the token is unset.');
assert_true(($unset_body['error']['code'] ?? '') === 'alerts_worker_token_unset', 'Missing expected alerts_worker_token_unset error.');

$worker_config = ['alerts' => ['worker_token' => 'secret-worker-token']];
$invalid_request = $evaluate_request;
$invalid_request['headers'] = ['x-tonbankcard-alert-worker-token' => 'wrong-token'];
$invalid = tonbankcard_api_handle($invalid_request, [], $worker_runtime, $worker_config);
$invalid_body = json_decode($invalid['body'], true);
assert_true($invalid['status'] === 401, 'Invalid worker token must be rejected with 401.');
assert_true(($invalid_body['error']['code'] ?? '') === 'alerts_worker_token_required', 'Missing expected alerts_worker_token_required error.');

$valid_request = $evaluate_request;
$valid_request['headers'] = ['x-tonbankcard-alert-worker-token' => 'secret-worker-token'];
$authorized = tonbankcard_api_handle($valid_request, [], $worker_runtime, $worker_config);
$authorized_body = json_decode($authorized['body'], true);
$authorized_code = $authorized_body['error']['code'] ?? '';
assert_true($authorized_code !== 'alerts_worker_token_unset' && $authorized_code !== 'alerts_worker_token_required', 'Valid worker token must pass authentication.');

$alertsSource = file_get_contents('api/alerts.php');
assert_true(FALSE !== strpos($alertsSource, 'function tonbankcard_api_alerts_retry_deliveries'), 'Alert worker should include a queued-delivery retry consumer.');
assert_true(FALSE !== strpos($alertsSource, 'FOR UPDATE SKIP LOCKED'), 'Alert worker should claim due rules or retries atomically.');
assert_true(FALSE !== strpos($alertsSource, 'evaluation_claim_token'), 'Alert worker should include a token fallback for due-rule claims.');
assert_true(FALSE !== strpos($alertsSource, 'retry_claim_token'), 'Alert worker should include a token fallback for queued retry claims.');
assert_true(FALSE !== strpos($alertsSource, "delivery_status = 'queued'"), 'Alert retry consumer should select queued deliveries.');
assert_true(FALSE !== strpos($alertsSource, "delivery_status IN ('sent', 'queued')"), 'Daily delivery caps should reserve capacity for sent and queued deliveries.');
assert_true(FALSE !== strpos($alertsSource, "tonbankcard_api_alerts_daily_cap_reached( \$pdo, \$row"), 'Retry delivery should enforce the daily cap before sending.');
PHP

echo "Smart alerts check passed."
