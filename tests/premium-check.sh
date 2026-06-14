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

assert_file docs/v2-premium-subscriptions.md
assert_contains docs/v2-premium-subscriptions.md "# TONBANKCARD V2 Premium Subscriptions"
assert_contains docs/v2-premium-subscriptions.md "Issue: [#37]"
assert_contains docs/v2-premium-subscriptions.md "Telegram Stars"
assert_contains docs/v2-premium-subscriptions.md "XTR"
assert_contains docs/v2-premium-subscriptions.md "provider_token"
assert_contains docs/v2-premium-subscriptions.md "2592000"
assert_contains docs/v2-premium-subscriptions.md "refund"
assert_contains docs/v2-premium-subscriptions.md "tests/premium-check.sh"

assert_contains README.md "docs/v2-premium-subscriptions.md"
assert_contains README.md "npm run test:premium"
assert_contains package.json "\"test:premium\""
assert_contains package.json "tests/premium-check.sh"

assert_file api/premium.php
assert_contains api/router.php "require_once __DIR__ . '/premium.php';"
assert_contains api/router.php "'/api/premium/checkout'"
assert_contains api/router.php "tonbankcard_api_premium_is_request"
assert_contains api/router.php "tonbankcard_api_premium_handle"
assert_contains api/telegram-bot.php "pre_checkout_query"
assert_contains api/telegram-bot.php "successful_payment"
assert_contains api/telegram-bot.php "refunded_payment"
assert_contains api/premium.php "function tonbankcard_api_premium_create_invoice_link"
assert_contains api/premium.php "createInvoiceLink"
assert_contains api/premium.php "'provider_token' => ''"
assert_contains api/premium.php "'currency'       => 'XTR'"
assert_contains api/premium.php "'subscription_period'"
assert_contains api/premium.php "answerPreCheckoutQuery"
assert_contains api/premium.php "editUserStarSubscription"
assert_contains api/premium.php "status = 'expired'"
assert_contains api/premium.php "status = 'revoked'"

assert_file database/migrations/0010_premium_payment_state.up.sql
assert_file database/migrations/0010_premium_payment_state.down.sql
assert_file database/migrations/0011_stage9_reliability_hardening.up.sql
assert_file database/migrations/0011_stage9_reliability_hardening.down.sql
assert_contains database/migrations/0010_premium_payment_state.up.sql "premium_payment_events"
assert_contains database/migrations/0010_premium_payment_state.up.sql "last_telegram_payment_charge_id"
assert_contains database/migrations/0010_premium_payment_state.up.sql "last_telegram_payment_charge_id_hash"
assert_contains database/migrations/0010_premium_payment_state.up.sql "cancel_at_period_end"
assert_contains database/migrations/0010_premium_payment_state.up.sql "refunded_at"
assert_contains database/migrations/0010_premium_payment_state.up.sql "subscription_period_seconds"
assert_contains database/migrations/0011_stage9_reliability_hardening.up.sql "UNIQUE KEY \`uniq_premium_payment_events_charge_event\`"
assert_contains docs/v2-database-schema-and-migrations.md "premium_payment_events"
assert_contains docs/v2-database-schema-and-migrations.md "0010_premium_payment_state"
assert_contains docs/v2-database-schema-and-migrations.md "0011_stage9_reliability_hardening"
assert_contains api/premium.php "tonbankcard_api_premium_reserve_payment_event"
assert_contains api/premium.php "beginTransaction()"
assert_contains api/premium.php "commit()"
assert_contains api/premium.php "rollBack()"

assert_contains config/api.php "TONBANKCARD_PREMIUM_MONTHLY_STARS"
assert_contains config/api.php "TONBANKCARD_PREMIUM_SUBSCRIPTION_PERIOD_SECONDS"
assert_contains config/api.php "'premium_limits'"
assert_contains .env.example "TONBANKCARD_PREMIUM_MONTHLY_STARS"
assert_contains .env.example "TONBANKCARD_PREMIUM_SIGNING_SECRET"

assert_file templates/routes/premium.php
assert_file dev/js/src/premium.js
assert_file dev/js/src/routes/premium.js
assert_contains config/routes.php "'premium'"
assert_contains config/routes.php "'/premium'"
assert_contains config/routes-v2.php "'premium'"
assert_contains config/navigation.php "'Premium'"
assert_contains views/app-scripts.php "premiumConfig"
assert_contains dev/js/source.json "\"premium.js\""
assert_contains dev/js/source.json "\"routes/premium.js\""
assert_contains templates/routes/premium.php "Stars checkout"
assert_contains templates/routes/premium.php "Current entitlement"
assert_contains assets/css/style.css "premium-benefit-grid"
assert_contains assets/css/style.css "premium-plan-card"

