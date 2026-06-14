<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 PREMIUM ENTITLEMENTS API
 * -------------------------------------------------------------------------
 * Telegram Stars checkout, entitlement state, and backend limit helpers.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Returns TRUE when the path belongs to the premium API surface.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_premium_is_request( string $path ) {
    $path = tonbankcard_api_normalize_path( $path );
    return '/api/premium' === $path || 0 === strpos( $path, '/api/premium/' );
}

/**
 * Returns documented premium routes.
 *
 * @return array
 */
function tonbankcard_api_premium_routes() {
    return [
        '/api/premium',
        '/api/premium/plans',
        '/api/premium/entitlement',
        '/api/premium/checkout',
        '/api/premium/entitlement/cancel',
    ];
}

/**
 * Handles premium API requests.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_premium_handle( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $path = tonbankcard_api_normalize_path( $request['path'] );
    $method = strtoupper( $request['method'] );
    $settings = tonbankcard_api_premium_settings( $runtime, $config );

    if ( '/api/premium' === $path ) {
        if ( 'GET' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            [
                'service'     => 'tonbankcard-premium',
                'routes'      => tonbankcard_api_premium_routes(),
                'settings'    => tonbankcard_api_premium_public_settings( $settings ),
                'plans'       => tonbankcard_api_premium_public_plans( $settings ),
                'entitlement' => tonbankcard_api_premium_public_state( tonbankcard_api_premium_state_for_request( $request, $runtime, $config, $settings ) ),
            ],
            $request_id,
            $headers,
            200,
            [ 'route_group' => 'premium' ]
        );
    }

    if ( '/api/premium/plans' === $path ) {
        if ( 'GET' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            [
                'settings' => tonbankcard_api_premium_public_settings( $settings ),
                'plans'    => tonbankcard_api_premium_public_plans( $settings ),
            ],
            $request_id,
            $headers,
            200,
            [ 'route_group' => 'premium' ]
        );
    }

    if ( '/api/premium/entitlement' === $path ) {
        if ( 'GET' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            [
                'entitlement' => tonbankcard_api_premium_public_state( tonbankcard_api_premium_state_for_request( $request, $runtime, $config, $settings ) ),
            ],
            $request_id,
            $headers,
            200,
            [ 'route_group' => 'premium' ]
        );
    }

    if ( '/api/premium/checkout' === $path ) {
        if ( 'POST' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_premium_checkout_response( $request, $runtime, $config, $settings, $request_id, $headers );
    }

    if ( '/api/premium/entitlement/cancel' === $path ) {
        if ( 'POST' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_premium_cancel_response( $request, $runtime, $config, $settings, $request_id, $headers );
    }

    return tonbankcard_api_error_response(
        404,
        'not_found',
        'No premium route matches the request.',
        [ 'path' => $path ],
        $request_id,
        $headers
    );
}

/**
 * Returns normalized premium settings.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_premium_settings( array $runtime, array $config ) {
    $premium = isset( $config['premium'] ) && is_array( $config['premium'] ) ? $config['premium'] : [];
    $telegram = isset( $runtime['telegram'] ) && is_array( $runtime['telegram'] ) ? $runtime['telegram'] : [];
    $features = isset( $runtime['feature_flags'] ) && is_array( $runtime['feature_flags'] )
        ? $runtime['feature_flags']
        : ( isset( $runtime['features'] ) && is_array( $runtime['features'] ) ? $runtime['features'] : [] );

    $plan_code = isset( $premium['monthly_plan_code'] ) ? tonbankcard_api_premium_plan_code( $premium['monthly_plan_code'] ) : 'premium_monthly';
    if ( '' === $plan_code || 'free' === $plan_code ) {
        $plan_code = 'premium_monthly';
    }

    $subscription_period = 2592000;

    $free_limits = tonbankcard_api_premium_normalize_limits(
        isset( $premium['free_limits'] ) && is_array( $premium['free_limits'] ) ? $premium['free_limits'] : [],
        [
            'alerts_per_user'       => 3,
            'watchlist_entries'     => 20,
            'advanced_ranges'       => [ '24h', '7d' ],
            'ai_digest_per_day'     => 1,
            'priority_refresh'      => FALSE,
            'market_refresh_seconds'=> 300,
        ]
    );
    $premium_limits = tonbankcard_api_premium_normalize_limits(
        isset( $premium['premium_limits'] ) && is_array( $premium['premium_limits'] ) ? $premium['premium_limits'] : [],
        [
            'alerts_per_user'       => 100,
            'watchlist_entries'     => 250,
            'advanced_ranges'       => [ '24h', '7d', '30d', '90d', '1y' ],
            'ai_digest_per_day'     => 24,
            'priority_refresh'      => TRUE,
            'market_refresh_seconds'=> 60,
        ]
    );

    return [
        'enabled'                     => isset( $features['premium'] ) ? (bool) $features['premium'] : FALSE,
        'checkout_enabled'            => isset( $premium['checkout_enabled'] ) ? (bool) $premium['checkout_enabled'] : ( isset( $features['premium'] ) ? (bool) $features['premium'] : FALSE ),
        'provider'                    => 'telegram_stars',
        'currency'                    => 'XTR',
        'provider_token'              => '',
        'bot_username'                => isset( $telegram['bot_username'] ) ? trim( (string) $telegram['bot_username'] ) : '',
        'bot_token'                   => isset( $telegram['bot_token'] ) ? trim( (string) $telegram['bot_token'] ) : '',
        'signing_secret'              => isset( $premium['signing_secret'] ) ? trim( (string) $premium['signing_secret'] ) : '',
        'premium_plan_code'           => $plan_code,
        'subscription_period_seconds' => $subscription_period,
        'plans'                       => [
            'free'      => [
                'code'                        => 'free',
                'name'                        => 'Free',
                'description'                 => 'Core market tracking with starter limits.',
                'price_stars'                 => 0,
                'currency'                    => 'XTR',
                'recurring'                   => FALSE,
                'subscription_period_seconds' => null,
                'duration_seconds'            => null,
                'limits'                      => $free_limits,
            ],
            $plan_code => [
                'code'                        => $plan_code,
                'name'                        => isset( $premium['monthly_plan_name'] ) && '' !== trim( (string) $premium['monthly_plan_name'] ) ? trim( (string) $premium['monthly_plan_name'] ) : 'TONBANKCARD Premium',
                'description'                 => isset( $premium['monthly_plan_description'] ) && '' !== trim( (string) $premium['monthly_plan_description'] ) ? trim( (string) $premium['monthly_plan_description'] ) : 'Telegram Stars monthly subscription for higher limits and priority refresh.',
                'price_stars'                 => isset( $premium['monthly_price_stars'] ) ? min( 10000, max( 1, (int) $premium['monthly_price_stars'] ) ) : 199,
                'currency'                    => 'XTR',
                'recurring'                   => TRUE,
                'subscription_period_seconds' => $subscription_period,
                'duration_seconds'            => $subscription_period,
                'limits'                      => $premium_limits,
            ],
        ],
    ];
}

/**
 * Normalizes entitlement limit values and exposes aliases used by older code.
 *
 * @param array $source
 * @param array $fallback
 * @return array
 */
function tonbankcard_api_premium_normalize_limits( array $source, array $fallback ) {
    $alerts = isset( $source['alerts_per_user'] ) ? $source['alerts_per_user'] : ( isset( $source['alerts'] ) ? $source['alerts'] : $fallback['alerts_per_user'] );
    $watchlist = isset( $source['watchlist_entries'] ) ? $source['watchlist_entries'] : ( isset( $source['watchlist'] ) ? $source['watchlist'] : $fallback['watchlist_entries'] );
    $ranges = isset( $source['advanced_ranges'] ) && is_array( $source['advanced_ranges'] ) ? $source['advanced_ranges'] : $fallback['advanced_ranges'];

    $normalized_ranges = [];
    foreach ( $ranges as $range ) {
        $range = strtolower( trim( (string) $range ) );
        if ( '' !== $range && preg_match( '/^[0-9]+[hdmy]$/', $range ) ) {
            $normalized_ranges[] = $range;
        }
    }

    $limits = [
        'alerts_per_user'        => max( 0, min( 500, (int) $alerts ) ),
        'watchlist_entries'      => max( 0, min( 1000, (int) $watchlist ) ),
        'advanced_ranges'        => array_values( array_unique( $normalized_ranges ) ),
        'ai_digest_per_day'      => isset( $source['ai_digest_per_day'] ) ? max( 0, min( 100, (int) $source['ai_digest_per_day'] ) ) : (int) $fallback['ai_digest_per_day'],
        'priority_refresh'       => isset( $source['priority_refresh'] ) ? (bool) $source['priority_refresh'] : (bool) $fallback['priority_refresh'],
        'market_refresh_seconds' => isset( $source['market_refresh_seconds'] ) ? max( 15, min( 3600, (int) $source['market_refresh_seconds'] ) ) : (int) $fallback['market_refresh_seconds'],
    ];
    $limits['alerts'] = $limits['alerts_per_user'];
    $limits['watchlist'] = $limits['watchlist_entries'];

    return $limits;
}

