# V2 Search and Routing Behavior

Date: 2026-04-30

This note records the verified V1 behavior that V2 must preserve until the
product requirements explicitly replace it.

## Current V1 Baseline

- The global search is an autocomplete in the top app bar, not a standalone
  route. It is effectively hidden until the user focuses the search field or
  begins typing.
- On focus or text input, the client loads the public search index from
  `https://localstorage.one/crypto/data/search.json`. The original CoinGecko
  `/search` API path is left in source as a disabled fallback because browser
  CORS blocks it.
- Search input is trimmed and matched case-insensitively against each result's
  `name`, `symbol`, and `id`.
- Empty search text shows the first configured currency and exchange matches,
  grouped under `Currencies` and `Exchanges`.
- Search results are limited to 10 currencies and 10 exchanges per query.
- Selecting a currency navigates to the existing route named `currency`, which
  renders `/currency/:id`.
- Selecting an exchange navigates to the existing route named `exchange`, which
  renders `/exchange/:id`.
- Selecting the currently open route does not push a duplicate navigation.

## V2 Compatibility Expectation

- Preserve `/`, `/currency/:id`, `/exchanges`, and `/exchange/:id` while V2 is
  introduced. The PRD may add `/coins/:id` later, but compatibility routes must
  keep rendering or redirecting deliberately.
- Keep global search available from market, coin detail, and exchange contexts.
- Keep currency result selection opening a coin detail page without requiring
  sign-in or a Telegram session.
- Prefer a server-owned search endpoint in V2 so provider keys, CORS behavior,
  request shaping, and caching are controlled by TONBANKCARD infrastructure.
- If V2 changes ranking, grouping, or canonical route names, document the change
  and keep regression coverage for old route compatibility.
- Add `/crypto-exchange` as the first-party partner exchange route while keeping
  `/exchange/:id` for exchange venue detail pages.

## V2 Smart Search UI

- The top app bar exposes smart search as a visible backend-backed command
  palette on desktop and as an icon-triggered compact dialog on mobile and
  Telegram Mini App surfaces.
- Keyboard users can open desktop search with `Ctrl+K`, `Cmd+K`, or `/`, then
  navigate results with the existing Vuetify autocomplete keyboard behavior.
- Empty searches combine local recent selections with backend quick actions.
  Non-empty searches render backend result groups for coins, exchanges, TON
  assets, categories, and quick actions.
- Selection uses the result `route` payload for Vue Router navigation, stores a
  bounded local recent-search list, and emits the `search_result_selected`
  analytics event without sending the raw query.
- For matched coin and TON asset queries, the API inserts a first-party
  `Exchange ...` quick action after the matched asset. The action opens
  `/crypto-exchange` with sanitized `from`, `to`, and `asset` query parameters
  so the ChangeNOW partner widget preselects the searched crypto pair, defaulting
  to TON into USDT on TON.
- Error and empty states stay inside the autocomplete or compact dialog so
  mobile search does not overlap Telegram safe areas, bottom navigation, or the
  virtual keyboard flow.

## First-Party Exchange Route

- `/crypto-exchange` renders the TONBANKCARD exchange surface with the
  ChangeNOW partner iframe and stepper connector.
- The route accepts `from`, `to`, and `asset` query parameters. Currency codes
  are reduced to lowercase alphanumeric ChangeNOW symbols before they are passed
  into the widget URL.
- The default pair is `from=ton&to=usdtton`, matching the TONBANKCARD partner
  widget configuration.

## Regression Coverage

`npm run test:smoke` stubs the search index and CoinGecko responses, then verifies:

- the currencies list renders with the configured `per_page` request,
- the coin detail route renders `/currency/bitcoin`,
- the default chart tab loads chart data,
- the ChangeNOW widget replaces the legacy converter, uses TON defaults for
  Toncoin, prefills supported assets such as Bitcoin, and shows a safe fallback
  for unsupported assets,
- the exchanges list renders with the configured `per_page` request,
- selecting `Toncoin` from global search navigates to `/currency/toncoin`.
- `Ctrl+K` focuses desktop search, result groups render for every smart-search
  type, local recent searches appear after selection, and compact mobile search
  opens without horizontal overflow.
- selecting the smart-search exchange action opens `/crypto-exchange` and the
  widget receives `from=ton&to=usdtton` for the searched TON pair.