assert_contains api/watchlist.php "watchlist_limit_reached"
assert_contains api/watchlist.php "tonbankcard_api_premium_limits_for_user"
assert_contains api/alerts.php "alerts_limit_reached"
assert_contains api/alerts.php "tonbankcard_api_alerts_apply_premium_limits"
assert_contains api/screener.php "premium_required"
assert_contains api/screener.php "advanced_range"

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

$lastTelegramCall = null;
$writerCall = null;
$redeemedCharges = [];
$runtime = [
    'feature_flags' => ['premium' => true],
    'telegram' => [
        'bot_token' => '123456:test-token',
        'bot_username' => 'MarketCapBot',
    ],
];
$config = [
    'premium' => [
        'monthly_plan_code' => 'premium_monthly',
        'monthly_price_stars' => 199,
        'subscription_period_seconds' => 2592000,
        'signing_secret' => 'premium-dedicated-signing-secret',
        'transport' => function ($method, $body) use (&$lastTelegramCall) {
            $lastTelegramCall = ['method' => $method, 'body' => $body];
            return ['ok' => true, 'result' => 'https://t.me/$MarketCapBot?start=stars'];
        },
        'payment_replay_lookup' => function ($payload, $payment) use (&$redeemedCharges) {
            $chargeId = isset($payment['telegram_payment_charge_id']) ? (string) $payment['telegram_payment_charge_id'] : '';
            return '' !== $chargeId && isset($redeemedCharges[$chargeId]);
        },
        'entitlement_writer' => function ($message, $payment, $plan, $settings, $parsed) use (&$writerCall, &$redeemedCharges) {
            $chargeId = isset($payment['telegram_payment_charge_id']) ? (string) $payment['telegram_payment_charge_id'] : '';
            if ('' !== $chargeId) {
                $redeemedCharges[$chargeId] = true;
            }
            $writerCall = [
                'plan' => $plan['code'],
                'telegram_user_id' => $parsed['telegram_user_id'],
                'amount' => $payment['total_amount'],
            ];
            return [
                'id' => 77,
                'plan_code' => $plan['code'],
                'status' => 'active',
                'expires_at' => '2026-05-31 00:00:00',
            ];
        },
    ],
    'telegram_bot' => [],
];

$settings = tonbankcard_api_premium_settings($runtime, $config);
assert_true($settings['enabled'] === true, 'Premium feature flag should be reflected in settings.');
assert_true($settings['currency'] === 'XTR', 'Premium checkout currency must be XTR.');
assert_true($settings['provider_token'] === '', 'Telegram Stars invoices must use an empty provider token.');
assert_true($settings['subscription_period_seconds'] === 2592000, 'Monthly Stars subscription period should be 2592000 seconds.');
assert_true($settings['plans']['free']['limits']['alerts_per_user'] === 3, 'Free alert limit should be defined.');
assert_true($settings['plans']['premium_monthly']['limits']['watchlist_entries'] === 250, 'Premium watchlist limit should be defined.');

$publicPlans = tonbankcard_api_premium_public_plans($settings);
assert_true(count($publicPlans) === 2, 'Public plans should include free and premium tiers.');
assert_true($publicPlans[1]['price_stars'] === 199, 'Premium pricing should be visible before checkout.');
assert_true($publicPlans[1]['currency'] === 'XTR', 'Public premium plan should disclose Stars currency.');

$payload = tonbankcard_api_premium_invoice_payload('premium_monthly', 42, 987654321, $settings);
$parsed = tonbankcard_api_premium_parse_invoice_payload($payload, $settings);
assert_true(is_array($parsed), 'Signed premium invoice payload should parse.');
assert_true($parsed['user_id'] === 42, 'Invoice payload should bind the internal user id.');
assert_true($parsed['telegram_user_id'] === 987654321, 'Invoice payload should bind the Telegram user id.');

