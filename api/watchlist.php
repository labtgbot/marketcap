<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 WATCHLIST API
 * -------------------------------------------------------------------------
 * Trusted-session watchlist sync for Telegram Mini App users.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Returns TRUE when the path belongs to the watchlist API.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_watchlist_is_request( string $path ) {
    $path = tonbankcard_api_normalize_path( $path );
    return '/api/watchlist' === $path || 0 === strpos( $path, '/api/watchlist/' );
}

/**
 * Handles trusted-session watchlist requests.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_watchlist_handle( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $path = tonbankcard_api_normalize_path( $request['path'] );
    $method = strtoupper( $request['method'] );

    if ( '/api/watchlist' !== $path && 0 !== strpos( $path, '/api/watchlist/' ) ) {
        return tonbankcard_api_error_response(
            404,
            'not_found',
            'No watchlist route matches the request.',
            [ 'path' => $path ],
            $request_id,
            $headers
        );
    }

    if ( '/api/watchlist' === $path && ! in_array( $method, [ 'GET', 'POST', 'PUT' ], TRUE ) ) {
        return tonbankcard_api_method_not_allowed_response( [ 'GET', 'POST', 'PUT', 'OPTIONS' ], $request_id, $headers );
    }

    if ( '/api/watchlist' !== $path && 'DELETE' !== $method ) {
        return tonbankcard_api_method_not_allowed_response( [ 'DELETE', 'OPTIONS' ], $request_id, $headers );
    }

    $session = tonbankcard_api_watchlist_trusted_session( $request, $runtime );
    if ( empty( $session['ok'] ) ) {
        if ( isset( $session['reason'] ) && 'storage_unavailable' === $session['reason'] ) {
            return tonbankcard_api_error_response(
                503,
                'watchlist_storage_unavailable',
                'Trusted watchlist sync requires the configured MySQL session store.',
                [ 'storage' => 'mysql' ],
                $request_id,
                $headers
            );
        }

        return tonbankcard_api_error_response(
            401,
            'watchlist_session_required',
            'Trusted watchlist sync requires a validated Telegram session.',
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
    $watchlist_id = tonbankcard_api_watchlist_default_id( $pdo, $user_id );

    if ( 'GET' === $method ) {
        return tonbankcard_api_success_response(
            tonbankcard_api_watchlist_payload( $pdo, $watchlist_id, $premium_state ),
            $request_id,
            $headers
        );
    }

    if ( 'POST' === $method || 'PUT' === $method ) {
        $payload = tonbankcard_api_json_body( $request );
        $entries = isset( $payload['entries'] ) && is_array( $payload['entries'] ) ? $payload['entries'] : $payload;
        $entries = tonbankcard_api_watchlist_normalize_entries( is_array( $entries ) ? $entries : [] );
        $removed = isset( $payload['removed'] ) && is_array( $payload['removed'] )
            ? tonbankcard_api_watchlist_normalize_removed( $payload['removed'] )
            : [];
        if ( null !== $premium_state && function_exists( 'tonbankcard_api_premium_limit_allows' ) && ! tonbankcard_api_premium_limit_allows( $premium_state, 'watchlist_entries', count( $entries ) ) ) {
            return tonbankcard_api_error_response(
                409,
                'watchlist_limit_reached',
                'This Telegram user has reached the watchlist size limit for the current plan.',
                tonbankcard_api_premium_limit_error_details( $premium_state, 'watchlist_entries', count( $entries ) ),
                $request_id,
                $headers
            );
        }

        tonbankcard_api_watchlist_sync_snapshot( $pdo, $watchlist_id, $user_id, $entries, $removed );

        return tonbankcard_api_success_response(
            tonbankcard_api_watchlist_payload( $pdo, $watchlist_id, $premium_state ),
            $request_id,
            $headers
        );
    }

    $coin_id = tonbankcard_api_watchlist_coin_id_from_path( $path );
    if ( null === $coin_id ) {
        return tonbankcard_api_error_response(
            400,
            'invalid_watchlist_coin_id',
            'The watchlist coin id is invalid.',
            [ 'path' => $path ],
            $request_id,
            $headers
        );
    }

    tonbankcard_api_watchlist_tombstone_entry( $pdo, $watchlist_id, $user_id, $coin_id, tonbankcard_api_watchlist_normalize_timestamp( null ) );

    return tonbankcard_api_success_response(
        tonbankcard_api_watchlist_payload( $pdo, $watchlist_id, $premium_state ),
        $request_id,
        $headers
    );
}

/**
 * Loads a trusted Telegram session and its database connection.
 *
 * @param array $request
 * @param array $runtime
 * @return array
 */
