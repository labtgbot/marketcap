# TONBANKCARD V2 AI Insight Cards

Date: 2026-05-01

Issue: [#28](https://github.com/labtgbot/marketcap/issues/28)

This document defines the Stage 4 AI insight cards that sit on top of the
Groq-first provider foundation from issue #17. The cards expose concise market
context while preserving the product rule that AI output is informational only,
is not financial advice, and must never recommend buying, selling, or holding
any asset.

## Surfaces

| Surface | Insight type | Context source |
| --- | --- | --- |
| Market pulse | `market_summary` | Global market metrics, top gainers and losers, TON assets, watchlist preview, and market freshness metadata. |
| Coin detail | `coin_summary` | Coin price, 24h change, market cap, volume, supply, score metadata, and TON ecosystem classification. |
| Watchlist digest | `watchlist_digest` | The watchlist digest uses user-selected assets, sort state, freshness status, storage mode, and the active quote currency. |
| TON ecosystem pulse | `ton_ecosystem_pulse` | TON-focused market rows plus route-level coverage areas for Toncoin, Telegram-native discovery, and risk-aware context. |
| Alert explanation | `alert_explanation` | Coin detail market data shaped as alert context, including price range, 24h movement, liquidity, and watchlist state. |

Each route prepares bounded factual context in the browser and sends it to
`POST /api/ai/insight`. Provider credentials, raw prompts, and provider bodies
remain server-side. The server includes provider name, model id, prompt version,
confidence, source freshness, uncertainty, and the not financial advice safety
flag only after schema validation succeeds.

## Safety Controls

AI cards never recommend buying, selling, or holding. The server prompt
explicitly forbids investment-action verbs, the schema requires
`not_financial_advice`, uncertainty, and `market_data_age_seconds`, and the
server rejects generated text that contains advice terms such as buy, sell,
hold, leverage, stop loss, take profit, or position size. The browser repeats a
client-side unsafe output guard before rendering any available insight.

When the AI feature flag is off, the provider is missing, the provider fails, or
schema validation rejects output, cards degrade to an unavailable state while
the underlying market data remains visible. The fallback does not expose raw
provider errors, prompts, secrets, or unsafe model text.

## Card UI

Every successful card displays:

- A neutral AI-generated title and summary.
- Sentiment and confidence indicators.
- Source freshness based on market data age.
- A visible not financial advice label.
- Factual drivers, risks, and uncertainty when supplied by the provider.
- Icon feedback controls for helpful, stale, wrong, and unsafe output.

Cards keep the same route layout density as the surrounding V2 UI. The feedback
buttons use icons with tooltips so the main card remains scan-friendly on
desktop and mobile.

## Feedback Storage

Feedback is submitted to `POST /api/ai/feedback` with the validated insight id,
insight type, feedback type, provider metadata, prompt version, source route,
surface, market data age, and small sanitized metadata. The endpoint stores
feedback for admin review in `ai_feedback` when MySQL or MariaDB is configured.
Local development can use `TONBANKCARD_AI_FEEDBACK_STORE` as a JSON file
fallback.

Feedback storage does not persist raw prompts, raw provider responses, or raw
insight subjects. Subjects and session tokens are hashed before durable storage.
Rows start in `review_state = pending` so future admin tools can review unsafe,
wrong, stale, or helpful output.

## Regression Coverage

`tests/ai-insight-cards-check.sh` verifies the issue #28 contract:

- `ton_ecosystem_pulse` validates and degrades cleanly when AI is disabled.
- Unsafe alert explanation output is rejected and does not reach users.
- Feedback payloads are validated and stored without raw subject text.
- Frontend routes include AI cards for market pulse, coin detail, watchlist,
  TON ecosystem, and alert explanation.
- Documentation, migration files, environment config, source bundle entries,
  and npm scripts remain wired into the repo.

Run the focused check with:

```sh
npm run test:ai-insights
```