$invoice = tonbankcard_api_premium_create_invoice_link(
    $settings['plans']['premium_monthly'],
    42,
    987654321,
    $runtime,
    $config,
    $settings
);
assert_true($invoice['ok'] === true, 'Stars invoice link should be created through the Telegram transport.');
assert_true($lastTelegramCall['method'] === 'createInvoiceLink', 'Checkout should call createInvoiceLink.');
assert_true($lastTelegramCall['body']['currency'] === 'XTR', 'Invoice request should use XTR.');
assert_true($lastTelegramCall['body']['provider_token'] === '', 'Invoice request should use an empty provider token.');
assert_true($lastTelegramCall['body']['subscription_period'] === 2592000, 'Invoice request should set the Stars subscription period.');
assert_true($lastTelegramCall['body']['prices'][0]['amount'] === 199, 'Invoice request should include configured Stars price.');

$preCheckout = tonbankcard_api_premium_pre_checkout_response(
    [
        'id' => 'pre-checkout-id',
        'from' => ['id' => 987654321],
        'currency' => 'XTR',
        'total_amount' => 199,
        'invoice_payload' => $payload,
    ],
    $runtime,
    $config,
    'premium-pre-checkout'
);
assert_true($preCheckout['ok'] === true, 'Pre-checkout response should be accepted by the webhook layer.');
assert_true($preCheckout['payload']['method'] === 'answerPreCheckoutQuery', 'Pre-checkout should answer Telegram directly.');
assert_true($preCheckout['payload']['ok'] === true, 'Valid Stars pre-checkout should be approved.');

$badPreCheckout = tonbankcard_api_premium_pre_checkout_response(
    [
        'id' => 'bad-pre-checkout-id',
        'from' => ['id' => 987654321],
        'currency' => 'XTR',
        'total_amount' => 1,
        'invoice_payload' => $payload,
    ],
    $runtime,
    $config,
    'premium-bad-pre-checkout'
);
assert_true($badPreCheckout['payload']['ok'] === false, 'Mismatched Stars amount should be rejected.');

$paymentResponse = tonbankcard_api_premium_successful_payment_response(
    [
        'from' => ['id' => 987654321, 'language_code' => 'en'],
        'chat' => ['id' => 987654321],
        'successful_payment' => [
            'currency' => 'XTR',
            'total_amount' => 199,
            'invoice_payload' => $payload,
            'telegram_payment_charge_id' => 'tg-charge-1',
            'provider_payment_charge_id' => 'provider-charge-1',
        ],
    ],
    $runtime,
    $config,
    'premium-payment'
);
assert_true($paymentResponse['ok'] === true, 'Successful payment should produce a Telegram sendMessage payload.');
assert_true($paymentResponse['payload']['method'] === 'sendMessage', 'Successful payment should acknowledge the user.');
assert_true($writerCall['plan'] === 'premium_monthly', 'Successful payment should grant the selected premium plan.');
assert_true($writerCall['telegram_user_id'] === 987654321, 'Successful payment should grant the paying Telegram user.');

// Security: a dedicated premium signing secret is required; the bot token must
// not be used as a fallback (it is shared with the webhook trust boundary).
$settingsWithoutSecret = $settings;
$settingsWithoutSecret['signing_secret'] = '';
assert_true(
    tonbankcard_api_premium_invoice_payload('premium_monthly', 42, 987654321, $settingsWithoutSecret) === null,
    'Invoice payloads must not be signed without a dedicated premium signing secret.'
);
assert_true(
    tonbankcard_api_premium_parse_invoice_payload($payload, $settingsWithoutSecret) === null,
    'Invoice payloads must not validate without the dedicated signing secret (no bot-token fallback).'
);

// Security: a forged successful_payment with no real Stars charge (missing
// telegram_payment_charge_id) must never grant premium.
$writerCall = null;
$forgedPayment = tonbankcard_api_premium_successful_payment_response(
    [
        'from' => ['id' => 987654321, 'language_code' => 'en'],
        'chat' => ['id' => 987654321],
        'successful_payment' => [
            'currency' => 'XTR',
            'total_amount' => 199,
            'invoice_payload' => $payload,
        ],
    ],
    $runtime,
    $config,
    'premium-forged-payment'
);
assert_true($forgedPayment['ok'] === false, 'Forged payment without a Telegram charge id must be rejected.');
assert_true($writerCall === null, 'Forged payment without a Telegram charge id must not grant an entitlement.');

