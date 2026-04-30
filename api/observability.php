<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 OBSERVABILITY
 * -------------------------------------------------------------------------
 * Privacy-aware JSON-line logging helpers for API, provider, browser, queue,
 * and bot delivery failure diagnostics.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

if ( ! function_exists( 'tonbankcard_observability_config' ) ) {
    /**
     * Returns normalized observability settings from runtime and API config.
     *
     * @param array $runtime
     * @param array $config
     * @return array
     */
    function tonbankcard_observability_config( array $runtime = [], array $config = [] ) {
        $runtime_observability = isset( $runtime['observability'] ) && is_array( $runtime['observability'] ) ? $runtime['observability'] : [];
        $api_observability = isset( $config['observability'] ) && is_array( $config['observability'] ) ? $config['observability'] : [];

        $log_level = isset( $api_observability['log_level'] )
            ? $api_observability['log_level']
            : ( isset( $runtime_observability['log_level'] ) ? $runtime_observability['log_level'] : 'warning' );
        $log_level = tonbankcard_observability_normalize_level( (string) $log_level );

        $verbose_tracing = isset( $api_observability['verbose_tracing'] )
            ? (bool) $api_observability['verbose_tracing']
            : ( ! empty( $runtime_observability['verbose_tracing'] ) );
        if ( $verbose_tracing && 'off' !== $log_level ) {
            $log_level = 'debug';
        }

        return [
            'log_level'              => $log_level,
            'verbose_tracing'        => $verbose_tracing,
            'client_error_reporting' => isset( $api_observability['client_error_reporting'] )
                ? (bool) $api_observability['client_error_reporting']
                : ( ! array_key_exists( 'client_error_reporting', $runtime_observability ) || (bool) $runtime_observability['client_error_reporting'] ),
            'sink'                   => isset( $api_observability['sink'] ) ? (string) $api_observability['sink'] : 'error_log',
        ];
    }
}

if ( ! function_exists( 'tonbankcard_observability_normalize_level' ) ) {
    /**
     * Returns a supported log level.
     *
     * @param string $level
     * @return string
     */
    function tonbankcard_observability_normalize_level( string $level ) {
        $level = strtolower( trim( $level ) );
        if ( 'warn' === $level ) {
            $level = 'warning';
        }

        return in_array( $level, [ 'debug', 'info', 'warning', 'error', 'critical', 'off' ], TRUE ) ? $level : 'warning';
    }
}

if ( ! function_exists( 'tonbankcard_observability_level_rank' ) ) {
    /**
     * Returns the severity rank for a log level.
     *
     * @param string $level
     * @return int
     */
    function tonbankcard_observability_level_rank( string $level ) {
        $ranks = [
            'debug'    => 10,
            'info'     => 20,
            'warning'  => 30,
            'error'    => 40,
            'critical' => 50,
            'off'      => 999,
        ];

        $level = tonbankcard_observability_normalize_level( $level );
        return $ranks[ $level ];
    }
}

if ( ! function_exists( 'tonbankcard_observability_should_log' ) ) {
    /**
     * Returns TRUE when the event should be emitted at the configured level.
     *
     * @param array $runtime
     * @param array $config
     * @param string $level
     * @return bool
     */
    function tonbankcard_observability_should_log( array $runtime, array $config, string $level ) {
        $settings = tonbankcard_observability_config( $runtime, $config );
        if ( 'off' === $settings['log_level'] ) {
            return FALSE;
        }

        return tonbankcard_observability_level_rank( $level ) >= tonbankcard_observability_level_rank( $settings['log_level'] );
    }
}

