# TONBANKCARD V2 Launch Readiness

Date: 2026-05-02

Issue: [#40](https://github.com/labtgbot/marketcap/issues/40)

This runbook packages TONBANKCARD V2 for production release on the public
website and inside Telegram. It turns the final launch work into assigned gates,
evidence requirements, BotFather Main Mini App assets, production verification,
support workflow, admin runbook, rollback, incident response, and phased rollout
steps.

Official Telegram references used for the Mini App launch plan:

- [Telegram Mini Apps](https://core.telegram.org/bots/webapps)
- [Telegram Bot Features](https://core.telegram.org/bots/features)

## Launch Owner Matrix

| Area | Owner | Backup | Evidence |
| --- | --- | --- | --- |
| Go or no-go decision | Product owner | TONBANKCARD operator | Signed launch note and PR approval. |
| Public website deployment | Tech lead | Release engineer | Commit SHA, deployment log, smoke-test output. |
| Telegram bot and BotFather setup | Bot owner | Product owner | BotFather screenshots or exported settings notes. |
| Mini App Store media and profile text | Brand owner | Product owner | Uploaded media list, locale list, and approved copy. |
| DNS, SSL, cache, database, workers | Operations owner | Tech lead | Verification commands, timestamps, and screenshots where useful. |
| Security, privacy, legal | Security owner | Legal owner | Completed legal and abuse checklist with reviewer and date. |
| Admin panel and support readiness | Support owner | TONBANKCARD operator | Admin access proof, support macros, escalation roster. |
| Incident response | Incident commander | Tech lead | Incident channel, rollback checkpoint, and status template. |

All release checklist rows in `docs/release-checklist.md` must have an Owner, a
status, and Evidence before the public launch decision.

## BotFather Main Mini App Setup

Use BotFather from the production bot owner account.

1. Open `@BotFather`.
2. Select `/mybots`.
3. Select the TONBANKCARD production bot.
4. Open `Bot Settings`.
5. Open `Configure Mini App`.
6. Enable the Main Mini App with the production URL:
   `https://marketcap.tonbankcard.com/`.
7. Set the Mini App menu button text to `Open TONBANKCARD`.
8. Open `Configure Splash Screen`.
9. Set loading screen colors and icon:
   - Light background: `#F6FAFD`
   - Light accent: `#1BB2DA`
   - Dark background: `#0B1020`
   - Dark accent: `#1BB2DA`
   - Icon: `assets/images/tonbankcard-icon-512x512.png`
10. Enable the Settings item and route it to `/settings` through the Mini App.
11. Save the Main Mini App configuration and record the BotFather confirmation
    screenshot in the launch notes.

The PWA manifest already exposes the matching display name, dark loading
background, theme color, and high-resolution icon in `site.webmanifest`.

## Mini App Profile Assets

### Profile text

| Field | English copy | Russian localization owner |
| --- | --- | --- |
| Bot name | TONBANKCARD Crypto Tracker | Brand owner |
| Short description | Track crypto markets, TON ecosystem assets, watchlists, alerts, and Telegram Stars premium tools. | Brand owner |
| Description | TONBANKCARD Crypto Tracker gives public web and Telegram users market pulse, coin pages, TON ecosystem context, watchlists, smart alerts, share cards, and premium limits without custody or investment advice. | Brand owner and legal owner |
| Support line | For help, open `/support` in the Mini App or use the official TONBANKCARD support channels. | Support owner |

### Loading screen asset

| Asset | Source file | Owner | Evidence |
| --- | --- | --- | --- |
| Splash icon | `assets/images/tonbankcard-icon-512x512.png` | Brand owner | BotFather Configure Splash Screen screenshot. |
| Light colors | `#F6FAFD` background, `#1BB2DA` accent | Brand owner | BotFather settings note. |
| Dark colors | `#0B1020` background, `#1BB2DA` accent | Brand owner | BotFather settings note. |

### Localized preview media

Telegram profile media supports localized preview media, so upload English and
Russian screenshot sets. Use these committed images as the launch source set and
replace them with fresh post-deploy captures if the UI changes during final QA.

| Locale | Slot | Source | Required caption focus | Owner |
| --- | --- | --- | --- | --- |
| English | 1 | `docs/screenshots/issue-26-pwa-telegram-webview.png` | Telegram Mini App market pulse and native shell. | Brand owner |
| English | 2 | `docs/screenshots/issue-20-public-shell-mobile.png` | Public mobile market browsing. | Brand owner |
| English | 3 | `docs/screenshots/issue-32-alerts.png` | Smart alert setup and Telegram delivery context. | Product owner |
| English | 4 | `docs/screenshots/issue-37-premium.png` | Telegram Stars premium limits and checkout readiness. | Business owner |
| Russian | 1 | `docs/screenshots/issue-26-pwa-telegram-webview.png` | Russian caption or localized capture of the Telegram Mini App shell. | Brand owner |
| Russian | 2 | `docs/screenshots/issue-20-public-shell-mobile.png` | Russian caption or localized capture of public mobile browsing. | Brand owner |
| Russian | 3 | `docs/screenshots/issue-32-alerts.png` | Russian caption or localized capture of alert setup. | Product owner |
| Russian | 4 | `docs/screenshots/issue-37-premium.png` | Russian caption or localized capture of premium limits. | Business owner |

Optional demo videos may replace slots 2 through 4 only when they show the same
flows, have English and Russian variants, and are approved by the brand owner.

## Production Verification Matrix

Record every command output, timestamp, operator, and screenshot in the release
notes. Secrets must be confirmed by name only, never copied into tickets, logs,
or PR comments.

| Target | Verification | Owner | Pass criteria | Evidence |
| --- | --- | --- | --- | --- |
| Domain | Open `https://marketcap.tonbankcard.com/` and `https://marketcap.tonbankcard.com/markets`. | Operations owner | Public website returns 200 and renders TONBANKCARD shell. | Browser screenshot and HTTP status. |
| DNS | `dig +short marketcap.tonbankcard.com` from two networks. | Operations owner | DNS points to the production edge or host. | Command output. |
| SSL | `curl -Iv https://marketcap.tonbankcard.com/`. | Operations owner | Valid certificate chain, HTTPS redirect, no mixed-content warning. | Header log. |
| Bot | Open `https://t.me/${TONBANKCARD_BOT_USERNAME}?startapp` and `/start` from Telegram. | Bot owner | Launch app button opens the Mini App and `/start` returns the expected button. | Telegram screenshot. |
| API | `curl -fsS https://marketcap.tonbankcard.com/api/health` and `/api/ready`. | Tech lead | `/api/health` returns ok and `/api/ready` reports ready dependencies. | JSON output with request id. |
| Cache | Check Upstash Redis health from `/api/ready` and admin operations view. | Operations owner | Cache reachable, TTL policy active, stale fallback known. | Readiness JSON and admin screenshot. |
| Database | `php database/migrate.php status` against production-safe credentials. | Operations owner | MySQL or MariaDB migrations are current and backup timestamp is recorded. | Status output with secrets redacted. |
| Workers | Trigger alert evaluation through the scheduled worker or cron wrapper. | Tech lead | Alert worker authenticates with `TONBANKCARD_ALERT_WORKER_TOKEN` and records no delivery errors. | Worker log and request id. |
| Cron | Confirm scheduled refresh and alert jobs in host scheduler. | Operations owner | Search refresh, alert evaluation, retention, and backup cron entries are present. | Scheduler export. |
| Backups | Run or verify a fresh database backup and restore drill status. | Operations owner | backup completed, encrypted, and restore was tested in staging. | Backup id and restore note. |
| Secrets | Compare configured secret names with `.env.example` and provider stores. | Security owner | Required secrets exist in the deployment store and are absent from client config. | Checklist with names only. |
| Legal | Open `/terms`, `/privacy-policy`, `/cookies-policy`, and `/support`. | Legal owner | Market, AI, alert, premium, Telegram, and ChangeNOW disclaimers are reachable. | Browser screenshots. |
| Observability | Search logs for `/api/health`, frontend errors, and bot delivery failures. | Incident commander | Request IDs are visible and no secrets are logged. | Redacted log samples. |

## User Documentation

User-facing launch documentation is the combination of live routes and support
macros:

- `/support` explains official channels, bug reporting, safety boundaries, and
  market-data limitations.
- `/terms`, `/privacy-policy`, and `/cookies-policy` cover Telegram, AI, alerts,
  wallet context, ChangeNOW, premium, analytics, and storage disclosures.
- Bot commands from `docs/v2-telegram-bot-companion.md` document `/start`,
  `/market`, `/watchlist`, `/alerts`, `/settings`, and `/support`.
- Premium support copy from `docs/v2-premium-subscriptions.md` explains
  Telegram Stars entitlements, cancellations, refunds, and non-advice limits.

Before beta, the support owner prepares short saved replies for:

- "How do I open the Mini App?"
- "How do I add or remove watchlist coins?"
- "How do I pause or delete alerts?"
- "How do Telegram Stars premium refunds work?"
- "Why is market data stale or unavailable?"
- "How do I request deletion of Telegram, alert, or AI feedback data?"

## Admin Runbook

The admin runbook for launch is:

1. Sign in to `/admin` with the owner token and support token separately.
2. Confirm support role is read-only.
3. Confirm feature flags for AI, alerts, ChangeNOW, TON Connect, referrals,
   gamification, and premium match the rollout phase.
4. Confirm CoinGecko, Groq, Upstash Redis, Telegram bot, ChangeNOW, and premium
   settings show configured status without exposing secret values.
5. Confirm audit log records each changed feature flag or provider setting.
6. Confirm `/api/health`, `/api/ready`, and admin operations views agree on
   dependency status.
7. During incident response, disable the narrowest risky feature flag before
   rolling back the full deployment.

The detailed admin behavior is documented in `docs/v2-admin-panel.md`.

## Support Workflow

The support workflow keeps launch support predictable:

1. Triage incoming reports as `access`, `data`, `alerts`, `payments`, `legal`,
   `security`, or `bug`.
2. Ask for route, approximate time, Telegram platform, language, and request id
   if visible. Do not ask for private keys, seed phrases, bot tokens, payment
   secrets, raw Telegram `initData`, or full wallet secrets.
3. Check `/api/health`, `/api/ready`, admin operations status, and relevant
   request-id logs.
4. Use support-token admin access for read-only checks.
5. Escalate security, privacy, payment, or data-deletion reports to the
   security owner and incident commander.
6. Record the support category, request id, resolution, and whether a docs or
   product change is needed.

## Rollback

The rollback path is ordered from least disruptive to most disruptive:

1. Disable a feature flag in admin for the affected surface, such as AI, alerts,
   ChangeNOW, gamification, premium, or referrals.
2. Pause a worker or cron job when the incident is isolated to scheduled work.
3. Revert BotFather Main Mini App URL to the previous stable URL or maintenance
   URL if Telegram launch is broken.
4. Redeploy the previous known-good commit SHA.
5. Restore the database only when data corruption is confirmed and the incident
   commander approves the restore plan.

Rollback evidence must include affected feature, operator, start time, end time,
commit SHA, migration version, backup id when relevant, and verification result.

## Incident Response

The incident response path uses the existing observability, security, and admin
documents:

1. Incident commander opens the incident channel and assigns severity.
2. Tech lead captures `/api/health`, `/api/ready`, recent request-id logs, and
   current deployment SHA.
3. Security owner checks for secret exposure, suspicious admin writes, CSRF
   failures, and leaked Telegram or payment data.
4. Operations owner checks DNS, SSL, cache, database, backups, and worker queues.
5. Product owner decides whether to pause beta/public rollout messaging.
6. Incident commander posts user-facing status if customer impact lasts more
   than 15 minutes.
7. After mitigation, write a post-incident note with timeline, root cause,
   affected users, data exposure decision, rollback decision, and follow-up
   tasks.

## Phased Rollout

The rollout order is internal test, beta, then public launch.

| Phase | Audience | Entry criteria | Exit criteria | Owner |
| --- | --- | --- | --- | --- |
| Internal test | TONBANKCARD operators and maintainers. | Production deploy complete, BotFather Main Mini App configured on test bot or limited production bot, smoke checks passing. | No P0 or P1 defects after core flows: market, search, watchlist, alerts, premium, support. | Product owner |
| Closed beta | Invited Telegram users and selected groups. | Internal test exit met, support workflow staffed, incident response channel open. | Stable `/api/ready`, no unresolved privacy/security blockers, support macros updated from real reports. | Product owner |
| Public launch | All website visitors and Telegram users. | Closed beta exit met, legal signoff, localized preview media uploaded, production verification matrix complete. | Public announcement completed and first-day monitoring reviewed. | TONBANKCARD operator |

## Acceptance Criteria Mapping

| Issue #40 acceptance criterion | Coverage |
| --- | --- |
| Launch checklist is complete and assigned. | Covered by Launch Owner Matrix and `docs/release-checklist.md`. |
| Mini App profile assets meet Telegram guidance and include localized screenshots or videos. | Covered by BotFather Main Mini App Setup and Mini App Profile Assets. |
| Production domain, bot, API, cache, database, and workers are verified. | Covered by Production Verification Matrix. |
| Rollback and incident response paths are documented. | Covered by Rollback and Incident Response. |
