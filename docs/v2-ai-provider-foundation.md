# TONBANKCARD V2 AI Provider Foundation

Date: 2026-04-30

Issue: [#17](https://github.com/labtgbot/marketcap/issues/17)

## Objective

Create a server-side AI provider abstraction for factual sentiment, summaries,
and insight generation while keeping Groq as the first configurable provider.
The browser and future admin surfaces use stable TONBANKCARD routes; provider
keys, raw prompts, and raw model responses stay server-side.

## Configuration

AI is disabled by default with `TONBANKCARD_FEATURE_AI=false`. When enabled,
the backend reads provider settings from environment variables and
`config/runtime.php` without source edits:

| Variable | Purpose |
| --- | --- |
| `TONBANKCARD_AI_PROVIDER` | Provider selector, default `groq`. |
| `TONBANKCARD_AI_PROMPT_VERSION` | Prompt/schema contract version, default `v1`. |
| `TONBANKCARD_AI_ENABLED_FEATURES` | Comma-separated subset of `summary,sentiment,insight`. |
| `TONBANKCARD_AI_FALLBACK_BEHAVIOR` | Default `unavailable`, returning `insight unavailable` when AI cannot answer safely. |
| `TONBANKCARD_AI_MAX_REQUEST_BODY_BYTES` | Maximum accepted `/api/ai/insight` request body size, default `16384`. |
| `TONBANKCARD_AI_MAX_PROMPT_BYTES` | Maximum normalized provider prompt message size, default `12288`. |
| `GROQ_API_KEY` | Secret API key, required only when AI is enabled. |
| `GROQ_MODEL_ID` | Groq model id, default `llama-3.3-70b-versatile`. |
| `GROQ_BASE_URL` | OpenAI-compatible Groq API root, default `https://api.groq.com/openai/v1/`. |
| `GROQ_TIMEOUT_SECONDS` | Provider timeout, default `10`. |
| `GROQ_RATE_LIMIT_WINDOW_SECONDS` | Dedicated provider rate-limit window, default `60`. |
| `GROQ_RATE_LIMIT_MAX_REQUESTS` | Dedicated provider rate-limit ceiling, default `20`. |

The AI route uses Groq's OpenAI-compatible Chat Completions endpoint
`/chat/completions`. `config/api.php` stores server-only provider fields and
test transports; client-facing responses expose only safe provider metadata such
as provider name, model id, prompt version, enabled features, fallback behavior,
rate-limit metadata, and whether credentials are configured.

## Routes

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/api/ai` | Returns AI service metadata, route list, and safe provider metadata. |
| `POST` | `/api/ai/insight` | Builds a server-side prompt from structured market context, executes it with the configured provider, validates the model JSON, and returns a safe insight or fallback. |

`/api/ai/insight` accepts structured input such as:

```json
{
  "insight_type": "market_summary",
  "subject": "TON market",
  "market_data_age_seconds": 90,
  "market_data": {
    "price_change_percentage_24h": 1.8,
    "total_volume": 12345678
  }
}
```

Supported `insight_type` values are `market_summary`, `coin_summary`,
`watchlist_digest`, `alert_explanation`, and `sentiment`. The route does not
accept raw user prompts. It sanitizes structured market context before prompt
construction and keeps the final prompt out of logs and responses.

## Abuse Bounds

`POST /api/ai/insight` rejects oversized raw request bodies with
`413 ai_request_too_large` before provider execution. The internal
`limits.max_request_body_bytes` and `limits.max_prompt_bytes` settings come from
the environment defaults above. After validation and normalization, the route
also measures the provider prompt messages and rejects oversized prompts with
`413 ai_prompt_too_large`.

The dedicated provider rate-limit bucket enforces the configured Groq
`rate_limit` before cache misses can call the paid provider. This bucket is
independent of the global API limiter, uses a valid session cookie or request IP
instead of spoofable session headers, and falls back to bounded in-process
enforcement when Redis is unavailable. Cache hits are returned without spending
provider bucket capacity.

## Structured JSON Validation

The provider is instructed to return structured JSON with:

- `title`
- `summary`
- `sentiment`
- `confidence`
- `drivers`
- `risks`
- `uncertainty`
- `market_data_age_seconds`
- `not_financial_advice`

Before any AI output reaches users, `api/ai.php` validates the shape, allowed
sentiment values, confidence range, required uncertainty, required
`market_data_age_seconds`, and required `not_financial_advice=true`. It also
blocks advice-like language such as instructions to buy, sell, hold, short,
long, use leverage, set stop losses, or size positions.

Successful responses use the shared API envelope:

```json
{
  "ok": true,
  "data": {
    "status": "available",
    "insight": {
      "type": "market_summary",
      "title": "TON market context",
      "summary": "TON is moving higher while volume remains below the recent peak.",
      "sentiment": "mixed",
      "confidence": 0.62,
      "drivers": ["Price is positive on the day."],
      "risks": ["Short-term reversals remain possible."],
      "uncertainty": "This is based on a narrow market snapshot and may change quickly.",
      "safety": {
        "not_financial_advice": true
      },
      "freshness": {
        "market_data_age_seconds": 90,
        "market_data_updated_at": null
      }
    }
  },
  "meta": {
    "request_id": "client-or-generated-id",
    "provider": {
      "name": "groq",
      "model_id": "llama-3.3-70b-versatile",
      "prompt_version": "v1",
      "credentialed": true
    },
    "cost": {
      "requests": 1,
      "provider_failures": 0,
      "input_tokens": 100,
      "output_tokens": 42,
      "total_tokens": 142,
      "estimated_cost_usd": null
    }
  }
}
```

## Safety Rules

Every prompt includes these requirements:

- Provide factual market context only.
- Do not provide investment advice.
- Include plain not financial advice safety framing.
- Do not recommend buying, selling, holding, leverage, shorts, longs, stop
  losses, take-profit levels, or position sizing.
- Include `not_financial_advice=true`.
- Cite `market_data_age_seconds` so users see market data age.
- Include uncertainty when evidence is mixed, sparse, or stale.

The response validator enforces those rules again after the provider responds.
Unsafe or malformed output is treated the same as a provider failure.

## Fallback Behavior

Provider failures degrade to a normal success envelope with:

```json
{
  "status": "insight unavailable",
  "insight": null,
  "reason": "provider_rate_limited"
}
```

Normalized fallback reasons include `ai_disabled`, `provider_not_configured`,
`feature_disabled`, `provider_rate_limited`, `provider_auth_failed`,
`provider_timeout`, `provider_invalid_json`, `provider_unavailable`, and
`schema_validation_failed`. Provider error bodies, raw prompts, API keys, and
unsafe model text are not copied into responses.

## Health And Cost Counters

`/api/health` and `/api/ready` include a Groq check under
`checks.upstream_providers.checks.groq`. The check reports safe values:
configured state, model id, prompt version, enabled features, fallback behavior,
rate-limit metadata, and cost counters tracked by responses. It never reports
`GROQ_API_KEY` or raw prompt text.

Per-request AI metadata includes cost counters derived from provider `usage`:
request count, provider failure count, input tokens, output tokens, total
tokens, and `estimated_cost_usd`. The estimated cost is `null` until pricing is
configured explicitly in a later admin/cost-management issue.

## Tests

Regression coverage lives in `tests/ai-provider-check.sh` and is available
through:

```sh
npm run test:ai-provider
```

The check verifies environment-driven Groq configuration, server-side
credential use, structured JSON validation, safety-rule enforcement, fallback
to `insight unavailable`, safe health metadata, cost counters, and package
script wiring.

## References

- Groq OpenAI compatibility: https://console.groq.com/docs/openai
- Groq Chat Completions API reference: https://console.groq.com/docs/api-reference
- Groq Structured Outputs: https://console.groq.com/docs/structured-outputs
