<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 API ROUTER
 * -------------------------------------------------------------------------
 * Small JSON API front controller used by the website and Telegram Mini App.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

require_once __DIR__ . '/market.php';

/**
 * Returns TRUE when the current or supplied path belongs to the API surface.
 *
 * @param string|null $path
 * @return bool
 */
function tonbankcard_api_is_request( $path = null ) {
    if ( null === $path ) {
        $path = parse_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH );
    }

    if ( FALSE === $path || null === $path || '' === $path ) {
        $path = '/';
    }

    return '/api' === $path || 0 === strpos( $path, '/api/' );
}

/**
 * Dispatches an API request from PHP globals and exits.
 *
 * @param array $invalid_configs
 * @return void
 */
function tonbankcard_api_dispatch( array $invalid_configs = [] ) {
    $response = tonbankcard_api_handle(
        tonbankcard_api_request_from_globals(),
        $invalid_configs,
        isset( $GLOBALS['runtime_config'] ) ? $GLOBALS['runtime_config'] : [],
        isset( $GLOBALS['api'] ) ? $GLOBALS['api'] : []
    );

    tonbankcard_api_emit_response( $response );
    exit;
}

/**
 * Builds a normalized request from PHP globals.
 *
 * @return array
 */
function tonbankcard_api_request_from_globals() {
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/';
    $path = parse_url( $request_uri, PHP_URL_PATH );
    if ( FALSE === $path || null === $path || '' === $path ) {
        $path = '/';
    }
    $query_string = parse_url( $request_uri, PHP_URL_QUERY );
    $query = [];
    if ( FALSE !== $query_string && null !== $query_string && '' !== $query_string ) {
        parse_str( $query_string, $query );
    }
    $body = file_get_contents( 'php://input' );

    return [
        'method'  => isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET',
        'path'    => $path,
        'query'   => $query,
        'headers' => tonbankcard_api_headers_from_globals(),
        'body'    => FALSE === $body ? '' : $body,
    ];
}

/**
 * Returns request headers using lower-case keys.
 *
 * @return array
 */
function tonbankcard_api_headers_from_globals() {
    $headers = [];

    if ( function_exists( 'getallheaders' ) ) {
        $raw_headers = getallheaders();
        if ( is_array( $raw_headers ) ) {
            foreach ( $raw_headers as $name => $value ) {
                $headers[ strtolower( $name ) ] = $value;
            }
        }
    }

    foreach ( $_SERVER as $name => $value ) {
        if ( 0 === strpos( $name, 'HTTP_' ) ) {
            $header_name = strtolower( str_replace( '_', '-', substr( $name, 5 ) ) );
            $headers[ $header_name ] = $value;
        }
    }

    if ( isset( $_SERVER['CONTENT_TYPE'] ) ) {
        $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
    }
    if ( isset( $_SERVER['CONTENT_LENGTH'] ) ) {
        $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
    }

    return $headers;
}