if ( ! function_exists( 'tonbankcard_observability_log' ) ) {
    /**
     * Emits one privacy-safe JSON log line.
     *
     * @param array $runtime
     * @param array $config
     * @param string $level
     * @param string $event
     * @param array $context
     * @return void
     */
    function tonbankcard_observability_log( array $runtime, array $config, string $level, string $event, array $context = [] ) {
        $level = tonbankcard_observability_normalize_level( $level );
        if ( ! tonbankcard_observability_should_log( $runtime, $config, $level ) ) {
            return;
        }

        $settings = tonbankcard_observability_config( $runtime, $config );
        $entry = array_merge(
            [
                'timestamp' => gmdate( 'c' ),
                'level'     => $level,
                'event'     => preg_replace( '/[^a-z0-9_.-]/i', '_', $event ),
                'service'   => 'tonbankcard-marketcap',
                'version'   => GECKO_CLIENT_VERSION,
            ],
            tonbankcard_observability_redact( $context )
        );

        $json = json_encode( $entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE );
        if ( FALSE === $json ) {
            $json = '{"timestamp":"' . gmdate( 'c' ) . '","level":"error","event":"observability.encoding_failed","service":"tonbankcard-marketcap"}';
        }

        if ( 'stderr' === $settings['sink'] ) {
            file_put_contents( 'php://stderr', $json . PHP_EOL );
            return;
        }

        error_log( $json );
    }
}

if ( ! function_exists( 'tonbankcard_observability_redact' ) ) {
    /**
     * Redacts sensitive values recursively.
     *
     * @param mixed $value
     * @param string $key
     * @return mixed
     */
    function tonbankcard_observability_redact( $value, string $key = '' ) {
        if ( tonbankcard_observability_sensitive_key( $key ) ) {
            return '[redacted]';
        }

        if ( is_array( $value ) ) {
            $redacted = [];
            foreach ( $value as $entry_key => $entry_value ) {
                $redacted[ $entry_key ] = tonbankcard_observability_redact( $entry_value, (string) $entry_key );
            }
            return $redacted;
        }

        if ( is_string( $value ) ) {
            return tonbankcard_observability_redact_string( $value );
        }

        if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) || null === $value ) {
            return $value;
        }

        return tonbankcard_observability_redact_string( (string) $value );
    }
}

if ( ! function_exists( 'tonbankcard_observability_sensitive_key' ) ) {
    /**
     * Returns TRUE when an array key commonly carries sensitive data.
     *
     * @param string $key
     * @return bool
     */
    function tonbankcard_observability_sensitive_key( string $key ) {
        $normalized = strtolower( str_replace( '-', '_', trim( $key ) ) );
        foreach ( [ 'authorization', 'cookie', 'api_key', 'apikey', 'token', 'secret', 'password', 'init_data', 'initdata', 'session_token', 'rest_token', 'bot_token', 'x_cg_demo_api_key', 'x_cg_pro_api_key' ] as $sensitive ) {
            if ( '' !== $normalized && FALSE !== strpos( $normalized, $sensitive ) ) {
                return TRUE;
            }
        }

        return FALSE;
    }
}

if ( ! function_exists( 'tonbankcard_observability_redact_string' ) ) {
    /**
     * Redacts secrets embedded in strings and bounds log size.
     *
     * @param string $value
     * @return string
     */
    function tonbankcard_observability_redact_string( string $value ) {
        $value = preg_replace( '/(api[_-]?key|x[_-]cg[_-](demo|pro)[_-]api[_-]key|token|password|secret|authorization)=([^&\s]+)/i', '$1=[redacted]', $value );
        $value = preg_replace( '/Bearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer [redacted]', $value );

        if ( 600 < strlen( $value ) ) {
            return substr( $value, 0, 600 ) . '...';
        }

        return $value;
    }
}

if ( ! function_exists( 'tonbankcard_observability_safe_identifier' ) ) {
    /**
     * Returns a safe identifier or null.
     *
     * @param mixed $value
     * @return string|null
     */
    function tonbankcard_observability_safe_identifier( $value ) {
        $candidate = trim( (string) $value );
        return preg_match( '/^[A-Za-z0-9._:-]{1,128}$/', $candidate ) ? $candidate : null;
    }
}

