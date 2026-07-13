# Accessibility, SEO & Code-Logic Audit - Stage 10

Date: 2026-07-13
Scope: Full accessibility, SEO/sitemap, code-logic, security, and reliability
audit for issue **#251**, performed after the Stage 9 hardening epic had been
closed.

> This document is the deliverable for issue **#251** and PR **#252**. Every
> actionable finding below is tracked as a separate GitHub issue under the
> Stage 10 tracking epic **#253**.

Issue #251 asks the team to bring the project to a professional, launch-ready
state: a product that is friendly to search engines, exposes every
cryptocurrency link through the sitemap, is fully accessible, and whose
application logic has been audited end to end. This audit answers that request
with concrete, verifiable findings and a follow-up issue per defect.

---

## 1. Method

The audit reviewed the current tree after the closed Stage 9 work. It first
re-read the previous audits and their follow-up issues so this report would not
duplicate already resolved defects.

Reviewed sources:

- Previous audits: `docs/code-logic-audit-2026-06-12.md` and
  `docs/code-logic-audit-2026-06-13.md`.
- Closed Stage 7 issues: #183 through #204.
- Closed Stage 8 issues: #231 through #236 (PRs #230 and #237).
- Closed Stage 9 issues: #241 through #248 under epic #240.
- SEO metadata, robots, canonical, and hreflang generation in `functions.php`,
  `config/routes-v2.php`, `config/seo-universe.php`, and `views/app-head.php`.
- Sitemap generation and coverage in `functions.php` and the bundled universe.
- Accessibility of the server-rendered shell and templates in `views/*.php` and
  `templates/**`, cross-checked against `tests/accessibility-check.js`.
- Market data gateway caching in `api/market.php`.
- Alert evaluation and delivery in `api/alerts.php`.
- Observability forwarding on the request path in `api/observability.php` and
  `api/router.php`.
- Public TON jetton lookup in `api/ton.php`.
- Telegram Stars payment validation in `api/premium.php`.
- Smart search scoring cost in `api/search.php`.
- Frontend formatting, PWA precache, and bundle validation in `dev/js/src/**`,
  `service-worker.js`, and `tests/generated-bundle-check.js`.

Severity and scheduling:

- **P1**: high-priority security or availability risk.
- **P2**: real SEO, accessibility, or reliability degradation with tangible
  impact on the public product.
- **P3**: reliability, hardening, or quality cleanup that should be tracked but
  is not a launch blocker on its own.

---

## 2. Stage 7-9 Baseline

The three prior code-logic audits found and closed their follow-ups. The issue
tracker and merged PR list show those follow-ups closed:

| Baseline area | Result verified during this audit |
| --- | --- |
| Stage 7: webhook fail-open, payment self-grant, JSON-LD XSS, locale redirect, worker fail-open, web access, HTTPS/HSTS, migrations, rate-limit fail-open, AI limits, CSP, private stores, DB TLS, URL scheme, debug defaults | Closed through #183 through #204. |
| Stage 8: admin `.env` newline injection, global API body cap, pre-auth rate-limit identity, worker query-string secrets, service-worker caching | Closed through #231 through #236 (PRs #230 and #237). |
| Stage 9: experiments/ web exposure, X-Forwarded-For rate-limit spoofing, Stars idempotency atomicity, alert delivery loss & dedup & atomic claim, AI byte-truncation UTF-8, admin `.env`/JSON non-atomic save, rate-limit missing TTL | Closed through #241 through #248 under epic #240. |

This audit found new or adjacent defects that are not duplicates of the closed
Stage 7, Stage 8, or Stage 9 issues. The accessibility and SEO findings are
net-new areas that the prior code-logic-focused audits did not cover.

---

## 3. Findings summary

