# TONBANKCARD V2 Upstash Cache, Rate Limits, and Coalescing

Date: 2026-04-30

Issue: [#15](https://github.com/labtgbot/marketcap/issues/15)

## Objective

Use server-side Upstash Redis REST for shared cache entries, request
coalescing, API rate limits, and lightweight operational counters. Browser and
Telegram clients continue to call the PHP API only; Upstash URL and token
values stay in runtime configuration and are never included in JSON responses.

## Configuration

Runtime reads `UPSTASH_REDIS_REST_URL` and `UPSTASH_REDIS_REST_TOKEN` from the
server environment. `config/api.php` enables Redis when both are present.

Feature toggles:

| Variable | Default | Purpose |
| --- | --- | --- |
| `TONBANKCARD_CACHE_ENABLED` | Enabled when Upstash is configured. | Enables shared market data cache reads, writes, stale fallback, and duplicate request coalescing. |
| `TONBANKCARD_RATE_LIMIT_ENABLED` | Enabled outside local profiles when Upstash is configured. | Enables Redis-backed API request limits. |

Redis timeout defaults to 2 seconds, and cache/rate-limit failures are
fail-open for page rendering. When Redis is unavailable, API routes continue to
serve uncached provider responses unless the upstream provider itself fails.

## Cache TTLs

Cache entries include a fresh window and a stale fallback window. The stale
window defaults to 3600 seconds so recent data can keep core pages rendering
during short provider outages.

| Cache type | Default TTL | Current routes |
| --- | ---: | --- |
| `live_prices` | 60 seconds | Markets, tickers, exchanges, derivatives, finance products. |
| `global_stats` | 300 seconds | Global market stats. |
| `coin_metadata` | 3600 seconds | Coin detail, exchange detail, finance platforms. |
| `charts` | 900 seconds | Coin market charts, chart ranges, exchange volume charts. |
| `search_index` | 3600 seconds | Coin lists, exchange lists, search, trending search. |
| `sentiment_inputs` | 300 seconds | Deterministic AI sentiment input snapshots. |
| `ai_summaries` | 21600 seconds | Reserved for AI summary cache entries. |
| `ton_metadata` | 86400 seconds | Reserved for curated TON metadata. |

Market responses expose `meta.freshness.cache_status` as `miss`, `hit`,
`stale`, `bypass`, or `pass`, along with `cache_age_seconds`,
`cache_ttl_seconds`, and `stale_age_seconds` when stale data is served.

## Upstream Fallback

Fresh cache hits return immediately. Stale entries are held as fallback
candidates while the gateway tries the upstream provider. If CoinGecko times
out, returns invalid JSON, or returns a non-success status, the gateway serves
the stale entry with:

- `meta.freshness.cache_status` set to `stale`
- `meta.freshness.upstream_fallback` set to `true`
- `meta.freshness.stale_age_seconds` set to the stale duration

This keeps core market pages rendering while still making stale state visible
to clients and operators.

## Request Coalescing

When a cacheable market request misses, the gateway attempts a short Redis lock
for that route, provider, and sanitized query. The lock holder fetches and
stores fresh provider data. Duplicate requests wait briefly for the cache entry
to appear and return the cached response with
`meta.freshness.coalesced=true` when another request filled it first.

The default lock TTL is 15 seconds, with a 250 ms wait budget and 25 ms poll
interval. These values are intentionally short so duplicate suppression does
not block page rendering if the lock holder fails.

## Rate Limits

The limiter classifies requests without storing raw identifiers in Redis keys:

| Policy | Identity source | Default |
| --- | --- | ---: |
| `anonymous_web` | Hashed IP and user agent. | 120 requests per 60 seconds. |
| `telegram_session` | Hashed `tonbankcard_session`, `X-TONBANKCARD-Session`, or `X-Telegram-Init-Data`. | 240 requests per 60 seconds. |
| `admin_action` | Admin path or admin/auth header. | 30 requests per 60 seconds. |

Blocked requests return `429 rate_limited` with a clear message, `Retry-After`,
`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`, and
`X-RateLimit-Policy`. Redis errors are fail-open and increment an unavailable
counter when possible.

## Metrics

`GET /api/metrics` returns operational counters from Redis without exposing
secrets:

- `cache.hits`, `cache.misses`, `cache.stale`, `cache.upstream_fallbacks`,
  `cache.bypassed`, `cache.cache_hit_rate`, and
  `cache.last_stale_age_seconds`
- `rate_limit.allowed`, `rate_limit.blocked`, and `rate_limit.unavailable`
- Redis configured/enabled state

The Redis metric keys are daily counters with a two-day expiry. They are meant
for lightweight health checks and early dashboards, not long-term analytics
storage.

## Tests

Regression coverage lives in `tests/cache-rate-limit-check.sh` and is available
through:

```sh
npm run test:cache-rate-limit
```

The check verifies configuration, documentation, cache miss/hit behavior,
stale upstream fallback, secret redaction, rate-limit identity classes,
clear 429 responses, metrics counters, and duplicate market request coalescing.