if ( ! function_exists( 'tonbankcard_observability_safe_path' ) ) {
    /**
     * Returns a path-only URL value with sensitive query parameters removed.
     *
     * @param mixed $value
     * @return string|null
     */
    function tonbankcard_observability_safe_path( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return null;
        }

        $is_absolute_url = 1 === preg_match( '/^[A-Za-z][A-Za-z0-9+.-]*:\/\//', $value ) || 0 === strpos( $value, '//' );
        if ( ! $is_absolute_url && 0 !== strpos( $value, '/' ) ) {
            return null;
        }

        $path = parse_url( $value, PHP_URL_PATH );
        if ( FALSE === $path || null === $path || '' === $path ) {
            $path = $is_absolute_url ? '/' : strtok( $value, '?' );
        }

        if ( null === $path || '' === $path ) {
            return null;
        }
        $path = tonbankcard_observability_redact_string( $path );

        $query = parse_url( $value, PHP_URL_QUERY );
        if ( FALSE === $query || null === $query || '' === $query ) {
            return substr( $path, 0, 300 );
        }

        parse_str( $query, $params );
        $keys = [];
        foreach ( array_keys( $params ) as $key ) {
            $key = (string) $key;
            if ( ! tonbankcard_observability_sensitive_key( $key ) && preg_match( '/^[A-Za-z0-9_.-]{1,80}$/', $key ) ) {
                $keys[] = $key;
            }
        }

        return substr( $path . ( empty( $keys ) ? '' : '?keys=' . implode( ',', $keys ) ), 0, 300 );
    }
}

if ( ! function_exists( 'tonbankcard_observability_client_event' ) ) {
    /**
     * Sanitizes a browser-submitted observability event.
     *
     * @param array $payload
     * @param string $ingest_request_id
     * @return array
     */
    function tonbankcard_observability_client_event( array $payload, string $ingest_request_id ) {
        $type = isset( $payload['type'] ) ? strtolower( preg_replace( '/[^a-z0-9_-]/i', '_', (string) $payload['type'] ) ) : 'frontend_error';
        if ( ! in_array( $type, [ 'boot_error', 'unhandled_rejection', 'vue_error', 'api_error', 'resource_error', 'frontend_error' ], TRUE ) ) {
            $type = 'frontend_error';
        }

        $event = [
            'type'              => $type,
            'client_event_id'   => tonbankcard_observability_safe_identifier( isset( $payload['client_event_id'] ) ? $payload['client_event_id'] : '' ),
            'request_id'        => tonbankcard_observability_safe_identifier( isset( $payload['request_id'] ) ? $payload['request_id'] : '' ),
            'ingest_request_id' => $ingest_request_id,
            'message'           => tonbankcard_observability_safe_text( isset( $payload['message'] ) ? $payload['message'] : '' ),
            'source'            => tonbankcard_observability_safe_path( isset( $payload['source'] ) ? $payload['source'] : '' ),
            'url_path'          => tonbankcard_observability_safe_path( isset( $payload['url_path'] ) ? $payload['url_path'] : '' ),
            'route_path'        => tonbankcard_observability_safe_path( isset( $payload['route_path'] ) ? $payload['route_path'] : '' ),
            'api_path'          => tonbankcard_observability_safe_path( isset( $payload['api_path'] ) ? $payload['api_path'] : '' ),
            'method'            => isset( $payload['method'] ) ? strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) $payload['method'] ), 0, 12 ) ) : null,
            'status'            => isset( $payload['status'] ) ? max( 0, (int) $payload['status'] ) : null,
            'error_code'        => isset( $payload['error_code'] ) ? substr( preg_replace( '/[^A-Za-z0-9_.-]/', '_', (string) $payload['error_code'] ), 0, 80 ) : null,
            'duration_ms'       => isset( $payload['duration_ms'] ) ? max( 0, (int) $payload['duration_ms'] ) : null,
            'line'              => isset( $payload['line'] ) ? max( 0, (int) $payload['line'] ) : null,
            'column'            => isset( $payload['column'] ) ? max( 0, (int) $payload['column'] ) : null,
            'component'         => tonbankcard_observability_safe_text( isset( $payload['component'] ) ? $payload['component'] : '', 120 ),
        ];

        if ( null === $event['client_event_id'] ) {
            $event['client_event_id'] = tonbankcard_observability_new_id( 'client' );
        }
        if ( null === $event['request_id'] ) {
            $event['request_id'] = $ingest_request_id;
        }

        return array_filter(
            $event,
            function ( $value ) {
                return null !== $value && '' !== $value;
            }
        );
    }
}

