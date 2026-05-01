<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 SMART ALERTS API
 * -------------------------------------------------------------------------
 * Trusted-session alert rule CRUD, retry-safe evaluation, and Telegram bot
 * delivery payloads with Mini App deep links.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Returns TRUE when the path belongs to the alerts API.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_alerts_is_request( string $path ) {
    $path = tonbankcard_api_normalize_path( $path );
    return '/api/alerts' === $path || 0 === strpos( $path, '/api/alerts/' );
}

/**
 * Returns documented alerts routes.
 *
 * @return array
 */
function tonbankcard_api_alerts_routes() {
    return [
        '/api/alerts',
        '/api/alerts/{id}',
        '/api/alerts/{id}/test',
        '/api/alerts/evaluate',
    ];
}

/**
 * Handles smart alert API requests.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_alerts_handle( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $path = tonbankcard_api_normalize_path( $request['path'] );
    $method = strtoupper( $request['method'] );
    $settings = tonbankcard_api_alerts_settings( $runtime, $config );

    if ( empty( $settings['enabled'] ) ) {
        return tonbankcard_api_error_response(
            503,
            'alerts_disabled',
            'Smart alerts are disabled for this deployment.',
            [ 'feature' => 'alerts' ],
            $request_id,
            $headers
        );
    }

    if ( '/api/alerts/evaluate' === $path ) {
        if ( 'POST' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_alerts_evaluate_response( $request, $runtime, $config, $settings, $request_id, $headers );
    }

    if ( '/api/alerts' === $path && ! in_array( $method, [ 'GET', 'POST' ], TRUE ) ) {
        return tonbankcard_api_method_not_allowed_response( [ 'GET', 'POST', 'OPTIONS' ], $request_id, $headers );
    }

    if ( '/api/alerts' !== $path && ! in_array( $method, [ 'GET', 'PUT', 'DELETE', 'POST' ], TRUE ) ) {
        return tonbankcard_api_method_not_allowed_response( [ 'GET', 'PUT', 'DELETE', 'POST', 'OPTIONS' ], $request_id, $headers );
    }

    $session = tonbankcard_api_alerts_trusted_session( $request, $runtime );
    if ( empty( $session['ok'] ) ) {
        if ( isset( $session['reason'] ) && 'storage_unavailable' === $session['reason'] ) {
            return tonbankcard_api_error_response(
                503,
                'alerts_storage_unavailable',
                'Trusted alert sync requires the configured MySQL session store.',
                [ 'storage' => 'mysql' ],
                $request_id,
                $headers
            );
        }

        return tonbankcard_api_error_response(
            401,
            'alerts_session_required',
            'Smart alert management requires a validated Telegram session.',
            [ 'session' => 'telegram_validated' ],
            $request_id,
            $headers
        );
    }

    $pdo = $session['pdo'];
    $user_id = (int) $session['user_id'];
    $premium_state = function_exists( 'tonbankcard_api_premium_limits_for_user' )
        ? tonbankcard_api_premium_limits_for_user( $pdo, $user_id, $runtime, $config )
        : null;
    if ( null !== $premium_state ) {
        $settings = tonbankcard_api_alerts_apply_premium_limits( $settings, $premium_state );
    }

    if ( '/api/alerts' === $path ) {
        if ( 'GET' === $method ) {
            return tonbankcard_api_success_response(
                tonbankcard_api_alerts_list_payload( $pdo, $user_id, $settings ),
                $request_id,
                $headers
            );
        }

        $active_count = tonbankcard_api_alerts_user_rule_count( $pdo, $user_id );
        if ( $active_count >= $settings['max_alerts_per_user'] ) {
            return tonbankcard_api_error_response(
                409,
                'alerts_limit_reached',
                'This Telegram user has reached the configured alert rule limit.',
                null !== $premium_state && function_exists( 'tonbankcard_api_premium_limit_error_details' )
                    ? tonbankcard_api_premium_limit_error_details( $premium_state, 'alerts_per_user', $active_count + 1 )
                    : [ 'limit' => $settings['max_alerts_per_user'] ],
                $request_id,
                $headers
            );
        }

        $normalized = tonbankcard_api_alerts_normalize_rule_payload( tonbankcard_api_json_body( $request ), $settings );
        if ( empty( $normalized['ok'] ) ) {
            return tonbankcard_api_alerts_validation_error( $normalized, $request_id, $headers );
        }

        $created = tonbankcard_api_alerts_insert_rule( $pdo, $user_id, $normalized['rule'], $settings );
        tonbankcard_observability_log(
            $runtime,
            $config,
            'info',
            'alerts.rule_created',
            [
                'alert_rule_id' => $created['id'],
                'user_id'       => $user_id,
                'trigger_type'  => $created['trigger_type'],
            ]
        );

        return tonbankcard_api_success_response(
            [
                'rule'     => tonbankcard_api_alerts_public_rule( $created, $settings ),
                'settings' => tonbankcard_api_alerts_public_settings( $settings ),
            ],
            $request_id,
            $headers,
            201
        );
    }

    $route = tonbankcard_api_alerts_rule_route( $path );
    if ( null === $route ) {
        return tonbankcard_api_error_response(
            404,
            'not_found',
            'No alert route matches the request.',
            [ 'path' => $path ],
            $request_id,
            $headers
        );
    }

    $rule = tonbankcard_api_alerts_select_rule( $pdo, $user_id, $route['id'], TRUE );
    if ( null === $rule ) {
        return tonbankcard_api_error_response(
            404,
            'alert_not_found',
            'The alert rule does not exist for this user.',
            [ 'id' => $route['id'] ],
            $request_id,
            $headers
        );
    }

    if ( $route['test'] ) {
        if ( 'POST' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers );
        }

        $event = tonbankcard_api_alerts_test_event( $rule );
        $delivery = tonbankcard_api_alerts_delivery_payload( $rule, $event, $settings, TRUE );
        tonbankcard_api_alerts_record_delivery( $pdo, $rule, $delivery, 'queued', $request_id, null );

        tonbankcard_observability_log(
            $runtime,
            $config,
            'info',
            'alerts.test_delivery_queued',
            [
                'alert_rule_id' => (int) $rule['id'],
                'user_id'       => $user_id,
                'deep_link'     => $delivery['links']['mini_app_path'],
            ]
        );

        return tonbankcard_api_success_response(
            [
                'accepted' => TRUE,
                'delivery' => $delivery,
            ],
            $request_id,
            $headers,
            202
        );
    }

    if ( 'GET' === $method ) {
        return tonbankcard_api_success_response(
            [
                'rule'     => tonbankcard_api_alerts_public_rule( $rule, $settings ),
                'settings' => tonbankcard_api_alerts_public_settings( $settings ),
            ],
            $request_id,
            $headers
        );
    }

    if ( 'PUT' === $method ) {
        $normalized = tonbankcard_api_alerts_normalize_rule_payload( tonbankcard_api_json_body( $request ), $settings, $rule );
        if ( empty( $normalized['ok'] ) ) {
            return tonbankcard_api_alerts_validation_error( $normalized, $request_id, $headers );
        }

        $updated = tonbankcard_api_alerts_update_rule( $pdo, $user_id, $route['id'], $normalized['rule'], $settings );
        return tonbankcard_api_success_response(
            [
                'rule'     => tonbankcard_api_alerts_public_rule( $updated, $settings ),
                'settings' => tonbankcard_api_alerts_public_settings( $settings ),
            ],
            $request_id,
            $headers
        );
    }

    if ( 'DELETE' === $method ) {
        tonbankcard_api_alerts_delete_rule( $pdo, $user_id, $route['id'] );
        return tonbankcard_api_success_response(
            [
                'deleted' => TRUE,
                'id'      => $route['id'],
            ],
            $request_id,
            $headers
        );
    }

    return tonbankcard_api_method_not_allowed_response( [ 'GET', 'PUT', 'DELETE', 'OPTIONS' ], $request_id, $headers );
}

/**
 * Returns normalized alert settings.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_alerts_settings( array $runtime, array $config ) {
    $settings = isset( $config['alerts'] ) && is_array( $config['alerts'] ) ? $config['alerts'] : [];
    $telegram = isset( $runtime['telegram'] ) && is_array( $runtime['telegram'] ) ? $runtime['telegram'] : [];
    $urls = isset( $runtime['urls'] ) && is_array( $runtime['urls'] ) ? $runtime['urls'] : [];
    $features = isset( $runtime['feature_flags'] ) && is_array( $runtime['feature_flags'] )
        ? $runtime['feature_flags']
        : ( isset( $runtime['features'] ) && is_array( $runtime['features'] ) ? $runtime['features'] : [] );

    return [
        'enabled'                       => isset( $features['alerts'] ) ? (bool) $features['alerts'] : TRUE,
        'max_alerts_per_user'           => isset( $settings['max_alerts_per_user'] ) ? max( 1, min( 200, (int) $settings['max_alerts_per_user'] ) ) : 20,
        'default_frequency_cap_seconds' => isset( $settings['default_frequency_cap_seconds'] ) ? max( 300, min( 86400, (int) $settings['default_frequency_cap_seconds'] ) ) : 3600,
        'min_frequency_cap_seconds'     => isset( $settings['min_frequency_cap_seconds'] ) ? max( 60, (int) $settings['min_frequency_cap_seconds'] ) : 300,
        'max_frequency_cap_seconds'     => isset( $settings['max_frequency_cap_seconds'] ) ? max( 300, (int) $settings['max_frequency_cap_seconds'] ) : 86400,
        'default_max_deliveries_per_day'=> isset( $settings['default_max_deliveries_per_day'] ) ? max( 1, min( 100, (int) $settings['default_max_deliveries_per_day'] ) ) : 8,
        'evaluation_interval_seconds'   => isset( $settings['evaluation_interval_seconds'] ) ? max( 60, min( 3600, (int) $settings['evaluation_interval_seconds'] ) ) : 300,
        'worker_token'                  => isset( $settings['worker_token'] ) ? trim( (string) $settings['worker_token'] ) : '',
        'bot_username'                  => isset( $telegram['bot_username'] ) ? trim( (string) $telegram['bot_username'] ) : ( isset( $runtime['bot_username'] ) ? trim( (string) $runtime['bot_username'] ) : '' ),
        'bot_token'                     => isset( $telegram['bot_token'] ) ? trim( (string) $telegram['bot_token'] ) : '',
        'telegram_base_url'             => isset( $urls['telegram'] ) ? rtrim( (string) $urls['telegram'], '/' ) : '',
        'public_base_url'               => isset( $urls['public'] ) && '' !== trim( (string) $urls['public'] ) ? rtrim( (string) $urls['public'], '/' ) : ( isset( $urls['active'] ) ? rtrim( (string) $urls['active'], '/' ) : '' ),
    ];
}

/**
 * Returns settings that are safe for browser clients.
 *
 * @param array $settings
 * @return array
 */