| ID | Severity | Area | Summary | Issue |
| --- | --- | --- | --- | --- |
| F1 | P2 | SEO | `/admin` indexed: `noindex` route meta never applied | #254 |
| F2 | P3 | SEO | robots.txt `/admin/` does not cover bare `/admin` | #255 |
| F3 | P2 | SEO | hreflang/canonical conflict: localized `?lang=` not self-canonical | #256 |
| F4 | P2 | SEO/Sitemap | Sitemap degrades to ~105 coins instead of all cryptocurrencies | #257 |
| F5 | P2 | Accessibility | No skip-to-content link | #258 |
| F6 | P2 | Accessibility | Icon-only buttons without accessible names | #259 |
| F7 | P3 | Accessibility | Hardcoded English `aria-label`s in the localized shell | #260 |
| F8 | P2 | Reliability | Queued alert accumulation bypasses frequency/daily caps | #261 |
| F9 | P2 | Reliability | Market gateway cache key without allowlist burns provider quota | #262 |
| F10 | P2 | Reliability | Synchronous blocking observability forward on the request path | #263 |
| F11 | P3 | Security | TON jetton metadata unbounded/unsanitized in `/api/ton/lookup` | #264 |
| F12 | P3 | Reliability | Stars payment amount checked against config, not invoice payload | #265 |
| F13 | P3 | Reliability | Search scoring over the full index is a CPU amplifier | #266 |
| F14 | P2 | Frontend | `currencyFormat` renders `$NaN` instead of empty | #267 |
| F15 | P3 | PWA | Service worker precache never matches versioned `?t=` URLs | #268 |
| F16 | P3 | Frontend | Currency converter breaks in comma-decimal locales | #269 |
| F17 | P3 | CI | `validate:bundle` does not check `app.min.js` against source | #270 |

---

## 4. SEO & sitemap findings

### F1 (P2, SEO) - Admin panel is indexable; `noindex` route meta is dead config

The admin routes declare `'robots' => 'noindex,nofollow'` in the
`$routes_v2['admin']` group, but the server-rendered metadata never applies it.
`tonbankcard_public_route_meta()` only iterates `$routes_v2['public']`, so
`/admin` falls through to the default `index,follow` meta with a self-canonical
URL, and `index.php` returns a 200 HTML shell for it.

- `functions.php:521` - `tonbankcard_public_route_for_path()` reads only
  `$GLOBALS['routes_v2']['public']`.
- `functions.php:595` - default `robots` is `index,follow,...` when no public
  route matches.
- `config/routes-v2.php:226` - the admin dashboard lives in the separate `admin`
  group with `noindex,nofollow`.
- `views/app-head.php:42` - renders `<meta name="robots">` from the resolved
  meta.

Fix: resolve route meta across every `routes_v2` group (or explicitly force
`noindex,nofollow` for `/admin` and `/admin/*`). Tracked in **#254**.

### F2 (P3, SEO) - robots.txt `/admin/` rule does not cover bare `/admin`

`Disallow: /admin/` only matches paths that start with `/admin/`; the bare
`/admin` dashboard path is not covered. Combined with F1 this is a direct route
to crawling and indexing the admin panel.

- `functions.php:1604` - `'Disallow: /admin/'` (trailing slash).
- `config/routes-v2.php:226` - the dashboard is at `/admin` with no trailing
  slash.
- `tests/seo-optimization-check.sh:147` - the test pins the incorrect rule.

Fix: use `Disallow: /admin` (no trailing slash). Tracked in **#255**.

### F3 (P2, SEO) - hreflang/canonical conflict: localized `?lang=` targets are not self-canonical

hreflang alternates for non-default languages are built as `...?lang=ru`, but
the canonical link on such a page always points at the bare URL without the
language parameter. Search engines ignore an hreflang cluster whose alternates
are not themselves canonical, so every localized signal (in the head and in the
sitemap) is discarded.

- `functions.php:918` - `tonbankcard_seo_localized_url()` builds `...?lang=<code>`.
- `functions.php:942` - those URLs feed the head and sitemap hreflang alternates.
- `functions.php:580` - `canonical_url` is always built without a language
  parameter.
