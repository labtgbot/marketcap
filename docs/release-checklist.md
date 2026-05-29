# Release Checklist

Date: 2026-05-02

Use this checklist before a public website or Telegram Mini App release. Every
row must have an Owner, status, and Evidence in the release notes before the
public go or no-go decision.

## Launch owner matrix

| Gate | Owner | Status | Evidence |
| --- | --- | --- | --- |
| Product go or no-go | Product owner | Pending | Signed launch note. |
| Public website deployment | Tech lead | Pending | Deployment SHA and smoke-test log. |
| BotFather Main Mini App | Bot owner | Pending | BotFather configuration screenshot. |
| Mini App profile assets | Brand owner | Pending | Uploaded localized preview media list. |
| DNS, SSL, cache, database, workers | Operations owner | Pending | Production verification matrix output. |
| Security, privacy, legal | Security owner and legal owner | Pending | Completed legal and abuse checks. |
| Support workflow and admin runbook | Support owner | Pending | Support macros and admin access proof. |
| Rollback and incident response | Incident commander | Pending | Rollback checkpoint and incident channel link. |

See `docs/v2-launch-readiness.md` for the detailed launch runbook.

## Legal review checkpoint

- Owner: legal owner.
- Evidence: reviewer name, review date, and release-note link.
- Confirm the root `NOTICE` includes TONBANKCARD modification attribution and
  preserves the original Gecko Client RunCoders and Envato Market Regular License
  notice.
- Complete license inventory verification by reviewing
  `docs/legal-license-inventory.md` against the current tree, including
  `assets/vendor/*`, `assets/images/*`, `assets/js/app.js`, and
  `assets/js/app.min.js`.
- Run `sh tests/legal-baseline-check.sh` and keep the passing result with the
  release notes.
- Verify no original copyright, license, or notice files were removed from source,
  assets, vendored packages, or generated bundles.
- Verify every newly added dependency, CDN asset, generated image, icon, font,
  dataset, or externally sourced asset has an inventory row and approval status.
- Confirm placeholder Gecko Client logos, icons, and team images are either
  replaced with approved TONBANKCARD assets or explicitly approved for continued
  use before launch.
- Record the reviewer and date for the legal checkpoint before tagging or
  deploying a public release.

## BotFather Main Mini App

- Owner: bot owner.
- Evidence: BotFather screenshots and uploaded media list.
- Configure the Main Mini App in `@BotFather` with
  `https://marketcap.tonbankcard.com/` as the production Mini App URL.
- Configure the menu button text as `Open TONBANKCARD`.
- Configure Splash Screen colors with light background `#F6FAFD`, dark
  background `#0B1020`, and accent `#1BB2DA`.
- Upload `assets/images/tonbankcard-icon-512x512.png` as the loading icon.
- Upload localized preview media for English and Russian. The launch source set
  is documented in `docs/v2-launch-readiness.md` and references:
  `docs/screenshots/issue-26-pwa-telegram-webview.png`,
  `docs/screenshots/issue-20-public-shell-mobile.png`,
  `docs/screenshots/issue-32-alerts.png`, and
  `docs/screenshots/issue-37-premium.png`.
- Confirm profile copy, support text, and preview captions avoid investment
  advice and match the legal pages.

## Production verification matrix

| Target | Owner | Verification | Evidence |
| --- | --- | --- | --- |
| Domain | Operations owner | Open `https://marketcap.tonbankcard.com/` and `/markets`. | HTTP status and screenshot. |
| DNS | Operations owner | Resolve `marketcap.tonbankcard.com` from two networks. | Command output. |
| SSL | Operations owner | Inspect `curl -Iv https://marketcap.tonbankcard.com/`. | Header log. |
| Bot | Bot owner | Open `https://t.me/${TONBANKCARD_BOT_USERNAME}?startapp` and `/start`. | Telegram screenshot. |
| API | Tech lead | Call `/api/health` and `/api/ready`. | JSON output with request id. |
| Cache | Operations owner | Confirm Upstash Redis readiness and stale fallback status. | Readiness JSON and admin screenshot. |
| Database | Operations owner | Run `php database/migrate.php status` with production-safe credentials. | Migration status with secrets redacted. |
| Workers | Tech lead | Trigger alert evaluation through the scheduled worker or cron wrapper. | Worker log and request id. |
| Backups | Operations owner | Confirm backup completion and latest restore drill. | Backup id and restore note. |
| Secrets | Security owner | Check required secret names against `.env.example` and deployment store. | Checklist with names only. |
| Legal and support | Legal owner and support owner | Open `/terms`, `/privacy-policy`, `/cookies-policy`, and `/support`. | Browser screenshots. |
| Uptime monitor | Monitoring owner | Confirm the external probe polls `/api/health` and `/api/ready` and pages the alert channel on failure. | Monitor configuration screenshot and a test alert. |
| Error aggregation | Monitoring owner | Confirm `TONBANKCARD_ERROR_MONITORING_ENABLED=true` with a configured DSN forwards a redacted test error. | Aggregator event link with secrets redacted. |