function tonbankcard_api_alerts_public_settings( array $settings ) {
    $payload = [
        'enabled'                         => (bool) $settings['enabled'],
        'max_alerts_per_user'             => (int) $settings['max_alerts_per_user'],
        'default_frequency_cap_seconds'   => (int) $settings['default_frequency_cap_seconds'],
        'default_max_deliveries_per_day'  => (int) $settings['default_max_deliveries_per_day'],
        'evaluation_interval_seconds'     => (int) $settings['evaluation_interval_seconds'],
        'telegram_bot_configured'         => '' !== $settings['bot_username'],
    ];

    if ( isset( $settings['premium_state'] ) && is_array( $settings['premium_state'] ) && function_exists( 'tonbankcard_api_premium_public_state' ) ) {
        $payload['premium'] = tonbankcard_api_premium_public_state( $settings['premium_state'] );
    }

    return $payload;
}

/**
 * Applies premium entitlement limits to alert settings.
 *
 * @param array $settings
 * @param array $premium_state
 * @return array
 */
function tonbankcard_api_alerts_apply_premium_limits( array $settings, array $premium_state ) {
    if ( function_exists( 'tonbankcard_api_premium_limit_value' ) ) {
        $settings['max_alerts_per_user'] = tonbankcard_api_premium_limit_value( $premium_state, 'alerts_per_user', $settings['max_alerts_per_user'] );
    }
    $settings['premium_state'] = $premium_state;

    return $settings;
}

/**
 * Loads a trusted Telegram session and its database connection.
 *
 * @param array $request
 * @param array $runtime
 * @return array
 */
function tonbankcard_api_alerts_trusted_session( array $request, array $runtime ) {
    $token = tonbankcard_api_alerts_session_token( $request );
    if ( null === $token ) {
        return [
            'ok'     => FALSE,
            'reason' => 'missing_session',
        ];
    }

    $database_error = null;
    $pdo = tonbankcard_api_session_database_connection( $runtime, $database_error );
    if ( null === $pdo ) {
        return [
            'ok'     => FALSE,
            'reason' => 'storage_unavailable',
        ];
    }

    $select = $pdo->prepare(
        "SELECT id, user_id
         FROM user_sessions
         WHERE session_token_hash = :session_token_hash
           AND trust_state = 'telegram_validated'
           AND user_id IS NOT NULL
           AND revoked_at IS NULL
           AND expires_at > UTC_TIMESTAMP(6)
         LIMIT 1"
    );
    $select->execute( [ ':session_token_hash' => hash( 'sha256', $token ) ] );
    $row = $select->fetch();

    if ( ! is_array( $row ) || empty( $row['user_id'] ) ) {
        return [
            'ok'     => FALSE,
            'reason' => 'untrusted_session',
        ];
    }

    $update = $pdo->prepare( 'UPDATE user_sessions SET last_seen_at = UTC_TIMESTAMP(6) WHERE id = :id' );
    $update->execute( [ ':id' => $row['id'] ] );

    return [
        'ok'      => TRUE,
        'pdo'     => $pdo,
        'user_id' => (int) $row['user_id'],
    ];
}

/**
 * Reads a session token from the cookie or Authorization bearer header.
 *
 * @param array $request
 * @return string|null
 */
function tonbankcard_api_alerts_session_token( array $request ) {
    $token = tonbankcard_api_session_token_from_request( $request );
    if ( null !== $token ) {
        return $token;
    }

    $authorization = isset( $request['headers']['authorization'] ) ? trim( (string) $request['headers']['authorization'] ) : '';
    if ( preg_match( '/^Bearer\s+([A-Fa-f0-9]{64})$/', $authorization, $matches ) ) {
        return strtolower( $matches[1] );
    }

    if ( isset( $request['headers']['x-tonbankcard-session'] ) && preg_match( '/^[A-Fa-f0-9]{64}$/', trim( (string) $request['headers']['x-tonbankcard-session'] ) ) ) {
        return strtolower( trim( (string) $request['headers']['x-tonbankcard-session'] ) );
    }

    return null;
}

/**
 * Normalizes and validates an alert rule payload.
 *
 * @param array $payload
 * @param array $settings
 * @param array $existing
 * @return array
 */
