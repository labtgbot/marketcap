#!/usr/bin/env sh
set -eu

failures=0
doc=docs/telegram-mini-app-setup-guide.md

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
        fail "$file does not document $description"
    fi
}

assert_file "$doc"

assert_contains "$doc" '^# Telegram Mini App Setup Guide$' 'the setup guide title'
assert_contains "$doc" 'Issue: \[#149\]' 'the issue reference'
assert_contains "$doc" 'BotFather' 'BotFather setup'
assert_contains "$doc" 'Configure Mini App' 'Mini App configuration flow'
assert_contains "$doc" 'TONBANKCARD_PROFILE=telegram' 'telegram runtime profile'
assert_contains "$doc" 'TONBANKCARD_TELEGRAM_BASE_URL' 'Telegram Mini App base URL'
assert_contains "$doc" 'TONBANKCARD_BOT_TOKEN' 'bot token configuration'
assert_contains "$doc" 'TONBANKCARD_BOT_WEBHOOK_SECRET' 'webhook secret configuration'
assert_contains "$doc" 'TONBANKCARD_FEATURE_ALERTS=true' 'alerts feature flag'
assert_contains "$doc" 'TONBANKCARD_FEATURE_PREMIUM=true' 'premium feature flag'
assert_contains "$doc" 'TONBANKCARD_PREMIUM_MONTHLY_STARS' 'Stars pricing variable'
assert_contains "$doc" 'TONBANKCARD_PREMIUM_SUBSCRIPTION_PERIOD_SECONDS=2592000' 'monthly Stars subscription period'
assert_contains "$doc" 'php database/migrate\.php up' 'database migration step'
assert_contains "$doc" '/api/telegram/session' 'Telegram session validation'
assert_contains "$doc" '/api/telegram/bot' 'Telegram bot webhook'
assert_contains "$doc" 'setWebhook' 'Telegram webhook registration'
assert_contains "$doc" '/api/alerts/evaluate' 'alerts worker endpoint'
assert_contains "$doc" 'X-TONBANKCARD-Alert-Worker-Token' 'alerts worker token'
assert_contains "$doc" 'POST /api/premium/checkout' 'premium checkout endpoint'
assert_contains "$doc" 'createInvoiceLink' 'Telegram Stars invoice creation'
assert_contains "$doc" 'pre_checkout_query' 'Telegram pre-checkout validation'
assert_contains "$doc" 'successful_payment' 'successful payment handling'
assert_contains "$doc" 'message\.refunded_payment' 'refund handling'
assert_contains "$doc" 'editUserStarSubscription' 'Stars subscription cancellation'
assert_contains "$doc" 'expired' 'expired entitlement handling'
assert_contains "$doc" 'subscription expiry notification' 'subscription expiry notification flow'
assert_contains "$doc" 'startapp' 'Telegram startapp deep links'
assert_contains "$doc" 'npm run test:pwa-telegram' 'PWA and Telegram verification'
assert_contains "$doc" 'npm run test:telegram-bot' 'bot verification'
assert_contains "$doc" 'npm run test:alerts' 'alerts verification'
assert_contains "$doc" 'npm run test:premium' 'premium verification'

assert_contains README.md 'docs/telegram-mini-app-setup-guide\.md' 'the Telegram Mini App setup guide link'
assert_contains README.md 'npm run test:mini-app-setup-guide' 'the Mini App setup guide npm check'
assert_contains package.json '"test:mini-app-setup-guide"' 'the Mini App setup guide npm script'
assert_contains package.json 'tests/mini-app-setup-guide-check\.sh' 'the Mini App setup guide shell check'
assert_contains package.json 'test:mini-app-setup-guide' 'the aggregate Mini App setup guide check'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Telegram Mini App setup guide check passed.'
