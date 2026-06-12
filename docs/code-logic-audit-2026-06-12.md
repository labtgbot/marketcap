# Code-Logic & Security Audit - Post-Stage 7 Follow-up

Date: 2026-06-12
Scope: Full code-logic, security, and reliability follow-up audit for issue
**#229**, performed after the Stage 7 hardening epic had been closed.

> This document is the deliverable for issue **#229** and PR **#230**. Every
> actionable finding below is tracked as a separate GitHub issue under the
> Stage 8 tracking epic **#231**.

---

## 1. Method

The audit reviewed the current tree after the closed Stage 7 work. The review
first checked the previous audit and its follow-up issues so this report would
not duplicate already resolved defects.

Reviewed sources:

- Previous audit: `docs/code-logic-audit-2026.md`.
- Closed Stage 7 issues: #183 through #204.
- Merged Stage 7 PRs: #205 through #226, plus dependency follow-ups #227 and
  #228.
- API routing, middleware, rate limits, CSRF, auth-adjacent helpers, and
  route-specific handlers in `api/*.php`.
- Runtime configuration and `.env` parsing in `config/runtime.php`.
- Admin configuration persistence in `api/admin.php`.
- Public web access controls in `.htaccess` and `dev/php/router.php`.
- PWA cache behavior in `service-worker.js`.
- Existing documentation gates and package-level test wiring.

Severity and scheduling:

- **P1**: high-priority security or availability risk that should be fixed
  before relying on production abuse controls.
- **P2**: security or operational hardening with lower immediate exploitability
  but real leakage or misuse risk.
- **P3**: reliability or hardening cleanup that should be tracked but is not a
  launch blocker on its own.

---

## 2. Stage 7 Baseline

The prior code-logic audit found 21 issues and created Stage 7 follow-ups. The
issue tracker and merged PR list show those follow-ups closed:

| Stage 7 area | Result verified during this audit |
| --- | --- |
| Webhook fail-open, payment self-grant, JSON-LD XSS, locale redirect, worker fail-open | Closed through #206 through #212. |
| Web access, HTTPS/HSTS, migrations, rate-limit fail-open, AI endpoint limits | Closed through #213 through #217. |
| CSP/X-Frame-Options, private stores, DB TLS, URL scheme validation, debug defaults | Closed through #218 through #222. |
| Route attrs, rollback safety, ZIP removal, action pinning | Closed through #223 through #226. |
| Dependency follow-up for `shivammathur/setup-php` | Closed through #227 and #228. |

This audit found new or adjacent defects that were not duplicates of the closed
Stage 7 issues.

---

## 3. Findings Summary

| ID | Priority | Area | Title | Primary location | Issue |
| --- | --- | --- | --- | --- | --- |
| F1 | P1 | Admin/Security | Admin `.env` writer allows newline injection | `api/admin.php:2523` | #232 |
| F2 | P1 | API/Security | API router reads request bodies without a global size cap | `api/router.php:79` | #233 |
| F3 | P1 | API/Security | Auth rate limits are bypassable by rotating invalid credentials | `api/cache.php:555` | #234 |
| F4 | P2 | Ops/Security | Worker secrets are accepted through URL query strings | `api/search.php:136`, `api/ton.php:864` | #235 |
| F5 | P3 | PWA/Reliability | Service worker caches non-cacheable navigation responses | `service-worker.js:60` | #236 |

---

## 4. Detailed Findings

### F1 - Admin `.env` writer allows newline injection (P1)

`tonbankcard_api_admin_save_env_updates()` filters updates to an allow-list of
keys, but it does not sanitize line breaks inside the values before writing
`.env`.

Evidence:

- `api/admin.php:2399-2472` writes filtered updates into `.env`.
- `api/admin.php:2480-2515` limits keys, but values are still arbitrary strings.
- `api/admin.php:2523-2531` escapes only backslashes and double quotes.
- `config/runtime.php:18-60` parses `.env` line by line, so an injected newline
  becomes a new variable assignment.
- `install/includes/installer.php:1014-1025` already has the safer behavior and
  escapes `\r` and `\n`; the admin writer did not inherit that fix.

Confirmed locally: an allowed value such as
`GROQ_MODEL_ID="llama\nTONBANKCARD_ADMIN_TOKEN=attacker-token"` is written as
two `.env` lines and creates the second variable on the next env load.

Risk: an admin write-flow, compromised admin session, or future XSS in the admin
surface can write configuration outside the intended allow-list.

Tracked by #232.

### F2 - API router reads request bodies without a global size cap (P1)

`tonbankcard_api_request_from_globals()` calls `file_get_contents('php://input')`
before routing, rate limiting, auth, JSON validation, or route-specific body
limits.

Evidence:

- `api/router.php:68-87` builds the request and reads the entire input stream.
- `api/router.php:975-990` validates JSON only after the full body is already in
  memory.
