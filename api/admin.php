<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 ADMIN API
 * -------------------------------------------------------------------------
 * Token-protected operator controls for provider metadata, feature flags,
 * content settings, cache operations, and audit review. Secret inputs are
 * write-only: raw values are fingerprinted and discarded before persistence.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Returns TRUE when the path belongs to the admin API surface.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_admin_is_request( string $path ) {
    $path = tonbankcard_api_normalize_path( $path );
    return '/api/admin' === $path || 0 === strpos( $path, '/api/admin/' );
}

/**
 * Returns documented admin API routes.
 *
 * @return array
 */
function tonbankcard_api_admin_routes() {
    return [
        '/api/admin',
        '/api/admin/session',
        '/api/admin/config',
        '/api/admin/mini-app',
        '/api/admin/mini-app/telegram-setup',
        '/api/admin/providers',
        '/api/admin/feature-flags',
        '/api/admin/content',
        '/api/admin/operations',
        '/api/admin/cache/purge',
        '/api/admin/audit-log',
    ];
}

/**
 * Handles an admin API request.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_admin_handle( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $path = tonbankcard_api_normalize_path( $request['path'] );
    $method = strtoupper( $request['method'] );

    if ( '/api/admin/session' === $path ) {
        if ( 'POST' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers );
        }

        $actor = tonbankcard_api_admin_authenticate( $request, $runtime, $config, TRUE );
        if ( empty( $actor['ok'] ) ) {
            return tonbankcard_api_admin_auth_error( $actor, $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            [
                'authenticated' => TRUE,
                'actor'         => tonbankcard_api_admin_public_actor( $actor['actor'] ),
            ],
            $request_id,
            $headers
        );
    }

    $actor = tonbankcard_api_admin_authenticate( $request, $runtime, $config, FALSE );
    if ( empty( $actor['ok'] ) ) {
        return tonbankcard_api_admin_auth_error( $actor, $request_id, $headers );
    }
    $actor = $actor['actor'];

    if ( '/api/admin' === $path ) {
        if ( 'GET' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            [
                'service' => 'tonbankcard-admin',
                'routes'  => tonbankcard_api_admin_routes(),
                'actor'   => tonbankcard_api_admin_public_actor( $actor ),
            ],
            $request_id,
            $headers
        );
    }

    if ( '/api/admin/config' === $path ) {
        if ( 'GET' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            tonbankcard_api_admin_config_payload( $runtime, $config, $actor ),
            $request_id,
            $headers
        );
    }

    if ( '/api/admin/audit-log' === $path ) {
        if ( 'GET' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            [
                'actor'     => tonbankcard_api_admin_public_actor( $actor ),
                'audit_log' => tonbankcard_api_admin_audit_log( $runtime, $config ),
            ],
            $request_id,
            $headers
        );
    }

    if ( ! tonbankcard_api_admin_can_write( $actor ) ) {
        return tonbankcard_api_error_response(
            403,
            'admin_write_forbidden',
            'This admin role is read-only for configuration changes.',
            [ 'role' => $actor['role'] ],
            $request_id,
            $headers
        );
    }

    if ( '/api/admin/feature-flags' === $path ) {
        if ( 'PUT' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'PUT', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_admin_update_feature_flags( $request, $runtime, $config, $actor, $request_id, $headers );
    }

    if ( '/api/admin/mini-app' === $path ) {
        if ( 'PUT' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'PUT', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_admin_update_mini_app( $request, $runtime, $config, $actor, $request_id, $headers );
    }

    if ( '/api/admin/mini-app/telegram-setup' === $path ) {
        if ( 'POST' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_admin_setup_mini_app_telegram( $request, $runtime, $config, $actor, $request_id, $headers );
    }

    if ( '/api/admin/providers' === $path ) {
        if ( 'PUT' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'PUT', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_admin_update_providers( $request, $runtime, $config, $actor, $request_id, $headers );
    }

    if ( '/api/admin/content' === $path ) {
        if ( 'PUT' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'PUT', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_admin_update_content( $request, $runtime, $config, $actor, $request_id, $headers );
    }

    if ( '/api/admin/operations' === $path ) {
        if ( 'PUT' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'PUT', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_admin_update_operations( $request, $runtime, $config, $actor, $request_id, $headers );
    }

    if ( '/api/admin/cache/purge' === $path ) {
        if ( 'POST' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_admin_cache_purge( $request, $runtime, $config, $actor, $request_id, $headers );
    }

    return tonbankcard_api_error_response(
        404,
        'not_found',
        'No admin route matches the request.',
        [ 'path' => $path ],
        $request_id,
        $headers
    );
}

/**
 * Authenticates an admin token from headers or a login payload.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param bool $allow_body_token
 * @return array
 */
function tonbankcard_api_admin_authenticate( array $request, array $runtime, array $config, bool $allow_body_token ) {
    $settings = tonbankcard_api_admin_settings( $runtime, $config );
    if ( '' === $settings['admin_token'] && '' === $settings['support_token'] ) {
        return [
            'ok'      => FALSE,
            'status'  => 503,
            'code'    => 'admin_auth_unconfigured',
            'message' => 'Admin authentication is not configured.',
            'details' => [ 'config' => 'TONBANKCARD_ADMIN_TOKEN' ],
        ];
    }

    $provided = tonbankcard_api_admin_token_from_request( $request, $allow_body_token );
    if ( '' === $provided ) {
        return [
            'ok'      => FALSE,
            'status'  => 401,
            'code'    => 'admin_auth_required',
            'message' => 'Admin authentication is required for this route.',
            'details' => [ 'session' => 'admin_validated' ],
        ];
    }

    if ( '' !== $settings['admin_token'] && hash_equals( $settings['admin_token'], $provided ) ) {
        return [
            'ok'    => TRUE,
            'actor' => tonbankcard_api_admin_actor( $provided, 'owner', TRUE ),
        ];
    }

    if ( '' !== $settings['support_token'] && hash_equals( $settings['support_token'], $provided ) ) {
        return [
            'ok'    => TRUE,
            'actor' => tonbankcard_api_admin_actor( $provided, 'support', FALSE ),
        ];
    }

    return [
        'ok'      => FALSE,
        'status'  => 401,
        'code'    => 'admin_auth_invalid',
        'message' => 'Admin credentials were not accepted.',
        'details' => [ 'session' => 'admin_validated' ],
    ];
}

/**
 * Returns admin API settings.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_admin_settings( array $runtime, array $config ) {
    $admin = isset( $config['admin'] ) && is_array( $config['admin'] ) ? $config['admin'] : [];
    $runtime_admin = isset( $runtime['admin'] ) && is_array( $runtime['admin'] ) ? $runtime['admin'] : [];
    $store_path = isset( $admin['store_path'] ) && '' !== trim( (string) $admin['store_path'] )
        ? (string) $admin['store_path']
        : ( isset( $runtime_admin['store_path'] ) ? (string) $runtime_admin['store_path'] : tonbankcard_runtime_admin_store_path() );

    return [
        'store_path'    => $store_path,
        'admin_token'   => trim( (string) tonbankcard_env( 'TONBANKCARD_ADMIN_TOKEN', '' ) ),
        'support_token' => trim( (string) tonbankcard_env( 'TONBANKCARD_ADMIN_SUPPORT_TOKEN', '' ) ),
    ];
}

/**
 * Extracts an admin token from supported request locations.
 *
 * @param array $request
 * @param bool $allow_body_token
 * @return string
 */
function tonbankcard_api_admin_token_from_request( array $request, bool $allow_body_token ) {
    $headers = isset( $request['headers'] ) && is_array( $request['headers'] ) ? $request['headers'] : [];
    if ( ! empty( $headers['authorization'] ) && preg_match( '/^Bearer\s+(.+)$/i', trim( (string) $headers['authorization'] ), $matches ) ) {
        return trim( $matches[1] );
    }

    if ( ! empty( $headers['x-tonbankcard-admin'] ) ) {
        return trim( (string) $headers['x-tonbankcard-admin'] );
    }

    if ( $allow_body_token ) {
        $body = tonbankcard_api_json_body( $request );
        if ( ! empty( $body['token'] ) && is_scalar( $body['token'] ) ) {
            return trim( (string) $body['token'] );
        }
    }

    return '';
}

/**
 * Builds a non-secret actor record.
 *
 * @param string $token
 * @param string $role
 * @param bool $write
 * @return array
 */
function tonbankcard_api_admin_actor( string $token, string $role, bool $write ) {
    return [
        'id'          => $role . ':' . substr( hash( 'sha256', $token ), 0, 16 ),
        'role'        => $role,
        'permissions' => [
            'read'  => TRUE,
            'write' => $write,
        ],
    ];
}

/**
 * Returns a public actor payload.
 *
 * @param array $actor
 * @return array
 */
function tonbankcard_api_admin_public_actor( array $actor ) {
    return [
        'id'          => isset( $actor['id'] ) ? $actor['id'] : 'unknown',
        'role'        => isset( $actor['role'] ) ? $actor['role'] : 'support',
        'permissions' => isset( $actor['permissions'] ) && is_array( $actor['permissions'] )
            ? $actor['permissions']
            : [ 'read' => TRUE, 'write' => FALSE ],
    ];
}

/**
 * Returns TRUE when the actor can write settings.
 *
 * @param array $actor
 * @return bool
 */
function tonbankcard_api_admin_can_write( array $actor ) {
    return ! empty( $actor['permissions']['write'] ) && in_array( isset( $actor['role'] ) ? $actor['role'] : '', [ 'owner', 'admin' ], TRUE );
}

/**
 * Converts auth failures to JSON errors.
 *
 * @param array $error
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_admin_auth_error( array $error, string $request_id, array $headers ) {
    return tonbankcard_api_error_response(
        isset( $error['status'] ) ? (int) $error['status'] : 401,
        isset( $error['code'] ) ? (string) $error['code'] : 'admin_auth_required',
        isset( $error['message'] ) ? (string) $error['message'] : 'Admin authentication is required.',
        isset( $error['details'] ) && is_array( $error['details'] ) ? $error['details'] : [],
        $request_id,
        $headers
    );
}

/**
 * Returns the current admin configuration state.
 *
 * @param array $runtime
 * @param array $config
 * @param array $actor
 * @return array
 */
function tonbankcard_api_admin_config_payload( array $runtime, array $config, array $actor ) {
    $state = tonbankcard_api_admin_load_state( $runtime, $config );

    return [
        'actor'         => tonbankcard_api_admin_public_actor( $actor ),
        'feature_flags' => $state['feature_flags'],
        'mini_app'      => $state['mini_app'],
        'providers'     => $state['providers'],
        'content'       => $state['content'],
        'operations'    => $state['operations'],
        'analytics'     => $state['analytics'],
        'audit_log'     => array_slice( $state['audit_log'], 0, 25 ),
        'meta'          => [
            'store_configured' => ! empty( $state['store_configured'] ),
            'store_loaded'     => ! empty( $state['store_loaded'] ),
            'updated_at'       => isset( $state['updated_at'] ) ? $state['updated_at'] : null,
            'secrets'          => 'write_only',
        ],
    ];
}

