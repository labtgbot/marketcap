# TONBANKCARD V2 Market Data Gateway

Date: 2026-04-30

Issue: [#14](https://github.com/labtgbot/marketcap/issues/14)

## Objective

Move browser market data calls behind the PHP API so the public website and
Telegram Mini App no longer call CoinGecko data endpoints directly. The
browser uses `/api/market/*`; the backend selects the CoinGecko root URL,
inserts server-side credentials when configured, and normalizes provider
failures into the shared API error envelope.

## Routes

The gateway intentionally allows only the market data paths needed by the
current app and near-term V2 work:

| Method | Route | CoinGecko upstream path |
| --- | --- | --- |
| `GET` | `/api/market` | Gateway metadata. |
| `GET` | `/api/market/global` | `/global` |
| `GET` | `/api/market/coins/list` | `/coins/list` |
| `GET` | `/api/market/coins/markets` | `/coins/markets` |
| `GET` | `/api/market/coins/{id}` | `/coins/{id}` |
| `GET` | `/api/market/coins/{id}/market_chart` | `/coins/{id}/market_chart` |
| `GET` | `/api/market/coins/{id}/market_chart/range` | `/coins/{id}/market_chart/range` |
| `GET` | `/api/market/coins/{id}/tickers` | `/coins/{id}/tickers` |
| `GET` | `/api/market/exchanges` | `/exchanges` |
| `GET` | `/api/market/exchanges/list` | `/exchanges/list` |
| `GET` | `/api/market/exchanges/{id}` | `/exchanges/{id}` |
| `GET` | `/api/market/exchanges/{id}/tickers` | `/exchanges/{id}/tickers` |
| `GET` | `/api/market/exchanges/{id}/volume_chart` | `/exchanges/{id}/volume_chart` |
| `GET` | `/api/market/search` | `/search` |
| `GET` | `/api/market/search/trending` | `/search/trending` |
| `GET` | `/api/market/finance_platforms` | `/finance_platforms` |
| `GET` | `/api/market/finance_products` | `/finance_products` |
| `GET` | `/api/market/derivatives` | `/derivatives` |

Unsupported paths return `404` before any upstream request is made. Coin and
exchange ids are limited to safe path characters, and client-supplied provider
credential query parameters such as `x_cg_demo_api_key` and `x_cg_pro_api_key`
are stripped.

When CoinGecko returns `404` for `/api/market/coins/{id}` and the same `id`
exists in the TON curation catalog, the gateway uses a TON curation fallback
instead of failing the coin detail page. The fallback returns a CoinGecko-shaped
coin detail payload with the curated name, symbol, description, TON contract
address, TON ecosystem category metadata, and empty market-data buckets. Chart
and ticker subroutes for the same curated id return empty arrays so the existing
coin page can render without provider market history. Response metadata marks
this path with `provider.fallback = "ton_curation"` and
`freshness.cache_status = "ton_curation_fallback"`.

## Authentication

The default configuration works without a CoinGecko API key. With
`COINGECKO_API_KEY` empty, the gateway uses the public CoinGecko `/api/v3`
root, sends no authentication header, and preserves the original V1 no-key data
loading behavior. Add a key only when the public quota is not enough.

`COINGECKO_API_PLAN` controls upstream selection:

| Plan | Root URL | Header |
| --- | --- | --- |
| `demo` | `https://api.coingecko.com/api/v3/` | `x-cg-demo-api-key` when `COINGECKO_API_KEY` is set. |
| `pro` | `https://pro-api.coingecko.com/api/v3/` | `x-cg-pro-api-key`; `COINGECKO_API_KEY` is required. |

The browser receives only `GeckoClient.cg.gatewayBaseUrl`. It never receives
`COINGECKO_API_KEY`, the selected upstream root, or the provider auth header.

## Response Metadata

Successful responses preserve the shared JSON envelope and place raw provider
data in `data`. Market responses add safe metadata for attribution and data
freshness:

```json
{
  "ok": true,
  "data": [],
  "meta": {
    "request_id": "client-or-generated-id",
    "provider": {
      "name": "coingecko",
      "plan": "demo",
      "attribution": {
        "name": "CoinGecko",
        "url": "https://www.coingecko.com/"
      },
      "credentialed": false
    },
    "freshness": {
      "fetched_at": "2026-04-30T20:00:00+00:00",
      "last_updated_at": "2026-04-30T19:59:00+00:00",
      "cache_age_seconds": 0,
      "cache_status": "pass",
      "data_age_seconds": 60
    }
  }
}
```

`cache_status` is `miss` for a fresh provider response that is stored, `hit`
for a fresh Redis response, `stale` for an upstream fallback response,
`bypass` when cache is disabled or unavailable, and `pass` for non-cacheable
metadata responses. `last_updated_at` is derived from common CoinGecko fields
such as `last_updated`, `market_data.last_updated`, or `updated_at` when
present. See `docs/v2-cache-rate-limit-coalescing.md` for TTLs, request
coalescing, and stale fallback rules.

## Error Normalization

Provider errors use the same API error envelope as the routing layer:

| Upstream condition | HTTP status | Error code |
| --- | --- | --- |
| CoinGecko `429` | `429` | `provider_rate_limited` |
| Transport timeout | `504` | `provider_timeout` |
| Coin or exchange not found | `404` | `invalid_symbol` |
| Provider auth failure | `502` | `provider_auth_failed` |
| Invalid provider JSON | `502` | `provider_invalid_json` |
| Other provider/network failure | `502` | `provider_unavailable` |

`Retry-After` is forwarded for normalized rate-limit responses. Error details
include safe provider name, plan, gateway path, upstream path, and upstream
status, but never include API keys or provider response bodies.

## Browser Integration

`dev/js/src/coingecko.js` now points the existing `CoinGecko.*` wrapper at
`GeckoClient.cg.gatewayBaseUrl` and unwraps the API success envelope before
running the existing consistency transforms. This preserves the current Vue
route code while moving these data calls behind `/api/market/*`:

- global market stats
- coin market lists
- coin details
- coin charts and chart ranges
- coin tickers
- exchange lists and details
- exchange tickers and volume charts
- search and trending search
- finance platforms and products
- derivatives

The market gateway still exposes `/api/market/search` and
`/api/market/search/trending` for provider compatibility. The visible search bar
uses the first-party `/api/search` smart search service, which replaces the
previous browser request to `https://localstorage.one/crypto/data/search.json`.

## Tests

Regression coverage lives in `tests/market-gateway-check.sh` and is available
through:

```sh
npm run test:market-gateway
```

The check verifies route documentation, browser source rewiring, default no-key
behavior, Demo and Pro header behavior, secret redaction, rate-limit
normalization, timeout normalization, invalid-symbol normalization, TON curation
fallback behavior for manually added TON assets, and freshness metadata. The
browser smoke test also stubs `/api/market/*` and fails if the app makes direct
data requests to `api.coingecko.com`, `pro-api.coingecko.com`, or
`localstorage.one`.

Cache and rate-limit regression coverage lives in
`tests/cache-rate-limit-check.sh` and runs through:

```sh
npm run test:cache-rate-limit
```

## References

- CoinGecko Demo authentication: https://docs.coingecko.com/v3.0.1/reference/authentication
- CoinGecko Pro authentication: https://docs.coingecko.com/reference
- CoinGecko API key setup: https://docs.coingecko.com/docs/setting-up-your-api-key
