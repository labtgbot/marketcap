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
- Coin detail: http://127.0.0.1:8888/currency/bitcoin
- Exchanges: http://127.0.0.1:8888/exchanges
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
npm run validate:bundle
npm run test:smoke
```

The checks write inspectable output to `test-logs/`. The browser smoke test
starts the PHP server automatically, stubs CoinGecko/search network responses,
and verifies the home, coin detail, exchanges, and search paths render without
browser JavaScript errors.

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