function tonbankcard_api_alerts_normalize_rule_payload( array $payload, array $settings, array $existing = [] ) {
    $errors = [];
    $allowed_types = [ 'price_cross', 'percent_move', 'volume_spike', 'market_cap_cross', 'rank_change', 'sentiment_change', 'ton_ecosystem' ];
    $allowed_operators = [ 'gt', 'gte', 'lt', 'lte' ];

    $coin_id = tonbankcard_api_alerts_payload_string( $payload, 'coin_id', isset( $existing['coin_id'] ) ? $existing['coin_id'] : '' );
    $coin_id = strtolower( trim( $coin_id ) );
    if ( ! preg_match( '/^[a-z0-9._-]{1,96}$/', $coin_id ) ) {
        $errors['coin_id'] = 'Enter a valid CoinGecko coin id.';
    }

    $symbol = strtoupper( tonbankcard_api_alerts_payload_string( $payload, 'symbol', isset( $existing['symbol'] ) ? $existing['symbol'] : '' ) );
    $symbol = preg_replace( '/[^A-Z0-9._-]/', '', $symbol );
    $symbol = is_string( $symbol ) ? substr( $symbol, 0, 32 ) : '';

    $display_name = tonbankcard_api_alerts_payload_string( $payload, 'display_name', isset( $existing['display_name'] ) ? $existing['display_name'] : '' );
    $display_name = substr( trim( preg_replace( '/\s+/', ' ', $display_name ) ), 0, 160 );

    $trigger_type = tonbankcard_api_alerts_payload_string( $payload, 'trigger_type', isset( $existing['trigger_type'] ) ? $existing['trigger_type'] : 'price_cross' );
    if ( ! in_array( $trigger_type, $allowed_types, TRUE ) ) {
        $errors['trigger_type'] = 'Choose a supported alert trigger type.';
    }

    $operator = tonbankcard_api_alerts_payload_string( $payload, 'operator', tonbankcard_api_alerts_payload_string( $payload, 'comparison_operator', isset( $existing['comparison_operator'] ) ? $existing['comparison_operator'] : 'gte' ) );
    $operator = strtolower( trim( $operator ) );
    if ( ! in_array( $operator, $allowed_operators, TRUE ) ) {
        $errors['operator'] = 'Choose gt, gte, lt, or lte.';
    }

    $threshold_present = array_key_exists( 'threshold', $payload ) || array_key_exists( 'threshold_value', $payload ) || isset( $existing['threshold_value'] );
    $threshold_value = array_key_exists( 'threshold', $payload ) ? $payload['threshold'] : ( array_key_exists( 'threshold_value', $payload ) ? $payload['threshold_value'] : ( isset( $existing['threshold_value'] ) ? $existing['threshold_value'] : null ) );
    if ( ! $threshold_present && 'ton_ecosystem' === $trigger_type ) {
        $threshold_value = 0;
        $threshold_present = TRUE;
    }
    if ( ! $threshold_present || ! is_numeric( $threshold_value ) ) {
        $errors['threshold'] = 'Enter a numeric alert threshold.';
        $threshold_value = 0;
    }
    $threshold_value = (float) $threshold_value;

    $status = strtolower( tonbankcard_api_alerts_payload_string( $payload, 'status', isset( $existing['status'] ) ? $existing['status'] : 'active' ) );
    if ( ! in_array( $status, [ 'active', 'paused' ], TRUE ) ) {
        $errors['status'] = 'Choose active or paused.';
    }

    $delivery_channel = strtolower( tonbankcard_api_alerts_payload_string( $payload, 'delivery_channel', isset( $existing['delivery_channel'] ) ? $existing['delivery_channel'] : 'telegram_bot' ) );
    if ( 'telegram_bot' !== $delivery_channel ) {
        $errors['delivery_channel'] = 'Telegram bot delivery is the supported alert channel.';
    }

    $quiet_start = tonbankcard_api_alerts_normalize_time( tonbankcard_api_alerts_payload_nullable_string( $payload, 'quiet_start', isset( $existing['quiet_hours_start'] ) ? $existing['quiet_hours_start'] : null ) );
    $quiet_end = tonbankcard_api_alerts_normalize_time( tonbankcard_api_alerts_payload_nullable_string( $payload, 'quiet_end', isset( $existing['quiet_hours_end'] ) ? $existing['quiet_hours_end'] : null ) );
    if ( FALSE === $quiet_start ) {
        $errors['quiet_start'] = 'Use HH:MM for quiet-hours start.';
        $quiet_start = null;
    }
    if ( FALSE === $quiet_end ) {
        $errors['quiet_end'] = 'Use HH:MM for quiet-hours end.';
        $quiet_end = null;
    }

    $timezone = tonbankcard_api_alerts_payload_string( $payload, 'timezone', isset( $existing['timezone'] ) ? $existing['timezone'] : 'UTC' );
    $timezone = trim( $timezone );
    if ( '' === $timezone || FALSE === @timezone_open( $timezone ) ) {
        $errors['timezone'] = 'Enter a valid timezone identifier.';
        $timezone = 'UTC';
    }

    $frequency_cap = tonbankcard_api_alerts_payload_int( $payload, 'frequency_cap_seconds', isset( $existing['frequency_cap_seconds'] ) ? (int) $existing['frequency_cap_seconds'] : (int) $settings['default_frequency_cap_seconds'] );
    $frequency_cap = max( (int) $settings['min_frequency_cap_seconds'], min( (int) $settings['max_frequency_cap_seconds'], $frequency_cap ) );

    $max_deliveries = tonbankcard_api_alerts_payload_int( $payload, 'max_deliveries_per_day', isset( $existing['max_deliveries_per_day'] ) ? (int) $existing['max_deliveries_per_day'] : (int) $settings['default_max_deliveries_per_day'] );
    $max_deliveries = max( 1, min( 100, $max_deliveries ) );

    $percent_window = tonbankcard_api_alerts_payload_nullable_int( $payload, 'percent_window_minutes', isset( $existing['percent_window_minutes'] ) ? $existing['percent_window_minutes'] : null );
    $volume_window = tonbankcard_api_alerts_payload_nullable_int( $payload, 'volume_window_minutes', isset( $existing['volume_window_minutes'] ) ? $existing['volume_window_minutes'] : null );

    $context_path = tonbankcard_api_alerts_payload_string( $payload, 'context_path', isset( $existing['context_path'] ) ? $existing['context_path'] : '/currency/' . $coin_id );
    $context_path = tonbankcard_api_alerts_safe_context_path( $context_path, $coin_id );

    if ( ! empty( $errors ) ) {
        return [
            'ok'     => FALSE,
            'errors' => $errors,
        ];
    }

    return [
        'ok'   => TRUE,
        'rule' => [
            'coin_id'                => $coin_id,
            'symbol'                 => '' === $symbol ? null : $symbol,
            'display_name'           => '' === $display_name ? null : $display_name,
            'trigger_type'           => $trigger_type,
            'comparison_operator'    => $operator,
            'threshold_value'        => $threshold_value,
            'percent_window_minutes' => $percent_window,
            'volume_window_minutes'  => $volume_window,
            'delivery_channel'       => $delivery_channel,
            'status'                 => $status,
            'quiet_hours_start'      => $quiet_start,
            'quiet_hours_end'        => $quiet_end,
            'timezone'               => $timezone,
            'frequency_cap_seconds'  => $frequency_cap,
            'max_deliveries_per_day' => $max_deliveries,
            'context_path'           => $context_path,
        ],
    ];
}

/**
 * Evaluates one alert rule against one market row.
 *
 * @param array $rule
 * @param array $market
 * @param array $settings
 * @param int|null $now
 * @return array
 */
function tonbankcard_api_alerts_evaluate_rule( array $rule, array $market, array $settings, $now = null ) {
    $now = null === $now ? time() : (int) $now;
    if ( isset( $rule['status'] ) && 'active' !== $rule['status'] ) {
        return tonbankcard_api_alerts_skip_event( 'paused', $rule, $market, $now );
    }

    if ( tonbankcard_api_alerts_in_quiet_hours( $rule, $now ) ) {
        return tonbankcard_api_alerts_skip_event( 'quiet_hours', $rule, $market, $now );
    }

    if ( tonbankcard_api_alerts_frequency_blocked( $rule, $now ) ) {
        return tonbankcard_api_alerts_skip_event( 'frequency_cap', $rule, $market, $now );
    }

    $metric = tonbankcard_api_alerts_metric_value( $rule, $market );
    if ( null === $metric ) {
        return tonbankcard_api_alerts_skip_event( 'metric_unavailable', $rule, $market, $now );
    }

    $comparison_metric = $metric;
    if ( in_array( isset( $rule['trigger_type'] ) ? $rule['trigger_type'] : '', [ 'percent_move', 'ton_ecosystem' ], TRUE )
        && in_array( isset( $rule['comparison_operator'] ) ? $rule['comparison_operator'] : 'gte', [ 'gt', 'gte' ], TRUE )
    ) {
        $comparison_metric = abs( $metric );
    }

    $threshold = isset( $rule['threshold_value'] ) ? (float) $rule['threshold_value'] : 0.0;
    $operator = isset( $rule['comparison_operator'] ) ? $rule['comparison_operator'] : 'gte';
    $matched = tonbankcard_api_alerts_compare( $comparison_metric, $operator, $threshold );

    if ( 'ton_ecosystem' === ( isset( $rule['trigger_type'] ) ? $rule['trigger_type'] : '' ) && ! tonbankcard_api_alerts_is_ton_asset( $rule, $market ) ) {
        $matched = FALSE;
    }

    if ( ! $matched ) {
        return tonbankcard_api_alerts_skip_event( 'condition_not_met', $rule, $market, $now, $metric );
    }

    return [
        'triggered'     => TRUE,
        'reason'        => 'condition_met',
        'evaluated_at'  => gmdate( 'c', $now ),
        'metric_value'  => $metric,
        'operator'      => $operator,
        'threshold'     => $threshold,
        'fingerprint'   => tonbankcard_api_alerts_fingerprint( $rule, $market, $metric, $threshold ),
        'market'        => tonbankcard_api_alerts_event_market_snapshot( $market ),
    ];
}

