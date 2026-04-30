# ADR 0001: V2 Migration Architecture

Date: 2026-04-30

Status: Accepted

Issue: [#9](https://github.com/labtgbot/marketcap/issues/9)

## Context

TONBANKCARD V2 must move the current PHP-rendered Vue 2 and Vuetify Gecko
Client application toward PHP 8.1+, Alpine.js, Tailwind CDN, and Chart.js while
keeping the public market website useful during the migration. The V2 product
requirements also require two first-class surfaces:

- Public website SEO pages that remain useful to search, social sharing, and
  unauthenticated visitors.
- Telegram Mini App webviews for watchlists, alerts, sharing, referrals, and
  personalized market context.

The current app has valuable baseline behavior that should not be broken while
V2 is introduced: `/`, `/currency/:id`, `/exchanges`, `/exchange/:id`, smart
search navigation, legal pages, and the existing browser-rendered market data
experience.

## Decision

Use a parallel V2 route migration with incremental replacement after parity.

V1 remains the default implementation for existing production routes until a V2
route has equivalent content, route metadata, regression coverage, and rollback
instructions. New V2 routes are implemented as PHP-rendered route shells with
Alpine.js components, Tailwind CDN configuration, and Chart.js modules. Once a
V2 route is accepted, the matching V1 route can redirect or render through the
V2 shell deliberately instead of being removed in place.

This is a strangler-style migration, not a full rewrite and not a component by
component rewrite inside the Vue 2 shell.

## Decision Drivers

- Preserve existing public website SEO routes and user-visible V1 behavior until
  replacements are ready.
- Support Telegram Mini App webviews without forcing Telegram-specific layout,
  theme, and back-button behavior into the legacy desktop drawer shell.
- Move new work away from Vue 2 and Vuetify 2 without creating a single large
  rewrite branch.
- Keep PHP templates, runtime configuration, and server-side routing as the
  stable deployment base.
- Allow AI, alerts, ChangeNOW, referrals, TON Connect, gamification, premium
  features, and V2 routes to be controlled independently by feature flags.
- Keep provider secrets, bot tokens, and future CoinGecko or Groq keys on the
  server side.

## Options Considered

### Incremental replacement inside the current Vue shell

This option would replace Vue components one by one while keeping
`views/app.php`, `templates/routes`, `templates/components`, and the current
Vue Router app as the primary shell.

Pros:

- Smallest first code change.
- Existing route templates, stats bar, search bar, and charts stay in place.
- Lowest short-term routing disruption.

Cons:

- New Alpine.js behavior would be embedded inside a Vue 2 lifecycle, which
  creates duplicate state management and unclear ownership.
- Tailwind utility classes would mix with Vuetify layout assumptions and make
  visual parity harder to reason about.
- Telegram Mini App navigation, safe areas, native back behavior, and compact
  mobile flows would remain coupled to a desktop-oriented V1 shell.
- Vue 2 and Vuetify 2 remain on the critical path for every V2 feature.

Decision: rejected as the primary migration path. It remains acceptable only for
small compatibility patches to V1 while V2 routes are being built.

### Parallel V2 routes

This option adds V2 route shells beside V1, using PHP templates and the requested
frontend stack for new routes while preserving V1 until replacement parity.

Pros:

- Existing V1 routes and SEO pages remain stable.
- V2 can use separate public website and Telegram layouts from the start.
- Alpine.js, Tailwind CDN, and Chart.js have clear ownership and do not depend on
  the legacy Vue bundle.
- Each route can be released, tested, redirected, or rolled back independently.
- It matches the PRD default of preserving `/currency/:id` while adding
  `/coins/:id` as the V2 canonical coin detail route.

Cons:

- Some route concepts, visual components, and market data mappings are duplicated
  during the migration.
- Shared analytics, watchlist, alert, and search behavior must be defined in
  server-side contracts to avoid drift.
- The router needs explicit precedence rules so V2 paths do not accidentally
  shadow V1 paths.

Decision: accepted.

### Full rewrite

This option would replace the current app with a new PHP, Alpine.js, Tailwind,
and Chart.js implementation before shipping any V2 route.

Pros:

- Cleanest final code shape.
- No long period with two frontend stacks.
- Easier to impose a new design system from the first commit.

Cons:

- Highest risk to existing public routes and visible website behavior.
- Blocks product progress until all core market, search, legal, and chart flows
  are rebuilt.
- Makes rollback difficult because V1 would be removed before V2 proves parity.
- Increases launch risk for Telegram Mini App work, API gateway work, and SEO
  route compatibility at the same time.

Decision: rejected.

## Folder Structure

Future implementation PRs should create folders only when they add the related
runtime code. The target organization is:

```text
config/
  v2.php                         # V2 route, feature, CDN, and UI defaults.
  routes-v2.php                  # Public and Telegram V2 route registry.
templates/
  v2/
    layouts/
      public.php                 # Public website SEO shell.
      telegram.php               # Telegram Mini App shell.
    partials/
      head-meta.php              # Titles, canonical URLs, Open Graph, JSON-LD.
      tailwind-config.php        # Tailwind CDN runtime configuration.
      telegram-adapter.php       # Telegram SDK bootstrap with browser fallback.
    routes/
      market-pulse.php
      coin.php
      watchlist.php
      alerts.php
      share.php
    components/
      market-card.php
      coin-header.php
      chart-panel.php
      changenow-widget.php
      stale-data-banner.php
assets/
  v2/
    js/
      alpine/                    # Alpine component controllers.
      charts/                    # Chart.js setup and lazy-loaded chart modules.
      shared/                    # Fetch, formatting, telemetry, and feature helpers.
    css/
      generated/                 # Future production Tailwind build output.
api/
  v2/
    market/
    telegram/
    watchlist/
    alerts/
```

V1 paths stay in their current locations: `views/`, `templates/routes/`,
`templates/components/`, `dev/js/src/`, and `assets/js/app.js`. V2 code should
not edit the generated V1 bundle unless the issue explicitly changes V1 behavior.

## Build Strategy

- Keep the current V1 bundle strategy unchanged. `dev/js/tools/build.js` remains
  responsible for `assets/js/app.js` and `assets/js/app.min.js`.
- V2 starts with PHP-rendered HTML, small Alpine.js controllers, and lazily
  loaded Chart.js modules. Alpine component files live under
  `assets/v2/js/alpine`, and chart setup lives under `assets/v2/js/charts`.
- Tailwind CDN configuration lives in `templates/v2/partials/tailwind-config.php`
  and reads tokens from `config/v2.php`. CDN versions must be pinned, and
  production CSP/SRI rules must be documented before public launch.
- Chart.js is loaded only on routes that render charts. Market pulse and coin
  detail can share chart defaults, but each route owns its own data adapter.
- Generated V2 assets belong under `assets/v2/css/generated` or
  `assets/v2/js/generated` if a later issue introduces an offline/PWA production
  build. Generated assets must be reproducible from committed source.
- No browser bundle may contain CoinGecko, Groq, Upstash, bot, database, or
  ChangeNOW secret values.

## Routing Rules

- Preserve `/`, `/currency/:id`, `/exchanges`, `/exchange/:id`, `/about`,
  `/terms`, `/privacy-policy`, and `/cookies-policy` until a documented V2
  replacement is accepted.
- Add `/coins/:id` as the canonical V2 public coin detail route. Keep
  `/currency/:id` rendering V1 or redirecting to `/coins/:id` only after parity,
  SEO metadata, and regression coverage are in place.
- Add Telegram Mini App routes under `/app`, including `/app/search`,
  `/app/coin/:id`, `/app/watchlist`, `/app/alerts`, `/app/alert/:id`,
  `/app/share/:payload`, and `/app/settings`.
- Public website SEO pages must render meaningful PHP HTML, titles, canonical
  URLs, Open Graph metadata, and safe fallback content before Alpine.js runs.
- Telegram Mini App webviews use the Telegram layout, theme adapter, safe-area
  spacing, native back behavior, and server-validated `initData` for trusted
  identity. Public routes must still work without Telegram.
- Keep existing data routes and browser data contracts available for V1 until
  the V2 API gateway replaces them. New V2 routes should call server-owned
  `/api/v2/*` endpoints instead of calling provider APIs directly from the
  browser.
- V2 route registration must be explicit. A future dispatcher should match V2
  public and `/app` routes before falling back to the current V1 history-mode
  router.

## Risks and Mitigations

| Risk | Mitigation |
| --- | --- |
| Two frontend stacks increase maintenance cost during migration. | Keep V1 changes limited to compatibility fixes, keep V2 route ownership explicit, and retire V1 routes only after parity. |
| CDN dependencies can fail, be blocked, or conflict with production CSP. | Pin versions, document CSP/SRI requirements, keep `TONBANKCARD_CDN` controlled by runtime config, and add vendored or generated fallbacks before public launch. |
| Tailwind CDN is not a complete offline/PWA strategy. | Treat Tailwind CDN as the prototyping path and require generated CSS plus vendored runtime assets before offline/PWA release. |
| Chart.js payload can slow mobile pages. | Load Chart.js only on chart routes and split chart modules by market pulse, coin detail, and alert history. |
| V1 and V2 route semantics can drift. | Keep compatibility tests for `/currency/:id`, `/coins/:id`, search selection, and route redirects before replacing V1 pages. |
| Direct browser provider calls can leak implementation details and create rate-limit pressure. | New V2 routes use `/api/v2/*` endpoints, server-side caching, and server-side secrets. V1 direct calls remain only until their route is replaced. |
| Telegram-only assumptions can break normal browsers. | The Telegram adapter must expose browser fallback behavior for local development, public web, CI smoke tests, and shared links. |

## Rollback Strategy

- Keep existing V1 routes and generated assets intact until each V2 route is
  accepted.
- Gate V2 route exposure with runtime configuration before public launch.
- If a V2 route fails, remove it from `config/routes-v2.php` or disable its
  feature flag so the dispatcher falls back to the V1 route or a stable public
  fallback.
- Avoid destructive database migrations in route migration PRs. Data schema
  changes must be additive until watchlist, alert, and referral behavior has
  rollback coverage.
- Keep PRs route-sized where practical: shell, market pulse, coin detail,
  watchlist, alerts, and admin should be reviewed independently.

## Consequences

- V2 implementation work can begin without blocking the current public website.
- V1 defects still need small targeted fixes while V1 routes remain live.
- The project will temporarily carry Vue/Vuetify/ECharts and
  Alpine/Tailwind/Chart.js together.
- Future issues can reference this ADR when deciding whether a route should stay
  in V1, be added as a parallel V2 route, or be redirected after parity.

## Acceptance Criteria Mapping

| Issue #9 acceptance criterion | ADR coverage |
| --- | --- |
| Architecture decision record names the chosen migration path and why. | Covered by Decision, Decision Drivers, and Options Considered. |
| Folder structure, build strategy, and routing rules are documented. | Covered by Folder Structure, Build Strategy, and Routing Rules. |
| The decision supports both public website SEO and Telegram Mini App webviews. | Covered by Context, Decision Drivers, and Routing Rules. |
| Risks and rollback strategy are documented. | Covered by Risks and Mitigations and Rollback Strategy. |
