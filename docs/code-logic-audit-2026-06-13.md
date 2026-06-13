# Code-Logic & Security Audit - Post-Stage 8 Follow-up

Date: 2026-06-13
Scope: Full code-logic, security, and reliability follow-up audit for issue
**#238**, performed after the Stage 8 hardening epic had been closed.

> This document is the deliverable for issue **#238** and PR **#239**. Every
> actionable finding below is tracked as a separate GitHub issue under the
> Stage 9 tracking epic **#240**.

---

## 1. Method

The audit reviewed the current tree after the closed Stage 8 work. The review
first checked the previous audits and their follow-up issues so this report
would not duplicate already resolved defects.

Reviewed sources:

- Previous audit: `docs/code-logic-audit-2026-06-12.md`.
- Closed Stage 7 issues: #183 through #204, merged through PRs #205 through #228.
- Closed Stage 8 issues: #231 through #236, merged through PRs #230 and #237.
- API routing, request-identity, and rate limiting in `api/router.php` and
  `api/cache.php`.
- Telegram Stars payment idempotency and entitlement grants in `api/premium.php`
  and `database/migrations/0010_premium_payment_state.up.sql`.
- Alert evaluation, delivery, deduplication, and worker claim logic in
  `api/alerts.php`.
- AI request pipeline text handling in `api/ai.php`.
- Admin configuration persistence across `.env` and the JSON store in
  `api/admin.php`.
- Public web access controls in `.htaccess` and `dev/php/router.php`, including
  the `experiments/` directory.
- Runtime state paths in `config/runtime.php`.
- Existing documentation gates and package-level test wiring.

Severity and scheduling:

- **P1**: high-priority security or availability risk that should be fixed
  before relying on production abuse controls.
- **P2**: security or operational hardening with lower immediate exploitability
  but real leakage or misuse risk.
- **P3**: reliability or hardening cleanup that should be tracked but is not a
  launch blocker on its own.

---

## 2. Stage 7 and Stage 8 Baseline

The two prior code-logic audits found and closed their follow-ups. The issue
tracker and merged PR list show those follow-ups closed:

| Baseline area | Result verified during this audit |
| --- | --- |
| Stage 7: webhook fail-open, payment self-grant, JSON-LD XSS, locale redirect, worker fail-open, web access, HTTPS/HSTS, migrations, rate-limit fail-open, AI limits, CSP, private stores, DB TLS, URL scheme, debug defaults | Closed through #183 through #204 (PRs #205 through #228). |
| Stage 8: admin `.env` newline injection, global API body cap, pre-auth rate-limit identity, worker query-string secrets, service-worker caching | Closed through #232 through #236 (PRs #230 and #237). |