/**
 * Builds a Telegram delivery payload for an alert event.
 *
 * @param array $rule
 * @param array $event
 * @param array $settings
 * @param bool $test
 * @return array
 */
function tonbankcard_api_alerts_delivery_payload( array $rule, array $event, array $settings, bool $test = FALSE ) {
    $symbol = tonbankcard_api_alerts_rule_symbol( $rule );
    $trigger_type = isset( $rule['trigger_type'] ) ? (string) $rule['trigger_type'] : 'price_cross';
    $metric = isset( $event['metric_value'] ) && is_numeric( $event['metric_value'] ) ? (float) $event['metric_value'] : ( isset( $rule['threshold_value'] ) ? (float) $rule['threshold_value'] : 0.0 );
    $threshold = isset( $rule['threshold_value'] ) ? (float) $rule['threshold_value'] : 0.0;
    $operator = isset( $rule['comparison_operator'] ) ? (string) $rule['comparison_operator'] : 'gte';
    $prefix = $test ? 'Test alert' : 'Market alert';
    $id = isset( $rule['id'] ) ? (int) $rule['id'] : 0;
    $mini_app_path = 0 < $id ? '/app/alert/' . $id : '/app/alerts?coin=' . rawurlencode( isset( $rule['coin_id'] ) ? (string) $rule['coin_id'] : '' );
    $coin_path = tonbankcard_api_alerts_safe_context_path( isset( $rule['context_path'] ) ? (string) $rule['context_path'] : '', isset( $rule['coin_id'] ) ? (string) $rule['coin_id'] : '' );
    $start_param = 0 < $id ? 'alert_' . $id : 'coin_' . preg_replace( '/[^A-Za-z0-9_-]/', '_', isset( $rule['coin_id'] ) ? (string) $rule['coin_id'] : 'market' );

    return [
        'text'        => sprintf(
            '%s: %s %s is %s %s (current %s).',
            $prefix,
            $symbol,
            str_replace( '_', ' ', $trigger_type ),
            $operator,
            tonbankcard_api_alerts_format_number( $threshold ),
            tonbankcard_api_alerts_format_number( $metric )
        ),
        'startapp'    => $start_param,
        'fingerprint' => isset( $event['fingerprint'] ) ? $event['fingerprint'] : hash( 'sha256', $start_param . '|' . gmdate( 'Y-m-d H:i' ) ),
        'links'       => [
            'mini_app_path'      => $mini_app_path,
            'coin_path'          => $coin_path,
            'telegram_deep_link' => tonbankcard_api_alerts_deep_link( $start_param, $settings, $mini_app_path ),
        ],
        'event'       => [
            'trigger_type' => $trigger_type,
            'metric_value' => $metric,
            'threshold'    => $threshold,
            'operator'     => $operator,
            'evaluated_at' => isset( $event['evaluated_at'] ) ? $event['evaluated_at'] : gmdate( 'c' ),
        ],
    ];
}

/**
 * Handles scheduled evaluation worker runs.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_alerts_evaluate_response( array $request, array $runtime, array $config, array $settings, string $request_id, array $headers ) {
    $worker_token = $settings['worker_token'];
    if ( '' !== $worker_token ) {
        $provided = isset( $request['headers']['x-tonbankcard-alert-worker-token'] ) ? trim( (string) $request['headers']['x-tonbankcard-alert-worker-token'] ) : '';
        if ( ! hash_equals( $worker_token, $provided ) ) {
            return tonbankcard_api_error_response(
                401,
                'alerts_worker_token_required',
                'Alert evaluation requires a valid worker token.',
                [ 'header' => 'X-TONBANKCARD-Alert-Worker-Token' ],
                $request_id,
                $headers
            );
        }
    }

    $database_error = null;
    $pdo = tonbankcard_api_session_database_connection( $runtime, $database_error );
    if ( null === $pdo ) {
        return tonbankcard_api_error_response(
            503,
            'alerts_storage_unavailable',
            'Alert evaluation requires the configured MySQL session store.',
            [ 'storage' => 'mysql' ],
            $request_id,
            $headers
        );
    }

    $payload = tonbankcard_api_json_body( $request );
    $limit = isset( $payload['limit'] ) && is_numeric( $payload['limit'] ) ? max( 1, min( 200, (int) $payload['limit'] ) ) : 50;
    $rules = tonbankcard_api_alerts_due_rules( $pdo, $limit );
    $markets = tonbankcard_api_alerts_market_rows( $rules, $runtime, $config );

    $stats = [
        'evaluated' => 0,
        'triggered' => 0,
        'delivered' => 0,
        'skipped'   => 0,
        'failed'    => 0,
    ];
    $results = [];

    foreach ( $rules as $rule ) {
        $stats['evaluated']++;
        $coin_id = isset( $rule['coin_id'] ) ? (string) $rule['coin_id'] : '';
        $market = isset( $markets[ $coin_id ] ) ? $markets[ $coin_id ] : [];
        $event = tonbankcard_api_alerts_evaluate_rule( $rule, $market, $settings );

        if ( empty( $event['triggered'] ) ) {
            $stats['skipped']++;
            tonbankcard_api_alerts_mark_evaluated( $pdo, $rule, $settings, null, null );
            $results[] = [
                'id'     => (int) $rule['id'],
                'status' => 'skipped',
                'reason' => isset( $event['reason'] ) ? $event['reason'] : 'not_triggered',
            ];
            continue;
        }

        if ( tonbankcard_api_alerts_daily_cap_reached( $pdo, $rule ) ) {
            $stats['skipped']++;
            tonbankcard_api_alerts_mark_evaluated( $pdo, $rule, $settings, null, null );
            $results[] = [
                'id'     => (int) $rule['id'],
                'status' => 'skipped',
                'reason' => 'daily_delivery_cap',
            ];
            continue;
        }

        $stats['triggered']++;
        $delivery = tonbankcard_api_alerts_delivery_payload( $rule, $event, $settings );
        $send = tonbankcard_api_alerts_send_telegram( $rule, $delivery, $settings );
        $delivery_status = ! empty( $send['ok'] ) ? 'sent' : ( empty( $send['retryable'] ) ? 'failed' : 'queued' );
        tonbankcard_api_alerts_record_delivery(
            $pdo,
            $rule,
            $delivery,
            $delivery_status,
            $request_id,
            isset( $send['message_id'] ) ? $send['message_id'] : null,
            isset( $send['error_code'] ) ? $send['error_code'] : null
        );
        tonbankcard_api_alerts_mark_evaluated( $pdo, $rule, $settings, $event['fingerprint'], tonbankcard_api_mysql_datetime( time() ) );

        if ( 'sent' === $delivery_status ) {
            $stats['delivered']++;
        } elseif ( 'failed' === $delivery_status ) {
            $stats['failed']++;
        } else {
            $stats['skipped']++;
        }

        $results[] = [
            'id'       => (int) $rule['id'],
            'status'   => $delivery_status,
            'deep_link' => $delivery['links']['mini_app_path'],
        ];
    }

    tonbankcard_observability_log(
        $runtime,
        $config,
        'info',
        'alerts.evaluation_completed',
        [
            'request_id' => $request_id,
            'stats'      => $stats,
        ]
    );

    return tonbankcard_api_success_response(
        [
            'worker'  => 'alerts',
            'stats'   => $stats,
            'results' => $results,
        ],
        $request_id,
        $headers,
        202
    );
}

/**
 * Returns a validation error response.
 *
 * @param array $normalized
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_alerts_validation_error( array $normalized, string $request_id, array $headers ) {
    return tonbankcard_api_error_response(
        422,
        'invalid_alert_rule',
        'The alert rule payload is invalid.',
        [ 'fields' => isset( $normalized['errors'] ) ? $normalized['errors'] : [] ],
        $request_id,
        $headers
    );
}

/**
 * Parses alert rule and test routes.
 *
 * @param string $path
 * @return array|null
 */
