<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 UPSTASH CACHE, RATE LIMITS, AND METRICS
 * -------------------------------------------------------------------------
 * Shared Redis-over-REST helpers. Upstash credentials stay in server-side
 * runtime configuration and are never included in client responses.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Returns Redis REST settings from runtime and API config.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_redis_settings( array $runtime, array $config ) {
    $redis = isset( $config['redis'] ) && is_array( $config['redis'] ) ? $config['redis'] : [];
    $upstash = isset( $runtime['providers']['upstash'] ) && is_array( $runtime['providers']['upstash'] ) ? $runtime['providers']['upstash'] : [];

    $rest_url = isset( $redis['rest_url'] ) ? (string) $redis['rest_url'] : ( isset( $upstash['rest_url'] ) ? (string) $upstash['rest_url'] : '' );
    $rest_token = isset( $redis['rest_token'] ) ? (string) $redis['rest_token'] : ( isset( $upstash['rest_token'] ) ? (string) $upstash['rest_token'] : '' );
    $configured = '' !== trim( $rest_url ) && '' !== trim( $rest_token );

    return [
        'configured'      => $configured,
        'enabled'         => $configured && ! empty( $redis['enabled'] ),
        'rest_url'        => rtrim( trim( $rest_url ), '/' ),
        'rest_token'      => $rest_token,
        'timeout_seconds' => isset( $redis['timeout_seconds'] ) ? max( 1, (int) $redis['timeout_seconds'] ) : 2,
        'key_prefix'      => isset( $redis['key_prefix'] ) && '' !== trim( (string) $redis['key_prefix'] ) ? trim( (string) $redis['key_prefix'], ':' ) : 'tonbankcard:v2',
        'transport'       => isset( $redis['transport'] ) && is_callable( $redis['transport'] ) ? $redis['transport'] : null,
    ];
}

/**
 * Executes a Redis command through Upstash REST or a test transport.
 *
 * @param array $runtime
 * @param array $config
 * @param array $command
 * @return array
 */
function tonbankcard_api_redis_command( array $runtime, array $config, array $command ) {
    $settings = tonbankcard_api_redis_settings( $runtime, $config );
    if ( empty( $settings['enabled'] ) ) {
        return [
            'ok'    => FALSE,
            'error' => empty( $settings['configured'] ) ? 'redis_not_configured' : 'redis_disabled',
        ];
    }

    if ( is_callable( $settings['transport'] ) ) {
        try {
            $response = call_user_func( $settings['transport'], array_values( $command ) );
            return tonbankcard_api_redis_normalize_response( $response );
        } catch ( Throwable $exception ) {
            return [
                'ok'    => FALSE,
                'error' => 'redis_transport_failed',
            ];
        }
    }

    $body = json_encode( array_values( $command ), JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE );
    if ( FALSE === $body ) {
        return [
            'ok'    => FALSE,
            'error' => 'redis_command_encode_failed',
        ];
    }

    if ( function_exists( 'curl_init' ) ) {
        return tonbankcard_api_redis_command_with_curl( $settings, $body );
    }

    return tonbankcard_api_redis_command_with_stream( $settings, $body );
}

/**
 * Normalizes Redis transport responses.
 *
 * @param mixed $response
 * @return array
 */
function tonbankcard_api_redis_normalize_response( $response ) {
    if ( is_array( $response ) && array_key_exists( 'ok', $response ) ) {
        return $response;
    }

    if ( is_array( $response ) && array_key_exists( 'error', $response ) ) {
        return [
            'ok'    => FALSE,
            'error' => (string) $response['error'],
        ];
    }

    if ( is_array( $response ) && array_key_exists( 'result', $response ) ) {
        return [
            'ok'     => TRUE,
            'result' => $response['result'],
        ];
    }

    return [
        'ok'     => TRUE,
        'result' => $response,
    ];
}

/**
 * Executes a Redis REST command with cURL.
 *
 * @param array $settings
 * @param string $body
 * @return array
 */