/**
 * Returns settings that are safe for browser clients.
 *
 * @param array $settings
 * @return array
 */
function tonbankcard_api_premium_public_settings( array $settings ) {
    return [
        'enabled'                     => (bool) $settings['enabled'],
        'checkout_enabled'            => (bool) $settings['checkout_enabled'] && '' !== $settings['bot_token'],
        'provider'                    => 'telegram_stars',
        'currency'                    => 'XTR',
        'premium_plan_code'           => $settings['premium_plan_code'],
        'subscription_period_seconds' => (int) $settings['subscription_period_seconds'],
        'telegram_bot_configured'     => '' !== $settings['bot_token'],
        'bot_username'                => $settings['bot_username'],
    ];
}

/**
 * Returns public plan metadata.
 *
 * @param array $settings
 * @return array
 */
function tonbankcard_api_premium_public_plans( array $settings ) {
    $plans = [];
    foreach ( $settings['plans'] as $plan ) {
        $plans[] = tonbankcard_api_premium_public_plan( $plan );
    }

    return $plans;
}

/**
 * Returns public plan metadata for one plan.
 *
 * @param array $plan
 * @return array
 */
function tonbankcard_api_premium_public_plan( array $plan ) {
    return [
        'code'                        => $plan['code'],
        'name'                        => $plan['name'],
        'description'                 => $plan['description'],
        'price_stars'                 => (int) $plan['price_stars'],
        'currency'                    => $plan['currency'],
        'recurring'                   => (bool) $plan['recurring'],
        'subscription_period_seconds' => null === $plan['subscription_period_seconds'] ? null : (int) $plan['subscription_period_seconds'],
        'duration_seconds'            => null === $plan['duration_seconds'] ? null : (int) $plan['duration_seconds'],
        'limits'                      => $plan['limits'],
    ];
}

/**
 * Returns a free entitlement state.
 *
 * @param array $settings
 * @param string $reason
 * @return array
 */
function tonbankcard_api_premium_free_state( array $settings, string $reason = 'free' ) {
    return [
        'plan_code'         => 'free',
        'entitled'          => FALSE,
        'status'            => 'free',
        'source'            => null,
        'reason'            => $reason,
        'limits'            => $settings['plans']['free']['limits'],
        'entitlement'       => null,
        'upgrade_plan_code' => $settings['premium_plan_code'],
    ];
}

/**
 * Returns a safe entitlement state for an API request, falling back to free.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @return array
 */
function tonbankcard_api_premium_state_for_request( array $request, array $runtime, array $config, array $settings ) {
    $session = tonbankcard_api_premium_trusted_session( $request, $runtime );
    if ( empty( $session['ok'] ) ) {
        return tonbankcard_api_premium_free_state( $settings, isset( $session['reason'] ) ? $session['reason'] : 'anonymous' );
    }

    return tonbankcard_api_premium_limits_for_user( $session['pdo'], (int) $session['user_id'], $runtime, $config );
}

/**
 * Returns entitlement state and limits for a trusted user.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_premium_limits_for_user( PDO $pdo, int $user_id, array $runtime, array $config ) {
    $settings = tonbankcard_api_premium_settings( $runtime, $config );
    tonbankcard_api_premium_expire_user_entitlements( $pdo, $user_id );
    $entitlement = tonbankcard_api_premium_current_entitlement( $pdo, $user_id, $settings );

    if ( null === $entitlement ) {
        return tonbankcard_api_premium_free_state( $settings, 'no_active_entitlement' );
    }

    $plan_code = isset( $settings['plans'][ $entitlement['plan_code'] ] ) ? $entitlement['plan_code'] : $settings['premium_plan_code'];
    return [
        'plan_code'         => $plan_code,
        'entitled'          => TRUE,
        'status'            => $entitlement['status'],
        'source'            => $entitlement['source'],
        'reason'            => 'active_entitlement',
        'limits'            => $settings['plans'][ $plan_code ]['limits'],
        'entitlement'       => tonbankcard_api_premium_public_entitlement( $entitlement ),
        'upgrade_plan_code' => $settings['premium_plan_code'],
    ];
}

/**
 * Expires stale entitlement rows for a user.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @return void
 */
function tonbankcard_api_premium_expire_user_entitlements( PDO $pdo, int $user_id ) {
    $update = $pdo->prepare(
        "UPDATE premium_entitlements
         SET status = 'expired',
             updated_at = UTC_TIMESTAMP(6)
         WHERE user_id = :user_id
           AND status IN ('trialing', 'active', 'past_due')
           AND expires_at IS NOT NULL
           AND expires_at <= UTC_TIMESTAMP(6)"
    );
    $update->execute( [ ':user_id' => $user_id ] );
}

/**
 * Loads the current active entitlement for a user.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param array $settings
 * @return array|null
 */
function tonbankcard_api_premium_current_entitlement( PDO $pdo, int $user_id, array $settings ) {
    $select = $pdo->prepare(
        "SELECT *
         FROM premium_entitlements
         WHERE user_id = :user_id
           AND status IN ('trialing', 'active')
           AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP(6))
         ORDER BY expires_at DESC, id DESC
         LIMIT 1"
    );
    $select->execute( [ ':user_id' => $user_id ] );
    $row = $select->fetch();

    return is_array( $row ) ? $row : null;
}

/**
 * Converts an entitlement row to a public payload.
 *
 * @param array $row
 * @return array
 */
function tonbankcard_api_premium_public_entitlement( array $row ) {
    return [
        'id'                   => isset( $row['id'] ) ? (int) $row['id'] : null,
        'plan_code'            => isset( $row['plan_code'] ) ? (string) $row['plan_code'] : '',
        'status'               => isset( $row['status'] ) ? (string) $row['status'] : '',
        'source'               => isset( $row['source'] ) ? (string) $row['source'] : '',
        'starts_at'            => tonbankcard_api_premium_iso_datetime( isset( $row['starts_at'] ) ? $row['starts_at'] : null ),
        'expires_at'           => tonbankcard_api_premium_iso_datetime( isset( $row['expires_at'] ) ? $row['expires_at'] : null ),
        'renewed_at'           => tonbankcard_api_premium_iso_datetime( isset( $row['renewed_at'] ) ? $row['renewed_at'] : null ),
        'revoked_at'           => tonbankcard_api_premium_iso_datetime( isset( $row['revoked_at'] ) ? $row['revoked_at'] : null ),
        'cancel_at_period_end' => ! empty( $row['cancel_at_period_end'] ),
        'canceled_at'          => tonbankcard_api_premium_iso_datetime( isset( $row['canceled_at'] ) ? $row['canceled_at'] : null ),
        'refunded_at'          => tonbankcard_api_premium_iso_datetime( isset( $row['refunded_at'] ) ? $row['refunded_at'] : null ),
    ];
}

/**
 * Converts an entitlement state to a public payload.
 *
 * @param array $state
 * @return array
 */
function tonbankcard_api_premium_public_state( array $state ) {
    return [
        'plan_code'         => $state['plan_code'],
        'entitled'          => (bool) $state['entitled'],
        'status'            => $state['status'],
        'source'            => $state['source'],
        'reason'            => $state['reason'],
        'limits'            => $state['limits'],
        'entitlement'       => $state['entitlement'],
        'upgrade_plan_code' => $state['upgrade_plan_code'],
    ];
}

/**
 * Returns TRUE when a feature value is allowed by the current premium state.
 *
 * @param array $state
 * @param string $feature
 * @param mixed $value
 * @return bool
 */
