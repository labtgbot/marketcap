# Improvements V2 Analysis

Date: 2026-04-30

This document analyzes the unpacked `gecko-client.zip` archive and proposes a phased plan for turning the current crypto market website into a Telegram Mini App with stronger user experience, retention, and viral loops.

No extracted application source files were edited for this analysis. The archive was unpacked at the repository root as requested.

## Current Project Snapshot

The project is a 2021-era PHP-rendered single page application named Gecko Client.

- `index.php` loads constants, config, helper functions, route templates, and the app shell.
- `views/app-scripts.php` serializes PHP config into `window.GeckoClient`, then loads the browser app.
- `templates/` contains Vue templates embedded as `text/x-template` script tags.
- `dev/js/src/` contains the readable JavaScript source; `assets/js/app.js` and `assets/js/app.min.js` are generated bundles.
- `assets/vendor/` vendors Vue 2, Vue Router 3, Vuetify 2, Axios, ECharts, Lodash, MDI icons, and Roboto fonts.
- Data is requested directly from the browser against CoinGecko endpoints in `dev/js/src/coingecko.js`.
- Search uses a third-party static JSON file from `https://localstorage.one/crypto/data/search.json` because the CoinGecko search endpoint is commented as CORS-blocked.
- User preferences are stored in browser `localStorage` under the `GeckoClient:` prefix.
- The app currently has no Telegram Mini Apps SDK integration. The only Telegram-related code is display of exchange Telegram links from CoinGecko data.

Notable existing risks and cleanup items:

- `config/site.php` leaves the production `base_url` empty while `constants.php` sets `GECKO_CLIENT_ENV` to `production`, so a fresh deployment renders configuration errors until configured.
- `constants.php` has `GECKO_CLIENT_DISPLAY_ERRORS` set to `TRUE`; production should not expose PHP errors.
- `dev/js/src/routes/exchanges.js` sends `per_page: this.per_page` instead of `this.perPage`, so the exchanges list does not honor the configured page size.
- Vue 2 support ended on 2023-12-31, while the project is still on `vue@2.6.14`.
- Dependencies are vendored and there is no package manifest, lockfile, test runner, formatter, or CI definition.
- The current design is a broad desktop-style market dashboard with tables, drawer navigation, footer pages, and placeholder content. It is useful as a web market portal, but it is not yet a Telegram-native mobile product.
- API calls are made directly from each user browser. This creates rate-limit, reliability, analytics, and API-key handling problems once traffic grows.
- About, social links, and legal pages are generic template content and should be replaced before public launch.

## Product Direction

The strongest Telegram Mini App direction is not "website inside Telegram." It should be a compact crypto market companion users can open from a bot, personalize quickly, share into chats, and return to when price movement happens.

Recommended positioning:

> A Telegram-native crypto market assistant for watchlists, price alerts, trending coins, shareable market cards, and group market discussions.

Primary user jobs:

- Check market direction in 5 seconds.
- Track a personal watchlist without account creation friction.
- Get alerts inside Telegram when a watched coin moves.
- Share a coin, chart, portfolio snapshot, or market take into a chat.
- Open a deep link from a shared message and land on the exact coin, list, or campaign.

## Telegram Mini App Gaps

The app should add the official Telegram Web App script and wrap `window.Telegram.WebApp` behind a small internal adapter. The adapter should expose:

- Startup and launch context: `initData`, validated server-side, `start_param`, `chat_type`, and `chat_instance`.
- Theme integration: `themeParams`, `colorScheme`, CSS variables, and `themeChanged`.
- Viewport integration: `viewportStableHeight`, `safeAreaInset`, `contentSafeAreaInset`, fullscreen mode, and resize events.
- Native controls: `BackButton`, `MainButton`, `SecondaryButton`, `SettingsButton`, popups, haptics, and closing confirmation.
- Storage: `CloudStorage` for cross-device settings, `DeviceStorage` for local state, and `SecureStorage` for sensitive client-side values where supported.
- Sharing: deep links with `startapp`, inline mode, generated share cards, and prepared messages where the bot flow supports them.

Security rule: `initDataUnsafe` is only display convenience. Any user identity, referral, or entitlement logic must validate raw `initData` on the backend before trusting it.

## Recommended Architecture

Move from a static PHP bundle with direct browser data calls to a Mini App plus bot-backed API.

