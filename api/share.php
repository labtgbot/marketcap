<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 SHARE AND REFERRAL API
 * -------------------------------------------------------------------------
 * Telegram-safe startapp payloads plus first-touch referral attribution after
 * trusted Telegram session validation.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Returns TRUE when the path belongs to the share API.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_share_is_request( string $path ) {
    $path = tonbankcard_api_normalize_path( $path );
    return '/api/share/resolve' === $path;
}

/**
 * Returns documented share API routes.
 *
 * @return array
 */
function tonbankcard_api_share_routes() {
    return [
        '/api/share/resolve',
    ];
}

/**
 * Handles share API requests.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_share_handle( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $path = tonbankcard_api_normalize_path( $request['path'] );
    $method = strtoupper( $request['method'] );

    if ( '/api/share/resolve' !== $path ) {
        return tonbankcard_api_error_response(
            404,
            'not_found',
            'No share API route matches the request.',
            [ 'path' => $path ],
            $request_id,
            $headers
        );
    }

    if ( ! in_array( $method, [ 'GET', 'POST' ], TRUE ) ) {
        return tonbankcard_api_method_not_allowed_response( [ 'GET', 'POST', 'OPTIONS' ], $request_id, $headers );
    }

    $payload = 'POST' === $method ? tonbankcard_api_json_body( $request ) : [];
    $start_param = '';
    if ( 'GET' === $method && isset( $request['query']['start_param'] ) ) {
        $start_param = trim( (string) $request['query']['start_param'] );
    } elseif ( isset( $payload['start_param'] ) ) {
        $start_param = trim( (string) $payload['start_param'] );
    } elseif ( isset( $payload['startParam'] ) ) {
        $start_param = trim( (string) $payload['startParam'] );
    }

    if ( '' === $start_param ) {
        return tonbankcard_api_error_response(
            400,
            'missing_start_param',
            'A Telegram startapp payload is required.',
            [ 'field' => 'start_param' ],
            $request_id,
            $headers
        );
    }

    $parsed = tonbankcard_api_share_parse_start_param( $start_param );
    if ( empty( $parsed['ok'] ) ) {
        return tonbankcard_api_error_response(
            isset( $parsed['status'] ) ? (int) $parsed['status'] : 400,
            isset( $parsed['code'] ) ? $parsed['code'] : 'invalid_start_param',
            isset( $parsed['message'] ) ? $parsed['message'] : 'The Telegram startapp payload is invalid.',
            isset( $parsed['details'] ) && is_array( $parsed['details'] ) ? $parsed['details'] : [],
            $request_id,
            $headers
        );
    }

    return tonbankcard_api_success_response(
        [
            'start_param'  => $start_param,
            'share'        => array_merge(
                $parsed['payload'],
                [
                    'payload_hash' => $parsed['payload_hash'],
                ]
            ),
            'resolved_at'  => tonbankcard_api_iso_datetime( time() ),
        ],
        $request_id,
        $headers
    );
}

/**
 * Builds a Telegram-safe startapp payload from route/campaign/inviter/context.
 *
 * @param array $payload
 * @return string
 */
function tonbankcard_api_share_build_start_param( array $payload ) {
    $compact = [
        'route'    => isset( $payload['route'] ) ? (string) $payload['route'] : '/',
        'campaign' => isset( $payload['campaign'] ) ? (string) $payload['campaign'] : 'organic-share',
        'inviter'  => isset( $payload['inviter'] ) ? (string) $payload['inviter'] : null,
        'context'  => isset( $payload['context'] ) ? (string) $payload['context'] : 'shared_view',
    ];

    return 's_' . tonbankcard_api_share_base64_url_encode(
        json_encode( $compact, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE )
    );
}

/**
 * Parses a Telegram startapp payload and returns normalized share metadata.
 *
 * @param string $start_param
 * @return array
 */