function tonbankcard_api_premium_limit_allows( array $state, string $feature, $value ) {
    $limits = isset( $state['limits'] ) && is_array( $state['limits'] ) ? $state['limits'] : [];

    if ( 'advanced_ranges' === $feature ) {
        return in_array( strtolower( (string) $value ), isset( $limits['advanced_ranges'] ) ? $limits['advanced_ranges'] : [], TRUE );
    }

    $limit = tonbankcard_api_premium_limit_value( $state, $feature, 0 );
    if ( 0 >= $limit ) {
        return TRUE;
    }

    return (int) $value <= $limit;
}

/**
 * Reads a named limit from a premium state.
 *
 * @param array $state
 * @param string $feature
 * @param int $fallback
 * @return int
 */
function tonbankcard_api_premium_limit_value( array $state, string $feature, int $fallback = 0 ) {
    $limits = isset( $state['limits'] ) && is_array( $state['limits'] ) ? $state['limits'] : [];
    $aliases = [
        'alerts'           => 'alerts_per_user',
        'alerts_per_user'  => 'alerts_per_user',
        'watchlist'        => 'watchlist_entries',
        'watchlist_entries'=> 'watchlist_entries',
    ];
    $key = isset( $aliases[ $feature ] ) ? $aliases[ $feature ] : $feature;

    return isset( $limits[ $key ] ) ? (int) $limits[ $key ] : $fallback;
}

/**
 * Returns details for a premium limit error.
 *
 * @param array $state
 * @param string $feature
 * @param mixed $attempted
 * @return array
 */
function tonbankcard_api_premium_limit_error_details( array $state, string $feature, $attempted ) {
    return [
        'feature'           => $feature,
        'attempted'         => $attempted,
        'limit'             => tonbankcard_api_premium_limit_value( $state, $feature, 0 ),
        'plan_code'         => isset( $state['plan_code'] ) ? $state['plan_code'] : 'free',
        'upgrade_plan_code' => isset( $state['upgrade_plan_code'] ) ? $state['upgrade_plan_code'] : 'premium_monthly',
        'entitled'          => ! empty( $state['entitled'] ),
    ];
}

/**
 * Handles checkout link creation.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_premium_checkout_response( array $request, array $runtime, array $config, array $settings, string $request_id, array $headers ) {
    if ( empty( $settings['checkout_enabled'] ) ) {
        return tonbankcard_api_error_response(
            503,
            'premium_checkout_disabled',
            'Premium checkout is disabled for this deployment.',
            [ 'feature' => 'premium' ],
            $request_id,
            $headers
        );
    }

    if ( '' === $settings['bot_token'] ) {
        return tonbankcard_api_error_response(
            503,
            'premium_bot_token_missing',
            'Telegram Stars checkout requires a configured Telegram bot token.',
            [ 'config' => 'TONBANKCARD_BOT_TOKEN' ],
            $request_id,
            $headers
        );
    }

    $session = tonbankcard_api_premium_trusted_session( $request, $runtime );
    if ( empty( $session['ok'] ) ) {
        return tonbankcard_api_error_response(
            isset( $session['reason'] ) && 'storage_unavailable' === $session['reason'] ? 503 : 401,
            isset( $session['reason'] ) && 'storage_unavailable' === $session['reason'] ? 'premium_storage_unavailable' : 'premium_session_required',
            isset( $session['reason'] ) && 'storage_unavailable' === $session['reason']
                ? 'Premium checkout requires the configured MySQL session store.'
                : 'Premium checkout requires a validated Telegram session.',
            [ 'session' => 'telegram_validated' ],
            $request_id,
            $headers
        );
    }

    if ( empty( $session['telegram_user_id'] ) ) {
        return tonbankcard_api_error_response(
            409,
            'premium_telegram_user_required',
            'Premium checkout requires a Telegram user identity.',
            [ 'session' => 'telegram_validated' ],
            $request_id,
            $headers
        );
    }

    $payload = tonbankcard_api_json_body( $request );
    $plan_code = isset( $payload['plan_code'] ) ? tonbankcard_api_premium_plan_code( $payload['plan_code'] ) : $settings['premium_plan_code'];
    if ( ! isset( $settings['plans'][ $plan_code ] ) || 'free' === $plan_code ) {
        return tonbankcard_api_error_response(
            400,
            'premium_plan_unavailable',
            'The requested premium plan is not available.',
            [ 'plan_code' => $plan_code ],
            $request_id,
            $headers
        );
    }

    $invoice = tonbankcard_api_premium_create_invoice_link(
        $settings['plans'][ $plan_code ],
        (int) $session['user_id'],
        (int) $session['telegram_user_id'],
        $runtime,
        $config,
        $settings
    );

    if ( empty( $invoice['ok'] ) ) {
        return tonbankcard_api_error_response(
            isset( $invoice['status'] ) ? (int) $invoice['status'] : 502,
            isset( $invoice['code'] ) ? (string) $invoice['code'] : 'premium_invoice_failed',
            isset( $invoice['message'] ) ? (string) $invoice['message'] : 'Telegram Stars invoice link could not be created.',
            isset( $invoice['details'] ) && is_array( $invoice['details'] ) ? $invoice['details'] : [],
            $request_id,
            $headers
        );
    }

    tonbankcard_api_premium_record_event(
        $session['pdo'],
        [
            'user_id'              => (int) $session['user_id'],
            'event_type'           => 'invoice_created',
            'plan_code'            => $plan_code,
            'invoice_payload_hash' => hash( 'sha256', $invoice['payload'] ),
            'amount_stars'         => (int) $settings['plans'][ $plan_code ]['price_stars'],
            'currency'             => 'XTR',
        ]
    );

    return tonbankcard_api_success_response(
        [
            'invoice_link' => $invoice['invoice_link'],
            'plan'         => tonbankcard_api_premium_public_plan( $settings['plans'][ $plan_code ] ),
        ],
        $request_id,
        $headers,
        201,
        [ 'route_group' => 'premium' ]
    );
}

/**
 * Handles cancellation intent for the active entitlement.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_premium_cancel_response( array $request, array $runtime, array $config, array $settings, string $request_id, array $headers ) {
    $session = tonbankcard_api_premium_trusted_session( $request, $runtime );
    if ( empty( $session['ok'] ) ) {
        return tonbankcard_api_error_response(
            isset( $session['reason'] ) && 'storage_unavailable' === $session['reason'] ? 503 : 401,
            isset( $session['reason'] ) && 'storage_unavailable' === $session['reason'] ? 'premium_storage_unavailable' : 'premium_session_required',
            isset( $session['reason'] ) && 'storage_unavailable' === $session['reason']
                ? 'Premium entitlement updates require the configured MySQL session store.'
                : 'Premium entitlement updates require a validated Telegram session.',
            [ 'session' => 'telegram_validated' ],
            $request_id,
            $headers
        );
    }

    $payload = tonbankcard_api_json_body( $request );
    $entitlement = tonbankcard_api_premium_cancel_active_entitlement( $session['pdo'], (int) $session['user_id'] );
    if ( null !== $entitlement ) {
        tonbankcard_api_premium_record_event(
            $session['pdo'],
            [
                'user_id'        => (int) $session['user_id'],
                'entitlement_id' => isset( $entitlement['id'] ) ? (int) $entitlement['id'] : null,
                'event_type'     => 'subscription_canceled',
                'plan_code'      => isset( $entitlement['plan_code'] ) ? (string) $entitlement['plan_code'] : $settings['premium_plan_code'],
                'currency'       => 'XTR',
            ]
        );
    }

    $telegram_payment_charge_id = isset( $payload['telegram_payment_charge_id'] ) ? substr( trim( (string) $payload['telegram_payment_charge_id'] ), 0, 180 ) : '';
    if ( '' === $telegram_payment_charge_id && null !== $entitlement && ! empty( $entitlement['last_telegram_payment_charge_id'] ) ) {
        $telegram_payment_charge_id = trim( (string) $entitlement['last_telegram_payment_charge_id'] );
    }

    if ( '' !== $telegram_payment_charge_id && '' !== $settings['bot_token'] && ! empty( $session['telegram_user_id'] ) ) {
        tonbankcard_api_premium_telegram_request(
            'editUserStarSubscription',
            [
                'user_id'                    => (int) $session['telegram_user_id'],
                'telegram_payment_charge_id' => $telegram_payment_charge_id,
                'is_canceled'                => TRUE,
            ],
            $runtime,
            $config
        );
    }

    return tonbankcard_api_success_response(
        [
            'cancel_at_period_end' => null !== $entitlement,
            'entitlement'          => tonbankcard_api_premium_public_state( tonbankcard_api_premium_limits_for_user( $session['pdo'], (int) $session['user_id'], $runtime, $config ) ),
        ],
        $request_id,
        $headers,
        200,
        [ 'route_group' => 'premium' ]
    );
}

/**
 * Creates a Telegram Stars invoice link using createInvoiceLink.
 *
 * @param array $plan
 * @param int $user_id
 * @param int $telegram_user_id
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @return array
 */