/**
 * Handles a normalized API request and returns a serializable response.
 *
 * @param array $request
 * @param array $invalid_configs
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_handle( array $request, array $invalid_configs = [], array $runtime = [], array $config = [] ) {
    $runtime = empty( $runtime ) && isset( $GLOBALS['runtime_config'] ) ? $GLOBALS['runtime_config'] : $runtime;
    $config  = empty( $config ) && isset( $GLOBALS['api'] ) ? $GLOBALS['api'] : $config;
    $request = tonbankcard_api_normalize_request( $request );

    $request_id = tonbankcard_api_request_id( $request['headers'] );
    $headers    = tonbankcard_api_base_headers( $request, $config, $request_id );

    if ( 'OPTIONS' === $request['method'] ) {
        return [
            'status'  => 204,
            'headers' => $headers,
            'body'    => '',
        ];
    }

    $body_error = tonbankcard_api_validate_json_body( $request );
    if ( null !== $body_error ) {
        if ( 'unsupported_media_type' === $body_error ) {
            return tonbankcard_api_error_response(
                415,
                'unsupported_media_type',
                'Request body must use application/json.',
                [ 'hint' => 'Send Content-Type: application/json or omit the request body.' ],
                $request_id,
                $headers
            );
        }

        return tonbankcard_api_error_response(
            400,
            'invalid_json',
            'Request body must be valid JSON.',
            [ 'hint' => 'Send a JSON object or omit the request body.' ],
            $request_id,
            $headers
        );
    }

    $context = tonbankcard_api_middleware_context( $request, $runtime, $config, $request_id );
    $path    = tonbankcard_api_normalize_path( $request['path'] );

    if ( '/api' === $path || '/api/' === $path ) {
        if ( 'GET' !== $request['method'] ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            [
                'service'    => 'tonbankcard-api',
                'version'    => GECKO_CLIENT_VERSION,
                'routes'     => [
                    '/api/health',
                    '/api/ready',
                    '/api/market',
                    '/api/market/*',
                ],
                'middleware' => $context['hooks'],
            ],
            $request_id,
            $headers
        );
    }

    if ( '/api/health' === $path ) {
        if ( 'GET' !== $request['method'] ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            tonbankcard_api_health_payload( $runtime, $config, $invalid_configs, FALSE ),
            $request_id,
            $headers
        );
    }

    if ( '/api/ready' === $path ) {
        if ( 'GET' !== $request['method'] ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        $payload = tonbankcard_api_health_payload( $runtime, $config, $invalid_configs, TRUE );
        if ( ! empty( $payload['ready'] ) ) {
            return tonbankcard_api_success_response( $payload, $request_id, $headers );
        }

        return tonbankcard_api_error_response(
            503,
            'not_ready',
            'API dependencies are not ready.',
            [ 'checks' => $payload['checks'] ],
            $request_id,
            $headers
        );
    }

    if ( tonbankcard_api_market_is_request( $path ) ) {
        return tonbankcard_api_market_handle( $request, $runtime, $config, $request_id, $headers );
    }

    return tonbankcard_api_error_response(
        404,
        'not_found',
        'No API route matches the request.',
        [ 'path' => $path ],
        $request_id,
        $headers
    );
}

/**
 * Emits a response returned by tonbankcard_api_handle().
 *
 * @param array $response
 * @return void
 */
function tonbankcard_api_emit_response( array $response ) {
    $status = isset( $response['status'] ) ? (int) $response['status'] : 500;
    http_response_code( $status );

    foreach ( isset( $response['headers'] ) ? $response['headers'] : [] as $name => $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $single_value ) {
                header( $name . ': ' . $single_value, FALSE );
            }
        } else {
            header( $name . ': ' . $value );
        }
    }

    echo isset( $response['body'] ) ? $response['body'] : '';
}

/**
 * Normalizes request method, path, headers, and body.
 *
 * @param array $request
 * @return array
 */
function tonbankcard_api_normalize_request( array $request ) {
    $headers = [];
    foreach ( isset( $request['headers'] ) ? $request['headers'] : [] as $name => $value ) {
        $headers[ strtolower( $name ) ] = is_array( $value ) ? implode( ',', $value ) : (string) $value;
    }

    $path = isset( $request['path'] ) ? (string) $request['path'] : '/';
    $path_query = parse_url( $path, PHP_URL_QUERY );
    $query = [];
    if ( FALSE !== $path_query && null !== $path_query && '' !== $path_query ) {
        parse_str( $path_query, $query );
    }
    if ( isset( $request['query'] ) && is_array( $request['query'] ) ) {
        $query = array_merge( $query, $request['query'] );
    }
    $path_only = parse_url( $path, PHP_URL_PATH );
    if ( FALSE === $path_only || null === $path_only || '' === $path_only ) {
        $path_only = '/';
    }

    return [
        'method'  => strtoupper( isset( $request['method'] ) ? (string) $request['method'] : 'GET' ),
        'path'    => $path_only,
        'query'   => $query,
        'headers' => $headers,
        'body'    => isset( $request['body'] ) ? (string) $request['body'] : '',
    ];
}

/**
 * Normalizes API route paths without removing the /api prefix.
 *
 * @param string $path
 * @return string
 */
function tonbankcard_api_normalize_path( string $path ) {
    $normalized = parse_url( $path, PHP_URL_PATH );
    if ( FALSE === $normalized || null === $normalized || '' === $normalized ) {
        $normalized = '/';
    }

    if ( '/' !== $normalized ) {
        $normalized = rtrim( $normalized, '/' );
    }

    return $normalized;
}

/**
 * Returns a safe request id from headers or generates one.
 *
 * @param array $headers
 * @return string
 */