This audit found new or adjacent defects that are not duplicates of the closed
Stage 7 or Stage 8 issues. Each new finding is distinct from the closed work:
F2 covers the *source* of the rate-limit IP (spoofable `X-Forwarded-For`),
which is orthogonal to the Stage 8 credential-rotation identity fix (#234);
F3 covers payment *replay and non-atomic idempotency*, which is distinct from
the Stage 7 self-grant fix.

---

## 3. Findings Summary

| ID | Priority | Area | Title | Primary location | Issue |
| --- | --- | --- | --- | --- | --- |
| F1 | P2 | Security/Ops | Dev `experiments/` scripts are web-reachable and disclose paths and runtime | `.htaccess:28`, `experiments/test-delete-fix.php:6` | #241 |
| F2 | P2 | API/Security | Rate-limit IP is taken from spoofable `X-Forwarded-For` | `api/router.php:2180`, `api/cache.php:548` | #242 |
| F3 | P2 | Payments/Security | Telegram Stars idempotency is non-atomic and replay-prone | `api/premium.php:1088`, `database/migrations/0010_premium_payment_state.up.sql:33` | #243 |
| F4 | P2 | Alerts/Reliability | Transient alert delivery failures are silently lost | `api/alerts.php:732`, `api/alerts.php:742` | #244 |
| F5 | P3 | Alerts/Reliability | Alert dedup is ineffective and rules are not claimed atomically | `api/alerts.php:1156`, `api/alerts.php:1036` | #245 |
| F6 | P3 | AI/Reliability | AI text truncation is byte-oriented and corrupts multibyte UTF-8 | `api/ai.php:1610` | #246 |
| F7 | P3 | Admin/Reliability | Admin save is non-atomic across `.env` and the JSON store | `api/admin.php:2351`, `api/admin.php:2466` | #247 |
| F8 | P3 | API/Reliability | Rate-limit key can be left without a TTL on `EXPIRE` failure | `api/cache.php:730` | #248 |

---

## 4. Detailed Findings

### F1 - Dev `experiments/` scripts are web-reachable and disclose paths and runtime (P2)

The `.htaccess` source-blocking rule denies `install/`, `database/`, `docs/`,
`tests/`, and `dev/`, but it omits `experiments/`. Because the rewrite block
only routes to `index.php` when the requested file does not exist
(`RewriteCond %{REQUEST_FILENAME} !-f`), an existing `experiments/*.php` file is
served and executed directly.

Evidence:

- `.htaccess:28` - `RewriteRule ^(?:install|database|docs|tests|dev)(?:/|$) - [F,L,NC]`
  has no `experiments` alternative.
- `.htaccess:134-136` - the catch-all rewrite skips existing files, so an
  on-disk `experiments/*.php` runs instead of being routed.
- `experiments/test-delete-fix.php:6` - `define('GECKO_CLIENT_DIR', '/tmp/gh-issue-solver-1777975464058')`
  hardcodes an absolute build path; on a host without that path the includes at
  lines 9-12 fatal and can disclose filesystem layout.
- `experiments/repro-issue-104.php:34-60` - writes to a temp admin store, boots
  the runtime (`constants.php`, `functions.php`, `config/runtime.php`,
  `api/ton.php`), and echoes the asset catalog plus the CoinGecko coin IDs that
  would be requested.

Risk: information disclosure (absolute paths, internal asset catalog, coin-id
request plan) and execution of unmaintained dev scripts in production. These
files were not in the Stage 8 false-positive list and are not covered by the
existing deny-list.

Confirmed locally: the deny-list regex does not match `experiments/`, and the
two scripts above run application boot logic with no CLI/`php_sapi_name` guard.

Tracked by #241.

### F2 - Rate-limit IP is taken from spoofable `X-Forwarded-For` (P2)

The request IP used to build anonymous rate-limit buckets is read from
client-controlled forwarding headers with no trusted-proxy validation and no
`REMOTE_ADDR` fallback.

Evidence:

- `api/router.php:2180-2187` - `tonbankcard_api_request_ip()` returns the first
  entry of `X-Forwarded-For`, else `X-Real-IP`; it never consults
  `REMOTE_ADDR` and does not validate against a configured trusted proxy.
- `api/cache.php:548-577` - `tonbankcard_api_rate_limit_identity()` builds the
  `anonymous_web` bucket as `hash('sha256', 'anonymous|'.$ip)`.

Risk: an attacker can rotate `X-Forwarded-For` to mint a fresh rate-limit bucket
per request, defeating anonymous throttling. This is distinct from Stage 8 #234
(which fixed bucket *classification* by untrusted credentials); here the *IP
source itself* is attacker-controlled.

Tracked by #242.

### F3 - Telegram Stars idempotency is non-atomic and replay-prone (P2)

The successful-payment handler performs a read-only replay check, then grants
the entitlement, then best-effort records the event, with no transaction or row
lock spanning the check and the grant, and the event table's charge-id index is
not unique.

Evidence:

- `api/premium.php:1088-1111` - handler: replay check, then grant, then
  best-effort `record_event` (no transaction, no `FOR UPDATE`).
- `api/premium.php:1147-1179` - `payment_replayed()` is a read-only `SELECT`.
- `api/premium.php:1272-1366` - `grant_entitlement()` is additive
  (1292-1298: `expires_at = max(existing, now) + duration`).
- `api/premium.php:1585-1614` - `record_event()` swallows all `Throwable`, so a
  failed write does not block the grant.
- `database/migrations/0010_premium_payment_state.up.sql:33` -
  `KEY idx_premium_payment_events_charge (telegram_payment_charge_id_hash)` is a
  plain index, not `UNIQUE`.

Risk: two concurrent deliveries of the same `telegram_payment_charge_id` (Telegram
retries webhooks) can both pass the read-only replay check and each extend the
entitlement, double-granting subscription time. The non-unique index cannot
enforce single-grant at the database layer.

Tracked by #243.

### F4 - Transient alert delivery failures are silently lost (P2)

The alert evaluator advances a rule's evaluation cursor regardless of delivery
outcome, and the recorded `next_retry_at` has no consumer, so a transient send
failure permanently drops that notification.

Evidence:

- `api/alerts.php:732` - delivery status is computed as `sent`/`queued`/`failed`.
- `api/alerts.php:742` - `mark_evaluated()` advances `next_evaluation_at`
  regardless of whether delivery succeeded.
- `api/alerts.php:1136` - `record_delivery()` writes `next_retry_at`, but no code
  path reads it to retry.
- `api/alerts.php:1189` - the daily cap counts deliveries `IN ('queued','sent')`.

Risk: a temporary provider/network failure marks the rule evaluated and moves
on; the failed delivery is never retried, so users silently miss alerts.

Tracked by #244.

### F5 - Alert dedup is ineffective and rules are not claimed atomically (P3)

`last_delivery_fingerprint` is written but never read, so the dedup mechanism is
dead code; additionally `due_rules` selects rules without an atomic claim, so
overlapping worker runs can deliver the same alert twice.

Evidence:

- `api/alerts.php:1156` - the only reference to `last_delivery_fingerprint` is a
  write (`COALESCE(:fingerprint, last_delivery_fingerprint)`); nothing reads or
  compares it.
- `api/alerts.php:539-584` - `evaluate_rule()` computes a fresh fingerprint but
  does not compare it against `last_delivery_fingerprint` to suppress a repeat.
- `api/alerts.php:1036-1050` - `due_rules()` is a plain `SELECT ... LIMIT` with
  no `FOR UPDATE`/`SKIP LOCKED`/lease.

Risk: duplicate notifications under concurrent worker runs, and a fingerprint
field that creates a false impression of idempotency without providing it.

Tracked by #245.

### F6 - AI text truncation is byte-oriented and corrupts multibyte UTF-8 (P3)

`tonbankcard_api_ai_clean_text()` truncates text with byte-oriented `strlen`
and `substr`. For multibyte content (the app supports `ru`, `fr`, `ar`, `zh`),
cutting on a byte boundary splits the trailing character and yields invalid
UTF-8.

Evidence:

- `api/ai.php:1610-1617` - `if ( strlen( $text ) > $max_length ) { $text = substr( $text, 0, $max_length ); }`
  uses byte functions.
- `tonbankcard_supported_languages()` includes `ru`, `fr`, `ar`, `zh`.

Risk: corrupted trailing characters in AI prompts/metadata and downstream
encoding errors. Fix with `mb_strlen`/`mb_substr` (UTF-8).

Tracked by #246.

### F7 - Admin save is non-atomic across `.env` and the JSON store (P3)

`tonbankcard_api_admin_save_state()` writes `.env` and mutates the live process
environment *before* writing the JSON store. If the JSON write fails, `.env` and
the environment are already changed with no rollback, so the two persistence
layers diverge. The `.env` read-modify-write also holds no lock across read and
rename, so concurrent saves can lose changes.

Evidence:

- `api/admin.php:2351` - `save_env_updates()` runs before the JSON store write.
- `api/admin.php:2466-2471` - the live env is mutated (`putenv` + `$_ENV` +
  `$_SERVER`) immediately after writing `.env`.
- `api/admin.php:2356-2387` - the JSON store is written afterwards; a failure
  there returns an error while `.env`/env stay applied.
- `api/admin.php:2416` (read) and `:2453` (rename) - no held lock between them,
  so concurrent saves of different keys race.

Risk: configuration/secret divergence between `.env` and the admin store on I/O
failure, and lost updates under concurrent saves.

Tracked by #247.

### F8 - Rate-limit key can be left without a TTL on `EXPIRE` failure (P3)

In the Redis rate limiter, `EXPIRE` is only set on the first increment
(`count === 1`) and its result is not checked. If `EXPIRE` fails after a
successful `INCR`, the key is left with no TTL forever: subsequent requests take
the `count > 1` branch and never re-set the expiry, so the counter never resets.

Evidence:

- `api/cache.php:730-739` - `INCR`, then `if ( 1 === $count ) { EXPIRE ... }`;
  the `EXPIRE` result is unchecked and not re-asserted on later requests.

Risk: an identity whose `EXPIRE` did not apply accumulates a counter with no
reset and, once over the limit, stays blocked permanently until the key is
cleared manually. Fix by atomically guaranteeing a TTL
(`SET key 1 EX <window> NX` + `INCR`, or verify/re-assert `EXPIRE`).

Tracked by #248.

---

## 5. False-Positive and Resolved Checks

These checks were explicitly reviewed and not opened as new Stage 9 issues:

- The live admin store defaults to
  `tonbankcard_runtime_admin_store_path()` → `.tonbankcard-marketcap-state/`
  (`config/runtime.php:619-625`), not `sys_get_temp_dir()`, so the temp-file
  writes in `experiments/` do not collide with or poison the production admin
  store. This is why F1 is rated P2 (disclosure/execution) rather than P1
  (store poisoning).
- The `.htaccess` deny-list, HTTPS/HSTS redirect, and security headers from
  Stage 7 (#191, #192, #196) remain present; only the `experiments/` gap (F1)
  is new.
- Stage 8 fixes verified present: admin `.env` writer escapes CR/LF (#232),
  the global API body cap exists (#233), pre-auth rate-limit identity no longer
  keys on raw credentials (#234), worker endpoints dropped the query-string
  token fallback (#235), and the service worker checks `response.ok`/cache
  headers (#236).

---

## 6. Follow-up Issues

Tracking epic: **#240 - [Stage 9] Глубокий аудит логики, безопасности и надёжности**.

| Issue | Priority | Labels | Summary |
| --- | --- | --- | --- |
| #241 | P2 | `security`, `reliability`, `roadmap`, `stage-9-deep-audit` | Block and remove web-reachable `experiments/` scripts; add a deny-list and CLI guard. |
| #242 | P2 | `bug`, `security`, `roadmap`, `stage-9-deep-audit` | Derive the rate-limit IP from `REMOTE_ADDR` or a validated trusted-proxy chain. |
| #243 | P2 | `bug`, `security`, `reliability`, `roadmap`, `stage-9-deep-audit` | Make Stars payment idempotency atomic with a unique charge-id and a transaction. |
| #244 | P2 | `bug`, `reliability`, `roadmap`, `stage-9-deep-audit` | Retry transient alert delivery failures instead of advancing the cursor. |
| #245 | P3 | `reliability`, `roadmap`, `stage-9-deep-audit` | Use or remove `last_delivery_fingerprint` and claim due rules atomically. |
| #246 | P3 | `bug`, `reliability`, `roadmap`, `stage-9-deep-audit` | Truncate AI text with `mb_strlen`/`mb_substr` to preserve UTF-8. |
| #247 | P3 | `reliability`, `roadmap`, `stage-9-deep-audit` | Order/roll back admin `.env` and JSON-store writes atomically and lock the RMW. |
| #248 | P3 | `bug`, `reliability`, `roadmap`, `stage-9-deep-audit` | Guarantee a TTL on every rate-limit key even when `EXPIRE` fails. |

---

## 7. Definition of Done

- Every P2 issue has a reproducing test that fails before the fix and passes
  after the fix.
- Every security fix changes the relevant behavior to fail closed or validates
  the trust boundary before using attacker-controlled input.
- Idempotency and delivery guarantees are enforced at the data layer (unique
  constraints, atomic claims) rather than best-effort application code.
- `npm test` and CI pass after the follow-up fixes are merged.

Audit reference: #238. Tracking epic: #240. Prepared PR: #239.