/**
 * Loads the saved admin state over safe defaults.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_admin_load_state( array $runtime, array $config ) {
    $settings = tonbankcard_api_admin_settings( $runtime, $config );
    $defaults = tonbankcard_api_admin_default_state( $runtime, $config );
    $store = tonbankcard_runtime_admin_store( $settings['store_path'] );

    if ( empty( $store ) ) {
        return $defaults;
    }

    $state = $defaults;
    $state['store_loaded'] = TRUE;
    $state['updated_at'] = isset( $store['updated_at'] ) ? (string) $store['updated_at'] : $state['updated_at'];
    $state['feature_flags'] = tonbankcard_api_admin_normalize_feature_flags(
        isset( $store['feature_flags'] ) && is_array( $store['feature_flags'] ) ? $store['feature_flags'] : [],
        $state['feature_flags']
    );
    $state['providers'] = tonbankcard_api_admin_merge_providers(
        $state['providers'],
        isset( $store['providers'] ) && is_array( $store['providers'] ) ? $store['providers'] : []
    );
    $state['mini_app'] = tonbankcard_api_admin_enrich_mini_app_state(
        tonbankcard_api_admin_merge_mini_app(
            $state['mini_app'],
            isset( $store['mini_app'] ) && is_array( $store['mini_app'] ) ? $store['mini_app'] : []
        )
    );
    $state['content'] = tonbankcard_api_admin_merge_content(
        $state['content'],
        isset( $store['content'] ) && is_array( $store['content'] ) ? $store['content'] : []
    );
    $state['operations'] = tonbankcard_api_admin_merge_operations(
        $state['operations'],
        isset( $store['operations'] ) && is_array( $store['operations'] ) ? $store['operations'] : []
    );
    $state['analytics'] = tonbankcard_api_admin_merge_analytics(
        $state['analytics'],
        isset( $store['analytics'] ) && is_array( $store['analytics'] ) ? $store['analytics'] : []
    );
    $state['audit_log'] = isset( $store['audit_log'] ) && is_array( $store['audit_log'] )
        ? array_slice( array_values( $store['audit_log'] ), 0, 100 )
        : [];

    return $state;
}

/**
 * Builds safe default admin state from runtime settings.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_admin_default_state( array $runtime, array $config ) {
    $settings = tonbankcard_api_admin_settings( $runtime, $config );
    $providers = isset( $runtime['providers'] ) && is_array( $runtime['providers'] ) ? $runtime['providers'] : [];
    $features = isset( $runtime['feature_flags'] ) && is_array( $runtime['feature_flags'] ) ? $runtime['feature_flags'] : [];
    $alerts = isset( $config['alerts'] ) && is_array( $config['alerts'] ) ? $config['alerts'] : [];

    return [
        'version'          => 1,
        'updated_at'       => null,
        'store_configured' => '' !== trim( $settings['store_path'] ),
        'store_loaded'     => FALSE,
        'feature_flags'    => tonbankcard_api_admin_normalize_feature_flags( $features, [] ),
        'mini_app'         => tonbankcard_api_admin_default_mini_app_state( $runtime, $config ),
        'providers'        => [
            'coingecko' => [
                'api_plan' => isset( $providers['coingecko']['api_plan'] ) ? (string) $providers['coingecko']['api_plan'] : 'demo',
                'api_key'  => tonbankcard_api_admin_secret_metadata_from_config( ! empty( $providers['coingecko']['api_key_configured'] ), 'env:COINGECKO_API_KEY' ),
            ],
            'groq'      => [
                'model_id' => isset( $providers['groq']['model_id'] ) ? (string) $providers['groq']['model_id'] : 'llama-3.3-70b-versatile',
                'api_key'  => tonbankcard_api_admin_secret_metadata_from_config( ! empty( $providers['groq']['api_key_configured'] ), 'env:GROQ_API_KEY' ),
            ],
            'upstash'   => [
                'status'              => ! empty( $providers['upstash']['token_configured'] ) ? 'configured' : 'not_configured',
                'rest_url_configured' => ! empty( $providers['upstash']['rest_url'] ),
                'rest_token'          => tonbankcard_api_admin_secret_metadata_from_config( ! empty( $providers['upstash']['token_configured'] ), 'env:UPSTASH_REDIS_REST_TOKEN' ),
            ],
            'tonapi'    => [
                'api_key' => tonbankcard_api_admin_secret_metadata_from_config( ! empty( $providers['tonapi']['api_key_configured'] ), 'env:TONAPI_API_KEY' ),
            ],
            'telegram'  => [
                'bot_username' => isset( $runtime['telegram']['bot_username'] ) ? (string) $runtime['telegram']['bot_username'] : '',
                'bot_token'    => tonbankcard_api_admin_secret_metadata_from_config( ! empty( $runtime['telegram']['bot_token_configured'] ), 'env:TONBANKCARD_BOT_TOKEN' ),
            ],
            'changenow' => [
                'link_id'     => isset( $providers['changenow']['link_id'] ) ? (string) $providers['changenow']['link_id'] : '',
                'listing_url' => isset( $providers['changenow']['listing_url'] ) ? (string) $providers['changenow']['listing_url'] : '',
            ],
        ],
        'content'          => [
            'legal_copy' => [
                'market_data_disclaimer' => 'Market data is informational only and may be delayed or unavailable.',
                'ai_disclaimer'          => 'AI summaries are informational and not financial advice.',
                'alerts_disclaimer'      => 'Alerts are best-effort notifications and can be delayed.',
                'widget_disclaimer'      => 'Exchange widgets are provided by third parties under their own terms.',
            ],
            'ton_assets'              => [],
            'ton_excluded_asset_ids'  => [],
        ],
        'operations'       => [
            'cache' => [
                'market_stale_mode' => 'serve_stale',
                'purge_status'      => 'idle',
                'last_purge_scope'  => null,
                'last_purge_at'     => null,
            ],
            'alert_thresholds' => [
                'max_alerts_per_user'             => isset( $alerts['max_alerts_per_user'] ) ? (int) $alerts['max_alerts_per_user'] : 20,
                'default_frequency_cap_seconds'   => isset( $alerts['default_frequency_cap_seconds'] ) ? (int) $alerts['default_frequency_cap_seconds'] : 3600,
                'default_max_deliveries_per_day'  => isset( $alerts['default_max_deliveries_per_day'] ) ? (int) $alerts['default_max_deliveries_per_day'] : 8,
                'evaluation_interval_seconds'     => isset( $alerts['evaluation_interval_seconds'] ) ? (int) $alerts['evaluation_interval_seconds'] : 300,
            ],
            'achievements' => [
                'enabled'             => ! empty( $features['gamification'] ),
                'daily_streak_points' => 10,
                'share_points'        => 5,
                'alert_points'        => 10,
            ],
        ],
        'analytics'        => [
            'yandex_metrica' => [
                'counter_id' => '',
                'enabled'    => FALSE,
            ],
        ],
        'audit_log'        => [],
    ];
}

/**
 * Builds safe default Mini App deployment state.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_admin_default_mini_app_state( array $runtime, array $config ) {
    $features = isset( $runtime['feature_flags'] ) && is_array( $runtime['feature_flags'] ) ? $runtime['feature_flags'] : [];
    $urls = isset( $runtime['urls'] ) && is_array( $runtime['urls'] ) ? $runtime['urls'] : [];
    $telegram = isset( $runtime['telegram'] ) && is_array( $runtime['telegram'] ) ? $runtime['telegram'] : [];
    $alerts = isset( $config['alerts'] ) && is_array( $config['alerts'] ) ? $config['alerts'] : [];
    $premium = isset( $config['premium'] ) && is_array( $config['premium'] ) ? $config['premium'] : [];

    return tonbankcard_api_admin_enrich_mini_app_state(
        [
            'runtime'  => [
                'profile'           => tonbankcard_api_admin_runtime_profile( isset( $runtime['profile'] ) ? $runtime['profile'] : 'local' ),
                'public_base_url'   => isset( $urls['public'] ) ? (string) $urls['public'] : '',
                'telegram_base_url' => isset( $urls['telegram'] ) ? (string) $urls['telegram'] : '',
            ],
            'telegram' => [
                'bot_username'   => tonbankcard_api_admin_telegram_username( isset( $telegram['bot_username'] ) ? $telegram['bot_username'] : '' ),
                'bot_token'      => tonbankcard_api_admin_secret_metadata_from_config( ! empty( $telegram['bot_token_configured'] ), 'env:TONBANKCARD_BOT_TOKEN' ),
                'webhook_secret' => tonbankcard_api_admin_secret_metadata_from_config( ! empty( $telegram['webhook_secret_configured'] ), 'env:TONBANKCARD_BOT_WEBHOOK_SECRET' ),
            ],
            'features' => [
                'alerts'      => ! empty( $features['alerts'] ),
                'premium'     => ! empty( $features['premium'] ),
                'referrals'   => ! empty( $features['referrals'] ),
                'ton_connect' => ! empty( $features['ton_connect'] ),
            ],
            'alerts'   => [
                'worker_token'                  => tonbankcard_api_admin_secret_metadata_from_config( ! empty( $alerts['worker_token'] ), 'env:TONBANKCARD_ALERT_WORKER_TOKEN' ),
                'max_alerts_per_user'           => isset( $alerts['max_alerts_per_user'] ) ? (int) $alerts['max_alerts_per_user'] : 20,
                'default_frequency_cap_seconds' => isset( $alerts['default_frequency_cap_seconds'] ) ? (int) $alerts['default_frequency_cap_seconds'] : 3600,
                'default_max_deliveries_per_day' => isset( $alerts['default_max_deliveries_per_day'] ) ? (int) $alerts['default_max_deliveries_per_day'] : 8,
                'evaluation_interval_seconds'   => isset( $alerts['evaluation_interval_seconds'] ) ? (int) $alerts['evaluation_interval_seconds'] : 300,
            ],
            'premium'  => [
                'plan_code'                   => isset( $premium['monthly_plan_code'] ) ? (string) $premium['monthly_plan_code'] : 'premium_monthly',
                'monthly_stars'               => isset( $premium['monthly_price_stars'] ) ? (int) $premium['monthly_price_stars'] : 199,
                'subscription_period_seconds' => isset( $premium['subscription_period_seconds'] ) ? (int) $premium['subscription_period_seconds'] : 2592000,
                'signing_secret'              => tonbankcard_api_admin_secret_metadata_from_config( ! empty( $premium['signing_secret'] ), 'env:TONBANKCARD_PREMIUM_SIGNING_SECRET' ),
            ],
        ]
    );
}

/**
 * Updates the Telegram Mini App deployment setup.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $actor
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_admin_update_mini_app( array $request, array $runtime, array $config, array $actor, string $request_id, array $headers ) {
    $body = tonbankcard_api_json_body( $request );
    $input = isset( $body['mini_app'] ) && is_array( $body['mini_app'] ) ? $body['mini_app'] : $body;
    $state = tonbankcard_api_admin_load_state( $runtime, $config );
    $before = $state['mini_app'];

    $state['mini_app'] = tonbankcard_api_admin_enrich_mini_app_state(
        tonbankcard_api_admin_merge_mini_app( $state['mini_app'], $input )
    );
    $state['feature_flags'] = tonbankcard_api_admin_feature_flags_from_mini_app( $state['feature_flags'], $state['mini_app'] );
    $state['providers'] = tonbankcard_api_admin_providers_from_mini_app( $state['providers'], $state['mini_app'] );
    $state['operations'] = tonbankcard_api_admin_operations_from_mini_app( $state['operations'], $state['mini_app'] );
    $state = tonbankcard_api_admin_record_write( $state, $actor, 'mini_app.updated', 'mini_app', $before, $state['mini_app'], $request_id );

    $env_updates = tonbankcard_api_admin_mini_app_env_updates( $state['mini_app'], $input );
    $saved = tonbankcard_api_admin_save_state( $state, $runtime, $config, $env_updates );
    if ( empty( $saved['ok'] ) ) {
        return tonbankcard_api_admin_store_error( $saved, $request_id, $headers );
    }

    return tonbankcard_api_success_response(
        [
            'actor'         => tonbankcard_api_admin_public_actor( $actor ),
            'mini_app'      => $state['mini_app'],
            'feature_flags' => $state['feature_flags'],
        ],
        $request_id,
        $headers
    );
}

/**
 * Checks the Telegram bot token and registers Mini App webhook automation.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $actor
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_admin_setup_mini_app_telegram( array $request, array $runtime, array $config, array $actor, string $request_id, array $headers ) {
    $body = tonbankcard_api_json_body( $request );
    $input = isset( $body['mini_app'] ) && is_array( $body['mini_app'] ) ? $body['mini_app'] : $body;
    $state = tonbankcard_api_admin_load_state( $runtime, $config );
    $before = $state['mini_app'];

    $mini_app = tonbankcard_api_admin_enrich_mini_app_state(
        tonbankcard_api_admin_merge_mini_app( $state['mini_app'], $input )
    );
    $bot_token = tonbankcard_api_admin_mini_app_secret_value( $input, 'bot_token', [ 'telegram', 'bot_token' ], 'TONBANKCARD_BOT_TOKEN' );
    if ( '' === $bot_token ) {
        return tonbankcard_api_error_response(
            400,
            'telegram_bot_token_required',
            'Enter a Telegram bot token before checking and registering the Mini App webhook.',
            [ 'field' => 'bot_token' ],
            $request_id,
            $headers
        );
    }
    if ( ! tonbankcard_api_admin_valid_telegram_bot_token( $bot_token ) ) {
        return tonbankcard_api_error_response(
            400,
            'telegram_bot_token_invalid',
            'The Telegram bot token format is invalid.',
            [ 'field' => 'bot_token' ],
            $request_id,
            $headers
        );
    }

    if ( ! tonbankcard_api_admin_is_https_url( $mini_app['runtime']['telegram_base_url'] ) ) {
        return tonbankcard_api_error_response(
            400,
            'telegram_mini_app_https_required',
            'Telegram Mini App URL must be an HTTPS URL before registering a webhook.',
            [ 'field' => 'telegram_base_url' ],
            $request_id,
            $headers
        );
    }

    $webhook_url = isset( $mini_app['launch']['webhook_url'] ) ? (string) $mini_app['launch']['webhook_url'] : '';
    if ( '' === $webhook_url || ! tonbankcard_api_admin_is_https_url( $webhook_url ) ) {
        return tonbankcard_api_error_response(
            400,
            'telegram_webhook_https_required',
            'Telegram webhook URL must be an HTTPS URL.',
            [ 'field' => 'launch.webhook_url' ],
            $request_id,
            $headers
        );
    }

    $webhook_secret = tonbankcard_api_admin_mini_app_secret_value( $input, 'webhook_secret', [ 'telegram', 'webhook_secret' ], 'TONBANKCARD_BOT_WEBHOOK_SECRET' );
    if ( '' === $webhook_secret ) {
        $webhook_secret = tonbankcard_api_admin_generate_telegram_webhook_secret();
    }
    if ( ! tonbankcard_api_admin_valid_telegram_webhook_secret( $webhook_secret ) ) {
        return tonbankcard_api_error_response(
            400,
            'telegram_webhook_secret_invalid',
            'Telegram webhook secret must contain only letters, digits, underscore, or hyphen.',
            [ 'field' => 'webhook_secret' ],
            $request_id,
            $headers
        );
    }

    $get_me = tonbankcard_api_admin_telegram_api_call( 'getMe', $bot_token, [], $config );
    if ( empty( $get_me['ok'] ) ) {
        return tonbankcard_api_admin_telegram_call_error_response(
            $get_me,
            'telegram_bot_check_failed',
            'Telegram did not accept the bot token.',
            $request_id,
            $headers
        );
    }

    $bot = tonbankcard_api_admin_telegram_public_bot( isset( $get_me['result'] ) && is_array( $get_me['result'] ) ? $get_me['result'] : [] );
    if ( empty( $bot['is_bot'] ) ) {
        return tonbankcard_api_error_response(
            502,
            'telegram_bot_identity_invalid',
            'Telegram getMe did not return a bot identity.',
            [ 'method' => 'getMe' ],
            $request_id,
            $headers
        );
    }

    if ( '' !== $bot['username'] ) {
        $mini_app['telegram']['bot_username'] = $bot['username'];
    }
    $mini_app['telegram']['bot_token'] = tonbankcard_api_admin_secret_metadata( $bot_token, 'TONBANKCARD_BOT_TOKEN' );
    $mini_app['telegram']['webhook_secret'] = tonbankcard_api_admin_secret_metadata( $webhook_secret, 'TONBANKCARD_BOT_WEBHOOK_SECRET' );
    $mini_app = tonbankcard_api_admin_enrich_mini_app_state( $mini_app );

    $allowed_updates = tonbankcard_api_admin_telegram_allowed_updates();
    $set_webhook = tonbankcard_api_admin_telegram_api_call(
        'setWebhook',
        $bot_token,
        [
            'url'                  => $mini_app['launch']['webhook_url'],
            'secret_token'         => $webhook_secret,
            'allowed_updates'      => $allowed_updates,
            'drop_pending_updates' => FALSE,
        ],
        $config
    );
    if ( empty( $set_webhook['ok'] ) ) {
        return tonbankcard_api_admin_telegram_call_error_response(
            $set_webhook,
            'telegram_webhook_registration_failed',
            'Telegram webhook registration failed.',
            $request_id,
            $headers
        );
    }

    $commands = tonbankcard_api_admin_telegram_bot_commands();
    $set_commands = tonbankcard_api_admin_telegram_api_call( 'setMyCommands', $bot_token, [ 'commands' => $commands ], $config );
    if ( empty( $set_commands['ok'] ) ) {
        return tonbankcard_api_admin_telegram_call_error_response(
            $set_commands,
            'telegram_commands_registration_failed',
            'Telegram bot command registration failed.',
            $request_id,
            $headers
        );
    }

    $menu_button = [
        'type'    => 'web_app',
        'text'    => 'Open Mini App',
        'web_app' => [
            'url' => $mini_app['launch']['mini_app_url'],
        ],
    ];
    $set_menu = tonbankcard_api_admin_telegram_api_call( 'setChatMenuButton', $bot_token, [ 'menu_button' => $menu_button ], $config );
    if ( empty( $set_menu['ok'] ) ) {
        return tonbankcard_api_admin_telegram_call_error_response(
            $set_menu,
            'telegram_menu_button_registration_failed',
            'Telegram Mini App menu button registration failed.',
            $request_id,
            $headers
        );
    }

    $webhook_info = tonbankcard_api_admin_telegram_api_call( 'getWebhookInfo', $bot_token, [], $config );
    if ( empty( $webhook_info['ok'] ) ) {
        return tonbankcard_api_admin_telegram_call_error_response(
            $webhook_info,
            'telegram_webhook_info_failed',
            'Telegram webhook verification failed.',
            $request_id,
            $headers
        );
    }

    $mini_app['telegram_setup'] = tonbankcard_api_admin_telegram_setup_payload(
        $bot,
        $mini_app,
        $allowed_updates,
        $commands,
        $menu_button,
        isset( $webhook_info['result'] ) && is_array( $webhook_info['result'] ) ? $webhook_info['result'] : []
    );

    $state['mini_app'] = tonbankcard_api_admin_enrich_mini_app_state( $mini_app );
    $state['feature_flags'] = tonbankcard_api_admin_feature_flags_from_mini_app( $state['feature_flags'], $state['mini_app'] );
    $state['providers'] = tonbankcard_api_admin_providers_from_mini_app( $state['providers'], $state['mini_app'] );
    $state['operations'] = tonbankcard_api_admin_operations_from_mini_app( $state['operations'], $state['mini_app'] );
    $state = tonbankcard_api_admin_record_write( $state, $actor, 'mini_app.telegram_setup', 'mini_app', $before, $state['mini_app'], $request_id );

    $persist_input = $input;
    $persist_input['bot_token'] = $bot_token;
    $persist_input['webhook_secret'] = $webhook_secret;
    if ( '' !== $bot['username'] ) {
        $persist_input['bot_username'] = $bot['username'];
    }
    $env_updates = tonbankcard_api_admin_mini_app_env_updates( $state['mini_app'], $persist_input );
    $saved = tonbankcard_api_admin_save_state( $state, $runtime, $config, $env_updates );
    if ( empty( $saved['ok'] ) ) {
        return tonbankcard_api_admin_store_error( $saved, $request_id, $headers );
    }

    return tonbankcard_api_success_response(
        [
            'actor'          => tonbankcard_api_admin_public_actor( $actor ),
            'mini_app'       => $state['mini_app'],
            'feature_flags'  => $state['feature_flags'],
            'telegram_setup' => $state['mini_app']['telegram_setup'],
        ],
        $request_id,
        $headers
    );
}

/**
 * Updates feature flags.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $actor
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_admin_update_feature_flags( array $request, array $runtime, array $config, array $actor, string $request_id, array $headers ) {
    $body = tonbankcard_api_json_body( $request );
    $input = isset( $body['feature_flags'] ) && is_array( $body['feature_flags'] ) ? $body['feature_flags'] : $body;
    $state = tonbankcard_api_admin_load_state( $runtime, $config );
    $before = $state['feature_flags'];
    $state['feature_flags'] = tonbankcard_api_admin_normalize_feature_flags( $input, $state['feature_flags'] );
    $state = tonbankcard_api_admin_record_write( $state, $actor, 'feature_flags.updated', 'feature_flags', $before, $state['feature_flags'], $request_id );

    $env_updates = tonbankcard_api_admin_feature_flag_env_updates( $input );
    $saved = tonbankcard_api_admin_save_state( $state, $runtime, $config, $env_updates );
    if ( empty( $saved['ok'] ) ) {
        return tonbankcard_api_admin_store_error( $saved, $request_id, $headers );
    }

    return tonbankcard_api_success_response(
        [
            'actor'         => tonbankcard_api_admin_public_actor( $actor ),
            'feature_flags' => $state['feature_flags'],
        ],
        $request_id,
        $headers
    );
}

/**
 * Updates provider metadata and write-only secret references.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $actor
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_admin_update_providers( array $request, array $runtime, array $config, array $actor, string $request_id, array $headers ) {
    $body = tonbankcard_api_json_body( $request );
    $input = isset( $body['providers'] ) && is_array( $body['providers'] ) ? $body['providers'] : $body;
    $state = tonbankcard_api_admin_load_state( $runtime, $config );
    $before = $state['providers'];
    $state['providers'] = tonbankcard_api_admin_merge_providers( $state['providers'], tonbankcard_api_admin_normalize_provider_input( $input, $state['providers'] ) );
    if ( isset( $body['analytics'] ) && is_array( $body['analytics'] ) ) {
        $state['analytics'] = tonbankcard_api_admin_merge_analytics( $state['analytics'], $body['analytics'] );
    }
    $state = tonbankcard_api_admin_record_write( $state, $actor, 'providers.updated', 'providers', $before, $state['providers'], $request_id );

    $env_updates = array_merge(
        tonbankcard_api_admin_provider_env_updates( $input ),
        isset( $body['analytics'] ) && is_array( $body['analytics'] ) ? tonbankcard_api_admin_analytics_env_updates( $body['analytics'] ) : []
    );
    $saved = tonbankcard_api_admin_save_state( $state, $runtime, $config, $env_updates );
    if ( empty( $saved['ok'] ) ) {
        return tonbankcard_api_admin_store_error( $saved, $request_id, $headers );
    }

    return tonbankcard_api_success_response(
        [
            'actor'     => tonbankcard_api_admin_public_actor( $actor ),
            'providers' => $state['providers'],
            'analytics' => $state['analytics'],
        ],
        $request_id,
        $headers
    );
}

/**
 * Updates admin-managed content.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $actor
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_admin_update_content( array $request, array $runtime, array $config, array $actor, string $request_id, array $headers ) {
    $body = tonbankcard_api_json_body( $request );
    $input = isset( $body['content'] ) && is_array( $body['content'] ) ? $body['content'] : $body;
    $state = tonbankcard_api_admin_load_state( $runtime, $config );
    $before = $state['content'];
    $state['content'] = tonbankcard_api_admin_merge_content( $state['content'], $input );
    $state = tonbankcard_api_admin_record_write( $state, $actor, 'content.updated', 'content', $before, $state['content'], $request_id );

    $saved = tonbankcard_api_admin_save_state( $state, $runtime, $config );
    if ( empty( $saved['ok'] ) ) {
        return tonbankcard_api_admin_store_error( $saved, $request_id, $headers );
    }

    return tonbankcard_api_success_response(
        [
            'actor'  => tonbankcard_api_admin_public_actor( $actor ),
            'content' => $state['content'],
        ],
        $request_id,
        $headers
    );
}

/**
 * Updates alert thresholds and achievement settings.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $actor
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_admin_update_operations( array $request, array $runtime, array $config, array $actor, string $request_id, array $headers ) {
    $body = tonbankcard_api_json_body( $request );
    $input = isset( $body['operations'] ) && is_array( $body['operations'] ) ? $body['operations'] : $body;
    $state = tonbankcard_api_admin_load_state( $runtime, $config );
    $before = $state['operations'];
    $state['operations'] = tonbankcard_api_admin_merge_operations( $state['operations'], $input );
    if ( isset( $body['analytics'] ) && is_array( $body['analytics'] ) ) {
        $state['analytics'] = tonbankcard_api_admin_merge_analytics( $state['analytics'], $body['analytics'] );
    }
    $state = tonbankcard_api_admin_record_write( $state, $actor, 'operations.updated', 'operations', $before, $state['operations'], $request_id );

    $env_updates = isset( $body['analytics'] ) && is_array( $body['analytics'] ) ? tonbankcard_api_admin_analytics_env_updates( $body['analytics'] ) : [];
    $saved = tonbankcard_api_admin_save_state( $state, $runtime, $config, $env_updates );
    if ( empty( $saved['ok'] ) ) {
        return tonbankcard_api_admin_store_error( $saved, $request_id, $headers );
    }

    return tonbankcard_api_success_response(
        [
            'actor'      => tonbankcard_api_admin_public_actor( $actor ),
            'operations' => $state['operations'],
            'analytics'  => $state['analytics'],
        ],
        $request_id,
        $headers
    );
}

/**
 * Records a cache purge request for operator follow-up.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $actor
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_admin_cache_purge( array $request, array $runtime, array $config, array $actor, string $request_id, array $headers ) {
    $body = tonbankcard_api_json_body( $request );
    $scope = tonbankcard_api_admin_slug( isset( $body['scope'] ) ? $body['scope'] : 'all', 'all' );
    $state = tonbankcard_api_admin_load_state( $runtime, $config );
    $before = $state['operations']['cache'];
    $state['operations']['cache']['purge_status'] = 'requested';
    $state['operations']['cache']['last_purge_scope'] = $scope;
    $state['operations']['cache']['last_purge_at'] = gmdate( 'c' );
    $state = tonbankcard_api_admin_record_write( $state, $actor, 'cache.purge_requested', 'cache', $before, $state['operations']['cache'], $request_id );

    $saved = tonbankcard_api_admin_save_state( $state, $runtime, $config );
    if ( empty( $saved['ok'] ) ) {
        return tonbankcard_api_admin_store_error( $saved, $request_id, $headers );
    }

    return tonbankcard_api_success_response(
        [
            'accepted' => TRUE,
            'scope'    => $scope,
            'cache'    => $state['operations']['cache'],
        ],
        $request_id,
        $headers,
        202
    );
}

/**
 * Normalizes feature flags.
 *
 * @param array $input
 * @param array $base
 * @return array
 */
