# TONBANKCARD V2 Premium Subscriptions

Issue: [#37](https://github.com/labtgbot/marketcap/issues/37)

Premium subscriptions monetize digital-only TONBANKCARD features through
Telegram Stars. The backend owns entitlement state and feature limits so the
frontend can only display or request access, not grant it.

## Plans and Limits

| Feature | Free | Premium |
| --- | ---: | ---: |
| Smart alert rules | 3 | 100 |
| Watchlist assets | 20 | 250 |
| Advanced screener ranges | 24h, 7d | 24h, 7d, 30d, 90d, 1y |
| AI watchlist digests | 1 per day | 24 per day |
| Market refresh | 300 seconds | 60 seconds priority refresh |

The default paid plan is `premium_monthly` at `199` Telegram Stars for a
30-day recurring subscription. Pricing is configured with:

```sh
TONBANKCARD_FEATURE_PREMIUM=true
TONBANKCARD_PREMIUM_PLAN_CODE=premium_monthly
TONBANKCARD_PREMIUM_MONTHLY_STARS=199
TONBANKCARD_PREMIUM_SUBSCRIPTION_PERIOD_SECONDS=2592000
TONBANKCARD_BOT_TOKEN=123456:telegram-bot-token
```

## Telegram Stars Flow

Telegram Stars digital goods use `XTR`, an empty `provider_token`, and a
monthly subscription `subscription_period` of `2592000` seconds. The checkout
endpoint creates a signed invoice payload, calls `createInvoiceLink`, and
returns the invoice link to the Mini App.

1. `POST /api/premium/checkout` requires a trusted Telegram session and creates
   a Stars invoice link.
2. Telegram sends `pre_checkout_query` to `/api/telegram/bot`; the bot validates
   the signed payload, user id, `XTR` currency, and amount before answering.
3. Telegram sends `message.successful_payment`; the bot grants or renews the
   entitlement server-side.
4. Telegram sends `message.refunded_payment`; the bot records refund state and
   revokes the matching entitlement.
5. Users can call `POST /api/premium/entitlement/cancel` to mark renewal
   cancellation at period end and call `editUserStarSubscription` with the
   server-held Telegram subscription charge id.

The latest Telegram subscription charge id is stored server-side so renewal
cancellation can call Telegram. Public APIs and payment event rows only expose
SHA-256 hashes for customer, subscription, and payment charge references.

## API Surface

| Route | Purpose |
| --- | --- |
| `GET /api/premium` | Service metadata, public settings, plans, and current entitlement. |
| `GET /api/premium/plans` | Public plan, price, and benefit comparison before payment. |
| `GET /api/premium/entitlement` | Current trusted-user entitlement, or Free when anonymous/expired. |
| `POST /api/premium/checkout` | Creates a Telegram Stars invoice link for the selected plan. |
| `POST /api/premium/entitlement/cancel` | Records cancellation intent for the active entitlement. |

## Backend Enforcement

Premium enforcement lives in shared helpers from `api/premium.php`:

- `tonbankcard_api_premium_limits_for_user()` loads active entitlement state and
  expires stale rows before returning limits.
- `tonbankcard_api_premium_limit_allows()` and
  `tonbankcard_api_premium_limit_error_details()` provide consistent limit
  checks and upgrade metadata.
- `api/watchlist.php` rejects snapshots over the current watchlist limit.
- `api/alerts.php` applies the current alert limit before inserts.
- `api/screener.php` rejects premium-only `advanced_range` requests with
  `402 premium_required`.

Expired entitlements are updated to `expired` and return the Free limit state
gracefully. Refunds set `status = revoked`, `refunded_at`, and `revoked_at`.

## Database

Migration `0010_premium_payment_state` extends `premium_entitlements` with the
server-held latest Stars charge id, hashed charge references, cancellation
timestamps, and refund timestamps. It also adds `premium_payment_events` for
coarse invoice, checkout, renewal, cancellation, and refund audit events.

## Verification

Run the focused premium check:

```sh
npm run test:premium
```

The check covers public pricing, signed Stars invoice payloads, pre-checkout
validation, successful payment entitlement fulfillment, expired entitlement
fallback, and backend enforcement hooks in `tests/premium-check.sh`.