function tonbankcard_api_share_parse_start_param( string $start_param ) {
    $start_param = trim( $start_param );
    if ( '' === $start_param ) {
        return tonbankcard_api_share_error( 400, 'missing_start_param', 'The Telegram startapp payload is empty.', [ 'field' => 'start_param' ] );
    }

    if ( strlen( $start_param ) > 512 ) {
        return tonbankcard_api_share_error( 400, 'start_param_too_large', 'The Telegram startapp payload exceeds Telegram limits.', [ 'max_bytes' => 512 ] );
    }

    if ( 0 !== strpos( $start_param, 's_' ) ) {
        return tonbankcard_api_share_error( 400, 'invalid_start_param', 'The Telegram startapp payload has an unsupported prefix.', [] );
    }

    if ( ! preg_match( '/^s_[A-Za-z0-9_-]+$/', $start_param ) ) {
        return tonbankcard_api_share_error( 400, 'invalid_start_param', 'The Telegram startapp payload contains unsupported characters.', [] );
    }

    $encoded = substr( $start_param, 2 );
    $json = tonbankcard_api_share_base64_url_decode( $encoded );
    if ( null === $json || '' === $json ) {
        return tonbankcard_api_share_error( 400, 'invalid_start_param', 'The Telegram startapp payload could not be decoded.', [] );
    }

    $decoded = json_decode( $json, TRUE );
    if ( ! is_array( $decoded ) ) {
        return tonbankcard_api_share_error( 400, 'invalid_start_param', 'The Telegram startapp payload is not valid JSON.', [] );
    }

    $normalized = tonbankcard_api_share_normalize_payload( $decoded );
    if ( empty( $normalized['ok'] ) ) {
        return $normalized;
    }

    return [
        'ok'           => TRUE,
        'payload'      => $normalized['payload'],
        'payload_hash' => hash( 'sha256', $json ),
    ];
}

/**
 * Records referral attribution from a trusted Telegram session start_param.
 *
 * @param array $session
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_share_record_referral_attribution( array $session, array $runtime, array $config ) {
    if ( empty( $runtime['feature_flags']['referrals'] ) ) {
        return [
            'ok'     => FALSE,
            'reason' => 'referrals_disabled',
        ];
    }

    if ( 'telegram_validated' !== $session['trust_state'] || empty( $session['telegram_user_id'] ) || empty( $session['start_param'] ) ) {
        return [
            'ok'     => FALSE,
            'reason' => 'not_attributable',
        ];
    }

    $parsed = tonbankcard_api_share_parse_start_param( (string) $session['start_param'] );
    if ( empty( $parsed['ok'] ) ) {
        return [
            'ok'     => FALSE,
            'reason' => isset( $parsed['code'] ) ? $parsed['code'] : 'invalid_start_param',
        ];
    }

    $payload = $parsed['payload'];
    $inviter_telegram_id = tonbankcard_api_share_inviter_telegram_user_id( $payload['inviter'] );
    if ( null === $inviter_telegram_id || (string) $inviter_telegram_id === (string) $session['telegram_user_id'] ) {
        return [
            'ok'     => FALSE,
            'reason' => null === $inviter_telegram_id ? 'missing_inviter' : 'self_referral',
        ];
    }

    $share = [
        'payload'             => $payload,
        'payload_hash'        => $parsed['payload_hash'],
        'inviter_telegram_id' => $inviter_telegram_id,
    ];

    $database_error = null;
    $pdo = tonbankcard_api_session_database_connection( $runtime, $database_error );
    if ( null !== $pdo ) {
        try {
            return tonbankcard_api_share_record_database_attribution( $pdo, $session, $share );
        } catch ( Throwable $error ) {
            $database_error = 'Referral attribution database write failed.';
        }
    }

    if ( tonbankcard_api_local_browser_fallback_allowed( $runtime ) ) {
        return tonbankcard_api_share_record_local_attribution( $session, $share, tonbankcard_api_telegram_session_settings( $config ) );
    }

    return [
        'ok'      => FALSE,
        'reason'  => 'storage_unavailable',
        'storage' => 'database',
        'error'   => $database_error,
    ];
}

/**
 * Persists referral attribution in MySQL/MariaDB.
 *
 * @param PDO $pdo
 * @param array $session
 * @param array $share
 * @return array
 */
