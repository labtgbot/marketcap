# TONBANKCARD V2 Responsive Design System

Date: 2026-04-30

Issue: [#19](https://github.com/labtgbot/marketcap/issues/19)

This document defines the visual foundation for TONBANKCARD V2 across the
current Vue/Vuetify shell, future parallel V2 routes, public desktop browsers,
mobile browsers, and Telegram Mini App webviews. The tokens are implemented in
`assets/css/style.css`, mapped into Vuetify through `config/vuetify.php`, and
adapted at runtime from Telegram WebApp theme parameters in `dev/js/src`.

## TONBANKCARD brand tokens

| Token | Light | Dark | Usage |
| --- | --- | --- | --- |
| `--tbc-brand-ton` | `#1BB2DA` | `#54C8E8` | Primary actions, active tabs, links, key chart accents. |
| `--tbc-brand-ink` | `#0B1020` | `#0B1020` | Brand anchor, Telegram header fallback, high-contrast text. |
| `--tbc-brand-gold` | `#F2B84B` | `#FFD166` | Focus ring, warning emphasis, premium-ready accents. |
| `--tbc-surface-canvas` | `#F6FAFD` | `#0B1020` | Page background. |
| `--tbc-surface-panel` | `#FFFFFF` | `#121A2B` | Cards, sheets, tables, dialogs. |
| `--tbc-surface-panel-muted` | `#EEF6FB` | `#18233A` | Table hover, subtle fills, skeleton base. |
| `--tbc-text-primary` | `#0B1020` | `#F5F9FF` | Primary copy and dense table values. |
| `--tbc-text-secondary` | `#4C6178` | `#A8B8CC` | Metadata, labels, helper text. |

The palette deliberately combines TON cyan, dark ink, white and blue-gray
surfaces, green/red market semantics, and gold focus or warning states so the
product does not become a one-note blue interface.

## Telegram Theme Parameters

The browser adapter reads `Telegram.WebApp.themeParams` when Telegram is present
or when the runtime profile is `telegram`. Supported Telegram theme parameters:

| Telegram parameter | CSS variable | Vuetify role |
| --- | --- | --- |
| `bg_color` | `--tbc-tg-bg` | `background` |
| `secondary_bg_color` | `--tbc-tg-secondary-bg` | `surface` |
| `text_color` | `--tbc-tg-text` | `text_primary` |
| `hint_color` | `--tbc-tg-hint` | `text_muted` |
| `link_color` | `--tbc-tg-link` | `info` |
| `button_color` | `--tbc-tg-button` | `primary` |
| `button_text_color` | `--tbc-tg-button-text` | action text |
| `header_bg_color` | `--tbc-tg-header-bg` | native header and app bar |
| `bottom_bar_bg_color` | `--tbc-tg-bottom-bar-bg` | bottom bar surface |
| `destructive_text_color` | `--tbc-tg-destructive-text` | negative/error emphasis |

The adapter listens for Telegram `themeChanged`, updates CSS custom properties,
syncs Vuetify light/dark mode from `Telegram.WebApp.colorScheme`, and calls
native color APIs such as `setHeaderColor`, `setBackgroundColor`, and
`setBottomBarColor` when they exist. Normal browsers keep the local stored theme
preference and do not require the Telegram SDK.

## Semantic market colors

| Token | Light | Dark | Usage |
| --- | --- | --- | --- |
| `--tbc-market-up` | `#12A978` | `#39D98A` | Positive change, high trust score, buy direction. |
| `--tbc-market-flat` | `#C77800` | `#E6A23C` | Moderate trust score, neutral warnings. |
| `--tbc-market-down` | `#D84A4A` | `#FF6B6B` | Negative change, low trust score, sell direction. |

Market colors must not be used as the only state indicator. Tables and cards
should keep icons, text labels, or score text where the current component
already provides them.

## Typography

- Keep Roboto as the current V1 font until the V2 route shell introduces a
  separate Tailwind font stack.
- Use dense product typography: compact labels, normal body text, and restrained
  page headings. Do not use hero-scale text inside dashboards, cards, sidebars,
  dialogs, tables, or chart panels.
- Letter spacing is `0` across controls and table headers unless a vendor
  component requires its own internal rendering.

## Spacing

- Base spacing tokens are `4px`, `8px`, `12px`, `16px`, `24px`, and `32px`.
- Cards, dialogs, controls, and tables use an `8px` or smaller radius.
- Repeated market rows should stay dense, with horizontal scrolling allowed only
  inside table wrappers, stats bars, or intentionally scrollable chip rails.

## Tables

Tables are optimized for repeated scanning:

- Header labels are small, strong, and uppercase.
- Values stay nowrap where comparison matters.
- Hover and active states use the muted panel token, not decorative gradients.
- Mobile table cells reduce horizontal padding so the 360px viewport does not
  create page-level overflow.

## Cards

Cards are for actual grouped content, dialogs, and repeated market objects. Page
sections should remain normal page layout, not cards inside cards. Card radius is
`8px`, panel backgrounds use `--tbc-surface-panel`, and borders use
`--tbc-surface-border`.

## Buttons

Buttons use the TON primary color for clear actions, semantic market colors for
buy/sell style states, and icon-only controls where the action is common. Icon
buttons need accessible labels. Focus uses `focus-visible` with the gold focus
ring so keyboard users can see the active control without adding noisy borders
for mouse users.

## Tabs

Tabs are compact and unframed. Active tab color follows the primary brand token
or Telegram `button_color`. Minimum touch target width is 44px; labels should
wrap or shorten before they create horizontal page overflow.

## Forms

Search, selects, radio groups, and converter inputs keep dense sizing with a
minimum control height of 40px. Forms must expose screen reader labels through
native labels or `aria-label` when the visual label is compact.

## Charts

Charts inherit light/dark mode from the app theme and should use market semantic
colors for up/down series. Chart containers keep stable dimensions to avoid
layout jumps while data loads. Tooltips must use formatted values and dates from
the existing formatter helpers.

## Skeleton states

Use `.tbc-skeleton` for loading placeholders in V2 route work. It uses muted
panel color and a restrained shimmer. Under `prefers-reduced-motion: reduce`,
the shimmer is disabled.

## Safe-Area And Bottom Bar Rules

The viewport includes `viewport-fit=cover`. Safe-area spacing tokens map to
`env(safe-area-inset-*)` so Telegram fullscreen and iOS browser chrome do not
cover content.

Bottom bar rules:

- Use `.tbc-bottom-bar` or Vuetify `.v-bottom-navigation` for fixed bottom
  navigation.
- Reserve `56px + safe-area` through `.tbc-has-bottom-bar .v-main`.
- Use `--tbc-tg-bottom-bar-bg` when Telegram provides it, otherwise use the
  normal panel surface.

## Responsive Rules

The current V1 routes remain desktop-dense and scan-friendly up to wide desktop
widths. Desktop tables can use broad max widths, but the main page must not use
oversized marketing composition.

At 360px width:

- Page-level horizontal overflow is disallowed.
- The top search field shrinks around navigation and action icons.
- Table padding and card padding compress before content clips.
- Stats and dense market rails scroll internally instead of widening the page.

## Accessibility States

- Keyboard focus is visible through `focus-visible` on buttons, links, tabs,
  list items, and text inputs.
- Compact icon and search controls expose screen reader labels with `aria-label`
  or equivalent text.
- `.tbc-sr-only` is available for future V2 route labels that should be read by
  assistive technology without taking visual space.
- `prefers-reduced-motion` disables decorative animation and keeps the product
  usable for motion-sensitive users.

## Acceptance Criteria Mapping

| Issue #19 acceptance criterion | Implementation |
| --- | --- |
| Design tokens are documented and implemented in the frontend. | This document, `assets/css/style.css`, and `config/vuetify.php`. |
| UI components adapt to Telegram light and dark themes. | `dev/js/src/initial.js` maps Telegram theme parameters and `dev/js/src/vm.js` syncs Vue/Vuetify theme state. |
| Mobile layouts fit 360px width without horizontal overflow. | CSS 360px breakpoint and browser smoke coverage. |
| Desktop layouts remain information-dense and scan-friendly. | Table, card, tab, typography, and spacing rules preserve dense market layouts. |