1. Frontend
   - Migrate to Vue 3, Vite, TypeScript, Vue Router 4, and Pinia.
   - Keep ECharts if the team wants proven charting, but lazy-load chart modules and tune mobile rendering.
   - Build Telegram theme tokens and safe-area spacing into the design system.
   - Use Playwright for mobile and Telegram-webview-like viewport checks.

2. Backend
   - Add a small API service for Telegram auth validation, user state, referrals, watchlists, alerts, and analytics.
   - Proxy CoinGecko requests through the backend with server-side caching, request coalescing, rate limiting, retries, and API key support.
   - Store frequently used lists such as markets, trending, and search index in cache so every user does not hit CoinGecko separately.
   - Keep public, non-sensitive config in the frontend; keep API keys and bot token only on the server.

3. Bot
   - Configure the Main Mini App in BotFather.
   - Add `/start`, referral-aware `/start <payload>`, market snapshot commands, alert delivery, and deep links into Mini App views.
   - Use inline mode so users can share coin cards and market snapshots into any chat.
   - Prepare profile media, localized previews, and loading screen assets for discoverability.

4. Data and Compliance
   - Use CoinGecko attribution where required by plan.
   - Respect CoinGecko plan limits: the current free/demo tier is not enough for viral traffic if every browser calls the API directly.
   - Add explicit "not financial advice" language around alerts, generated summaries, and market signals.
   - If wallet, token, airdrop, or on-chain features are added, follow Telegram's blockchain rules: Mini Apps must use TON for new issued/distributed crypto assets and TON Connect for wallet interactions.

## Step-by-Step Tasks

### Phase 0: Baseline and Product Definition

1. Confirm license and ownership
   - Verify the Envato license permits the intended Telegram Mini App use, redistribution, and modification.
   - Decide whether Gecko Client is a temporary prototype or the long-term codebase.
   - Acceptance: documented license decision and product name/brand direction.

2. Make the extracted app reproducible
   - Add a README section with local run steps.
   - Add environment-based `base_url` handling instead of editing PHP config per deployment.
   - Turn PHP display errors off by default outside development.
   - Acceptance: a reviewer can run the app locally and production config does not show debug output.

3. Inventory current defects before feature work
   - Fix the exchanges `perPage` typo.
   - Replace placeholder social links, About content, and legal copy.
   - Add basic PHP lint and JavaScript bundle validation to CI.
   - Acceptance: current app loads its core routes and obvious template defaults are removed.

### Phase 1: Telegram Mini App MVP

4. Add Telegram WebApp adapter
   - Load the Telegram Web App script.
   - Add an adapter for ready/expand/theme/viewport/back button/main button/haptics.
   - Add browser fallback behavior for normal web testing.
   - Acceptance: inside Telegram, the app uses Telegram colors, handles safe areas, and back navigation works.

5. Add backend auth validation
   - Build `/api/telegram/session` that validates raw `initData`.
   - Create or update a user record with Telegram user id, language, premium flag, and referral start parameter.
   - Acceptance: frontend can create a trusted session, and tampered init data is rejected.

6. Add CoinGecko API gateway
   - Move market, coin, exchange, trending, and search requests behind `/api/market/*`.
   - Add cache TTLs by data type: short for prices, longer for metadata/search.
   - Add retry/backoff for 429 and upstream timeouts.
   - Acceptance: browser no longer calls CoinGecko directly for core app data.

7. Redesign first screen for Telegram mobile
   - Replace the desktop table-first landing with a compact Market Pulse screen.
   - Include top movers, watchlist, trending, market cap/volume summary, and a single search entry.
   - Replace drawer-heavy navigation with bottom tabs or compact segmented views.
   - Acceptance: first meaningful screen is usable on 360px width without horizontal table scanning.

### Phase 2: Retention

8. Watchlist
   - Let users add/remove coins from coin rows and detail pages.
   - Store lightweight preferences in Telegram CloudStorage when available, backed by server state for logged-in sessions.
   - Add a watchlist tab as the default return destination once the user has selected coins.
   - Acceptance: users can build a watchlist in under 10 seconds and see it after reopening the app.

9. Price alerts
   - Add alert creation from a coin detail page: price crosses, percent move, volume spike, or rank change.
   - Deliver alerts through the bot with deep links back into the relevant coin view.
   - Add quiet hours and alert frequency controls.
   - Acceptance: a server-side job can trigger a test alert and the message opens the matching Mini App route.

10. Personalized market digest
   - Generate a daily or on-demand digest from watchlist movements and trending data.
   - Keep language factual and avoid investment recommendations.
   - Acceptance: digest can be shared as a bot message and opened as a Mini App detail view.

