# TONBANKCARD Automatic Hosting Installer

Date: 2026-05-02

Issue: [#84](https://github.com/labtgbot/marketcap/issues/84)

TONBANKCARD includes a guarded browser installer at `/install/` for standard
PHP 8.1+ hosting with MySQL or MariaDB. Upload the repository to the hosting web
root, open `https://your-domain.example/install/`, and complete the setup form
before sending users to the public site.

The installer turns the manual plan in
[docs/hosting-installation.md](hosting-installation.md) into an interactive
workflow. It checks the PHP runtime, writes `.env`, tests the database, and can
run database migrations without SSH access.

## Access Model

The installer is available automatically only before `.env` exists. After a
successful write it saves:

```dotenv
TONBANKCARD_INSTALLER_ENABLED=false
```

That locks `/install/` so it cannot be reused casually on a live site. To reopen
it later, edit `.env` manually:

```dotenv
TONBANKCARD_INSTALLER_ENABLED=true
TONBANKCARD_INSTALLER_TOKEN=replace-with-a-long-random-token
```

Then open `/install/?token=replace-with-a-long-random-token`. When the installer
writes `.env` again it sets `TONBANKCARD_INSTALLER_ENABLED=false` to lock itself.

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