- `views/app-head.php:43` - `/coins/bitcoin?lang=ru` still emits a bare
  canonical, and `views/app-head.php:56` hardcodes `og:locale` to `en_US`.

Fix: make localized URLs self-canonical (canonical on `?lang=xx` equals the
`?lang=xx` URL) or move to language path segments, and emit the active
`og:locale` plus `og:locale:alternate`. Tracked in **#256**.

### F4 (P2, SEO/Sitemap) - Sitemap degrades to ~105 coins instead of all cryptocurrencies

Issue #251 requires the sitemap to expose links to every cryptocurrency. By
default (the `local` profile with `TONBANKCARD_SITEMAP_LIVE_SOURCES=false`) the
sitemap silently emits only the curated bundled universe (~105 coins, ~27
exchanges) instead of the full CoinGecko catalog. The threshold that enables the
full catalog is hidden behind two options whose interaction is contradictory and
untested.

- `functions.php:1081` - coin ids come from live `coins/list` layered over the
  bundled universe; without live the bundled set remains.
- `config/seo-universe.php` - the bundled universe is ~105 coins / ~27
  exchanges, not the full catalog.
- `functions.php:1015` - `tonbankcard_sitemap_live_ids()` returns `[]`
  unconditionally on the `local` profile (before the flag check) and when
  `TONBANKCARD_SITEMAP_LIVE_SOURCES` is not true.
- `.env.example` defaults `TONBANKCARD_PROFILE=local` and
  `TONBANKCARD_SITEMAP_LIVE_SOURCES=false`.
- `tests/sitemap-coverage-check.sh` only asserts `>= 50` coins from the bundled
  set on `local`; the live path is never exercised.

Fix: gate the live source on the flag alone (or log a warning when the flag is
true but the profile is `local`), add a regression test that exercises the live
path with a stubbed provider, and document the settings required for full
coverage. Tracked in **#257**.

---

## 5. Accessibility findings

### F5 (P2, Accessibility) - No skip-to-content link

There is no "skip to content" link anywhere in the app, so keyboard and screen
reader users must tab through the whole app bar, navigation, search, and toggles
on every SPA route before reaching the content (WCAG 2.4.1 Bypass Blocks,
level A).

- `views/app.php:32` - `<v-main>` follows the navigation and top bar directly,
  with no bypass link.
- `tests/accessibility-check.js:318` - up to 45 focus stops on the home page.

Fix: add a focus-visible skip link at the start of `v-app` targeting
`<v-main id="main-content" tabindex="-1">`, translated via `__()`. Tracked in
**#258**.

### F6 (P2, Accessibility) - Icon-only buttons without accessible names

Several icon-only buttons have no accessible name (`aria-label`/`title`), so
screen readers announce a bare "button" with no purpose (WCAG 4.1.2, 2.4.4).
These screens are outside the axe audit list, so the regression is not caught.

- `templates/components/cookies-dialog.php:77` - close button, `mdi-close` only.
- `templates/routes/derivatives.php:235` - clear filters, `mdi-filter-off` only.
- `templates/routes/currency.php:295` - copy contract address, `mdi-content-copy`
  only.
- `templates/routes/admin.php:675` and `templates/routes/admin.php:700` -
  add/remove TON asset, icons only.

Fix: add translated `:aria-label`/`title`, and extend the axe/keyboard checks to
at least `/derivatives` and the coin contract card. Tracked in **#259**.

### F7 (P3, Accessibility) - Hardcoded English `aria-label`s in the localized shell

Key shell controls carry hardcoded English `aria-label`s that are not wrapped in
`__()`, even though the interface is fully localized. Non-English screen reader
users hear English labels for core controls.

- `views/app-top-bar.php:27` - `aria-label="Open navigation"`, plus the quote
  currency selector, theme toggle, and install button on the same file.

Fix: wrap the values in `__()` and add the strings to the translation
dictionaries. Tracked in **#260**.

---

## 6. Reliability & security findings

### F8 (P2, Reliability) - Queued alert accumulation bypasses frequency and daily caps

