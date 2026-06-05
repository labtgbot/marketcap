# TONBANKCARD Hosting Installation Guide

Date: 2026-05-02

Issue: [#82](https://github.com/labtgbot/marketcap/issues/82)

This guide describes a production installation of TONBANKCARD Crypto Tracker on
standard PHP hosting with PHP 8.1+ and MySQL or MariaDB. It covers shared
hosting, cPanel-style control panels, and VPS hosts where Apache or Nginx points
at the repository root as the public web root.

Use `docs/runtime-configuration.md` for the full environment variable reference
and `docs/v2-database-schema-and-migrations.md` for the migration model.
For guided first-run setup after upload, use the browser installer documented in
`docs/automatic-hosting-installer.md` and available at `/install/`.

## Prerequisites

The host must provide:

| Requirement | Minimum | Notes |
| --- | --- | --- |
| PHP | PHP 8.1+ | PHP 8.3 is used in CI, but the application avoids features that require PHP newer than 8.1. |
| PHP extensions | `pdo_mysql`, `curl`, `json`, `hash`, `openssl`, `filter`, `session` | `pdo_mysql` is required for MySQL or MariaDB persistence. `curl` is preferred for provider, Redis REST, Telegram, and Groq requests; stream fallback exists for several calls. |
| Database | MySQL 5.7+ or MariaDB 10.4+ | Use `utf8mb4` and InnoDB. |
| Web server | Apache 2.4 with `mod_rewrite` and `mod_headers`, or Nginx with equivalent rules | The included `.htaccess` handles Apache rewrites, headers, asset cache policy, and dotfile protection. |
| TLS | Valid HTTPS certificate | Required for production web, Telegram Mini App, service worker, and secure cookies. |
| Shell or panel tasks | SSH, cPanel Terminal, or scheduled tasks | Needed for migrations, cron jobs, and backups. |

Node.js and npm are not required on the hosting server when deploying the
checked-in bundles. They are only needed on a development machine or CI runner
for `npm test`.

## Hosting Layout

The app uses `index.php` as the front controller and serves static assets from
the same repository. There is no separate `public/` directory.

Recommended VPS layout:

```sh
/var/www/marketcap/current/
  index.php
  .htaccess
  assets/
  api/
  config/
  database/
  docs/
  views/
```

Point the domain document root, also called the web root in many hosting
panels, at the repository root. For shared hosting, this is often
`public_html/` or a domain-specific directory such as
`public_html/marketcap.example.com/`.

Keep secrets out of Git. Prefer host-level environment variables in the control
panel, PHP-FPM pool, Apache virtual host, Nginx `fastcgi_param`, or deployment
secret store. If the host only supports a `.env` file, place it in the
repository root and confirm that dotfile requests return 403. The included
Apache `.htaccess` blocks `/.env`, `/.git/*`, and other dotfiles while allowing
`/.well-known/` certificate challenges.

## Step-by-step Installation Plan

### 1. Prepare the domain and PHP runtime

1. Create the domain, subdomain, or addon domain in the hosting panel.
2. Set the document root to the TONBANKCARD repository root.
3. Select PHP 8.1 or newer for the domain.
4. Enable `pdo_mysql`, `curl`, `json`, `hash`, `openssl`, `filter`, and
   `session`.
5. Enable HTTPS and force HTTPS at the edge or web server.
6. Confirm the PHP version from SSH or the hosting terminal:

```sh
php -v
php -m | grep -E 'PDO|pdo_mysql|curl|json|openssl'
```

For Apache, confirm `mod_rewrite` is enabled and `.htaccess` files are honored.
Deep links such as `/markets` and `/currency/bitcoin` depend on rewrite rules.

### 2. Upload or checkout the application

On a VPS with Git:

```sh
cd /var/www
git clone https://github.com/labtgbot/marketcap.git marketcap
cd /var/www/marketcap
git checkout main
```

For a prepared release branch or archive, upload the repository contents to the
domain web root. Include hidden files, especially `.htaccess`. Do not upload a
local `.git/` directory unless the host is private and dotfile protection is
verified.

Set conservative permissions:

```sh
find /var/www/marketcap -type d -exec chmod 755 {} \;
find /var/www/marketcap -type f -exec chmod 644 {} \;
```

The application does not need broad write access to the repository. Set
`TONBANKCARD_STATE_DIR` to an app-owned private directory outside the public web
root when local JSON stores are used. If you override `TONBANKCARD_ADMIN_STORE`,
`TONBANKCARD_TON_CURATION_FILE`, or `TONBANKCARD_AI_FEEDBACK_STORE`, keep those
files in a `0700` directory and `0600` file; do not use shared `/tmp` paths on a
hosted deployment.

### 3. Create the database

Create an empty database and an application user. On a VPS:

```sh
mysql -uroot -p
```

Then run:

```sql
CREATE DATABASE marketcap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'marketcap'@'localhost' IDENTIFIED BY 'replace-with-a-long-password';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
    ON marketcap.* TO 'marketcap'@'localhost';
FLUSH PRIVILEGES;
```

On cPanel or a managed host, create the database and user through the MySQL
Databases tool, grant all privileges to the application database, and copy the
actual database name, username, host, and password. Shared hosts often prefix
names, for example `account_marketcap`.

### 4. Configure environment variables

Copy the example file only when file-based configuration is required:

```sh
cd /var/www/marketcap
cp .env.example .env
chmod 600 .env
```

For production public website hosting, set at least:

```dotenv
TONBANKCARD_PROFILE=production
TONBANKCARD_PUBLIC_BASE_URL=https://marketcap.example.com/
TONBANKCARD_TELEGRAM_BASE_URL=

TONBANKCARD_DEBUG=false
TONBANKCARD_APP_MINIFIED=true
TONBANKCARD_CDN=false
TONBANKCARD_OBSERVABILITY_LOG_LEVEL=warning
TONBANKCARD_VERBOSE_TRACING=false
TONBANKCARD_CLIENT_ERROR_REPORTING=true

TONBANKCARD_BOT_USERNAME=tonbankcard_bot
TONBANKCARD_BOT_TOKEN=
TONBANKCARD_BOT_WEBHOOK_SECRET=
TONBANKCARD_ALERT_WORKER_TOKEN=alerts-worker-token-rotate-me
TONBANKCARD_SEARCH_REFRESH_TOKEN=search-refresh-token-rotate-me

COINGECKO_API_PLAN=demo
COINGECKO_API_KEY=

UPSTASH_REDIS_REST_URL=https://example.upstash.io
UPSTASH_REDIS_REST_TOKEN=replace-with-upstash-token

MYSQL_DSN=mysql:host=127.0.0.1;dbname=marketcap;charset=utf8mb4
MYSQL_USER=marketcap
MYSQL_PASSWORD=replace-with-a-long-password
MYSQL_SSL_CA=/etc/mysql/managed-ca.pem
MYSQL_SSL_VERIFY_SERVER_CERT=true

TONBANKCARD_FEATURE_AI=false
TONBANKCARD_FEATURE_ALERTS=false
TONBANKCARD_FEATURE_WIDGET=false
TONBANKCARD_FEATURE_CHANGENOW=false
TONBANKCARD_FEATURE_TON_CONNECT=false
TONBANKCARD_FEATURE_REFERRALS=false
TONBANKCARD_FEATURE_GAMIFICATION=false
TONBANKCARD_FEATURE_PREMIUM=false
```

Notes:

- `TONBANKCARD_PUBLIC_BASE_URL` must be the exact HTTPS URL users open.
- Non-local profiles require `TONBANKCARD_BOT_USERNAME`,
  `UPSTASH_REDIS_REST_URL`, `UPSTASH_REDIS_REST_TOKEN`, `MYSQL_DSN`,
  `MYSQL_USER`, `MYSQL_PASSWORD`, and explicit feature flags.
- `TONBANKCARD_BOT_TOKEN` is required for the `telegram` profile and when
  alerts are enabled.
- `GROQ_API_KEY` is required only when `TONBANKCARD_FEATURE_AI=true`.
- `CHANGENOW_LINK_ID` is required only when
  `TONBANKCARD_FEATURE_CHANGENOW=true`.
- Keep `TONBANKCARD_DEBUG=false` in production.

For a Telegram Mini App deployment, use `TONBANKCARD_PROFILE=telegram`, set
`TONBANKCARD_TELEGRAM_BASE_URL` to the BotFather Mini App URL, keep
`TONBANKCARD_PUBLIC_BASE_URL` set for shared links, and set
`TONBANKCARD_BOT_TOKEN`.

### 5. Configure web server routing

Apache hosting should use the committed `.htaccess`. Confirm it was uploaded.
It provides:

- HTTPS-ready rewrite support for Vue history-mode routes.
- `mod_rewrite` front-controller routing to `index.php`.
- Static asset cache headers.
- `service-worker.js` no-cache headers.
- Dotfile request protection except `/.well-known/`.

If you use Nginx, configure equivalent rules:

```nginx
server {
    listen 443 ssl http2;
    server_name marketcap.example.com;
    root /var/www/marketcap;
    index index.php;

    location ^~ /.well-known/ {
        allow all;
    }

    location ~ /\.(?!well-known/) {
        deny all;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /service-worker.js {
        add_header Cache-Control "no-cache, no-store, must-revalidate";
        add_header Service-Worker-Allowed "/";
        try_files $uri =404;
    }

    location ~* \.(?:css|js|mjs|png|jpe?g|gif|svg|ico|woff2?|ttf|eot|webmanifest)$ {
        add_header Cache-Control "public, max-age=86400, stale-while-revalidate=604800";
        try_files $uri =404;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param TONBANKCARD_PROFILE production;
        fastcgi_param TONBANKCARD_PUBLIC_BASE_URL https://marketcap.example.com/;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

Replace the PHP-FPM socket and environment values with your host's values.

### 6. Run database migrations

From the repository root:

```sh
php database/migrate.php dry-run
php database/migrate.php up
php database/migrate.php status
```

`dry-run` lists available migrations without opening a database connection.
`up` creates the `schema_migrations` ledger and all pending application tables.
`status` confirms whether each migration is applied.

Before every production migration, take a backup:

```sh
mysqldump --single-transaction --routines --triggers --set-gtid-purged=OFF \
  -u marketcap -p marketcap > marketcap-$(date +%Y%m%d%H%M%S).sql
```

Use the managed database provider's snapshot tool when shell dumps are not
available.

### 7. Verify the installation

Open the public pages:

- `https://marketcap.example.com/`
- `https://marketcap.example.com/markets`
- `https://marketcap.example.com/currency/bitcoin`
- `https://marketcap.example.com/ton`
- `https://marketcap.example.com/support`

Check the API:

```sh
curl -fsS https://marketcap.example.com/api/health
curl -fsS https://marketcap.example.com/api/ready
```

Expected result:

- `/api/health` returns an `ok` status envelope.
- `/api/ready` returns ready status when required environment variables are set.
- Deep links render the app shell instead of 404.
- `/.env` and `/.git/config` return 403 or 404.

If `/api/ready` reports degraded dependencies, fix the named environment
variable, database, Redis, or provider setting before enabling feature flags.

### 8. Configure scheduled jobs

Search index refresh can run from CLI:

```sh
cd /var/www/marketcap && php api/search-refresh.php
```

Example cron entry:

```cron
*/15 * * * * cd /var/www/marketcap && php api/search-refresh.php >/dev/null 2>&1
```

If you expose the HTTP refresh route instead, set
`TONBANKCARD_SEARCH_REFRESH_TOKEN` and call `/api/search/refresh` with
`X-TONBANKCARD-Search-Refresh-Token`.

Alert evaluation runs through the worker endpoint when alerts are enabled:

```sh
curl -fsS -X POST \
  -H "Content-Type: application/json" \
  -H "X-TONBANKCARD-Alert-Worker-Token: alerts-worker-token-rotate-me" \
  -d '{"limit":50}' \
  https://marketcap.example.com/api/alerts/evaluate
```

Example cron entry:

```cron
*/5 * * * * curl -fsS -X POST -H "Content-Type: application/json" -H "X-TONBANKCARD-Alert-Worker-Token: alerts-worker-token-rotate-me" -d '{"limit":50}' https://marketcap.example.com/api/alerts/evaluate >/dev/null 2>&1
```

Use the same secret value as `TONBANKCARD_ALERT_WORKER_TOKEN`. Rotate it when
cron access changes.

### 9. Enable optional features deliberately

Start with all feature flags set to `false`, verify the public site, then enable
features one at a time:

| Feature flag | Also configure |
| --- | --- |
| `TONBANKCARD_FEATURE_ALERTS=true` | `TONBANKCARD_BOT_TOKEN`, `TONBANKCARD_ALERT_WORKER_TOKEN`, alert cron, MySQL migrations. |
| `TONBANKCARD_FEATURE_AI=true` | `GROQ_API_KEY`, `GROQ_MODEL_ID`, Groq rate limits, AI fallback behavior. |
| `TONBANKCARD_FEATURE_CHANGENOW=true` | `CHANGENOW_LINK_ID`. |
| `TONBANKCARD_FEATURE_TON_CONNECT=true` | Telegram/PWA launch URLs and TON Connect manifest verification. |
| `TONBANKCARD_FEATURE_REFERRALS=true` | Trusted Telegram session flow and share/referral migrations. |
| `TONBANKCARD_FEATURE_GAMIFICATION=true` | Achievement migrations and launch copy. |
| `TONBANKCARD_FEATURE_PREMIUM=true` | Telegram Stars settings, bot token, premium signing secret, and payment webhook checks. |

After each feature change, open `/api/health`, `/api/ready`, and the affected UI
route.

### 10. Backups, rollback, and updates

Before deploying an update:

1. Record the current Git commit or uploaded archive name.
2. Back up the MySQL or MariaDB database.
3. Back up host-level environment variables or the `.env` file without posting
   secrets in tickets or logs.
4. Run `php database/migrate.php dry-run`.
5. Put the site in maintenance mode at the host level if the provider requires
   downtime.

Deploy updates:

```sh
cd /var/www/marketcap
git fetch origin
git checkout main
git pull --ff-only
php database/migrate.php up
php database/migrate.php status
```

Rollback order:

1. Disable the affected feature flag.
2. Pause the affected cron or worker job.
3. Redeploy the previous known-good commit or archive.
4. Run `php database/migrate.php down --step=1` only when the release notes say
   the down migration is safe for the incident.
5. Restore the database backup only when data corruption is confirmed.

## Shared Hosting Checklist

Use this condensed cPanel-style checklist when SSH access is limited:

1. Upload the repository contents into the domain web root, including
   `.htaccess`.
2. Select PHP 8.1+ and enable `pdo_mysql` and `curl`.
3. Create the MySQL or MariaDB database and user in the panel.
4. Create `.env` from `.env.example` or use the panel's environment variable
   manager.
5. Verify `/.env` is not downloadable.
6. Run `php database/migrate.php dry-run` and `php database/migrate.php up` from
   Terminal. If Terminal is unavailable, run a temporary one-time cron command
   and remove it after it succeeds.
7. Add recurring cron jobs for `php api/search-refresh.php`, alert evaluation,
   and database backup.
8. Open `/api/health`, `/api/ready`, `/markets`, and `/currency/bitcoin`.
9. Leave `TONBANKCARD_DEBUG=false`.

## Troubleshooting

| Symptom | Likely cause | Fix |
| --- | --- | --- |
| Configuration error page | Missing non-local environment variable. | Check `TONBANKCARD_PROFILE`, `TONBANKCARD_PUBLIC_BASE_URL`, `TONBANKCARD_BOT_USERNAME`, Redis, MySQL, and feature flags. |
| Deep links return 404 | Rewrite rules are disabled. | Enable Apache `mod_rewrite`, allow `.htaccess`, or add the Nginx `try_files` rule. |
| `/.env` downloads | Web server is serving dotfiles. | Stop the deployment, rotate exposed secrets, enable the dotfile deny rule, and verify 403/404 before restoring traffic. |
| Migration fails with `PDO is not available` | PHP lacks PDO MySQL support. | Enable `pdo_mysql` for both CLI PHP and web PHP. |
| Migration cannot connect | Wrong database host, name, user, password, or host grant. | Test with `mysql -h HOST -u USER -p DATABASE` and update `MYSQL_DSN`, `MYSQL_USER`, `MYSQL_PASSWORD`. |
| `/api/ready` is degraded | Required dependency is missing or unreachable. | Read the JSON details and fix database, Upstash Redis, provider, or runtime configuration. |
| Market or search data is stale | Cache refresh job is missing or provider quota is exhausted. | Run `php api/search-refresh.php`, check CoinGecko settings, and verify Upstash Redis credentials. |
| Alerts do not deliver | Alerts disabled, bot token missing, worker token mismatch, or cron not running. | Set `TONBANKCARD_FEATURE_ALERTS=true`, configure `TONBANKCARD_BOT_TOKEN`, match `TONBANKCARD_ALERT_WORKER_TOKEN`, and inspect worker logs. |
| Blank page after deploy | PHP fatal error, stale generated bundle, or blocked static assets. | Check host PHP error logs, verify `assets/js/app.js` exists, and run local `npm test` before uploading again. |

## Acceptance Criteria Mapping

| Issue #82 request | Coverage |
| --- | --- |
| Complete instructions for installing the project on hosting. | Covered by prerequisites, hosting layout, web server, environment, migration, verification, cron, backup, and troubleshooting sections. |
| PHP 8.1+ available everywhere. | Covered by PHP version and extension prerequisites. |
| MySQL/MariaDB standard. | Covered by database creation, `MYSQL_DSN`, migration, backup, and restore steps. |
| Step-by-step detailed installation plan. | Covered by the numbered installation plan and shared hosting checklist. |
