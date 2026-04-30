# TONBANKCARD V2 Database Schema and Migrations

Date: 2026-04-30

Issue: [#11](https://github.com/labtgbot/marketcap/issues/11)

This document defines the first durable MySQL or MariaDB data model for
TONBANKCARD V2. It turns the roadmap requirements for users, Telegram sessions,
watchlists, alerts, referrals, AI cache metadata, provider settings, admin audit
logs, and premium entitlements into a repeatable migration baseline.

The initial migration lives in:

- `database/migrations/0001_v2_core_schema.up.sql`
- `database/migrations/0001_v2_core_schema.down.sql`

Telegram session context added for issue #13 lives in:

- `database/migrations/0002_telegram_session_context.up.sql`
- `database/migrations/0002_telegram_session_context.down.sql`

## Data Minimization

- Store raw Telegram identity only where the application needs it for trusted
  bot delivery and user deletion. The `users` table keeps the numeric Telegram
  user id, language code, and Telegram premium hint, but does not store phone
  numbers, profile photos, raw names, or usernames.
- Store session records with hashes for session tokens, Telegram `initData`,
  start parameters, chat instances, IP addresses, and user agents. Raw
  Telegram `initData` is validation input, not durable data.
- Store exact alert rules in application tables because users need them to run
  alerts. Analytics should continue to receive only trigger types and threshold
  buckets from the issue #10 contract.
- Store AI insight cache metadata rather than raw prompts or full model
  responses. `ai_insight_cache` tracks provider, model, prompt version, market
  data hash, safety state, output hash, response reference, and expiry.
- Store provider secrets outside the database when possible. `provider_settings`
  uses `secret_ref` for secret-manager references and `value_is_secret` to
  prevent secret values from being shown in admin diffs.
- Store admin audit diffs only after redacting secrets. Raw bot tokens, Groq
  keys, database passwords, payment receipts, wallet addresses, AI prompts, and
  full AI responses must not be written to audit logs.

## Entity Model

| Table | Purpose | Sensitive-data notes |
| --- | --- | --- |
| `schema_migrations` | Migration ledger used by `database/migrate.php`. | Stores migration checksums only. |
| `users` | Internal user row with Telegram user identity after server validation. | Keeps raw Telegram numeric id because bot delivery and deletion workflows need it. |
| `user_sessions` | Anonymous, Telegram, bot, and admin session records. | Uses hashes for session tokens, Telegram `initData`, start params, chat instances, IP, and user agent. Stores signed Telegram chat type when present. |
| `watchlists` | Named user watchlists, starting with one default personal list. | Tied to internal user id; anonymous web lists remain browser-local for MVP. |
| `watchlist_entries` | Coin entries in user watchlists. | Stores CoinGecko-style public coin ids and optional symbols only. |
| `alert_rules` | User alert rules for price crosses, percent moves, volume spikes, and market cap thresholds. | Exact thresholds stay server-side and are excluded from analytics payloads. |
| `alert_deliveries` | Telegram bot alert delivery attempts and outcomes. | Stores delivery status and optional Telegram message id, not message bodies. |
| `referral_campaigns` | Campaign definitions for referral and share attribution. | Campaign metadata only. |
| `referral_attributions` | First-touch referral attribution for trusted Telegram users. | Uses internal ids and payload hashes so raw invite payloads are not retained. |
| `ai_insight_cache` | AI insight cache metadata and expiry. | Stores hashes and references, not raw prompts or full model responses. |
| `admin_users` | Operator accounts for support, content, admin, and owner roles. | Uses hashed email identity by default. |
| `provider_settings` | Provider settings for CoinGecko, Groq, Upstash, ChangeNOW, Telegram, and future providers. | Secrets are stored as `secret_ref` references, not plaintext values. |
| `feature_flags` | Runtime feature controls for AI, alerts, ChangeNOW, TON Connect, referrals, gamification, and premium. | Rule JSON must not contain secrets. |
| `admin_audit_logs` | Admin and support audit trail. | Stores redacted before/after JSON and hashed request metadata. |
| `premium_entitlements` | Premium plan state from Telegram Stars, manual grants, partner grants, or tests. | Stores hashed provider customer/subscription references. |

## Query Paths and Indexes

The initial migration includes indexes for the query paths expected by the MVP
and the issue #10 analytics/privacy baseline:

| Query path | Index coverage |
| --- | --- |
| Lookup user by Telegram user identity. | `uniq_users_telegram_user_id` |
| Validate or revoke session by token hash. | `uniq_user_sessions_token_hash` |
| Expire session records by user and expiry. | `idx_user_sessions_user_expires` |
| Load watchlists by user, including the default list. | `idx_watchlists_user` |
| Check whether a user watches a coin. | `idx_watchlist_entries_user_coin` |
| Find watchlist entries by coin for future fanout or aggregate counts. | `idx_watchlist_entries_coin` |
| List a user's alert rules by status. | `idx_alert_rules_user_status` |
| Evaluate active alerts by coin and next evaluation time. | `idx_alert_rules_active_coin` |
| Pull queued alert work by next evaluation. | `idx_alert_rules_next_evaluation` |
| Inspect alert delivery history by rule or user. | `idx_alert_deliveries_rule_time`, `idx_alert_deliveries_user_time` |
| Report referrals by campaign. | `idx_referral_attributions_campaign` |
| Report inviter attribution. | `idx_referral_attributions_inviter` |
| Resolve AI cache entries by hashed cache key. | `uniq_ai_insight_cache_key` |
| Expire AI cache metadata. | `idx_ai_insight_cache_expires` |
| Read provider settings by provider. | `idx_provider_settings_provider_enabled` |
| Read enabled feature flags. | `idx_feature_flags_enabled` |
| Review audit logs by actor. | `idx_admin_audit_logs_actor` |
| Review audit logs by subject. | `idx_admin_audit_logs_subject` |
| Check premium entitlement state by user. | `idx_premium_entitlements_user_status` |

## Migration Runner Conventions

Migrations are plain SQL files under `database/migrations/` with matching
`*.up.sql` and `*.down.sql` files. The version prefix controls apply order. The
runner records applied versions, descriptions, checksums, and timestamps in
`schema_migrations`.

Use these commands:

```sh
php database/migrate.php dry-run
php database/migrate.php status
php database/migrate.php up
php database/migrate.php down --step=1
```

Conventions for future migrations:

- Keep migrations additive unless a destructive change has a tested rollback and
  a deployment note.
- Put one SQL statement per semicolon-terminated block. Do not use stored
  procedures, custom delimiters, or migration-time application code.
- Include an up migration and a down migration. Down migrations may be best
  effort when data loss is unavoidable, but the risk must be documented in the
  same PR.
- Use `DATETIME(6)` in UTC for durable timestamps. Application code owns
  timezone presentation.
- Use `utf8mb4_unicode_ci` for tables unless a future search requirement needs a
  different collation.
- Do not store provider secrets, bot tokens, payment receipts, raw AI prompts,
  raw Telegram `initData`, or raw wallet addresses in migration-managed tables.
- Run `php database/migrate.php dry-run` in CI-friendly checks when a live
  database is unavailable, and run `php database/migrate.php up` against a local
  MySQL/MariaDB database before deploying migrations.

## Local Empty Database Setup

Local development can initialize an empty MySQL or MariaDB database with the
same environment variables already documented in `.env.example`:

```sh
mysql -uroot -p -e "CREATE DATABASE marketcap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -p -e "CREATE USER 'marketcap'@'localhost' IDENTIFIED BY 'marketcap-local-password';"
mysql -uroot -p -e "GRANT ALL PRIVILEGES ON marketcap.* TO 'marketcap'@'localhost';"
```

Then export credentials or place them in `.env`:

```sh
MYSQL_DSN='mysql:host=127.0.0.1;dbname=marketcap;charset=utf8mb4'
MYSQL_USER=marketcap
MYSQL_PASSWORD=marketcap-local-password
php database/migrate.php dry-run
php database/migrate.php up
php database/migrate.php status
```

The `dry-run` command lists migration files without connecting to a database.
The `up` command creates the migration ledger and all V2 core tables on an empty
database.

## Backup and Restore Expectations

- Take a logical backup before every production migration with
  `mysqldump --single-transaction --routines --triggers --set-gtid-purged=OFF`
  or the managed database provider's equivalent snapshot.
- Include `schema_migrations` in backups so restores preserve migration state.
- Encrypt backups at rest and in transit. Store database passwords, bot tokens,
  Groq keys, Upstash tokens, ChangeNOW private values, and payment provider
  credentials outside the dump when they live in the deployment secret store.
- Test restores in staging before public launch and after any migration that
  changes watchlists, alerts, referrals, entitlements, or admin audit tables.
- Restore order is database schema and data first, then application deployment,
  then cache warmup. Upstash/AI cache data can be regenerated unless incident
  response explicitly needs it.
- Document the backup timestamp, application commit, migration version, restore
  target, operator, and verification query results in the deployment notes.

## Retention Policy

| Data class | Default retention |
| --- | --- |
| User rows | Keep while the account is active; soft-delete immediately on deletion request, then purge or anonymize after the legal hold window. |
| User session records | Purge 30 days after `expires_at` or immediately after account deletion unless needed for abuse review. |
| Watchlist entries | Keep until user removal or account deletion. |
| Active alert rules | Keep while active or paused; purge deleted rules after 90 days unless needed for support review. |
| Alert delivery records | Keep 180 days after delivery or 90 days after alert deletion, whichever is earlier. |
| Referral attribution | Keep 180 days for MVP attribution reporting, then aggregate or anonymize. |
| AI insight cache metadata | Purge expired rows after 30 days unless retained for a short incident review. |
| Provider settings and feature flags | Keep current values and audit changes; secrets remain in the secret store. |
| Admin audit logs | Keep at least 365 days for support, security, and configuration accountability. |
| Premium entitlements | Keep active entitlement rows and retain expired/revoked rows for the business/accounting period selected before launch. |

Retention jobs should use small batches and emit admin/system audit entries when
they delete user, alert, referral, premium, or audit-adjacent data.

## Acceptance Criteria Mapping

| Issue #11 acceptance criterion | Coverage |
| --- | --- |
| Schema supports all MVP flows without storing unnecessary sensitive data. | Covered by Data Minimization and the V2 core tables for users, sessions, watchlists, alerts, referrals, AI cache metadata, provider settings, admin users, audit logs, feature flags, and premium entitlements. |
| Migrations are repeatable, reversible where practical, and documented. | Covered by paired up/down SQL migrations, `schema_migrations`, `database/migrate.php`, and Migration Runner Conventions. |
| Indexes cover expected query paths for watchlists, alerts, referrals, and admin audit logs. | Covered by Query Paths and Indexes and implemented in `0001_v2_core_schema.up.sql`. |
| Local setup can initialize an empty database. | Covered by Local Empty Database Setup and `php database/migrate.php up`. |