function tonbankcard_api_admin_normalize_feature_flags( array $input, array $base ) {
    $flags = array_merge(
        [
            'ai'           => FALSE,
            'alerts'       => FALSE,
            'widget'       => FALSE,
            'changenow'    => FALSE,
            'ton_connect'  => FALSE,
            'referrals'    => FALSE,
            'gamification' => FALSE,
            'premium'      => FALSE,
        ],
        $base
    );

    foreach ( array_keys( $flags ) as $flag ) {
        if ( array_key_exists( $flag, $input ) ) {
            $flags[ $flag ] = (bool) $input[ $flag ];
        }
    }

    if ( array_key_exists( 'widget', $input ) ) {
        $flags['changenow'] = (bool) $input['widget'];
    } elseif ( array_key_exists( 'changenow', $input ) ) {
        $flags['widget'] = (bool) $input['changenow'];
    }

    return $flags;
}

/**
 * Returns environment updates represented by a feature flag payload.
 *
 * @param array $input
 * @return array
 */
function tonbankcard_api_admin_feature_flag_env_updates( array $input ) {
    $map = [
        'ai'           => 'TONBANKCARD_FEATURE_AI',
        'alerts'       => 'TONBANKCARD_FEATURE_ALERTS',
        'widget'       => 'TONBANKCARD_FEATURE_WIDGET',
        'changenow'    => 'TONBANKCARD_FEATURE_CHANGENOW',
        'ton_connect'  => 'TONBANKCARD_FEATURE_TON_CONNECT',
        'referrals'    => 'TONBANKCARD_FEATURE_REFERRALS',
        'gamification' => 'TONBANKCARD_FEATURE_GAMIFICATION',
        'premium'      => 'TONBANKCARD_FEATURE_PREMIUM',
    ];
    $updates = [];
    foreach ( $map as $flag => $env_key ) {
        if ( array_key_exists( $flag, $input ) ) {
            $updates[ $env_key ] = ! empty( $input[ $flag ] ) ? 'true' : 'false';
        }
    }
    if ( array_key_exists( 'widget', $input ) && ! array_key_exists( 'TONBANKCARD_FEATURE_CHANGENOW', $updates ) ) {
        $updates['TONBANKCARD_FEATURE_CHANGENOW'] = ! empty( $input['widget'] ) ? 'true' : 'false';
    } elseif ( array_key_exists( 'changenow', $input ) && ! array_key_exists( 'TONBANKCARD_FEATURE_WIDGET', $updates ) ) {
        $updates['TONBANKCARD_FEATURE_WIDGET'] = ! empty( $input['changenow'] ) ? 'true' : 'false';
    }

    return $updates;
}

