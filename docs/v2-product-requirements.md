# TONBANKCARD V2 Product Requirements and Information Architecture

Date: 2026-04-30

Issue: [#4](https://github.com/labtgbot/marketcap/issues/4)

This PRD converts the TONBANKCARD V2 concept into a product plan for the public
website, Telegram Mini App, Telegram bot, and operator admin panel. It uses the
current Gecko Client website as the V1 baseline and narrows the first release so
V2 can ship without blocking later AI, TON, and gamification work.

## Context

The current website is a PHP-rendered Gecko Client market tracker with Vue 2,
Vuetify, ECharts, and direct browser requests to CoinGecko. Its visible product
shape is a desktop-style side navigation, global stats bar, cryptocurrency table,
exchange pages, and coin detail pages. The Toncoin detail page already contains a
buy and sell area that V2 will replace with the ChangeNOW partner widget.

V2 should become a TONBANKCARD-branded crypto intelligence product with two
first-class surfaces:

- A public website at `marketcap.tonbankcard.com` for SEO, sharing, desktop use,
  legal pages, and unauthenticated market discovery.
- A Telegram Mini App opened from the TONBANKCARD bot for watchlists, alerts,
  Telegram-native sharing, referral links, and personalized market context.

The bot and admin panel are supporting products. They must be designed early so
the website and Mini App do not create dead-end flows.

## Goals

- Let users understand the market pulse within 5 seconds on mobile or desktop.
- Make TON ecosystem discovery a clear product advantage over generic trackers.
- Preserve useful V1 market routes while replacing placeholder branding, social
  links, legal content, and desktop-only interaction patterns.
- Provide personal watchlists, alerts, and shareable market context with minimum
  account friction.
- Use AI for factual summaries, sentiment explanation, and market context while
  avoiding investment advice.
- Embed the ChangeNOW exchange widget on coin detail pages with safe fallbacks
  for unsupported assets.
- Support Telegram deep links, referrals, inline sharing, and bot-delivered
  alerts as core user journeys.
- Give TONBANKCARD operators admin controls for providers, feature flags, legal
  copy, curated TON assets, and operational health.
- Keep secrets, AI provider keys, bot tokens, and premium entitlement checks on
  the server side.

## Non-Goals

- V2 MVP is not a trading terminal, order book, custody wallet, tax tool, or
  portfolio accounting product.
- V2 MVP will not issue a token, distribute crypto assets, or handle private
  keys.
- AI output will not recommend buying, selling, holding, leverage, or position
  sizing.
- The first release will not require users to connect a TON wallet to use market
  pulse, search, coin detail, watchlists, or basic alerts.
- The first release will not attempt to replace CoinGecko as the market data
  source of record.
- Gamification, premium subscriptions, advanced screeners, and group rooms are
  planned after the MVP unless explicitly pulled forward by product leadership.
- Admin users will not edit arbitrary server code or see saved secret values
  after submission.

## Personas

| Persona | Description | Primary jobs | Success signal |
| --- | --- | --- | --- |
| Casual market viewer | A visitor from search, social, or a shared link who wants quick market context without signing in. | Check prices, trend direction, top movers, and a single coin page. | Finds a relevant coin or market summary within one session. |
| TON user | A Telegram-native user interested in TON, jettons, TON DeFi, stablecoins, and wallet-aware context. | Discover TON ecosystem assets, track Toncoin, open Telegram links, and later connect TON Connect. | Adds a TON asset to watchlist or returns through the bot. |
| Active trader | A power user who compares movers, charts, market cap, volume, sentiment, and alerts. | Search quickly, inspect coin detail, configure alerts, compare charts, and use the swap widget. | Creates a watchlist, alert, or repeated coin-detail session. |
| Telegram group admin | A community operator who wants useful market content for a group without spam. | Share market cards, use inline coin cards, open group-specific context, and start discussions. | Shares a card or opens a group-context Mini App session. |
| TONBANKCARD operator | An internal admin responsible for product configuration, providers, content, and support. | Manage feature flags, providers, curated TON lists, legal copy, alerts, and operational status. | Resolves support/configuration tasks without deployment. |

## Primary User Journeys

### Market pulse

1. User opens the public homepage or Mini App default route.
2. The product shows global market stats, TON ecosystem pulse, top gainers,
   top losers, trending coins, and data freshness.
3. User can search, open a coin, add a watched coin, share the pulse, or inspect
   the full table.

MVP requirement: Market pulse must work for anonymous website users and Telegram
users without requiring a wallet, premium plan, or AI provider.

### Coin detail

1. User opens a coin from search, market pulse, watchlist, alert, bot command, or
   shared deep link.
2. The page shows price, 24h change, chart, market stats, links, freshness,
   watchlist action, alert action, share action, and exchange widget area.
3. For Toncoin and supported assets, the ChangeNOW widget opens with partner
   configuration and selected-asset defaults.
4. Unsupported widget assets show a safe fallback message and preserve market
   information.

MVP requirement: V1 coin pages remain reachable through public URLs while V2 adds
the new interaction layer.

### Watchlist

1. User taps add from market pulse, search results, table rows, or coin detail.
2. Anonymous website users store the list locally.
3. Trusted Telegram users sync through server state, with Telegram storage used
   where appropriate.
4. Returning users see watchlist preview on market pulse and a dedicated
   watchlist route.

MVP requirement: A user can add the first coin in under 10 seconds and still see
it after reload.

### Alerts

1. User opens alert creation from coin detail or watchlist.
2. User chooses a simple trigger: price crosses, percent move, or volume spike.
3. The backend evaluates active rules and sends Telegram bot notifications.
4. The alert message deep-links back to the relevant Mini App or website route.

MVP requirement: Basic Telegram alert delivery works before advanced sentiment,
rank-change, and TON ecosystem alerts.

### AI insights

1. User opens market pulse, coin detail, watchlist digest, or alert explanation.
2. Product displays a short factual AI card only when provider, cache, and safety
   validation are available.
3. AI cards include data freshness, confidence, and "not financial advice"
   language.
4. Users can mark cards helpful, stale, wrong, or unsafe.

MVP requirement: AI can be disabled without breaking core market routes.

### Swap widget

1. User opens coin detail and scrolls to the exchange area.
2. Product builds the ChangeNOW iframe parameters from the selected coin when the
   asset is supported.
3. Toncoin uses the initial partner defaults from the roadmap source:
   `from=ton`, `to=usdtton`, `link_id=3cc0024a18fd9d`,
   `primaryColor=1bb2da`, `backgroundColor=f6fafd`.
4. Unsupported coins show a fallback and do not display a broken iframe.

MVP requirement: Widget loading must not block the rest of the coin detail page.

### Share card

1. User taps share from market pulse, coin detail, watchlist, alert, or AI card.
2. Product creates a compact card with title, price or summary, freshness, route,
   and disclaimers when needed.
3. Telegram contexts use Telegram share APIs or `startapp` payloads where
   available.
4. Public web contexts use normal web share or copy-link fallback.

MVP requirement: Coin detail and market pulse have a share path before advanced
card rendering lands.

### Referral landing

1. A new user opens a Telegram `startapp` deep link or public referral link.
2. Product records route, campaign, inviter, and context after trusted session
   creation.
3. User lands on the intended market pulse, coin, watchlist preview, or campaign
   page.
4. Attribution is stored once and does not override later user identity data.

MVP requirement: Referral payload parsing and route landing are designed before
campaign automation or rewards are enabled.

### Admin configuration

1. TONBANKCARD operator signs in to the admin panel with an authorized role.
2. Operator views provider health, cache status, feature flags, legal copy,
   curated TON assets, widget configuration, and audit logs.
3. Operator changes allowed settings without seeing stored secret values.
4. Changes are audit logged and can be rolled back where practical.

MVP requirement: Feature flags must be available for AI, alerts, ChangeNOW,
TON Connect, referrals, gamification, and premium features before public launch.

## Permissions and Roles

| Role | Access | Notes |
| --- | --- | --- |
| Anonymous website visitor | Public market pulse, coin detail, search, exchanges, legal pages, local watchlist, share links. | No server-trusted identity. Rate limits apply by IP/session. |
| Trusted Telegram user | Mini App routes, server-synced watchlist, alerts, referrals, bot-delivered messages, Telegram share flows. | Identity must come from server-validated Telegram `initData`. |
| Telegram group context | Group market cards, shared group context, group-specific deep links where supported. | Must keep group state separate from personal state. |
| Premium user | Higher watchlist or alert limits, advanced ranges, AI digest frequency, or priority refresh after payment launch. | Backend enforces entitlements. |
| Support operator | Read-only user/support views, provider status, audit trail, and limited diagnostics. | No secret access and no destructive settings. |
| Content operator | Curated TON assets, legal copy drafts, link copy, share-card templates, and feature-copy controls. | Changes require audit logging. |
| Admin operator | Provider settings, feature flags, cache controls, alert thresholds, widget config, roles, and operational toggles. | Saved secrets are write-only after entry. |

## Success Metrics

| Area | MVP metric | Target direction |
| --- | --- | --- |
| Activation | Homepage or Mini App session reaches first meaningful market content. | Median mobile first meaningful content under 2.5 seconds on cached data. |
| Discovery | Search result click, coin detail open, or TON ecosystem route open. | Increase repeat route opens from search and Telegram deep links. |
| Personalization | Watchlist add, alert create, or return to watchlist. | At least one personalized action in the first qualified Telegram session. |
| Retention | Telegram user returns within 7 days. | Improve D7 return rate after alerts and watchlist launch. |
| Virality | Share card sent, copied, or opened through referral/deep link. | Track card opens and invite attribution without forcing spam loops. |
| Trust | AI feedback, legal copy visibility, stale-data notices, alert unsubscribe rate. | Low unsafe/wrong AI feedback and clear unsubscribe behavior. |
| Operations | Provider uptime, cache hit rate, API error rate, bot delivery failures. | Operators can diagnose provider or cache failures from admin views. |
| Revenue readiness | Premium view exposure, checkout start, entitlement check success. | Validate monetization funnel after Telegram Stars work begins. |

## Release Phases

### MVP

MVP should be small enough to ship the public website and Mini App foundation
without waiting for advanced AI, TON Connect, gamification, or paid features.

- Public website shell with market pulse, search entry, coin detail, legal pages,
  and preserved route compatibility for V1 market pages.
- Telegram Mini App shell with theme, viewport, safe-area, back navigation, and
  browser fallback behavior.
- Backend API foundation for market data gateway, trusted Telegram session
  validation, watchlist storage, basic alerts, analytics events, and feature
  flags.
- Watchlist add/remove and persistence for anonymous website and trusted
  Telegram sessions.
- Basic alert creation and bot delivery for price or percent movement.
- Coin detail page with ChangeNOW widget integration and safe unsupported-asset
  fallback.
- AI placeholder or disabled state with no broken UI when providers are not
  configured.
- Admin basics for feature flags, provider health, ChangeNOW config, curated TON
  assets, and audit logs.

### Beta

- AI market pulse, coin insight, watchlist digest, and alert explanation cards.
- TON ecosystem page, curated TON asset taxonomy, and TON filters in search.
- Referral-aware deep links, share cards, inline-mode coin cards, and campaign
  attribution.
- Advanced chart ranges, screener filters, and saved presets.
- More alert triggers, quiet hours, alert limits, and delivery diagnostics.
- Expanded admin content controls, support workflows, and cache operations.

### Post-Launch

- TON Connect wallet profile features and read-only portfolio placeholders.
- Telegram group rooms, group polls, and group-specific watchlists.
- Gamification achievements and streaks with dismissible prompts.
- Telegram Stars premium subscriptions and backend entitlements.
- Load testing, performance budgets in CI, Mini App Store assets, and public
  launch rollout automation.

## Information Architecture

The information architecture separates public web, Mini App, bot, and admin
routes while keeping shared route concepts stable. Public URLs must remain useful
without Telegram. Telegram routes may use compact controls, native back behavior,
and `startapp` payloads, but should resolve to the same content concepts.

### Public website routes

| Route | Purpose | MVP |
| --- | --- | --- |
| `/` | Market pulse homepage with global stats, TON pulse, movers, trending, watchlist preview, and search. | Yes |
| `/markets` | Full cryptocurrency market table preserved from V1 for power users. | Yes |
| `/currency/:id` | SEO-compatible V1 coin detail route, redirected or rendered by V2 coin detail. | Yes |
| `/coins/:id` | Canonical V2 coin detail route with chart, stats, widget, watchlist, alert, and share actions. | Yes |
| `/ton` | TON ecosystem overview and curated TON assets. | Beta |
| `/watchlist` | Anonymous local watchlist or Telegram-linked watchlist when authenticated. | Yes |
| `/alerts` | Alert list and management, with sign-in or Telegram prompt if needed. | Yes |
| `/exchanges` | Exchange list compatibility route from V1. | Yes |
| `/exchange/:id` | Exchange detail compatibility route from V1. | Beta |
| `/screener` | Advanced filters for market cap, movement, volume, category, TON tag, and sentiment. | Beta |
| `/share/:payload` | Public landing route for share cards and referral links. | Yes |
| `/ref/:campaign` | Public referral campaign landing before Telegram session attribution. | Beta |
| `/about` | TONBANKCARD product and company context. | Yes |
| `/support` | Support, contact, and bot help entry point. | Yes |
| `/terms` | Terms and risk disclosures. | Yes |
| `/privacy-policy` | Privacy policy covering Telegram, analytics, AI, alerts, and wallet data. | Yes |
| `/cookies-policy` | Cookie and local storage policy. | Yes |

Desktop web navigation should use a compact header plus dense market content.
Mobile web navigation should use top search and bottom tabs for Home, Search,
Watchlist, Alerts, and More. The legacy side drawer should not be the primary
mobile pattern in V2.

### Telegram Mini App routes

| Route | Purpose | MVP |
| --- | --- | --- |
| `/app` | Mini App market pulse default route. | Yes |
| `/app/search` | Compact search and command palette. | Yes |
| `/app/coin/:id` | Coin detail optimized for Telegram viewport, native back behavior, and share actions. | Yes |
| `/app/watchlist` | Personalized watchlist. | Yes |
| `/app/alerts` | Alert list, create, pause, edit, and delete. | Yes |
| `/app/alert/:id` | Alert detail opened from bot message. | Yes |
| `/app/ton` | TON ecosystem view. | Beta |
| `/app/share/:payload` | Share card or referral payload landing route. | Yes |
| `/app/referral/:payload` | Referral attribution landing route. | Beta |
| `/app/settings` | Currency, language, theme behavior, alert quiet hours, and privacy controls. | Yes |
| `/app/premium` | Premium limits and Telegram Stars upgrade path. | Post-launch |

Telegram webview navigation should use bottom tabs for Home, Search, Watchlist,
Alerts, and Settings. Coin detail, alert detail, and create flows should use the
Telegram BackButton instead of duplicating browser-style breadcrumbs.

### Bot flows

| Flow | Entry | Result | MVP |
| --- | --- | --- | --- |
| Start | `/start` | Welcome, open Mini App, explain market pulse and watchlist. | Yes |
| Referral start | `/start <payload>` or `startapp=<payload>` | Validate payload, create attribution, open target route. | Yes |
| Market snapshot | `/market` | Send market pulse card with Mini App deep link. | Yes |
| Coin lookup | `/coin <symbol>` or inline query | Return coin card and Mini App route. | Beta |
| Watchlist | `/watchlist` | Open watchlist route or explain how to add coins. | Yes |
| Alerts | `/alerts` | Open alert management route. | Yes |
| Alert delivery | Scheduled bot message | Notify trigger and deep-link to alert detail or coin page. | Yes |
| Settings | `/settings` | Open settings route. | Yes |
| Help/support | `/help` | Show support, disclaimers, and product commands. | Yes |
| Group context | Group-launched Mini App or inline mode | Share market card or group-specific context. | Beta |

Bot messages must stay concise, include unsubscribe or settings paths for alerts,
and avoid investment advice.

### Admin routes

| Route | Purpose | MVP |
| --- | --- | --- |
| `/admin` | Admin dashboard with service health, recent errors, and key metrics. | Yes |
| `/admin/login` | Admin authentication entry. | Yes |
| `/admin/providers` | CoinGecko, Groq, Upstash, bot, and ChangeNOW provider status and settings. | Yes |
| `/admin/feature-flags` | Enable or disable AI, alerts, ChangeNOW, referrals, TON Connect, gamification, and premium features. | Yes |
| `/admin/ton-assets` | Curate TON assets, categories, verified tags, and featured lists. | Yes |
| `/admin/legal-copy` | Manage legal copy drafts and release status. | Beta |
| `/admin/alerts` | Alert thresholds, limits, queue status, and delivery diagnostics. | Beta |
| `/admin/ai` | Prompt versions, model settings, safety validation status, and feedback review. | Beta |
| `/admin/cache` | Cache keys, TTL status, purge tools, and stale-data mode. | Beta |
| `/admin/users` | Support view for Telegram users, watchlists, alert counts, and entitlements. | Beta |
| `/admin/audit-log` | Audit log for admin changes and security review. | Yes |
| `/admin/roles` | Admin roles and permissions. | Beta |

Admin routes must never be exposed to anonymous users or trusted Telegram user
sessions. Admin authentication and audit logging are required before any
production provider controls are enabled.

## MVP Scope Guardrails

- Ship the market pulse, coin detail, search, watchlist, basic alerts, share
  links, and widget integration before advanced intelligence features.
- Keep AI optional and default-safe. Core market data routes must work while AI
  is disabled, unavailable, rate-limited, or failing validation.
- Keep TON Connect out of the MVP critical path. TON ecosystem content can launch
  before wallet features.
- Keep gamification out of MVP except for analytics hooks that allow future
  achievements.
- Preserve V1 public URLs until replacements have redirects, metadata, and
  equivalent content.
- Do not require a Telegram session for public market browsing.
- Do not require a public website account system in MVP. Telegram trusted
  sessions provide the first authenticated identity layer.
- Prefer feature flags over partial removals when uncertainty remains.

## Data, Privacy, and Compliance Requirements

- Validate raw Telegram `initData` server-side before trusting user identity,
  premium status, language, referrals, or chat context.
- Do not expose CoinGecko, Groq, Upstash, bot, database, or ChangeNOW secret
  configuration to browser JavaScript.
- Store only the Telegram, alert, watchlist, referral, wallet, and analytics data
  required for product behavior.
- Avoid logging raw secrets, raw AI prompts containing sensitive user data,
  wallet addresses beyond required support diagnostics, or private bot payloads.
- Every AI insight, alert explanation, share card, and swap-adjacent surface must
  include risk framing and avoid financial advice.
- Market data and AI outputs must show freshness or stale-state indicators.
- Users must be able to mute alerts and reach privacy/support information from
  website and bot surfaces.

## Analytics Events

MVP implementation tasks should use this event taxonomy as the first baseline.

| Event | Required properties |
| --- | --- |
| `market_pulse_viewed` | surface, freshness_age, telegram_context |
| `search_opened` | surface, trigger |
| `search_result_selected` | result_type, symbol_or_id, rank, surface |
| `coin_detail_viewed` | coin_id, symbol, source_route, freshness_age |
| `watchlist_added` | coin_id, surface, storage_mode |
| `watchlist_removed` | coin_id, surface, storage_mode |
| `alert_created` | coin_id, trigger_type, delivery_channel |
| `alert_delivered` | alert_id, trigger_type, delivery_status |
| `ai_card_viewed` | card_type, provider, model, prompt_version, freshness_age |
| `swap_widget_opened` | coin_id, from_asset, to_asset, widget_supported |
| `share_started` | card_type, route_type, surface |
| `share_opened` | payload_type, campaign, inviter_present |
| `referral_attributed` | campaign, inviter_id_hash, landing_route |
| `admin_setting_changed` | setting_group, actor_id, audit_id |

Sensitive values should be hashed or omitted according to the analytics and
privacy baseline in roadmap issue #8.

## Open Decisions

| Decision | Owner | Deadline | Default if unresolved |
| --- | --- | --- | --- |
| Confirm canonical V2 route names for `/currency/:id` versus `/coins/:id`. | Product owner and tech lead | 2026-05-06 | Preserve `/currency/:id` and add `/coins/:id` as canonical with redirects later. |
| Choose final frontend migration path: incremental PHP/Vue evolution, parallel V2 routes, or rewrite with the requested Alpine/Tailwind direction. | Tech lead | 2026-05-08 | Use parallel V2 routes until an architecture decision record is approved. |
| Approve TONBANKCARD logo, favicon, colors, social links, and legal copy owners. | Brand owner and legal owner | 2026-05-08 | Keep placeholders flagged as not launch-ready. |
| Confirm CoinGecko plan, data attribution requirements, and cache TTL policy. | Operations owner | 2026-05-10 | Use conservative cache TTLs and display data freshness on every market surface. |
| Confirm AI provider list and first Groq model for MVP trials. | Product owner and AI owner | 2026-05-13 | Ship AI disabled by default with placeholder states. |
| Confirm ChangeNOW supported asset mapping and fallback copy. | Product owner and operations owner | 2026-05-13 | Enable Toncoin defaults only and show fallback for other assets. |
| Decide whether beta includes Telegram inline mode before public launch. | Product owner | 2026-05-15 | Keep inline mode in beta, not MVP. |
| Confirm premium packaging and Telegram Stars timing. | Business owner | 2026-05-20 | Leave premium routes hidden behind feature flags. |

## Acceptance Criteria Mapping

| Issue #4 acceptance criterion | PRD coverage |
| --- | --- |
| PRD includes goals, non-goals, user journeys, permissions, success metrics, and release phases. | Covered by Goals, Non-Goals, Primary User Journeys, Permissions and Roles, Success Metrics, and Release Phases. |
| Information architecture covers public website routes, Mini App routes, bot flows, and admin routes. | Covered by Information Architecture and its four route/flow tables. |
| MVP scope is small enough to ship without blocking future AI, TON, and gamification work. | Covered by Release Phases and MVP Scope Guardrails. |
| Open decisions are captured with owners and deadlines. | Covered by Open Decisions. |