function tonbankcard_api_alerts_rule_route( string $path ) {
    if ( preg_match( '#^/api/alerts/([0-9]+)(/test)?$#', $path, $matches ) ) {
        return [
            'id'   => (int) $matches[1],
            'test' => ! empty( $matches[2] ),
        ];
    }

    return null;
}

/**
 * Returns the user's active and paused alert rules.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param array $settings
 * @return array
 */
function tonbankcard_api_alerts_list_payload( PDO $pdo, int $user_id, array $settings ) {
    $select = $pdo->prepare(
        "SELECT *
         FROM alert_rules
         WHERE user_id = :user_id
           AND deleted_at IS NULL
           AND status IN ('active', 'paused')
         ORDER BY updated_at DESC, id DESC"
    );
    $select->execute( [ ':user_id' => $user_id ] );
    $rules = [];
    foreach ( $select->fetchAll() as $row ) {
        $rules[] = tonbankcard_api_alerts_public_rule( $row, $settings );
    }

    return [
        'rules'    => $rules,
        'settings' => tonbankcard_api_alerts_public_settings( $settings ),
    ];
}

/**
 * Counts non-deleted rules for one user.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @return int
 */
function tonbankcard_api_alerts_user_rule_count( PDO $pdo, int $user_id ) {
    $select = $pdo->prepare(
        "SELECT COUNT(*) AS count
         FROM alert_rules
         WHERE user_id = :user_id
           AND deleted_at IS NULL
           AND status IN ('active', 'paused')"
    );
    $select->execute( [ ':user_id' => $user_id ] );
    $row = $select->fetch();
    return is_array( $row ) && isset( $row['count'] ) ? (int) $row['count'] : 0;
}

/**
 * Inserts a rule.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param array $rule
 * @param array $settings
 * @return array
 */
function tonbankcard_api_alerts_insert_rule( PDO $pdo, int $user_id, array $rule, array $settings ) {
    $insert = $pdo->prepare(
        "INSERT INTO alert_rules (
            user_id, coin_id, symbol, display_name, trigger_type, comparison_operator,
            threshold_value, percent_window_minutes, volume_window_minutes, delivery_channel,
            context_path, status, quiet_hours_start, quiet_hours_end, timezone,
            frequency_cap_seconds, max_deliveries_per_day, next_evaluation_at
         ) VALUES (
            :user_id, :coin_id, :symbol, :display_name, :trigger_type, :comparison_operator,
            :threshold_value, :percent_window_minutes, :volume_window_minutes, :delivery_channel,
            :context_path, :status, :quiet_hours_start, :quiet_hours_end, :timezone,
            :frequency_cap_seconds, :max_deliveries_per_day, UTC_TIMESTAMP(6)
         )"
    );
    $params = tonbankcard_api_alerts_rule_params( $rule );
    $params[':user_id'] = $user_id;
    $insert->execute( $params );

    return tonbankcard_api_alerts_select_rule( $pdo, $user_id, (int) $pdo->lastInsertId(), TRUE );
}

/**
 * Updates a rule.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param int $id
 * @param array $rule
 * @param array $settings
 * @return array
 */
function tonbankcard_api_alerts_update_rule( PDO $pdo, int $user_id, int $id, array $rule, array $settings ) {
    $update = $pdo->prepare(
        "UPDATE alert_rules
         SET coin_id = :coin_id,
             symbol = :symbol,
             display_name = :display_name,
             trigger_type = :trigger_type,
             comparison_operator = :comparison_operator,
             threshold_value = :threshold_value,
             percent_window_minutes = :percent_window_minutes,
             volume_window_minutes = :volume_window_minutes,
             delivery_channel = :delivery_channel,
             context_path = :context_path,
             status = :status,
             quiet_hours_start = :quiet_hours_start,
             quiet_hours_end = :quiet_hours_end,
             timezone = :timezone,
             frequency_cap_seconds = :frequency_cap_seconds,
             max_deliveries_per_day = :max_deliveries_per_day,
             next_evaluation_at = CASE WHEN status <> :status_next THEN UTC_TIMESTAMP(6) ELSE next_evaluation_at END,
             updated_at = UTC_TIMESTAMP(6)
         WHERE id = :id
           AND user_id = :user_id
           AND deleted_at IS NULL"
    );
    $params = tonbankcard_api_alerts_rule_params( $rule );
    $params[':id'] = $id;
    $params[':user_id'] = $user_id;
    $params[':status_next'] = $rule['status'];
    $update->execute( $params );

    return tonbankcard_api_alerts_select_rule( $pdo, $user_id, $id, TRUE );
}

/**
 * Soft-deletes a rule.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param int $id
 * @return void
 */
function tonbankcard_api_alerts_delete_rule( PDO $pdo, int $user_id, int $id ) {
    $delete = $pdo->prepare(
        "UPDATE alert_rules
         SET status = 'deleted',
             deleted_at = UTC_TIMESTAMP(6),
             updated_at = UTC_TIMESTAMP(6)
         WHERE id = :id
           AND user_id = :user_id
           AND deleted_at IS NULL"
    );
    $delete->execute(
        [
            ':id'      => $id,
            ':user_id' => $user_id,
        ]
    );
}

/**
 * Selects one rule.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param int $id
 * @param bool $include_user
 * @return array|null
 */
function tonbankcard_api_alerts_select_rule( PDO $pdo, int $user_id, int $id, bool $include_user = FALSE ) {
    $sql = $include_user
        ? "SELECT alert_rules.*, users.telegram_user_id
           FROM alert_rules
           INNER JOIN users ON users.id = alert_rules.user_id
           WHERE alert_rules.id = :id
             AND alert_rules.user_id = :user_id
             AND alert_rules.deleted_at IS NULL
           LIMIT 1"
        : "SELECT *
           FROM alert_rules
           WHERE id = :id
             AND user_id = :user_id
             AND deleted_at IS NULL
           LIMIT 1";
    $select = $pdo->prepare( $sql );
    $select->execute(
        [
            ':id'      => $id,
            ':user_id' => $user_id,
        ]
    );
    $row = $select->fetch();
    return is_array( $row ) ? $row : null;
}

/**
 * Builds prepared statement params for a rule.
 *
 * @param array $rule
 * @return array
 */
function tonbankcard_api_alerts_rule_params( array $rule ) {
    return [
        ':coin_id'                 => $rule['coin_id'],
        ':symbol'                  => $rule['symbol'],
        ':display_name'            => $rule['display_name'],
        ':trigger_type'            => $rule['trigger_type'],
        ':comparison_operator'     => $rule['comparison_operator'],
        ':threshold_value'         => sprintf( '%.18F', (float) $rule['threshold_value'] ),
        ':percent_window_minutes'  => $rule['percent_window_minutes'],
        ':volume_window_minutes'   => $rule['volume_window_minutes'],
        ':delivery_channel'        => $rule['delivery_channel'],
        ':context_path'            => $rule['context_path'],
        ':status'                  => $rule['status'],
        ':quiet_hours_start'       => $rule['quiet_hours_start'],
        ':quiet_hours_end'         => $rule['quiet_hours_end'],
        ':timezone'                => $rule['timezone'],
        ':frequency_cap_seconds'   => (int) $rule['frequency_cap_seconds'],
        ':max_deliveries_per_day'  => (int) $rule['max_deliveries_per_day'],
    ];
}

/**
 * Returns rules due for evaluation.
 *
 * @param PDO $pdo
 * @param int $limit
 * @return array
 */