function tonbankcard_api_redis_command_with_curl( array $settings, string $body ) {
    $handle = curl_init( $settings['rest_url'] );
    if ( FALSE === $handle ) {
        return [
            'ok'    => FALSE,
            'error' => 'redis_transport_unavailable',
        ];
    }

    curl_setopt_array(
        $handle,
        [
            CURLOPT_POST           => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => (int) $settings['timeout_seconds'],
            CURLOPT_CONNECTTIMEOUT => min( 2, (int) $settings['timeout_seconds'] ),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $settings['rest_token'],
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => $body,
        ]
    );

    $response_body = curl_exec( $handle );
    $errno = curl_errno( $handle );
    $status = (int) curl_getinfo( $handle, CURLINFO_HTTP_CODE );
    curl_close( $handle );

    if ( 0 !== $errno || 200 > $status || 300 <= $status || FALSE === $response_body ) {
        return [
            'ok'     => FALSE,
            'error'  => 'redis_request_failed',
            'status' => $status,
        ];
    }

    return tonbankcard_api_redis_decode_body( (string) $response_body );
}

/**
 * Executes a Redis REST command with PHP streams.
 *
 * @param array $settings
 * @param string $body
 * @return array
 */
function tonbankcard_api_redis_command_with_stream( array $settings, string $body ) {
    $context = stream_context_create(
        [
            'http' => [
                'method'        => 'POST',
                'header'        => implode(
                    "\r\n",
                    [
                        'Authorization: Bearer ' . $settings['rest_token'],
                        'Content-Type: application/json',
                    ]
                ),
                'content'       => $body,
                'timeout'       => (int) $settings['timeout_seconds'],
                'ignore_errors' => TRUE,
            ],
        ]
    );

    $response_body = @file_get_contents( $settings['rest_url'], FALSE, $context );
    if ( FALSE === $response_body ) {
        return [
            'ok'    => FALSE,
            'error' => 'redis_request_failed',
        ];
    }

    return tonbankcard_api_redis_decode_body( (string) $response_body );
}

/**
 * Decodes an Upstash REST JSON response.
 *
 * @param string $body
 * @return array
 */
function tonbankcard_api_redis_decode_body( string $body ) {
    $decoded = json_decode( $body, TRUE );
    if ( ! is_array( $decoded ) ) {
        return [
            'ok'    => FALSE,
            'error' => 'redis_invalid_json',
        ];
    }

    if ( array_key_exists( 'error', $decoded ) ) {
        return [
            'ok'    => FALSE,
            'error' => 'redis_command_failed',
        ];
    }

    return [
        'ok'     => TRUE,
        'result' => array_key_exists( 'result', $decoded ) ? $decoded['result'] : null,
    ];
}

/**
 * Returns cache settings with the configured TTL map.
 *
 * @param array $config
 * @return array
 */
function tonbankcard_api_cache_settings( array $config ) {
    $cache = isset( $config['cache'] ) && is_array( $config['cache'] ) ? $config['cache'] : [];
    $ttls = isset( $cache['ttls'] ) && is_array( $cache['ttls'] ) ? $cache['ttls'] : [];
    $coalesce = isset( $cache['coalesce'] ) && is_array( $cache['coalesce'] ) ? $cache['coalesce'] : [];

    return [
        'enabled'           => ! empty( $cache['enabled'] ),
        'stale_ttl_seconds' => isset( $cache['stale_ttl_seconds'] ) ? max( 0, (int) $cache['stale_ttl_seconds'] ) : 3600,
        'ttls'              => array_merge(
            [
                'live_prices'      => 60,
                'global_stats'     => 300,
                'coin_metadata'    => 3600,
                'charts'           => 900,
                'search_index'     => 3600,
                'sentiment_inputs' => 300,
                'ai_summaries'     => 21600,
                'ton_metadata'     => 86400,
            ],
            $ttls
        ),
        'coalesce'          => [
            'enabled'          => ! empty( $coalesce['enabled'] ),
            'lock_ttl_seconds' => isset( $coalesce['lock_ttl_seconds'] ) ? max( 1, (int) $coalesce['lock_ttl_seconds'] ) : 15,
            'wait_ms'          => isset( $coalesce['wait_ms'] ) ? max( 0, (int) $coalesce['wait_ms'] ) : 250,
            'poll_ms'          => isset( $coalesce['poll_ms'] ) ? max( 1, (int) $coalesce['poll_ms'] ) : 25,
        ],
    ];
}