- `api/cache.php:689-735` applies rate limiting after the request object exists.
- AI-specific size checks are not a global defense because the router has
  already read the body and other routes do not have equivalent guards.

Risk: a large POST to any `/api/*` path can consume PHP worker memory before the
application can reject it, including routes that later return 404 or 405.

Tracked by #233.

### F3 - Auth rate limits are bypassable by rotating invalid credentials (P1)

The rate limiter classifies identity before auth. For admin and session-like
requests it hashes the provided credential material directly into the bucket
key, even when that credential has not been validated.

Evidence:

- `api/cache.php:555-560` uses raw `Authorization` or
  `X-TONBANKCARD-Admin` values for `admin_action`.
- `api/cache.php:563-571` uses raw session or Telegram initData values for
  `telegram_session`.
- `api/cache.php:689-735` applies the decision before route handlers authenticate
  the token or session.

Local reproduction against `tonbankcard_api_rate_limit_identity()`:

```json
{
  "admin_policy": "admin_action",
  "admin_keys_differ": true,
  "session_policy": "telegram_session",
  "session_keys_differ": true
}
```

This means two arbitrary invalid values create two different buckets before
auth. Stage 7 fixed User-Agent rotation and Redis fail-open behavior, but this
is a separate credential-rotation bypass.

Tracked by #234.

### F4 - Worker secrets are accepted through URL query strings (P2)

Search refresh and TON curation endpoints accept secrets from `?token=` as a
fallback to headers.

Evidence:

- `api/search.php:133-140` accepts
  `x-tonbankcard-search-refresh-token` or `query.token`.
- `api/ton.php:861-868` accepts
  `x-tonbankcard-ton-curation-token` or `query.token`.
- The alert worker already uses a header-only model, which is the safer local
  precedent.

Risk: query-string secrets are commonly copied into access logs, reverse-proxy
logs, browser history, screenshots, shell history, analytics, and referrers.

Tracked by #235.

### F5 - Service worker caches non-cacheable navigation responses (P3)

The service worker writes navigation and static responses into runtime cache
without checking whether the response is successful or cacheable.

Evidence:

- `service-worker.js:60-67` caches navigation responses without `response.ok` or
  `Cache-Control` checks.
- `service-worker.js:70-77` applies the same pattern to static assets.
- `service-worker.js:39-54` excludes `/api`, but not admin, installer, or other
  sensitive navigation prefixes.

Risk: transient error pages, redirect shells, login/admin pages, or `no-store`
responses can be persisted unexpectedly. The offline fallback also ignores a
route-specific cached response and always falls back to `/offline.html` or `/`.

Tracked by #236.

---

## 5. False-Positive and Resolved Checks

These checks were explicitly reviewed and not opened as new Stage 8 issues:

- `.htaccess` and `dev/php/router.php` now block `install/`, `database/`,
  `docs/`, `tests/`, `dev/`, dotfiles, and sensitive file extensions. This
  corresponds to the closed Stage 7 web-access issue #191.
- HTTPS redirect, HSTS, `X-Frame-Options`, and static security headers are
  present in `.htaccess`, matching the Stage 7 fixes for #192 and #196.
- `validURLString` now allows only `http:` and `https:` URLs, and invoice-link
  navigation uses that helper before changing `window.location`.
- Alert worker authorization fails closed when the worker token is unset and
  does not use the query-string token pattern.
- Admin JSON state persistence redacts secrets and writes through a private
  store path before the `.env` writer path is reached.

---

## 6. Follow-up Issues

Tracking epic: **#231 - [Stage 8] Post-hardening code-logic and security audit**.

| Issue | Priority | Labels | Summary |
| --- | --- | --- | --- |
| #232 | P1 | `bug`, `security`, `roadmap`, `stage-8-post-hardening` | Escape or reject CR/LF in the admin `.env` writer and add a regression test. |
| #233 | P1 | `bug`, `security`, `reliability`, `roadmap`, `stage-8-post-hardening` | Enforce a global API request body limit before reading the full stream. |
| #234 | P1 | `bug`, `security`, `roadmap`, `stage-8-post-hardening` | Classify pre-auth rate-limit buckets by stable request identity, not untrusted credentials. |
| #235 | P2 | `enhancement`, `security`, `roadmap`, `stage-8-post-hardening` | Remove production query-string token fallback for worker and curation endpoints. |
| #236 | P3 | `bug`, `reliability`, `roadmap`, `stage-8-post-hardening` | Make service-worker runtime caching respect status, cache headers, and sensitive paths. |

---

## 7. Definition of Done

- Every P1 issue has a reproducing test that fails before the fix and passes
  after the fix.
- Every security fix changes the relevant behavior to fail closed or validates
  the trust boundary before using attacker-controlled input.
- Documentation and operational examples avoid placing secrets in URLs.
- `npm test` and CI pass after the follow-up fixes are merged.

Audit reference: #229. Tracking epic: #231. Prepared PR: #230.
