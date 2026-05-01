# TONBANKCARD V2 TON Ecosystem Curation

Issue: [#29](https://github.com/labtgbot/marketcap/issues/29)
Date: 2026-05-01

## Objective

Stage 4 makes the TON ecosystem a first-class public surface instead of a static placeholder. The website now has a TON route, market filters, screener filters, and a backend-owned curation API that can add, update, or hide TON assets without code deployment.

## Data Model

The curation model has three stable objects:

- `categories`: named TON buckets such as `native`, `stablecoin`, `jetton`, `defi`, `wallet`, `infrastructure`, and `community`.
- `lists`: editorial groupings such as `featured`, `trending`, `stablecoins`, `defi`, `wallets`, `infrastructure`, and `community_queue`.
- `assets`: normalized TON ecosystem entries with `id`, optional CoinGecko `coin_id`, `symbol`, `category`, `tags`, `list_ids`, `verification_state`, `featured`, and safe route/link metadata.

Asset verification states are:

- `verified`: confirmed first-party or high-confidence ecosystem assets.
- `curated`: editorially accepted assets that are tracked but not treated as first-party verified.
- `unverified`: community or manually submitted assets that must remain visually distinct until reviewed.

The SQL migration adds relational tables for the same model so production can move from the JSON store to database-backed administration without changing the public contract.

## API

The public read endpoint is:

```text
GET /api/ton/assets
```

Supported filters:

- `tag`: matches asset tags and maps `ton` to `ton_ecosystem`.
- `category`: matches a category id.
- `state`: matches `verified`, `curated`, or `unverified`.
- `list`: matches a curated list id.
- `featured`: accepts `true`, `false`, `1`, or `0`.
- `q`: searches asset name, symbol, id, description, tags, and aliases.

The write endpoint is:

```text
POST /api/ton/assets
PUT /api/ton/assets
```

Writes require `TONBANKCARD_TON_CURATION_FILE` and should use `TONBANKCARD_TON_CURATION_TOKEN` outside local development. Operators send the token with `X-TONBANKCARD-TON-Curation-Token`. The API validates, normalizes, and atomically writes the JSON store.

## Search and Screener

`/api/search` now accepts `tag` and echoes the safe tag in `data.tag` and `meta.search.tag`. Curated TON entries are indexed with verification metadata, list ids, contract aliases, and category tags so searches such as `/api/search?q=TON&tag=stablecoin` can rank verified USDT on TON separately from generic Tether results.

The market table and screener use the same curation feed for TON tag filters. Assets with `verification_state=unverified` receive separate UI styling and retain their `verified=false` payload value.

## Operations

Required configuration:

```sh
TONBANKCARD_TON_CURATION_FILE=/path/to/ton-curation.json
TONBANKCARD_TON_CURATION_TOKEN=replace-with-admin-token
```

The default local profile falls back to a temp-file path so the read API always exposes built-in curated defaults. Production should set an explicit writable file path or migrate the same model to the `0005_ton_ecosystem_curation` tables.

## Acceptance Mapping

- TON ecosystem page is a first-class `/ton` route backed by `/api/ton/assets`.
- TON assets can be curated through a writable JSON store without code deployment.
- Search, markets, and screener surfaces can filter by TON tags.
- Unverified assets are visibly distinct and preserve verification metadata in API responses.

## Verification

Run:

```sh
npm run test:ton-ecosystem
```

The check exercises default assets, manual curation writes, tag filtering, smart search integration, and required route/source/template coverage.