function tonbankcard_api_share_record_database_attribution( PDO $pdo, array $session, array $share ) {
    $now = tonbankcard_api_mysql_datetime( time() );
    $payload = $share['payload'];

    $pdo->beginTransaction();
    try {
        $referred_user_id = tonbankcard_api_share_select_user_id( $pdo, $session['telegram_user_id'] );
        if ( null === $referred_user_id ) {
            throw new RuntimeException( 'Referred user row is unavailable.' );
        }

        $inviter_user_id = tonbankcard_api_share_upsert_referral_user( $pdo, $share['inviter_telegram_id'], $now );
        if ( null === $inviter_user_id || (int) $inviter_user_id === (int) $referred_user_id ) {
            $pdo->commit();
            return [
                'ok'     => FALSE,
                'reason' => 'self_referral',
            ];
        }

        $campaign_id = tonbankcard_api_share_upsert_campaign( $pdo, $payload['campaign'], $payload['context'], $now );

        $insert = $pdo->prepare(
            'INSERT INTO referral_attributions (
                referred_user_id,
                inviter_user_id,
                campaign_id,
                payload_hash,
                landing_route,
                attribution_model,
                attributed_at
             )
             VALUES (
                :referred_user_id,
                :inviter_user_id,
                :campaign_id,
                :payload_hash,
                :landing_route,
                :attribution_model,
                :attributed_at
             )
             ON DUPLICATE KEY UPDATE
                payload_hash = payload_hash'
        );
        $insert->execute(
            [
                ':referred_user_id'  => $referred_user_id,
                ':inviter_user_id'   => $inviter_user_id,
                ':campaign_id'       => $campaign_id,
                ':payload_hash'      => $share['payload_hash'],
                ':landing_route'     => $payload['route'],
                ':attribution_model' => 'first_touch',
                ':attributed_at'     => $now,
            ]
        );

        $affected = $insert->rowCount();
        $pdo->commit();

        return [
            'ok'       => TRUE,
            'storage'  => 'database',
            'recorded' => $affected > 0,
            'campaign' => $payload['campaign'],
        ];
    } catch ( Throwable $error ) {
        if ( $pdo->inTransaction() ) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

/**
 * Persists referral attribution in the local session store.
 *
 * @param array $session
 * @param array $share
 * @param array $settings
 * @return array
 */
function tonbankcard_api_share_record_local_attribution( array $session, array $share, array $settings ) {
    $path = $settings['local_session_store_path'];
    $dir = dirname( $path );
    if ( ! is_dir( $dir ) && ! mkdir( $dir, 0700, TRUE ) ) {
        return [
            'ok'      => FALSE,
            'storage' => 'local_file',
        ];
    }

    $store = [
        'users'                 => [],
        'sessions'              => [],
        'referral_campaigns'    => [],
        'referral_attributions' => [],
    ];
    if ( is_readable( $path ) ) {
        $decoded = json_decode( (string) file_get_contents( $path ), TRUE );
        if ( is_array( $decoded ) ) {
            $store = array_merge( $store, $decoded );
        }
    }
    if ( ! isset( $store['referral_campaigns'] ) || ! is_array( $store['referral_campaigns'] ) ) {
        $store['referral_campaigns'] = [];
    }
    if ( ! isset( $store['referral_attributions'] ) || ! is_array( $store['referral_attributions'] ) ) {
        $store['referral_attributions'] = [];
    }

    $payload = $share['payload'];
    $campaign_code = $payload['campaign'];
    if ( ! isset( $store['referral_campaigns'][ $campaign_code ] ) ) {
        $store['referral_campaigns'][ $campaign_code ] = [
            'code'       => $campaign_code,
            'name'       => tonbankcard_api_share_campaign_name( $campaign_code, $payload['context'] ),
            'source'     => $payload['context'],
            'status'     => 'active',
            'created_at' => tonbankcard_api_iso_datetime( time() ),
        ];
    }

    $key = tonbankcard_api_share_local_attribution_key( $session['telegram_user_id'], $campaign_code );
    if ( ! isset( $store['referral_attributions'][ $key ] ) ) {
        $store['referral_attributions'][ $key ] = [
            'referred_telegram_user_id' => (string) $session['telegram_user_id'],
            'inviter_telegram_user_id'  => (string) $share['inviter_telegram_id'],
            'campaign_code'             => $campaign_code,
            'payload_hash'              => $share['payload_hash'],
            'landing_route'             => $payload['route'],
            'context'                   => $payload['context'],
            'attribution_model'         => 'first_touch',
            'attributed_at'             => tonbankcard_api_iso_datetime( time() ),
        ];
    }

    $written = file_put_contents( $path, json_encode( $store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE ), LOCK_EX );
    if ( FALSE === $written ) {
        return [
            'ok'      => FALSE,
            'storage' => 'local_file',
        ];
    }
    @chmod( $path, 0600 );

    return [
        'ok'       => TRUE,
        'storage'  => 'local_file',
        'recorded' => isset( $store['referral_attributions'][ $key ] ),
        'campaign' => $campaign_code,
    ];
}

/**
 * Normalizes and validates decoded share payload fields.
 *
 * @param array $decoded
 * @return array
 */
function tonbankcard_api_share_normalize_payload( array $decoded ) {
    $route = isset( $decoded['route'] ) ? trim( (string) $decoded['route'] ) : '';
    if ( ! tonbankcard_api_share_valid_route( $route ) ) {
        return tonbankcard_api_share_error( 400, 'invalid_share_route', 'The share route must be an internal application route.', [ 'field' => 'route' ] );
    }

    $campaign = tonbankcard_api_share_safe_token( isset( $decoded['campaign'] ) ? $decoded['campaign'] : '', 'organic-share', 80 );
    $context = tonbankcard_api_share_safe_token( isset( $decoded['context'] ) ? $decoded['context'] : '', 'shared_view', 80 );
    $inviter = tonbankcard_api_share_normalize_inviter( isset( $decoded['inviter'] ) ? $decoded['inviter'] : null );
    if ( FALSE === $inviter ) {
        return tonbankcard_api_share_error( 400, 'invalid_share_inviter', 'The share inviter field is invalid.', [ 'field' => 'inviter' ] );
    }

    return [
        'ok'      => TRUE,
        'payload' => [
            'route'    => $route,
            'campaign' => $campaign,
            'inviter'  => $inviter,
            'context'  => $context,
        ],
    ];
}

/**
 * Returns TRUE for relative app routes safe to open from shared links.
 *
 * @param string $route
 * @return bool
 */
function tonbankcard_api_share_valid_route( string $route ) {
    if ( '' === $route || '/' !== substr( $route, 0, 1 ) || 0 === strpos( $route, '//' ) ) {
        return FALSE;
    }

    if ( preg_match( '/^[A-Za-z][A-Za-z0-9+.-]*:|[\\\\\r\n\x00-\x1F]/', $route ) ) {
        return FALSE;
    }

    $parts = parse_url( $route );
    if ( FALSE === $parts || isset( $parts['scheme'] ) || isset( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
        return FALSE;
    }

    return strlen( $route ) <= 200 && preg_match( '/^\/[A-Za-z0-9._~\/%:-]*(?:\?[A-Za-z0-9._~%=&:+,-]*)?$/', $route ) === 1;
}

/**
 * Sanitizes short campaign/context tokens.
 *
 * @param mixed $value
 * @param string $fallback
 * @param int $max
 * @return string
 */
function tonbankcard_api_share_safe_token( $value, string $fallback, int $max ) {
    $value = strtolower( trim( (string) $value ) );
    $value = preg_replace( '/[^a-z0-9._-]+/', '-', $value );
    $value = trim( (string) $value, '.-_' );
    if ( '' === $value ) {
        $value = $fallback;
    }

    return substr( $value, 0, $max );
}

/**
 * Normalizes inviter values. FALSE means invalid; null means absent.
 *
 * @param mixed $value
 * @return string|null|false
 */
function tonbankcard_api_share_normalize_inviter( $value ) {
    if ( null === $value || '' === trim( (string) $value ) ) {
        return null;
    }

    $value = trim( (string) $value );
    if ( preg_match( '/^telegram:[1-9][0-9]{0,19}$/', $value ) ) {
        return $value;
    }

    if ( preg_match( '/^[1-9][0-9]{0,19}$/', $value ) ) {
        return 'telegram:' . $value;
    }

    return FALSE;
}

/**
 * Extracts a Telegram user id from a normalized inviter field.
 *
 * @param string|null $inviter
 * @return string|null
 */
function tonbankcard_api_share_inviter_telegram_user_id( $inviter ) {
    if ( null === $inviter || ! preg_match( '/^telegram:([1-9][0-9]{0,19})$/', (string) $inviter, $matches ) ) {
        return null;
    }

    return $matches[1];
}

/**
 * Selects an internal user id by Telegram user id.
 *
 * @param PDO $pdo
 * @param string $telegram_user_id
 * @return int|null
 */
function tonbankcard_api_share_select_user_id( PDO $pdo, string $telegram_user_id ) {
    $select = $pdo->prepare( 'SELECT id FROM users WHERE telegram_user_id = :telegram_user_id LIMIT 1' );
    $select->execute( [ ':telegram_user_id' => $telegram_user_id ] );
    $row = $select->fetch();

    return is_array( $row ) && ! empty( $row['id'] ) ? (int) $row['id'] : null;
}

/**
 * Ensures an inviter has a user row and returns its internal id.
 *
 * @param PDO $pdo
 * @param string $telegram_user_id
 * @param string $now
 * @return int|null
 */
function tonbankcard_api_share_upsert_referral_user( PDO $pdo, string $telegram_user_id, string $now ) {
    $insert = $pdo->prepare(
        'INSERT INTO users (telegram_user_id, last_seen_at)
         VALUES (:telegram_user_id, NULL)
         ON DUPLICATE KEY UPDATE telegram_user_id = telegram_user_id'
    );
    $insert->execute( [ ':telegram_user_id' => $telegram_user_id ] );

    return tonbankcard_api_share_select_user_id( $pdo, $telegram_user_id );
}

/**
 * Ensures a referral campaign exists and returns its id.
 *
 * @param PDO $pdo
 * @param string $campaign
 * @param string $context
 * @param string $now
 * @return int
 */
function tonbankcard_api_share_upsert_campaign( PDO $pdo, string $campaign, string $context, string $now ) {
    $insert = $pdo->prepare(
        'INSERT INTO referral_campaigns (code, name, source, status, created_at, updated_at)
         VALUES (:code, :name, :source, :status, :created_at, :updated_at)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            source = VALUES(source),
            status = IF(status = \'draft\', VALUES(status), status),
            updated_at = VALUES(updated_at)'
    );
    $insert->execute(
        [
            ':code'       => $campaign,
            ':name'       => tonbankcard_api_share_campaign_name( $campaign, $context ),
            ':source'     => $context,
            ':status'     => 'active',
            ':created_at' => $now,
            ':updated_at' => $now,
        ]
    );

    $select = $pdo->prepare( 'SELECT id FROM referral_campaigns WHERE code = :code LIMIT 1' );
    $select->execute( [ ':code' => $campaign ] );
    $row = $select->fetch();
    if ( ! is_array( $row ) || empty( $row['id'] ) ) {
        throw new RuntimeException( 'Referral campaign row was not persisted.' );
    }

    return (int) $row['id'];
}

/**
 * Builds a readable campaign name from a code and context.
 *
 * @param string $campaign
 * @param string $context
 * @return string
 */
function tonbankcard_api_share_campaign_name( string $campaign, string $context ) {
    $label = trim( ucwords( str_replace( [ '-', '_' ], ' ', $campaign ) ) );
    $context_label = trim( ucwords( str_replace( [ '-', '_' ], ' ', $context ) ) );

    return substr( trim( $label . ( '' === $context_label ? '' : ' - ' . $context_label ) ), 0, 160 );
}

/**
 * Returns the local dedupe key for one referred user and campaign.
 *
 * @param string $telegram_user_id
 * @param string $campaign
 * @return string
 */
function tonbankcard_api_share_local_attribution_key( string $telegram_user_id, string $campaign ) {
    return hash( 'sha256', $telegram_user_id . '|' . $campaign );
}

/**
 * Encodes bytes with URL-safe base64 without padding.
 *
 * @param string|false $value
 * @return string
 */
function tonbankcard_api_share_base64_url_encode( $value ) {
    return rtrim( strtr( base64_encode( (string) $value ), '+/', '-_' ), '=' );
}

/**
 * Decodes URL-safe base64.
 *
 * @param string $value
 * @return string|null
 */
function tonbankcard_api_share_base64_url_decode( string $value ) {
    $remainder = strlen( $value ) % 4;
    if ( $remainder ) {
        $value .= str_repeat( '=', 4 - $remainder );
    }

    $decoded = base64_decode( strtr( $value, '-_', '+/' ), TRUE );

    return FALSE === $decoded ? null : $decoded;
}

/**
 * Builds a typed share validation error result.
 *
 * @param int $status
 * @param string $code
 * @param string $message
 * @param array $details
 * @return array
 */
function tonbankcard_api_share_error( int $status, string $code, string $message, array $details ) {
    return [
        'ok'      => FALSE,
        'status'  => $status,
        'code'    => $code,
        'message' => $message,
        'details' => $details,
    ];
}
