# Readiness Analysis — TONBANKCARD Crypto Tracker (V2)

Date: 2026-05-29
Scope: Full availability and production-readiness audit of the application as it
stands after the Stage 1–5 roadmap, based on the merged work in closed issues
(#1–#161) and merged pull requests (#2–#162). This document outlines what is
already done, what remains, and the concrete follow-up tasks needed to ship a
finished, professional, search-engine-friendly product — including a sitemap
that exposes every cryptocurrency and related entity to crawlers.

> This audit is the deliverable for issue #163. The actionable follow-ups it
> identifies are tracked as separate, labeled GitHub issues (see
> [Follow-up issues](#follow-up-issues)).

---

## 1. Method

The audit reviewed:

- The closed roadmap issues #3–#40 (Stages 1–5) and the operational/bug-fix
  issues #82–#161, plus their merged pull requests.
- The current source tree: `functions.php`, `config/`, `api/`, `templates/`,
  `views/`, `database/migrations/`, `assets/`, `docs/`, and `tests/`.
- The SEO surface specifically: `functions.php` (sitemap, robots, structured
  data), `config/routes-v2.php`, `views/app-head.php`, and
  `tests/seo-optimization-check.sh`.
- The release process: `.github/workflows/ci.yml`, `package.json` test matrix,
  and `docs/release-checklist.md`.

Findings below cite concrete code locations so each gap is verifiable.

---

## 2. What is already done (state of the product)

The project is a mature, well-structured PHP 8.1+ application with a
Vue/Alpine front end, a strong test matrix (38 reproducible checks in
`package.json`), CI on every PR, and a five-stage roadmap that is fully closed.

| Area | Status | Evidence |
| --- | --- | --- |
| Product requirements & architecture | Done | Issues #4, #9; `docs/adr/0001-v2-migration-architecture.md` |
| Runtime config & environment strategy | Done | Issue #5; `config/runtime.php`, `.env.example` |
| Local dev workflow + CI | Done | Issue #6; `.github/workflows/ci.yml`, `package.json` |
| Legal NOTICE & license inventory | Done | Issues #3, #7; `NOTICE`, `docs/legal-license-inventory.md` |
| MySQL/MariaDB schema & migrations | Done | Issue #11; `database/migrations/0001…0010` |
| PHP API routing layer | Done | Issue #12; `api/router.php` |
| Telegram initData validation & sessions | Done | Issue #13; `api/` telegram-session |
| CoinGecko market data gateway | Done | Issue #14; `api/market.php` |
| Upstash Redis cache & rate limiting | Done | Issue #15 |
| Smart search API + index refresh | Done | Issues #16, #147 |
| AI provider foundation (Groq) + insights + sentiment | Done | Issues #17, #27, #28 |
| Observability & operational logging | Done | Issue #18; `api/observability.php` |
| Responsive design system & Telegram theme | Done | Issue #19 |
| Public website shell, routes, SEO metadata | Done | Issues #20, #157, #158 |
| Market Pulse homepage | Done | Issue #21 |
| Smart search UI + crypto-exchange action | Done | Issue #22 |
| Watchlist UX & persistence | Done | Issue #23 |
| Coin detail + ChangeNOW exchange widget | Done | Issues #24, #90–#155 |
| Charts / advanced visualization | Done | Issue #25 |
| PWA + Telegram WebApp integration | Done | Issue #26; `service-worker.js`, `site.webmanifest` |
| TON ecosystem data + curated views + per-asset pages | Done | Issues #29, #88, #98, #104, #113, #151 |
| TON Connect wallet profile | Done | Issue #30 |
| Advanced screener & analytics filters | Done | Issues #31, #125, #132 |
| Smart Telegram alerts | Done | Issue #32 |
| Shareable market cards + referral deep links | Done | Issue #33 |
| Gamification achievements & streaks | Done | Issue #34 |
| Admin panel (providers, content, flags, ops) | Done | Issue #35; `api/admin.php` |
| Telegram bot commands, inline mode, group flows | Done | Issue #36 |
| Telegram Stars premium subscriptions | Done | Issue #37; `api/premium.php` |
| Security, privacy, compliance hardening | Done | Issue #38; `.htaccess` security headers |
| Performance, load, reliability hardening | Done | Issue #39; `config/performance.php` |
| Launch readiness, store assets, rollout docs | Done | Issue #40; `docs/release-checklist.md` |
| Automatic hosting installer + guide | Done | Issues #82, #84 |
| Multi-language UI (EN/RU/FR/AR/ZH) | Done | Issues #110, #127, #135; `config/translations/*` |
| Yandex Metrica analytics | Done | Issues #141, #153, #159 |
| Telegram Mini App auto-setup from admin | Done | Issue #161 |

**Existing SEO foundation (strong):** dynamic `robots.txt` and `sitemap.xml`
served from `functions.php` (`tonbankcard_public_robots_txt`,
`tonbankcard_public_sitemap_xml`), canonical URLs, Open Graph + Twitter cards,
and rich JSON-LD structured data (`Organization`, `WebSite` with `SearchAction`,
`WebApplication`, `BreadcrumbList`, `FinancialProduct`) in `views/app-head.php`
and `functions.php`. There is an automated SEO regression check
(`tests/seo-optimization-check.sh`).

**Verdict:** the application is feature-complete against its roadmap and is
close to a launch-ready state. The remaining work is *finishing polish* —
primarily SEO discoverability at scale, internationalized crawler signals, and
a handful of operational/quality items.

---

## 3. Gaps and remaining work

### 3.1 SEO — discoverability at scale (highest priority, explicit in #163)

**Finding 1 — the sitemap exposes only three hardcoded coins.**
`config/routes-v2.php` defines the `coins` route with a static
`sitemap_params` list:

```php
'sitemap_params' => [
    [ 'id' => 'bitcoin' ],
    [ 'id' => 'ethereum' ],
    [ 'id' => 'toncoin' ],
],
```

`tonbankcard_public_sitemap_entries()` in `functions.php` only emits parameterized
routes for the params explicitly listed, so `/sitemap.xml` contains just three
coin URLs. The issue explicitly asks the product to "give [search engines] all
the links to cryptocurrencies and the like in the sitemap file." Today it does
not. The CoinGecko market gateway (`api/market.php`,
`/api/market/coins/markets`) already provides the full coin universe, and the
TON catalog and exchanges lists are available, so the data exists — it is simply
not wired into sitemap generation.

**Finding 2 — exchange and TON-asset detail pages are excluded from the sitemap.**
`exchange/:id` is marked `'sitemap' => FALSE` and TON asset detail pages are not
enumerated, so entire indexable sections are invisible to crawlers.

**Finding 3 — no internationalized crawler signals (hreflang).**
The UI supports five languages (`config/translations/{en,ru,fr,ar,zh}.php`), but
`views/app-head.php` emits only the current language plus `x-default`:

```php
<link rel="alternate" hreflang="<?php echo … $site['lang'] …; ?>" … />
<link rel="alternate" hreflang="x-default" … />
```

There are no per-language `hreflang` alternates in either the page head or the
sitemap (`xhtml:link` entries). Search engines therefore cannot discover or
correctly serve the localized variants.

**Finding 4 — no sitemap index, caching, or data-derived `lastmod` for large URL sets.**
A complete coin sitemap will exceed the 50,000-URL / 50 MB per-file limit and
should be split behind a `sitemap_index.xml`. The current generator builds the
XML on every request with no caching/ETag, and `lastmod` is derived from file
mtimes rather than live market data freshness.

### 3.2 Quality & accessibility

**Finding 5 — no accessibility (a11y) audit or automated check.** There is no
WCAG/axe-style check in the `tests/` matrix. A professional product should have
keyboard-navigation, contrast, and ARIA coverage verified, at least for the
public pages.

### 3.3 Operations & growth

**Finding 6 — no error monitoring / uptime alerting wired in.** There is rich
internal observability (`api/observability.php`) and a client error-reporting
flag (`TONBANKCARD_CLIENT_ERROR_REPORTING`), but no external uptime monitor or
error-aggregation integration is documented for production.

**Finding 7 — no CHANGELOG / release versioning trigger.** `package.json` has no
`version` field and there is no `CHANGELOG.md`; releases rely on the manual
`docs/release-checklist.md`. A finished product benefits from a tracked version
and changelog.

---

## 4. Prioritized recommendations

1. **P0 — Complete, dynamic sitemap of every cryptocurrency, exchange, and TON
   asset** (directly satisfies #163). Wire the market gateway + TON catalog +
   exchanges into sitemap generation; add a sitemap index and caching.
2. **P0 — hreflang alternates** in the page head and sitemap for all five
   supported languages.
3. **P1 — Sitemap index + caching + data-derived `lastmod`** for scale and
   freshness.
4. **P1 — Automated test** asserting the sitemap covers the full coin universe
   and validates against the sitemaps.org schema.
5. **P2 — Accessibility audit + automated a11y check** for public pages.
6. **P2 — Production error-monitoring / uptime integration** documented and
   flag-driven.
7. **P3 — `CHANGELOG.md` + `version` field** to formalize releases.

---

## Follow-up issues

The recommendations above are filed as individual, labeled GitHub issues so each
can be scheduled and tracked independently. They are grouped under the
`stage-6-readiness` label with `roadmap` + `enhancement`, mirroring the existing
staged-roadmap convention.

| # | Priority | Issue |
| --- | --- | --- |
| #165 | — | [Stage 6] Launch-readiness and SEO-completeness audit follow-ups (tracking epic) |
| #166 | P0 | [SEO] Generate a complete sitemap covering every cryptocurrency, exchange, and TON asset |
| #167 | P0 | [SEO] Emit hreflang alternates for all supported languages in head and sitemap |
| #168 | P1 | [SEO] Add a sitemap index, caching/ETag, and data-derived lastmod |
| #169 | P1 | [SEO] Add an automated test asserting full sitemap coverage and schema validity |
| #170 | P2 | [Quality] Accessibility (a11y) audit and automated check for public pages |
| #171 | P2 | [Ops] Production error-monitoring and uptime alerting integration |
| #172 | P3 | [Release] Add CHANGELOG.md and a package version trigger |

---

## 5. Resolution status (Stage 6 follow-ups)

All findings above were addressed under the tracking epic #165. Status as of the
Stage 6 follow-up pull request:

| Finding | Issue | Status | Resolution |
| --- | --- | --- | --- |
| 1 — sitemap exposes only three hardcoded coins | #166 | Resolved | `tonbankcard_public_sitemap_entries()` now derives the full coin universe from the market gateway with a bundled fallback. |
| 2 — exchanges and TON assets excluded | #166 | Resolved | Exchange and TON-asset detail pages are enumerated into the sitemap. |
| 3 — no hreflang alternates | #167 | Resolved | Per-language `hreflang` alternates (en, ru, fr, ar, zh) plus `x-default` in `views/app-head.php` and as sitemap `xhtml:link` entries. |
| 4 — no sitemap index / caching / data-derived lastmod | #168 | Resolved | `sitemap_index.xml` with paginated sections, `robots.txt` advertising the index, Upstash caching with `ETag`/`Last-Modified`/`304`, and data-derived `lastmod`. |
| 4 (test) — coverage regression risk | #169 | Resolved | `tests/sitemap-coverage-check.sh` asserts full coverage and sitemaps.org schema validity, wired into `npm test` and CI. |
| 5 — no accessibility audit | #170 | Resolved (with deferrals) | `tests/accessibility-check.js` runs axe-core via Playwright against key public routes in `npm test` and CI; critical/serious violations fail the build. See deferrals below. |
| 6 — no error monitoring / uptime alerting | #171 | Resolved | Flag-driven, default-off error-aggregation forwarding in the observability layer plus a documented uptime/alert-routing matrix in `docs/release-checklist.md`. |
| 7 — no CHANGELOG / version trigger | #172 | Resolved | `package.json` carries `version` `2.0.0`, `CHANGELOG.md` follows Keep a Changelog, and SemVer is documented as a release gate. |

### Consciously deferred accessibility items (Finding 5)

The automated a11y check fails on critical and serious axe-core violations, with a
documented allowlist of two design-token-level issues deferred to a dedicated
design pass rather than blocking the Stage 6 SEO/operations work:

- **`color-contrast`** — white text on the brand primary in the app bar resolves
  to roughly a 3.4:1 ratio, below the WCAG AA 4.5:1 target. Raising it requires
  revising the brand color in `config/vuetify.php`, a design-system decision with
  visual-regression impact, so it is deferred rather than changed unilaterally.
- **`link-in-text-block`** — inline links that rely on color alone share the same
  design-token revision.

Both are tracked in the accessibility report (`test-logs/accessibility-report.json`)
as warnings so they remain visible until the design pass resolves them.