/**
 * Returns environment updates represented by a provider payload.
 *
 * @param array $input
 * @return array
 */
function tonbankcard_api_admin_provider_env_updates( array $input ) {
    $updates = [];
    if ( isset( $input['coingecko'] ) && is_array( $input['coingecko'] ) ) {
        if ( isset( $input['coingecko']['api_plan'] ) ) {
            $plan = strtolower( trim( (string) $input['coingecko']['api_plan'] ) );
            if ( in_array( $plan, [ 'demo', 'pro' ], TRUE ) ) {
                $updates['COINGECKO_API_PLAN'] = $plan;
            }
        }
        if ( isset( $input['coingecko']['api_key'] ) && '' !== trim( (string) $input['coingecko']['api_key'] ) ) {
            $updates['COINGECKO_API_KEY'] = trim( (string) $input['coingecko']['api_key'] );
        }
    }
    if ( isset( $input['groq'] ) && is_array( $input['groq'] ) ) {
        if ( isset( $input['groq']['model_id'] ) ) {
            $model = tonbankcard_api_admin_safe_text( $input['groq']['model_id'], 96 );
            if ( '' !== $model ) {
                $updates['GROQ_MODEL_ID'] = $model;
            }
        }
        if ( isset( $input['groq']['api_key'] ) && '' !== trim( (string) $input['groq']['api_key'] ) ) {
            $updates['GROQ_API_KEY'] = trim( (string) $input['groq']['api_key'] );
        }
    }
    if ( isset( $input['upstash'] ) && is_array( $input['upstash'] ) ) {
        if ( isset( $input['upstash']['rest_token'] ) && '' !== trim( (string) $input['upstash']['rest_token'] ) ) {
            $updates['UPSTASH_REDIS_REST_TOKEN'] = trim( (string) $input['upstash']['rest_token'] );
        }
    }
    if ( isset( $input['tonapi'] ) && is_array( $input['tonapi'] ) ) {
        if ( isset( $input['tonapi']['api_key'] ) && '' !== trim( (string) $input['tonapi']['api_key'] ) ) {
            $updates['TONAPI_API_KEY'] = trim( (string) $input['tonapi']['api_key'] );
        }
    }
    if ( isset( $input['telegram'] ) && is_array( $input['telegram'] ) ) {
        if ( isset( $input['telegram']['bot_username'] ) ) {
            $updates['TONBANKCARD_BOT_USERNAME'] = tonbankcard_api_admin_safe_text( $input['telegram']['bot_username'], 64 );
        }
        if ( isset( $input['telegram']['bot_token'] ) && '' !== trim( (string) $input['telegram']['bot_token'] ) ) {
            $updates['TONBANKCARD_BOT_TOKEN'] = trim( (string) $input['telegram']['bot_token'] );
        }
    }
    if ( isset( $input['changenow'] ) && is_array( $input['changenow'] ) && isset( $input['changenow']['link_id'] ) ) {
        $updates['CHANGENOW_LINK_ID'] = tonbankcard_api_admin_safe_text( $input['changenow']['link_id'], 96 );
    }

    return $updates;
}

/**
 * Returns environment updates represented by an analytics payload.
 *
 * @param array $input
 * @return array
 */
function tonbankcard_api_admin_analytics_env_updates( array $input ) {
    $analytics = tonbankcard_api_admin_merge_analytics( [], $input );
    $metrica = isset( $analytics['yandex_metrica'] ) && is_array( $analytics['yandex_metrica'] ) ? $analytics['yandex_metrica'] : [];

    $updates = [];
    if ( isset( $input['yandex_metrica'] ) && is_array( $input['yandex_metrica'] ) ) {
        if ( array_key_exists( 'counter_id', $input['yandex_metrica'] ) ) {
            $updates['YANDEX_METRICA_COUNTER_ID'] = isset( $metrica['counter_id'] ) ? (string) $metrica['counter_id'] : '';
        }
        if ( array_key_exists( 'enabled', $input['yandex_metrica'] ) ) {
            $updates['YANDEX_METRICA_ENABLED'] = ! empty( $metrica['enabled'] ) ? 'true' : 'false';
        }
    }

    return $updates;
}

/**
 * Normalizes provider update input.
 *
 * @param array $input
 * @param array $current
 * @return array
 */
function tonbankcard_api_admin_normalize_provider_input( array $input, array $current ) {
    $providers = [];

    if ( isset( $input['coingecko'] ) && is_array( $input['coingecko'] ) ) {
        $plan = isset( $input['coingecko']['api_plan'] ) ? strtolower( trim( (string) $input['coingecko']['api_plan'] ) ) : '';
        $providers['coingecko'] = [];
        if ( in_array( $plan, [ 'demo', 'pro' ], TRUE ) ) {
            $providers['coingecko']['api_plan'] = $plan;
        }
        if ( isset( $input['coingecko']['api_key'] ) && '' !== trim( (string) $input['coingecko']['api_key'] ) ) {
            $providers['coingecko']['api_key'] = tonbankcard_api_admin_secret_metadata( (string) $input['coingecko']['api_key'], 'COINGECKO_API_KEY' );
        }
    }

    if ( isset( $input['groq'] ) && is_array( $input['groq'] ) ) {
        $providers['groq'] = [];
        if ( isset( $input['groq']['model_id'] ) ) {
            $model = tonbankcard_api_admin_safe_text( $input['groq']['model_id'], 96 );
            if ( '' !== $model ) {
                $providers['groq']['model_id'] = $model;
            }
        }
        if ( isset( $input['groq']['api_key'] ) && '' !== trim( (string) $input['groq']['api_key'] ) ) {
            $providers['groq']['api_key'] = tonbankcard_api_admin_secret_metadata( (string) $input['groq']['api_key'], 'GROQ_API_KEY' );
        }
    }

    if ( isset( $input['upstash'] ) && is_array( $input['upstash'] ) ) {
        $providers['upstash'] = [];
        if ( isset( $input['upstash']['status'] ) ) {
            $status = tonbankcard_api_admin_slug( $input['upstash']['status'], 'configured' );
            if ( in_array( $status, [ 'enabled', 'configured', 'not_configured', 'disabled' ], TRUE ) ) {
                $providers['upstash']['status'] = $status;
            }
        }
        if ( isset( $input['upstash']['rest_url_configured'] ) ) {
            $providers['upstash']['rest_url_configured'] = (bool) $input['upstash']['rest_url_configured'];
        }
        if ( isset( $input['upstash']['rest_token'] ) && '' !== trim( (string) $input['upstash']['rest_token'] ) ) {
            $providers['upstash']['rest_token'] = tonbankcard_api_admin_secret_metadata( (string) $input['upstash']['rest_token'], 'UPSTASH_REDIS_REST_TOKEN' );
        }
    }

    if ( isset( $input['tonapi'] ) && is_array( $input['tonapi'] ) ) {
        $providers['tonapi'] = [];
        if ( isset( $input['tonapi']['api_key'] ) && '' !== trim( (string) $input['tonapi']['api_key'] ) ) {
            $providers['tonapi']['api_key'] = tonbankcard_api_admin_secret_metadata( (string) $input['tonapi']['api_key'], 'TONAPI_API_KEY' );
        }
    }

    if ( isset( $input['telegram'] ) && is_array( $input['telegram'] ) ) {
        $providers['telegram'] = [];
        if ( isset( $input['telegram']['bot_username'] ) ) {
            $providers['telegram']['bot_username'] = tonbankcard_api_admin_safe_text( $input['telegram']['bot_username'], 64 );
        }
        if ( isset( $input['telegram']['bot_token'] ) && '' !== trim( (string) $input['telegram']['bot_token'] ) ) {
            $providers['telegram']['bot_token'] = tonbankcard_api_admin_secret_metadata( (string) $input['telegram']['bot_token'], 'TONBANKCARD_BOT_TOKEN' );
        }
    }

    if ( isset( $input['changenow'] ) && is_array( $input['changenow'] ) ) {
        $providers['changenow'] = [];
        if ( isset( $input['changenow']['link_id'] ) ) {
            $providers['changenow']['link_id'] = tonbankcard_api_admin_safe_text( $input['changenow']['link_id'], 96 );
        }
        if ( isset( $input['changenow']['listing_url'] ) ) {
            $providers['changenow']['listing_url'] = tonbankcard_api_admin_safe_text( $input['changenow']['listing_url'], 512 );
        }
    }

    return $providers;
}

/**
 * Merges provider maps recursively.
 *
 * @param array $base
 * @param array $overrides
 * @return array
 */
function tonbankcard_api_admin_merge_providers( array $base, array $overrides ) {
    foreach ( $overrides as $provider => $settings ) {
        if ( ! is_array( $settings ) ) {
            continue;
        }
        if ( ! isset( $base[ $provider ] ) || ! is_array( $base[ $provider ] ) ) {
            $base[ $provider ] = [];
        }
        foreach ( $settings as $key => $value ) {
            if ( is_array( $value ) && isset( $base[ $provider ][ $key ] ) && is_array( $base[ $provider ][ $key ] ) ) {
                $base[ $provider ][ $key ] = array_merge( $base[ $provider ][ $key ], $value );
            } else {
                $base[ $provider ][ $key ] = $value;
            }
        }
    }

    return $base;
}

/**
 * Merges content settings.
 *
 * @param array $base
 * @param array $input
 * @return array
 */