## Monitoring and alerting

- Owner: Monitoring owner.
- Evidence: monitor configuration, alert-routing screenshot, and a test alert
  delivered end to end.
- **Uptime/health monitor.** Configure an external uptime tool (for example
  UptimeRobot, Better Stack, or a self-hosted probe) to poll
  `https://marketcap.tonbankcard.com/api/health` and `/api/ready` on a short
  interval. `/api/health` confirms the app boots; `/api/ready` confirms cache and
  provider readiness, so alert on either a non-200 status or a `ready: false`
  body.
- **Alert routing.** Route monitor failures to the operations Telegram alert
  channel (the same channel used by the alerts worker) and page the incident
  commander after a sustained outage. Record the channel id, escalation policy,
  and on-call owner in the release notes.
- **Error aggregation.** Forwarding to a Sentry-compatible DSN (or plain webhook)
  is gated behind `TONBANKCARD_ERROR_MONITORING_ENABLED` and disabled by default.
  When enabled, the observability layer forwards `error` and `critical` events —
  already redacted — to the configured `TONBANKCARD_ERROR_MONITORING_DSN`. The
  client side reuses `TONBANKCARD_CLIENT_ERROR_REPORTING` to ship browser errors
  to `/api/observability/client-error`, which then flow through the same hook.
- **Monitoring matrix.** Record the monitoring owner, the uptime tool, the error
  aggregation tool, and the alert routing destination with the release notes
  before the go or no-go decision.

| Monitoring concern | Owner | Tool | Alert routing |
| --- | --- | --- | --- |
| Uptime and readiness | Monitoring owner | External uptime probe against `/api/health` and `/api/ready` | Operations Telegram alert channel, then incident commander page |
| Server and client errors | Monitoring owner | Error aggregator via `TONBANKCARD_ERROR_MONITORING_DSN` | Aggregator project alerts plus operations Telegram channel |

## Release quality gates

- Owner: tech lead.
- Evidence: local check logs from `test-logs/` and CI link.
- Confirm local setup and smoke-test instructions are current in `README.md`.
- Rebuild generated bundles from source when JavaScript sources change.
- Test the public website and Telegram Mini App entry points on mobile and
  desktop widths.
- Confirm production debug output is disabled and required secrets are not
  committed.
- Run `npm test`, including `npm run test:launch-readiness`, before tagging or
  moving the pull request out of draft.

## Versioning and changelog

- Owner: tech lead.
- Evidence: updated `CHANGELOG.md` entry and the matching `package.json`
  `version` field.
- The project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html):
  bump the MAJOR version for incompatible changes, MINOR for backward-compatible
  features, and PATCH for backward-compatible fixes.
- Every release must move the `## [Unreleased]` notes in `CHANGELOG.md` into a new
  dated, versioned section that matches the `package.json` `version`, and tag the
  release as `v<version>`.
- A populated changelog entry for the release version is a required release gate:
  do not tag or deploy without it.

## Performance, load, and reliability checkpoint

- Run `npm run test:performance` and keep the
  `test-logs/performance-load-summary.json` result with release notes.
- Confirm `first_contentful_render_ms`, `app_ready_ms`, and `chart_render_ms`
  budgets from `config/performance.php` on mobile and desktop browsers.
- Verify production static asset headers mirror the app policy: timestamped
  assets are immutable for one year, unversioned assets allow
  `stale-while-revalidate`, and `service-worker.js` revalidates.
- Confirm market provider outage mode renders stale cached data or explanatory
  UI on the market pulse and coin chart paths.

## Rollback and incident response

- Owner: incident commander.
- Evidence: rollback checkpoint, previous commit SHA, backup id when relevant,
  and incident channel link.
- Prefer feature-flag rollback first for AI, alerts, ChangeNOW, TON Connect,
  referrals, gamification, and premium.
- Pause the affected worker or cron job when the incident is isolated to
  scheduled work.
- Revert the BotFather Main Mini App URL to the previous stable URL or a
  maintenance URL if Telegram launch is broken.
- Redeploy the previous known-good commit when feature rollback is insufficient.
- Restore the database only after data corruption is confirmed and the incident
  commander approves the restore plan.
- Follow `docs/v2-launch-readiness.md`, `docs/v2-observability-operational-logging.md`,
  and `docs/v2-security-privacy-compliance.md` for incident response.
