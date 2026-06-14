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
$api_sentiment_cache_ttl = tonbankcard_env_int( 'TONBANKCARD_SENTIMENT_CACHE_TTL_SECONDS', 300, 60, 21600 );
$api_ton_curation_file = trim( (string) tonbankcard_env( 'TONBANKCARD_TON_CURATION_FILE', '' ) );
$api_alert_worker_token = trim( (string) tonbankcard_env( 'TONBANKCARD_ALERT_WORKER_TOKEN', '' ) );
$api_alert_max_rules = tonbankcard_env_int( 'TONBANKCARD_ALERT_MAX_RULES_PER_USER', 20, 1, 200 );
$api_alert_default_frequency_cap = tonbankcard_env_int( 'TONBANKCARD_ALERT_DEFAULT_FREQUENCY_CAP_SECONDS', 3600, 300, 86400 );
$api_alert_max_deliveries = tonbankcard_env_int( 'TONBANKCARD_ALERT_MAX_DELIVERIES_PER_DAY', 8, 1, 100 );
$api_alert_evaluation_interval = tonbankcard_env_int( 'TONBANKCARD_ALERT_EVALUATION_INTERVAL_SECONDS', 300, 60, 3600 );
$api_max_request_body_bytes = tonbankcard_env_int( 'TONBANKCARD_API_MAX_REQUEST_BODY_BYTES', 1048576, 1024, 10485760 );
$api_ai_max_request_body_bytes = tonbankcard_env_int( 'TONBANKCARD_AI_MAX_REQUEST_BODY_BYTES', 16384, 1024, 1048576 );
$api_ai_max_prompt_bytes = tonbankcard_env_int( 'TONBANKCARD_AI_MAX_PROMPT_BYTES', 12288, 1024, 262144 );
$api_premium_plan_code = trim( (string) tonbankcard_env( 'TONBANKCARD_PREMIUM_PLAN_CODE', 'premium_monthly' ) );
$api_premium_price_stars = tonbankcard_env_int( 'TONBANKCARD_PREMIUM_MONTHLY_STARS', 199, 1, 10000 );
$api_premium_subscription_period = tonbankcard_env_int( 'TONBANKCARD_PREMIUM_SUBSCRIPTION_PERIOD_SECONDS', 2592000, 2592000, 2592000 );
$api_premium_signing_secret = trim( (string) tonbankcard_env( 'TONBANKCARD_PREMIUM_SIGNING_SECRET', '' ) );
$api_achievement_weekly_check_days = tonbankcard_env_int( 'TONBANKCARD_ACHIEVEMENT_WEEKLY_CHECK_DAYS', 7, 2, 30 );
$api_achievement_share_milestone_count = tonbankcard_env_int( 'TONBANKCARD_ACHIEVEMENT_SHARE_MILESTONE_COUNT', 3, 1, 100 );
$api_achievement_movement_threshold_percent = tonbankcard_env( 'TONBANKCARD_ACHIEVEMENT_MOVEMENT_THRESHOLD_PERCENT', '7.5' );
$api_achievement_movement_threshold_percent = is_numeric( $api_achievement_movement_threshold_percent )
    ? max( 1.0, min( 100.0, (float) $api_achievement_movement_threshold_percent ) )
    : 7.5;
