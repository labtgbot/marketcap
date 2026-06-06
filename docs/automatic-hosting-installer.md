# TONBANKCARD Automatic Hosting Installer

Date: 2026-05-02

Issue: [#84](https://github.com/labtgbot/marketcap/issues/84)

TONBANKCARD includes a guarded browser installer at `/install/` for standard
PHP 8.1+ hosting with MySQL or MariaDB. The route is denied by
`install/.htaccess` by default and the PHP installer also requires an
out-of-band TONBANKCARD_INSTALLER_TOKEN before `.env` exists. Upload the
repository to the hosting web root, open the installer only during a controlled
setup window, and complete the setup form before sending users to the public
site.

The installer turns the manual plan in
[docs/hosting-installation.md](hosting-installation.md) into an interactive
workflow. It checks the PHP runtime, writes `.env`, tests the database, and can
run database migrations without SSH access.

## Access Model

The installer has two access layers:

1. `install/.htaccess` denies `/install/` on Apache by default. Temporarily
   remove or relax that file only from a trusted network during setup.
2. PHP requires a token even on first run. Before `.env` exists, set an
   out-of-band TONBANKCARD_INSTALLER_TOKEN in the hosting control panel, Apache
   `SetEnv`, PHP-FPM environment, or another server environment mechanism. Then
   open `/install/?token=that-token`.

When the installer writes `.env` for the first time it generates and persists a
strong re-entry token, then saves:

```dotenv
TONBANKCARD_INSTALLER_ENABLED=false
TONBANKCARD_INSTALLER_TOKEN=generated-long-random-token
```

That locks `/install/` so it cannot be reused casually on a live site. To reopen
it later, edit `.env` manually:

```dotenv
TONBANKCARD_INSTALLER_ENABLED=true
TONBANKCARD_INSTALLER_TOKEN=replace-with-a-long-random-token
```

Then open `/install/?token=replace-with-a-long-random-token`. When the installer
writes `.env` again it sets `TONBANKCARD_INSTALLER_ENABLED=false` to lock itself.
After setup, delete the install/ directory from production hosting.

## Language Selection

The installer UI supports English and Russian. Use the language selector in the
top-right corner of `/install/` before filling fields. The selected language is
kept while previewing `.env`, testing the database, running migrations, and
writing configuration. It does not add a language setting to `.env`; it only
changes installer labels, help text, readiness messages, and action buttons.

## Installer Steps

1. Readiness checks: PHP version, required extensions, `.env` write access, and
   HTTPS status.
2. Runtime profile and public URLs: production, staging, local, or Telegram,
   including `TONBANKCARD_PUBLIC_BASE_URL` and
   `TONBANKCARD_TELEGRAM_BASE_URL`.
3. Telegram Mini App and bot parameters: bot username, bot token, webhook
   secret, search refresh token, and alert worker token.
4. MySQL or MariaDB parameters: host, port, database, user, password, generated
   `MYSQL_DSN`, and connection testing.
5. Providers and cache: CoinGecko, Upstash Redis, Groq AI, and ChangeNOW.
6. Feature flags and product limits: AI, alerts, TON Connect, referrals,
   gamification, premium subscriptions, and their related limits.
7. Operations: admin tokens, optional store paths, tracing, readiness probes,
   cache controls, and performance budgets.
8. Final write: preview `.env`, write `.env`, optionally run database
   migrations, and verify the migration list.

Leave optional features disabled for the first production boot. After
`/api/health` and `/api/ready` pass, enable features one at a time and recheck
the affected route.

## Field Filling Reference

Collect these values before opening `/install/`. Values marked optional can stay
empty during the first production boot unless the related feature is enabled.

| Installer field | What to enter | Where to find it |
| --- | --- | --- |
| `TONBANKCARD_PROFILE` | Use `production` for the public website, `telegram` when the Mini App is the primary entry point, `staging` for a test domain, or `local` only on a development machine. | Deployment plan. |
| `TONBANKCARD_PUBLIC_BASE_URL` | The exact HTTPS website URL with trailing slash, for example `https://marketcap.example.com/`. | Hosting domain or CDN control panel. |
| `TONBANKCARD_TELEGRAM_BASE_URL` | The HTTPS URL configured for the Telegram Mini App. Required for the `telegram` profile. | BotFather Mini App settings. |
| `TONBANKCARD_BOT_USERNAME` | Bot username without `@`, for example `tonbankcard_bot`. | BotFather bot profile. |
| `TONBANKCARD_BOT_TOKEN` | Server-side bot token. Required for the `telegram` profile and alerts. | BotFather token screen. Rotate it if it was shared in chat or logs. |
| `TONBANKCARD_BOT_WEBHOOK_SECRET` | Long random secret used to verify Telegram webhook calls. Optional until webhooks are enabled. | Generate with the hosting password tool or a local secret generator. |
| `TONBANKCARD_ALERT_WORKER_TOKEN` | Long random token for the alert evaluation worker. Leave empty to let the installer generate one. | Generate locally or let the installer fill it. |
| `TONBANKCARD_SEARCH_REFRESH_TOKEN` | Long random token for search refresh jobs. Leave empty to let the installer generate one. | Generate locally or let the installer fill it. |
| `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DATABASE`, `MYSQL_CHARSET` | Database connection parts. `MYSQL_CHARSET` should normally be `utf8mb4`; `MYSQL_PORT` is often `3306` or blank on shared hosting. | Hosting MySQL or MariaDB database panel. |
| `MYSQL_DSN` | Full DSN such as `mysql:host=127.0.0.1;dbname=marketcap;charset=utf8mb4`. The installer builds it from helper fields when left empty. | Built by the installer or copied from hosting database docs. |
| `MYSQL_USER` and `MYSQL_PASSWORD` | Database user and password with permissions for the application database. | Hosting MySQL or MariaDB database panel. |
| `MYSQL_SSL_CA` and `MYSQL_SSL_VERIFY_SERVER_CERT` | CA certificate path and verification flag used by `PDO::MYSQL_ATTR_SSL_CA` and `PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT`. Keep verification `true` for non-local installs. | Managed database TLS settings or hosting support docs. |
| `MYSQL_SSL_CERT`, `MYSQL_SSL_KEY`, `MYSQL_SSL_CAPATH`, `MYSQL_SSL_CIPHER` | Optional advanced `PDO::MYSQL_ATTR_SSL_*` paths and cipher list when the database provider requires client certificates or a custom CA directory. | Managed database TLS settings. |
| `COINGECKO_API_PLAN` and `COINGECKO_API_KEY` | Use `demo` without a key for first boot. Use `pro` only when a Pro key is available. | CoinGecko account. |
| `UPSTASH_REDIS_REST_URL` and `UPSTASH_REDIS_REST_TOKEN` | Redis REST endpoint and token. Required for non-local installs because readiness and provider caching expect Redis. | Upstash database details page. |
| `GROQ_API_KEY`, `GROQ_MODEL_ID`, `GROQ_BASE_URL` | AI provider settings. Keep `TONBANKCARD_FEATURE_AI=false` until the Groq key is configured. | Groq console. |
| `CHANGENOW_LINK_ID` | Partner or link identifier for the exchange widget. Required only when `TONBANKCARD_FEATURE_CHANGENOW=true`. | ChangeNOW partner dashboard. |
| `TONBANKCARD_FEATURE_ALERTS` | Set `false` for first boot. Set `true` only after the bot token, alert worker token, migrations, and cron are ready. | Product rollout decision. |
| Other `TONBANKCARD_FEATURE_*` flags | Keep optional features `false` until `/api/health`, `/api/ready`, and the related UI route pass. | Product rollout decision and feature-specific docs linked from `README.md`. |
| Admin, curation, cache, rate-limit, observability, and budget fields | Keep defaults unless the hosting runbook requires a specific path, token, log level, or performance budget. | Operations runbook. |

## Database Migrations

The installer reads `database/migrations/*.up.sql`, creates the
`schema_migrations` ledger when needed, skips applied migrations, and records the
checksum for every migration it applies. This matches the CLI runner behavior:

```sh
php database/migrate.php dry-run
php database/migrate.php up
```

Take a database backup before applying migrations on an existing production
database. On shared hosting, use the provider snapshot tool or phpMyAdmin export
when `mysqldump` is unavailable.

## Security Notes

- Use HTTPS for production and Telegram Mini App deployments.
- Keep `.env` private. The committed `.htaccess` blocks dotfile downloads on
  Apache, but verify `/.env` returns 403 or 404 after upload.
- Do not leave `TONBANKCARD_INSTALLER_ENABLED=true` on a live site.
- Rotate `TONBANKCARD_INSTALLER_TOKEN` after any support session.
- Leave `install/.htaccess` in deny-by-default mode until setup starts, and
  delete the install/ directory after setup.
- Keep `TONBANKCARD_DEBUG=false` outside controlled troubleshooting windows.

## Verification

Run the installer check locally:

```sh
npm run test:automatic-installer
```

After completing a real hosted install, verify:

```sh
curl -fsS https://your-domain.example/api/health
curl -fsS https://your-domain.example/api/ready
```

Open `/markets`, `/currency/bitcoin`, `/ton`, and the Telegram Mini App URL
configured in BotFather.