### Phase 3: Virality

11. Referral-aware deep links
   - Generate `https://t.me/<bot>?startapp=<payload>` links for coins, watchlists, campaigns, and inviter attribution.
   - Persist inviter, campaign, and landing route after validated session creation.
   - Acceptance: opening a shared link lands on the right view and records referral attribution once.

12. Shareable market cards
   - Generate compact cards for coin price, 24h move, watchlist snapshot, and market pulse.
   - Support direct sharing to Telegram chats and stories where available.
   - Acceptance: every key view has one obvious share action that creates a useful Telegram-native artifact.

13. Inline mode cards
   - Let users type the bot username in any chat and search coins inline.
   - Return cards that open the Mini App with the correct `startapp` payload.
   - Acceptance: a user can share a live coin card without first opening the app.

14. Group market rooms
   - Use `chat_type` and `chat_instance` for chat-specific shared state.
   - Add group watchlists, polls such as "bullish/bearish", and recap cards.
   - Acceptance: when opened from a group context, the app can show group-specific market context without mixing it with personal state.

15. Viral achievements without spam
   - Add opt-in achievements such as "first watchlist", "caught 10 percent move", or "weekly market streak".
   - Use haptics and share cards, not forced invitations.
   - Acceptance: achievements are useful, dismissible, and measurable without blocking core workflows.

### Phase 4: Monetization and Scale

16. Telegram Stars subscriptions
   - Offer premium alerts, more watchlist slots, advanced chart ranges, or priority refresh.
   - Use Telegram Stars for digital subscriptions and keep entitlement validation on the backend.
   - Acceptance: purchase, entitlement check, renewal state, and cancellation path are tested.

17. App Store readiness
   - Configure Main Mini App in BotFather.
   - Upload localized demo media and screenshots to the bot profile.
   - Configure splash/loading screen for light and dark themes.
   - Acceptance: bot profile presents the app clearly and can be submitted for Mini App Store consideration.

18. Performance and reliability hardening
   - Remove unused vendored source maps from production delivery.
   - Lazy-load heavy charting code.
   - Add CDN/cache headers for static assets.
   - Add Sentry or equivalent error monitoring.
   - Acceptance: mobile cold load, interaction timing, upstream error rate, and alert delivery latency are tracked.

## Suggested First PRs

1. Documentation and reproducible baseline
   - Keep this PR to archive extraction and planning documentation.
   - Follow-up PR: add local run docs, CI lint, and production-safe config defaults.

2. Runtime bug cleanup
   - Fix `this.per_page` in `dev/js/src/routes/exchanges.js`.
   - Replace placeholder About/social/legal content.
   - Add a minimal browser smoke test for home, currency detail, exchanges, and search.

3. Telegram adapter spike
   - Add official Telegram script and adapter with browser fallback.
   - Apply Telegram theme/safe-area CSS variables to the current app without redesigning everything.

4. Backend gateway spike
   - Add a small server endpoint that validates `initData`.
   - Proxy one CoinGecko endpoint with cache.
   - Use this to prove auth, rate limiting, and API key handling before migrating all data calls.

5. Mobile MVP redesign
   - Build the Market Pulse first screen.
   - Add watchlist UI using local/CloudStorage first, then sync to backend.

## Measurement Plan

Track these from the first Telegram MVP:

- Activation: first open to first watchlist add.
- Retention: users returning after an alert, digest, or shared card.
- Viral coefficient: shares sent per active user and opens per shared link.
- Alert usefulness: alert open rate and alert disable rate.
- Data reliability: CoinGecko cache hit rate, upstream 429 rate, and stale data age.
- Performance: app ready time in Telegram webviews, chart render time, and frontend error rate.

## Source References

- Telegram Mini Apps documentation: https://core.telegram.org/bots/webapps
- Telegram Bot features and Mini App profile guidance: https://core.telegram.org/bots/features
- Telegram Stars payments for digital goods: https://core.telegram.org/bots/payments-stars
- Telegram blockchain guidelines for Mini Apps: https://core.telegram.org/bots/blockchain-guidelines
- CoinGecko Pro API quick start: https://support.coingecko.com/hc/en-us/articles/9383547636249-Quick-Start-on-using-CoinGecko-Pro-API
- CoinGecko API pricing and rate-limit tiers: https://www.coingecko.com/en/api/pricing
- Vue 3 introduction and Vue 2 EOL notice: https://vuejs.org/guide/introduction.html
- Vite getting started and Vue/TypeScript templates: https://vite.dev/guide/
