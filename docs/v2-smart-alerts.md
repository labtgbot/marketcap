# TONBANKCARD V2 Smart Alerts

Issue: [#32](https://github.com/labtgbot/marketcap/issues/32)

## Scope

Smart alerts let a trusted Telegram Mini App user create, pause, edit, delete, and test market rules that are delivered by the Telegram bot. Delivery payloads include a `startapp` deep link back into the Mini App alert detail route or the matching coin context.

Supported trigger types:

- `price_cross`
- `percent_move`
- `volume_spike`
- `market_cap_cross`
- `rank_change`
- `sentiment_change`
- `ton_ecosystem`

Each rule stores the coin id, optional symbol/name, comparison operator, threshold, Telegram delivery channel, quiet hours, timezone, frequency caps, daily delivery cap, and a Mini App context path.

## API

The alerts API uses the shared JSON envelope from `/api/*` and trusted Telegram sessions from `/api/telegram/session`.

- `GET /api/alerts` lists the user's active and paused rules.
- `POST /api/alerts` creates a rule until the per-user rule limit is reached.
- `GET /api/alerts/{id}` returns one rule.
- `PUT /api/alerts/{id}` edits a rule or pauses/resumes it through `status`.
- `DELETE /api/alerts/{id}` soft-deletes a rule.
- `POST /api/alerts/{id}/test` queues a test delivery payload and returns the exact Mini App link.
- `POST /api/alerts/evaluate` is the scheduled worker entry point.

`/api/alerts/evaluate` requires `X-TONBANKCARD-Alert-Worker-Token` to match the configured `TONBANKCARD_ALERT_WORKER_TOKEN`. The endpoint fails closed: when the token is unset it returns `503 alerts_worker_token_unset`, and a missing or wrong token returns `401 alerts_worker_token_required` (compared with `hash_equals`). Once authenticated, the worker selects due active rules, fetches market rows through the server-owned CoinGecko gateway, evaluates conditions, applies quiet hours and frequency caps, records alert delivery attempts, and schedules the next evaluation timestamp.

## Delivery

Telegram bot messages include concise market context plus an inline button. When a bot username is configured, links use:

```text
https://t.me/{bot_username}?startapp=alert_{alert_id}
```

The Mini App routes are:

- `/alerts` for public web management.
- `/app/alerts` for the Mini App list.
- `/app/alert/{id}` for alert detail context.

If Telegram bot delivery is unavailable, the worker records a retryable queued delivery with the same `deep_link_url` and `delivery_fingerprint` so later retries remain idempotent and observable.

## Limits

- `TONBANKCARD_ALERT_MAX_RULES_PER_USER` controls per-user alert limits.
- `TONBANKCARD_ALERT_DEFAULT_FREQUENCY_CAP_SECONDS` sets the default repeated-delivery guard.
- `TONBANKCARD_ALERT_MAX_DELIVERIES_PER_DAY` limits daily messages per rule.
- `TONBANKCARD_ALERT_EVALUATION_INTERVAL_SECONDS` sets the next worker evaluation cadence.
- `TONBANKCARD_FEATURE_ALERTS=true` enables the server-side API and worker.

Quiet hours are stored per rule with `quiet_hours_start`, `quiet_hours_end`, and `timezone`. Overnight windows are supported by treating the range as crossing midnight.

## Frontend

`dev/js/src/alerts.js` provides local alert persistence first and upgrades to server sync when a Telegram Mini App session validates. `dev/js/src/routes/alerts.js` powers the management route, while coin detail pages write `TONBANKCARD:alertDraft` and navigate to the alerts form.

The checked-in bundle must be rebuilt with:

```sh
node dev/js/tools/build.js
```

## Testing

Regression coverage lives in `tests/alerts-check.sh`. It verifies route wiring, schema migration fields, API evaluator helpers, Telegram `startapp` delivery links, quiet hours, frequency caps, package scripts, docs, and frontend route assets.
