# TONBANKCARD V2 Shareable Market Cards and Referral Deep Links

Issue: [#33](https://github.com/labtgbot/marketcap/issues/33)

## Scope

Stage 4 turns useful market views into Telegram-native sharing and referral
loops. Each share surface builds the same compact card contract and the same
Telegram `startapp` payload fields: `route`, `campaign`, `inviter`, and
`context`.

Core share card contexts:

- `coin_price`: coin price cards on currency detail pages.
- `market_pulse`: market pulse cards with global stats, TON movers, and top
  gainers or losers.
- `watchlist_snapshot`: watchlist snapshot cards with saved assets and storage
  mode.
- `ton_movers`: TON ecosystem movers cards with curated asset and verification
  counts.
- `alert_win`: alert wins cards for alert lists, alert rules, and test delivery
  links.
- `ai_insight`: AI insight summaries from generated insight cards.

## Payload Contract

Client and server payload builders use a Telegram-safe envelope:

```json
{
  "route": "/currency/toncoin",
  "campaign": "coin-price",
  "inviter": "telegram:12345",
  "context": "coin_price"
}
```

The encoded value is prefixed with `s_` and can be attached to Telegram
Mini App links as `startapp`. The backend rejects empty payloads, unsupported
prefixes, invalid base64 or JSON, external routes, control characters, and
invalid inviter identifiers. Resolved links return the validated route and a
stable payload hash from `/api/share/resolve`.

## Share Behavior

The client chooses the best available share path:

- Telegram WebApp `shareUrl` when available.
- Native Web Share when available.
- Clipboard copy as a normal web share fallback.

All share cards include freshness labels and the `Not financial advice`
disclaimer. Shared web links include the same `startapp` value so browser opens
can resolve the target route before or after Telegram context is available.

## Referral Attribution

Referral attribution runs only after server-validated Telegram session creation.
The recorder parses the trusted session `start_param`, rejects self referrals,
and stores first-touch attribution once per referred user and campaign. Database
storage uses `uniq_referral_attributions_referred_campaign`; local development
storage uses the same per-user and per-campaign dedupe key in JSON.

## Verification

Run the focused regression check:

```sh
npm run test:share-referrals
```

The check is implemented in `tests/share-referrals-check.sh` and verifies the
docs, API route, payload round trip, route safety, migration, frontend share
surfaces, and local referral attribution dedupe.