function tonbankcard_api_admin_merge_content( array $base, array $input ) {
    if ( isset( $input['legal_copy'] ) && is_array( $input['legal_copy'] ) ) {
        foreach ( $input['legal_copy'] as $key => $value ) {
            $safe_key = tonbankcard_api_admin_slug( $key, '' );
            if ( '' !== $safe_key ) {
                $base['legal_copy'][ $safe_key ] = tonbankcard_api_admin_safe_text( $value, 2000 );
            }
        }
    }

    if ( isset( $input['ton_assets'] ) && is_array( $input['ton_assets'] ) ) {
        $assets = [];
        foreach ( $input['ton_assets'] as $asset ) {
            if ( ! is_array( $asset ) ) {
                continue;
            }
            $normalized = function_exists( 'tonbankcard_api_ton_normalize_asset' )
                ? tonbankcard_api_ton_normalize_asset( $asset )
                : $asset;
            if ( ! empty( $normalized['id'] ) && ! empty( $normalized['name'] ) ) {
                $assets[] = $normalized;
            }
        }
        $base['ton_assets'] = array_slice( $assets, 0, 100 );
    }

    if ( isset( $input['ton_excluded_asset_ids'] ) && is_array( $input['ton_excluded_asset_ids'] ) ) {
        $excluded = [];
        foreach ( $input['ton_excluded_asset_ids'] as $id ) {
            $safe = tonbankcard_api_admin_slug( $id, '' );
            if ( '' !== $safe ) {
                $excluded[] = $safe;
            }
        }
        $base['ton_excluded_asset_ids'] = array_values( array_unique( array_slice( $excluded, 0, 200 ) ) );
    }

    return $base;
}

/**
 * Merges operations settings.
 *
 * @param array $base
 * @param array $input
 * @return array
 */
function tonbankcard_api_admin_merge_operations( array $base, array $input ) {
    if ( isset( $input['cache'] ) && is_array( $input['cache'] ) ) {
        if ( isset( $input['cache']['market_stale_mode'] ) ) {
            $mode = tonbankcard_api_admin_slug( $input['cache']['market_stale_mode'], 'serve_stale' );
            if ( in_array( $mode, [ 'serve_stale', 'bypass', 'disabled' ], TRUE ) ) {
                $base['cache']['market_stale_mode'] = $mode;
            }
        }
    }

    if ( isset( $input['alert_thresholds'] ) && is_array( $input['alert_thresholds'] ) ) {
        foreach ( [ 'max_alerts_per_user', 'default_frequency_cap_seconds', 'default_max_deliveries_per_day', 'evaluation_interval_seconds' ] as $key ) {
            if ( array_key_exists( $key, $input['alert_thresholds'] ) ) {
                $base['alert_thresholds'][ $key ] = max( 1, min( 86400, (int) $input['alert_thresholds'][ $key ] ) );
            }
        }
    }

    if ( isset( $input['achievements'] ) && is_array( $input['achievements'] ) ) {
        if ( array_key_exists( 'enabled', $input['achievements'] ) ) {
            $base['achievements']['enabled'] = (bool) $input['achievements']['enabled'];
        }
        foreach ( [ 'daily_streak_points', 'share_points', 'alert_points' ] as $key ) {
            if ( array_key_exists( $key, $input['achievements'] ) ) {
                $base['achievements'][ $key ] = max( 0, min( 100000, (int) $input['achievements'][ $key ] ) );
            }
        }
    }

    return $base;
}

/**
 * Merges analytics settings.
 *
 * @param array $base
 * @param array $input
 * @return array
 */
function tonbankcard_api_admin_merge_analytics( array $base, array $input ) {
    if ( ! isset( $base['yandex_metrica'] ) || ! is_array( $base['yandex_metrica'] ) ) {
        $base['yandex_metrica'] = [
            'counter_id' => '',
            'enabled'    => FALSE,
        ];
    }

    if ( isset( $input['yandex_metrica'] ) && is_array( $input['yandex_metrica'] ) ) {
        if ( array_key_exists( 'counter_id', $input['yandex_metrica'] ) ) {
            $base['yandex_metrica']['counter_id'] = tonbankcard_api_admin_numeric_id( $input['yandex_metrica']['counter_id'], 16 );
        }
        if ( array_key_exists( 'enabled', $input['yandex_metrica'] ) ) {
            $base['yandex_metrica']['enabled'] = (bool) $input['yandex_metrica']['enabled'];
        }
    }

    if ( '' === $base['yandex_metrica']['counter_id'] ) {
        $base['yandex_metrica']['enabled'] = FALSE;
    }

    return $base;
}

/**
 * Merges Mini App deployment setup settings.
 *
 * @param array $base
 * @param array $input
 * @return array
 */
function tonbankcard_api_admin_merge_mini_app( array $base, array $input ) {
    $base = tonbankcard_api_admin_mini_app_shape( $base );

    $found = FALSE;
    $value = tonbankcard_api_admin_mini_app_input_value( $input, 'profile', [ 'runtime', 'profile' ], $found );
    if ( $found ) {
        $base['runtime']['profile'] = tonbankcard_api_admin_runtime_profile( $value );
    }

    $value = tonbankcard_api_admin_mini_app_input_value( $input, 'public_base_url', [ 'runtime', 'public_base_url' ], $found );
    if ( $found ) {
        $base['runtime']['public_base_url'] = tonbankcard_api_admin_absolute_url( $value );
    }

    $value = tonbankcard_api_admin_mini_app_input_value( $input, 'telegram_base_url', [ 'runtime', 'telegram_base_url' ], $found );
    if ( $found ) {
        $base['runtime']['telegram_base_url'] = tonbankcard_api_admin_absolute_url( $value );
    }

    $value = tonbankcard_api_admin_mini_app_input_value( $input, 'bot_username', [ 'telegram', 'bot_username' ], $found );
    if ( $found ) {
        $base['telegram']['bot_username'] = tonbankcard_api_admin_telegram_username( $value );
    }

    foreach (
        [
            [ 'bot_token', [ 'telegram', 'bot_token' ], [ 'telegram', 'bot_token' ], 'TONBANKCARD_BOT_TOKEN' ],
            [ 'webhook_secret', [ 'telegram', 'webhook_secret' ], [ 'telegram', 'webhook_secret' ], 'TONBANKCARD_BOT_WEBHOOK_SECRET' ],
            [ 'alert_worker_token', [ 'alerts', 'worker_token' ], [ 'alerts', 'worker_token' ], 'TONBANKCARD_ALERT_WORKER_TOKEN' ],
            [ 'premium_signing_secret', [ 'premium', 'signing_secret' ], [ 'premium', 'signing_secret' ], 'TONBANKCARD_PREMIUM_SIGNING_SECRET' ],
        ] as $secret_field
    ) {
        $value = tonbankcard_api_admin_mini_app_input_value( $input, $secret_field[0], $secret_field[1], $found );
        if ( ! $found ) {
            continue;
        }
        $target = $secret_field[2];
        if ( is_array( $value ) ) {
            $base[ $target[0] ][ $target[1] ] = array_merge( $base[ $target[0] ][ $target[1] ], $value );
        } elseif ( '' !== trim( (string) $value ) ) {
            $base[ $target[0] ][ $target[1] ] = tonbankcard_api_admin_secret_metadata( (string) $value, $secret_field[3] );
        }
    }

    foreach (
        [
            [ 'feature_alerts', [ 'features', 'alerts' ], 'alerts' ],
            [ 'feature_premium', [ 'features', 'premium' ], 'premium' ],
            [ 'feature_referrals', [ 'features', 'referrals' ], 'referrals' ],
            [ 'feature_ton_connect', [ 'features', 'ton_connect' ], 'ton_connect' ],
        ] as $feature_field
    ) {
        $value = tonbankcard_api_admin_mini_app_input_value( $input, $feature_field[0], $feature_field[1], $found );
        if ( $found ) {
            $base['features'][ $feature_field[2] ] = (bool) $value;
        }
    }

    foreach (
        [
            [ 'max_alerts_per_user', [ 'alerts', 'max_alerts_per_user' ], 'max_alerts_per_user', 1, 200 ],
            [ 'default_frequency_cap_seconds', [ 'alerts', 'default_frequency_cap_seconds' ], 'default_frequency_cap_seconds', 300, 86400 ],
            [ 'default_max_deliveries_per_day', [ 'alerts', 'default_max_deliveries_per_day' ], 'default_max_deliveries_per_day', 1, 100 ],
            [ 'evaluation_interval_seconds', [ 'alerts', 'evaluation_interval_seconds' ], 'evaluation_interval_seconds', 60, 3600 ],
        ] as $alert_field
    ) {
        $value = tonbankcard_api_admin_mini_app_input_value( $input, $alert_field[0], $alert_field[1], $found );
        if ( $found ) {
            $base['alerts'][ $alert_field[2] ] = tonbankcard_api_admin_bounded_int( $value, $base['alerts'][ $alert_field[2] ], $alert_field[3], $alert_field[4] );
        }
    }

    $value = tonbankcard_api_admin_mini_app_input_value( $input, 'premium_plan_code', [ 'premium', 'plan_code' ], $found );
    if ( $found ) {
        $plan_code = tonbankcard_api_admin_slug( $value, 'premium_monthly' );
        $base['premium']['plan_code'] = '' !== $plan_code ? $plan_code : 'premium_monthly';
    }

    $value = tonbankcard_api_admin_mini_app_input_value( $input, 'premium_monthly_stars', [ 'premium', 'monthly_stars' ], $found );
    if ( $found ) {
        $base['premium']['monthly_stars'] = tonbankcard_api_admin_bounded_int( $value, $base['premium']['monthly_stars'], 1, 10000 );
    }

    $value = tonbankcard_api_admin_mini_app_input_value( $input, 'premium_subscription_period', [ 'premium', 'subscription_period_seconds' ], $found );
    if ( ! $found ) {
        $value = tonbankcard_api_admin_mini_app_input_value( $input, 'premium_subscription_period_seconds', [ 'premium', 'subscription_period_seconds' ], $found );
    }
    if ( $found ) {
        $base['premium']['subscription_period_seconds'] = tonbankcard_api_admin_bounded_int( $value, $base['premium']['subscription_period_seconds'], 2592000, 2592000 );
    }

    return $base;
}

/**
 * Ensures Mini App state has every expected section.
 *
 * @param array $state
 * @return array
 */
function tonbankcard_api_admin_mini_app_shape( array $state ) {
    $state['runtime'] = isset( $state['runtime'] ) && is_array( $state['runtime'] ) ? $state['runtime'] : [];
    $state['telegram'] = isset( $state['telegram'] ) && is_array( $state['telegram'] ) ? $state['telegram'] : [];
    $state['telegram_setup'] = isset( $state['telegram_setup'] ) && is_array( $state['telegram_setup'] ) ? $state['telegram_setup'] : [];
    $state['features'] = isset( $state['features'] ) && is_array( $state['features'] ) ? $state['features'] : [];
    $state['alerts'] = isset( $state['alerts'] ) && is_array( $state['alerts'] ) ? $state['alerts'] : [];
    $state['premium'] = isset( $state['premium'] ) && is_array( $state['premium'] ) ? $state['premium'] : [];

    $state['runtime'] = array_merge(
        [
            'profile'           => 'local',
            'public_base_url'   => '',
            'telegram_base_url' => '',
        ],
        $state['runtime']
    );
    $state['telegram'] = array_merge(
        [
            'bot_username'   => '',
            'bot_token'      => tonbankcard_api_admin_secret_metadata_from_config( FALSE, 'env:TONBANKCARD_BOT_TOKEN' ),
            'webhook_secret' => tonbankcard_api_admin_secret_metadata_from_config( FALSE, 'env:TONBANKCARD_BOT_WEBHOOK_SECRET' ),
        ],
        $state['telegram']
    );
    $state['telegram_setup'] = array_merge(
        [
            'status'      => 'not_checked',
            'checked_at'  => null,
            'bot'         => null,
            'webhook'     => null,
            'commands'    => null,
            'menu_button' => null,
            'steps'       => [],
        ],
        $state['telegram_setup']
    );
    $state['features'] = array_merge(
        [
            'alerts'      => FALSE,
            'premium'     => FALSE,
            'referrals'   => FALSE,
            'ton_connect' => FALSE,
        ],
        $state['features']
    );
    $state['alerts'] = array_merge(
        [
            'worker_token'                  => tonbankcard_api_admin_secret_metadata_from_config( FALSE, 'env:TONBANKCARD_ALERT_WORKER_TOKEN' ),
            'max_alerts_per_user'           => 20,
            'default_frequency_cap_seconds' => 3600,
            'default_max_deliveries_per_day' => 8,
            'evaluation_interval_seconds'   => 300,
        ],
        $state['alerts']
    );
    $state['premium'] = array_merge(
        [
            'plan_code'                   => 'premium_monthly',
            'monthly_stars'               => 199,
            'subscription_period_seconds' => 2592000,
            'signing_secret'              => tonbankcard_api_admin_secret_metadata_from_config( FALSE, 'env:TONBANKCARD_PREMIUM_SIGNING_SECRET' ),
        ],
        $state['premium']
    );

    return $state;
}

/**
 * Adds computed Mini App launch helpers and readiness checks.
 *
 * @param array $state
 * @return array
 */
