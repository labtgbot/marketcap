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
            'X-TONBANKCARD-Session',
            'X-Telegram-Init-Data',
        ],
        'exposed_headers'      => [
            'X-Request-ID',
            'X-RateLimit-Limit',
            'X-RateLimit-Remaining',
        ],
        'supports_credentials' => TRUE,
        'max_age'              => 600,
    ],
    'rate_limit' => [
        'enabled'        => FALSE,
        'window_seconds' => 60,
        'max_requests'   => 60,
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
];
