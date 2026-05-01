# TONBANKCARD V2 Charting And Market Visualization

Date: 2026-05-01

Issue: [#25](https://github.com/labtgbot/marketcap/issues/25)

## Rendering Decision

The current Vue/Vuetify route shell keeps ECharts for coin and exchange charts.
It is already vendored in `assets/vendor/echarts`, matches the existing option
objects, and avoids adding Chart.js while the legacy shell is still active.

Chart.js remains the target for future parallel V2 routes described in
`docs/adr/0001-v2-migration-architecture.md`, where route-level modules can be
split cleanly around Alpine and Tailwind. Mixing Chart.js into the current coin
detail tab would increase payload and ownership overlap without removing the
existing ECharts dependency.

## Implementation

ECharts is no longer loaded by the global script list. The browser receives
`GeckoClient.assets.echartsUrl`, and `GeckoClient.loadECharts()` injects the
script only when a chart component needs it. This lazy-load boundary keeps chart
code out of non-chart first screens. The bundled dark theme registers
immediately when ECharts is already present or defers registration until the
lazy loader finishes.

The coin detail chart supports:

- price
- volume
- market cap
- dominance against the currently selected global market cap
- relative performance from the first point in the selected range
- 1D, 7D, 1M, 3M, 6M, 1Y, and all-time ranges

The component keeps a stable chart shell while data and chart code load, uses
the shared `.tbc-skeleton` loading state, and reads `/api/market/*` freshness
metadata through `CoinGecko.metaGet`. The stale cache fallback data stays visible
with a warning message instead of replacing the chart with a blank state.

Market chart request failures are isolated to the chart component. The coin
header, converter, stats, market tab, and historical tab continue to render, and
the chart area shows a retry action.

## Accessibility

Each rendered chart exposes `role="img"` and is associated with an accessible
summary generated from the active metric and range. The accessible summary
values are also shown as compact stat cells for sighted users: start, end,
high, and low.

## Tests

Regression coverage lives in `tests/chart-visualization-check.sh` and browser
behavior is covered by `tests/browser-smoke.js`:

```sh
npm run test:charts
npm run test:smoke
```
