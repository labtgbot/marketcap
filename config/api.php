<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 API CONFIGURATION
 * -------------------------------------------------------------------------
 * Server-side API routing defaults for the public website and Telegram Mini
 * App. This file must not expose provider keys, bot tokens, or database
 * passwords to browser JavaScript.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$api_runtime = isset( $GLOBALS['runtime_config'] ) ? $GLOBALS['runtime_config'] : tonbankcard_runtime_config();
$api_profile = isset( $api_runtime['profile'] ) ? (string) $api_runtime['profile'] : 'local';
$api_upstash = isset( $api_runtime['providers']['upstash'] ) && is_array( $api_runtime['providers']['upstash'] ) ? $api_runtime['providers']['upstash'] : [];
$api_upstash_configured = ! empty( $api_upstash['rest_url'] ) && ! empty( $api_upstash['rest_token'] );
$api_redis_timeout = max( 1, (int) tonbankcard_env( 'TONBANKCARD_REDIS_TIMEOUT_SECONDS', 2 ) );
$api_redis_key_prefix = trim( (string) tonbankcard_env( 'TONBANKCARD_REDIS_KEY_PREFIX', 'tonbankcard:v2' ), ':' );
if ( '' === $api_redis_key_prefix ) {
    $api_redis_key_prefix = 'tonbankcard:v2';
}

$api_allowed_origins = [];
foreach ( [ 'active', 'local', 'staging', 'public', 'telegram' ] as $url_key ) {
    if ( ! empty( $api_runtime['urls'][ $url_key ] ) ) {
        $api_allowed_origins[] = rtrim( $api_runtime['urls'][ $url_key ], '/' );
    }
}

$api = [
    'cors'       => [
        'allowed_origins'      => array_values( array_unique( array_filter( $api_allowed_origins ) ) ),
        'allowed_methods'      => [ 'GET', 'POST', 'OPTIONS' ],
        'allowed_headers'      => [
            'Authorization',
            'Content-Type',
            'X-Request-ID',
            'X-TONBANKCARD-Search-Refresh-Token',
            'X-TONBANKCARD-Admin',
            'X-TONBANKCARD-Session',
            'X-Telegram-Init-Data',
        ],
        'exposed_headers'      => [
            'X-Request-ID',
            'X-RateLimit-Limit',
            'X-RateLimit-Remaining',
            'X-RateLimit-Reset',
            'X-RateLimit-Policy',
            'Retry-After',
        ],
        'supports_credentials' => TRUE,
        'max_age'              => 600,
    ],
    'redis'      => [
        'enabled'         => $api_upstash_configured,
        'rest_url'        => isset( $api_upstash['rest_url'] ) ? $api_upstash['rest_url'] : '',
        'rest_token'      => isset( $api_upstash['rest_token'] ) ? $api_upstash['rest_token'] : '',
        'timeout_seconds' => $api_redis_timeout,
        'key_prefix'      => $api_redis_key_prefix,
    ],
    'cache'      => [
        'enabled'           => tonbankcard_env_bool( 'TONBANKCARD_CACHE_ENABLED', $api_upstash_configured ),
        'stale_ttl_seconds' => 3600,
        'ttls'              => [
            'live_prices'   => 60,
            'global_stats'  => 300,
            'coin_metadata' => 3600,
            'charts'        => 900,
            'search_index'  => 3600,
            'ai_summaries'  => 21600,
            'ton_metadata'  => 86400,
        ],
        'coalesce'          => [
            'enabled'          => TRUE,
            'lock_ttl_seconds' => 15,
            'wait_ms'          => 250,
            'poll_ms'          => 25,
        ],
    ],
    'rate_limit' => [
        'enabled'  => tonbankcard_env_bool( 'TONBANKCARD_RATE_LIMIT_ENABLED', $api_upstash_configured && 'local' !== $api_profile ),
        'policies' => [
            'anonymous_web'    => [
                'window_seconds' => 60,
                'max_requests'   => 120,
            ],
            'telegram_session' => [
                'window_seconds' => 60,
                'max_requests'   => 240,
            ],
            'admin_action'     => [
                'window_seconds' => 60,
                'max_requests'   => 30,
            ],
        ],
    ],
    'audit'      => [
        'enabled' => tonbankcard_env_bool( 'TONBANKCARD_API_AUDIT_LOG', FALSE ),
        'sink'    => 'error_log',
    ],
    'observability' => [
        'log_level'              => $api_runtime['observability']['log_level'],
        'verbose_tracing'        => (bool) $api_runtime['observability']['verbose_tracing'],
        'client_error_reporting' => (bool) $api_runtime['observability']['client_error_reporting'],
        'sink'                   => $api_runtime['observability']['sink'],
    ],
    'telegram_session' => [
        'init_data_max_age_seconds'     => 86400,
        'auth_date_future_skew_seconds' => 60,
        'session_ttl_seconds'           => 2592000,
        'local_session_store_path'      => (string) tonbankcard_env( 'TONBANKCARD_LOCAL_SESSION_STORE', sys_get_temp_dir() . '/tonbankcard-marketcap-sessions.json' ),
    ],
    'readiness'  => [
        'active_checks'   => tonbankcard_env_bool( 'TONBANKCARD_API_ACTIVE_READINESS', FALSE ),
        'timeout_seconds' => 2,
    ],
    'ai'          => [
        'provider'          => $api_runtime['ai']['provider'],
        'prompt_version'    => $api_runtime['ai']['prompt_version'],
        'enabled_features'  => $api_runtime['ai']['enabled_features'],
        'fallback_behavior' => $api_runtime['ai']['fallback_behavior'],
        'safety'            => [
            'require_not_financial_advice' => TRUE,
            'require_uncertainty'          => TRUE,
            'require_market_data_age'      => TRUE,
        ],
        'groq'              => [
            'api_key'            => $api_runtime['providers']['groq']['api_key'],
            'api_key_configured' => $api_runtime['providers']['groq']['api_key_configured'],
            'model_id'           => $api_runtime['providers']['groq']['model_id'],
            'base_url'           => $api_runtime['providers']['groq']['base_url'],
            'timeout_seconds'    => $api_runtime['providers']['groq']['timeout_seconds'],
            'rate_limit'         => $api_runtime['providers']['groq']['rate_limit'],
        ],
    ],
    'market_data' => [
        'provider'          => 'coingecko',
        'timeout_seconds'   => 10,
        'cache_ttl_seconds' => 0,
        'attribution'       => [
            'name' => 'CoinGecko',
            'url'  => 'https://www.coingecko.com/',
        ],
        'coingecko'         => [
            'plan'               => $api_runtime['providers']['coingecko']['api_plan'],
            'api_key'            => $api_runtime['providers']['coingecko']['api_key'],
            'api_key_configured' => $api_runtime['providers']['coingecko']['api_key_configured'],
            'demo_base_url'      => 'https://api.coingecko.com/api/v3/',
            'pro_base_url'       => 'https://pro-api.coingecko.com/api/v3/',
        ],
    ],
    'search'      => [
        'index_cache_key'        => $api_redis_key_prefix . ':search:index',
        'index_ttl_seconds'     => 3600,
        'default_limit'         => 12,
        'max_limit'             => 30,
        'redis_timeout_seconds' => $api_redis_timeout,
        'refresh_token'         => (string) tonbankcard_env( 'TONBANKCARD_SEARCH_REFRESH_TOKEN', '' ),
    ],
];
