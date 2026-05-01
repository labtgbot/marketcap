# marketcap

This repository now includes the extracted Gecko Client project from `gecko-client.zip`.

## Local Development

Prerequisites:

- PHP 8.3 or compatible PHP 8 runtime.
- Node.js 20 and npm for local checks and browser smoke tests.

Install JavaScript test dependencies:

```sh
npm install
npx playwright install chromium
```

Start the local PHP server from the repository root:

```sh
TONBANKCARD_PROFILE=local TONBANKCARD_LOCAL_BASE_URL=http://127.0.0.1:8888/ php -S 127.0.0.1:8888 dev/php/router.php
```

Open these browser URLs while the server is running:

- Home: http://127.0.0.1:8888/
- Markets: http://127.0.0.1:8888/markets
- Coin detail: http://127.0.0.1:8888/currency/bitcoin
- Canonical coin detail: http://127.0.0.1:8888/coins/bitcoin
- TON ecosystem: http://127.0.0.1:8888/ton
- Crypto exchange: http://127.0.0.1:8888/crypto-exchange?from=ton&to=usdtton
- Screener: http://127.0.0.1:8888/screener
- Alerts: http://127.0.0.1:8888/alerts
- Admin panel: http://127.0.0.1:8888/admin
- Achievements: http://127.0.0.1:8888/achievements
- Exchanges: http://127.0.0.1:8888/exchanges
- Support: http://127.0.0.1:8888/support
- API health: http://127.0.0.1:8888/api/health
- Search: focus the top search field on the home page and search for a coin or exchange.

Copy `.env.example` to `.env` only when you need local overrides. A fresh
checkout defaults to the `local` profile without requiring secrets.

Optional V2 database setup uses the `MYSQL_DSN`, `MYSQL_USER`, and
`MYSQL_PASSWORD` values from `.env.example`. After creating an empty local MySQL
or MariaDB database, initialize it with:

```sh
php database/migrate.php dry-run
php database/migrate.php up
```

## Local Checks

Run all reproducible checks:

```sh
npm test
```

Individual checks:

```sh
npm run test:workflow
npm run lint:php
npm run test:content
npm run test:architecture
npm run test:analytics
npm run test:database
npm run test:api
npm run test:market-gateway
npm run test:search-api
npm run test:ton-ecosystem
npm run test:cache-rate-limit
npm run test:telegram-session
npm run test:telegram-bot
npm run test:watchlist
npm run test:screener
npm run test:alerts
npm run test:share-referrals
npm run test:achievements
npm run test:public-shell
npm run test:ai-provider
npm run test:ai-sentiment
npm run test:observability
npm run test:design-system
npm run test:pwa-telegram
npm run test:admin-panel
npm run validate:bundle
npm run test:smoke
```

The checks write inspectable output to `test-logs/`. The browser smoke test
starts the PHP server automatically, stubs CoinGecko/search/ChangeNOW network
responses, and verifies the home, coin detail, exchanges, crypto exchange, and
search paths render without browser JavaScript errors.
The public shell check starts the PHP server and verifies server-rendered
metadata, canonical URLs, the install manifest, and the public website route
registry for shareable web routes.

## Troubleshooting

- If deep links such as `/currency/bitcoin` return 404, start PHP with
  `dev/php/router.php`; the default PHP server command does not route Vue
  history-mode URLs.
- If port 8888 is already in use for smoke tests, run
  `SMOKE_PORT=8890 npm run test:smoke`.
- If Playwright reports a missing browser, run `npx playwright install chromium`.
- If generated bundle validation fails after editing `dev/js/src`, rebuild the
  checked-in bundles with `node dev/js/tools/build.js`. Install `uglify-js`
  locally first if the minified bundle also needs to be regenerated.
- If the app renders a configuration error, check `.env` values or temporarily
  move `.env` aside to use the default local profile.

See [docs/improvements-v2-analysis.md](docs/improvements-v2-analysis.md) for the Telegram Mini App improvement analysis from issue #1.

See [docs/v2-implementation-roadmap.md](docs/v2-implementation-roadmap.md) for the 38-task TONBANKCARD V2 implementation backlog across 5 stages.

See [docs/v2-product-requirements.md](docs/v2-product-requirements.md) for the TONBANKCARD V2 product requirements and information architecture from issue #4.

See [docs/v2-search-and-routing-behavior.md](docs/v2-search-and-routing-behavior.md) for the verified V1 smart search and route compatibility baseline that V2 must preserve.

See [docs/adr/0001-v2-migration-architecture.md](docs/adr/0001-v2-migration-architecture.md) for the accepted V2 migration architecture decision covering parallel V2 routes, PHP templates, Alpine.js, Tailwind CDN, Chart.js, routing compatibility, and rollback.

See [docs/legal-license-inventory.md](docs/legal-license-inventory.md) for the current legal notice and license inventory, and [docs/release-checklist.md](docs/release-checklist.md) for the public launch legal review checkpoint.

