# TONBANKCARD V2 PWA and Telegram WebApp Integration

Date: 2026-05-01

Issue: [#26](https://github.com/labtgbot/marketcap/issues/26)

This document describes the native shell behavior for TONBANKCARD V2 across the
public installable website and Telegram Mini App webviews.

## Browser PWA Shell

The public website exposes install metadata through `site.webmanifest`, including
a stable app id, standalone display mode, theme and background colors, and 192px
plus 512px install icons with maskable purpose. The browser adapter captures the
`beforeinstallprompt` event, keeps the prompt available for a user gesture, and
shows a compact install icon in the top app bar only when the browser reports
that installation is available.

## Service Worker Strategy

`service-worker.js` uses a versioned cache. During install it pre-caches the
offline shell, manifest, core CSS and JavaScript bundles, favicon, and install
icons. Navigation requests use network-first behavior with `offline.html` as the
offline shell fallback. Static assets use stale-while-revalidate. `/api/`
requests are intentionally excluded from service worker caching so market data,
search, watchlist, and Telegram session calls preserve their server contracts.

## Telegram WebApp Adapter

The Telegram SDK script is loaded only for the `telegram` runtime profile. Normal
browser profiles do not require Telegram globals. The shared adapter still
initializes safely when `window.Telegram` is missing so local and public web
surfaces continue to render.

The adapter wraps:

- `ready`, `expand`, `requestFullscreen`, `exitFullscreen` where supported.
- `lockOrientation` and `unlockOrientation` (Bot API 7.8+) to stabilise the
  viewport orientation when running in fullscreen.
- `themeChanged`, `viewportChanged`, `safeAreaChanged`,
  `contentSafeAreaChanged`, `fullscreenChanged`, and `fullscreenFailed` events.
  `fullscreenFailed` triggers an `expand()` fallback so the Mini App is always
  at maximum height even on clients that do not support fullscreen.
- Native color synchronization through `setHeaderColor`,
  `setBackgroundColor`, and `setBottomBarColor`.
- `BackButton` with Vue Router synchronization for in-app navigation.
- `MainButton` and `SecondaryButton` configuration helpers.
- `HapticFeedback` impact, notification, and selection helpers.
- `showPopup`, `showAlert`, and `showConfirm` with browser fallbacks.
- Share helpers for Telegram links, stories, inline query, Web Share, and
  clipboard fallback.

## Safe Areas and Viewport

The adapter maps Telegram viewport height, stable viewport height, fullscreen
state, expanded state, safe-area inset, and content safe-area inset values into
CSS custom properties on the root element. Existing V2 safe-area rules consume
those variables alongside browser `env(safe-area-inset-*)` values.

When fullscreen mode is active (the `tbc-telegram-fullscreen` class is present
on the root element) the CSS constrains the app to exactly the reported viewport
height and adds content-safe-area padding to the top bar, bottom navigation, and
main content area so none of the UI is obscured by the Telegram chrome.

## Verification

Automated coverage:

- `npm run test:pwa-telegram` validates the manifest, service worker, offline
  shell, install icon metadata, adapter entry points, documentation, and
  screenshot hooks.
- `npm run test:smoke` exercises normal mobile web and Telegram-like mobile
  viewports in Playwright, including install prompt capture and Telegram theme,
  native color, safe-area, back button, and haptic stubs.

Playwright screenshots are written to `test-logs/pwa-mobile-web.png` and
`test-logs/pwa-telegram-webview.png` during the smoke test. The PR also includes
committed review screenshots under `docs/screenshots/`.