On a retryable delivery failure the status is set to `queued`, but the
fingerprint and `last_triggered_at` are not persisted, so the frequency cap and
dedup never fire, the daily cap does not count queued rows, and the rule
"triggers" every cycle, creating a new queued delivery each time. When the bot
recovers, the retry worker sends all accumulated messages at once, flooding the
user past the caps.

- `api/alerts.php:757` - `mark_evaluated( ..., null, null )` on the queued path.
- `api/alerts.php:1512` - `COALESCE(null, ...)` leaves
  `last_triggered_at`/`last_delivery_fingerprint` unchanged while
  `next_evaluation_at` still advances.
- `api/alerts.php:549` - `frequency_blocked` and dedup depend on those fields.
- `api/alerts.php:1545` - `daily_cap_reached` counts only `delivery_status='sent'`.

Fix: persist a soft trigger marker and fingerprint on enqueue (or count queued
in the frequency/dedup logic), count `sent`+`queued` in the daily cap, and check
the daily cap before sending on the retry path. Tracked in **#261**.

### F9 (P2, Reliability) - Market gateway cache key without allowlist burns provider quota

The market gateway cache key and coalescing lock key are built from the full
normalized query with no allowlist, and `sanitize_query` accepts any key
matching `^[A-Za-z0-9_.-]{1,80}$`. Varying a junk parameter produces a unique
cache key per request, so every request is a cache miss, coalescing never
applies, and each request hits CoinGecko under the server key.

- `api/market.php:265` - `sanitize_query` accepts any key matching the mask.
- `api/market.php:365` - the cache key and lock key are built from the full
  `normalize_cache_query` (`ksort` only, no allowlist).
- `api/market.php:526` - the full query is forwarded to CoinGecko with the
  server key.

Fix: build the cache key and upstream query from a per-route allowlist of
supported CoinGecko parameters, dropping unknown keys. Tracked in **#262**.

### F10 (P2, Reliability) - Synchronous blocking observability forward on the request path

Every response (level `error` on 5xx) and every client error event synchronously
performs a blocking outbound `curl_exec` to the aggregator before the response
returns. With a slow or unreachable DSN the PHP-FPM worker blocks up to
`timeout_ms` (clamped to 15000ms). The "never interrupts request handling"
comment does not hold.

- `api/router.php:821` - `finalize_response` logs every response through
  `observability_log`.
- `api/observability.php:187` - `forward_event` runs synchronously after the
  local write.
- `api/observability.php:426` - blocking `curl_exec` with
  `CURLOPT_TIMEOUT_MS`/`CONNECTTIMEOUT_MS = timeout_ms`.

Fix: move delivery off the request path
(`fastcgi_finish_request`/`register_shutdown_function` plus a local queue) or cap
the timeout aggressively (<=500ms) with a non-blocking transport. Tracked in
**#263**.

### F11 (P3, Security) - TON jetton metadata unbounded and unsanitized in `/api/ton/lookup`

The public `GET /api/ton/lookup` returns `name`, `symbol`, `description`, and
`image`/`image_data` from the tonapi.io response via `trim((string) ...)` with
no length bound and no control/HTML/scheme sanitization, unlike the project's
other string fields which pass through `clean_text`/`slug`.

- `api/ton.php:699` - metadata taken without bounds or sanitization.
- `api/ton.php:722` - returned as-is in the public lookup.

Fix: run `name`/`symbol`/`description` through `clean_text` with explicit
limits, allow only `http(s)://` for `image` (drop `data:`/`javascript:`), and
bound `total_supply`. Tracked in **#264**.

### F12 (P3, Reliability) - Stars payment amount checked against config, not invoice payload

`pre_checkout` and `successful_payment` compare `total_amount` against the price
computed from the current config, not the amount fixed when the invoice was
created. Telegram fixes the Stars subscription price at creation and charges it
on renewal, so an admin price change makes renewals arrive with the old amount,
the check rejects the webhook, and the entitlement silently fails to renew while
the user keeps paying Telegram.