// Security: replay protection. A given telegram_payment_charge_id is redeemable
// at most once; replaying the same charge must not grant or extend again.
$writerCall = null;
$replayResponse = tonbankcard_api_premium_successful_payment_response(
    [
        'from' => ['id' => 987654321, 'language_code' => 'en'],
        'chat' => ['id' => 987654321],
        'successful_payment' => [
            'currency' => 'XTR',
            'total_amount' => 199,
            'invoice_payload' => $payload,
            'telegram_payment_charge_id' => 'tg-charge-1',
            'provider_payment_charge_id' => 'provider-charge-1',
        ],
    ],
    $runtime,
    $config,
    'premium-replay-payment'
);
assert_true($replayResponse['ok'] === true, 'Replayed payment should be acknowledged idempotently.');
assert_true(!empty($replayResponse['replayed']), 'Replayed payment should be flagged as already processed.');
assert_true($writerCall === null, 'Replayed telegram_payment_charge_id must not grant a second entitlement.');

// A genuinely new charge (distinct telegram_payment_charge_id) is still granted.
$writerCall = null;
$renewalResponse = tonbankcard_api_premium_successful_payment_response(
    [
        'from' => ['id' => 987654321, 'language_code' => 'en'],
        'chat' => ['id' => 987654321],
        'successful_payment' => [
            'currency' => 'XTR',
            'total_amount' => 199,
            'invoice_payload' => $payload,
            'telegram_payment_charge_id' => 'tg-charge-2',
            'provider_payment_charge_id' => 'provider-charge-2',
        ],
    ],
    $runtime,
    $config,
    'premium-renewal-payment'
);
assert_true($renewalResponse['ok'] === true, 'A distinct Stars charge should still be fulfilled.');
assert_true(empty($renewalResponse['replayed']), 'A distinct Stars charge should not be treated as a replay.');
assert_true($writerCall['telegram_user_id'] === 987654321, 'A distinct Stars charge should grant the entitlement.');

$free = tonbankcard_api_premium_free_state($settings, 'expired');
assert_true($free['entitled'] === false, 'Expired entitlement fallback should remove premium access.');
assert_true($free['limits']['alerts_per_user'] === 3, 'Expired entitlement fallback should restore free alert limits.');
assert_true(tonbankcard_api_premium_limit_allows($free, 'watchlist_entries', 20) === true, 'Free watchlist limit should allow the maximum free count.');
assert_true(tonbankcard_api_premium_limit_allows($free, 'watchlist_entries', 21) === false, 'Free watchlist limit should reject overflow.');
assert_true(tonbankcard_api_premium_limit_allows($free, 'advanced_ranges', '90d') === false, 'Free plan should reject premium screener ranges.');

$plansResponse = tonbankcard_api_handle(
    [
        'method' => 'GET',
        'path' => '/api/premium/plans',
        'headers' => ['x-request-id' => 'premium-plans'],
        'body' => '',
    ],
    [],
    $runtime,
    $config
);
$plansBody = json_decode($plansResponse['body'], true);
assert_true($plansResponse['status'] === 200, 'Premium plans endpoint should return 200.');
assert_true(($plansBody['data']['plans'][1]['currency'] ?? '') === 'XTR', 'Premium plans endpoint should expose XTR pricing.');

$checkoutResponse = tonbankcard_api_handle(
    [
        'method' => 'POST',
        'path' => '/api/premium/checkout',
        'headers' => ['content-type' => 'application/json'],
        'body' => json_encode(['plan_code' => 'premium_monthly']),
    ],
    [],
    $runtime,
    $config
);
$checkoutBody = json_decode($checkoutResponse['body'], true);
assert_true($checkoutResponse['status'] === 401 || $checkoutResponse['status'] === 503, 'Anonymous premium checkout must require trusted session storage.');
assert_true(in_array($checkoutBody['error']['code'] ?? '', ['premium_session_required', 'premium_storage_unavailable'], true), 'Checkout should fail with a premium session/storage error.');

$screenerResponse = tonbankcard_api_handle(
    [
        'method' => 'GET',
        'path' => '/api/screener/markets?advanced_range=90d',
        'headers' => ['x-request-id' => 'premium-screener-gate'],
        'body' => '',
    ],
    [],
    $runtime,
    $config
);
$screenerBody = json_decode($screenerResponse['body'], true);
assert_true($screenerResponse['status'] === 402, 'Premium-only screener range should be rejected before provider calls.');
assert_true(($screenerBody['error']['code'] ?? '') === 'premium_required', 'Screener premium gate should return premium_required.');
PHP

echo "Premium subscriptions check passed."
