# TONBANKCARD V2 Watchlist UX and Persistence

Issue: [#23](https://github.com/labtgbot/marketcap/issues/23)

The V2 watchlist gives public web and Telegram Mini App users the same visible
add/remove controls across market rows, search results, coin detail pages, and
the dedicated Watchlist route.

## Persistence Tiers

Anonymous browser sessions persist the canonical snapshot in `localStorage`
under `TONBANKCARD:watchlist:v1`. Older `TONBANKCARD:watchlist` and
`GeckoClient:watchlist` shapes are read during bootstrap so previous local
lists can migrate forward without a server account.

Telegram Mini App sessions prefer Telegram CloudStorage when it is available.
The same snapshot shape is stored there so a Telegram user can move across
devices without losing entries. If CloudStorage is blocked or unavailable, the
client falls back to localStorage.

When a Telegram session is validated by `/api/telegram/session`, server sync is
enabled through `/api/watchlist`. Trusted sessions are checked against MySQL
`user_sessions`, then entries are stored in the existing `watchlists` and
`watchlist_entries` tables. Removal timestamps are stored in
`watchlist_tombstones` from `database/migrations/0003_watchlist_tombstones.up.sql`.
The `uniq_watchlist_entries_watchlist_coin` key prevents duplicate rows during
repeated or concurrent sync attempts.

If browser storage is unavailable, the watchlist uses an in-memory fallback for
the current page session. The UI still supports add/remove and the route still
renders saved in-memory entries, but the data is not durable after page close.

## Conflict Handling

Client snapshots include `entries`, `updated_at`, and remove tombstones so
conflict handling is deterministic. During
CloudStorage and MySQL sync, entries are merged by coin id and timestamp while
newer removals suppress older adds. Server sync applies active entries and
explicit tombstones to the trusted user's default list, keeping deletes
authoritative until a newer add arrives and avoiding duplicate inserts.

## UX States

The Watchlist route has a first-use empty state, sort controls, freshness chips,
and a stale data alert when market data comes from an expired or fallback cache.
If live market data is temporarily unavailable, saved entries remain visible
with their stored name, symbol, and image metadata.

## Regression Coverage

`tests/watchlist-check.sh` verifies the documentation, source registration,
client storage fallbacks, API route contract, and server-side entry
normalization. `tests/browser-smoke.js` covers add, remove, reload persistence,
the Watchlist route, and unavailable-storage fallback in a real Chromium
session.