function tonbankcard_api_alerts_due_rules( PDO $pdo, int $limit ) {
    $select = $pdo->prepare(
        "SELECT alert_rules.*, users.telegram_user_id
         FROM alert_rules
         INNER JOIN users ON users.id = alert_rules.user_id
         WHERE alert_rules.status = 'active'
           AND alert_rules.deleted_at IS NULL
           AND (alert_rules.next_evaluation_at IS NULL OR alert_rules.next_evaluation_at <= UTC_TIMESTAMP(6))
         ORDER BY alert_rules.next_evaluation_at IS NULL DESC, alert_rules.next_evaluation_at ASC, alert_rules.id ASC
         LIMIT :limit"
    );
    $select->bindValue( ':limit', $limit, PDO::PARAM_INT );
    $select->execute();
    return $select->fetchAll();
}

/**
 * Fetches market rows for due rules.
 *
 * @param array $rules
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_alerts_market_rows( array $rules, array $runtime, array $config ) {
    $ids = [];
    foreach ( $rules as $rule ) {
        if ( ! empty( $rule['coin_id'] ) && ! in_array( $rule['coin_id'], $ids, TRUE ) ) {
            $ids[] = $rule['coin_id'];
        }
    }
    if ( empty( $ids ) ) {
        return [];
    }

    $provider = tonbankcard_api_market_provider_config( $runtime, $config );
    $response = tonbankcard_api_market_fetch(
        'coins/markets',
        [
            'vs_currency' => 'usd',
            'ids'         => implode( ',', $ids ),
            'price_change_percentage' => '24h',
        ],
        $provider,
        $config
    );
    if ( empty( $response['status'] ) || 200 !== (int) $response['status'] ) {
        return [];
    }

    $rows = json_decode( isset( $response['body'] ) ? (string) $response['body'] : '[]', TRUE );
    if ( ! is_array( $rows ) ) {
        return [];
    }

    $mapped = [];
    foreach ( $rows as $row ) {
        if ( is_array( $row ) && ! empty( $row['id'] ) ) {
            $mapped[ (string) $row['id'] ] = $row;
        }
    }

    return $mapped;
}

/**
 * Records an alert delivery attempt.
 *
 * @param PDO $pdo
 * @param array $rule
 * @param array $delivery
 * @param string $status
 * @param string $request_id
 * @param int|null $telegram_message_id
 * @param string|null $error_code
 * @return void
 */
function tonbankcard_api_alerts_record_delivery( PDO $pdo, array $rule, array $delivery, string $status, string $request_id, $telegram_message_id = null, $error_code = null ) {
    $insert = $pdo->prepare(
        "INSERT INTO alert_deliveries (
            alert_rule_id, user_id, delivery_channel, delivery_status, telegram_message_id,
            error_code, request_id, deep_link_url, delivery_fingerprint, attempt_count,
            attempted_at, delivered_at, next_retry_at
         ) VALUES (
            :alert_rule_id, :user_id, 'telegram_bot', :delivery_status, :telegram_message_id,
            :error_code, :request_id, :deep_link_url, :delivery_fingerprint, 1,
            UTC_TIMESTAMP(6), :delivered_at, :next_retry_at
         )"
    );
    $insert->execute(
        [
            ':alert_rule_id'        => (int) $rule['id'],
            ':user_id'              => (int) $rule['user_id'],
            ':delivery_status'      => $status,
            ':telegram_message_id'  => $telegram_message_id,
            ':error_code'           => $error_code,
            ':request_id'           => substr( $request_id, 0, 36 ),
            ':deep_link_url'        => $delivery['links']['telegram_deep_link'],
            ':delivery_fingerprint' => $delivery['fingerprint'],
            ':delivered_at'         => 'sent' === $status ? tonbankcard_api_mysql_datetime( time() ) : null,
            ':next_retry_at'        => 'queued' === $status || 'failed' === $status ? tonbankcard_api_mysql_datetime( time() + 300 ) : null,
        ]
    );
}

/**
 * Marks a rule as evaluated and schedules the next attempt.
 *
 * @param PDO $pdo
 * @param array $rule
 * @param array $settings
 * @param string|null $fingerprint
 * @param string|null $triggered_at
 * @return void
 */
function tonbankcard_api_alerts_mark_evaluated( PDO $pdo, array $rule, array $settings, $fingerprint, $triggered_at ) {
    $update = $pdo->prepare(
        "UPDATE alert_rules
         SET last_evaluated_at = UTC_TIMESTAMP(6),
             last_triggered_at = COALESCE(:triggered_at, last_triggered_at),
             last_delivery_fingerprint = COALESCE(:fingerprint, last_delivery_fingerprint),
             next_evaluation_at = DATE_ADD(UTC_TIMESTAMP(6), INTERVAL :interval_seconds SECOND),
             updated_at = UTC_TIMESTAMP(6)
         WHERE id = :id"
    );
    if ( null === $triggered_at ) {
        $update->bindValue( ':triggered_at', null, PDO::PARAM_NULL );
    } else {
        $update->bindValue( ':triggered_at', $triggered_at );
    }
    if ( null === $fingerprint ) {
        $update->bindValue( ':fingerprint', null, PDO::PARAM_NULL );
    } else {
        $update->bindValue( ':fingerprint', $fingerprint );
    }
    $update->bindValue( ':interval_seconds', (int) $settings['evaluation_interval_seconds'], PDO::PARAM_INT );
    $update->bindValue( ':id', (int) $rule['id'], PDO::PARAM_INT );
    $update->execute();
}

/**
 * Returns whether the daily delivery cap is reached.
 *
 * @param PDO $pdo
 * @param array $rule
 * @return bool
 */
function tonbankcard_api_alerts_daily_cap_reached( PDO $pdo, array $rule ) {
    $limit = isset( $rule['max_deliveries_per_day'] ) ? (int) $rule['max_deliveries_per_day'] : 8;
    $select = $pdo->prepare(
        "SELECT COUNT(*) AS count
         FROM alert_deliveries
         WHERE alert_rule_id = :alert_rule_id
           AND delivery_status IN ('queued', 'sent')
           AND attempted_at >= DATE_SUB(UTC_TIMESTAMP(6), INTERVAL 1 DAY)"
    );
    $select->execute( [ ':alert_rule_id' => (int) $rule['id'] ] );
    $row = $select->fetch();
    $count = is_array( $row ) && isset( $row['count'] ) ? (int) $row['count'] : 0;
    return $count >= $limit;
}

/**
 * Attempts Telegram bot delivery.
 *
 * @param array $rule
 * @param array $delivery
 * @param array $settings
 * @return array
 */
function tonbankcard_api_alerts_send_telegram( array $rule, array $delivery, array $settings ) {
    if ( empty( $settings['bot_token'] ) ) {
        return [
            'ok'         => FALSE,
            'retryable'  => TRUE,
            'error_code' => 'telegram_bot_token_missing',
        ];
    }
    if ( empty( $rule['telegram_user_id'] ) ) {
        return [
            'ok'         => FALSE,
            'retryable'  => FALSE,
            'error_code' => 'telegram_user_missing',
        ];
    }

    $url = 'https://api.telegram.org/bot' . $settings['bot_token'] . '/sendMessage';
    $body = json_encode(
        [
            'chat_id'      => (string) $rule['telegram_user_id'],
            'text'         => $delivery['text'],
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Open alert',
                            'url'  => $delivery['links']['telegram_deep_link'],
                        ],
                    ],
                ],
            ],
        ],
        JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );

    $response_body = '';
    $status = 0;
    if ( function_exists( 'curl_init' ) ) {
        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt( $ch, CURLOPT_POST, TRUE );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ] );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 8 );
        $response_body = curl_exec( $ch );
        $status = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );
    } else {
        $context = stream_context_create(
            [
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\n",
                    'content' => $body,
                    'timeout' => 8,
                ],
            ]
        );
        $response_body = @file_get_contents( $url, FALSE, $context );
        if ( isset( $http_response_header ) && is_array( $http_response_header ) ) {
            foreach ( $http_response_header as $line ) {
                if ( preg_match( '#^HTTP/\S+\s+([0-9]{3})#', $line, $matches ) ) {
                    $status = (int) $matches[1];
                    break;
                }
            }
        }
    }

    $decoded = is_string( $response_body ) ? json_decode( $response_body, TRUE ) : null;
    if ( 200 <= $status && 300 > $status && is_array( $decoded ) && ! empty( $decoded['ok'] ) ) {
        return [
            'ok'         => TRUE,
            'message_id' => isset( $decoded['result']['message_id'] ) ? (int) $decoded['result']['message_id'] : null,
        ];
    }

    return [
        'ok'         => FALSE,
        'retryable'  => 500 <= $status || 0 === $status,
        'error_code' => 'telegram_send_failed',
    ];
}