See [docs/runtime-configuration.md](docs/runtime-configuration.md) and [.env.example](.env.example) for local, staging, production, and Telegram Mini App runtime configuration.

See [docs/v2-analytics-privacy-metrics.md](docs/v2-analytics-privacy-metrics.md) for the analytics event taxonomy, privacy rules, KPI definitions, retention windows, and dashboard requirements from issue #10.

See [docs/v2-database-schema-and-migrations.md](docs/v2-database-schema-and-migrations.md) for the MySQL/MariaDB V2 schema, migration runner conventions, indexes, retention policy, and backup/restore expectations from issue #11.

See [docs/v2-api-routing-layer.md](docs/v2-api-routing-layer.md) for the `/api/*` JSON envelope, health and readiness checks, CORS policy, middleware hooks, and backend test conventions from issue #12.

See [docs/v2-market-data-gateway.md](docs/v2-market-data-gateway.md) for the `/api/market/*` CoinGecko gateway, Demo/Pro key handling, provider error normalization, and freshness metadata from issue #14.

See [docs/v2-smart-search-api.md](docs/v2-smart-search-api.md) for the `/api/search` smart search API, Redis-backed index refresh workflow, deep links, and click analytics from issue #16.

See [docs/v2-ton-ecosystem-curation.md](docs/v2-ton-ecosystem-curation.md) for the `/api/ton/assets` curation model, manual TON asset updates, tag filters, and verification-state UI behavior from issue #29.

See [docs/v2-ton-connect-wallet-profile.md](docs/v2-ton-connect-wallet-profile.md) for the TON Connect manifest, wallet profile route, disconnect behavior, and wallet-aware placeholder coverage from issue #30.

See [docs/v2-cache-rate-limit-coalescing.md](docs/v2-cache-rate-limit-coalescing.md) for the Upstash Redis cache TTLs, stale fallback behavior, request coalescing, rate-limit policies, and metrics from issue #15.

See [docs/v2-telegram-session.md](docs/v2-telegram-session.md) for the `/api/telegram/session` initData validation, session storage, local browser fallback, and regression coverage from issue #13.

See [docs/v2-telegram-bot-companion.md](docs/v2-telegram-bot-companion.md) for Telegram bot commands, inline mode, group launch context, webhook request-id logging, and regression coverage from issue #36.

See [docs/v2-watchlist-ux-persistence.md](docs/v2-watchlist-ux-persistence.md) for the Watchlist UX, localStorage, Telegram CloudStorage, MySQL trusted-session sync, and conflict behavior from issue #23.

See [docs/v2-advanced-screener.md](docs/v2-advanced-screener.md) for the advanced screener filters, backend `/api/screener/*` endpoints, trusted-session saved presets, mobile filter drawer, and CSV export decision from issue #31.

See [docs/v2-smart-alerts.md](docs/v2-smart-alerts.md) for Telegram bot smart alerts, `/api/alerts` rule management, `/api/alerts/evaluate` worker delivery, quiet hours, frequency caps, and Mini App `startapp` deep links from issue #32.

See [docs/v2-shareable-market-cards.md](docs/v2-shareable-market-cards.md) for shareable market cards, Telegram `startapp` payloads, share fallbacks, and referral attribution from issue #33.

See [docs/v2-gamification-achievements.md](docs/v2-gamification-achievements.md) for opt-in achievement badges, streaks, haptics, dismissible prompts, shareable achievement cards, and admin controls from issue #34.

See [docs/v2-charting-and-market-visualization.md](docs/v2-charting-and-market-visualization.md) for the chart rendering decision, advanced coin chart views, lazy ECharts loading, stale state, and accessibility coverage from issue #25.

See [docs/v2-observability-operational-logging.md](docs/v2-observability-operational-logging.md) for request ID tracing, frontend/API/provider error logging, verbose tracing flags, and operational health queries from issue #18.

See [docs/v2-ai-provider-foundation.md](docs/v2-ai-provider-foundation.md) for the configurable Groq-first AI provider layer, structured insight validation, safety rules, and fallback behavior from issue #17.

See [docs/v2-ai-sentiment-ingestion.md](docs/v2-ai-sentiment-ingestion.md) for the deterministic sentiment ingestion and scoring pipeline from issue #27.

See [docs/v2-ai-insight-cards.md](docs/v2-ai-insight-cards.md) for the AI insight card surfaces, safety controls, feedback storage, and regression coverage from issue #28.

See [docs/v2-responsive-design-system.md](docs/v2-responsive-design-system.md) for the responsive TONBANKCARD design tokens, Telegram theme behavior, safe-area rules, accessibility states, and dense desktop/mobile UI guidance from issue #19.

See [docs/v2-pwa-telegram-webapp.md](docs/v2-pwa-telegram-webapp.md) for the PWA install shell, service worker strategy, offline fallback, and Telegram WebApp adapter behavior from issue #26.

See [docs/v2-admin-panel.md](docs/v2-admin-panel.md) for the authenticated provider, feature flag, content, operations, and audit controls from issue #35.
