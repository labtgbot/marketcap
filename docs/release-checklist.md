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