function tonbankcard_api_request_id( array $headers ) {
    $candidate = '';
    if ( isset( $headers['x-request-id'] ) ) {
        $candidate = trim( (string) $headers['x-request-id'] );
    } elseif ( isset( $headers['x-correlation-id'] ) ) {
        $candidate = trim( (string) $headers['x-correlation-id'] );
    }

    if ( preg_match( '/^[A-Za-z0-9._:-]{1,128}$/', $candidate ) ) {
        return $candidate;
    }

    try {
        return bin2hex( random_bytes( 16 ) );
    } catch ( Exception $exception ) {
        return str_replace( '.', '', uniqid( 'req_', TRUE ) );
    }
}

/**
 * Builds headers shared by all API responses.
 *
 * @param array $request
 * @param array $config
 * @param string $request_id
 * @return array
 */
function tonbankcard_api_base_headers( array $request, array $config, string $request_id ) {
    return array_merge(
        [
            'Content-Type'  => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-store',
            'X-Request-ID'  => $request_id,
        ],
        tonbankcard_api_cors_headers( $request, $config )
    );
}

/**
 * Builds CORS response headers for configured origins.
 *
 * @param array $request
 * @param array $config
 * @return array
 */
function tonbankcard_api_cors_headers( array $request, array $config ) {
    $cors = isset( $config['cors'] ) && is_array( $config['cors'] ) ? $config['cors'] : [];
    $origin = isset( $request['headers']['origin'] ) ? rtrim( trim( $request['headers']['origin'] ), '/' ) : '';
    $allowed_origins = isset( $cors['allowed_origins'] ) && is_array( $cors['allowed_origins'] ) ? $cors['allowed_origins'] : [];

    $headers = [
        'Vary'                         => 'Origin',
        'Access-Control-Allow-Methods' => implode( ', ', isset( $cors['allowed_methods'] ) ? $cors['allowed_methods'] : [ 'GET', 'POST', 'OPTIONS' ] ),
        'Access-Control-Allow-Headers' => implode( ', ', isset( $cors['allowed_headers'] ) ? $cors['allowed_headers'] : [ 'Content-Type', 'X-Request-ID' ] ),
        'Access-Control-Expose-Headers' => implode( ', ', isset( $cors['exposed_headers'] ) ? $cors['exposed_headers'] : [ 'X-Request-ID' ] ),
        'Access-Control-Max-Age'       => (string) ( isset( $cors['max_age'] ) ? (int) $cors['max_age'] : 600 ),
    ];

    if ( '' !== $origin && in_array( $origin, $allowed_origins, TRUE ) ) {
        $headers['Access-Control-Allow-Origin'] = $origin;
        if ( ! empty( $cors['supports_credentials'] ) ) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }
    }

    return $headers;
}

/**
 * Validates JSON request bodies before route handling.
 *
 * @param array $request
 * @return string|null
 */
function tonbankcard_api_validate_json_body( array $request ) {
    if ( '' === trim( $request['body'] ) ) {
        return null;
    }

    $content_type = isset( $request['headers']['content-type'] ) ? strtolower( $request['headers']['content-type'] ) : '';
    if ( FALSE === strpos( $content_type, 'application/json' ) ) {
        return 'unsupported_media_type';
    }

    json_decode( $request['body'], TRUE );
    if ( JSON_ERROR_NONE !== json_last_error() ) {
        return 'invalid_json';
    }

    return null;
}

/**
 * Builds safe middleware context for route handlers and diagnostics.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @return array
 */
function tonbankcard_api_middleware_context( array $request, array $runtime, array $config, string $request_id ) {
    return [
        'request_id' => $request_id,
        'session'    => tonbankcard_api_session_context( $request ),
        'rate_limit' => tonbankcard_api_rate_limit_context( $config ),
        'audit'      => tonbankcard_api_audit_context( $config ),
        'hooks'      => [
            'request_ids'  => TRUE,
            'cors'         => TRUE,
            'sessions'     => TRUE,
            'rate_limits'  => isset( $config['rate_limit']['enabled'] ) ? (bool) $config['rate_limit']['enabled'] : FALSE,
            'validation'   => TRUE,
            'audit_logging' => isset( $config['audit']['enabled'] ) ? (bool) $config['audit']['enabled'] : FALSE,
        ],
    ];
}

/**
 * Returns a safe session context without exposing bearer or cookie values.
 *
 * @param array $request
 * @return array
 */
