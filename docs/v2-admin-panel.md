# TONBANKCARD V2 Admin Panel

Issue: [#35](https://github.com/labtgbot/marketcap/issues/35)

The admin panel adds authenticated operator controls for runtime configuration that previously required a code or environment deployment. The browser route is `/admin`; all state-changing work is performed through `/api/admin/*` JSON endpoints with the standard API envelope and request-id headers.

## Access Model

Admin API access is token based. `TONBANKCARD_ADMIN_TOKEN` grants the owner role with write permission. `TONBANKCARD_ADMIN_SUPPORT_TOKEN` grants the support role with read-only access for review and incident triage.

Telegram Mini App sessions and anonymous browser sessions are not accepted for admin routes. A Telegram initData header without an admin token still receives `401`.

The admin store path is configured with `TONBANKCARD_ADMIN_STORE`. The store contains redacted configuration, operational settings, and the audit log. It must be writable by the PHP process before write actions can succeed.

## Controls

- AI provider: Groq model id and write-only API key metadata.
- Market provider: CoinGecko plan and write-only key metadata.
- Cache provider: Upstash status and write-only REST token metadata.
- Exchange widget: ChangeNOW link id.
- Feature flags: `/api/admin/feature-flags` can independently disable AI, alerts, widget, TON Connect, gamification, referrals, and premium controls.
- Mini App setup: a separate `/admin/mini-app` tab writes the Telegram runtime profile, public and Mini App URLs, bot username, bot token metadata, webhook secret metadata, alert worker token metadata, Telegram Stars premium settings, and launch feature flags from one operator workflow.
- Content: legal copy for the global disclaimer and curated TON assets merged into `/api/ton/assets`.
- Operations: cache stale mode, purge requests, alert thresholds, and achievement settings.

## Secrets

Secrets are write-only. Submitted values are converted into metadata with a configured flag, redacted display value, source, fingerprint, and timestamp. Full values are never returned by the API, stored in the admin JSON file, or written into audit entries.

Existing environment-backed secrets appear only as configured metadata, for example `env:GROQ_API_KEY`.

The Mini App setup tab follows the same rule for `TONBANKCARD_BOT_TOKEN`, `TONBANKCARD_BOT_WEBHOOK_SECRET`, `TONBANKCARD_ALERT_WORKER_TOKEN`, and `TONBANKCARD_PREMIUM_SIGNING_SECRET`. It can persist those values into `.env`, but API responses, admin store JSON, and audit entries expose only configured/redacted metadata.

## Audit Log

Every write is appended to the audit log with actor, role, action, subject type, request id, and UTC timestamp. Before and after snapshots are redacted before persistence. The admin API returns the newest audit entries through `/api/admin/audit-log`.

## Endpoints

- `GET /api/admin`: route discovery for authenticated operators.
- `POST /api/admin/session`: validates an owner or support token.
- `GET /api/admin/config`: returns safe current admin state.
- `PUT /api/admin/mini-app`: updates Telegram Mini App deployment settings, writes the allowed `.env` keys, returns readiness status, launch URL, webhook URL, and a safe `setWebhook` command template.
- `PUT /api/admin/providers`: updates provider metadata and secret references.
- `PUT /api/admin/feature-flags`: updates runtime feature flags.
- `PUT /api/admin/content`: updates legal copy and curated TON assets.
- `PUT /api/admin/operations`: updates operational thresholds and achievement settings.
- `POST /api/admin/cache/purge`: records a cache purge request.
- `GET /api/admin/audit-log`: returns the redacted audit log.

## Local Verification

Run the focused regression check:

```sh
npm run test:admin-panel
```

Run the full local suite before preparing a pull request:

```sh
npm test
```