function tonbankcard_api_admin_enrich_mini_app_state( array $state ) {
    $state = tonbankcard_api_admin_mini_app_shape( $state );
    $state['runtime']['profile'] = tonbankcard_api_admin_runtime_profile( $state['runtime']['profile'] );
    $state['runtime']['public_base_url'] = tonbankcard_api_admin_absolute_url( $state['runtime']['public_base_url'] );
    $state['runtime']['telegram_base_url'] = tonbankcard_api_admin_absolute_url( $state['runtime']['telegram_base_url'] );
    $state['telegram']['bot_username'] = tonbankcard_api_admin_telegram_username( $state['telegram']['bot_username'] );

    $telegram_base_url = $state['runtime']['telegram_base_url'];
    $bot_username = $state['telegram']['bot_username'];
    $webhook_url = '' !== $telegram_base_url ? tonbankcard_api_admin_join_url( $telegram_base_url, 'api/telegram/bot' ) : '';
    $alert_worker_url = '' !== $telegram_base_url ? tonbankcard_api_admin_join_url( $telegram_base_url, 'api/alerts/evaluate' ) : '';

    $state['launch'] = [
        'mini_app_url'         => $telegram_base_url,
        'telegram_launch_url'  => '' !== $bot_username ? 'https://t.me/' . $bot_username . '?startapp' : '',
        'webhook_url'          => $webhook_url,
        'set_webhook_command'  => '' !== $webhook_url
            ? 'curl -X POST "https://api.telegram.org/bot${TONBANKCARD_BOT_TOKEN}/setWebhook" -d "url=' . $webhook_url . '" -d "secret_token=${TONBANKCARD_BOT_WEBHOOK_SECRET}" -d "allowed_updates[]=message" -d "allowed_updates[]=pre_checkout_query" -d "allowed_updates[]=inline_query"'
            : '',
        'alert_worker_command' => '' !== $alert_worker_url
            ? 'curl -X POST "' . $alert_worker_url . '" -H "X-TONBANKCARD-Alert-Worker-Token: ${TONBANKCARD_ALERT_WORKER_TOKEN}"'
            : '',
    ];

    $state['readiness'] = [
        tonbankcard_api_admin_readiness_item( 'profile', 'Telegram profile selected', 'telegram' === $state['runtime']['profile'], 'Use the telegram runtime profile for a Mini App-first deployment.' ),
        tonbankcard_api_admin_readiness_item( 'telegram_url', 'Telegram Mini App HTTPS URL', tonbankcard_api_admin_is_https_url( $telegram_base_url ), 'BotFather requires an HTTPS Mini App URL.' ),
        tonbankcard_api_admin_readiness_item( 'bot_username', 'Telegram bot username', '' !== $bot_username, 'Set the BotFather username without the @ prefix.' ),
        tonbankcard_api_admin_readiness_item( 'bot_token', 'Telegram bot token', ! empty( $state['telegram']['bot_token']['configured'] ), 'The backend needs a server-side bot token.' ),
        tonbankcard_api_admin_readiness_item( 'webhook_secret', 'Webhook secret', ! empty( $state['telegram']['webhook_secret']['configured'] ), 'Set a webhook secret before registering Telegram webhooks.' ),
        tonbankcard_api_admin_readiness_item( 'alert_worker', 'Alert worker token', empty( $state['features']['alerts'] ) || ! empty( $state['alerts']['worker_token']['configured'] ), 'Alerts require a worker token.' ),
        tonbankcard_api_admin_readiness_item( 'premium_signing', 'Premium signing secret', empty( $state['features']['premium'] ) || ! empty( $state['premium']['signing_secret']['configured'] ), 'Telegram Stars premium requires a signing secret.' ),
    ];

    return $state;
}

/**
 * Returns a readiness item for the Mini App setup checklist.
 *
 * @param string $key
 * @param string $label
 * @param bool $ok
 * @param string $message
 * @return array
 */
function tonbankcard_api_admin_readiness_item( string $key, string $label, bool $ok, string $message ) {
    return [
        'key'     => $key,
        'label'   => $label,
        'status'  => $ok ? 'ok' : 'missing',
        'message' => $ok ? 'Ready.' : $message,
    ];
}

/**
 * Returns a raw Mini App secret from request input or current environment.
 *
 * @param array $input
 * @param string $flat_key
 * @param array $path
 * @param string $env_key
 * @return string
 */
function tonbankcard_api_admin_mini_app_secret_value( array $input, string $flat_key, array $path, string $env_key ) {
    $found = FALSE;
    $value = tonbankcard_api_admin_mini_app_input_value( $input, $flat_key, $path, $found );
    if ( $found && is_scalar( $value ) && '' !== trim( (string) $value ) ) {
        return trim( (string) $value );
    }

    return trim( (string) tonbankcard_env( $env_key, '' ) );
}

/**
 * Returns TRUE when a Telegram bot token has the expected public shape.
 *
 * @param string $token
 * @return bool
 */
function tonbankcard_api_admin_valid_telegram_bot_token( string $token ) {
    return 1 === preg_match( '/^[0-9]{4,}:[A-Za-z0-9_-]{20,}$/', $token );
}

/**
 * Generates a Telegram-compatible webhook secret token.
 *
 * @return string
 */
function tonbankcard_api_admin_generate_telegram_webhook_secret() {
    try {
        $raw = random_bytes( 32 );
    } catch ( Exception $exception ) {
        $raw = hash( 'sha256', uniqid( 'tonbankcard_webhook_', TRUE ), TRUE );
    }

    return rtrim( strtr( base64_encode( $raw ), '+/', '-_' ), '=' );
}

/**
 * Returns TRUE when a secret can be sent as Telegram secret_token.
 *
 * @param string $secret
 * @return bool
 */
function tonbankcard_api_admin_valid_telegram_webhook_secret( string $secret ) {
    return 1 === preg_match( '/^[A-Za-z0-9_-]{1,256}$/', $secret );
}

/**
 * Returns update types needed by the bot companion.
 *
 * @return array
 */
function tonbankcard_api_admin_telegram_allowed_updates() {
    return [ 'message', 'pre_checkout_query', 'inline_query' ];
}

/**
 * Returns the bot command menu configured during automatic setup.
 *
 * @return array
 */
function tonbankcard_api_admin_telegram_bot_commands() {
    return [
        [ 'command' => 'start', 'description' => 'Open the Mini App' ],
        [ 'command' => 'market', 'description' => 'Open market overview' ],
        [ 'command' => 'watchlist', 'description' => 'Open watchlist' ],
        [ 'command' => 'alerts', 'description' => 'Open alerts' ],
        [ 'command' => 'settings', 'description' => 'Open settings' ],
        [ 'command' => 'support', 'description' => 'Open support' ],
    ];
}

/**
 * Returns safe bot identity metadata from Telegram getMe.
 *
 * @param array $bot
 * @return array
 */
function tonbankcard_api_admin_telegram_public_bot( array $bot ) {
    return [
        'id'                       => array_key_exists( 'id', $bot ) ? (string) $bot['id'] : '',
        'is_bot'                   => ! empty( $bot['is_bot'] ),
        'first_name'               => isset( $bot['first_name'] ) ? tonbankcard_api_admin_safe_text( $bot['first_name'], 64 ) : '',
        'username'                 => isset( $bot['username'] ) ? tonbankcard_api_admin_telegram_username( $bot['username'] ) : '',
        'can_join_groups'          => array_key_exists( 'can_join_groups', $bot ) ? (bool) $bot['can_join_groups'] : null,
        'supports_inline_queries'  => array_key_exists( 'supports_inline_queries', $bot ) ? (bool) $bot['supports_inline_queries'] : null,
        'has_main_web_app'         => array_key_exists( 'has_main_web_app', $bot ) ? (bool) $bot['has_main_web_app'] : null,
    ];
}

/**
 * Builds the public automatic Telegram setup result.
 *
 * @param array $bot
 * @param array $mini_app
 * @param array $allowed_updates
 * @param array $commands
 * @param array $menu_button
 * @param array $webhook_info
 * @return array
 */
function tonbankcard_api_admin_telegram_setup_payload( array $bot, array $mini_app, array $allowed_updates, array $commands, array $menu_button, array $webhook_info ) {
    $last_error = isset( $webhook_info['last_error_message'] )
        ? tonbankcard_api_admin_safe_text( tonbankcard_api_admin_redact_value( $webhook_info['last_error_message'] ), 240 )
        : null;

    return [
        'status'      => 'configured',
        'checked_at'  => gmdate( 'c' ),
        'bot'         => $bot,
        'webhook'     => [
            'registered'           => TRUE,
            'url'                  => isset( $webhook_info['url'] ) && '' !== trim( (string) $webhook_info['url'] ) ? (string) $webhook_info['url'] : $mini_app['launch']['webhook_url'],
            'allowed_updates'      => isset( $webhook_info['allowed_updates'] ) && is_array( $webhook_info['allowed_updates'] ) ? array_values( $webhook_info['allowed_updates'] ) : $allowed_updates,
            'pending_update_count' => isset( $webhook_info['pending_update_count'] ) ? max( 0, (int) $webhook_info['pending_update_count'] ) : null,
            'last_error_message'   => $last_error,
        ],
        'commands'    => [
            'registered' => TRUE,
            'count'      => count( $commands ),
        ],
        'menu_button' => [
            'registered' => TRUE,
            'type'       => isset( $menu_button['type'] ) ? (string) $menu_button['type'] : 'web_app',
            'text'       => isset( $menu_button['text'] ) ? (string) $menu_button['text'] : '',
            'url'        => isset( $menu_button['web_app']['url'] ) ? (string) $menu_button['web_app']['url'] : '',
        ],
        'steps'       => [
            tonbankcard_api_admin_telegram_setup_step( 'get_me', 'Bot token checked' ),
            tonbankcard_api_admin_telegram_setup_step( 'set_webhook', 'Webhook registered' ),
            tonbankcard_api_admin_telegram_setup_step( 'set_my_commands', 'Bot commands registered' ),
            tonbankcard_api_admin_telegram_setup_step( 'set_chat_menu_button', 'Mini App menu button registered' ),
            tonbankcard_api_admin_telegram_setup_step( 'get_webhook_info', 'Webhook registration verified' ),
        ],
    ];
}

/**
 * Returns a single automatic setup step.
 *
 * @param string $key
 * @param string $label
 * @return array
 */
function tonbankcard_api_admin_telegram_setup_step( string $key, string $label ) {
    return [
        'key'     => $key,
        'label'   => $label,
        'status'  => 'ok',
        'message' => 'Ready.',
    ];
}

/**
 * Calls the Telegram Bot API or a test transport override.
 *
 * @param string $method
 * @param string $bot_token
 * @param array $payload
 * @param array $config
 * @return array
 */
function tonbankcard_api_admin_telegram_api_call( string $method, string $bot_token, array $payload, array $config ) {
    $telegram = isset( $config['telegram_bot'] ) && is_array( $config['telegram_bot'] ) ? $config['telegram_bot'] : [];
    if ( isset( $telegram['transport'] ) && is_callable( $telegram['transport'] ) ) {
        try {
            return tonbankcard_api_admin_telegram_api_response( $method, call_user_func( $telegram['transport'], $method, $payload ) );
        } catch ( Exception $exception ) {
            return [
                'ok'          => FALSE,
                'method'      => $method,
                'status'      => 0,
                'description' => tonbankcard_api_admin_safe_text( $exception->getMessage(), 240 ),
            ];
        }
    }

    $base_url = isset( $telegram['api_base_url'] ) ? tonbankcard_api_admin_absolute_url( $telegram['api_base_url'] ) : 'https://api.telegram.org/';
    if ( '' === $base_url ) {
        $base_url = 'https://api.telegram.org/';
    }
    $request = [
        'url'             => rtrim( $base_url, '/' ) . '/bot' . $bot_token . '/' . rawurlencode( $method ),
        'payload'         => $payload,
        'timeout_seconds' => isset( $telegram['timeout_seconds'] ) ? max( 1, min( 15, (int) $telegram['timeout_seconds'] ) ) : 8,
    ];

    if ( function_exists( 'curl_init' ) ) {
        return tonbankcard_api_admin_telegram_api_response( $method, tonbankcard_api_admin_telegram_api_call_with_curl( $request ) );
    }

    return tonbankcard_api_admin_telegram_api_response( $method, tonbankcard_api_admin_telegram_api_call_with_stream( $request ) );
}

/**
 * Performs a Telegram Bot API POST with cURL.
 *
 * @param array $request
 * @return array
 */
function tonbankcard_api_admin_telegram_api_call_with_curl( array $request ) {
    $body = json_encode( $request['payload'], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE );
    if ( FALSE === $body ) {
        $body = '{}';
    }

    $handle = curl_init( $request['url'] );
    if ( FALSE === $handle ) {
        return [ 'status' => 0, 'body' => '', 'error' => 'telegram_transport_unavailable' ];
    }

    curl_setopt_array(
        $handle,
        [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST           => TRUE,
            CURLOPT_HTTPHEADER     => [ 'Content-Type: application/json' ],
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => (int) $request['timeout_seconds'],
            CURLOPT_CONNECTTIMEOUT => min( 5, (int) $request['timeout_seconds'] ),
        ]
    );
    $response_body = curl_exec( $handle );
    $status = (int) curl_getinfo( $handle, CURLINFO_HTTP_CODE );
    $error = curl_error( $handle );
    curl_close( $handle );

    return [
        'status' => $status,
        'body'   => FALSE === $response_body ? '' : $response_body,
        'error'  => $error,
    ];
}