- `api/premium.php:981` - `pre_checkout` compares `total_amount` to the
  config-derived `price_stars`.
- `api/premium.php:1041` - `successful_payment` does the same.
- `api/premium.php:888` - the signed payload carries only
  `plan_code/user_id/telegram_user_id/nonce`; the amount is not embedded.

Fix: embed the expected amount in the signed payload (or persist it in
`premium_payment_events` at invoice creation) and validate `total_amount`
against the fixed amount. Tracked in **#265**.

### F13 (P3, Reliability) - Search scoring over the full index is a CPU amplifier

`GET /api/search` linearly scores every entry of the full CoinGecko index
(~13-17k coins) on every request, running `levenshtein()` for each query token
against each entry token. An unauthenticated request with a long junk `q` is a
cheap CPU amplifier that worsens sharply when Redis is unavailable (full index
rebuild plus upstream on every request).

- `api/search.php:415` - the whole `coins/list` is indexed without a limit.
- `api/search.php:744` - linear scoring of every entry per request.
- `api/search.php:1099` - `levenshtein()` per token; `q` up to 160 chars.

Fix: precompute normalized tokens at index build time, apply levenshtein only to
candidates that pass a cheap prefix/substring filter, and bound the query token
count and indexed catalog size. Tracked in **#266**.

---

## 7. Frontend & PWA findings

### F14 (P2, Frontend) - `currencyFormat` renders `$NaN` instead of empty

`GeckoClient.currencyFormat` uses `if (_.isFinite(value) || formatter)`, but the
`formatter` (Intl.NumberFormat) is always truthy, so the `_.isFinite(value)`
guard is dead. With `value=NaN`/`null` it calls `formatter.format(NaN)` and
renders "$NaN" instead of an empty value.

- `dev/js/src/initial.js:900` - the `||` guard that should be `&&`.
- `assets/js/app.min.js` - the same bug ships in the minified bundle.
- `templates/components/stats-bar.php:127` -
  `marketCapFormat($root.totalMarketCap)` before `global` loads.

Fix: change `||` to `&&` (`if (_.isFinite(value) && formatter)`), return `null`
otherwise, and regenerate the bundles. Tracked in **#267**.

### F15 (P3, PWA) - Service worker precache never matches versioned `?t=` URLs

The service worker precaches bare paths (`/assets/js/app.min.js`,
`/assets/css/style.css`, `/site.webmanifest`), but the page requests those
assets with a version query `?t=<mtime>`. The cache key is the full URL with the
query, so precached entries never match requests; the offline shell is actually
served from the runtime cache of a prior visit, not the precache. Both bundles
(`app.js` and `app.min.js`) are precached even though only one is used.

- `service-worker.js:22` - bare `/assets/js/app.min.js` precache entry.
- `functions.php:1850` - `get_file_url_for_display` appends `?t=<mtime>`.
- `views/app-head.php:97` - assets requested with `?t=`.

Fix: precache the same URLs the page requests (with `?t=`, generated at
template/build time) or move sub-resources to runtime-first, and precache only
the active bundle. Tracked in **#268**.

### F16 (P3, Frontend) - Currency converter breaks in comma-decimal locales

The currency converter formats input locale-aware via
`Intl.NumberFormat(formats.converter.locale)` and then re-reads the value with
`parseFloat`. In comma-decimal locales (fr/ru/ar) `parseFloat("0,5") === 0`, so
the fractional part is lost on the round-trip conversion.

- `dev/js/src/components/currency-converter.js:31` - locale-aware formatter from
  `$site['lang']`.
- `dev/js/src/components/currency-converter.js:60` - `format()` then
  `parseFloat()` of the formatted string.

Fix: keep the numeric model separate from the display string and parse input
with locale-aware decimal normalization instead of `parseFloat`. Tracked in
**#269**.

### F17 (P3, CI) - `validate:bundle` does not check `app.min.js` against source

