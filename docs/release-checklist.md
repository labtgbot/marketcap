# Release Checklist

Date: 2026-04-30

Use this checklist before a public website or Telegram Mini App release.

## Legal review checkpoint

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

## Release quality gates

- Confirm local setup and smoke-test instructions are current in `README.md`.
- Rebuild generated bundles from source when JavaScript sources change.
- Test the public website and Telegram Mini App entry points on mobile and
  desktop widths.
- Confirm production debug output is disabled and required secrets are not
  committed.

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