/**
 * Builds a stable Redis key.
 *
 * @param array $runtime
 * @param array $config
 * @param string $namespace
 * @param mixed $parts
 * @return string
 */
function tonbankcard_api_redis_key( array $runtime, array $config, string $namespace, $parts ) {
    $settings = tonbankcard_api_redis_settings( $runtime, $config );
    $encoded = json_encode( $parts, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE );
    if ( FALSE === $encoded ) {
        $encoded = serialize( $parts );
    }

    return $settings['key_prefix'] . ':' . trim( $namespace, ':' ) . ':' . hash( 'sha256', $encoded );
}

/**
 * Reads and classifies a cache entry.
 *
 * @param array $runtime
 * @param array $config
 * @param string $key
 * @return array
 */
function tonbankcard_api_cache_get( array $runtime, array $config, string $key ) {
    $settings = tonbankcard_api_cache_settings( $config );
    if ( empty( $settings['enabled'] ) ) {
        return [ 'state' => 'disabled' ];
    }

    $response = tonbankcard_api_redis_command( $runtime, $config, [ 'GET', $key ] );
    if ( empty( $response['ok'] ) ) {
        return [
            'state' => 'unavailable',
            'error' => isset( $response['error'] ) ? $response['error'] : 'redis_unavailable',
        ];
    }

    if ( null === $response['result'] || '' === $response['result'] ) {
        return [ 'state' => 'miss' ];
    }

    $entry = json_decode( (string) $response['result'], TRUE );
    if ( ! is_array( $entry ) || ! isset( $entry['data'] ) || empty( $entry['stored_at'] ) || empty( $entry['expires_at'] ) ) {
        return [ 'state' => 'miss' ];
    }

    $now = time();
    $cache_age = max( 0, $now - (int) $entry['stored_at'] );
    if ( (int) $entry['expires_at'] >= $now ) {
        return [
            'state'             => 'hit',
            'entry'             => $entry,
            'cache_age_seconds' => $cache_age,
            'stale_age_seconds' => 0,
        ];
    }

    if ( ! empty( $entry['stale_until'] ) && (int) $entry['stale_until'] >= $now ) {
        return [
            'state'             => 'stale',
            'entry'             => $entry,
            'cache_age_seconds' => $cache_age,
            'stale_age_seconds' => max( 0, $now - (int) $entry['expires_at'] ),
        ];
    }

    return [ 'state' => 'expired' ];
}

/**
 * Stores a cache entry with fresh and stale TTLs.
 *
 * @param array $runtime
 * @param array $config
 * @param string $key
 * @param array $data
 * @param array $upstream
 * @param int $ttl_seconds
 * @param int $stale_ttl_seconds
 * @param int $stored_at
 * @return bool
 */
function tonbankcard_api_cache_set( array $runtime, array $config, string $key, array $data, array $upstream, int $ttl_seconds, int $stale_ttl_seconds, int $stored_at ) {
    $settings = tonbankcard_api_cache_settings( $config );
    if ( empty( $settings['enabled'] ) || 0 >= $ttl_seconds ) {
        return FALSE;
    }

    $entry = [
        'stored_at'   => $stored_at,
        'expires_at'  => $stored_at + $ttl_seconds,
        'stale_until' => $stored_at + $ttl_seconds + $stale_ttl_seconds,
        'data'        => $data,
        'upstream'    => $upstream,
    ];
    $payload = json_encode( $entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE );
    if ( FALSE === $payload ) {
        return FALSE;
    }

    $expire_seconds = max( 1, $ttl_seconds + $stale_ttl_seconds );
    $response = tonbankcard_api_redis_command( $runtime, $config, [ 'SET', $key, $payload, 'EX', $expire_seconds ] );
    return ! empty( $response['ok'] );
}

