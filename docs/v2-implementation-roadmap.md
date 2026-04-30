# TONBANKCARD V2 Implementation Roadmap

Date: 2026-04-30

This backlog expands the V2 analysis into 38 implementation tasks across 5 stages. The target product is a modern TONBANKCARD Crypto Tracker that works as both a full public website for `marketcap.tonbankcard.com` and a Telegram Mini App.

The roadmap keeps the owner's requested direction: AI-powered crypto intelligence, TON ecosystem focus, Telegram-native growth loops, advanced analytics, gamification, smart alerts, mobile-first PWA behavior, security, and a configurable AI provider layer with Groq as the first provider.

## Stage Overview

| Stage | Focus | Issues |
| --- | --- | --- |
| 1 | Foundation, legal, product baseline | 1-8 |
| 2 | Backend, data, AI provider foundation | 9-16 |
| 3 | Website and Telegram Mini App MVP UX | 17-24 |
| 4 | Intelligence, TON, social, alerts, gamification | 25-32 |
| 5 | Admin, monetization, hardening, launch | 33-38 |

## Created GitHub Issues

| Roadmap item | GitHub issue |
| --- | --- |
| 1 | [#3](https://github.com/labtgbot/marketcap/issues/3) |
| 2 | [#4](https://github.com/labtgbot/marketcap/issues/4) |
| 3 | [#5](https://github.com/labtgbot/marketcap/issues/5) |
| 4 | [#6](https://github.com/labtgbot/marketcap/issues/6) |
| 5 | [#7](https://github.com/labtgbot/marketcap/issues/7) |
| 6 | [#8](https://github.com/labtgbot/marketcap/issues/8) |
| 7 | [#9](https://github.com/labtgbot/marketcap/issues/9) |
| 8 | [#10](https://github.com/labtgbot/marketcap/issues/10) |
| 9 | [#11](https://github.com/labtgbot/marketcap/issues/11) |
| 10 | [#12](https://github.com/labtgbot/marketcap/issues/12) |
| 11 | [#13](https://github.com/labtgbot/marketcap/issues/13) |
| 12 | [#14](https://github.com/labtgbot/marketcap/issues/14) |
| 13 | [#15](https://github.com/labtgbot/marketcap/issues/15) |
| 14 | [#16](https://github.com/labtgbot/marketcap/issues/16) |
| 15 | [#17](https://github.com/labtgbot/marketcap/issues/17) |
| 16 | [#18](https://github.com/labtgbot/marketcap/issues/18) |
| 17 | [#19](https://github.com/labtgbot/marketcap/issues/19) |
| 18 | [#20](https://github.com/labtgbot/marketcap/issues/20) |
| 19 | [#21](https://github.com/labtgbot/marketcap/issues/21) |
| 20 | [#22](https://github.com/labtgbot/marketcap/issues/22) |
| 21 | [#23](https://github.com/labtgbot/marketcap/issues/23) |
| 22 | [#24](https://github.com/labtgbot/marketcap/issues/24) |
| 23 | [#25](https://github.com/labtgbot/marketcap/issues/25) |
| 24 | [#26](https://github.com/labtgbot/marketcap/issues/26) |
| 25 | [#27](https://github.com/labtgbot/marketcap/issues/27) |
| 26 | [#28](https://github.com/labtgbot/marketcap/issues/28) |
| 27 | [#29](https://github.com/labtgbot/marketcap/issues/29) |
| 28 | [#30](https://github.com/labtgbot/marketcap/issues/30) |
| 29 | [#31](https://github.com/labtgbot/marketcap/issues/31) |
| 30 | [#32](https://github.com/labtgbot/marketcap/issues/32) |
| 31 | [#33](https://github.com/labtgbot/marketcap/issues/33) |
| 32 | [#34](https://github.com/labtgbot/marketcap/issues/34) |
| 33 | [#35](https://github.com/labtgbot/marketcap/issues/35) |
| 34 | [#36](https://github.com/labtgbot/marketcap/issues/36) |
| 35 | [#37](https://github.com/labtgbot/marketcap/issues/37) |
| 36 | [#38](https://github.com/labtgbot/marketcap/issues/38) |
| 37 | [#39](https://github.com/labtgbot/marketcap/issues/39) |
| 38 | [#40](https://github.com/labtgbot/marketcap/issues/40) |

## Issue Specs

### 1. [Stage 1] Add legal NOTICE and license audit baseline

**Objective**
Create the legal baseline for TONBANKCARD Crypto Tracker while preserving all original Gecko Client and vendor notices.

**Scope**
- Add the root `NOTICE` file with TONBANKCARD modification attribution.
- Inventory existing license and copyright files in the extracted archive.
- Document which source files and bundled vendors carry original notices.
- Confirm that future V2 changes must preserve upstream notices in source, assets, and generated bundles.

**Acceptance Criteria**
- Root `NOTICE` exists with the TONBANKCARD attribution text.
- A license inventory notes Gecko Client, vendored JavaScript/CSS/font packages, and image assets.
- No original copyright or license files are removed.
- Release documentation includes a legal review checkpoint before public launch.

**Dependencies**
None.

### 2. [Stage 1] Define TONBANKCARD V2 product requirements and information architecture

**Objective**
Convert the concept into a product requirements document that aligns the website, Telegram Mini App, bot, and admin panel.

**Scope**
- Define personas for casual market viewers, TON users, active traders, Telegram group admins, and TONBANKCARD operators.
- Map primary user journeys: market pulse, coin detail, watchlist, alerts, AI insights, swap widget, share card, referral landing, and admin configuration.
- Define navigation for desktop web, mobile web, and Telegram webview.
- Prioritize MVP, beta, and post-launch capabilities.

**Acceptance Criteria**
- PRD includes goals, non-goals, user journeys, permissions, success metrics, and release phases.
- Information architecture covers public website routes, Mini App routes, bot flows, and admin routes.
- MVP scope is small enough to ship without blocking future AI, TON, and gamification work.
- Open decisions are captured with owners and deadlines.

**Dependencies**
Issue 1.

### 3. [Stage 1] Establish runtime configuration and environment strategy

**Objective**
Make the project configurable across local development, staging, production website, and Telegram Mini App contexts.

**Scope**
- Define required environment variables for base URL, bot username, bot token, CoinGecko key, Groq key, Upstash Redis, MySQL, ChangeNOW link id, and feature flags.
- Replace hard-coded production assumptions with environment-driven config.
- Add separate config profiles for local, staging, production web, and Telegram Mini App.
- Ensure debug display is off by default outside local development.

**Acceptance Criteria**
- A fresh checkout can run locally with documented example environment values.
- Missing required production values fail with actionable messages that do not leak secrets.
- Production debug output is disabled by default.
- Telegram and public web URLs can coexist without editing source files.

**Dependencies**
Issue 2.

### 4. [Stage 1] Add reproducible local development workflow and CI checks

**Objective**
Give contributors a reliable way to install, run, lint, and smoke-test the current app before V2 feature work.

**Scope**
- Document local PHP server startup and browser URLs.
- Add PHP linting and generated bundle validation.
- Add a minimal browser smoke test for home, coin detail, exchanges, and search.
- Configure GitHub Actions for lint and smoke checks if repository settings allow it.

**Acceptance Criteria**
- README documents local run steps and troubleshooting.
- CI or documented local checks run PHP lint and a browser smoke path.
- Smoke tests prove the app renders the main routes without JavaScript boot errors.
- Check failures produce logs that are easy to inspect.

**Dependencies**
Issue 3.

### 5. [Stage 1] Replace placeholder branding, content, social links, and legal pages

**Objective**
Remove template content so the current website can become a credible TONBANKCARD public product.

**Scope**
- Replace generic About, footer, team, social, Telegram, YouTube, privacy, cookies, and terms content.
- Update favicon/logo usage to TONBANKCARD-approved assets.
- Add required risk disclaimers for market data, AI summaries, alerts, and swap widgets.
- Add localization placeholders for English and Russian copy where the current app already links Telegram RU.

**Acceptance Criteria**
- No obvious Gecko Client placeholder marketing copy remains in visible routes.
- Legal pages include market-data, AI, alert, and exchange-widget disclaimers.
- External links point to approved TONBANKCARD destinations.
- Content is reviewed on desktop and mobile widths.

**Dependencies**
Issues 1, 2.

### 6. [Stage 1] Fix known V1 defects and add baseline regression tests

**Objective**
Stabilize the existing Gecko Client behavior before large V2 changes.

**Scope**
- Fix the exchanges `perPage` configuration typo.
- Verify the hidden smart search behavior and document how it should work in V2.
- Add regression coverage for currencies list, exchanges list, coin detail, chart tab, and converter area.
- Preserve existing routes unless the PRD explicitly replaces them.

**Acceptance Criteria**
- Known exchange pagination bug is fixed.
- Reproducing tests fail before the fix and pass after it.
- Search and coin navigation have a documented expected behavior.
- No existing feature is removed without an explicit product decision.

**Dependencies**
Issue 4.

### 7. [Stage 1] Choose migration architecture for PHP, Alpine.js, Tailwind, and charts

**Objective**
Decide how V2 will evolve from PHP-rendered Vue 2/Vuetify into the requested PHP 8.1+, Alpine.js, Tailwind CDN, and Chart.js direction.

**Scope**
- Compare incremental replacement, parallel V2 routes, and full rewrite options.
- Define how PHP templates, Alpine components, Tailwind CDN configuration, Chart.js modules, and generated assets will be organized.
- Define compatibility rules for existing data routes and SEO pages.
- Capture risks around CDN dependencies and offline/PWA behavior.

**Acceptance Criteria**
- Architecture decision record names the chosen migration path and why.
- Folder structure, build strategy, and routing rules are documented.
- The decision supports both public website SEO and Telegram Mini App webviews.
- Risks and rollback strategy are documented.

**Dependencies**
Issues 2, 4.

### 8. [Stage 1] Define analytics, privacy, and success metrics baseline

**Objective**
Define how the team measures activation, retention, virality, alert usefulness, and performance without over-collecting data.

**Scope**
- Define an event taxonomy for search, watchlist add, alert create, share, referral open, swap widget open, AI insight view, TON view, and premium conversion.
- Separate anonymous website analytics from authenticated Telegram sessions.
- Define privacy rules for Telegram user data, wallet addresses, AI prompts, and admin audit logs.
- Add metrics dashboards requirements.

**Acceptance Criteria**
- Analytics spec includes event names, properties, identity rules, and retention windows.
- Sensitive data fields are classified and excluded from client logs.
- KPI definitions are accepted for MVP and launch.
- Implementation tasks can reference the event taxonomy.

**Dependencies**
Issue 2.

### 9. [Stage 2] Design MySQL or MariaDB schema and migrations

**Objective**
Create the durable data model for users, watchlists, alerts, referrals, provider settings, audit logs, and premium entitlements.

**Scope**
- Define tables, indexes, constraints, and retention policy.
- Add migration runner conventions for PHP deployments.
- Include Telegram user identity, session records, watchlist entries, alert rules, referral attribution, AI insight cache metadata, and admin users.
- Define backup and restore expectations.

**Acceptance Criteria**
- Schema supports all MVP flows without storing unnecessary sensitive data.
- Migrations are repeatable, reversible where practical, and documented.
- Indexes cover expected query paths for watchlists, alerts, referrals, and admin audit logs.
- Local setup can initialize an empty database.

**Dependencies**
Issues 3, 8.

### 10. [Stage 2] Build PHP 8.1+ API routing layer

**Objective**
Add a small backend API surface that can serve the website and Telegram Mini App without direct browser calls to private providers.

**Scope**
- Define `/api/*` routing, JSON response format, error format, request IDs, and CORS policy.
- Add middleware for sessions, rate limiting hooks, validation, and audit logging.
- Add health and readiness endpoints.
- Keep API keys and bot secrets server-side only.

**Acceptance Criteria**
- API routes return consistent JSON success and error shapes.
- Invalid requests produce safe, actionable errors.
- Health endpoint distinguishes app boot, database, Redis, and upstream provider availability.
- Backend code style and test conventions are documented.

**Dependencies**
Issues 3, 9.

### 11. [Stage 2] Implement Telegram initData validation and session creation

**Objective**
Trust Telegram identity only after server-side validation of raw Mini App init data.

**Scope**
- Add `/api/telegram/session`.
- Validate raw `initData` using the bot token-derived signature process.
- Store trusted Telegram user id, language, premium flag, start parameter, chat type, and chat instance where present.
- Reject tampered, expired, or missing auth data.

**Acceptance Criteria**
- Valid Telegram init data creates or refreshes a server session.
- Tampered hash, stale auth date, and missing required fields are rejected in tests.
- Frontend receives only the minimum session fields needed for UI.
- Browser fallback works for local development without impersonating production users.

**Dependencies**
Issues 9, 10.

### 12. [Stage 2] Build CoinGecko market data gateway

**Objective**
Move market, coin, exchange, trending, global, and search data behind server endpoints so the browser no longer calls CoinGecko directly.

**Scope**
- Add `/api/market/*` endpoints for lists, details, charts, global stats, exchanges, derivatives, and trending.
- Support CoinGecko Demo and Pro API key configuration.
- Normalize provider errors and rate-limit responses.
- Prepare attribution and data freshness metadata for the UI.

**Acceptance Criteria**
- Core app data loads through the backend gateway.
- Provider keys are never exposed to browser JavaScript.
- Upstream 429, timeout, and invalid-symbol cases have tests.
- Responses include cache age or last-updated metadata.

**Dependencies**
Issue 10.

### 13. [Stage 2] Add Upstash Redis caching, rate limiting, and request coalescing

**Objective**
Protect upstream providers and improve latency using Upstash Redis over REST.

**Scope**
- Configure Upstash REST URL and token.
- Add TTLs by data type: live prices, global stats, coin metadata, charts, search index, AI summaries, and TON metadata.
- Add rate limiting for anonymous web users, Telegram sessions, and admin actions.
- Coalesce duplicate concurrent market data requests where possible.

**Acceptance Criteria**
- Cache hit, miss, stale, and upstream fallback behavior is tested.
- Rate-limit responses are clear and do not break core page rendering.
- Upstash secrets stay server-side.
- Metrics include cache hit rate, stale age, and rate-limit counts.

**Dependencies**
Issues 10, 12.

### 14. [Stage 2] Build smart search API and search index refresh workflow

**Objective**
Replace the current third-party static search JSON dependency with a first-party smart search service.

**Scope**
- Create a backend search endpoint for coins, exchanges, TON assets, categories, and popular actions.
- Support symbol, name, contract address, TON ecosystem tags, and fuzzy matching.
- Refresh the search index on a schedule and cache it in Redis.
- Return deep links compatible with web and Telegram Mini App routes.

**Acceptance Criteria**
- Search works without `localstorage.one`.
- Common searches like BTC, TON, USDT TON, exchanges, and trending return useful results.
- Empty, typo, and high-cardinality searches are covered by tests.
- Search result clicks produce analytics events.

**Dependencies**
Issues 12, 13.

### 15. [Stage 2] Add configurable AI provider foundation with Groq first

**Objective**
Create the provider abstraction for AI sentiment, summaries, and insight generation while allowing other providers later.

**Scope**
- Define provider config fields for Groq and future providers: API key, model id, timeout, rate limits, prompt version, enabled features, and fallback behavior.
- Add server-side prompt execution service with structured JSON output validation.
- Add safety rules: no investment advice, cite market data age, and include uncertainty.
- Add provider health checks and cost counters.

**Acceptance Criteria**
- Groq can be configured without code changes.
- AI output is schema-validated before reaching users.
- Provider errors degrade gracefully to "insight unavailable".
- Admin and logs do not reveal API keys or raw sensitive prompts.

**Dependencies**
Issues 8, 10.

### 16. [Stage 2] Add observability and operational logging

**Objective**
Make failures traceable before adding alerts, AI, payment, and bot workflows.

**Scope**
- Add request IDs across API responses and server logs.
- Capture frontend boot errors, API errors, provider errors, queue failures, and bot delivery failures.
- Define log levels and default-off verbose tracing flags.
- Add dashboards or documented queries for core service health.

**Acceptance Criteria**
- A failed market request can be traced from frontend event to backend log to provider response.
- Verbose tracing can be enabled without exposing secrets.
- Logs include enough context for debugging while respecting privacy rules.
- Operational runbook documents common failure modes.

**Dependencies**
Issues 8, 10, 13.

### 17. [Stage 3] Define responsive design system and Telegram theme tokens

**Objective**
Create a modern visual foundation for a dense crypto product that works in desktop browsers and Telegram webviews.

**Scope**
- Define color tokens for TONBANKCARD brand, Telegram theme parameters, semantic market colors, and dark mode.
- Define typography, spacing, tables, cards, buttons, tabs, forms, charts, and skeleton states.
- Add safe-area spacing and bottom-bar rules for Telegram fullscreen mode.
- Include accessibility states for keyboard, screen reader labels, and reduced motion.

**Acceptance Criteria**
- Design tokens are documented and implemented in the frontend.
- UI components adapt to Telegram light and dark themes.
- Mobile layouts fit 360px width without horizontal overflow.
- Desktop layouts remain information-dense and scan-friendly.

**Dependencies**
Issues 2, 7.

### 18. [Stage 3] Build public website shell, SEO metadata, and route structure

**Objective**
Modernize `marketcap.tonbankcard.com` as a full website, not only an embedded Mini App.

**Scope**
- Define public routes for market overview, coin detail, TON ecosystem, exchanges, screener, about, legal, and support.
- Add page titles, meta descriptions, canonical URLs, Open Graph/Twitter cards, and structured data where appropriate.
- Keep the app installable and shareable from public web.
- Add responsive header and navigation distinct from Telegram webview controls.

**Acceptance Criteria**
- Website routes render useful metadata without requiring Telegram.
- Shared coin and market links preview correctly.
- Public website navigation works on desktop and mobile.
- Telegram-specific UI does not appear in normal browser context unless relevant.

**Dependencies**
Issues 5, 17.

### 19. [Stage 3] Build Market Pulse homepage for web and Mini App

**Objective**
Replace the table-first first screen with a fast market overview tailored to TONBANKCARD users.

**Scope**
- Show global market stats, TON ecosystem pulse, top gainers, top losers, trending coins, watchlist preview, and AI market summary placeholder.
- Preserve access to full cryptocurrency table for power users.
- Add loading, empty, stale-data, and upstream-error states.
- Optimize for one-hand mobile use and desktop scanning.

**Acceptance Criteria**
- First meaningful content appears quickly on mobile and desktop.
- Users can reach search, watchlist, TON view, and coin detail in one tap or click.
- Market data includes freshness labels.
- Visual regression or screenshot evidence is attached to the PR.

**Dependencies**
Issues 12, 13, 17, 18.

### 20. [Stage 3] Implement smart search UI and command palette

**Objective**
Bring the existing hidden smart search into V2 as a visible, fast, keyboard-friendly feature.

**Scope**
- Add a global search entry for desktop and mobile.
- Support keyboard shortcut on web and compact search flow in Telegram.
- Display coins, exchanges, TON assets, categories, recent searches, and quick actions.
- Deep-link results to exact routes and track search analytics.

**Acceptance Criteria**
- Search opens quickly and returns useful results from the backend search API.
- Keyboard users can open, navigate, select, and close search without a mouse.
- Mobile search does not overlap Telegram controls or virtual keyboard.
- Empty and error states are clear.

**Dependencies**
Issues 14, 17, 18.

### 21. [Stage 3] Build watchlist UX and persistence

**Objective**
Let users personalize the product in seconds and return to the same coins later.

**Scope**
- Add watchlist add/remove controls in market rows, search results, and coin detail.
- Persist anonymous website watchlists locally, Telegram watchlists in CloudStorage when available, and trusted sessions in MySQL.
- Add watchlist route, sort controls, stale data state, and first-use empty state.
- Sync conflicts safely across devices.

**Acceptance Criteria**
- A user can add a coin to watchlist in under 10 seconds.
- Watchlist persists after reload and Telegram reopen.
- Server sync does not duplicate entries.
- Tests cover add, remove, reload, and unavailable-storage fallback.

**Dependencies**
Issues 9, 11, 12, 17.

### 22. [Stage 3] Rebuild coin detail page with dynamic ChangeNOW exchange widget

**Objective**
Modernize the coin detail page and replace the V1 buy/sell converter area with the partner exchange widget.

**Scope**
- Redesign price header, market stats, chart tabs, links, TON indicators, watchlist action, alerts action, share action, and exchange widget placement.
- Generate ChangeNOW widget parameters from the selected cryptocurrency where supported.
- Use the provided TON to USDT TON widget defaults as the initial partner configuration.
- Add fallback behavior when a coin is not supported by the widget.

**Acceptance Criteria**
- Toncoin detail uses the provided ChangeNOW partner link id and TON defaults.
- Supported coins prefill the widget from the selected asset.
- Unsupported coins show a safe, non-broken fallback.
- The page works in desktop web, mobile web, and Telegram webview.

**Dependencies**
Issues 12, 17, 18, 21.

### 23. [Stage 3] Upgrade charts and advanced market visualization UI

**Objective**
Provide clear, mobile-friendly charting and analytics without overwhelming the first screen.

**Scope**
- Decide whether V2 chart rendering uses Chart.js, ECharts, or both for specific views.
- Add price, volume, market cap, dominance, and relative-performance views.
- Add time ranges, skeleton state, stale-data messaging, and accessible chart summaries.
- Lazy-load chart code to reduce first screen weight.

**Acceptance Criteria**
- Charts render correctly on mobile and desktop.
- Users can switch ranges without layout jumps.
- Chart code is lazy-loaded outside first screen where practical.
- Market chart failures do not break the coin detail page.

**Dependencies**
Issues 12, 13, 17, 22.

### 24. [Stage 3] Add PWA behavior and Telegram WebApp integration

**Objective**
Make V2 feel native both as an installable website and as a Telegram Mini App.

**Scope**
- Add Telegram WebApp adapter for ready, expand, theme, viewport, safe areas, fullscreen, BackButton, MainButton, SecondaryButton, haptics, popups, and share APIs where supported.
- Add browser fallback for local and public web.
- Add PWA manifest, service worker strategy, icons, install prompts, and offline shell.
- Respect Telegram theme changes and mobile safe areas.

**Acceptance Criteria**
- App works in normal browser without Telegram globals.
- Telegram webview uses theme colors, back navigation, safe areas, and haptics.
- PWA install metadata validates.
- Playwright screenshots cover mobile web and Telegram-like viewport.

**Dependencies**
Issues 11, 17, 18.

### 25. [Stage 4] Implement AI sentiment ingestion and scoring pipeline

**Objective**
Add the backend pipeline that turns market, social, and news-like signals into scored sentiment inputs.

**Scope**
- Define data sources allowed for MVP and their refresh intervals.
- Normalize market movement, volume spikes, trend ranking, watchlist concentration, and curated TON ecosystem signals.
- Feed structured context to the AI provider layer.
- Cache outputs and store prompt/version metadata for traceability.

**Acceptance Criteria**
- Sentiment inputs are deterministic and testable before AI generation.
- Pipeline handles missing or stale sources.
- Scores include source freshness and confidence.
- No copyrighted article text is stored or exposed unless licensed.

**Dependencies**
Issues 12, 13, 15, 16.

### 26. [Stage 4] Build AI insight cards with safety controls

**Objective**
Show useful AI summaries without presenting them as financial advice.

**Scope**
- Add AI cards for market pulse, coin detail, watchlist digest, TON ecosystem pulse, and alert explanation.
- Use structured prompts with provider/model versioning.
- Add disclaimers, confidence indicators, source freshness, and "not financial advice" language.
- Add feedback controls for helpful, stale, wrong, or unsafe output.

**Acceptance Criteria**
- AI cards never recommend buying, selling, or holding.
- Cards degrade cleanly when AI provider is disabled or unavailable.
- Feedback is stored for admin review.
- Tests cover schema validation and unsafe output rejection.

**Dependencies**
Issues 15, 19, 21, 22, 25.

### 27. [Stage 4] Add TON ecosystem data model and curated TON market views

**Objective**
Differentiate TONBANKCARD with TON-focused discovery and analytics.

**Scope**
- Define TON asset metadata, categories, verified tags, ecosystem lists, and manual curation workflow.
- Add TON ecosystem page with Toncoin, jettons, stablecoins, DeFi, wallets, infrastructure, and trending assets.
- Add TON filters to search, screener, and market tables.
- Add editorial controls for featured TON assets.

**Acceptance Criteria**
- TON ecosystem page works as a first-class route.
- TON assets can be curated without code deployment.
- Search and screener can filter by TON tags.
- Unverified assets are visually distinct from verified or curated assets.

**Dependencies**
Issues 9, 12, 14, 18.

### 28. [Stage 4] Integrate TON Connect wallet profile features

**Objective**
Allow users to connect a TON wallet for optional wallet-aware features while keeping private keys outside the app.

**Scope**
- Add TON Connect manifest and connection UI.
- Show connected wallet address, network, and supported wallet metadata.
- Prepare read-only wallet-aware watchlist and portfolio placeholders.
- Add disconnect, privacy explanation, and Telegram compliance checks.

**Acceptance Criteria**
- Wallet connection uses TON Connect only.
- Private keys are never requested or handled.
- Connected wallet state can be disconnected and cleared.
- Browser and Telegram contexts are tested.

**Dependencies**
Issues 8, 17, 24, 27.

### 29. [Stage 4] Build advanced screener and analytics filters

**Objective**
Give power users a practical way to find market opportunities without turning the homepage into a dense terminal.

**Scope**
- Add filters for market cap, volume, 24h/7d/30d movement, rank, category, exchange availability, TON tag, sentiment score, and watchlist status.
- Add saved screener presets for logged-in Telegram users.
- Add sortable responsive table and compact mobile filter drawer.
- Add CSV export only if product/legal approval allows it.

**Acceptance Criteria**
- Screener filters run through backend endpoints or cached datasets.
- Mobile filters are usable without horizontal overflow.
- Saved presets persist for trusted sessions.
- Empty states explain which filters produced no matches.

**Dependencies**
Issues 12, 13, 21, 26, 27.

### 30. [Stage 4] Implement smart alerts delivered by Telegram bot

**Objective**
Create alert rules that notify users inside Telegram and deep-link back into the exact market context.

**Scope**
- Support price crosses, percent moves, volume spikes, rank changes, sentiment changes, and TON ecosystem alerts.
- Add quiet hours, frequency caps, and per-user alert limits.
- Add scheduled job or worker for alert evaluation.
- Deliver bot messages with `startapp` deep links to coin or alert detail.

**Acceptance Criteria**
- Users can create, pause, edit, and delete alerts.
- Test alert delivery opens the matching Mini App route.
- Frequency caps prevent repeated spam.
- Alert evaluation is observable and retry-safe.

**Dependencies**
Issues 9, 11, 12, 13, 24.

### 31. [Stage 4] Add shareable market cards and referral deep links

**Objective**
Turn useful market views into Telegram-native sharing and growth loops.

**Scope**
- Generate share cards for coin price, market pulse, watchlist snapshot, TON ecosystem movers, alert wins, and AI insight summaries.
- Generate `startapp` payloads for route, campaign, inviter, and context.
- Support Telegram share APIs where available and normal web share fallback.
- Record referral attribution after validated session creation.

**Acceptance Criteria**
- Every core view has one clear share action.
- Shared links open the correct route in web and Telegram contexts.
- Referral attribution is recorded once per user and campaign.
- Share cards include data freshness and disclaimers.

**Dependencies**
Issues 8, 11, 19, 21, 22, 24.

### 32. [Stage 4] Add gamification achievements and streaks

**Objective**
Increase retention through opt-in, non-spammy achievements tied to useful product behavior.

**Scope**
- Define achievements for first watchlist, first alert, weekly market check, TON explorer, share milestone, and caught market movement.
- Add streak logic, haptics, badges, and shareable achievement cards.
- Keep achievements dismissible and never block core workflows.
- Add admin controls to enable, disable, or tune achievements.

**Acceptance Criteria**
- Achievements are opt-in or low-friction and do not force invitations.
- Streak calculations are tested across time zones.
- Users can dismiss achievement prompts.
- Achievement events appear in analytics.

**Dependencies**
Issues 8, 21, 24, 30, 31.

### 33. [Stage 5] Build admin panel for providers, content, flags, and operations

**Objective**
Give TONBANKCARD operators safe controls over providers and product behavior without code deployments.

**Scope**
- Manage AI providers, Groq model choice, CoinGecko plan/key metadata, Upstash status, ChangeNOW link id, feature flags, curated TON assets, legal copy, alert thresholds, and achievement settings.
- Add admin authentication, roles, audit log, and read-only support role.
- Add provider health views and cache controls.
- Add safe secret entry that never redisplays full values.

**Acceptance Criteria**
- Admin changes are audit logged with actor and timestamp.
- Secrets cannot be viewed after saving.
- Feature flags can disable AI, alerts, widget, TON Connect, or gamification independently.
- Admin panel is not exposed to anonymous users or Telegram-only sessions.

**Dependencies**
Issues 9, 10, 15, 16, 27, 32.

### 34. [Stage 5] Add Telegram bot commands, inline mode, and group flows

**Objective**
Make the bot a real companion to the Mini App, not only a launch button.

**Scope**
- Add `/start`, referral-aware start, `/market`, `/watchlist`, `/alerts`, `/settings`, and support commands.
- Add inline mode coin search cards and market cards.
- Add group context flows using `chat_type` and `chat_instance`.
- Add bot copy in English and Russian where required.

**Acceptance Criteria**
- Bot commands open the correct Mini App routes.
- Inline mode returns useful coin cards that can be shared into any chat.
- Group-opened sessions can show group-specific context without mixing personal data.
- Bot errors are logged with request IDs.

**Dependencies**
Issues 11, 14, 24, 30, 31.

### 35. [Stage 5] Add Telegram Stars premium subscriptions and entitlements

**Objective**
Monetize premium digital features using Telegram-native payments where appropriate.

**Scope**
- Define free and premium limits for alerts, watchlist size, advanced ranges, AI digest frequency, and priority refresh.
- Implement Stars invoices or subscriptions according to Telegram Payments API.
- Store entitlement state server-side and enforce it in backend APIs.
- Add cancellation, renewal, expiration, and refund-state handling.

**Acceptance Criteria**
- Test purchase flow grants premium entitlement.
- Expired entitlement removes premium limits gracefully.
- Backend enforces premium access, not only frontend UI.
- Pricing and benefits are visible before payment.

**Dependencies**
Issues 9, 11, 30, 33, 34.

### 36. [Stage 5] Harden security, privacy, and compliance

**Objective**
Prepare the product for public users, Telegram review, and crypto-related scrutiny.

**Scope**
- Add CSP, security headers, CSRF protections, input validation, output escaping, secure cookies, and secret rotation plan.
- Review Telegram blockchain guidelines, TON Connect usage, market-data attribution, AI disclaimers, and ChangeNOW widget disclosure.
- Add privacy controls for Telegram data, wallet addresses, AI feedback, analytics, and deletion requests.
- Run dependency and static checks where tooling exists.

**Acceptance Criteria**
- Security checklist is complete before launch.
- Sensitive endpoints require the correct session or admin role.
- Compliance notes cover Telegram Mini App, TON Connect, CoinGecko, AI, and exchange widget risks.
- Basic penetration test or manual abuse checklist is documented.

**Dependencies**
Issues 1, 8, 24, 28, 33, 35.

### 37. [Stage 5] Performance, load, and reliability hardening

**Objective**
Ensure V2 stays fast under website traffic, Telegram bursts, and provider rate limits.

**Scope**
- Define performance budgets for first contentful render, app ready time, market API latency, search latency, chart render, and alert delivery.
- Add load tests for market pulse, search, coin detail, alert evaluation, and share-card generation.
- Tune cache TTLs, request coalescing, static asset delivery, and chart lazy loading.
- Add graceful degradation for provider outages.

**Acceptance Criteria**
- Performance budgets are measured in CI or release checklist.
- Load test results identify safe traffic assumptions and bottlenecks.
- Provider outage mode still renders cached or explanatory UI.
- Static assets have production cache headers.

**Dependencies**
Issues 13, 16, 19, 20, 23, 30.

### 38. [Stage 5] Launch readiness, Mini App Store assets, rollout, and documentation

**Objective**
Package TONBANKCARD V2 for production release on the website and inside Telegram.

**Scope**
- Prepare BotFather Main Mini App setup, loading screen colors, icon, demo media, localized screenshots, and profile text.
- Prepare deployment checklist for `marketcap.tonbankcard.com`, SSL, DNS, backups, cron or worker jobs, secrets, and rollback.
- Add user documentation, admin runbook, support workflow, and incident response notes.
- Plan phased rollout from internal test to beta to public launch.

**Acceptance Criteria**
- Launch checklist is complete and assigned.
- Mini App profile assets meet Telegram guidance and include localized screenshots or videos.
- Production domain, bot, API, cache, database, and workers are verified.
- Rollback and incident response paths are documented.

**Dependencies**
Issues 34, 35, 36, 37.

## Current External References Checked

- Telegram Mini Apps documentation: https://core.telegram.org/bots/webapps
- Telegram Bot features and Main Mini App guidance: https://core.telegram.org/bots/features
- Telegram Stars payments for digital goods: https://core.telegram.org/bots/payments-stars
- Telegram blockchain guidelines for Mini Apps: https://core.telegram.org/bots/blockchain-guidelines
- TON Connect overview: https://docs.ton.org/ecosystem/ton-connect/overview
- CoinGecko API documentation: https://docs.coingecko.com/
- Upstash Redis REST API documentation: https://upstash.com/docs/redis/features/restapi
- Groq supported models and production guidance: https://console.groq.com/docs/models
- ChangeNOW exchange widget page: https://changenow.io/widget