function tonbankcard_api_session_context( array $request ) {
    if ( ! empty( $request['headers']['authorization'] ) ) {
        return [
            'state'  => 'present',
            'source' => 'authorization',
        ];
    }

    if ( ! empty( $request['headers']['cookie'] ) && FALSE !== strpos( $request['headers']['cookie'], 'tonbankcard_session=' ) ) {
        return [
            'state'  => 'present',
            'source' => 'cookie',
        ];
    }

    return [
        'state'  => 'anonymous',
        'source' => null,
    ];
}

/**
 * Returns configured rate limit policy. Enforcement is added by later issues.
 *
 * @param array $config
 * @return array
 */
function tonbankcard_api_rate_limit_context( array $config ) {
    $rate_limit = isset( $config['rate_limit'] ) && is_array( $config['rate_limit'] ) ? $config['rate_limit'] : [];

    return [
        'enabled'        => ! empty( $rate_limit['enabled'] ),
        'state'          => empty( $rate_limit['enabled'] ) ? 'not_enforced' : 'configured',
        'window_seconds' => isset( $rate_limit['window_seconds'] ) ? (int) $rate_limit['window_seconds'] : 60,
        'max_requests'   => isset( $rate_limit['max_requests'] ) ? (int) $rate_limit['max_requests'] : 60,
    ];
}

/**
 * Returns audit logging policy. Logging is disabled unless explicitly enabled.
 *
 * @param array $config
 * @return array
 */
function tonbankcard_api_audit_context( array $config ) {
    $audit = isset( $config['audit'] ) && is_array( $config['audit'] ) ? $config['audit'] : [];

    return [
        'enabled' => ! empty( $audit['enabled'] ),
        'sink'    => isset( $audit['sink'] ) ? (string) $audit['sink'] : 'error_log',
    ];
}

/**
 * Builds the health/readiness payload.
 *
 * @param array $runtime
 * @param array $config
 * @param array $invalid_configs
 * @param bool $readiness
 * @return array
 */
function tonbankcard_api_health_payload( array $runtime, array $config, array $invalid_configs, bool $readiness ) {
    $checks = [
        'app_boot'           => [
            'status'  => 'ok',
            'message' => 'Application bootstrap completed.',
        ],
        'configuration'      => tonbankcard_api_configuration_check( $invalid_configs ),
        'database'           => tonbankcard_api_database_check( $runtime, $config ),
        'redis'              => tonbankcard_api_redis_check( $runtime, $config ),
        'upstream_providers' => tonbankcard_api_upstream_provider_check( $runtime, $config ),
    ];

    $ready = empty( $invalid_configs );
    foreach ( [ 'database', 'redis', 'upstream_providers' ] as $check_name ) {
        if ( isset( $checks[ $check_name ]['required'] ) && TRUE === $checks[ $check_name ]['required'] && 'fail' === $checks[ $check_name ]['status'] ) {
            $ready = FALSE;
        }
    }

    return [
        'service' => 'tonbankcard-api',
        'version' => GECKO_CLIENT_VERSION,
        'profile' => isset( $runtime['profile'] ) ? $runtime['profile'] : 'local',
        'status'  => $ready ? 'ok' : 'degraded',
        'ready'   => $readiness ? $ready : null,
        'checks'  => $checks,
    ];
}

/**
 * Returns a safe configuration check.
 *
 * @param array $invalid_configs
 * @return array
 */
function tonbankcard_api_configuration_check( array $invalid_configs ) {
    if ( empty( $invalid_configs ) ) {
        return [
            'status'  => 'ok',
            'message' => 'Runtime configuration is valid for the active profile.',
        ];
    }

    $names = [];
    foreach ( $invalid_configs as $entry ) {
        if ( ! empty( $entry['config'] ) ) {
            $names[] = $entry['config'];
        } elseif ( ! empty( $entry['constant'] ) ) {
            $names[] = $entry['constant'];
        }
    }

    return [
        'status'  => 'fail',
        'message' => 'Runtime configuration has actionable errors.',
        'errors'  => array_values( array_unique( $names ) ),
    ];
}