function tonbankcard_api_premium_create_invoice_link( array $plan, int $user_id, int $telegram_user_id, array $runtime, array $config, array $settings ) {
    $payload = tonbankcard_api_premium_invoice_payload( $plan['code'], $user_id, $telegram_user_id, $settings );
    if ( null === $payload ) {
        return [
            'ok'      => FALSE,
            'status'  => 503,
            'code'    => 'premium_payload_signing_unavailable',
            'message' => 'Premium invoice payload signing is not configured.',
            'details' => [ 'config' => 'TONBANKCARD_BOT_TOKEN' ],
        ];
    }

    $body = [
        'title'          => $plan['name'],
        'description'    => $plan['description'],
        'payload'        => $payload,
        'provider_token' => '',
        'currency'       => 'XTR',
        'prices'         => [
            [
                'label'  => $plan['name'],
                'amount' => (int) $plan['price_stars'],
            ],
        ],
    ];

    if ( ! empty( $plan['recurring'] ) ) {
        $body['subscription_period'] = (int) $plan['subscription_period_seconds'];
    }

    $result = tonbankcard_api_premium_telegram_request( 'createInvoiceLink', $body, $runtime, $config );
    if ( empty( $result['ok'] ) ) {
        return [
            'ok'      => FALSE,
            'status'  => isset( $result['status'] ) ? (int) $result['status'] : 502,
            'code'    => isset( $result['code'] ) ? (string) $result['code'] : 'telegram_invoice_error',
            'message' => isset( $result['message'] ) ? (string) $result['message'] : 'Telegram createInvoiceLink failed.',
            'details' => isset( $result['details'] ) && is_array( $result['details'] ) ? $result['details'] : [],
        ];
    }

    $invoice_link = is_string( $result['result'] ) ? $result['result'] : ( isset( $result['result']['invoice_link'] ) ? $result['result']['invoice_link'] : '' );
    if ( '' === $invoice_link ) {
        return [
            'ok'      => FALSE,
            'status'  => 502,
            'code'    => 'telegram_invoice_link_missing',
            'message' => 'Telegram createInvoiceLink returned an unexpected payload.',
            'details' => [],
        ];
    }

    return [
        'ok'           => TRUE,
        'invoice_link' => $invoice_link,
        'payload'      => $payload,
        'request'      => $body,
    ];
}

/**
 * Calls the Telegram Bot API or an injected test transport.
 *
 * @param string $method
 * @param array $body
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_premium_telegram_request( string $method, array $body, array $runtime, array $config ) {
    $premium = isset( $config['premium'] ) && is_array( $config['premium'] ) ? $config['premium'] : [];
    if ( isset( $premium['transport'] ) && is_callable( $premium['transport'] ) ) {
        $result = call_user_func( $premium['transport'], $method, $body );
        return is_array( $result ) ? $result : [ 'ok' => FALSE, 'code' => 'telegram_transport_invalid', 'message' => 'Premium transport returned an invalid response.' ];
    }

    $bot_token = isset( $runtime['telegram']['bot_token'] ) ? trim( (string) $runtime['telegram']['bot_token'] ) : '';
    if ( '' === $bot_token ) {
        return [
            'ok'      => FALSE,
            'status'  => 503,
            'code'    => 'telegram_bot_token_missing',
            'message' => 'Telegram bot token is not configured.',
            'details' => [ 'config' => 'TONBANKCARD_BOT_TOKEN' ],
        ];
    }

    $url = 'https://api.telegram.org/bot' . $bot_token . '/' . rawurlencode( $method );
    $json = json_encode( $body );
    $response_body = FALSE;
    $status = 0;

    if ( function_exists( 'curl_init' ) ) {
        $curl = curl_init( $url );
        curl_setopt( $curl, CURLOPT_POST, TRUE );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ] );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $json );
        curl_setopt( $curl, CURLOPT_TIMEOUT, 10 );
        $response_body = curl_exec( $curl );
        $status = (int) curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        curl_close( $curl );
    } else {
        $context = stream_context_create(
            [
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\n",
                    'content' => $json,
                    'timeout' => 10,
                ],
            ]
        );
        $response_body = @file_get_contents( $url, FALSE, $context );
        if ( isset( $http_response_header ) && is_array( $http_response_header ) && preg_match( '#HTTP/\S+\s+(\d+)#', $http_response_header[0], $matches ) ) {
            $status = (int) $matches[1];
        }
    }

    if ( FALSE === $response_body || '' === (string) $response_body ) {
        return [
            'ok'      => FALSE,
            'status'  => 502,
            'code'    => 'telegram_request_failed',
            'message' => 'Telegram Bot API request failed.',
            'details' => [ 'method' => $method ],
        ];
    }

    $decoded = json_decode( (string) $response_body, TRUE );
    if ( ! is_array( $decoded ) ) {
        return [
            'ok'      => FALSE,
            'status'  => 502,
            'code'    => 'telegram_response_invalid',
            'message' => 'Telegram Bot API returned invalid JSON.',
            'details' => [ 'http_status' => $status ],
        ];
    }

    if ( empty( $decoded['ok'] ) ) {
        return [
            'ok'      => FALSE,
            'status'  => 502,
            'code'    => 'telegram_api_error',
            'message' => isset( $decoded['description'] ) ? (string) $decoded['description'] : 'Telegram Bot API returned an error.',
            'details' => [ 'http_status' => $status, 'error_code' => isset( $decoded['error_code'] ) ? (int) $decoded['error_code'] : null ],
        ];
    }

    return [
        'ok'     => TRUE,
        'result' => isset( $decoded['result'] ) ? $decoded['result'] : null,
    ];
}

/**
 * Builds a compact signed invoice payload.
 *
 * @param string $plan_code
 * @param int $user_id
 * @param int $telegram_user_id
 * @param array $settings
 * @return string|null
 */
function tonbankcard_api_premium_invoice_payload( string $plan_code, int $user_id, int $telegram_user_id, array $settings ) {
    $secret = tonbankcard_api_premium_signing_secret( $settings );
    if ( '' === $secret ) {
        return null;
    }

    $nonce = bin2hex( random_bytes( 8 ) );
    $base = implode( ':', [ $plan_code, $user_id, $telegram_user_id, $nonce ] );
    $signature = substr( hash_hmac( 'sha256', $base, $secret ), 0, 24 );

    return 'tbcpm:' . $base . ':' . $signature;
}

/**
 * Parses and validates a premium invoice payload.
 *
 * @param string $payload
 * @param array $settings
 * @return array|null
 */
function tonbankcard_api_premium_parse_invoice_payload( string $payload, array $settings ) {
    if ( ! preg_match( '/^tbcpm:([a-z0-9_-]{1,80}):([0-9]{1,20}):([0-9]{1,20}):([a-f0-9]{16}):([a-f0-9]{24})$/', $payload, $matches ) ) {
        return null;
    }

    $plan_code = $matches[1];
    if ( ! isset( $settings['plans'][ $plan_code ] ) || 'free' === $plan_code ) {
        return null;
    }

    $secret = tonbankcard_api_premium_signing_secret( $settings );
    if ( '' === $secret ) {
        return null;
    }

    $base = implode( ':', [ $matches[1], $matches[2], $matches[3], $matches[4] ] );
    $expected = substr( hash_hmac( 'sha256', $base, $secret ), 0, 24 );
    if ( ! hash_equals( $expected, $matches[5] ) ) {
        return null;
    }

    return [
        'plan_code'        => $plan_code,
        'user_id'          => (int) $matches[2],
        'telegram_user_id' => (int) $matches[3],
        'nonce'            => $matches[4],
    ];
}

/**
 * Returns the dedicated secret used to sign invoice payloads.
 *
 * A dedicated `premium_signing_secret` is required: the bot token is shared with
 * the webhook trust boundary, so falling back to it would let anyone who can
 * deliver a webhook also forge a signed invoice payload. When no dedicated
 * secret is configured, signing and verification are disabled (returns '').
 *
 * @param array $settings
 * @return string
 */
function tonbankcard_api_premium_signing_secret( array $settings ) {
    return ! empty( $settings['signing_secret'] ) ? (string) $settings['signing_secret'] : '';
}