/**
 * Performs a Telegram Bot API POST with PHP streams.
 *
 * @param array $request
 * @return array
 */
function tonbankcard_api_admin_telegram_api_call_with_stream( array $request ) {
    $body = json_encode( $request['payload'], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE );
    if ( FALSE === $body ) {
        $body = '{}';
    }

    $context = stream_context_create(
        [
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\n",
                'content'       => $body,
                'timeout'       => (int) $request['timeout_seconds'],
                'ignore_errors' => TRUE,
            ],
        ]
    );
    $response_body = @file_get_contents( $request['url'], FALSE, $context );
    $status = 0;
    if ( isset( $http_response_header ) && is_array( $http_response_header ) ) {
        foreach ( $http_response_header as $line ) {
            if ( preg_match( '#^HTTP/\S+\s+([0-9]{3})#', $line, $matches ) ) {
                $status = (int) $matches[1];
                break;
            }
        }
    }

    return [
        'status' => $status,
        'body'   => FALSE === $response_body ? '' : $response_body,
        'error'  => FALSE === $response_body ? 'telegram_transport_failed' : '',
    ];
}

/**
 * Normalizes Telegram Bot API transport results.
 *
 * @param string $method
 * @param mixed $response
 * @return array
 */
function tonbankcard_api_admin_telegram_api_response( string $method, $response ) {
    if ( is_array( $response ) && array_key_exists( 'ok', $response ) && ! array_key_exists( 'body', $response ) ) {
        return [
            'ok'          => ! empty( $response['ok'] ),
            'method'      => $method,
            'status'      => isset( $response['status'] ) ? (int) $response['status'] : ( ! empty( $response['ok'] ) ? 200 : 502 ),
            'result'      => isset( $response['result'] ) ? $response['result'] : null,
            'error_code'  => isset( $response['error_code'] ) ? (int) $response['error_code'] : null,
            'description' => isset( $response['description'] ) ? tonbankcard_api_admin_safe_text( tonbankcard_api_admin_redact_value( $response['description'] ), 240 ) : '',
        ];
    }

    $status = is_array( $response ) && isset( $response['status'] ) ? (int) $response['status'] : 0;
    $body = is_array( $response ) && isset( $response['body'] ) ? (string) $response['body'] : '';
    $decoded = '' !== $body ? json_decode( $body, TRUE ) : null;
    if ( is_array( $decoded ) ) {
        return [
            'ok'          => 200 <= $status && 300 > $status && ! empty( $decoded['ok'] ),
            'method'      => $method,
            'status'      => $status,
            'result'      => isset( $decoded['result'] ) ? $decoded['result'] : null,
            'error_code'  => isset( $decoded['error_code'] ) ? (int) $decoded['error_code'] : null,
            'description' => isset( $decoded['description'] ) ? tonbankcard_api_admin_safe_text( tonbankcard_api_admin_redact_value( $decoded['description'] ), 240 ) : '',
        ];
    }

    return [
        'ok'          => FALSE,
        'method'      => $method,
        'status'      => $status,
        'result'      => null,
        'error_code'  => null,
        'description' => is_array( $response ) && ! empty( $response['error'] ) ? tonbankcard_api_admin_safe_text( $response['error'], 240 ) : 'Telegram API response was not valid JSON.',
    ];
}

/**
 * Converts Telegram call failures to a safe admin API response.
 *
 * @param array $call
 * @param string $code
 * @param string $message
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_admin_telegram_call_error_response( array $call, string $code, string $message, string $request_id, array $headers ) {
    $details = [
        'method' => isset( $call['method'] ) ? (string) $call['method'] : 'telegram',
        'status' => isset( $call['status'] ) ? (int) $call['status'] : 0,
    ];
    if ( isset( $call['error_code'] ) && null !== $call['error_code'] ) {
        $details['telegram_error_code'] = (int) $call['error_code'];
    }
    if ( ! empty( $call['description'] ) ) {
        $details['description'] = tonbankcard_api_admin_safe_text( tonbankcard_api_admin_redact_value( $call['description'] ), 240 );
    }

    return tonbankcard_api_error_response( 502, $code, $message, $details, $request_id, $headers );
}

/**
 * Applies Mini App feature choices to the global admin feature flags.
 *
 * @param array $flags
 * @param array $mini_app
 * @return array
 */
function tonbankcard_api_admin_feature_flags_from_mini_app( array $flags, array $mini_app ) {
    $features = isset( $mini_app['features'] ) && is_array( $mini_app['features'] ) ? $mini_app['features'] : [];
    foreach ( [ 'alerts', 'premium', 'referrals', 'ton_connect' ] as $flag ) {
        if ( array_key_exists( $flag, $features ) ) {
            $flags[ $flag ] = (bool) $features[ $flag ];
        }
    }

    return tonbankcard_api_admin_normalize_feature_flags( $flags, $flags );
}

/**
 * Mirrors Mini App Telegram settings into provider metadata.
 *
 * @param array $providers
 * @param array $mini_app
 * @return array
 */
function tonbankcard_api_admin_providers_from_mini_app( array $providers, array $mini_app ) {
    if ( ! isset( $providers['telegram'] ) || ! is_array( $providers['telegram'] ) ) {
        $providers['telegram'] = [];
    }
    $telegram = isset( $mini_app['telegram'] ) && is_array( $mini_app['telegram'] ) ? $mini_app['telegram'] : [];
    if ( isset( $telegram['bot_username'] ) ) {
        $providers['telegram']['bot_username'] = tonbankcard_api_admin_telegram_username( $telegram['bot_username'] );
    }
    if ( isset( $telegram['bot_token'] ) && is_array( $telegram['bot_token'] ) ) {
        $providers['telegram']['bot_token'] = $telegram['bot_token'];
    }

    return $providers;
}

/**
 * Mirrors Mini App alert settings into operations metadata.
 *
 * @param array $operations
 * @param array $mini_app
 * @return array
 */
function tonbankcard_api_admin_operations_from_mini_app( array $operations, array $mini_app ) {
    if ( ! isset( $operations['alert_thresholds'] ) || ! is_array( $operations['alert_thresholds'] ) ) {
        $operations['alert_thresholds'] = [];
    }
    $alerts = isset( $mini_app['alerts'] ) && is_array( $mini_app['alerts'] ) ? $mini_app['alerts'] : [];
    foreach ( [ 'max_alerts_per_user', 'default_frequency_cap_seconds', 'default_max_deliveries_per_day', 'evaluation_interval_seconds' ] as $key ) {
        if ( array_key_exists( $key, $alerts ) ) {
            $operations['alert_thresholds'][ $key ] = (int) $alerts[ $key ];
        }
    }

    return $operations;
}

/**
 * Returns .env updates represented by a Mini App setup payload.
 *
 * @param array $mini_app
 * @param array $input
 * @return array
 */
function tonbankcard_api_admin_mini_app_env_updates( array $mini_app, array $input ) {
    $updates = [
        'TONBANKCARD_PROFILE'                             => $mini_app['runtime']['profile'],
        'TONBANKCARD_PUBLIC_BASE_URL'                     => $mini_app['runtime']['public_base_url'],
        'TONBANKCARD_TELEGRAM_BASE_URL'                   => $mini_app['runtime']['telegram_base_url'],
        'TONBANKCARD_BOT_USERNAME'                        => $mini_app['telegram']['bot_username'],
        'TONBANKCARD_FEATURE_ALERTS'                      => ! empty( $mini_app['features']['alerts'] ) ? 'true' : 'false',
        'TONBANKCARD_FEATURE_PREMIUM'                     => ! empty( $mini_app['features']['premium'] ) ? 'true' : 'false',
        'TONBANKCARD_FEATURE_REFERRALS'                   => ! empty( $mini_app['features']['referrals'] ) ? 'true' : 'false',
        'TONBANKCARD_FEATURE_TON_CONNECT'                 => ! empty( $mini_app['features']['ton_connect'] ) ? 'true' : 'false',
        'TONBANKCARD_ALERT_MAX_RULES_PER_USER'            => (string) $mini_app['alerts']['max_alerts_per_user'],
        'TONBANKCARD_ALERT_DEFAULT_FREQUENCY_CAP_SECONDS' => (string) $mini_app['alerts']['default_frequency_cap_seconds'],
        'TONBANKCARD_ALERT_MAX_DELIVERIES_PER_DAY'        => (string) $mini_app['alerts']['default_max_deliveries_per_day'],
        'TONBANKCARD_ALERT_EVALUATION_INTERVAL_SECONDS'   => (string) $mini_app['alerts']['evaluation_interval_seconds'],
        'TONBANKCARD_PREMIUM_PLAN_CODE'                   => $mini_app['premium']['plan_code'],
        'TONBANKCARD_PREMIUM_MONTHLY_STARS'               => (string) $mini_app['premium']['monthly_stars'],
        'TONBANKCARD_PREMIUM_SUBSCRIPTION_PERIOD_SECONDS' => (string) $mini_app['premium']['subscription_period_seconds'],
    ];

    foreach (
        [
            'TONBANKCARD_BOT_TOKEN'              => [ 'bot_token', [ 'telegram', 'bot_token' ] ],
            'TONBANKCARD_BOT_WEBHOOK_SECRET'     => [ 'webhook_secret', [ 'telegram', 'webhook_secret' ] ],
            'TONBANKCARD_ALERT_WORKER_TOKEN'     => [ 'alert_worker_token', [ 'alerts', 'worker_token' ] ],
            'TONBANKCARD_PREMIUM_SIGNING_SECRET' => [ 'premium_signing_secret', [ 'premium', 'signing_secret' ] ],
        ] as $env_key => $secret_path
    ) {
        $found = FALSE;
        $value = tonbankcard_api_admin_mini_app_input_value( $input, $secret_path[0], $secret_path[1], $found );
        if ( $found && is_scalar( $value ) && '' !== trim( (string) $value ) ) {
            $updates[ $env_key ] = trim( (string) $value );
        }
    }

    return $updates;
}

/**
 * Reads a Mini App input value from a flat key or nested path.
 *
 * @param array $input
 * @param string $flat_key
 * @param array $path
 * @param bool $found
 * @return mixed
 */
function tonbankcard_api_admin_mini_app_input_value( array $input, string $flat_key, array $path, bool &$found ) {
    if ( array_key_exists( $flat_key, $input ) ) {
        $found = TRUE;
        return $input[ $flat_key ];
    }

    $value = $input;
    foreach ( $path as $part ) {
        if ( ! is_array( $value ) || ! array_key_exists( $part, $value ) ) {
            $found = FALSE;
            return null;
        }
        $value = $value[ $part ];
    }

    $found = TRUE;
    return $value;
}

/**
 * Returns a supported runtime profile.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_admin_runtime_profile( $value ) {
    $profile = strtolower( trim( (string) $value ) );
    if ( in_array( $profile, [ 'local', 'staging', 'production', 'telegram' ], TRUE ) ) {
        return $profile;
    }

    return 'local';
}

/**
 * Returns a normalized absolute HTTP(S) URL or an empty string.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_admin_absolute_url( $value ) {
    $url = trim( (string) $value );
    if ( '' === $url ) {
        return '';
    }

    $parts = parse_url( $url );
    if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) || ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], TRUE ) ) {
        return '';
    }

    return rtrim( $url, '/' ) . '/';
}

/**
 * Returns TRUE when a URL uses HTTPS.
 *
 * @param string $url
 * @return bool
 */
function tonbankcard_api_admin_is_https_url( string $url ) {
    $parts = parse_url( $url );
    return is_array( $parts ) && ! empty( $parts['scheme'] ) && 'https' === strtolower( (string) $parts['scheme'] );
}

/**
 * Joins a base URL and route path.
 *
 * @param string $base_url
 * @param string $path
 * @return string
 */
function tonbankcard_api_admin_join_url( string $base_url, string $path ) {
    return rtrim( $base_url, '/' ) . '/' . ltrim( $path, '/' );
}

/**
 * Returns a safe Telegram username without the @ prefix.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_admin_telegram_username( $value ) {
    $username = trim( (string) $value );
    $username = ltrim( $username, '@' );
    $username = preg_replace( '/[^A-Za-z0-9_]+/', '', $username );
    if ( null === $username ) {
        return '';
    }

    return substr( $username, 0, 64 );
}

/**
 * Returns a bounded integer.
 *
 * @param mixed $value
 * @param int $default
 * @param int $min
 * @param int $max
 * @return int
 */
