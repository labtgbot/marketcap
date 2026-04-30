# TONBANKCARD V2 Smart Search API

Date: 2026-04-30
Issue: [#16](https://github.com/labtgbot/marketcap/issues/16)

## Objective

Replace the legacy third-party search data dependency with a first-party smart
search service for the public website and Telegram Mini App. Browser code calls
`/api/search`; provider calls, Redis credentials, ranking rules, and refresh
workflows stay server-side.

## Routes

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/api/search?q=BTC&limit=12&surface=web` | Returns ranked search results in the shared `/api/*` JSON envelope. |
| `POST` | `/api/search/refresh` | Rebuilds the Redis-backed search index after validating `X-TONBANKCARD-Search-Refresh-Token` outside local development. |

`api/search-refresh.php` provides the CLI entrypoint for scheduled refresh jobs:

```sh
php api/search-refresh.php
```

## Index Sources

The index combines curated TONBANKCARD entries with provider data fetched
through the existing market gateway:

- coins from `coins/list?include_platform=true`
- exchanges from `exchanges/list`
- categories from `coins/categories/list`
- trending coins and exchanges from `search/trending`
- curated popular actions such as trending, exchanges, and TON ecosystem
- curated TON assets such as Toncoin and USDT on TON

## Matching And Ranking

Search supports exact symbol, exact name, id, prefix, contains, contract address,
TON ecosystem tags, and fuzzy typo matching. Empty queries return popular action
results. High-cardinality queries are bounded by the requested `limit` and the
configured maximum.

TON-specific results receive a boost when the query includes TON terms or a TON
contract address. Contract address matching supports full, prefix, and contains
matches so copied jetton addresses can resolve directly to the relevant asset.

## Redis Cache

The search index cache uses Upstash Redis REST when
`UPSTASH_REDIS_REST_URL` and `UPSTASH_REDIS_REST_TOKEN` are configured. The
default cache key is `tonbankcard:v2:search:index`, and the default TTL is 900
seconds. Without Redis, the API still builds an index on demand and reports a
`pass` cache status.

The Redis token is sent only in the `Authorization` header. It must never appear
in command bodies, browser configuration, or API responses.

## Deep Links

Every result includes:

- `route` for Vue Router navigation on the public website
- `links.web` for canonical website deep links
- `links.telegram` for Telegram Mini App deep links

Coins route to `/currency/:id`, exchanges route to `/exchange/:id`, categories
route to filtered market lists, and action results route to the relevant public
or Mini App view.

## Analytics

Search UI events use the taxonomy from
`docs/v2-analytics-privacy-metrics.md`:

- `search_opened`
- `search_result_selected`

Result payloads include click metadata for `search_result_selected` with result
type, coin id, exchange id, rank, surface, and query length bucket. Raw search
queries are not analytics properties.

## Tests

Regression coverage lives in `tests/search-api-check.sh` and runs through:

```sh
npm run test:search-api
```

The check covers route registration, documentation, frontend endpoint wiring,
BTC and TON result ranking, contract address matching, fuzzy typo matching,
empty queries, high-cardinality limits, Redis cache reads/writes, and Redis
token redaction.