/**
 * Handles a Telegram pre_checkout_query update.
 *
 * @param array $pre_checkout_query
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @return array
 */
function tonbankcard_api_premium_pre_checkout_response( array $pre_checkout_query, array $runtime, array $config, string $request_id ) {
    $settings = tonbankcard_api_premium_settings( $runtime, $config );
    $query_id = isset( $pre_checkout_query['id'] ) ? (string) $pre_checkout_query['id'] : '';
    if ( '' === $query_id ) {
        return [
            'ok'      => FALSE,
            'status'  => 400,
            'code'    => 'premium_pre_checkout_id_missing',
            'message' => 'Telegram pre_checkout_query must include id.',
            'details' => [ 'field' => 'pre_checkout_query.id' ],
        ];
    }

    $payload = isset( $pre_checkout_query['invoice_payload'] ) ? (string) $pre_checkout_query['invoice_payload'] : '';
    $parsed = tonbankcard_api_premium_parse_invoice_payload( $payload, $settings );
    $approved = TRUE;
    $error_message = '';
    if ( null === $parsed ) {
        $approved = FALSE;
        $error_message = 'Premium invoice is no longer valid.';
    }

    $plan = $approved ? $settings['plans'][ $parsed['plan_code'] ] : null;
    if ( $approved && 'XTR' !== ( isset( $pre_checkout_query['currency'] ) ? (string) $pre_checkout_query['currency'] : '' ) ) {
        $approved = FALSE;
        $error_message = 'Premium checkout must use Telegram Stars.';
    }
    if ( $approved && (int) $pre_checkout_query['total_amount'] !== (int) $plan['price_stars'] ) {
        $approved = FALSE;
        $error_message = 'Premium checkout amount does not match the selected plan.';
    }
    if ( $approved && isset( $pre_checkout_query['from']['id'] ) && (int) $pre_checkout_query['from']['id'] !== (int) $parsed['telegram_user_id'] ) {
        $approved = FALSE;
        $error_message = 'Premium checkout user does not match the invoice.';
    }

    if ( $approved ) {
        $database_error = null;
        $pdo = tonbankcard_api_session_database_connection( $runtime, $database_error );
        if ( null !== $pdo ) {
            tonbankcard_api_premium_record_event(
                $pdo,
                [
                    'user_id'              => (int) $parsed['user_id'],
                    'event_type'           => 'pre_checkout_approved',
                    'plan_code'            => $parsed['plan_code'],
                    'invoice_payload_hash' => hash( 'sha256', $payload ),
                    'amount_stars'         => (int) $plan['price_stars'],
                    'currency'             => 'XTR',
                ]
            );
        }
    }

    return [
        'ok'      => TRUE,
        'payload' => [
            'method'                => 'answerPreCheckoutQuery',
            'pre_checkout_query_id' => $query_id,
            'ok'                    => $approved,
            'error_message'         => $approved ? null : $error_message,
        ],
    ];
}

/**
 * Handles a Telegram successful_payment message update.
 *
 * @param array $message
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @return array
 */
function tonbankcard_api_premium_successful_payment_response( array $message, array $runtime, array $config, string $request_id ) {
    $settings = tonbankcard_api_premium_settings( $runtime, $config );
    $payment = isset( $message['successful_payment'] ) && is_array( $message['successful_payment'] ) ? $message['successful_payment'] : [];
    $payload = isset( $payment['invoice_payload'] ) ? (string) $payment['invoice_payload'] : '';
    $parsed = tonbankcard_api_premium_parse_invoice_payload( $payload, $settings );
    if ( null === $parsed ) {
        return tonbankcard_api_telegram_bot_update_error( 'Premium payment invoice payload is invalid.', [ 'field' => 'message.successful_payment.invoice_payload' ] );
    }

    if ( 'XTR' !== ( isset( $payment['currency'] ) ? (string) $payment['currency'] : '' ) ) {
        return tonbankcard_api_telegram_bot_update_error( 'Premium payment must use Telegram Stars.', [ 'field' => 'message.successful_payment.currency' ] );
    }

    $plan = $settings['plans'][ $parsed['plan_code'] ];
    if ( (int) $payment['total_amount'] !== (int) $plan['price_stars'] ) {
        return tonbankcard_api_telegram_bot_update_error( 'Premium payment amount does not match the selected plan.', [ 'field' => 'message.successful_payment.total_amount' ] );
    }

    $from = isset( $message['from'] ) && is_array( $message['from'] ) ? $message['from'] : [];
    $telegram_user_id = isset( $from['id'] ) ? (int) $from['id'] : (int) $parsed['telegram_user_id'];
    if ( $telegram_user_id !== (int) $parsed['telegram_user_id'] ) {
        return tonbankcard_api_telegram_bot_update_error( 'Premium payment user does not match the invoice.', [ 'field' => 'message.from.id' ] );
    }
    $payment['telegram_user_id'] = $telegram_user_id;

    // A real Telegram Stars charge always carries telegram_payment_charge_id.
    // Refusing to fulfill without it stops forged webhooks that merely echo a
    // signed invoice payload with no money actually moved.
    $telegram_charge_id = isset( $payment['telegram_payment_charge_id'] ) ? trim( (string) $payment['telegram_payment_charge_id'] ) : '';
    if ( '' === $telegram_charge_id ) {
        return tonbankcard_api_telegram_bot_update_error(
            'Premium payment is missing the Telegram charge identifier.',
            [ 'field' => 'message.successful_payment.telegram_payment_charge_id' ]
        );
    }

    $premium_config = isset( $config['premium'] ) && is_array( $config['premium'] ) ? $config['premium'] : [];

    if ( isset( $premium_config['entitlement_writer'] ) && is_callable( $premium_config['entitlement_writer'] ) ) {
        if ( tonbankcard_api_premium_payment_replayed( $payload, $payment, $runtime, $config, null ) ) {
            return tonbankcard_api_premium_payment_replay_ack( $message, $telegram_user_id );
        }

        $entitlement = call_user_func( $premium_config['entitlement_writer'], $message, $payment, $plan, $settings, $parsed );
    } else {
        $database_error = null;
        $pdo = tonbankcard_api_session_database_connection( $runtime, $database_error );
        if ( null === $pdo ) {
            return tonbankcard_api_premium_payment_storage_error( [ 'storage' => 'mysql' ] );
        }

        try {
            $pdo->beginTransaction();
            $user_id = tonbankcard_api_premium_ensure_telegram_user( $pdo, $telegram_user_id, isset( $from['language_code'] ) ? (string) $from['language_code'] : null, ! empty( $from['is_premium'] ) );
            if ( (int) $parsed['user_id'] > 0 ) {
                $user_id = (int) $parsed['user_id'];
            }

            if ( tonbankcard_api_premium_payment_replayed( $payload, $payment, $runtime, $config, $pdo ) ) {
                $pdo->rollBack();
                return tonbankcard_api_premium_payment_replay_ack( $message, $telegram_user_id );
            }

            $reservation = tonbankcard_api_premium_reserve_payment_event( $pdo, $user_id, $plan, $payload, $payment );
            if ( ! empty( $reservation['replayed'] ) ) {
                $pdo->rollBack();
                return tonbankcard_api_premium_payment_replay_ack( $message, $telegram_user_id );
            }
            if ( empty( $reservation['ok'] ) ) {
                $pdo->rollBack();
                return tonbankcard_api_premium_payment_storage_error( isset( $reservation['details'] ) ? $reservation['details'] : [] );
            }

            $entitlement = tonbankcard_api_premium_grant_entitlement( $pdo, $user_id, $plan, $payment, $settings );
            tonbankcard_api_premium_complete_payment_event(
                $pdo,
                (int) $reservation['id'],
                isset( $entitlement['id'] ) ? (int) $entitlement['id'] : null,
                ! empty( $entitlement['renewed'] ) ? 'subscription_renewed' : 'payment_succeeded'
            );
            $pdo->commit();
        } catch ( Throwable $exception ) {
            if ( $pdo->inTransaction() ) {
                $pdo->rollBack();
            }

            return tonbankcard_api_premium_payment_storage_error( [ 'error' => 'premium_payment_transaction_failed' ] );
        }
    }

    $chat = isset( $message['chat'] ) && is_array( $message['chat'] ) ? $message['chat'] : [];

    return [
        'ok'      => TRUE,
        'payload' => [
            'method'                  => 'sendMessage',
            'chat_id'                 => isset( $chat['id'] ) ? $chat['id'] : $telegram_user_id,
            'text'                    => 'Premium is active. Your TONBANKCARD limits have been upgraded.',
            'disable_web_page_preview' => TRUE,
        ],
        'entitlement' => $entitlement,
    ];
}

