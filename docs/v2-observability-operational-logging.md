# TONBANKCARD V2 Observability And Operational Logging

Date: 2026-04-30

Issue: [#18](https://github.com/labtgbot/marketcap/issues/18)

## Objective

Make failures traceable before alerts, AI, payment, and bot workflows add more
moving parts. Every API request keeps a `request_id` through browser events,
API envelopes, server logs, and provider diagnostics.

## Runtime Flags

| Variable | Default | Purpose |
| --- | --- | --- |
| `TONBANKCARD_OBSERVABILITY_LOG_LEVEL` | `warning` | Minimum emitted server log level: `debug`, `info`, `warning`, `error`, `critical`, or `off`. |
| `TONBANKCARD_VERBOSE_TRACING` | `false` | Default-off verbose tracing. When `true`, safe debug/info records are emitted without logging secrets or request bodies. |
| `TONBANKCARD_CLIENT_ERROR_REPORTING` | `true` | Allows browser boot, Vue, unhandled promise, and API error reports to post to `/api/observability/client-error`. |
| `TONBANKCARD_ERROR_MONITORING_ENABLED` | `false` | Default-off error-aggregation forwarding. When `true` with a DSN set, severe redacted events are forwarded to an external aggregator. |
| `TONBANKCARD_ERROR_MONITORING_DSN` | empty | Sentry-compatible DSN (`https://<key>@<host>/<project>`) or plain HTTP(S) webhook URL. Keep this secret out of the repo and set it only in the deployment environment. |
| `TONBANKCARD_ERROR_MONITORING_MIN_LEVEL` | `error` | Minimum severity to forward. Forwarding never fires below this level. |
| `TONBANKCARD_ERROR_MONITORING_ENVIRONMENT` | profile | Environment tag attached to forwarded events. Defaults to the active `TONBANKCARD_PROFILE`. |
| `TONBANKCARD_ERROR_MONITORING_TIMEOUT_MS` | `2000` | Best-effort dispatch timeout. Forwarding never blocks or fails the originating request. |
| `TONBANKCARD_UPTIME_MONITOR_ENABLED` | `false` | Default-off bundled uptime monitor (`api/uptime-monitor.php`). When `true`, scheduled runs probe the health endpoints and page Telegram on failure. |
| `TONBANKCARD_UPTIME_MONITOR_BASE_URL` | active URL | Base URL the monitor polls. Defaults to the active profile base URL. |
| `TONBANKCARD_UPTIME_MONITOR_TARGETS` | `/api/health,/api/ready` | Comma-separated health paths to probe. A probe fails on a non-200 status or a `ready: false` body. |
| `TONBANKCARD_UPTIME_MONITOR_TELEGRAM_CHAT_ID` | empty | Telegram chat/channel id paged on failure. Paging is skipped when empty. |
| `TONBANKCARD_UPTIME_MONITOR_BOT_TOKEN` | bot token | Bot token used to send alerts. Defaults to `TONBANKCARD_BOT_TOKEN` (the alerts worker bot). |
| `TONBANKCARD_UPTIME_MONITOR_TIMEOUT_MS` | `5000` | Per-probe timeout. Probing is best-effort and never blocks request handling. |
| `TONBANKCARD_UPTIME_MONITOR_ENVIRONMENT` | profile | Environment tag included in the alert message. Defaults to the active `TONBANKCARD_PROFILE`. |

Operational logs are JSON lines written to the PHP `error_log` sink by default.
Secrets, authorization headers, cookies, session tokens, bot tokens, provider
API keys, Telegram `initData`, and password-like fields are redacted before
emission.

## Trace Contract

Frontend market requests receive an `X-Request-ID` header from the browser. The
same value appears in:

- The browser-submitted `frontend.api_error` event when a request fails.
- `api.request_completed` server logs for the `/api/*` request.
- `market.provider_request` debug logs when verbose tracing is enabled.
- `market.provider_response` logs for provider success or failure.
- The API response `X-Request-ID` header and `meta.request_id` envelope field.

A failed market request can therefore be traced with:

```sh
grep '"request_id":"trace-market-1"' /var/log/php*.log
```

## Captured Events

| Event | Level | Notes |
| --- | --- | --- |
| `api.request_completed` | `info`, `warning`, or `error` | Includes method, path, route group, status, error code, and duration. Success logs require `info` or verbose tracing. |
| `api.unhandled_exception` | `error` | Captures exception class and request context without request bodies. |
| `market.provider_request` | `debug` | Includes provider, plan, gateway path, upstream path, and query keys only. |
| `market.provider_response` | `info`, `warning`, or `error` | Includes provider status, normalized public error code, retry-after presence, and duration. |
| `frontend.boot_error` | `error` | Browser boot errors sent through the client error endpoint. |
| `frontend.vue_error` | `error` | Vue render and lifecycle errors. |
| `frontend.unhandled_rejection` | `error` | Unhandled promise rejections. |
| `frontend.api_error` | `warning` or `error` | Browser API failures with request id, method, path-only URL, status, and normalized error code. |
| `queue.failure` | `warning` | Shared helper for future alert, payment, AI, and notification queue failures. |
| `bot.delivery_failure` | `warning` | Shared helper for future Telegram bot delivery failures. |

## Core Service Health Queries

Check readiness without active dependency probes:

```sh
curl -fsS https://marketcap.tonbankcard.com/api/ready | jq .
```

Find failed API requests by route group:

```sh
jq -r 'select(.event=="api.request_completed" and .status >= 400) | [.timestamp,.route_group,.status,.error_code,.request_id] | @tsv' /var/log/tonbankcard/*.jsonl
```

Find market provider failures:

```sh
jq -r 'select(.event=="market.provider_response" and .status >= 400) | [.timestamp,.provider,.provider_plan,.upstream_status,.error_code,.request_id] | @tsv' /var/log/tonbankcard/*.jsonl
```

Find frontend API failures:

```sh
jq -r 'select(.event=="frontend.api_error") | [.timestamp,.status,.error_code,.api_path,.request_id] | @tsv' /var/log/tonbankcard/*.jsonl
```

Count queue failure and bot delivery failure logs:

```sh
jq -r 'select(.event=="queue.failure" or .event=="bot.delivery_failure") | [.timestamp,.event,.operation,.request_id] | @tsv' /var/log/tonbankcard/*.jsonl
```

## Common Failure Modes

| Symptom | First checks | Expected signal |
| --- | --- | --- |
| Market screen fails to load. | Search the frontend `request_id`, then inspect matching `api.request_completed` and `market.provider_response` logs. | `provider_timeout`, `provider_rate_limited`, `provider_auth_failed`, or `provider_unavailable`. |
| Browser renders a blank screen. | Search `frontend.boot_error`, `frontend.vue_error`, and `frontend.unhandled_rejection`. | Path-only source, message, and client event id without stack dumps or secrets. |
| `/api/ready` returns `503`. | Inspect readiness JSON and recent `api.request_completed` logs for `/api/ready`. | `not_ready` plus safe dependency check names. |
| Queue failure. | Search `queue.failure` by `request_id`, queue, or operation. | Redacted queue context and safe job identifiers. |
| Telegram bot delivery failure. | Search `bot.delivery_failure` by `request_id`, provider, or operation. | Redacted delivery context and hashed user identifiers where available. |

## Error Aggregation And Uptime Monitoring

- Error-aggregation forwarding is disabled by default. It only activates when
  `TONBANKCARD_ERROR_MONITORING_ENABLED=true` and a non-empty
  `TONBANKCARD_ERROR_MONITORING_DSN` are both set, so no events leave the host
  unless an operator opts in.
- When enabled, `tonbankcard_observability_log` forwards events at or above
  `TONBANKCARD_ERROR_MONITORING_MIN_LEVEL` (default `error`) to the destination.
  Only the already-redacted log entry is forwarded; the same privacy rules below
  apply, so secrets are never shipped to the aggregator.
- Sentry-compatible DSNs are posted to the Sentry store API; plain HTTP(S) DSNs
  receive a generic JSON envelope. Dispatch is best-effort with a short timeout
  and never blocks or fails the originating request.
- Browser errors reuse `TONBANKCARD_CLIENT_ERROR_REPORTING` and post to
  `/api/observability/client-error`, which logs through the same hook so frontend
  errors reach the aggregator alongside server events.
- Pair forwarding with an uptime monitor that polls `/api/health` and
  `/api/ready` and routes failures to the operations Telegram alert channel. See
  `docs/release-checklist.md` for the monitoring owner, tool, and alert-routing
  matrix.
- A bundled monitor `api/uptime-monitor.php` ships for self-hosters without an
  external probe. It is disabled by default and enabled with
  `TONBANKCARD_UPTIME_MONITOR_ENABLED=true`. Schedule it from cron, for example
  every minute:

  ```cron
  * * * * * php /path/to/api/uptime-monitor.php >> /var/log/uptime-monitor.log 2>&1
  ```

  Each run probes `TONBANKCARD_UPTIME_MONITOR_TARGETS` (default `/api/health` and
  `/api/ready`). A target fails on a non-200 status or a `ready: false` body. On
  failure it pages `TONBANKCARD_UPTIME_MONITOR_TELEGRAM_CHAT_ID` using the
  existing bot infrastructure (the bot token defaults to `TONBANKCARD_BOT_TOKEN`).
  The CLI exits non-zero on failure, and the bot token — embedded only in the
  Telegram API URL — is never logged or printed in the run summary.

## Privacy Rules

- Do not log request bodies for Telegram session validation, AI prompts, wallet
  addresses, payment receipts, alert thresholds, or provider payloads.
- Use allowlisted frontend fields only: event type, request id, path-only URLs,
  method, status, error code, duration, and bounded messages.
- Keep verbose tracing disabled by default. Enable it temporarily during
  incident response and disable it when the investigation is complete.
- Use hashes for user, session, wallet, chat, and bot-recipient identifiers when
  a future workflow needs identity-level diagnostics.

## Tests

Regression coverage lives in `tests/observability-check.sh` and is available
through:

```sh
npm run test:observability
```

The check verifies runtime flags, documentation, browser instrumentation,
request-id propagation, provider and API failure logs, frontend error ingestion,
secret redaction, and queue/bot helper logging.