$api_achievement_max_prompts_per_session = tonbankcard_env_int( 'TONBANKCARD_ACHIEVEMENT_MAX_PROMPTS_PER_SESSION', 1, 1, 6 );
if ( '' === $api_redis_key_prefix ) {
    $api_redis_key_prefix = 'tonbankcard:v2';
}
if ( '' === $api_ton_curation_file ) {
    $api_ton_curation_file = tonbankcard_runtime_state_store_path( 'tonbankcard-marketcap-ton-curation.json' );
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
        'allowed_methods'      => [ 'GET', 'POST', 'PUT', 'DELETE', 'OPTIONS' ],
        'allowed_headers'      => [
            'Authorization',
            'Content-Type',
            'X-Request-ID',
            'X-TONBANKCARD-Search-Refresh-Token',
            'X-TONBANKCARD-TON-Curation-Token',
            'X-TONBANKCARD-Alert-Worker-Token',
            'X-TONBANKCARD-Admin',
            'X-TONBANKCARD-CSRF',
            'X-TONBANKCARD-Session',
            'X-Telegram-Init-Data',
            'X-Telegram-Bot-Api-Secret-Token',
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
    'limits'     => [
        'max_request_body_bytes' => $api_max_request_body_bytes,
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
            'live_prices'      => 60,
            'global_stats'     => 300,
            'coin_metadata'    => 3600,
            'charts'           => 900,
            'search_index'     => 3600,
            'sentiment_inputs' => $api_sentiment_cache_ttl,
            'ai_summaries'     => 21600,
            'ton_metadata'     => 86400,
        ],
        'coalesce'          => [
            'enabled'          => TRUE,
            'lock_ttl_seconds' => 15,
            'wait_ms'          => 250,
            'poll_ms'          => 25,
        ],
    ],
    'rate_limit' => [
        'enabled'            => tonbankcard_env_bool( 'TONBANKCARD_RATE_LIMIT_ENABLED', $api_upstash_configured && 'local' !== $api_profile ),
        'trusted_proxy_hops' => tonbankcard_env_int( 'TONBANKCARD_TRUSTED_PROXY_HOPS', 0, 0, 10 ),
        'trusted_proxies'    => array_values(
            array_filter(
                array_map(
                    'trim',
                    explode( ',', (string) tonbankcard_env( 'TONBANKCARD_TRUSTED_PROXIES', '' ) )
                )
            )
        ),
        'policies'           => [
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
    'admin'      => [
        'store_path'               => isset( $api_runtime['admin']['store_path'] ) ? (string) $api_runtime['admin']['store_path'] : '',
        'store_configured'         => ! empty( $api_runtime['admin']['store_configured'] ),
        'token_configured'         => ! empty( $api_runtime['admin']['token_configured'] ),
        'support_token_configured' => ! empty( $api_runtime['admin']['support_token_configured'] ),
    ],
    'observability' => [
        'log_level'              => $api_runtime['observability']['log_level'],
        'verbose_tracing'        => (bool) $api_runtime['observability']['verbose_tracing'],
        'client_error_reporting' => (bool) $api_runtime['observability']['client_error_reporting'],
        'sink'                   => $api_runtime['observability']['sink'],
        'error_monitoring'       => [
            'enabled'     => ! empty( $api_runtime['observability']['error_monitoring']['enabled'] ),
            'dsn'         => isset( $api_runtime['observability']['error_monitoring']['dsn'] ) ? (string) $api_runtime['observability']['error_monitoring']['dsn'] : '',
            'min_level'   => isset( $api_runtime['observability']['error_monitoring']['min_level'] ) ? (string) $api_runtime['observability']['error_monitoring']['min_level'] : 'error',
            'environment' => isset( $api_runtime['observability']['error_monitoring']['environment'] ) ? (string) $api_runtime['observability']['error_monitoring']['environment'] : '',
            'timeout_ms'  => isset( $api_runtime['observability']['error_monitoring']['timeout_ms'] ) ? (int) $api_runtime['observability']['error_monitoring']['timeout_ms'] : 2000,
        ],
        'uptime'                 => [
            'enabled'     => ! empty( $api_runtime['observability']['uptime']['enabled'] ),
            'base_url'    => isset( $api_runtime['observability']['uptime']['base_url'] ) ? (string) $api_runtime['observability']['uptime']['base_url'] : '',
            'targets'     => isset( $api_runtime['observability']['uptime']['targets'] ) && is_array( $api_runtime['observability']['uptime']['targets'] ) ? $api_runtime['observability']['uptime']['targets'] : [ '/api/health', '/api/ready' ],
            'bot_token'   => isset( $api_runtime['observability']['uptime']['bot_token'] ) ? (string) $api_runtime['observability']['uptime']['bot_token'] : '',
            'chat_id'     => isset( $api_runtime['observability']['uptime']['chat_id'] ) ? (string) $api_runtime['observability']['uptime']['chat_id'] : '',
            'timeout_ms'  => isset( $api_runtime['observability']['uptime']['timeout_ms'] ) ? (int) $api_runtime['observability']['uptime']['timeout_ms'] : 5000,
            'environment' => isset( $api_runtime['observability']['uptime']['environment'] ) ? (string) $api_runtime['observability']['uptime']['environment'] : '',
        ],
    ],
    'telegram_session' => [
        'init_data_max_age_seconds'     => 86400,
        'auth_date_future_skew_seconds' => 60,
        'session_ttl_seconds'           => 2592000,
        'local_session_store_path'      => (string) tonbankcard_env( 'TONBANKCARD_LOCAL_SESSION_STORE', tonbankcard_runtime_state_store_path( 'tonbankcard-marketcap-sessions.json' ) ),
    ],
    'telegram_bot' => [
        'webhook_secret'       => isset( $api_runtime['telegram']['webhook_secret'] ) ? (string) $api_runtime['telegram']['webhook_secret'] : '',
        'inline_cache_seconds' => 300,
        'inline_max_results'   => 8,
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
        'feedback_store_path' => (string) tonbankcard_env(
            'TONBANKCARD_AI_FEEDBACK_STORE',
            tonbankcard_runtime_state_store_path( 'tonbankcard-marketcap-ai-feedback.json' )
        ),
        'safety'            => [
            'require_not_financial_advice' => TRUE,
            'require_uncertainty'          => TRUE,
            'require_market_data_age'      => TRUE,
        ],
        'limits'            => [
            'max_request_body_bytes' => $api_ai_max_request_body_bytes,
            'max_prompt_bytes'       => $api_ai_max_prompt_bytes,
        ],
        'sentiment'         => [
            'pipeline_version'         => 'v1',
            'cache_ttl_seconds'        => $api_sentiment_cache_ttl,
            'max_coin_ids'             => 12,
            'max_watchlist_coin_ids'   => 80,
            'default_coin_ids'         => [ 'toncoin', 'bitcoin', 'ethereum', 'tether' ],
            'source_refresh_intervals' => [
                'market_movement'         => 60,
                'volume_spike'            => 60,
                'global_market'           => 300,
                'trend_ranking'           => 900,
                'watchlist_concentration' => 300,
                'curated_ton_ecosystem'   => 86400,
            ],
            'curated_ton_assets'       => [
                [
                    'coin_id' => 'toncoin',
                    'symbol'  => 'TON',
                    'label'   => 'Native TON ecosystem asset',
                    'tags'    => [ 'ton_ecosystem', 'native_ton' ],
                ],
                [
                    'coin_id' => 'tether',
                    'symbol'  => 'USDT',
                    'label'   => 'USDT on TON curated asset',
                    'tags'    => [ 'ton_ecosystem', 'stablecoin', 'jetton' ],
                ],
            ],
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
    'ton_ecosystem' => [
        'curation_store_path' => $api_ton_curation_file,
        'curation_token'      => (string) tonbankcard_env( 'TONBANKCARD_TON_CURATION_TOKEN', '' ),
    ],
    'alerts' => [
        'worker_token'                    => $api_alert_worker_token,
        'max_alerts_per_user'             => $api_alert_max_rules,
        'default_frequency_cap_seconds'   => $api_alert_default_frequency_cap,
        'min_frequency_cap_seconds'       => 300,
        'max_frequency_cap_seconds'       => 86400,
        'default_max_deliveries_per_day'  => $api_alert_max_deliveries,
        'evaluation_interval_seconds'     => $api_alert_evaluation_interval,
    ],
    'premium' => [
        'monthly_plan_code'               => '' !== $api_premium_plan_code ? $api_premium_plan_code : 'premium_monthly',
        'monthly_plan_name'               => 'TONBANKCARD Premium',
        'monthly_plan_description'        => 'Telegram Stars monthly subscription for higher limits and priority refresh.',
        'monthly_price_stars'             => $api_premium_price_stars,
        'subscription_period_seconds'     => $api_premium_subscription_period,
        'signing_secret'                  => $api_premium_signing_secret,
        'free_limits'                     => [
            'alerts_per_user'        => 3,
            'watchlist_entries'      => 20,
            'advanced_ranges'        => [ '24h', '7d' ],
            'ai_digest_per_day'      => 1,
            'priority_refresh'       => FALSE,
            'market_refresh_seconds' => 300,
        ],
        'premium_limits'                  => [
            'alerts_per_user'        => 100,
            'watchlist_entries'      => 250,
            'advanced_ranges'        => [ '24h', '7d', '30d', '90d', '1y' ],
            'ai_digest_per_day'      => 24,
            'priority_refresh'       => TRUE,
            'market_refresh_seconds' => 60,
        ],
    ],
    'achievements' => [
        'weekly_check_days'          => $api_achievement_weekly_check_days,
        'share_milestone_count'      => $api_achievement_share_milestone_count,
        'movement_threshold_percent' => $api_achievement_movement_threshold_percent,
        'max_prompts_per_session'    => $api_achievement_max_prompts_per_session,
        'prompt_cooldown_hours'      => 24,
        'haptics_enabled'            => TRUE,
    ],
];
