# TONBANKCARD V2 AI Sentiment Ingestion Pipeline

Date: 2026-05-01

Issue: [#27](https://github.com/labtgbot/marketcap/issues/27)

## Objective

The Stage 4 sentiment pipeline builds deterministic, testable inputs before
the AI provider generates prose. Clients call `POST /api/ai/sentiment-inputs`
with a subject, target coin ids, and an optional watchlist snapshot. The API
returns normalized scores, freshness/confidence metadata, and an `ai_context`
payload that can be passed directly to `POST /api/ai/insight`.

## MVP Sources

Allowed MVP source classes are intentionally narrow:

| Source | Refresh target | Signal |
| --- | ---: | --- |
| market movement | 60 seconds | CoinGecko `coins/markets` price movement over 1h, 24h, and 7d windows. |
| volume spike | 60 seconds | Volume-to-market-cap pressure derived from the same market snapshot. |
| global market | 300 seconds | Global crypto market cap movement from CoinGecko `global`. |
| trend ranking | 900 seconds | CoinGecko `search/trending` rank overlap for requested assets. |
| watchlist concentration | 300 seconds | Provided watchlist overlap with requested assets. |
| curated TON ecosystem | 86400 seconds | Server-owned TON ecosystem asset list such as Toncoin and TON USDT. |

The response includes `source_refresh_intervals` so clients and the AI prompt
can explain how fresh each source is expected to be. Sources are marked
`available`, `stale`, `missing`, or `unavailable`; stale or missing sources
lower confidence but still produce a partial success response when enough
context remains.

## Scoring

Each source produces a bounded signal score from -1 to 1 plus a confidence
value from 0 to 1. The aggregate score is a weighted blend of market movement,
volume spike, trend ranking, global market, watchlist concentration, and
curated TON ecosystem matches. The aggregate includes:

- `scores.overall.score`
- `scores.overall.sentiment` as `bullish`, `neutral`, `bearish`, or `mixed`
- `scores.overall.confidence`
- `scores.source_completeness`
- `scores.signal_count`

The scoring code does not call the AI provider, so tests can verify exact
source handling and score shape without consuming provider quota.

## AI Provider Context

`data.ai_context` is shaped for the existing AI provider layer:

```json
{
  "insight_type": "sentiment",
  "subject": "TON ecosystem pulse",
  "market_data_age_seconds": 120,
  "market_data_updated_at": "2026-05-01T00:00:00+00:00",
  "market_data": {
    "sentiment_pipeline_version": "v1",
    "source_refresh_intervals": {},
    "scores": {},
    "signals": [],
    "sources": {},
    "trace": {}
  }
}
```

The provider layer receives structured context only. No copyrighted article text
is ingested, stored, or exposed by this MVP pipeline. Future news-like signals
must be represented as source metadata, source categories, hashes, or short
non-copyrighted labels instead of article bodies.

## Cache And Traceability

Redis caches successful sentiment input snapshots in the `sentiment_inputs`
namespace for `TONBANKCARD_SENTIMENT_CACHE_TTL_SECONDS` seconds, defaulting to
300. Cache keys include the pipeline version, prompt version, requested assets,
watchlist hash, and refresh interval settings.

Every response includes trace metadata:

- `trace.pipeline_version`
- `trace.prompt_version`
- `trace.market_data_hash`
- `trace.cache_key_hash`
- `trace.provider`
- `trace.model_id`

The API never returns Redis URLs, Redis tokens, Groq keys, raw prompts, or
provider credentials.

## Tests

Regression coverage lives in `tests/ai-sentiment-check.sh` and is available
through:

```sh
npm run test:ai-sentiment
```

The check verifies route registration, documentation, deterministic score
generation, cache miss/hit behavior, sanitized coin ids, partial responses for
missing and stale sources, trace metadata, and compatibility with the
`/api/ai/insight` provider endpoint.
