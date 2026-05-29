<?php
/**
 * -------------------------------------------------------------------------
 * GECKO CLIENT
 * -------------------------------------------------------------------------
 * Bundled SEO discovery universe.
 *
 * This curated, offline-safe dataset lists the coin and exchange identifiers
 * that should always appear in the XML sitemap, even when the live CoinGecko
 * market gateway is unavailable (self-hosted installs without an API key,
 * the `local` profile used by the test harness, or a transient upstream
 * outage). The live gateway, when reachable, is merged on top of this set so
 * the sitemap reflects the full coin universe; this file is the deterministic
 * fallback referenced by the Stage 6 readiness audit (see
 * `docs/readiness-analysis-2026.md` §3.1).
 *
 * Identifiers use CoinGecko ids so the `/coins/:id` and `/exchange/:id`
 * routes resolve to real market pages.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

return [
    // Indexable coin detail pages (`/coins/:id`).
    'coins' => [
        'bitcoin', 'ethereum', 'tether', 'ripple', 'binancecoin', 'solana',
        'usd-coin', 'cardano', 'dogecoin', 'tron', 'the-open-network',
        'toncoin', 'avalanche-2', 'chainlink', 'polkadot', 'polygon',
        'matic-network', 'shiba-inu', 'litecoin', 'wrapped-bitcoin', 'dai',
        'bitcoin-cash', 'uniswap', 'stellar', 'monero', 'okb', 'ethereum-classic',
        'cosmos', 'hedera-hashgraph', 'filecoin', 'internet-computer', 'aptos',
        'arbitrum', 'optimism', 'near', 'vechain', 'maker', 'aave', 'algorand',
        'quant-network', 'the-graph', 'render-token', 'stacks', 'injective-protocol',
        'theta-token', 'fantom', 'tezos', 'immutable-x', 'first-digital-usd',
        'flow', 'axie-infinity', 'sandbox', 'decentraland', 'chiliz', 'eos',
        'kava', 'gala', 'curve-dao-token', 'pancakeswap-token', 'lido-dao',
        'rocket-pool', 'frax', 'gmx', 'dydx', 'synthetix-network-token',
        'compound-governance-token', 'thorchain', 'mina-protocol', 'kusama',
        'zcash', 'dash', 'neo', 'iota', 'helium', 'sui', 'sei-network',
        'celestia', 'pepe', 'bonk', 'floki', 'dogwifcoin', 'jupiter-exchange-solana',
        'pyth-network', 'jito-governance-token', 'ondo-finance', 'ethena',
        'starknet', 'worldcoin-wld', 'blur', 'mantle', 'kaspa', 'bittensor',
        'fetch-ai', 'singularitynet', 'ocean-protocol', 'akash-network',
        'notcoin', 'dogs-2', 'hamster-kombat', 'catizen', 'gram', 'storm',
        'tonstakers-staked-ton', 'whales-club',
    ],

    // Indexable exchange detail pages (`/exchange/:id`).
    'exchanges' => [
        'binance', 'coinbase-exchange', 'kraken', 'okx', 'bybit_spot',
        'kucoin', 'gate', 'bitfinex', 'bitstamp', 'gemini', 'htx',
        'crypto_com', 'mexc', 'bitget', 'upbit', 'bingx', 'whitebit',
        'lbank', 'bithumb', 'coinex', 'bitmart', 'ston-fi', 'dedust',
        'uniswap_v3', 'curve', 'pancakeswap-v3-bsc', 'raydium2', 'jupiter',
        'sushiswap',
    ],
];