/**
 * Attempts to acquire a short coalescing lock.
 *
 * @param array $runtime
 * @param array $config
 * @param string $key
 * @param string $owner
 * @param int $ttl_seconds
 * @return bool
 */
function tonbankcard_api_cache_lock_acquire( array $runtime, array $config, string $key, string $owner, int $ttl_seconds ) {
    $response = tonbankcard_api_redis_command( $runtime, $config, [ 'SET', $key, $owner, 'NX', 'EX', $ttl_seconds ] );
    return ! empty( $response['ok'] ) && 'OK' === $response['result'];
}

/**
 * Waits briefly for another request to fill the cache.
 *
 * @param array $runtime
 * @param array $config
 * @param string $key
 * @param int $wait_ms
 * @param int $poll_ms
 * @return array
 */
function tonbankcard_api_cache_wait_for_fill( array $runtime, array $config, string $key, int $wait_ms, int $poll_ms ) {
    $deadline = microtime( TRUE ) + ( $wait_ms / 1000 );

    do {
        usleep( max( 1, $poll_ms ) * 1000 );
        $cached = tonbankcard_api_cache_get( $runtime, $config, $key );
        if ( isset( $cached['state'] ) && 'hit' === $cached['state'] ) {
            $cached['coalesced'] = TRUE;
            return $cached;
        }
    } while ( microtime( TRUE ) < $deadline );

    return [ 'state' => 'miss' ];
}

/**
 * Returns the daily metric key.
 *
 * @param array $runtime
 * @param array $config
 * @param string $name
 * @return string
 */
function tonbankcard_api_metric_key( array $runtime, array $config, string $name ) {
    $settings = tonbankcard_api_redis_settings( $runtime, $config );
    return $settings['key_prefix'] . ':metrics:' . gmdate( 'Ymd' ) . ':' . $name;
}

/**
 * Increments a metric counter.
 *
 * @param array $runtime
 * @param array $config
 * @param string $name
 * @return void
 */
function tonbankcard_api_metric_increment( array $runtime, array $config, string $name ) {
    $key = tonbankcard_api_metric_key( $runtime, $config, $name );
    $response = tonbankcard_api_redis_command( $runtime, $config, [ 'INCR', $key ] );
    if ( ! empty( $response['ok'] ) ) {
        tonbankcard_api_redis_command( $runtime, $config, [ 'EXPIRE', $key, 172800 ] );
    }
}

/**
 * Stores the latest metric value.
 *
 * @param array $runtime
 * @param array $config
 * @param string $name
 * @param mixed $value
 * @return void
 */
function tonbankcard_api_metric_set( array $runtime, array $config, string $name, $value ) {
    tonbankcard_api_redis_command( $runtime, $config, [ 'SET', tonbankcard_api_metric_key( $runtime, $config, $name ), (string) $value, 'EX', 172800 ] );
}