/**
 * Returns a public alert rule representation.
 *
 * @param array $rule
 * @param array $settings
 * @return array
 */
function tonbankcard_api_alerts_public_rule( array $rule, array $settings ) {
    $start_param = isset( $rule['id'] ) ? 'alert_' . (int) $rule['id'] : '';
    $mini_app_path = isset( $rule['id'] ) ? '/app/alert/' . (int) $rule['id'] : '/app/alerts';

    return [
        'id'                       => isset( $rule['id'] ) ? (int) $rule['id'] : null,
        'coin_id'                  => isset( $rule['coin_id'] ) ? (string) $rule['coin_id'] : '',
        'symbol'                   => isset( $rule['symbol'] ) ? $rule['symbol'] : null,
        'display_name'             => isset( $rule['display_name'] ) ? $rule['display_name'] : null,
        'trigger_type'             => isset( $rule['trigger_type'] ) ? (string) $rule['trigger_type'] : 'price_cross',
        'operator'                 => isset( $rule['comparison_operator'] ) ? (string) $rule['comparison_operator'] : 'gte',
        'threshold'                => isset( $rule['threshold_value'] ) ? (float) $rule['threshold_value'] : null,
        'delivery_channel'         => isset( $rule['delivery_channel'] ) ? (string) $rule['delivery_channel'] : 'telegram_bot',
        'status'                   => isset( $rule['status'] ) ? (string) $rule['status'] : 'active',
        'quiet_start'              => isset( $rule['quiet_hours_start'] ) ? $rule['quiet_hours_start'] : null,
        'quiet_end'                => isset( $rule['quiet_hours_end'] ) ? $rule['quiet_hours_end'] : null,
        'timezone'                 => isset( $rule['timezone'] ) ? (string) $rule['timezone'] : 'UTC',
        'frequency_cap_seconds'    => isset( $rule['frequency_cap_seconds'] ) ? (int) $rule['frequency_cap_seconds'] : (int) $settings['default_frequency_cap_seconds'],
        'max_deliveries_per_day'   => isset( $rule['max_deliveries_per_day'] ) ? (int) $rule['max_deliveries_per_day'] : (int) $settings['default_max_deliveries_per_day'],
        'context_path'             => tonbankcard_api_alerts_safe_context_path( isset( $rule['context_path'] ) ? (string) $rule['context_path'] : '', isset( $rule['coin_id'] ) ? (string) $rule['coin_id'] : '' ),
        'last_evaluated_at'        => isset( $rule['last_evaluated_at'] ) ? $rule['last_evaluated_at'] : null,
        'last_triggered_at'        => isset( $rule['last_triggered_at'] ) ? $rule['last_triggered_at'] : null,
        'next_evaluation_at'       => isset( $rule['next_evaluation_at'] ) ? $rule['next_evaluation_at'] : null,
        'links'                    => [
            'mini_app_path'      => $mini_app_path,
            'telegram_deep_link' => tonbankcard_api_alerts_deep_link( $start_param, $settings, $mini_app_path ),
        ],
    ];
}

/**
 * Determines if an alert is inside quiet hours for the user timezone.
 *
 * @param array $rule
 * @param int $now
 * @return bool
 */
function tonbankcard_api_alerts_in_quiet_hours( array $rule, int $now ) {
    $start = isset( $rule['quiet_hours_start'] ) ? $rule['quiet_hours_start'] : ( isset( $rule['quiet_start'] ) ? $rule['quiet_start'] : null );
    $end = isset( $rule['quiet_hours_end'] ) ? $rule['quiet_hours_end'] : ( isset( $rule['quiet_end'] ) ? $rule['quiet_end'] : null );
    if ( empty( $start ) || empty( $end ) ) {
        return FALSE;
    }

    $timezone = isset( $rule['timezone'] ) && FALSE !== @timezone_open( (string) $rule['timezone'] ) ? (string) $rule['timezone'] : 'UTC';
    $now_time = (int) ( new DateTime( '@' . $now ) )->setTimezone( new DateTimeZone( $timezone ) )->format( 'His' );
    $start_time = (int) str_replace( ':', '', substr( (string) $start, 0, 8 ) );
    $end_time = (int) str_replace( ':', '', substr( (string) $end, 0, 8 ) );

    if ( $start_time === $end_time ) {
        return FALSE;
    }
    if ( $start_time < $end_time ) {
        return $now_time >= $start_time && $now_time < $end_time;
    }

    return $now_time >= $start_time || $now_time < $end_time;
}

/**
 * Determines if the rule is blocked by its frequency cap.
 *
 * @param array $rule
 * @param int $now
 * @return bool
 */
function tonbankcard_api_alerts_frequency_blocked( array $rule, int $now ) {
    if ( empty( $rule['last_triggered_at'] ) ) {
        return FALSE;
    }
    $last = strtotime( (string) $rule['last_triggered_at'] . ' UTC' );
    if ( FALSE === $last ) {
        return FALSE;
    }

    $cap = isset( $rule['frequency_cap_seconds'] ) ? (int) $rule['frequency_cap_seconds'] : 3600;
    return ( $now - $last ) < $cap;
}

/**
 * Creates a skipped evaluation event.
 *
 * @param string $reason
 * @param array $rule
 * @param array $market
 * @param int $now
 * @param float|null $metric
 * @return array
 */
function tonbankcard_api_alerts_skip_event( string $reason, array $rule, array $market, int $now, $metric = null ) {
    return [
        'triggered'    => FALSE,
        'reason'       => $reason,
        'evaluated_at' => gmdate( 'c', $now ),
        'metric_value' => $metric,
        'market'       => tonbankcard_api_alerts_event_market_snapshot( $market ),
    ];
}

/**
 * Returns the rule metric value from market data.
 *
 * @param array $rule
 * @param array $market
 * @return float|null
 */
function tonbankcard_api_alerts_metric_value( array $rule, array $market ) {
    $type = isset( $rule['trigger_type'] ) ? $rule['trigger_type'] : 'price_cross';
    $keys = [
        'price_cross'      => [ 'current_price', 'price' ],
        'market_cap_cross' => [ 'market_cap' ],
        'volume_spike'     => [ 'total_volume', 'volume_24h' ],
        'rank_change'      => [ 'market_cap_rank', 'rank' ],
        'sentiment_change' => [ 'sentiment_score', 'sentiment' ],
        'percent_move'     => [ 'price_change_percentage_24h_in_currency', 'price_change_percentage_24h' ],
        'ton_ecosystem'    => [ 'price_change_percentage_24h_in_currency', 'price_change_percentage_24h' ],
    ];
    $candidates = isset( $keys[ $type ] ) ? $keys[ $type ] : $keys['price_cross'];

    foreach ( $candidates as $key ) {
        if ( isset( $market[ $key ] ) && is_numeric( $market[ $key ] ) ) {
            return (float) $market[ $key ];
        }
        if ( 'sentiment' === $key && isset( $market['sentiment']['score'] ) && is_numeric( $market['sentiment']['score'] ) ) {
            return (float) $market['sentiment']['score'];
        }
    }

    return null;
}

/**
 * Compares a metric to a threshold.
 *
 * @param float $metric
 * @param string $operator
 * @param float $threshold
 * @return bool
 */
function tonbankcard_api_alerts_compare( float $metric, string $operator, float $threshold ) {
    if ( 'gt' === $operator ) {
        return $metric > $threshold;
    }
    if ( 'lt' === $operator ) {
        return $metric < $threshold;
    }
    if ( 'lte' === $operator ) {
        return $metric <= $threshold;
    }

    return $metric >= $threshold;
}

/**
 * Determines TON ecosystem membership.
 *
 * @param array $rule
 * @param array $market
 * @return bool
 */
