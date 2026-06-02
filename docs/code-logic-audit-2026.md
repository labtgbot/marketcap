# Code-Logic & Security Audit — TONBANKCARD Crypto Tracker (V2)

Date: 2026-06-01
Scope: Full review of the application's logic, correctness, and security posture
after the Stage 1–6 roadmap. This audit goes deeper into the *code itself* than
the Stage 6 readiness review ([`docs/readiness-analysis-2026.md`](readiness-analysis-2026.md))
— it hunts for concrete bugs, logic flaws, and exploitable vulnerabilities so the
team can fix them step by step.

> This document is the deliverable for issue **#181**. Every actionable finding
> below is tracked as a separate, labeled GitHub issue (see
> [Follow-up issues](#follow-up-issues)). The tracking epic is **#183**.

---

## 1. Method

The review covered the whole source tree, divided across five parallel deep
reviews, each verifying findings against the real code paths (and, where
possible, by executing the relevant code on PHP 8.3.30):

- **API surface** — `api/*.php`, `api/router.php`, auth/CSRF/rate-limit helpers.
- **Core library & config** — `functions.php` (~2.5k lines), `config/*`,
  `constants.php`, `index.php`.
- **Data layer & installer** — `database/migrate.php`, `database/migrations/*`,
  `install/*`.
- **Client side** — `dev/js/src/**`, `templates/**`, `views/**`,
  `service-worker.js`.
- **Infrastructure & CI** — `.htaccess`, `.github/workflows/*`, `.env.example`,
  packaging.

Every finding cites a concrete `file:line` location so it is independently
verifiable. Findings that could not be fully confirmed from code alone are
marked **UNCONFIRMED** with the check needed to resolve them.

Severity uses a standard scale: **Critical** (remote, unauthenticated, high
impact) → **High** → **Medium** → **Low**. Priority for scheduling maps roughly
as Critical→P0, High→P1, Medium→P2, Low→P3.

---

## 2. Findings summary

| # | Severity | Area | Title | Location | Issue |
| --- | --- | --- | --- | --- | --- |
| F1 | Critical | API/Security | Telegram bot webhook fails open when secret is unset | `api/telegram-bot.php:151` | #184 |
| F2 | Critical | API/Security | Premium entitlement self-grant via forged `successful_payment` | `api/premium.php:1027` | #185 |
| F3 | Critical | Web/Security | Reflected XSS in JSON-LD via `JSON_UNESCAPED_SLASHES` + unsanitized slug | `views/app-head.php:256` | #186 |
| F4 | High | API/Security | Worker endpoints fail open when token unset (`/api/alerts/evaluate`) | `api/alerts.php:648` | #187 |
| F5 | High | Web/Security | Open redirect + missing same-origin check on locale-set endpoint | `functions.php:2044` | #188 |
| F6 | High | Installer/Security | `.env` writer allows env-var injection via unescaped newlines | `install/includes/installer.php:920` | #189 |
| F7 | High | Installer/Security | Installer unauthenticated on first run, not web-blocked, no persistent token | `install/includes/installer.php:1152` | #190 |
| F8 | High | Infra/Security | Sensitive files served over HTTP (schema, docs, install/, 8 MB zip) | `.htaccess:14` | #191 |
| F9 | High | Infra/Security | No HTTPS enforcement and no HSTS header | `.htaccess:1` | #192 |
| F10 | High | Data/Reliability | Migrations non-transactional & non-idempotent → wedged half-applied state | `database/migrate.php:236` | #193 |
| F11 | Medium | API/Security | Rate limiter fails open and is bypassable via User-Agent rotation | `api/cache.php:548` | #194 |
| F12 | Medium | API/Security | Anonymous, unbounded AI insight endpoint (provider rate limit not enforced) | `api/ai.php:64` | #195 |
| F13 | Medium | Web/Security | CSP allows `'unsafe-inline'`/`'unsafe-eval'`; missing `X-Frame-Options` | `functions.php:136` | #196 |
| F14 | Medium | Config/Security | Default secret/state stores in world-readable temp dir | `config/runtime.php` | #197 |
| F15 | Medium | Data/Security | DB connections never request TLS | `database/migrate.php:114` | #198 |
| F16 | Medium | Web/Security | `validURLString` accepts `javascript:`/`data:` schemes → `:href` sink | `dev/js/src/initial.js:12` | #199 |
| F17 | Medium | Config/Security | `debug`/`display_errors` fails open to ON when profile unset | `config/runtime.php:451` | #200 |
| F18 | Low | Core/Bug | Fatal `implode()` arg-order + malformed `:to` in `to_attr()`/`link_attrs()` | `functions.php:2112` | #201 |
| F19 | Low | Data/Reliability | Destructive/irreversible down migrations; unguarded `DROP` | `database/migrations/0007_smart_alerts.down.sql` | #202 |
| F20 | Low | Infra/Maintainability | 8 MB `gecko-client.zip` committed to the repo | `gecko-client.zip` | #203 |
| F21 | Low | CI/Security | Third-party GitHub Action pinned to a mutable tag | `.github/workflows/ci.yml:22` | #204 |

---

## 3. Detailed findings

### F1 — Telegram bot webhook fails open when secret is unset (Critical)

`api/telegram-bot.php:151` returns `TRUE` (accept) when no webhook secret is
configured:

```php
$configured = trim( (string) $settings['webhook_secret'] );
if ( '' === $configured ) {
    return TRUE; // no X-Telegram-Bot-Api-Secret-Token required
}
```

`TONBANKCARD_BOT_WEBHOOK_SECRET` defaults to `''` (`config/runtime.php:367`), so
out of the box `/api/telegram/bot` accepts **any** unauthenticated POST. The
`hash_equals` check only runs once a secret is set. This is the entry point that
makes F2 exploitable and lets an attacker spoof arbitrary bot-driven side
effects.

**Fix:** fail closed — when no secret is configured, reject the request; document
the secret as mandatory for webhook deployments.

**Status:** Resolved (#184). `tonbankcard_api_telegram_bot_secret_allowed` now
returns `FALSE` when no secret is configured, and the handler rejects an
unconfigured webhook with `503 telegram_bot_secret_unconfigured` before any
update is processed. A configured secret is still validated with `hash_equals`
(`401 telegram_bot_unauthorized` on mismatch).

### F2 — Premium entitlement self-grant via forged payment webhook (Critical)

`api/premium.php:1027` grants the premium entitlement from a `successful_payment`
webhook based only on a valid HMAC-signed `invoice_payload`, `currency === 'XTR'`,
a matching amount, and `from.id` equal to the payload's `tg_user_id`. There is no
verification that Telegram actually charged the user (no
`telegram_payment_charge_id` reconciliation), no replay protection, and the
signing secret falls back to the bot token (`api/premium.php:936`):

```php
$secret = trim( (string) ( $settings['premium_signing_secret'] ?? '' ) );
if ( '' === $secret ) {
    $secret = trim( (string) ( $settings['bot_token'] ?? '' ) );
}
```

Combined with F1, the attack is: obtain a legitimately signed `invoice_payload`
for your own account via `/api/premium/checkout`, then POST a forged
`successful_payment` echoing it to the unauthenticated webhook. Premium is
granted with no Stars spent.

**Fix:** close F1; treat the webhook as untrusted — de-duplicate and reconcile on
`telegram_payment_charge_id`, require a dedicated `premium_signing_secret` (no
bot-token fallback), and add replay protection so a payload/charge is redeemable
once.

### F3 — Reflected XSS in JSON-LD via `JSON_UNESCAPED_SLASHES` (Critical)

`views/app-head.php:256` emits server-rendered structured data with slash-escaping
explicitly disabled:

```php
<script type="application/ld+json"><?php echo json_encode( $linked_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>
```

For `/coins/:id` and `/currency/:id`, `$linked_data` includes
`tonbankcard_slug_title($params['id'])`, where `:id` is captured by `([^/]+)` and
`rawurldecode`d, and `tonbankcard_slug_title()` only splits on `-`/`_` and
`ucwords()`s — it never strips `<`, `>`, `/`. Normally `json_encode` escapes `/`
to `\/` (so `</script>` is neutralized), but `JSON_UNESCAPED_SLASHES` disables
exactly that protection. Verified end-to-end:

```
GET /coins/%3C%2Fscript%3E%3Cscript%3Ealert(document.domain)%3C%2Fscript%3E
→ {"name":"</script><script>alert(document.domain)</script>", ...}
```

The CSP includes `'unsafe-inline'` (F13), so the injected inline script executes.
One-click reflected XSS in the app origin → session/CSRF-token theft, actions as
the victim.

**Fix:** drop `JSON_UNESCAPED_SLASHES` for in-`<script>` output and add
`JSON_HEX_TAG` (ideally `JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT`); also sanitize
`tonbankcard_slug_title()` to strip non-`[A-Za-z0-9 ]`.

### F4 — Worker endpoints fail open when token unset (High)

`api/alerts.php:648` only enforces the worker token when one is configured:

```php
if ( '' !== $worker_token ) {
    // hash_equals check, 401 on mismatch
}
// else: proceeds with no authentication
```

`TONBANKCARD_ALERT_WORKER_TOKEN` defaults to empty and the route is CSRF-exempt
(`api/router.php:1916`), so `/api/alerts/evaluate` runs unauthenticated by
default. An attacker can repeatedly trigger alert evaluation → forced Telegram
notification sends and upstream market fetches (spam + cost amplification). The
same fail-open pattern should be reviewed for every worker/automation token.

**Fix:** fail closed — reject (`401`/`503`) when the token is unset; require it for
the route to function.

### F5 — Open redirect + missing same-origin check on locale-set (High)

`functions.php:2044` (`tonbankcard_safe_redirect_path`) only rejects `//` for the
relative-path fast path, not a backslash:

```php
if ( '/' === $target[0] && ( strlen( $target ) < 2 || '/' !== $target[1] ) ) {
    return $target; // "/\evil.com" passes
}
```

`/\evil.com` is returned verbatim and emitted as `Location:` at
`functions.php:2025`; browsers normalize `\` to `/`, treating it as the
protocol-relative `//evil.com` → redirect to an arbitrary external host.
Separately, the locale-set docstring (`functions.php:1990`) claims a same-origin
`Referer` check that the code never performs, and the endpoint mutates the
`tbc_lang` cookie via `GET` with no CSRF token.

**Fix:** reject backslashes (and any control characters) in the relative-path
branch; implement the documented `Origin`/`Referer` same-origin check (or correct
the docstring and rely on a hardened redirect guard).

### F6 — `.env` writer allows env-var injection via newlines (High)

`install/includes/installer.php:920` (`tonbankcard_installer_format_env_value`)
quotes values but only escapes `\` and `"`:

```php
return '"' . str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], $value ) . '"';
```

Submitted values are only `trim()`-ed (line 850), so internal newlines survive.
Because the dotenv parser (`config/runtime.php:28`) is line-based, a value
containing a newline followed by `KEY=VALUE` injects an arbitrary additional
environment variable — e.g. overriding the admin token or DSN — into the written
`.env`. Config injection escalating to full compromise.

**Fix:** reject (or strip) any value containing `\r`/`\n` before writing.

### F7 — Installer unauthenticated on first run / not web-blocked (High)

`install/includes/installer.php:1152` returns `allowed => TRUE` with **no token
check** whenever `.env` is absent. There is no `install/.htaccess` and the root
`.htaccess` does not deny `/install/`, so on a fresh deploy any visitor reaching
`/install/` before `.env` exists can write an attacker-controlled `.env` (DSN,
admin tokens), run migrations against an attacker DB, and read reflected DB error
details (`install/includes/installer.php:1299`). The installer also never
generates a persistent `TONBANKCARD_INSTALLER_TOKEN` (it ships empty in
`.env.example`), so the post-install lock rests entirely on a single boolean, and
the token-generation fallback uses weak entropy (`uniqid()` + `mt_rand()`,
`install/includes/installer.php:825`).

**Fix:** require an out-of-band secret even on first run; ship `install/.htaccess`
denying access by default; generate and persist a strong installer token on first
write; show generic DB-error messages in the UI (log details server-side); remove
the weak-entropy token fallback (fail closed instead).

### F8 — Sensitive files served over HTTP (High)

`.htaccess:14` blocks only dotfiles, and the front-controller rewrite only routes
**non-existent** paths to `index.php`. Every real non-dotfile path is served
directly: `https://host/gecko-client.zip` (8.1 MB bundle),
`https://host/database/migrations/0001_v2_core_schema.up.sql` (full schema),
`https://host/database/migrate.php`, and the entire `docs/` tree (including
`docs/v2-security-privacy-compliance.md`). No nested `.htaccess` exists in
`install/`, `database/`, or `docs/`.

**Fix:** deny `install/`, `database/`, `docs/`, `tests/`, `dev/`, and
`*.zip`/`*.sql`/`*.md` at the web layer (or move them outside the web root);
remove `gecko-client.zip` from the deployed tree.

### F9 — No HTTPS enforcement and no HSTS (High)

The HTTPS-forcing rewrite ships commented out (`.htaccess:1`), and neither
`.htaccess` nor `tonbankcard_security_headers()` (`functions.php:109`) emits
`Strict-Transport-Security`. The session cookie only gets `Secure` when the
active URL is already HTTPS (`api/router.php:1788`), so a plain-HTTP deploy sends
the session cookie in cleartext (SSL-strip / MITM).

**Fix:** enable HTTPS redirect by default (or via env) and add an HSTS header once
HTTPS is confirmed.

### F10 — Migrations non-transactional & non-idempotent (High)

`database/migrate.php:236` applies each migration statement-by-statement with no
surrounding transaction, recording the `schema_migrations` ledger row only after
all statements succeed. Since MySQL DDL auto-commits, a mid-file failure leaves
earlier statements applied but the migration unrecorded → considered "pending".
Re-running then fails on non-idempotent `ADD COLUMN`/`ADD KEY` (e.g. `0002`,
`0007`, `0010`) with "Duplicate column/key", permanently wedging the runner. There
is also no advisory lock, so concurrent runners (CLI + installer button) race and
double-apply (`database/migrate.php:236`, `install/includes/installer.php:1429`),
and the statement splitter (`database/migrate.php:160`) is not SQL-aware (splits
on `;`+EOL, mis-parsing semicolons inside string literals — latent today).

**Fix:** make up-migrations idempotent (existence-guarded DDL), acquire a MySQL
advisory lock (`GET_LOCK`) for the run, and replace the naive splitter with a
SQL-aware one.

### F11 — Rate limiter fails open and is UA-bypassable (Medium)

`api/cache.php` fails open when Upstash Redis is unreachable (`api/cache.php:602`)
and keys the anonymous identity on the client-controlled `User-Agent`
(`api/cache.php:548`): `hash('sha256','anonymous|'.$ip.'|'.$user_agent)`. Rotating
the UA mints a fresh bucket per request; a Redis outage disables limiting
globally. Enables brute-force/scraping/cost amplification on anonymous endpoints.

**Fix:** key anonymous identity on IP (optionally a coarse network prefix), not UA;
choose fail-closed or a bounded in-process fallback for Redis outages.

### F12 — Anonymous, unbounded AI insight endpoint (Medium)

`api/ai.php:64` exposes `POST /api/ai/insight` anonymously, proxying to a paid,
server-keyed Groq provider. The provider's advertised `rate_limit` is **not
enforced in code** — only the (fail-open, UA-bypassable) global limiter and a
response cache apply, and the cache is bypassed by varying the prompt. Cost
amplification / provider quota exhaustion.

**Fix:** enforce the provider's `rate_limit` as a dedicated server-side bucket on a
robust identity; bound request body size; consider requiring a validated session.

### F13 — Weak CSP and missing `X-Frame-Options` (Medium)

`functions.php:136` sets `script-src 'self' 'unsafe-inline' 'unsafe-eval' …` (and
`style-src 'unsafe-inline'`). With `'unsafe-inline'`, CSP gives essentially no XSS
protection (it is what makes F3 executable); `'unsafe-eval'` permits `eval`
gadgets. Clickjacking protection relies solely on CSP `frame-ancestors` — there is
no `X-Frame-Options` fallback header.

**Fix:** migrate inline scripts to nonce/hash allowlisting and drop
`'unsafe-inline'`; use the Vue runtime build to drop `'unsafe-eval'`; add
`X-Frame-Options: SAMEORIGIN`.

### F14 — Default secret/state stores in world-readable temp dir (Medium)

When the relevant env vars are unset, admin config, session, AI-feedback, and TON
curation state persist as JSON under `sys_get_temp_dir()`
(`config/runtime.php`, `config/api.php`). On shared hosts `/tmp` is
world-readable/traversable with predictable names → other local users can read or
tamper with sensitive operational state (and pre-create/symlink-race the files).

**Fix:** default these stores to a private, app-owned directory
(`0700`/`0600`) outside the web root; fail closed if it is not private/writable.

### F15 — DB connections never request TLS (Medium)

Every `new PDO(...)` (`database/migrate.php:114`,
`install/includes/installer.php:1272`, `api/router.php:1516`, `:2165`) sets only
error mode / fetch mode — no `PDO::MYSQL_ATTR_SSL_*`, and the installer DSN
builder omits SSL. For a remote/managed DB the handshake auth and all PII travel
in cleartext unless the server forces TLS.

**Fix:** expose SSL options in the installer and set `MYSQL_ATTR_SSL_CA` /
`MYSQL_ATTR_SSL_VERIFY_SERVER_CERT` for non-local profiles.

### F16 — `validURLString` accepts dangerous schemes (Medium)

`dev/js/src/initial.js:12` validates URLs with `new URL(...)` only and applies no
scheme allow-list, so `javascript:`/`data:` pass through. The result is bound to
`:href` on links rendered from proxied provider data (`dev/js/src/coingecko.js`,
`templates/routes/currency.php`, `exchange.php`, `finance-platforms.php`). Vue does
not sanitize `javascript:` in `:href`, so a hostile link value executes on click.
Related: `dev/js/src/premium.js:154` assigns a server-provided `invoiceLink` to
`window.location.href` with no scheme check.

**Fix:** restrict `validURLString` to `http(s)` (plus any intentionally allowed
`mailto:`/`tg:`); validate the invoice-link scheme before navigation.

### F17 — `debug`/`display_errors` fails open to ON (Medium)

`config/runtime.php:451` sets `'debug' => env_bool('TONBANKCARD_DEBUG', 'local'===$profile)`
and the shipped default profile is `local` (`.env.example:1`), so an unset/unknown
profile yields `debug = true`, which makes `index.php:31` run
`error_reporting(-1); ini_set('display_errors', 1)` → stack traces / path leakage
on a misconfigured production deploy.

**Fix:** default `debug` to `false` unless explicitly enabled; treat unknown/unset
profile as production for error display.

### F18 — Fatal `implode()` arg-order + malformed `:to` (Low)

`functions.php:2112` and `:2150` call `implode( $params, ',' )` — the pre-PHP-8
swapped argument order, which throws `TypeError` on PHP 8 (verified on 8.3.30).
Additionally, `to_attr()` at `:2150` builds a malformed Vue location
(`{name:'x'},params:{…}` — `params` outside the object braces) and imploads the
raw `$params` array instead of the formatted `$_params` built at lines 2146–2149.
Both param branches are latent today (no current call site passes params) but are
documented as supported and will 500 the request when used.

**Fix:** `sprintf( ':to="{name:\'%s\',params:{%s}}"', esc_attr($route), implode(',', $_params) )`
in `to_attr()`, and `implode( ',', $params )` in `link_attrs()`.

### F19 — Destructive/irreversible down migrations (Low)

`database/migrations/0007_smart_alerts.down.sql` rewrites new `trigger_type`
values to `percent_move` before shrinking the enum — a lossy, unrecoverable
mutation. `0008_share_referral_attribution` replaces a unique key on
`referred_user_id` with one on `(referred_user_id, campaign_id)` where
`campaign_id` is NULLable (NULLs are distinct), so duplicates can accumulate and
the down migration's `ADD UNIQUE KEY` then fails. Several down migrations use bare
`DROP COLUMN`/`DROP KEY` (no `IF EXISTS`), so they cannot cleanly reverse a
partially applied up (compounds F10).

**Fix:** document destructive rollbacks (or refuse when affected rows exist);
de-duplicate before re-adding unique keys; guard `DROP`s with existence checks.

### F20 — 8 MB `gecko-client.zip` committed to the repo (Low)

An 8.1 MB binary archive is tracked at the repo root, bloating clones/CI, unused
by any build script, web-served (F8), and bundling a third-party licensed product
into the repo.

**Fix:** remove from the tree (and history if it must not be public); ship as a
release artifact / Git LFS; add `*.zip` to `.gitignore`.

### F21 — Third-party GitHub Action pinned to a mutable tag (Low)

`.github/workflows/ci.yml:22` uses `shivammathur/setup-php@v2` — a mutable major
tag that a compromised maintainer could repoint to arbitrary code in CI. Blast
radius is limited (`contents: read`, no secrets in the job) but it is an avoidable
supply-chain risk.

**Fix:** pin to a full commit SHA and enable Dependabot for `actions`.

---

## 4. Verified as NOT vulnerable (false-positive ledger)

To keep the follow-ups focused, these were checked and found sound:

- **Telegram initData validation** (`api/router.php:1244`) — HMAC-SHA256 with
  `secret = HMAC(bot_token,"WebAppData")` and constant-time compare. Correct.
- **SQL injection** — watchlist, alerts, screener, premium, share all use PDO
  prepared statements; the one dynamic query (`api/alerts.php:966`) selects
  between two static strings. None found.
- **IDOR** — watchlist/alerts/screener operations are scoped by `user_id`
  (e.g. `… WHERE id = :id AND user_id = :user_id`, `api/screener.php:967`).
- **SSRF** — market-data upstream paths are allow-listed
  (`/^[A-Za-z0-9._-]{1,160}$/`, `api/market.php:169`) and keys stripped.
- **Admin auth** (`api/admin.php:214`) — fails closed (503) when unconfigured,
  constant-time compare, role separation.
- **CORS** (`api/router.php:945`) — Origin reflected only when allow-listed;
  credentials only for allow-listed origins. No wildcard-with-credentials.
- **CSRF token handling** (`dev/js/src/initial.js:152`) — format-validated,
  attached only to same-origin `/api/*` unsafe methods.
- **TON Connect** (`dev/js/src/ton-connect.js`) — validates address format and
  rejects wallet objects containing secret-shaped keys.
- **No DOM XSS sinks** — no `innerHTML`/`v-html`/`insertAdjacentHTML`/`document.write`
  in `dev/js/src/**` or templates; Vue `{{ }}` auto-escapes.
- **`views/app-scripts.php:202`** `json_encode($gecko_client)` (no flags) — default
  escaping neutralizes `</script>`. Safe.
- **Service worker** (`service-worker.js`) — same-origin scope, `/api/` excluded,
  network-first navigations. (One latent caveat tracked as part of F-review only:
  caching auth-aware pages once `/admin` becomes personalized.)

---

## 5. Follow-up issues

Tracking epic: **#183 — [Stage 7] Code-logic & security hardening audit follow-ups**.

Priority legend: **P0** Critical · **P1** High · **P2** Medium · **P3** Low.

- [x] **[P0]** #184 — F1 Telegram bot webhook fails open when secret is unset
- [ ] **[P0]** #185 — F2 Premium entitlement self-grant via forged payment webhook
- [ ] **[P0]** #186 — F3 Reflected XSS in JSON-LD via `JSON_UNESCAPED_SLASHES`
- [ ] **[P1]** #187 — F4 Worker endpoints fail open when token unset
- [ ] **[P1]** #188 — F5 Open redirect + missing same-origin check on locale-set
- [ ] **[P1]** #189 — F6 `.env` writer allows env-var injection via newlines
- [ ] **[P1]** #190 — F7 Installer unauthenticated on first run / not web-blocked
- [ ] **[P1]** #191 — F8 Sensitive files served over HTTP
- [ ] **[P1]** #192 — F9 No HTTPS enforcement and no HSTS
- [ ] **[P1]** #193 — F10 Migrations non-transactional & non-idempotent
- [ ] **[P2]** #194 — F11 Rate limiter fails open and is UA-bypassable
- [ ] **[P2]** #195 — F12 Anonymous, unbounded AI insight endpoint
- [ ] **[P2]** #196 — F13 Weak CSP and missing `X-Frame-Options`
- [ ] **[P2]** #197 — F14 Default secret/state stores in world-readable temp dir
- [ ] **[P2]** #198 — F15 DB connections never request TLS
- [ ] **[P2]** #199 — F16 `validURLString` accepts dangerous schemes
- [ ] **[P2]** #200 — F17 `debug`/`display_errors` fails open to ON
- [ ] **[P3]** #201 — F18 Fatal `implode()` arg-order + malformed `:to`
- [ ] **[P3]** #202 — F19 Destructive/irreversible down migrations
- [ ] **[P3]** #203 — F20 8 MB `gecko-client.zip` committed to the repo
- [ ] **[P3]** #204 — F21 Third-party GitHub Action pinned to a mutable tag

## 6. Definition of done

- All P0/P1 issues resolved with a reproducing test (or documented manual
  verification) and passing CI.
- Each fix changes a fail-open default to fail-closed, or adds the missing
  validation/escaping, exactly as described in its issue.
- Findings in this document are either resolved or consciously deferred with a
  note in the tracking epic.

Audit reference: #181 · Epic: #183