function tonbankcard_api_watchlist_trusted_session( array $request, array $runtime ) {
    $token = tonbankcard_api_watchlist_session_token( $request );
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
function tonbankcard_api_watchlist_session_token( array $request ) {
    $token = tonbankcard_api_session_token_from_request( $request );
    if ( null !== $token ) {
        return $token;
    }

    $authorization = isset( $request['headers']['authorization'] ) ? trim( (string) $request['headers']['authorization'] ) : '';
    if ( preg_match( '/^Bearer\s+([A-Fa-f0-9]{64})$/', $authorization, $matches ) ) {
        return strtolower( $matches[1] );
    }

    return null;
}

/**
 * Ensures the user's default personal watchlist exists.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @return int
 */
function tonbankcard_api_watchlist_default_id( PDO $pdo, int $user_id ) {
    $upsert = $pdo->prepare(
        "INSERT INTO watchlists (user_id, name, scope, is_default, deleted_at)
         VALUES (:user_id, 'Default', 'personal', 1, NULL)
         ON DUPLICATE KEY UPDATE
            is_default = 1,
            deleted_at = NULL,
            updated_at = UTC_TIMESTAMP(6)"
    );
    $upsert->execute( [ ':user_id' => $user_id ] );

    $select = $pdo->prepare(
        "SELECT id
         FROM watchlists
         WHERE user_id = :user_id
           AND name = 'Default'
           AND deleted_at IS NULL
         LIMIT 1"
    );
    $select->execute( [ ':user_id' => $user_id ] );
    $row = $select->fetch();

    if ( ! is_array( $row ) || empty( $row['id'] ) ) {
        throw new RuntimeException( 'Default watchlist could not be loaded.' );
    }

    return (int) $row['id'];
}

/**
 * Returns watchlist response payload.
 *
 * @param PDO $pdo
 * @param int $watchlist_id
 * @param array|null $premium_state
 * @return array
 */
function tonbankcard_api_watchlist_payload( PDO $pdo, int $watchlist_id, array $premium_state = null ) {
    $entries = tonbankcard_api_watchlist_entries( $pdo, $watchlist_id );
    $removed = tonbankcard_api_watchlist_removed( $pdo, $watchlist_id );
    $updated_at = null;
    foreach ( $entries as $entry ) {
        if ( null === $updated_at || strcmp( $entry['added_at'], $updated_at ) > 0 ) {
            $updated_at = $entry['added_at'];
        }
    }
    foreach ( $removed as $removed_at ) {
        if ( null === $updated_at || strcmp( $removed_at, $updated_at ) > 0 ) {
            $updated_at = $removed_at;
        }
    }

    $payload = [
        'version'    => 1,
        'updated_at' => $updated_at,
        'entries'    => $entries,
        'removed'    => $removed,
        'storage'    => 'mysql',
    ];

    if ( null !== $premium_state && function_exists( 'tonbankcard_api_premium_public_state' ) ) {
        $payload['premium'] = tonbankcard_api_premium_public_state( $premium_state );
    }

    return $payload;
}

/**
 * Loads entries for a watchlist.
 *
 * @param PDO $pdo
 * @param int $watchlist_id
 * @return array
 */
function tonbankcard_api_watchlist_entries( PDO $pdo, int $watchlist_id ) {
    $select = $pdo->prepare(
        'SELECT coin_id, symbol, source, sort_order, added_at
         FROM watchlist_entries
         WHERE watchlist_id = :watchlist_id
         ORDER BY sort_order ASC, added_at ASC, coin_id ASC'
    );
    $select->execute( [ ':watchlist_id' => $watchlist_id ] );

    $entries = [];
    foreach ( $select->fetchAll() as $row ) {
        $entries[] = [
            'coin_id'   => $row['coin_id'],
            'id'        => $row['coin_id'],
            'symbol'    => $row['symbol'],
            'source'    => $row['source'],
            'sort_order' => (int) $row['sort_order'],
            'added_at'  => tonbankcard_api_watchlist_datetime_to_iso( $row['added_at'] ),
            'updated_at' => tonbankcard_api_watchlist_datetime_to_iso( $row['added_at'] ),
        ];
    }

    return $entries;
}

/**
 * Loads removal tombstones for conflict-safe cross-device sync.
 *
 * @param PDO $pdo
 * @param int $watchlist_id
 * @return array
 */
function tonbankcard_api_watchlist_removed( PDO $pdo, int $watchlist_id ) {
    $select = $pdo->prepare(
        'SELECT coin_id, removed_at
         FROM watchlist_tombstones
         WHERE watchlist_id = :watchlist_id
         ORDER BY removed_at ASC, coin_id ASC'
    );
    $select->execute( [ ':watchlist_id' => $watchlist_id ] );

    $removed = [];
    foreach ( $select->fetchAll() as $row ) {
        $removed[ $row['coin_id'] ] = tonbankcard_api_watchlist_datetime_to_iso( $row['removed_at'] );
    }

    return $removed;
}

/**
 * Applies a client snapshot without letting stale devices resurrect removals.
 *
 * The unique key uniq_watchlist_entries_watchlist_coin prevents duplicate
 * rows when devices submit the same coin during sync. Removal tombstones keep
 * deletes authoritative until a newer add timestamp arrives.
 *
 * @param PDO $pdo
 * @param int $watchlist_id
 * @param int $user_id
 * @param array $entries
 * @param array $removed
 * @return void
 */
function tonbankcard_api_watchlist_sync_snapshot( PDO $pdo, int $watchlist_id, int $user_id, array $entries, array $removed ) {
    $pdo->beginTransaction();
    try {
        foreach ( $entries as $index => $entry ) {
            tonbankcard_api_watchlist_upsert_entry( $pdo, $watchlist_id, $user_id, $entry, $index );
        }

        foreach ( $removed as $coin_id => $removed_at ) {
            tonbankcard_api_watchlist_tombstone_entry( $pdo, $watchlist_id, $user_id, $coin_id, $removed_at );
        }

        $pdo->commit();
    } catch ( Throwable $exception ) {
        if ( $pdo->inTransaction() ) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/**
 * Adds an active entry unless an equal-or-newer tombstone already exists.
 *
 * @param PDO $pdo
 * @param int $watchlist_id
 * @param int $user_id
 * @param array $entry
 * @param int $sort_order
 * @return void
 */
function tonbankcard_api_watchlist_upsert_entry( PDO $pdo, int $watchlist_id, int $user_id, array $entry, int $sort_order ) {
    $entry_at = tonbankcard_api_watchlist_timestamp_to_mysql( isset( $entry['updated_at'] ) ? $entry['updated_at'] : $entry['added_at'] );

    $blocked = $pdo->prepare(
        'SELECT 1
         FROM watchlist_tombstones
         WHERE watchlist_id = :watchlist_id
           AND coin_id = :coin_id
           AND removed_at >= :entry_at
         LIMIT 1'
    );
    $blocked->execute(
        [
            ':watchlist_id' => $watchlist_id,
            ':coin_id'      => $entry['coin_id'],
            ':entry_at'     => $entry_at,
        ]
    );
    if ( $blocked->fetchColumn() ) {
        return;
    }

    $delete_tombstone = $pdo->prepare(
        'DELETE FROM watchlist_tombstones
         WHERE watchlist_id = :watchlist_id
           AND coin_id = :coin_id'
    );
    $delete_tombstone->execute(
        [
            ':watchlist_id' => $watchlist_id,
            ':coin_id'      => $entry['coin_id'],
        ]
    );

    $upsert = $pdo->prepare(
        'INSERT INTO watchlist_entries (watchlist_id, user_id, coin_id, symbol, source, sort_order, added_at)
         VALUES (:watchlist_id, :user_id, :coin_id, :symbol, :source, :sort_order, :added_at)
         ON DUPLICATE KEY UPDATE
            symbol = VALUES(symbol),
            source = VALUES(source),
            sort_order = VALUES(sort_order)'
    );
    $upsert->execute(
        [
            ':watchlist_id' => $watchlist_id,
            ':user_id'      => $user_id,
            ':coin_id'      => $entry['coin_id'],
            ':symbol'       => $entry['symbol'],
            ':source'       => $entry['source'],
            ':sort_order'   => $sort_order,
            ':added_at'     => tonbankcard_api_watchlist_timestamp_to_mysql( $entry['added_at'] ),
        ]
    );
}

/**
 * Records a removal if it is at least as new as the active entry.
 *
 * @param PDO $pdo
 * @param int $watchlist_id
 * @param int $user_id
 * @param string $coin_id
 * @param string $removed_at
 * @return void
 */
function tonbankcard_api_watchlist_tombstone_entry( PDO $pdo, int $watchlist_id, int $user_id, string $coin_id, string $removed_at ) {
    $removed_at_mysql = tonbankcard_api_watchlist_timestamp_to_mysql( $removed_at );

    $active = $pdo->prepare(
        'SELECT added_at
         FROM watchlist_entries
         WHERE watchlist_id = :watchlist_id
           AND coin_id = :coin_id
         LIMIT 1'
    );
    $active->execute(
        [
            ':watchlist_id' => $watchlist_id,
            ':coin_id'      => $coin_id,
        ]
    );
    $row = $active->fetch();
    if ( is_array( $row ) && ! empty( $row['added_at'] ) && strcmp( (string) $row['added_at'], $removed_at_mysql ) > 0 ) {
        return;
    }

    $delete_entry = $pdo->prepare(
        'DELETE FROM watchlist_entries
         WHERE watchlist_id = :watchlist_id
           AND coin_id = :coin_id'
    );
    $delete_entry->execute(
        [
            ':watchlist_id' => $watchlist_id,
            ':coin_id'      => $coin_id,
        ]
    );

    $upsert_tombstone = $pdo->prepare(
        'INSERT INTO watchlist_tombstones (watchlist_id, user_id, coin_id, removed_at)
         VALUES (:watchlist_id, :user_id, :coin_id, :removed_at)
         ON DUPLICATE KEY UPDATE
            removed_at = GREATEST(removed_at, VALUES(removed_at))'
    );
    $upsert_tombstone->execute(
        [
            ':watchlist_id' => $watchlist_id,
            ':user_id'      => $user_id,
            ':coin_id'      => $coin_id,
            ':removed_at'   => $removed_at_mysql,
        ]
    );
}

/**
 * Normalizes and deduplicates entries before server sync.
 *
 * @param array $entries
 * @return array
 */
function tonbankcard_api_watchlist_normalize_entries( array $entries ) {
    $normalized = [];
    $seen = [];

    foreach ( $entries as $entry ) {
        if ( is_string( $entry ) ) {
            $entry = [ 'coin_id' => $entry ];
        }
        if ( ! is_array( $entry ) ) {
            continue;
        }

        $coin_id = tonbankcard_api_watchlist_normalize_coin_id(
            isset( $entry['coin_id'] ) ? $entry['coin_id'] : ( isset( $entry['id'] ) ? $entry['id'] : '' )
        );
        if ( null === $coin_id || isset( $seen[ $coin_id ] ) ) {
            continue;
        }

        $seen[ $coin_id ] = TRUE;
        $added_at = tonbankcard_api_watchlist_normalize_timestamp( isset( $entry['added_at'] ) ? $entry['added_at'] : null );
        $updated_at = tonbankcard_api_watchlist_normalize_timestamp(
            isset( $entry['updated_at'] ) ? $entry['updated_at'] : ( isset( $entry['updatedAt'] ) ? $entry['updatedAt'] : $added_at )
        );
        $normalized[] = [
            'coin_id'    => $coin_id,
            'symbol'     => tonbankcard_api_watchlist_normalize_symbol( isset( $entry['symbol'] ) ? $entry['symbol'] : null ),
            'source'     => tonbankcard_api_watchlist_normalize_source( isset( $entry['source'] ) ? $entry['source'] : 'coingecko' ),
            'added_at'   => $added_at,
            'updated_at' => $updated_at,
        ];

        if ( count( $normalized ) >= 200 ) {
            break;
        }
    }

    return $normalized;
}

/**
 * Normalizes removal tombstones from client snapshots.
 *
 * @param array $removed
 * @return array
 */
function tonbankcard_api_watchlist_normalize_removed( array $removed ) {
    $normalized = [];

    foreach ( $removed as $coin_id => $removed_at ) {
        $coin_id = tonbankcard_api_watchlist_normalize_coin_id( $coin_id );
        if ( null === $coin_id ) {
            continue;
        }

        $normalized[ $coin_id ] = tonbankcard_api_watchlist_normalize_timestamp( $removed_at );
    }

    return $normalized;
}

/**
 * Extracts and validates a coin id from /api/watchlist/{coin_id}.
 *
 * @param string $path
 * @return string|null
 */
function tonbankcard_api_watchlist_coin_id_from_path( string $path ) {
    $prefix = '/api/watchlist/';
    if ( 0 !== strpos( $path, $prefix ) ) {
        return null;
    }

    return tonbankcard_api_watchlist_normalize_coin_id( rawurldecode( substr( $path, strlen( $prefix ) ) ) );
}

/**
 * Normalizes a CoinGecko-style public coin id.
 *
 * @param mixed $value
 * @return string|null
 */
function tonbankcard_api_watchlist_normalize_coin_id( $value ) {
    $value = strtolower( trim( (string) $value ) );
    return preg_match( '/^[a-z0-9._-]{1,96}$/', $value ) ? $value : null;
}

/**
 * Normalizes optional symbols.
 *
 * @param mixed $value
 * @return string|null
 */
function tonbankcard_api_watchlist_normalize_symbol( $value ) {
    if ( null === $value || '' === trim( (string) $value ) ) {
        return null;
    }

    $value = strtolower( trim( (string) $value ) );
    return preg_match( '/^[a-z0-9._-]{1,32}$/', $value ) ? $value : null;
}

/**
 * Normalizes provider source labels.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_watchlist_normalize_source( $value ) {
    $value = strtolower( trim( (string) $value ) );
    return preg_match( '/^[a-z0-9._-]{1,32}$/', $value ) ? $value : 'coingecko';
}

/**
 * Normalizes a client timestamp to a UTC ISO string.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_watchlist_normalize_timestamp( $value ) {
    if ( null !== $value && '' !== trim( (string) $value ) ) {
        try {
            $date = new DateTimeImmutable( (string) $value );
            return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' );
        } catch ( Throwable $exception ) {}
    }

    return gmdate( 'Y-m-d\TH:i:s\Z' );
}

/**
 * Converts an ISO timestamp to a MySQL UTC DATETIME string.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_watchlist_timestamp_to_mysql( $value ) {
    $iso = tonbankcard_api_watchlist_normalize_timestamp( $value );
    return str_replace( 'T', ' ', substr( $iso, 0, 19 ) ) . '.000000';
}

/**
 * Converts MySQL DATETIME(6) strings to compact UTC ISO strings.
 *
 * @param string|null $value
 * @return string|null
 */
function tonbankcard_api_watchlist_datetime_to_iso( $value ) {
    if ( null === $value || '' === $value ) {
        return null;
    }

    $value = preg_replace( '/\.\d+$/', '', (string) $value );
    return str_replace( ' ', 'T', $value ) . 'Z';
}