/**
 * Returns current operational counters.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_metrics_payload( array $runtime, array $config ) {
    $settings = tonbankcard_api_redis_settings( $runtime, $config );
    $names = [
        'cache.hit',
        'cache.miss',
        'cache.stale',
        'cache.upstream_fallback',
        'cache.bypass',
        'cache.stale_age_seconds',
        'rate_limit.allowed',
        'rate_limit.blocked',
        'rate_limit.unavailable',
    ];
    $keys = [];
    foreach ( $names as $name ) {
        $keys[] = tonbankcard_api_metric_key( $runtime, $config, $name );
    }

    $values = array_fill( 0, count( $names ), 0 );
    $response = tonbankcard_api_redis_command( $runtime, $config, array_merge( [ 'MGET' ], $keys ) );
    if ( ! empty( $response['ok'] ) && is_array( $response['result'] ) ) {
        foreach ( $response['result'] as $index => $value ) {
            $values[ $index ] = null === $value ? 0 : (int) $value;
        }
    }

    $metrics = array_combine( $names, $values );
    $cache_total = $metrics['cache.hit'] + $metrics['cache.miss'] + $metrics['cache.stale'];
    $cache_hits = $metrics['cache.hit'] + $metrics['cache.stale'];

    return [
        'redis'      => [
            'configured' => (bool) $settings['configured'],
            'enabled'    => (bool) $settings['enabled'],
        ],
        'cache'      => [
            'hits'                   => $metrics['cache.hit'],
            'misses'                 => $metrics['cache.miss'],
            'stale'                  => $metrics['cache.stale'],
            'upstream_fallbacks'     => $metrics['cache.upstream_fallback'],
            'bypassed'               => $metrics['cache.bypass'],
            'cache_hit_rate'         => $cache_total > 0 ? round( $cache_hits / $cache_total, 4 ) : 0,
            'last_stale_age_seconds' => $metrics['cache.stale_age_seconds'],
        ],
        'rate_limit' => [
            'allowed'     => $metrics['rate_limit.allowed'],
            'blocked'     => $metrics['rate_limit.blocked'],
            'unavailable' => $metrics['rate_limit.unavailable'],
        ],
    ];
}

/**
 * Returns rate-limit settings.
 *
 * @param array $config
 * @return array
 */
function tonbankcard_api_rate_limit_settings( array $config ) {
    $rate_limit = isset( $config['rate_limit'] ) && is_array( $config['rate_limit'] ) ? $config['rate_limit'] : [];
    $policies = isset( $rate_limit['policies'] ) && is_array( $rate_limit['policies'] ) ? $rate_limit['policies'] : [];
    $default_policies = [
        'anonymous_web'    => [ 'window_seconds' => 60, 'max_requests' => 120 ],
        'telegram_session' => [ 'window_seconds' => 60, 'max_requests' => 240 ],
        'admin_action'     => [ 'window_seconds' => 60, 'max_requests' => 30 ],
    ];

    foreach ( $policies as $name => $policy ) {
        if ( ! is_array( $policy ) ) {
            continue;
        }
        $default_policies[ $name ] = array_merge(
            isset( $default_policies[ $name ] ) ? $default_policies[ $name ] : [],
            $policy
        );
    }

    return [
        'enabled'  => ! empty( $rate_limit['enabled'] ),
        'policies' => $default_policies,
    ];
}

/**
 * Classifies a request for rate limiting without exposing raw identifiers.
 *
 * @param array $request
 * @return array
 */
function tonbankcard_api_rate_limit_identity( array $request ) {
    $headers = [];
    foreach ( isset( $request['headers'] ) && is_array( $request['headers'] ) ? $request['headers'] : [] as $name => $value ) {
        $headers[ strtolower( (string) $name ) ] = is_array( $value ) ? implode( ',', $value ) : (string) $value;
    }
    $path = isset( $request['path'] ) ? (string) $request['path'] : '/';

    if ( '/api/admin' === $path || 0 === strpos( $path, '/api/admin/' ) || ! empty( $headers['x-tonbankcard-admin'] ) ) {
        $source = ! empty( $headers['authorization'] ) ? $headers['authorization'] : ( ! empty( $headers['x-tonbankcard-admin'] ) ? $headers['x-tonbankcard-admin'] : 'admin' );
        return [
            'policy' => 'admin_action',
            'key'    => hash( 'sha256', 'admin|' . $source ),
        ];
    }

    $session_token = function_exists( 'tonbankcard_api_session_token_from_request' )
        ? tonbankcard_api_session_token_from_request( [ 'headers' => $headers ] )
        : null;
    if ( null !== $session_token || ! empty( $headers['x-tonbankcard-session'] ) || ! empty( $headers['x-telegram-init-data'] ) ) {
        $source = null !== $session_token ? $session_token : ( ! empty( $headers['x-tonbankcard-session'] ) ? $headers['x-tonbankcard-session'] : $headers['x-telegram-init-data'] );
        return [
            'policy' => 'telegram_session',
            'key'    => hash( 'sha256', 'telegram|' . $source ),
        ];
    }

    $ip = function_exists( 'tonbankcard_api_request_ip' ) ? tonbankcard_api_request_ip( [ 'headers' => $headers ] ) : null;
    $user_agent = isset( $headers['user-agent'] ) ? $headers['user-agent'] : '';

    return [
        'policy' => 'anonymous_web',
        'key'    => hash( 'sha256', 'anonymous|' . ( null === $ip ? 'unknown' : $ip ) . '|' . $user_agent ),
    ];
}