/**
 * Returns TRUE when a Stars charge has already been redeemed.
 *
 * The webhook is never treated as proof of payment on its own: each successful
 * payment is keyed by its (unique) telegram_payment_charge_id. A duplicate
 * delivery or a replayed forged webhook echoing an already-redeemed charge is
 * rejected before any entitlement is granted or extended.
 *
 * When a `payment_replay_lookup` callable is configured it is used instead of
 * the database (tests, alternative stores). Otherwise the audit trail in
 * premium_payment_events is consulted.
 *
 * @param string $payload
 * @param array $payment
 * @param array $runtime
 * @param array $config
 * @param PDO|null $pdo
 * @return bool
 */
function tonbankcard_api_premium_payment_replayed( string $payload, array $payment, array $runtime, array $config, $pdo = null ) {
    $premium_config = isset( $config['premium'] ) && is_array( $config['premium'] ) ? $config['premium'] : [];
    if ( isset( $premium_config['payment_replay_lookup'] ) && is_callable( $premium_config['payment_replay_lookup'] ) ) {
        return (bool) call_user_func( $premium_config['payment_replay_lookup'], $payload, $payment );
    }

    if ( ! ( $pdo instanceof PDO ) ) {
        $database_error = null;
        $pdo = tonbankcard_api_session_database_connection( $runtime, $database_error );
        if ( null === $pdo ) {
            return FALSE;
        }
    }

    $telegram_charge_hash = tonbankcard_api_premium_hash_nullable( isset( $payment['telegram_payment_charge_id'] ) ? $payment['telegram_payment_charge_id'] : null );
    if ( null === $telegram_charge_hash ) {
        return FALSE;
    }

    try {
        $select = $pdo->prepare(
            "SELECT id
             FROM premium_payment_events
             WHERE event_type IN ('payment_succeeded', 'subscription_renewed')
               AND telegram_payment_charge_id_hash = :telegram_charge_hash
             LIMIT 1"
        );
        $select->execute( [ ':telegram_charge_hash' => $telegram_charge_hash ] );

        return FALSE !== $select->fetchColumn();
    } catch ( Throwable $exception ) {
        return FALSE;
    }
}

/**
 * Builds an idempotent acknowledgement for an already-redeemed Stars payment.
 *
 * @param array $message
 * @param int $telegram_user_id
 * @return array
 */
function tonbankcard_api_premium_payment_replay_ack( array $message, int $telegram_user_id ) {
    $chat = isset( $message['chat'] ) && is_array( $message['chat'] ) ? $message['chat'] : [];

    return [
        'ok'       => TRUE,
        'replayed' => TRUE,
        'payload'  => [
            'method'                   => 'sendMessage',
            'chat_id'                  => isset( $chat['id'] ) ? $chat['id'] : $telegram_user_id,
            'text'                     => 'Premium is already active. This payment was already processed.',
            'disable_web_page_preview' => TRUE,
        ],
    ];
}

/**
 * Returns a storage error for failed premium fulfillment.
 *
 * @param array $details
 * @return array
 */
function tonbankcard_api_premium_payment_storage_error( array $details = [] ) {
    return [
        'ok'      => FALSE,
        'status'  => 503,
        'code'    => 'premium_storage_unavailable',
        'message' => 'Premium payment fulfillment requires the configured MySQL session store.',
        'details' => empty( $details ) ? [ 'storage' => 'mysql' ] : $details,
    ];
}

/**
 * Atomically reserves a Telegram Stars charge before granting premium.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param array $plan
 * @param string $payload
 * @param array $payment
 * @return array
 */
function tonbankcard_api_premium_reserve_payment_event( PDO $pdo, int $user_id, array $plan, string $payload, array $payment ) {
    $telegram_charge_hash = tonbankcard_api_premium_hash_nullable( isset( $payment['telegram_payment_charge_id'] ) ? $payment['telegram_payment_charge_id'] : null );
    if ( null === $telegram_charge_hash ) {
        return [ 'ok' => FALSE, 'details' => [ 'field' => 'telegram_payment_charge_id' ] ];
    }

    try {
        $insert = $pdo->prepare(
            "INSERT INTO premium_payment_events
                (user_id, entitlement_id, provider, event_type, plan_code, invoice_payload_hash,
                 telegram_payment_charge_id_hash, provider_payment_charge_id_hash, amount_stars,
                 currency, event_at, metadata_json, created_at)
             VALUES
                (:user_id, NULL, 'telegram_stars', 'payment_succeeded', :plan_code, :invoice_payload_hash,
                 :telegram_payment_charge_id_hash, :provider_payment_charge_id_hash, :amount_stars,
                 'XTR', UTC_TIMESTAMP(6), :metadata_json, UTC_TIMESTAMP(6))"
        );
        $insert->execute(
            [
                ':user_id'                         => $user_id,
                ':plan_code'                       => isset( $plan['code'] ) ? $plan['code'] : null,
                ':invoice_payload_hash'            => hash( 'sha256', $payload ),
                ':telegram_payment_charge_id_hash' => $telegram_charge_hash,
                ':provider_payment_charge_id_hash' => tonbankcard_api_premium_hash_nullable( isset( $payment['provider_payment_charge_id'] ) ? $payment['provider_payment_charge_id'] : null ),
                ':amount_stars'                    => isset( $payment['total_amount'] ) ? (int) $payment['total_amount'] : null,
                ':metadata_json'                   => json_encode( [ 'state' => 'reserved' ] ),
            ]
        );

        return [ 'ok' => TRUE, 'id' => (int) $pdo->lastInsertId() ];
    } catch ( PDOException $exception ) {
        if ( '23000' === $exception->getCode() ) {
            return [ 'ok' => TRUE, 'replayed' => TRUE ];
        }

        return [ 'ok' => FALSE, 'details' => [ 'error' => 'premium_payment_event_reserve_failed' ] ];
    } catch ( Throwable $exception ) {
        return [ 'ok' => FALSE, 'details' => [ 'error' => 'premium_payment_event_reserve_failed' ] ];
    }
}

/**
 * Completes the reserved payment event after entitlement fulfillment.
 *
 * @param PDO $pdo
 * @param int $event_id
 * @param int|null $entitlement_id
 * @param string $fulfilled_event_type
 * @return void
 */
function tonbankcard_api_premium_complete_payment_event( PDO $pdo, int $event_id, $entitlement_id, string $fulfilled_event_type ) {
    $update = $pdo->prepare(
        "UPDATE premium_payment_events
         SET entitlement_id = :entitlement_id,
             metadata_json = :metadata_json
         WHERE id = :id"
    );
    $update->execute(
        [
            ':entitlement_id' => $entitlement_id,
            ':metadata_json'  => json_encode(
                [
                    'state'                => 'fulfilled',
                    'fulfilled_event_type' => $fulfilled_event_type,
                ]
            ),
            ':id'             => $event_id,
        ]
    );
}

/**
 * Handles a Telegram refunded_payment message update.
 *
 * @param array $message
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @return array
 */