function tonbankcard_api_alerts_is_ton_asset( array $rule, array $market ) {
    $coin_id = isset( $rule['coin_id'] ) ? strtolower( (string) $rule['coin_id'] ) : '';
    if ( in_array( $coin_id, [ 'toncoin', 'the-open-network' ], TRUE ) ) {
        return TRUE;
    }
    if ( ! empty( $market['ton_asset'] ) || ! empty( $market['is_ton_asset'] ) ) {
        return TRUE;
    }
    if ( isset( $market['tags'] ) && is_array( $market['tags'] ) && in_array( 'ton_ecosystem', $market['tags'], TRUE ) ) {
        return TRUE;
    }

    return FALSE;
}

/**
 * Builds a stable delivery fingerprint.
 *
 * @param array $rule
 * @param array $market
 * @param float $metric
 * @param float $threshold
 * @return string
 */
function tonbankcard_api_alerts_fingerprint( array $rule, array $market, float $metric, float $threshold ) {
    $bucket = gmdate( 'Y-m-d-H' );
    return hash(
        'sha256',
        implode(
            '|',
            [
                isset( $rule['id'] ) ? (string) $rule['id'] : '',
                isset( $rule['trigger_type'] ) ? (string) $rule['trigger_type'] : '',
                isset( $market['id'] ) ? (string) $market['id'] : ( isset( $rule['coin_id'] ) ? (string) $rule['coin_id'] : '' ),
                sprintf( '%.8F', $metric ),
                sprintf( '%.8F', $threshold ),
                $bucket,
            ]
        )
    );
}

/**
 * Returns a small market snapshot for observability and API responses.
 *
 * @param array $market
 * @return array
 */
function tonbankcard_api_alerts_event_market_snapshot( array $market ) {
    $snapshot = [];
    foreach ( [ 'id', 'symbol', 'name', 'current_price', 'market_cap_rank', 'market_cap', 'total_volume', 'price_change_percentage_24h_in_currency', 'price_change_percentage_24h' ] as $key ) {
        if ( array_key_exists( $key, $market ) ) {
            $snapshot[ $key ] = $market[ $key ];
        }
    }
    return $snapshot;
}

/**
 * Builds a synthetic event for the Test alert button.
 *
 * @param array $rule
 * @return array
 */
function tonbankcard_api_alerts_test_event( array $rule ) {
    $metric = isset( $rule['threshold_value'] ) ? (float) $rule['threshold_value'] : 0.0;
    return [
        'triggered'    => TRUE,
        'reason'       => 'test_delivery',
        'evaluated_at' => gmdate( 'c' ),
        'metric_value' => $metric,
        'operator'     => isset( $rule['comparison_operator'] ) ? $rule['comparison_operator'] : 'gte',
        'threshold'    => $metric,
        'fingerprint'  => hash( 'sha256', 'test|' . ( isset( $rule['id'] ) ? (string) $rule['id'] : '0' ) . '|' . gmdate( 'Y-m-d H:i:s' ) ),
        'market'       => [
            'id'     => isset( $rule['coin_id'] ) ? $rule['coin_id'] : '',
            'symbol' => isset( $rule['symbol'] ) ? $rule['symbol'] : '',
        ],
    ];
}

/**
 * Builds a Telegram Mini App deep link.
 *
 * @param string $start_param
 * @param array $settings
 * @param string $mini_app_path
 * @return string
 */
function tonbankcard_api_alerts_deep_link( string $start_param, array $settings, string $mini_app_path = '/app/alerts' ) {
    if ( '' !== $settings['bot_username'] ) {
        return 'https://t.me/' . rawurlencode( ltrim( $settings['bot_username'], '@' ) ) . '?startapp=' . rawurlencode( $start_param );
    }

    $base = '' !== $settings['telegram_base_url'] ? $settings['telegram_base_url'] : $settings['public_base_url'];
    if ( '' === $base ) {
        return $mini_app_path . '?startapp=' . rawurlencode( $start_param );
    }

    return rtrim( $base, '/' ) . $mini_app_path . '?startapp=' . rawurlencode( $start_param );
}

/**
 * Returns a concise symbol label.
 *
 * @param array $rule
 * @return string
 */
function tonbankcard_api_alerts_rule_symbol( array $rule ) {
    if ( ! empty( $rule['symbol'] ) ) {
        return strtoupper( (string) $rule['symbol'] );
    }
    if ( ! empty( $rule['display_name'] ) ) {
        return (string) $rule['display_name'];
    }
    if ( ! empty( $rule['coin_id'] ) ) {
        return strtoupper( str_replace( [ '-', '_' ], ' ', (string) $rule['coin_id'] ) );
    }

    return 'Asset';
}

/**
 * Formats a number for bot text.
 *
 * @param float $value
 * @return string
 */
function tonbankcard_api_alerts_format_number( float $value ) {
    if ( abs( $value ) >= 1000 ) {
        return number_format( $value, 2, '.', ',' );
    }
    if ( abs( $value ) >= 1 ) {
        return rtrim( rtrim( number_format( $value, 4, '.', '' ), '0' ), '.' );
    }

    return rtrim( rtrim( number_format( $value, 8, '.', '' ), '0' ), '.' );
}

/**
 * Reads a string from payload.
 *
 * @param array $payload
 * @param string $key
 * @param string|null $default
 * @return string
 */
function tonbankcard_api_alerts_payload_string( array $payload, string $key, $default = '' ) {
    if ( array_key_exists( $key, $payload ) && null !== $payload[ $key ] ) {
        return (string) $payload[ $key ];
    }
    return null === $default ? '' : (string) $default;
}

/**
 * Reads a nullable string from payload.
 *
 * @param array $payload
 * @param string $key
 * @param string|null $default
 * @return string|null
 */
function tonbankcard_api_alerts_payload_nullable_string( array $payload, string $key, $default = null ) {
    if ( array_key_exists( $key, $payload ) ) {
        $value = trim( (string) $payload[ $key ] );
        return '' === $value ? null : $value;
    }

    return null === $default || '' === trim( (string) $default ) ? null : (string) $default;
}

/**
 * Reads an integer from payload.
 *
 * @param array $payload
 * @param string $key
 * @param int $default
 * @return int
 */
function tonbankcard_api_alerts_payload_int( array $payload, string $key, int $default ) {
    return array_key_exists( $key, $payload ) && is_numeric( $payload[ $key ] ) ? (int) $payload[ $key ] : $default;
}

/**
 * Reads a nullable integer from payload.
 *
 * @param array $payload
 * @param string $key
 * @param int|null $default
 * @return int|null
 */
function tonbankcard_api_alerts_payload_nullable_int( array $payload, string $key, $default = null ) {
    if ( array_key_exists( $key, $payload ) ) {
        return is_numeric( $payload[ $key ] ) ? max( 1, (int) $payload[ $key ] ) : null;
    }

    return null === $default ? null : max( 1, (int) $default );
}

/**
 * Normalizes HH:MM or HH:MM:SS times.
 *
 * @param string|null $time
 * @return string|false|null
 */
function tonbankcard_api_alerts_normalize_time( $time ) {
    if ( null === $time || '' === trim( (string) $time ) ) {
        return null;
    }

    if ( ! preg_match( '/^([01][0-9]|2[0-3]):([0-5][0-9])(?::([0-5][0-9]))?$/', trim( (string) $time ), $matches ) ) {
        return FALSE;
    }

    return $matches[1] . ':' . $matches[2] . ':' . ( isset( $matches[3] ) && '' !== $matches[3] ? $matches[3] : '00' );
}

/**
 * Safely returns an internal context path.
 *
 * @param string $context_path
 * @param string $coin_id
 * @return string
 */
function tonbankcard_api_alerts_safe_context_path( string $context_path, string $coin_id ) {
    $context_path = trim( $context_path );
    if ( '' === $context_path || '/' !== substr( $context_path, 0, 1 ) || 0 === strpos( $context_path, '//' ) ) {
        return '/currency/' . rawurlencode( $coin_id );
    }
    if ( ! preg_match( '#^/[A-Za-z0-9._~!$&\'()*+,;=:@%/?-]{1,190}$#', $context_path ) ) {
        return '/currency/' . rawurlencode( $coin_id );
    }

    return $context_path;
}