/**
 * Applies the configured rate limit.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_rate_limit_check( array $request, array $runtime, array $config ) {
    if ( isset( $request['method'] ) && 'OPTIONS' === $request['method'] ) {
        return [ 'allowed' => TRUE, 'headers' => [] ];
    }

    $settings = tonbankcard_api_rate_limit_settings( $config );
    if ( empty( $settings['enabled'] ) ) {
        return [ 'allowed' => TRUE, 'headers' => [] ];
    }

    $redis = tonbankcard_api_redis_settings( $runtime, $config );
    if ( empty( $redis['enabled'] ) ) {
        tonbankcard_api_metric_increment( $runtime, $config, 'rate_limit.unavailable' );
        return [ 'allowed' => TRUE, 'headers' => [] ];
    }

    $identity = tonbankcard_api_rate_limit_identity( $request );
    $policy_name = $identity['policy'];
    $policy = isset( $settings['policies'][ $policy_name ] ) ? $settings['policies'][ $policy_name ] : $settings['policies']['anonymous_web'];
    $window_seconds = isset( $policy['window_seconds'] ) ? max( 1, (int) $policy['window_seconds'] ) : 60;
    $max_requests = isset( $policy['max_requests'] ) ? max( 1, (int) $policy['max_requests'] ) : 60;
    $key = $redis['key_prefix'] . ':rate_limit:' . $policy_name . ':' . $identity['key'];

    $increment = tonbankcard_api_redis_command( $runtime, $config, [ 'INCR', $key ] );
    if ( empty( $increment['ok'] ) ) {
        tonbankcard_api_metric_increment( $runtime, $config, 'rate_limit.unavailable' );
        return [ 'allowed' => TRUE, 'headers' => [] ];
    }

    $count = (int) $increment['result'];
    if ( 1 === $count ) {
        tonbankcard_api_redis_command( $runtime, $config, [ 'EXPIRE', $key, $window_seconds ] );
    }

    $ttl_response = tonbankcard_api_redis_command( $runtime, $config, [ 'TTL', $key ] );
    $retry_after = ! empty( $ttl_response['ok'] ) && (int) $ttl_response['result'] > 0 ? (int) $ttl_response['result'] : $window_seconds;
    $remaining = max( 0, $max_requests - $count );
    $headers = [
        'X-RateLimit-Limit'     => (string) $max_requests,
        'X-RateLimit-Remaining' => (string) $remaining,
        'X-RateLimit-Reset'     => (string) ( time() + $retry_after ),
        'X-RateLimit-Policy'    => $policy_name,
    ];

    if ( $count > $max_requests ) {
        tonbankcard_api_metric_increment( $runtime, $config, 'rate_limit.blocked' );
        $headers['Retry-After'] = (string) $retry_after;

        return [
            'allowed'      => FALSE,
            'headers'      => $headers,
            'retry_after'  => $retry_after,
            'policy'       => $policy_name,
            'limit'        => $max_requests,
            'window'       => $window_seconds,
        ];
    }

    tonbankcard_api_metric_increment( $runtime, $config, 'rate_limit.allowed' );

    return [
        'allowed' => TRUE,
        'headers' => $headers,
    ];
}
