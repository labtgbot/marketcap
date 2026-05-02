<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 PERFORMANCE CONFIGURATION
 * -------------------------------------------------------------------------
 * Budgets, synthetic load profiles, and static asset cache policy for launch
 * hardening. Values are conservative enough for CI and configurable for
 * staging or production release drills.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$performance = [
    'budgets'       => [
        'first_contentful_render_ms'      => tonbankcard_env_int( 'TONBANKCARD_BUDGET_FIRST_CONTENTFUL_RENDER_MS', 1800, 500, 10000 ),
        'app_ready_ms'                    => tonbankcard_env_int( 'TONBANKCARD_BUDGET_APP_READY_MS', 2500, 750, 15000 ),
        'market_api_p95_ms'               => tonbankcard_env_int( 'TONBANKCARD_BUDGET_MARKET_API_P95_MS', 350, 50, 5000 ),
        'search_api_p95_ms'               => tonbankcard_env_int( 'TONBANKCARD_BUDGET_SEARCH_API_P95_MS', 200, 25, 3000 ),
        'coin_detail_api_p95_ms'          => tonbankcard_env_int( 'TONBANKCARD_BUDGET_COIN_DETAIL_API_P95_MS', 450, 50, 5000 ),
        'chart_render_ms'                 => tonbankcard_env_int( 'TONBANKCARD_BUDGET_CHART_RENDER_MS', 900, 100, 8000 ),
        'alert_delivery_p95_ms'           => tonbankcard_env_int( 'TONBANKCARD_BUDGET_ALERT_DELIVERY_P95_MS', 1000, 100, 10000 ),
        'share_card_generation_p95_ms'    => tonbankcard_env_int( 'TONBANKCARD_BUDGET_SHARE_CARD_GENERATION_P95_MS', 250, 25, 3000 ),
    ],
    'load_profiles' => [
        'market_pulse'          => [
            'description'       => 'Home market pulse global stats plus first-page market rows.',
            'iterations'        => tonbankcard_env_int( 'TONBANKCARD_LOAD_MARKET_PULSE_ITERATIONS', 40, 5, 1000 ),
            'concurrency'       => 8,
            'safe_requests_per_second' => 20,
            'budget_key'        => 'market_api_p95_ms',
            'routes'            => [ '/api/market/global', '/api/market/coins/markets' ],
        ],
        'smart_search'          => [
            'description'       => 'Top navigation search suggestions from the first-party index.',
            'iterations'        => tonbankcard_env_int( 'TONBANKCARD_LOAD_SMART_SEARCH_ITERATIONS', 60, 5, 1500 ),
            'concurrency'       => 12,
            'safe_requests_per_second' => 30,
            'budget_key'        => 'search_api_p95_ms',
            'routes'            => [ '/api/search?q=ton&limit=8' ],
        ],
        'coin_detail'           => [
            'description'       => 'Coin detail provider envelope and freshness metadata.',
            'iterations'        => tonbankcard_env_int( 'TONBANKCARD_LOAD_COIN_DETAIL_ITERATIONS', 40, 5, 1000 ),
            'concurrency'       => 8,
            'safe_requests_per_second' => 15,
            'budget_key'        => 'coin_detail_api_p95_ms',
            'routes'            => [ '/api/market/coins/bitcoin' ],
        ],
        'alert_evaluation'      => [
            'description'       => 'Rule evaluation, frequency guards, and Telegram delivery payload creation.',
            'iterations'        => tonbankcard_env_int( 'TONBANKCARD_LOAD_ALERT_EVALUATION_ITERATIONS', 80, 5, 2000 ),
            'concurrency'       => 4,
            'safe_rules_per_minute' => 1200,
            'budget_key'        => 'alert_delivery_p95_ms',
            'routes'            => [ '/api/alerts/evaluate' ],
        ],
        'share_card_generation' => [
            'description'       => 'Telegram startapp payload generation and validation for share cards.',
            'iterations'        => tonbankcard_env_int( 'TONBANKCARD_LOAD_SHARE_CARD_ITERATIONS', 100, 5, 3000 ),
            'concurrency'       => 12,
            'safe_requests_per_second' => 40,
            'budget_key'        => 'share_card_generation_p95_ms',
            'routes'            => [ '/api/share/resolve' ],
        ],
    ],
    'static_assets' => [
        'immutable_max_age_seconds'       => tonbankcard_env_int( 'TONBANKCARD_STATIC_ASSET_IMMUTABLE_MAX_AGE_SECONDS', 31536000, 3600, 31536000 ),
        'unversioned_max_age_seconds'     => tonbankcard_env_int( 'TONBANKCARD_STATIC_ASSET_UNVERSIONED_MAX_AGE_SECONDS', 86400, 60, 604800 ),
        'stale_while_revalidate_seconds'  => tonbankcard_env_int( 'TONBANKCARD_STATIC_ASSET_STALE_REVALIDATE_SECONDS', 604800, 60, 31536000 ),
        'manifest_max_age_seconds'        => tonbankcard_env_int( 'TONBANKCARD_STATIC_ASSET_MANIFEST_MAX_AGE_SECONDS', 3600, 60, 86400 ),
        'service_worker_cache_control'    => 'no-cache, no-store, must-revalidate',
        'extensions'                      => [
            'css',
            'js',
            'mjs',
            'json',
            'webmanifest',
            'png',
            'jpg',
            'jpeg',
            'gif',
            'svg',
            'ico',
            'woff',
            'woff2',
            'ttf',
            'eot',
        ],
    ],
];