`validate:bundle` strictly compares `app.js` against the source concatenation
but only checks `app.min.js` for non-emptiness and syntax, never against the
`app.js`/source content. Production serves `app.min.js`, so a stale minified
bundle can ship while `validate:bundle` stays green.

- `tests/generated-bundle-check.js:75` - strict `app.js` comparison.
- `tests/generated-bundle-check.js:95` - `app.min.js` only checked for
  emptiness/syntax.
- `views/app-scripts.php:251` - production serves `app.min.js`.

Fix: minify `app.js` (uglify-js is already a devDependency) and compare against
`app.min.js`, or compare the equivalent string-literal/AST set, and fail on
drift. Tracked in **#270**.

---

## 8. Follow-up issues

All findings are tracked under epic **#253** with the `stage-10-availability-audit`
label:

| Issue | Sev | Labels | Summary |
| --- | --- | --- | --- |
| #254 | P2 | `bug`, `seo`, `roadmap`, `stage-10-availability-audit` | Apply `noindex,nofollow` to `/admin` and every non-public route group. |
| #255 | P3 | `seo`, `roadmap`, `stage-10-availability-audit` | Fix robots.txt so the disallow rule covers the bare `/admin` path. |
| #256 | P2 | `bug`, `seo`, `roadmap`, `stage-10-availability-audit` | Make localized `?lang=` URLs self-canonical and emit the active `og:locale`. |
| #257 | P2 | `seo`, `reliability`, `roadmap`, `stage-10-availability-audit` | Expose every cryptocurrency in the sitemap instead of the bundled ~105. |
| #258 | P2 | `accessibility`, `roadmap`, `stage-10-availability-audit` | Add a keyboard skip-to-content link. |
| #259 | P2 | `accessibility`, `roadmap`, `stage-10-availability-audit` | Give icon-only buttons accessible names and extend the axe checks. |
| #260 | P3 | `accessibility`, `roadmap`, `stage-10-availability-audit` | Localize the hardcoded English `aria-label`s in the shell. |
| #261 | P2 | `bug`, `reliability`, `roadmap`, `stage-10-availability-audit` | Stop queued alerts from bypassing frequency and daily caps. |
| #262 | P2 | `bug`, `reliability`, `roadmap`, `stage-10-availability-audit` | Build the market cache/upstream key from a parameter allowlist. |
| #263 | P2 | `reliability`, `roadmap`, `stage-10-availability-audit` | Move observability forwarding off the blocking request path. |
| #264 | P3 | `security`, `roadmap`, `stage-10-availability-audit` | Bound and sanitize TON jetton metadata in `/api/ton/lookup`. |
| #265 | P3 | `bug`, `reliability`, `roadmap`, `stage-10-availability-audit` | Validate Stars payments against the signed invoice amount. |
| #266 | P3 | `reliability`, `roadmap`, `stage-10-availability-audit` | Bound search scoring cost with candidate filtering. |
| #267 | P2 | `bug`, `roadmap`, `stage-10-availability-audit` | Fix `currencyFormat` so invalid values render empty, not `$NaN`. |
| #268 | P3 | `bug`, `roadmap`, `stage-10-availability-audit` | Precache the versioned asset URLs the pages actually request. |
| #269 | P3 | `bug`, `roadmap`, `stage-10-availability-audit` | Parse currency-converter input with locale-aware decimals. |
| #270 | P3 | `reliability`, `roadmap`, `stage-10-availability-audit` | Validate `app.min.js` against source in `validate:bundle`. |

## 9. Definition of Done

- Each P2 finding ships with a reproducing test (fails before the fix, passes
  after).
- SEO fixes leave the robots/meta/canonical/hreflang signals consistent and are
  covered by `tests/seo-optimization-check.sh`.
- Accessibility fixes are covered by `tests/accessibility-check.js` (skip link,
  accessible button names).
- `npm test` and CI stay green after the follow-up fixes merge.

Source: #251. Tracking epic: #253. Prepared PR: #252.