if ( ! function_exists( 'tonbankcard_observability_safe_text' ) ) {
    /**
     * Returns bounded, redacted text.
     *
     * @param mixed $value
     * @param int $max
     * @return string
     */
    function tonbankcard_observability_safe_text( $value, int $max = 300 ) {
        $text = tonbankcard_observability_redact_string( trim( (string) $value ) );
        if ( $max < strlen( $text ) ) {
            return substr( $text, 0, $max ) . '...';
        }

        return $text;
    }
}

if ( ! function_exists( 'tonbankcard_observability_client_event_level' ) ) {
    /**
     * Returns a log level for a browser event.
     *
     * @param array $event
     * @return string
     */
    function tonbankcard_observability_client_event_level( array $event ) {
        if ( 'api_error' === $event['type'] && isset( $event['status'] ) ) {
            return 500 <= (int) $event['status'] ? 'error' : 'warning';
        }

        return in_array( $event['type'], [ 'boot_error', 'unhandled_rejection', 'vue_error' ], TRUE ) ? 'error' : 'warning';
    }
}

if ( ! function_exists( 'tonbankcard_observability_new_id' ) ) {
    /**
     * Returns an opaque event id.
     *
     * @param string $prefix
     * @return string
     */
    function tonbankcard_observability_new_id( string $prefix = 'evt' ) {
        try {
            return $prefix . '-' . bin2hex( random_bytes( 8 ) );
        } catch ( Exception $exception ) {
            return $prefix . '-' . str_replace( '.', '', uniqid( '', TRUE ) );
        }
    }
}

if ( ! function_exists( 'tonbankcard_observability_log_queue_failure' ) ) {
    /**
     * Logs a queue failure using the common operational schema.
     *
     * @param array $runtime
     * @param array $config
     * @param string $request_id
     * @param string $queue
     * @param string $operation
     * @param array $details
     * @return void
     */
    function tonbankcard_observability_log_queue_failure( array $runtime, array $config, string $request_id, string $queue, string $operation, array $details = [] ) {
        tonbankcard_observability_log(
            $runtime,
            $config,
            'warning',
            'queue.failure',
            array_merge(
                $details,
                [
                    'request_id' => $request_id,
                    'queue'      => $queue,
                    'operation'  => $operation,
                ]
            )
        );
    }
}

if ( ! function_exists( 'tonbankcard_observability_log_bot_delivery_failure' ) ) {
    /**
     * Logs a bot delivery failure using the common operational schema.
     *
     * @param array $runtime
     * @param array $config
     * @param string $request_id
     * @param string $provider
     * @param string $operation
     * @param array $details
     * @return void
     */
    function tonbankcard_observability_log_bot_delivery_failure( array $runtime, array $config, string $request_id, string $provider, string $operation, array $details = [] ) {
        tonbankcard_observability_log(
            $runtime,
            $config,
            'warning',
            'bot.delivery_failure',
            array_merge(
                $details,
                [
                    'request_id' => $request_id,
                    'provider'   => $provider,
                    'operation'  => $operation,
                ]
            )
        );
    }
}
