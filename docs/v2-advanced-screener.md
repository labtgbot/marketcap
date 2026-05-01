# TONBANKCARD V2 Advanced Screener

Issue: [#31](https://github.com/labtgbot/marketcap/issues/31)

The advanced screener turns the existing TON curation surface into a backend-owned market filter workflow. Browser clients call `/api/screener/markets`; the server loads cached CoinGecko gateway datasets, enriches rows with TON curation context, deterministic sentiment, watchlist snapshot state, and optional exchange availability, then returns only matching opportunities.

## Backend API

- `GET /api/screener` lists the screener API surface.
- `GET /api/screener/markets` accepts market cap, volume, rank, 24h/7d/30d movement, category, exchange, TON tag, sentiment score, watchlist status, sort, direction, page, and per-page filters.
- `GET /api/screener/presets` lists saved presets for trusted Telegram sessions.
- `POST /api/screener/presets` creates or replaces a preset by name for the current trusted user.
- `PUT /api/screener/presets/{id}` updates an owned preset.
- `DELETE /api/screener/presets/{id}` soft-deletes an owned preset.

The market endpoint always requests `price_change_percentage=24h,7d,30d` through the existing `/api/market/coins/markets` gateway. It does not make direct provider calls from the browser. Exchange filtering is bounded and uses cached `/api/market/coins/{id}/tickers` responses.

## Filters

The market endpoint normalizes and echoes accepted filters in the JSON envelope:

- Market cap: `market_cap_min`, `market_cap_max`
- Volume: `volume_min`, `volume_max`
- Movement: `change_24h_min/max`, `change_7d_min/max`, `change_30d_min/max`
- Rank: `rank_min`, `rank_max`
- Category: `category`
- Exchange availability: `exchange`
- TON curation tag: `ton_tag`
- Sentiment score: `sentiment_min`, `sentiment_max`
- Watchlist status: `watchlist=all|watched|unwatched` plus `watchlist_ids`
- Sorting: `sort` and `direction=asc|desc`

Empty responses include the active filter summary so users can see why no assets matched.

## Saved Presets

Saved presets persist only for trusted Telegram sessions. The frontend attempts `/api/telegram/session` with Telegram `initData`; when the backend confirms `telegram_validated`, preset controls load and save through `/api/screener/presets` with credentials. Anonymous web users can still use filters and table sorting, but the UI marks preset sync as unavailable instead of writing private preset data to the server.

The `0006_screener_presets` migration stores presets by internal `user_id`, with a per-user unique name constraint and soft deletion.

## Frontend UX

The `/screener` route renders the filter form, sortable result table, summary counts, watchlist controls, and preset actions as the first screen. Desktop uses a dense responsive filter grid. Mobile uses a compact `screener-filter-drawer` so filters remain usable without horizontal overflow, while the result table keeps stable columns and scroll-safe content.

## CSV Export

CSV export is disabled in the response and UI until product and legal approval explicitly allow it. The API exposes `csv_export_enabled: false` in the summary and metadata so the decision remains visible to clients without offering the action prematurely.

## Verification

`npm run test:screener` covers documentation, migrations, router wiring, frontend hooks, and backend filtering with a mocked market-data transport. The aggregate `npm test` includes this check.
