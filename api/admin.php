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
        'providers'     => $state['providers'],
        'content'       => $state['content'],
        'operations'    => $state['operations'],
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
    $state['content'] = tonbankcard_api_admin_merge_content(
        $state['content'],
        isset( $store['content'] ) && is_array( $store['content'] ) ? $store['content'] : []
    );
    $state['operations'] = tonbankcard_api_admin_merge_operations(
        $state['operations'],
        isset( $store['operations'] ) && is_array( $store['operations'] ) ? $store['operations'] : []
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
            'telegram'  => [
                'bot_username' => isset( $runtime['telegram']['bot_username'] ) ? (string) $runtime['telegram']['bot_username'] : '',
                'bot_token'    => tonbankcard_api_admin_secret_metadata_from_config( ! empty( $runtime['telegram']['bot_token_configured'] ), 'env:TONBANKCARD_BOT_TOKEN' ),
            ],
            'changenow' => [
                'link_id' => isset( $providers['changenow']['link_id'] ) ? (string) $providers['changenow']['link_id'] : '',
            ],
        ],
        'content'          => [
            'legal_copy' => [
                'market_data_disclaimer' => 'Market data is informational only and may be delayed or unavailable.',
                'ai_disclaimer'          => 'AI summaries are informational and not financial advice.',
                'alerts_disclaimer'      => 'Alerts are best-effort notifications and can be delayed.',
                'widget_disclaimer'      => 'Exchange widgets are provided by third parties under their own terms.',
            ],
            'ton_assets' => [],
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
        'audit_log'        => [],
    ];
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
    $state = tonbankcard_api_admin_record_write( $state, $actor, 'providers.updated', 'providers', $before, $state['providers'], $request_id );

    $env_updates = tonbankcard_api_admin_provider_env_updates( $input );
    $saved = tonbankcard_api_admin_save_state( $state, $runtime, $config, $env_updates );
    if ( empty( $saved['ok'] ) ) {
        return tonbankcard_api_admin_store_error( $saved, $request_id, $headers );
    }

    return tonbankcard_api_success_response(
        [
            'actor'     => tonbankcard_api_admin_public_actor( $actor ),
            'providers' => $state['providers'],
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
    $state = tonbankcard_api_admin_record_write( $state, $actor, 'operations.updated', 'operations', $before, $state['operations'], $request_id );

    $saved = tonbankcard_api_admin_save_state( $state, $runtime, $config );
    if ( empty( $saved['ok'] ) ) {
        return tonbankcard_api_admin_store_error( $saved, $request_id, $headers );
    }

    return tonbankcard_api_success_response(
        [
            'actor'      => tonbankcard_api_admin_public_actor( $actor ),
            'operations' => $state['operations'],
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
        'providers'     => tonbankcard_api_admin_redact_value( $state['providers'] ),
        'content'       => tonbankcard_api_admin_redact_value( $state['content'] ),
        'operations'    => tonbankcard_api_admin_redact_value( $state['operations'] ),
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
        'TONBANKCARD_FEATURE_AI',
        'TONBANKCARD_FEATURE_ALERTS',
        'TONBANKCARD_FEATURE_WIDGET',
        'TONBANKCARD_FEATURE_CHANGENOW',
        'TONBANKCARD_FEATURE_TON_CONNECT',
        'TONBANKCARD_FEATURE_REFERRALS',
        'TONBANKCARD_FEATURE_GAMIFICATION',
        'TONBANKCARD_FEATURE_PREMIUM',
        'COINGECKO_API_PLAN',
        'COINGECKO_API_KEY',
        'GROQ_API_KEY',
        'GROQ_MODEL_ID',
        'UPSTASH_REDIS_REST_TOKEN',
        'TONBANKCARD_BOT_USERNAME',
        'TONBANKCARD_BOT_TOKEN',
        'CHANGENOW_LINK_ID',
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
