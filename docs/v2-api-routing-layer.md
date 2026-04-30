# TONBANKCARD V2 API Routing Layer

Date: 2026-04-30

Issue: [#12](https://github.com/labtgbot/marketcap/issues/12)

## Objective

Provide a small PHP 8.1+ `/api/*` surface for the public website and Telegram
Mini App so future V2 screens can call server-owned endpoints instead of
calling private providers directly from browser JavaScript.

## Routing Contract

The local PHP router sends `/api` and `/api/*` requests through `index.php`,
which loads runtime configuration, validates it, and dispatches to
`api/router.php` before rendering the Vue/Vuetify website shell.

Initial routes:

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/api` | Service metadata and registered route list. |
| `GET` | `/api/health` | Liveness response with app boot, configuration, database, Redis, and upstream provider checks. |
| `GET` | `/api/ready` | Readiness response. Returns `503` when required configuration or required dependency checks fail. |
| `POST` | `/api/telegram/session` | Validates raw Telegram Mini App `initData` and creates or refreshes a server session. |
| `OPTIONS` | `/api/*` | CORS preflight response for configured website and Mini App origins. |

Unknown routes return `404` with a JSON error envelope. Unsupported methods
return `405` with an `Allow` header.

## JSON Envelope

Successful responses use one shape:

```json
{
  "ok": true,
  "data": {},
  "meta": {
    "request_id": "client-or-generated-id"
  }
}
```

Error responses use one shape:

```json
{
  "ok": false,
  "error": {
    "code": "not_found",
    "message": "No API route matches the request.",
    "details": {}
  },
  "meta": {
    "request_id": "client-or-generated-id"
  }
}
```

`X-Request-ID` is accepted when it contains only safe identifier characters and
is also returned in `meta.request_id` and the response header. Invalid or missing
request IDs are replaced with generated IDs.

## Health And Readiness

`/api/health` confirms that the PHP app booted and reports distinct check
objects for:

- `app_boot`
- `configuration`
- `database`
- `redis`
- `upstream_providers`

Database and Redis probes are non-invasive by default. They report whether the
dependency is configured, whether it is required for the active runtime profile,
and whether active probing is enabled. Set
`TONBANKCARD_API_ACTIVE_READINESS=true` to enable connection probes in
environments where that is safe.

`/api/ready` uses the same check model and returns a success envelope only when
required configuration is valid. If readiness fails, the response is an error
envelope with code `not_ready` and safe check details.

## CORS

The CORS policy lives in `config/api.php`. Allowed origins are derived from the
active runtime URLs: local, staging, public website, and Telegram Mini App. The
API allows `GET`, `POST`, and `OPTIONS` so later write endpoints can share the
same preflight behavior.

Allowed request headers:

- `Authorization`
- `Content-Type`
- `X-Request-ID`
- `X-TONBANKCARD-Session`
- `X-Telegram-Init-Data`

Credentialed CORS is allowed only for configured origins. The API does not emit
a wildcard origin.

## Middleware Hooks

The first implementation adds explicit hooks for request IDs, CORS, sessions,
rate limits, validation, and audit logging. In practical terms, the API now has
middleware hooks for sessions, rate limits, validation, and audit logging. The
hooks are intentionally small:

- Session middleware detects whether a bearer token or
  `tonbankcard_session` cookie exists, but does not trust or expose its value.
- Rate limiting is configured as a disabled hook until the Redis-backed
  limiter is implemented.
- Validation rejects invalid JSON request bodies before route handlers run.
- Audit logging is disabled by default and can be enabled with
  `TONBANKCARD_API_AUDIT_LOG=true` after a production-safe sink is selected.

These hooks are the extension points for the Telegram session, provider gateway,
watchlist, alert, and admin issues that follow this routing layer.

Issue #13 adds `/api/telegram/session` on top of this routing layer. See
`docs/v2-telegram-session.md` for the Telegram-specific validation, storage,
and local browser fallback contract.

## Secret Handling

API keys, bot tokens, Redis tokens, and database passwords stay in server-side
runtime configuration. Health, readiness, and error responses expose only
configuration state and safe names, never secret values.

Future API routes must keep this rule: secrets stay server-side and browser
responses may only include derived, minimum-necessary data.

## Backend Style And Tests

Backend API code follows the existing repository style:

- Plain PHP functions with the `tonbankcard_api_` prefix.
- Array payloads and explicit JSON encoding instead of framework globals.
- Uppercase `TRUE` and `FALSE`, matching existing PHP files.
- Small pure functions where possible so shell tests can exercise route logic
  without starting a web server.

Regression coverage for this layer lives in `tests/api-routing-check.sh` and is
available through:

```sh
npm run test:api
```

The check verifies required files, documentation, JSON success and error
envelopes, safe request ID behavior, CORS preflight headers, invalid JSON
validation, and package script wiring.

## Acceptance Criteria Mapping

| Issue #12 acceptance criterion | Coverage |
| --- | --- |
| API routes return consistent JSON success and error shapes. | `tonbankcard_api_success_response()`, `tonbankcard_api_error_response()`, and `tests/api-routing-check.sh`. |
| Invalid requests produce safe, actionable errors. | Unknown routes, unsupported methods, and invalid JSON bodies return structured errors without leaking secrets. |
| Health endpoint distinguishes app boot, database, Redis, and upstream provider availability. | `/api/health` includes distinct `app_boot`, `database`, `redis`, and `upstream_providers` checks. |
| Backend code style and test conventions are documented. | This document defines the API style and points to `tests/api-routing-check.sh`. |