/**
 * Returns database availability metadata without leaking credentials.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_database_check( array $runtime, array $config ) {
    $mysql = isset( $runtime['providers']['mysql'] ) && is_array( $runtime['providers']['mysql'] ) ? $runtime['providers']['mysql'] : [];
    $profile = isset( $runtime['profile'] ) ? $runtime['profile'] : 'local';
    $required = 'local' !== $profile;

    if ( empty( $mysql['dsn'] ) ) {
        return [
            'status'     => $required ? 'fail' : 'not_configured',
            'required'   => $required,
            'configured' => FALSE,
            'available'  => FALSE,
            'message'    => $required ? 'MySQL or MariaDB DSN is required for this profile.' : 'MySQL or MariaDB DSN is not configured for local development.',
        ];
    }

    if ( empty( $config['readiness']['active_checks'] ) ) {
        return [
            'status'     => 'configured',
            'required'   => $required,
            'configured' => TRUE,
            'available'  => null,
            'message'    => 'Database is configured; active connection probe is disabled.',
        ];
    }

    if ( ! class_exists( 'PDO' ) ) {
        return [
            'status'     => 'fail',
            'required'   => $required,
            'configured' => TRUE,
            'available'  => FALSE,
            'message'    => 'PDO is not available for database readiness probes.',
        ];
    }

    try {
        new PDO(
            $mysql['dsn'],
            isset( $mysql['user'] ) ? $mysql['user'] : '',
            isset( $mysql['password'] ) ? $mysql['password'] : '',
            [ PDO::ATTR_TIMEOUT => isset( $config['readiness']['timeout_seconds'] ) ? (int) $config['readiness']['timeout_seconds'] : 2 ]
        );

        return [
            'status'     => 'ok',
            'required'   => $required,
            'configured' => TRUE,
            'available'  => TRUE,
            'message'    => 'Database connection probe succeeded.',
        ];
    } catch ( Exception $exception ) {
        return [
            'status'     => 'fail',
            'required'   => $required,
            'configured' => TRUE,
            'available'  => FALSE,
            'message'    => 'Database connection probe failed.',
        ];
    }
}

/**
 * Returns Redis availability metadata without leaking tokens.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_redis_check( array $runtime, array $config ) {
    $upstash = isset( $runtime['providers']['upstash'] ) && is_array( $runtime['providers']['upstash'] ) ? $runtime['providers']['upstash'] : [];
    $profile = isset( $runtime['profile'] ) ? $runtime['profile'] : 'local';
    $required = 'local' !== $profile;

    if ( empty( $upstash['rest_url'] ) || empty( $upstash['rest_token'] ) ) {
        return [
            'status'     => $required ? 'fail' : 'not_configured',
            'required'   => $required,
            'configured' => FALSE,
            'available'  => FALSE,
            'message'    => $required ? 'Upstash Redis REST URL and token are required for this profile.' : 'Upstash Redis is not configured for local development.',
        ];
    }

    if ( empty( $config['readiness']['active_checks'] ) || ! function_exists( 'curl_init' ) ) {
        return [
            'status'     => 'configured',
            'required'   => $required,
            'configured' => TRUE,
            'available'  => null,
            'message'    => 'Redis is configured; active REST probe is disabled or curl is unavailable.',
        ];
    }

    $url = rtrim( $upstash['rest_url'], '/' ) . '/ping';
    $handle = curl_init( $url );
    if ( FALSE === $handle ) {
        return [
            'status'     => 'fail',
            'required'   => $required,
            'configured' => TRUE,
            'available'  => FALSE,
            'message'    => 'Redis readiness probe could not start.',
        ];
    }

    curl_setopt_array(
        $handle,
        [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => isset( $config['readiness']['timeout_seconds'] ) ? (int) $config['readiness']['timeout_seconds'] : 2,
            CURLOPT_HTTPHEADER     => [ 'Authorization: Bearer ' . $upstash['rest_token'] ],
        ]
    );
    curl_exec( $handle );
    $status = (int) curl_getinfo( $handle, CURLINFO_HTTP_CODE );
    curl_close( $handle );

    return [
        'status'     => $status >= 200 && $status < 300 ? 'ok' : 'fail',
        'required'   => $required,
        'configured' => TRUE,
        'available'  => $status >= 200 && $status < 300,
        'message'    => $status >= 200 && $status < 300 ? 'Redis REST probe succeeded.' : 'Redis REST probe failed.',
    ];
}

/**
 * Returns upstream provider readiness metadata without exposing keys.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_upstream_provider_check( array $runtime, array $config ) {
    $providers = isset( $runtime['providers'] ) && is_array( $runtime['providers'] ) ? $runtime['providers'] : [];
    $features = isset( $runtime['feature_flags'] ) && is_array( $runtime['feature_flags'] ) ? $runtime['feature_flags'] : [];

    $checks = [
        'coingecko' => [
            'status'     => ! empty( $providers['coingecko']['api_key_configured'] ) ? 'configured' : 'keyless',
            'required'   => FALSE,
            'configured' => ! empty( $providers['coingecko']['api_key_configured'] ),
            'available'  => null,
            'message'    => ! empty( $providers['coingecko']['api_key_configured'] ) ? 'CoinGecko API key is configured server-side for the market data gateway.' : 'CoinGecko gateway uses keyless public Demo API access until a server-side key is configured.',
        ],
        'groq'      => [
            'status'     => ! empty( $providers['groq']['api_key_configured'] ) ? 'configured' : ( ! empty( $features['ai'] ) ? 'fail' : 'not_configured' ),
            'required'   => ! empty( $features['ai'] ),
            'configured' => ! empty( $providers['groq']['api_key_configured'] ),
            'available'  => null,
            'message'    => ! empty( $features['ai'] ) ? 'Groq API key is required when AI features are enabled.' : 'Groq is optional while AI features are disabled.',
        ],
        'changenow' => [
            'status'     => ! empty( $providers['changenow']['link_id'] ) ? 'configured' : ( ! empty( $features['changenow'] ) ? 'fail' : 'not_configured' ),
            'required'   => ! empty( $features['changenow'] ),
            'configured' => ! empty( $providers['changenow']['link_id'] ),
            'available'  => null,
            'message'    => ! empty( $features['changenow'] ) ? 'ChangeNOW link id is required when the exchange widget is enabled.' : 'ChangeNOW is optional while the feature flag is disabled.',
        ],
    ];

    $status = 'ok';
    $required = FALSE;
    foreach ( $checks as $check ) {
        if ( ! empty( $check['required'] ) ) {
            $required = TRUE;
        }
        if ( ! empty( $check['required'] ) && 'fail' === $check['status'] ) {
            $status = 'fail';
        }
    }

    return [
        'status'   => $status,
        'required' => $required,
        'checks'   => $checks,
    ];
}

/**
 * Builds a JSON success response.
 *
 * @param array $data
 * @param string $request_id
 * @param array $headers
 * @param int $status
 * @return array
 */