function tonbankcard_api_premium_refunded_payment_response( array $message, array $runtime, array $config, string $request_id ) {
    $settings = tonbankcard_api_premium_settings( $runtime, $config );
    $payment = isset( $message['refunded_payment'] ) && is_array( $message['refunded_payment'] ) ? $message['refunded_payment'] : [];
    $payload = isset( $payment['invoice_payload'] ) ? (string) $payment['invoice_payload'] : '';
    $parsed = tonbankcard_api_premium_parse_invoice_payload( $payload, $settings );
    if ( null === $parsed ) {
        return tonbankcard_api_telegram_bot_update_error( 'Premium refund invoice payload is invalid.', [ 'field' => 'message.refunded_payment.invoice_payload' ] );
    }

    $database_error = null;
    $pdo = tonbankcard_api_session_database_connection( $runtime, $database_error );
    if ( null === $pdo ) {
        return [
            'ok'      => FALSE,
            'status'  => 503,
            'code'    => 'premium_storage_unavailable',
            'message' => 'Premium refund handling requires the configured MySQL session store.',
            'details' => [ 'storage' => 'mysql' ],
        ];
    }

    $revoked = tonbankcard_api_premium_revoke_by_payment( $pdo, (int) $parsed['user_id'], $payment, 'refund' );
    tonbankcard_api_premium_record_event(
        $pdo,
        [
            'user_id'                          => (int) $parsed['user_id'],
            'event_type'                       => 'payment_refunded',
            'plan_code'                        => $parsed['plan_code'],
            'invoice_payload_hash'             => hash( 'sha256', $payload ),
            'telegram_payment_charge_id_hash'  => tonbankcard_api_premium_hash_nullable( isset( $payment['telegram_payment_charge_id'] ) ? $payment['telegram_payment_charge_id'] : null ),
            'provider_payment_charge_id_hash'  => tonbankcard_api_premium_hash_nullable( isset( $payment['provider_payment_charge_id'] ) ? $payment['provider_payment_charge_id'] : null ),
            'amount_stars'                     => isset( $payment['total_amount'] ) ? (int) $payment['total_amount'] : null,
            'currency'                         => isset( $payment['currency'] ) ? (string) $payment['currency'] : 'XTR',
        ]
    );

    $chat = isset( $message['chat'] ) && is_array( $message['chat'] ) ? $message['chat'] : [];

    return [
        'ok'      => TRUE,
        'payload' => [
            'method'                  => 'sendMessage',
            'chat_id'                 => isset( $chat['id'] ) ? $chat['id'] : $parsed['telegram_user_id'],
            'text'                    => $revoked ? 'Premium refund recorded. Your account has returned to Free limits.' : 'Premium refund recorded.',
            'disable_web_page_preview' => TRUE,
        ],
    ];
}

/**
 * Grants or renews a premium entitlement from a successful Stars payment.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param array $plan
 * @param array $payment
 * @param array $settings
 * @return array
 */
function tonbankcard_api_premium_grant_entitlement( PDO $pdo, int $user_id, array $plan, array $payment, array $settings ) {
    $now = tonbankcard_api_premium_mysql_datetime();
    $duration = isset( $plan['duration_seconds'] ) ? (int) $plan['duration_seconds'] : (int) $settings['subscription_period_seconds'];
    $select = $pdo->prepare(
        "SELECT *
         FROM premium_entitlements
         WHERE user_id = :user_id
           AND plan_code = :plan_code
           AND status IN ('trialing', 'active', 'past_due')
         ORDER BY expires_at DESC, id DESC
         LIMIT 1"
    );
    $select->execute(
        [
            ':user_id'   => $user_id,
            ':plan_code' => $plan['code'],
        ]
    );
    $existing = $select->fetch();
    $base_timestamp = time();
    if ( is_array( $existing ) && ! empty( $existing['expires_at'] ) ) {
        $existing_timestamp = strtotime( $existing['expires_at'] . ' UTC' );
        if ( FALSE !== $existing_timestamp && $existing_timestamp > $base_timestamp ) {
            $base_timestamp = $existing_timestamp;
        }
    }
    $expires_at = tonbankcard_api_premium_mysql_datetime( $base_timestamp + $duration );

    $customer_hash = tonbankcard_api_premium_hash_nullable( 'telegram_user:' . ( isset( $payment['telegram_user_id'] ) ? $payment['telegram_user_id'] : $user_id ) );
    $telegram_charge_id = isset( $payment['telegram_payment_charge_id'] ) ? substr( trim( (string) $payment['telegram_payment_charge_id'] ), 0, 180 ) : '';
    $provider_charge_id = isset( $payment['provider_payment_charge_id'] ) ? substr( trim( (string) $payment['provider_payment_charge_id'] ), 0, 180 ) : '';
    $telegram_charge_hash = tonbankcard_api_premium_hash_nullable( isset( $payment['telegram_payment_charge_id'] ) ? $payment['telegram_payment_charge_id'] : null );
    $provider_charge_hash = tonbankcard_api_premium_hash_nullable( isset( $payment['provider_payment_charge_id'] ) ? $payment['provider_payment_charge_id'] : null );
    $subscription_hash = $telegram_charge_hash ? $telegram_charge_hash : $provider_charge_hash;

    if ( is_array( $existing ) && ! empty( $existing['id'] ) ) {
        $update = $pdo->prepare(
            "UPDATE premium_entitlements
             SET status = 'active',
                 source = 'telegram_stars',
                 provider_customer_ref_hash = :provider_customer_ref_hash,
                 provider_subscription_ref_hash = :provider_subscription_ref_hash,
                 last_telegram_payment_charge_id = :last_telegram_payment_charge_id,
                 last_provider_payment_charge_id = :last_provider_payment_charge_id,
                 last_telegram_payment_charge_id_hash = :last_telegram_payment_charge_id_hash,
                 last_provider_payment_charge_id_hash = :last_provider_payment_charge_id_hash,
                 expires_at = :expires_at,
                 renewed_at = :renewed_at,
                 revoked_at = NULL,
                 cancel_at_period_end = 0,
                 canceled_at = NULL,
                 refunded_at = NULL,
                 updated_at = UTC_TIMESTAMP(6)
             WHERE id = :id"
        );
        $update->execute(
            [
                ':provider_customer_ref_hash'            => $customer_hash,
                ':provider_subscription_ref_hash'        => $subscription_hash,
                ':last_telegram_payment_charge_id'       => '' !== $telegram_charge_id ? $telegram_charge_id : null,
                ':last_provider_payment_charge_id'       => '' !== $provider_charge_id ? $provider_charge_id : null,
                ':last_telegram_payment_charge_id_hash'  => $telegram_charge_hash,
                ':last_provider_payment_charge_id_hash'  => $provider_charge_hash,
                ':expires_at'                            => $expires_at,
                ':renewed_at'                            => $now,
                ':id'                                    => (int) $existing['id'],
            ]
        );

        $existing['expires_at'] = $expires_at;
        $existing['renewed_at'] = $now;
        $existing['status'] = 'active';
        $existing['renewed'] = TRUE;
        return $existing;
    }

    $insert = $pdo->prepare(
        "INSERT INTO premium_entitlements
            (user_id, plan_code, status, source, provider_customer_ref_hash, provider_subscription_ref_hash,
             last_telegram_payment_charge_id, last_provider_payment_charge_id,
             last_telegram_payment_charge_id_hash, last_provider_payment_charge_id_hash, starts_at, expires_at, renewed_at,
             cancel_at_period_end, created_at, updated_at)
         VALUES
            (:user_id, :plan_code, 'active', 'telegram_stars', :provider_customer_ref_hash, :provider_subscription_ref_hash,
             :last_telegram_payment_charge_id, :last_provider_payment_charge_id,
             :last_telegram_payment_charge_id_hash, :last_provider_payment_charge_id_hash, :starts_at, :expires_at, NULL,
             0, UTC_TIMESTAMP(6), UTC_TIMESTAMP(6))"
    );
    $insert->execute(
        [
            ':user_id'                               => $user_id,
            ':plan_code'                             => $plan['code'],
            ':provider_customer_ref_hash'            => $customer_hash,
            ':provider_subscription_ref_hash'        => $subscription_hash,
            ':last_telegram_payment_charge_id'       => '' !== $telegram_charge_id ? $telegram_charge_id : null,
            ':last_provider_payment_charge_id'       => '' !== $provider_charge_id ? $provider_charge_id : null,
            ':last_telegram_payment_charge_id_hash'  => $telegram_charge_hash,
            ':last_provider_payment_charge_id_hash'  => $provider_charge_hash,
            ':starts_at'                             => $now,
            ':expires_at'                            => $expires_at,
        ]
    );

    return [
        'id'         => (int) $pdo->lastInsertId(),
        'user_id'    => $user_id,
        'plan_code'  => $plan['code'],
        'status'     => 'active',
        'source'     => 'telegram_stars',
        'starts_at'  => $now,
        'expires_at' => $expires_at,
        'renewed'    => FALSE,
    ];
}

/**
 * Marks the current active entitlement as canceled at period end.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @return array|null
 */
function tonbankcard_api_premium_cancel_active_entitlement( PDO $pdo, int $user_id ) {
    $select = $pdo->prepare(
        "SELECT *
         FROM premium_entitlements
         WHERE user_id = :user_id
           AND status IN ('trialing', 'active')
           AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP(6))
         ORDER BY expires_at DESC, id DESC
         LIMIT 1"
    );
    $select->execute( [ ':user_id' => $user_id ] );
    $row = $select->fetch();
    if ( ! is_array( $row ) || empty( $row['id'] ) ) {
        return null;
    }

    $update = $pdo->prepare(
        "UPDATE premium_entitlements
         SET cancel_at_period_end = 1,
             canceled_at = UTC_TIMESTAMP(6),
             updated_at = UTC_TIMESTAMP(6)
         WHERE id = :id"
    );
    $update->execute( [ ':id' => (int) $row['id'] ] );
    $row['cancel_at_period_end'] = 1;
    $row['canceled_at'] = tonbankcard_api_premium_mysql_datetime();

    return $row;
}

