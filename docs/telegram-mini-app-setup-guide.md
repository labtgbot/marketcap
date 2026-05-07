# Telegram Mini App Setup Guide

Issue: [#149](https://github.com/labtgbot/marketcap/issues/149)

This guide describes the complete production setup path for running TONBANKCARD
as a Telegram Mini App with trusted sessions, smart alerts, Telegram Stars
premium subscriptions, and subscription expiry notifications.

## 1. Prepare The Deployment

1. Deploy the repository to an HTTPS domain that Telegram can open inside a Mini
   App webview. The Telegram runtime must use the Mini App URL, while public
   share and canonical links can keep the public website URL.
2. Configure PHP 8.1+, MySQL or MariaDB, and any cache/provider services listed
   in `docs/hosting-installation.md`.
3. Copy `.env.example` to the hosting secret store or `.env` and set the
   Telegram profile:

```sh
TONBANKCARD_PROFILE=telegram
TONBANKCARD_PUBLIC_BASE_URL=https://marketcap.tonbankcard.com/
TONBANKCARD_TELEGRAM_BASE_URL=https://marketcap.tonbankcard.com/
TONBANKCARD_BOT_USERNAME=MarketCapBot
TONBANKCARD_BOT_TOKEN=123456:telegram-bot-token
TONBANKCARD_BOT_WEBHOOK_SECRET=long-random-webhook-secret
```

4. Enable the feature flags needed by this guide:

```sh
TONBANKCARD_FEATURE_ALERTS=true
TONBANKCARD_FEATURE_PREMIUM=true
TONBANKCARD_FEATURE_REFERRALS=true
```

5. Configure alert and premium settings:

```sh
TONBANKCARD_ALERT_WORKER_TOKEN=long-random-alert-worker-token
TONBANKCARD_ALERT_MAX_RULES_PER_USER=3
TONBANKCARD_ALERT_DEFAULT_FREQUENCY_CAP_SECONDS=3600
TONBANKCARD_ALERT_MAX_DELIVERIES_PER_DAY=24
TONBANKCARD_ALERT_EVALUATION_INTERVAL_SECONDS=300
TONBANKCARD_PREMIUM_PLAN_CODE=premium_monthly
TONBANKCARD_PREMIUM_MONTHLY_STARS=199
TONBANKCARD_PREMIUM_SUBSCRIPTION_PERIOD_SECONDS=2592000
TONBANKCARD_PREMIUM_SIGNING_SECRET=long-random-premium-signing-secret
```

`2592000` seconds is the Telegram Stars monthly subscription period. Telegram
Stars invoices use currency `XTR` and an empty provider token, so no external
payment provider key is required for digital premium features.

## 2. Initialize The Database

1. Create the production database and application user.
2. Set `MYSQL_DSN`, `MYSQL_USER`, and `MYSQL_PASSWORD`.
3. Preview and apply migrations:

```sh
php database/migrate.php dry-run
php database/migrate.php up
```

The migrations create trusted Telegram session storage, smart alert rules,
delivery attempts, premium entitlements, and premium payment events. The premium
payment state migration stores only server-held Telegram charge identifiers and
safe SHA-256 hashes for exposed references.

## 3. Configure BotFather

1. Create or open the bot in BotFather.
2. Use the BotFather Web Apps menu to `Configure Mini App`.
3. Set the Mini App URL to `TONBANKCARD_TELEGRAM_BASE_URL`.
4. Configure the Main Mini App profile, icon, short description, and localized
   preview media as described in `docs/v2-launch-readiness.md`.
5. Use `Configure Splash Screen` to match the manifest theme color and loading
   screen.
6. Keep the bot username synchronized with `TONBANKCARD_BOT_USERNAME` because
   alerts and bot commands build `https://t.me/<bot>?startapp=...` links.

After BotFather is configured, open the Mini App from Telegram and confirm that
`/api/telegram/session` validates `initData`. Browser-only local sessions can
render the UI, but alerts, premium checkout, and entitlement changes require a
trusted Telegram user.

## 4. Register The Bot Webhook

Register the webhook with Telegram and pass the same secret configured in
`TONBANKCARD_BOT_WEBHOOK_SECRET`:

```sh
curl -X POST "https://api.telegram.org/bot${TONBANKCARD_BOT_TOKEN}/setWebhook" \
  -d "url=${TONBANKCARD_TELEGRAM_BASE_URL}api/telegram/bot" \
  -d "secret_token=${TONBANKCARD_BOT_WEBHOOK_SECRET}" \
  -d "allowed_updates[]=message" \
  -d "allowed_updates[]=pre_checkout_query" \
  -d "allowed_updates[]=inline_query"
```

The application endpoint is `/api/telegram/bot`. It handles bot commands,
inline mode, `pre_checkout_query`, `successful_payment`, and
`message.refunded_payment`. Telegram sends the webhook secret in
`X-Telegram-Bot-Api-Secret-Token`; production should reject requests that do not
match it.

## 5. Enable Alerts

1. Confirm `TONBANKCARD_FEATURE_ALERTS=true`.
2. Confirm `TONBANKCARD_BOT_TOKEN` and `TONBANKCARD_BOT_USERNAME` are set.
3. Schedule the evaluator to call `/api/alerts/evaluate` every few minutes:

```sh
curl -X POST "${TONBANKCARD_TELEGRAM_BASE_URL}api/alerts/evaluate" \
  -H "X-TONBANKCARD-Alert-Worker-Token: ${TONBANKCARD_ALERT_WORKER_TOKEN}"
```

The worker loads due rules, checks market conditions, applies quiet hours,
frequency caps, and daily caps, records delivery attempts, and sends Telegram
messages with `startapp` links back to `/app/alert/{id}` or the related market
context. Users manage alert rules from `/alerts` or `/app/alerts`.

Use premium limits to control alert capacity. Free users default to three alert
rules, while active premium users can create the higher premium limit documented
in `docs/v2-premium-subscriptions.md`.

## 6. Enable Stars Premium Checkout

The Mini App starts checkout with `POST /api/premium/checkout`. The backend must
own the full payment flow:

1. The trusted Telegram session identifies the internal user and Telegram user.
2. The checkout endpoint signs the invoice payload and calls Telegram
   `createInvoiceLink`.
3. The Mini App opens the returned Stars invoice link.
4. Telegram sends `pre_checkout_query` to `/api/telegram/bot`.
5. The bot validates the signed payload, Telegram user id, plan code, `XTR`
   currency, amount, and subscription period before answering
   `answerPreCheckoutQuery`.
6. Telegram sends `successful_payment` after payment succeeds.
7. The webhook grants or renews the premium entitlement server-side and records
   the payment event.

Do not grant premium from browser state. The frontend can show plan details and
request checkout, but only the webhook should activate or renew an entitlement.

## 7. Handle Renewal, Cancellation, Refunds, And Expiry

Premium entitlements are server-owned rows with `status`, `starts_at`,
`expires_at`, latest Telegram charge references, cancellation timestamps, and
refund timestamps.

1. Renewal: a new `successful_payment` for the same trusted Telegram user
   extends the entitlement by `TONBANKCARD_PREMIUM_SUBSCRIPTION_PERIOD_SECONDS`.
2. Cancellation: `POST /api/premium/entitlement/cancel` records
   `cancel_at_period_end` and calls Telegram `editUserStarSubscription` with the
   latest server-held subscription charge id.
3. Refund: `message.refunded_payment` records refund state and changes the
   entitlement to `revoked`.
4. Expiry: premium helpers mark stale rows as `expired` before returning limits,
   so expired users automatically fall back to Free limits.

For subscription expiry notification, schedule a daily operational task that
selects active or cancel-at-period-end entitlements ending soon and sends a bot
message through `TONBANKCARD_BOT_TOKEN`. The message should include a `startapp`
deep link to `/premium`, explain the exact expiry date, and avoid sending more
than one notice for the same entitlement window. After `expires_at` passes, the
same task can send a final subscription expiry notification after the row becomes
`expired`, again linking back to `/premium` for renewal.

## 8. Verify The Setup

Run the focused documentation and behavior checks before launch:

```sh
npm run test:mini-app-setup-guide
npm run test:pwa-telegram
npm run test:telegram-bot
npm run test:alerts
npm run test:premium
npm run test:launch-readiness
```

Manual production verification:

1. Open the Mini App from Telegram and confirm `/api/telegram/session` returns a
   trusted user.
2. Create a test alert, call `/api/alerts/evaluate`, and confirm a Telegram
   alert message arrives with a working `startapp` link.
3. Start premium checkout, verify the Stars invoice shows `XTR` and the
   configured price, then complete a test payment from Telegram's allowed test
   environment.
4. Confirm `/api/premium/entitlement` returns Premium after
   `successful_payment`.
5. Cancel renewal and confirm the entitlement remains active until `expires_at`.
6. Test a refund update and confirm the entitlement becomes revoked.
7. Move a staging entitlement past `expires_at` and confirm the user falls back
   to Free limits and receives the subscription expiry notification.

## 9. Troubleshooting

- If the Mini App opens but trusted features fail, inspect
  `/api/telegram/session` first. Most failures are caused by a missing
  `TONBANKCARD_BOT_TOKEN`, a mismatched Mini App URL, or stale Telegram
  `initData`.
- If webhook requests fail, compare BotFather webhook configuration,
  `setWebhook`, and `TONBANKCARD_BOT_WEBHOOK_SECRET`.
- If alerts are created but no messages arrive, verify the scheduled worker,
  `X-TONBANKCARD-Alert-Worker-Token`, bot token, quiet hours, and delivery caps.
- If Stars checkout opens but payment is rejected, inspect `pre_checkout_query`
  validation for a mismatched amount, Telegram user id, currency, plan code, or
  expired invoice signature.
- If premium appears in the browser but limits are not lifted, verify that the
  entitlement was granted by `successful_payment` and that backend limit helpers
  are reading an active, non-expired row.
