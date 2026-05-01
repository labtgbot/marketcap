# TONBANKCARD V2 TON Connect Wallet Profile

Issue: [#30](https://github.com/labtgbot/marketcap/issues/30)
Date: 2026-05-01

## Objective

Stage 4 adds an optional wallet profile surface so users can connect a TON
wallet for wallet-aware context while keeping custody and signing inside the
wallet application.

## TON Connect Boundary

Wallet connection uses TON Connect only. The browser loads the pinned
`@tonconnect/ui` package lazily after a user action and points it at:

```text
GET /tonconnect-manifest.json
```

The manifest is rendered from the active runtime base URL and includes only the
app URL, app name, icon URL, terms URL, and privacy URL. Private keys and seed phrases are never requested, accepted, stored, logged, or sent to the server.

## Wallet Profile State

The client stores a bounded local snapshot under
`TONBANKCARD:ton-connect-wallet:v1` with public address, network, wallet app
name, platform, provider, and supported wallet feature metadata. The snapshot is
used only to restore visible profile state after reload. Disconnect clears the
TON Connect session when the SDK exposes `disconnect` and removes the local
wallet profile snapshot.

## Wallet-Aware Placeholders

The `/wallet` route prepares read-only wallet-aware surfaces without server
syncing balances or transactions yet:

- Connected wallet address, network, and wallet metadata.
- Read-only watchlist context using the existing local or Telegram-backed
  watchlist snapshot.
- Portfolio placeholders for balances, jettons, and activity.
- Privacy and compliance notes linking to the public policy route.

## Telegram Mini App

The wallet profile uses the existing Telegram WebApp adapter. Telegram sessions
show the same public wallet metadata boundary as browser sessions, keep
Telegram initData validation separate from wallet connection state, and do not
request wallet secrets from the Mini App.

## Verification

Run:

```sh
npm run test:ton-connect
npm run test:smoke
```

The static check validates the manifest contract, route wiring, client adapter,
privacy boundary, source bundle registration, and documentation link. The smoke
test stubs TON Connect UI in Chromium, connects and disconnects a public wallet
profile, verifies storage clearing, and checks the wallet route in a
Telegram-like webview.