function tonbankcard_api_admin_bounded_int( $value, int $default, int $min, int $max ) {
    if ( ! is_numeric( $value ) ) {
        return $default;
    }

    return max( $min, min( $max, (int) $value ) );
}

/**
 * Returns a bounded numeric identifier.
 *
 * @param mixed $value
 * @param int $max_length
 * @return string
 */
function tonbankcard_api_admin_numeric_id( $value, int $max_length ) {
    $value = preg_replace( '/\D+/', '', (string) $value );
    if ( null === $value ) {
        return '';
    }
    return substr( $value, 0, max( 1, $max_length ) );
}

/**
 * Returns redacted secret metadata from configured environment state.
 *
 * @param bool $configured
 * @param string $source
 * @return array
 */
function tonbankcard_api_admin_secret_metadata_from_config( bool $configured, string $source ) {
    return [
        'configured'    => $configured,
        'source'        => $configured ? $source : null,
        'display_value' => $configured ? '[redacted]' : '',
        'updated_at'    => null,
    ];
}

/**
 * Builds redacted metadata for a submitted write-only secret.
 *
 * @param string $secret
 * @param string $name
 * @return array
 */
function tonbankcard_api_admin_secret_metadata( string $secret, string $name ) {
    return [
        'configured'    => TRUE,
        'source'        => 'admin_secret_entry:' . $name,
        'display_value' => '[redacted]',
        'fingerprint'   => substr( hash( 'sha256', $name . '|' . $secret ), 0, 16 ),
        'updated_at'    => gmdate( 'c' ),
    ];
}

/**
 * Records an audit entry and updates state timestamps.
 *
 * @param array $state
 * @param array $actor
 * @param string $action
 * @param string $subject_type
 * @param array $before
 * @param array $after
 * @param string $request_id
 * @return array
 */
function tonbankcard_api_admin_record_write( array $state, array $actor, string $action, string $subject_type, array $before, array $after, string $request_id ) {
    $entry = [
        'created_at'   => gmdate( 'c' ),
        'actor'        => tonbankcard_api_admin_public_actor( $actor ),
        'action'       => $action,
        'subject_type' => $subject_type,
        'request_id'   => $request_id,
        'before'       => tonbankcard_api_admin_redact_value( $before ),
        'after'        => tonbankcard_api_admin_redact_value( $after ),
    ];

    array_unshift( $state['audit_log'], $entry );
    $state['audit_log'] = array_slice( $state['audit_log'], 0, 100 );
    $state['updated_at'] = $entry['created_at'];

    return $state;
}

/**
 * Returns persisted audit entries.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_admin_audit_log( array $runtime, array $config ) {
    $state = tonbankcard_api_admin_load_state( $runtime, $config );
    return isset( $state['audit_log'] ) && is_array( $state['audit_log'] ) ? array_values( $state['audit_log'] ) : [];
}

/**
 * Saves admin state.
 *
 * @param array $state
 * @param array $runtime
 * @param array $config
 * @param array $env_updates
 * @return array
 */
function tonbankcard_api_admin_save_state( array $state, array $runtime, array $config, array $env_updates = [] ) {
    $settings = tonbankcard_api_admin_settings( $runtime, $config );
    $path = trim( $settings['store_path'] );
    if ( '' === $path ) {
        return [
            'ok'      => FALSE,
            'status'  => 503,
            'code'    => 'admin_store_unconfigured',
            'message' => 'Set TONBANKCARD_ADMIN_STORE before writing admin settings.',
        ];
    }

    $persisted = [
        'version'       => 1,
        'updated_at'    => isset( $state['updated_at'] ) ? $state['updated_at'] : gmdate( 'c' ),
        'feature_flags' => $state['feature_flags'],
        'mini_app'      => tonbankcard_api_admin_redact_value( isset( $state['mini_app'] ) ? $state['mini_app'] : [] ),
        'providers'     => tonbankcard_api_admin_redact_value( $state['providers'] ),
        'content'       => tonbankcard_api_admin_redact_value( $state['content'] ),
        'operations'    => tonbankcard_api_admin_redact_value( $state['operations'] ),
        'analytics'     => tonbankcard_api_admin_redact_value( isset( $state['analytics'] ) ? $state['analytics'] : [] ),
        'audit_log'     => tonbankcard_api_admin_redact_value( $state['audit_log'] ),
    ];

    $env_saved = tonbankcard_api_admin_save_env_updates( $env_updates );
    if ( empty( $env_saved['ok'] ) ) {
        return $env_saved;
    }

    $dir = dirname( $path );
    if ( ! is_dir( $dir ) && ! mkdir( $dir, 0775, TRUE ) && ! is_dir( $dir ) ) {
        return [
            'ok'      => FALSE,
            'status'  => 500,
            'code'    => 'admin_store_directory_failed',
            'message' => 'Admin store directory could not be created.',
        ];
    }

    $json = json_encode( $persisted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE );
    if ( FALSE === $json ) {
        return [
            'ok'      => FALSE,
            'status'  => 500,
            'code'    => 'admin_store_encode_failed',
            'message' => 'Admin store payload could not be encoded.',
        ];
    }

    $tmp = $path . '.tmp.' . getmypid();
    if ( FALSE === file_put_contents( $tmp, $json . "\n", LOCK_EX ) || ! rename( $tmp, $path ) ) {
        if ( is_file( $tmp ) ) {
            @unlink( $tmp );
        }
        return [
            'ok'      => FALSE,
            'status'  => 500,
            'code'    => 'admin_store_write_failed',
            'message' => 'Admin store could not be written.',
        ];
    }
    @chmod( $path, 0600 );

    return [ 'ok' => TRUE ];
}

/**
 * Persists admin-editable runtime settings into .env.
 *
 * @param array $updates
 * @return array
 */
function tonbankcard_api_admin_save_env_updates( array $updates ) {
    if ( empty( $updates ) ) {
        return [ 'ok' => TRUE ];
    }

    $allowed = tonbankcard_api_admin_env_keys();
    $filtered = [];
    foreach ( $updates as $key => $value ) {
        if ( in_array( $key, $allowed, TRUE ) ) {
            $filtered[ $key ] = (string) $value;
        }
    }
    if ( empty( $filtered ) ) {
        return [ 'ok' => TRUE ];
    }

    $path = GECKO_CLIENT_DIR . '/.env';
    $lines = is_file( $path ) ? file( $path, FILE_IGNORE_NEW_LINES ) : [];
    if ( ! is_array( $lines ) ) {
        $lines = [];
    }

    $seen = [];
    foreach ( $lines as $index => $line ) {
        if ( preg_match( '/^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=/', $line, $matches ) ) {
            $key = $matches[1];
            if ( array_key_exists( $key, $filtered ) ) {
                $lines[ $index ] = $key . '=' . tonbankcard_api_admin_format_env_value( $filtered[ $key ] );
                $seen[ $key ] = TRUE;
            }
        }
    }

    if ( empty( $lines ) ) {
        $lines[] = '# Updated by the TONBANKCARD admin panel.';
    }
    foreach ( $filtered as $key => $value ) {
        if ( empty( $seen[ $key ] ) ) {
            $lines[] = $key . '=' . tonbankcard_api_admin_format_env_value( $value );
        }
    }

    $dir = dirname( $path );
    if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
        return [
            'ok'      => FALSE,
            'status'  => 500,
            'code'    => 'admin_env_write_failed',
            'message' => 'The .env file could not be written from the admin panel.',
        ];
    }

    $env = implode( "\n", $lines ) . "\n";
    $tmp = $path . '.tmp.' . getmypid();
    if ( FALSE === file_put_contents( $tmp, $env, LOCK_EX ) || ! rename( $tmp, $path ) ) {
        if ( is_file( $tmp ) ) {
            @unlink( $tmp );
        }
        return [
            'ok'      => FALSE,
            'status'  => 500,
            'code'    => 'admin_env_write_failed',
            'message' => 'The .env file could not be written from the admin panel.',
        ];
    }
    @chmod( $path, 0600 );

    foreach ( $filtered as $key => $value ) {
        putenv( $key . '=' . $value );
        $_ENV[ $key ] = $value;
        $_SERVER[ $key ] = $value;
    }

    return [ 'ok' => TRUE ];
}

/**
 * Returns environment keys that may be written through the admin panel.
 *
 * @return array
 */
function tonbankcard_api_admin_env_keys() {
    return [
        'TONBANKCARD_PROFILE',
        'TONBANKCARD_PUBLIC_BASE_URL',
        'TONBANKCARD_TELEGRAM_BASE_URL',
        'TONBANKCARD_FEATURE_AI',
        'TONBANKCARD_FEATURE_ALERTS',
        'TONBANKCARD_FEATURE_WIDGET',
        'TONBANKCARD_FEATURE_CHANGENOW',
        'TONBANKCARD_FEATURE_TON_CONNECT',
        'TONBANKCARD_FEATURE_REFERRALS',
        'TONBANKCARD_FEATURE_GAMIFICATION',
        'TONBANKCARD_FEATURE_PREMIUM',
        'TONBANKCARD_ALERT_WORKER_TOKEN',
        'TONBANKCARD_ALERT_MAX_RULES_PER_USER',
        'TONBANKCARD_ALERT_DEFAULT_FREQUENCY_CAP_SECONDS',
        'TONBANKCARD_ALERT_MAX_DELIVERIES_PER_DAY',
        'TONBANKCARD_ALERT_EVALUATION_INTERVAL_SECONDS',
        'TONBANKCARD_PREMIUM_PLAN_CODE',
        'TONBANKCARD_PREMIUM_MONTHLY_STARS',
        'TONBANKCARD_PREMIUM_SUBSCRIPTION_PERIOD_SECONDS',
        'TONBANKCARD_PREMIUM_SIGNING_SECRET',
        'COINGECKO_API_PLAN',
        'COINGECKO_API_KEY',
        'GROQ_API_KEY',
        'GROQ_MODEL_ID',
        'UPSTASH_REDIS_REST_TOKEN',
        'TONAPI_API_KEY',
        'TONBANKCARD_BOT_USERNAME',
        'TONBANKCARD_BOT_TOKEN',
        'TONBANKCARD_BOT_WEBHOOK_SECRET',
        'CHANGENOW_LINK_ID',
        'YANDEX_METRICA_COUNTER_ID',
        'YANDEX_METRICA_ENABLED',
    ];
}

/**
 * Formats a dotenv value.
 *
 * @param string $value
 * @return string
 */
function tonbankcard_api_admin_format_env_value( string $value ) {
    if ( '' === $value ) {
        return '';
    }
    if ( preg_match( '/^[A-Za-z0-9._:\/@%+,;=?-]+$/', $value ) && FALSE === strpos( $value, '#' ) ) {
        return $value;
    }

    return '"' . str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], $value ) . '"';
}

/**
 * Converts store write failures to JSON errors.
 *
 * @param array $saved
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_admin_store_error( array $saved, string $request_id, array $headers ) {
    return tonbankcard_api_error_response(
        isset( $saved['status'] ) ? (int) $saved['status'] : 500,
        isset( $saved['code'] ) ? (string) $saved['code'] : 'admin_store_failed',
        isset( $saved['message'] ) ? (string) $saved['message'] : 'Admin settings could not be persisted.',
        [],
        $request_id,
        $headers
    );
}

/**
 * Recursively redacts raw secret-shaped fields.
 *
 * @param mixed $value
 * @param string $key
 * @return mixed
 */
function tonbankcard_api_admin_redact_value( $value, string $key = '' ) {
    if ( is_array( $value ) ) {
        $redacted = [];
        foreach ( $value as $child_key => $child_value ) {
            $redacted[ $child_key ] = tonbankcard_api_admin_redact_value( $child_value, (string) $child_key );
        }
        return $redacted;
    }

    if ( preg_match( '/(token|secret|password|api_key|rest_token|bot_token|authorization)/i', $key ) ) {
        return is_bool( $value ) ? $value : '[redacted]';
    }

    if ( is_string( $value ) ) {
        return preg_replace( '/(token|secret|password|api[_-]?key)=([^&\s]+)/i', '$1=[redacted]', $value );
    }

    return $value;
}

/**
 * Returns safe text.
 *
 * @param mixed $value
 * @param int $max
 * @return string
 */
function tonbankcard_api_admin_safe_text( $value, int $max ) {
    $value = trim( preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]+/', ' ', (string) $value ) );
    return substr( $value, 0, $max );
}

/**
 * Returns a safe slug.
 *
 * @param mixed $value
 * @param string $default
 * @return string
 */
function tonbankcard_api_admin_slug( $value, string $default ) {
    $slug = strtolower( trim( (string) $value ) );
    $slug = preg_replace( '/[^a-z0-9._:-]+/', '_', $slug );
    $slug = trim( (string) $slug, '._:-' );
    return '' === $slug ? $default : substr( $slug, 0, 96 );
}
