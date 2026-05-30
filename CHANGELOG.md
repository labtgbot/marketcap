# Changelog

All notable changes to TONBANKCARD MarketCap are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Stage 6 — launch-readiness and SEO-completeness audit follow-ups
(`docs/readiness-analysis-2026.md`).

### Added

- Dynamic sitemap covering the full coin universe plus exchanges and TON assets,
  with live data and a hardcoded fallback, graceful degradation, and
  observability logging (#166). The live coin source now reads the gateway's
  `coins/list` endpoint, enumerating every coin known to the provider instead of
  a single 250-coin market-cap page, with a `tests/sitemap-live-source-check.php`
  regression test wired into `npm test` and CI.
- `hreflang` alternates for every supported language plus `x-default`, emitted
  in the document head and as `xhtml:link` entries in the sitemap, using the
  `?lang=` localized-URL strategy (#167). The language list is derived from the
  translation registry (`config/translations/`), which now discovers dictionary
  files dynamically, so adding a translation updates the switcher, request-time
  resolution, and the SEO signals together.
- Sitemap index with paginated sections, `robots.txt` advertising the index,
  Upstash Redis caching with `ETag`/`Last-Modified`/`304` handling, and
  data-derived `lastmod` values (#168). Pagination, cache TTL, and the
  stale-while-revalidate window are now configurable through the
  `TONBANKCARD_SITEMAP_MAX_URLS`, `TONBANKCARD_SITEMAP_CACHE_TTL`, and
  `TONBANKCARD_SITEMAP_CACHE_STALE_TTL` environment variables (validated on
  boot and documented in `.env.example`), and a deterministic
  `tests/sitemap-index-cache-check.sh` regression test exercises automatic
  pagination, conditional-request revalidation, and the data-derived `lastmod`,
  wired into `npm test` and CI.
- Deterministic sitemap coverage test (`tests/sitemap-coverage-check.sh`) wired
  into `npm test` and CI (#169).
- Automated accessibility audit (`tests/accessibility-check.js`) running axe-core
  through Playwright against key public routes, wired into `npm test` and CI,
  with accessible names added to navigation and progress indicators (#170).
- Flag-driven error-aggregation forwarding in the observability layer
  (Sentry-compatible DSN or plain webhook), disabled by default, with an uptime
  and alert-routing matrix in `docs/release-checklist.md` (#171). A bundled,
  cron-schedulable uptime monitor (`api/uptime-monitor.php`) probes `/api/health`
  and `/api/ready` and pages a Telegram alert channel on failure, reusing the
  existing bot infrastructure; it is disabled by default
  (`TONBANKCARD_UPTIME_MONITOR_ENABLED`) and covered by `tests/observability-check.sh`.
- `CHANGELOG.md` and a documented Semantic Versioning convention for releases
  (#172). A `Release changelog gate` GitHub Action
  (`.github/workflows/release-changelog.yml`) now enforces the gate on `v*`
  release tags: `tests/changelog-check.sh` runs in strict mode and rejects a tag
  unless the changelog already carries a dated `## [<version>]` section matching
  the `package.json` `version`, while per-PR runs still accept in-progress
  `## [Unreleased]` notes.

## [2.0.0] - 2026-05-02

The TONBANKCARD V2 rebuild: a PHP 8.1+ market intelligence platform serving both
a public website and a Telegram Mini App. Delivered across Stages 1–5 of
`docs/v2-implementation-roadmap.md`.

### Added

- **Stage 1 — Foundation, legal, product baseline.** Legal `NOTICE` and license
  audit baseline, V2 product requirements and information architecture, runtime
  configuration and environment strategy, reproducible local development workflow
  and CI checks, TONBANKCARD branding and legal pages, V1 defect fixes with
  baseline regression tests, the migration architecture decision, and the
  analytics, privacy, and success-metrics baseline.
- **Stage 2 — Backend, data, AI provider foundation.** MySQL/MariaDB schema and
  migrations, the PHP 8.1+ API routing layer, Telegram `initData` validation and
  session creation, the CoinGecko market data gateway, Upstash Redis caching with
  rate limiting and request coalescing, the smart search API and index refresh
  workflow, the configurable AI provider foundation (Groq first), and
  observability with operational logging.
- **Stage 3 — Website and Mini App MVP UX.** Responsive design system and
  Telegram theme tokens, the public website shell with SEO metadata and routing,
  the Market Pulse homepage, smart search UI and command palette, watchlist UX
  and persistence, the coin detail page with the dynamic ChangeNOW exchange
  widget, upgraded charts and market visualization, and PWA plus Telegram WebApp
  integration.
- **Stage 4 — Intelligence, TON, social, alerts, gamification.** AI sentiment
  ingestion and scoring, AI insight cards with safety controls, the TON ecosystem
  data model and curated TON market views, TON Connect wallet profile features,
  the advanced screener and analytics filters, smart alerts delivered by the
  Telegram bot, shareable market cards with referral deep links, and gamification
  achievements and streaks.
- **Stage 5 — Admin, monetization, hardening, launch.** The admin panel for
  providers, content, flags, and operations; Telegram bot commands, inline mode,
  and group flows; Telegram Stars premium subscriptions and entitlements;
  security, privacy, and compliance hardening; performance, load, and reliability
  hardening; and launch-readiness assets, rollout, and documentation.

[Unreleased]: https://github.com/labtgbot/marketcap/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/labtgbot/marketcap/releases/tag/v2.0.0