/**
 * Revokes a premium entitlement by payment identifiers.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param array $payment
 * @param string $reason
 * @return bool
 */
function tonbankcard_api_premium_revoke_by_payment( PDO $pdo, int $user_id, array $payment, string $reason ) {
    $telegram_charge_hash = tonbankcard_api_premium_hash_nullable( isset( $payment['telegram_payment_charge_id'] ) ? $payment['telegram_payment_charge_id'] : null );
    $provider_charge_hash = tonbankcard_api_premium_hash_nullable( isset( $payment['provider_payment_charge_id'] ) ? $payment['provider_payment_charge_id'] : null );

    $clauses = [ 'user_id = :user_id', "status IN ('trialing', 'active', 'past_due')" ];
    $params = [ ':user_id' => $user_id ];
    if ( null !== $telegram_charge_hash ) {
        $clauses[] = 'last_telegram_payment_charge_id_hash = :telegram_charge_hash';
        $params[':telegram_charge_hash'] = $telegram_charge_hash;
    }
    if ( null !== $provider_charge_hash ) {
        $clauses[] = 'last_provider_payment_charge_id_hash = :provider_charge_hash';
        $params[':provider_charge_hash'] = $provider_charge_hash;
    }
    if ( 2 === count( $clauses ) ) {
        return FALSE;
    }

    $update = $pdo->prepare(
        "UPDATE premium_entitlements
         SET status = 'revoked',
             revoked_at = UTC_TIMESTAMP(6),
             refunded_at = UTC_TIMESTAMP(6),
             updated_at = UTC_TIMESTAMP(6)
         WHERE " . implode( ' AND ', $clauses )
    );
    $update->execute( $params );

    return 0 < $update->rowCount();
}

/**
 * Ensures a Telegram user row exists and returns the internal user id.
 *
 * @param PDO $pdo
 * @param int $telegram_user_id
 * @param string|null $language_code
 * @param bool $telegram_is_premium
 * @return int
 */
function tonbankcard_api_premium_ensure_telegram_user( PDO $pdo, int $telegram_user_id, $language_code = null, bool $telegram_is_premium = FALSE ) {
    $upsert = $pdo->prepare(
        'INSERT INTO users (telegram_user_id, telegram_language_code, telegram_is_premium, last_seen_at)
         VALUES (:telegram_user_id, :telegram_language_code, :telegram_is_premium, UTC_TIMESTAMP(6))
         ON DUPLICATE KEY UPDATE
            telegram_language_code = VALUES(telegram_language_code),
            telegram_is_premium = VALUES(telegram_is_premium),
            last_seen_at = UTC_TIMESTAMP(6)'
    );
    $upsert->execute(
        [
            ':telegram_user_id'       => $telegram_user_id,
            ':telegram_language_code' => $language_code,
            ':telegram_is_premium'    => $telegram_is_premium ? 1 : 0,
        ]
    );

    $select = $pdo->prepare( 'SELECT id FROM users WHERE telegram_user_id = :telegram_user_id LIMIT 1' );
    $select->execute( [ ':telegram_user_id' => $telegram_user_id ] );
    $row = $select->fetch();

    return is_array( $row ) && ! empty( $row['id'] ) ? (int) $row['id'] : 0;
}

/**
 * Loads a trusted Telegram session and its database connection.
 *
 * @param array $request
 * @param array $runtime
 * @return array
 */
function tonbankcard_api_premium_trusted_session( array $request, array $runtime ) {
    $token = tonbankcard_api_premium_session_token( $request );
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
        "SELECT user_sessions.id, user_sessions.user_id, users.telegram_user_id
         FROM user_sessions
         INNER JOIN users ON users.id = user_sessions.user_id
         WHERE user_sessions.session_token_hash = :session_token_hash
           AND user_sessions.trust_state = 'telegram_validated'
           AND user_sessions.user_id IS NOT NULL
           AND user_sessions.revoked_at IS NULL
           AND user_sessions.expires_at > UTC_TIMESTAMP(6)
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
        'ok'               => TRUE,
        'pdo'              => $pdo,
        'user_id'          => (int) $row['user_id'],
        'telegram_user_id' => isset( $row['telegram_user_id'] ) ? (int) $row['telegram_user_id'] : null,
    ];
}

/**
 * Reads a session token from cookie, bearer auth, or the trusted session header.
 *
 * @param array $request
 * @return string|null
 */
function tonbankcard_api_premium_session_token( array $request ) {
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
 * Writes a coarse premium payment event when the audit table exists.
 *
 * @param PDO $pdo
 * @param array $event
 * @return void
 */
function tonbankcard_api_premium_record_event( PDO $pdo, array $event ) {
    try {
        $insert = $pdo->prepare(
            "INSERT INTO premium_payment_events
                (user_id, entitlement_id, provider, event_type, plan_code, invoice_payload_hash,
                 telegram_payment_charge_id_hash, provider_payment_charge_id_hash, amount_stars,
                 currency, event_at, metadata_json, created_at)
             VALUES
                (:user_id, :entitlement_id, 'telegram_stars', :event_type, :plan_code, :invoice_payload_hash,
                 :telegram_payment_charge_id_hash, :provider_payment_charge_id_hash, :amount_stars,
                 :currency, UTC_TIMESTAMP(6), :metadata_json, UTC_TIMESTAMP(6))"
        );
        $insert->execute(
            [
                ':user_id'                         => isset( $event['user_id'] ) ? $event['user_id'] : null,
                ':entitlement_id'                  => isset( $event['entitlement_id'] ) ? $event['entitlement_id'] : null,
                ':event_type'                      => $event['event_type'],
                ':plan_code'                       => isset( $event['plan_code'] ) ? $event['plan_code'] : null,
                ':invoice_payload_hash'            => isset( $event['invoice_payload_hash'] ) ? $event['invoice_payload_hash'] : null,
                ':telegram_payment_charge_id_hash' => isset( $event['telegram_payment_charge_id_hash'] ) ? $event['telegram_payment_charge_id_hash'] : null,
                ':provider_payment_charge_id_hash' => isset( $event['provider_payment_charge_id_hash'] ) ? $event['provider_payment_charge_id_hash'] : null,
                ':amount_stars'                    => isset( $event['amount_stars'] ) ? $event['amount_stars'] : null,
                ':currency'                        => isset( $event['currency'] ) ? $event['currency'] : 'XTR',
                ':metadata_json'                   => isset( $event['metadata'] ) ? json_encode( $event['metadata'] ) : null,
            ]
        );
    } catch ( Throwable $exception ) {
        return;
    }
}

/**
 * Hashes an external identifier or returns null for missing values.
 *
 * @param mixed $value
 * @return string|null
 */
function tonbankcard_api_premium_hash_nullable( $value ) {
    if ( null === $value || '' === trim( (string) $value ) ) {
        return null;
    }

    return hash( 'sha256', (string) $value );
}

/**
 * Normalizes a premium plan code.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_premium_plan_code( $value ) {
    $value = strtolower( trim( (string) $value ) );
    $value = preg_replace( '/[^a-z0-9_-]/', '', $value );

    return is_string( $value ) ? substr( $value, 0, 80 ) : '';
}

/**
 * Formats a MySQL UTC datetime.
 *
 * @param int|null $timestamp
 * @return string
 */
function tonbankcard_api_premium_mysql_datetime( $timestamp = null ) {
    $timestamp = null === $timestamp ? time() : (int) $timestamp;
    return gmdate( 'Y-m-d H:i:s', $timestamp );
}

/**
 * Converts a MySQL UTC datetime to ISO-8601.
 *
 * @param mixed $value
 * @return string|null
 */
function tonbankcard_api_premium_iso_datetime( $value ) {
    if ( null === $value || '' === trim( (string) $value ) ) {
        return null;
    }

    $timestamp = strtotime( (string) $value . ' UTC' );
    if ( FALSE === $timestamp ) {
        return null;
    }

    return gmdate( 'Y-m-d\TH:i:s\Z', $timestamp );
}