function tonbankcard_api_success_response( array $data, string $request_id, array $headers, int $status = 200, array $meta = [] ) {
    return [
        'status'  => $status,
        'headers' => $headers,
        'body'    => tonbankcard_api_encode(
            [
                'ok'   => TRUE,
                'data' => $data,
                'meta' => array_merge(
                    [
                        'request_id' => $request_id,
                    ],
                    $meta
                ),
            ]
        ),
    ];
}

/**
 * Builds a JSON error response.
 *
 * @param int $status
 * @param string $code
 * @param string $message
 * @param array $details
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_error_response( int $status, string $code, string $message, array $details, string $request_id, array $headers ) {
    return [
        'status'  => $status,
        'headers' => $headers,
        'body'    => tonbankcard_api_encode(
            [
                'ok'    => FALSE,
                'error' => [
                    'code'    => $code,
                    'message' => $message,
                    'details' => $details,
                ],
                'meta'  => [
                    'request_id' => $request_id,
                ],
            ]
        ),
    ];
}

/**
 * Builds a method-not-allowed response.
 *
 * @param array $allowed_methods
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_method_not_allowed_response( array $allowed_methods, string $request_id, array $headers ) {
    $headers['Allow'] = implode( ', ', $allowed_methods );

    return tonbankcard_api_error_response(
        405,
        'method_not_allowed',
        'The API route does not support this HTTP method.',
        [ 'allowed_methods' => $allowed_methods ],
        $request_id,
        $headers
    );
}

/**
 * Encodes response payloads consistently.
 *
 * @param array $payload
 * @return string
 */
function tonbankcard_api_encode( array $payload ) {
    $json = json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE );
    if ( FALSE === $json ) {
        return '{"ok":false,"error":{"code":"encoding_failed","message":"Response encoding failed.","details":[]},"meta":{"request_id":"unknown"}}';
    }

    return $json;
}
