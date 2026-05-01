<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 API ROUTER
 * -------------------------------------------------------------------------
 * Small JSON API front controller used by the website and Telegram Mini App.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

require_once __DIR__ . '/observability.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/market.php';
require_once __DIR__ . '/share.php';
require_once __DIR__ . '/ton.php';
require_once __DIR__ . '/ai.php';
require_once __DIR__ . '/search.php';
require_once __DIR__ . '/watchlist.php';
require_once __DIR__ . '/alerts.php';
require_once __DIR__ . '/screener.php';

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
    $started_at = microtime( TRUE );

    $request_id = tonbankcard_api_request_id( $request['headers'] );
    $headers    = tonbankcard_api_base_headers( $request, $config, $request_id );

    try {
        if ( 'OPTIONS' === $request['method'] ) {
            return tonbankcard_api_finalize_response(
                [
                    'status'  => 204,
                    'headers' => $headers,
                    'body'    => '',
                ],
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        $rate_limit = tonbankcard_api_rate_limit_check( $request, $runtime, $config );
        if ( ! empty( $rate_limit['headers'] ) && is_array( $rate_limit['headers'] ) ) {
            $headers = array_merge( $headers, $rate_limit['headers'] );
        }
        if ( empty( $rate_limit['allowed'] ) ) {
            return tonbankcard_api_finalize_response(
                tonbankcard_api_error_response(
                    429,
                    'rate_limited',
                    'Too many requests. Try again after the retry window resets.',
                    [
                        'policy'              => isset( $rate_limit['policy'] ) ? $rate_limit['policy'] : 'anonymous_web',
                        'limit'               => isset( $rate_limit['limit'] ) ? (int) $rate_limit['limit'] : null,
                        'window_seconds'      => isset( $rate_limit['window'] ) ? (int) $rate_limit['window'] : null,
                        'retry_after_seconds' => isset( $rate_limit['retry_after'] ) ? (int) $rate_limit['retry_after'] : null,
                    ],
                    $request_id,
                    $headers
                ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        $body_error = tonbankcard_api_validate_json_body( $request );
        if ( null !== $body_error ) {
            if ( 'unsupported_media_type' === $body_error ) {
                return tonbankcard_api_finalize_response(
                    tonbankcard_api_error_response(
                        415,
                        'unsupported_media_type',
                        'Request body must use application/json.',
                        [ 'hint' => 'Send Content-Type: application/json or omit the request body.' ],
                        $request_id,
                        $headers
                    ),
                    $request,
                    $runtime,
                    $config,
                    $request_id,
                    $started_at
                );
            }

            return tonbankcard_api_finalize_response(
                tonbankcard_api_error_response(
                    400,
                    'invalid_json',
                    'Request body must be valid JSON.',
                    [ 'hint' => 'Send a JSON object or omit the request body.' ],
                    $request_id,
                    $headers
                ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        $context = tonbankcard_api_middleware_context( $request, $runtime, $config, $request_id );
        $path    = tonbankcard_api_normalize_path( $request['path'] );

        if ( '/api' === $path || '/api/' === $path ) {
            if ( 'GET' !== $request['method'] ) {
                return tonbankcard_api_finalize_response(
                    tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers ),
                    $request,
                    $runtime,
                    $config,
                    $request_id,
                    $started_at
                );
            }

            return tonbankcard_api_finalize_response(
                tonbankcard_api_success_response(
                    [
                        'service'    => 'tonbankcard-api',
                        'version'    => GECKO_CLIENT_VERSION,
                        'routes'     => [
                            '/api/health',
                            '/api/metrics',
                            '/api/ready',
                            '/api/ai',
                            '/api/ai/insight',
                            '/api/ai/feedback',
                            '/api/ai/sentiment-inputs',
                            '/api/search',
                            '/api/search/refresh',
                            '/api/ton',
                            '/api/ton/assets',
                            '/api/share/resolve',
                            '/api/market',
                            '/api/market/*',
                            '/api/watchlist',
                            '/api/watchlist/{coin_id}',
                            '/api/alerts',
                            '/api/alerts/{id}',
                            '/api/alerts/{id}/test',
                            '/api/alerts/evaluate',
                            '/api/screener',
                            '/api/screener/markets',
                            '/api/screener/presets',
                            '/api/screener/presets/{id}',
                            '/api/observability/client-error',
                            '/api/telegram/session',
                        ],
                        'middleware' => $context['hooks'],
                    ],
                    $request_id,
                    $headers
                ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( '/api/metrics' === $path ) {
            if ( 'GET' !== $request['method'] ) {
                return tonbankcard_api_finalize_response(
                    tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers ),
                    $request,
                    $runtime,
                    $config,
                    $request_id,
                    $started_at
                );
            }

            return tonbankcard_api_finalize_response(
                tonbankcard_api_success_response(
                    tonbankcard_api_metrics_payload( $runtime, $config ),
                    $request_id,
                    $headers
                ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( '/api/observability/client-error' === $path ) {
            if ( 'POST' !== $request['method'] ) {
                return tonbankcard_api_finalize_response(
                    tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers ),
                    $request,
                    $runtime,
                    $config,
                    $request_id,
                    $started_at
                );
            }

            return tonbankcard_api_finalize_response(
                tonbankcard_api_client_error_response( $request, $runtime, $config, $request_id, $headers ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( '/api/telegram/session' === $path ) {
            if ( 'POST' !== $request['method'] ) {
                return tonbankcard_api_finalize_response(
                    tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers ),
                    $request,
                    $runtime,
                    $config,
                    $request_id,
                    $started_at
                );
            }

            return tonbankcard_api_finalize_response(
                tonbankcard_api_telegram_session_response( $request, $runtime, $config, $request_id, $headers ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( '/api/health' === $path ) {
            if ( 'GET' !== $request['method'] ) {
                return tonbankcard_api_finalize_response(
                    tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers ),
                    $request,
                    $runtime,
                    $config,
                    $request_id,
                    $started_at
                );
            }

            return tonbankcard_api_finalize_response(
                tonbankcard_api_success_response(
                    tonbankcard_api_health_payload( $runtime, $config, $invalid_configs, FALSE ),
                    $request_id,
                    $headers
                ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( '/api/ready' === $path ) {
            if ( 'GET' !== $request['method'] ) {
                return tonbankcard_api_finalize_response(
                    tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers ),
                    $request,
                    $runtime,
                    $config,
                    $request_id,
                    $started_at
                );
            }

            $payload = tonbankcard_api_health_payload( $runtime, $config, $invalid_configs, TRUE );
            if ( ! empty( $payload['ready'] ) ) {
                return tonbankcard_api_finalize_response(
                    tonbankcard_api_success_response( $payload, $request_id, $headers ),
                    $request,
                    $runtime,
                    $config,
                    $request_id,
                    $started_at
                );
            }

            return tonbankcard_api_finalize_response(
                tonbankcard_api_error_response(
                    503,
                    'not_ready',
                    'API dependencies are not ready.',
                    [ 'checks' => $payload['checks'] ],
                    $request_id,
                    $headers
                ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( function_exists( 'tonbankcard_api_ai_is_request' ) && tonbankcard_api_ai_is_request( $path ) ) {
            return tonbankcard_api_finalize_response(
                tonbankcard_api_ai_handle( $request, $runtime, $config, $request_id, $headers ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( tonbankcard_api_search_is_request( $path ) ) {
            return tonbankcard_api_finalize_response(
                tonbankcard_api_search_handle( $request, $runtime, $config, $request_id, $headers ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( tonbankcard_api_ton_is_request( $path ) ) {
            return tonbankcard_api_finalize_response(
                tonbankcard_api_ton_handle( $request, $runtime, $config, $request_id, $headers ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( tonbankcard_api_share_is_request( $path ) ) {
            return tonbankcard_api_finalize_response(
                tonbankcard_api_share_handle( $request, $runtime, $config, $request_id, $headers ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( tonbankcard_api_watchlist_is_request( $path ) ) {
            return tonbankcard_api_finalize_response(
                tonbankcard_api_watchlist_handle( $request, $runtime, $config, $request_id, $headers ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( tonbankcard_api_alerts_is_request( $path ) ) {
            return tonbankcard_api_finalize_response(
                tonbankcard_api_alerts_handle( $request, $runtime, $config, $request_id, $headers ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( tonbankcard_api_screener_is_request( $path ) ) {
            return tonbankcard_api_finalize_response(
                tonbankcard_api_screener_handle( $request, $runtime, $config, $request_id, $headers ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        if ( tonbankcard_api_market_is_request( $path ) ) {
            return tonbankcard_api_finalize_response(
                tonbankcard_api_market_handle( $request, $runtime, $config, $request_id, $headers ),
                $request,
                $runtime,
                $config,
                $request_id,
                $started_at
            );
        }

        return tonbankcard_api_finalize_response(
            tonbankcard_api_error_response(
                404,
                'not_found',
                'No API route matches the request.',
                [ 'path' => $path ],
                $request_id,
                $headers
            ),
            $request,
            $runtime,
            $config,
            $request_id,
            $started_at
        );
    } catch ( Throwable $exception ) {
        tonbankcard_observability_log(
            $runtime,
            $config,
            'error',
            'api.unhandled_exception',
            [
                'request_id'      => $request_id,
                'method'          => $request['method'],
                'path'            => tonbankcard_api_normalize_path( $request['path'] ),
                'exception_class' => get_class( $exception ),
            ]
        );

        return tonbankcard_api_finalize_response(
            tonbankcard_api_error_response(
                500,
                'internal_error',
                'The API request failed unexpectedly.',
                [],
                $request_id,
                $headers
            ),
            $request,
            $runtime,
            $config,
            $request_id,
            $started_at
        );
    }
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
 * Logs and returns a finished API response.
 *
 * @param array $response
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param float $started_at
 * @return array
 */
function tonbankcard_api_finalize_response( array $response, array $request, array $runtime, array $config, string $request_id, float $started_at ) {
    $status = isset( $response['status'] ) ? (int) $response['status'] : 500;
    $error_code = tonbankcard_api_response_error_code( $response );
    $level = 500 <= $status ? 'error' : ( 400 <= $status ? 'warning' : 'info' );

    tonbankcard_observability_log(
        $runtime,
        $config,
        $level,
        'api.request_completed',
        [
            'request_id'  => $request_id,
            'method'      => $request['method'],
            'path'        => tonbankcard_api_normalize_path( $request['path'] ),
            'route_group' => tonbankcard_api_route_group( $request['path'] ),
            'status'      => $status,
            'error_code'  => $error_code,
            'duration_ms' => max( 0, (int) round( ( microtime( TRUE ) - $started_at ) * 1000 ) ),
        ]
    );

    return $response;
}

/**
 * Extracts a safe error code from a response body.
 *
 * @param array $response
 * @return string|null
 */
function tonbankcard_api_response_error_code( array $response ) {
    $body = isset( $response['body'] ) ? (string) $response['body'] : '';
    if ( '' === $body ) {
        return null;
    }

    $payload = json_decode( $body, TRUE );
    if ( ! is_array( $payload ) || empty( $payload['error']['code'] ) ) {
        return null;
    }

    return substr( preg_replace( '/[^A-Za-z0-9_.-]/', '_', (string) $payload['error']['code'] ), 0, 80 );
}

/**
 * Returns a coarse route group for operational dashboards.
 *
 * @param string $path
 * @return string
 */
function tonbankcard_api_route_group( string $path ) {
    $path = tonbankcard_api_normalize_path( $path );
    if ( tonbankcard_api_market_is_request( $path ) ) {
        return 'market';
    }
    if ( tonbankcard_api_ai_is_request( $path ) ) {
        return 'ai';
    }
    if ( tonbankcard_api_search_is_request( $path ) ) {
        return 'search';
    }
    if ( tonbankcard_api_ton_is_request( $path ) ) {
        return 'ton';
    }
    if ( tonbankcard_api_share_is_request( $path ) ) {
        return 'share';
    }
    if ( tonbankcard_api_watchlist_is_request( $path ) ) {
        // Server writes rely on uniq_watchlist_entries_watchlist_coin to prevent duplicate coin rows.
        return 'watchlist';
    }
    if ( tonbankcard_api_alerts_is_request( $path ) ) {
        return 'alerts';
    }
    if ( tonbankcard_api_screener_is_request( $path ) ) {
        return 'screener';
    }
    if ( '/api/ai' === $path || 0 === strpos( $path, '/api/ai/' ) ) {
        return 'ai';
    }
    if ( 0 === strpos( $path, '/api/telegram/' ) ) {
        return 'telegram';
    }
    if ( 0 === strpos( $path, '/api/observability/' ) ) {
        return 'observability';
    }
    if ( '/api/metrics' === $path ) {
        return 'metrics';
    }
    if ( in_array( $path, [ '/api', '/api/health', '/api/ready' ], TRUE ) ) {
        return 'core';
    }

    return 'unknown';
}

/**
 * Accepts sanitized browser error reports and emits server-side logs.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_client_error_response( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $settings = tonbankcard_observability_config( $runtime, $config );
    if ( empty( $settings['client_error_reporting'] ) ) {
        return tonbankcard_api_success_response(
            [
                'accepted' => FALSE,
                'reason'   => 'client_error_reporting_disabled',
            ],
            $request_id,
            $headers,
            202
        );
    }

    $event = tonbankcard_observability_client_event( tonbankcard_api_json_body( $request ), $request_id );
    tonbankcard_observability_log(
        $runtime,
        $config,
        tonbankcard_observability_client_event_level( $event ),
        'frontend.' . $event['type'],
        $event
    );

    return tonbankcard_api_success_response(
        [
            'accepted' => TRUE,
            'event_id' => $event['client_event_id'],
        ],
        $request_id,
        $headers,
        202
    );
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
 * Handles Telegram Mini App initData validation and session creation.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_telegram_session_response( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $settings = tonbankcard_api_telegram_session_settings( $config );
    $payload  = tonbankcard_api_json_body( $request );
    $init_data = tonbankcard_api_telegram_init_data_from_request( $request, $payload );

    if ( '' === $init_data ) {
        if ( tonbankcard_api_local_browser_fallback_allowed( $runtime ) ) {
            return tonbankcard_api_local_browser_session_response( $request, $runtime, $config, $settings, $request_id, $headers );
        }

        return tonbankcard_api_error_response(
            400,
            'missing_init_data',
            'Telegram initData is required for this session endpoint.',
            [ 'field' => 'initData' ],
            $request_id,
            $headers
        );
    }

    if ( strlen( $init_data ) > 8192 ) {
        return tonbankcard_api_error_response(
            400,
            'init_data_too_large',
            'Telegram initData is larger than the accepted limit.',
            [ 'max_bytes' => 8192 ],
            $request_id,
            $headers
        );
    }

    $bot_token = isset( $runtime['telegram']['bot_token'] ) ? trim( (string) $runtime['telegram']['bot_token'] ) : '';
    if ( '' === $bot_token ) {
        return tonbankcard_api_error_response(
            503,
            'telegram_bot_token_missing',
            'Telegram session validation is not configured.',
            [ 'config' => 'TONBANKCARD_BOT_TOKEN' ],
            $request_id,
            $headers
        );
    }

    $validation = tonbankcard_api_validate_telegram_init_data( $init_data, $bot_token, $settings );
    if ( empty( $validation['ok'] ) ) {
        return tonbankcard_api_error_response(
            isset( $validation['status'] ) ? (int) $validation['status'] : 400,
            isset( $validation['code'] ) ? $validation['code'] : 'invalid_telegram_init_data',
            isset( $validation['message'] ) ? $validation['message'] : 'Telegram initData could not be validated.',
            isset( $validation['details'] ) && is_array( $validation['details'] ) ? $validation['details'] : [],
            $request_id,
            $headers
        );
    }

    $session = tonbankcard_api_build_telegram_session_record( $validation['data'], $init_data, $request, $settings );
    $stored = tonbankcard_api_store_session_record( $session, $runtime, $config );
    if ( empty( $stored['ok'] ) ) {
        return tonbankcard_api_error_response(
            503,
            'session_store_unavailable',
            'The validated Telegram session could not be stored.',
            [ 'storage' => isset( $stored['storage'] ) ? $stored['storage'] : 'database' ],
            $request_id,
            $headers
        );
    }

    if ( function_exists( 'tonbankcard_api_share_record_referral_attribution' ) ) {
        tonbankcard_api_share_record_referral_attribution( $session, $runtime, $config );
    }

    $headers['Set-Cookie'] = tonbankcard_api_session_cookie_header( $session, $runtime, $settings );

    return tonbankcard_api_success_response(
        tonbankcard_api_session_response_payload( $session ),
        $request_id,
        $headers
    );
}

/**
 * Creates an anonymous local-development session when Telegram is unavailable.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_local_browser_session_response( array $request, array $runtime, array $config, array $settings, string $request_id, array $headers ) {
    $session = tonbankcard_api_build_anonymous_session_record( $request, $settings );
    $stored = tonbankcard_api_store_session_record( $session, $runtime, $config );
    if ( empty( $stored['ok'] ) ) {
        return tonbankcard_api_error_response(
            503,
            'session_store_unavailable',
            'The local browser session could not be stored.',
            [ 'storage' => isset( $stored['storage'] ) ? $stored['storage'] : 'local_file' ],
            $request_id,
            $headers
        );
    }

    $headers['Set-Cookie'] = tonbankcard_api_session_cookie_header( $session, $runtime, $settings );

    return tonbankcard_api_success_response(
        tonbankcard_api_session_response_payload( $session ),
        $request_id,
        $headers
    );
}

/**
 * Returns Telegram session settings with safe defaults.
 *
 * @param array $config
 * @return array
 */
function tonbankcard_api_telegram_session_settings( array $config ) {
    $settings = isset( $config['telegram_session'] ) && is_array( $config['telegram_session'] ) ? $config['telegram_session'] : [];

    return [
        'init_data_max_age_seconds'     => isset( $settings['init_data_max_age_seconds'] ) ? max( 60, (int) $settings['init_data_max_age_seconds'] ) : 86400,
        'auth_date_future_skew_seconds' => isset( $settings['auth_date_future_skew_seconds'] ) ? max( 0, (int) $settings['auth_date_future_skew_seconds'] ) : 60,
        'session_ttl_seconds'           => isset( $settings['session_ttl_seconds'] ) ? max( 300, (int) $settings['session_ttl_seconds'] ) : 2592000,
        'local_session_store_path'      => ! empty( $settings['local_session_store_path'] ) ? (string) $settings['local_session_store_path'] : sys_get_temp_dir() . '/tonbankcard-marketcap-sessions.json',
    ];
}

/**
 * Decodes an already validated JSON request body.
 *
 * @param array $request
 * @return array
 */
function tonbankcard_api_json_body( array $request ) {
    if ( '' === trim( isset( $request['body'] ) ? $request['body'] : '' ) ) {
        return [];
    }

    $payload = json_decode( $request['body'], TRUE );
    return is_array( $payload ) ? $payload : [];
}

/**
 * Reads raw Telegram initData from JSON body or the allowed request header.
 *
 * @param array $request
 * @param array $payload
 * @return string
 */
function tonbankcard_api_telegram_init_data_from_request( array $request, array $payload ) {
    if ( isset( $payload['initData'] ) && is_string( $payload['initData'] ) ) {
        return trim( $payload['initData'] );
    }

    if ( isset( $payload['init_data'] ) && is_string( $payload['init_data'] ) ) {
        return trim( $payload['init_data'] );
    }

    if ( isset( $request['headers']['x-telegram-init-data'] ) ) {
        return trim( (string) $request['headers']['x-telegram-init-data'] );
    }

    return '';
}

/**
 * Returns TRUE when anonymous browser fallback is allowed.
 *
 * @param array $runtime
 * @return bool
 */
function tonbankcard_api_local_browser_fallback_allowed( array $runtime ) {
    return isset( $runtime['profile'] ) && 'local' === $runtime['profile'];
}

/**
 * Validates Telegram Mini App initData using the bot-token-derived secret.
 *
 * @param string $init_data
 * @param string $bot_token
 * @param array $settings
 * @return array
 */
function tonbankcard_api_validate_telegram_init_data( string $init_data, string $bot_token, array $settings ) {
    $parsed = tonbankcard_api_parse_telegram_init_data( $init_data );
    if ( empty( $parsed['ok'] ) ) {
        return $parsed;
    }

    $fields = $parsed['fields'];
    if ( empty( $fields['hash'] ) ) {
        return tonbankcard_api_telegram_validation_error( 400, 'missing_telegram_hash', 'Telegram initData is missing its hash field.', [ 'field' => 'hash' ] );
    }

    $received_hash = strtolower( $fields['hash'] );
    if ( ! preg_match( '/^[a-f0-9]{64}$/', $received_hash ) ) {
        return tonbankcard_api_telegram_validation_error( 400, 'invalid_telegram_hash', 'Telegram initData hash is malformed.', [ 'field' => 'hash' ] );
    }

    $check_fields = $fields;
    unset( $check_fields['hash'] );
    ksort( $check_fields, SORT_STRING );

    $data_check_parts = [];
    foreach ( $check_fields as $key => $value ) {
        $data_check_parts[] = $key . '=' . $value;
    }
    $data_check_string = implode( "\n", $data_check_parts );
    $secret_key = hash_hmac( 'sha256', $bot_token, 'WebAppData', TRUE );
    $calculated_hash = hash_hmac( 'sha256', $data_check_string, $secret_key );

    if ( ! hash_equals( $calculated_hash, $received_hash ) ) {
        return tonbankcard_api_telegram_validation_error( 401, 'invalid_telegram_init_data', 'Telegram initData signature did not match.', [] );
    }

    if ( empty( $fields['auth_date'] ) || ! ctype_digit( $fields['auth_date'] ) ) {
        return tonbankcard_api_telegram_validation_error( 400, 'missing_auth_date', 'Telegram initData is missing a valid auth_date.', [ 'field' => 'auth_date' ] );
    }

    $auth_date = (int) $fields['auth_date'];
    $now = time();
    if ( $auth_date < $now - $settings['init_data_max_age_seconds'] ) {
        return tonbankcard_api_telegram_validation_error( 401, 'expired_telegram_init_data', 'Telegram initData is stale and must be refreshed.', [ 'max_age_seconds' => $settings['init_data_max_age_seconds'] ] );
    }

    if ( $auth_date > $now + $settings['auth_date_future_skew_seconds'] ) {
        return tonbankcard_api_telegram_validation_error( 401, 'invalid_telegram_init_data', 'Telegram initData auth_date is too far in the future.', [ 'field' => 'auth_date' ] );
    }

    if ( empty( $fields['user'] ) ) {
        return tonbankcard_api_telegram_validation_error( 400, 'missing_telegram_user', 'Telegram initData is missing the user object required for session creation.', [ 'field' => 'user' ] );
    }

    $user = json_decode( $fields['user'], TRUE );
    if ( ! is_array( $user ) ) {
        return tonbankcard_api_telegram_validation_error( 400, 'invalid_telegram_user', 'Telegram initData user object is not valid JSON.', [ 'field' => 'user' ] );
    }

    $telegram_user_id = isset( $user['id'] ) ? (string) $user['id'] : '';
    if ( ! preg_match( '/^[1-9][0-9]{0,19}$/', $telegram_user_id ) ) {
        return tonbankcard_api_telegram_validation_error( 400, 'missing_telegram_user_id', 'Telegram initData user object is missing a valid id.', [ 'field' => 'user.id' ] );
    }

    $language_code = null;
    if ( isset( $user['language_code'] ) && is_string( $user['language_code'] ) && preg_match( '/^[A-Za-z0-9_-]{1,16}$/', $user['language_code'] ) ) {
        $language_code = $user['language_code'];
    }

    $start_param = isset( $fields['start_param'] ) && '' !== $fields['start_param'] ? substr( $fields['start_param'], 0, 512 ) : null;
    $chat_type = isset( $fields['chat_type'] ) && preg_match( '/^[A-Za-z0-9_-]{1,32}$/', $fields['chat_type'] ) ? $fields['chat_type'] : null;
    $chat_instance = isset( $fields['chat_instance'] ) && '' !== $fields['chat_instance'] ? $fields['chat_instance'] : null;

    return [
        'ok'   => TRUE,
        'data' => [
            'fields'               => $fields,
            'auth_date'            => $auth_date,
            'telegram_user_id'     => $telegram_user_id,
            'telegram_language_code' => $language_code,
            'telegram_is_premium'  => ! empty( $user['is_premium'] ),
            'start_param'          => $start_param,
            'chat_type'            => $chat_type,
            'chat_instance'        => $chat_instance,
        ],
    ];
}

/**
 * Parses Telegram initData query string without trusting PHP superglobals.
 *
 * @param string $init_data
 * @return array
 */
function tonbankcard_api_parse_telegram_init_data( string $init_data ) {
    $fields = [];
    foreach ( explode( '&', $init_data ) as $part ) {
        if ( '' === $part ) {
            continue;
        }

        $separator = strpos( $part, '=' );
        if ( FALSE === $separator ) {
            return tonbankcard_api_telegram_validation_error( 400, 'malformed_init_data', 'Telegram initData contains a malformed field.', [] );
        }

        $key = urldecode( substr( $part, 0, $separator ) );
        $value = urldecode( substr( $part, $separator + 1 ) );
        if ( '' === $key || preg_match( '/[\[\]\r\n=]/', $key ) ) {
            return tonbankcard_api_telegram_validation_error( 400, 'malformed_init_data', 'Telegram initData contains an unsafe field name.', [] );
        }

        if ( array_key_exists( $key, $fields ) ) {
            return tonbankcard_api_telegram_validation_error( 400, 'duplicate_init_data_field', 'Telegram initData contains a duplicate field.', [ 'field' => $key ] );
        }

        $fields[ $key ] = $value;
    }

    return [
        'ok'     => TRUE,
        'fields' => $fields,
    ];
}

/**
 * Builds a typed validation error result.
 *
 * @param int $status
 * @param string $code
 * @param string $message
 * @param array $details
 * @return array
 */
function tonbankcard_api_telegram_validation_error( int $status, string $code, string $message, array $details ) {
    return [
        'ok'      => FALSE,
        'status'  => $status,
        'code'    => $code,
        'message' => $message,
        'details' => $details,
    ];
}

/**
 * Builds a trusted Telegram session record.
 *
 * @param array $validated
 * @param string $init_data
 * @param array $request
 * @param array $settings
 * @return array
 */
function tonbankcard_api_build_telegram_session_record( array $validated, string $init_data, array $request, array $settings ) {
    $token = tonbankcard_api_session_token_from_request( $request );
    if ( null === $token ) {
        $token = tonbankcard_api_new_session_token();
    }

    $expires_at = time() + $settings['session_ttl_seconds'];

    return [
        'session_token'          => $token,
        'session_token_hash'     => hash( 'sha256', $token ),
        'telegram_init_data_hash' => hash( 'sha256', $init_data ),
        'surface'                => 'telegram_mini_app',
        'trust_state'            => 'telegram_validated',
        'source'                 => 'telegram_init_data',
        'telegram_user_id'       => $validated['telegram_user_id'],
        'telegram_language_code' => $validated['telegram_language_code'],
        'telegram_is_premium'    => (bool) $validated['telegram_is_premium'],
        'start_param'            => $validated['start_param'],
        'start_param_hash'       => tonbankcard_api_optional_hash( $validated['start_param'] ),
        'chat_type'              => $validated['chat_type'],
        'chat_instance_present'  => null !== $validated['chat_instance'],
        'chat_instance_hash'     => tonbankcard_api_optional_hash( $validated['chat_instance'] ),
        'ip_hash'                => tonbankcard_api_optional_hash( tonbankcard_api_request_ip( $request ) ),
        'user_agent_hash'        => tonbankcard_api_optional_hash( isset( $request['headers']['user-agent'] ) ? $request['headers']['user-agent'] : null ),
        'expires_at_timestamp'   => $expires_at,
        'expires_at_mysql'       => tonbankcard_api_mysql_datetime( $expires_at ),
        'expires_at_iso'         => tonbankcard_api_iso_datetime( $expires_at ),
    ];
}

/**
 * Builds an anonymous local browser session record.
 *
 * @param array $request
 * @param array $settings
 * @return array
 */
function tonbankcard_api_build_anonymous_session_record( array $request, array $settings ) {
    $token = tonbankcard_api_session_token_from_request( $request );
    if ( null === $token ) {
        $token = tonbankcard_api_new_session_token();
    }

    $expires_at = time() + $settings['session_ttl_seconds'];

    return [
        'session_token'          => $token,
        'session_token_hash'     => hash( 'sha256', $token ),
        'telegram_init_data_hash' => null,
        'surface'                => 'public_web',
        'trust_state'            => 'anonymous',
        'source'                 => 'local_browser',
        'telegram_user_id'       => null,
        'telegram_language_code' => null,
        'telegram_is_premium'    => FALSE,
        'start_param'            => null,
        'start_param_hash'       => null,
        'chat_type'              => null,
        'chat_instance_present'  => FALSE,
        'chat_instance_hash'     => null,
        'ip_hash'                => tonbankcard_api_optional_hash( tonbankcard_api_request_ip( $request ) ),
        'user_agent_hash'        => tonbankcard_api_optional_hash( isset( $request['headers']['user-agent'] ) ? $request['headers']['user-agent'] : null ),
        'expires_at_timestamp'   => $expires_at,
        'expires_at_mysql'       => tonbankcard_api_mysql_datetime( $expires_at ),
        'expires_at_iso'         => tonbankcard_api_iso_datetime( $expires_at ),
    ];
}

/**
 * Stores a session in MySQL/MariaDB when configured or in a local file fallback.
 *
 * @param array $session
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_store_session_record( array $session, array $runtime, array $config ) {
    $database_error = null;
    $pdo = tonbankcard_api_session_database_connection( $runtime, $database_error );
    if ( null !== $pdo ) {
        try {
            tonbankcard_api_store_database_session_record( $pdo, $session );
            return [
                'ok'      => TRUE,
                'storage' => 'database',
            ];
        } catch ( Throwable $error ) {
            $database_error = 'Session database write failed.';
        }
    }

    if ( tonbankcard_api_local_browser_fallback_allowed( $runtime ) ) {
        return tonbankcard_api_store_local_session_record( $session, tonbankcard_api_telegram_session_settings( $config ) );
    }

    return [
        'ok'      => FALSE,
        'storage' => 'database',
        'error'   => $database_error,
    ];
}

/**
 * Opens the configured session database connection.
 *
 * @param array $runtime
 * @param string|null $error
 * @return PDO|null
 */
function tonbankcard_api_session_database_connection( array $runtime, &$error = null ) {
    if ( ! class_exists( 'PDO' ) ) {
        $error = 'PDO is not available.';
        return null;
    }

    $mysql = isset( $runtime['providers']['mysql'] ) && is_array( $runtime['providers']['mysql'] ) ? $runtime['providers']['mysql'] : [];
    if ( empty( $mysql['dsn'] ) || empty( $mysql['user'] ) ) {
        $error = 'Session database is not configured.';
        return null;
    }

    try {
        return new PDO(
            $mysql['dsn'],
            $mysql['user'],
            isset( $mysql['password'] ) ? $mysql['password'] : '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch ( Throwable $exception ) {
        $error = 'Session database connection failed.';
        return null;
    }
}

/**
 * Persists a session record into the V2 database schema.
 *
 * @param PDO $pdo
 * @param array $session
 * @return void
 */
function tonbankcard_api_store_database_session_record( PDO $pdo, array $session ) {
    $now = tonbankcard_api_mysql_datetime( time() );
    $user_id = null;

    $pdo->beginTransaction();
    try {
        if ( null !== $session['telegram_user_id'] ) {
            $upsert_user = $pdo->prepare(
                'INSERT INTO users (telegram_user_id, telegram_language_code, telegram_is_premium, last_seen_at)
                 VALUES (:telegram_user_id, :telegram_language_code, :telegram_is_premium, :last_seen_at)
                 ON DUPLICATE KEY UPDATE
                    telegram_language_code = :update_telegram_language_code,
                    telegram_is_premium = :update_telegram_is_premium,
                    last_seen_at = :update_last_seen_at'
            );
            $upsert_user->execute(
                [
                    ':telegram_user_id'               => $session['telegram_user_id'],
                    ':telegram_language_code'         => $session['telegram_language_code'],
                    ':telegram_is_premium'            => $session['telegram_is_premium'] ? 1 : 0,
                    ':last_seen_at'                   => $now,
                    ':update_telegram_language_code'  => $session['telegram_language_code'],
                    ':update_telegram_is_premium'     => $session['telegram_is_premium'] ? 1 : 0,
                    ':update_last_seen_at'            => $now,
                ]
            );

            $select_user = $pdo->prepare( 'SELECT id FROM users WHERE telegram_user_id = :telegram_user_id LIMIT 1' );
            $select_user->execute( [ ':telegram_user_id' => $session['telegram_user_id'] ] );
            $row = $select_user->fetch();
            if ( ! is_array( $row ) || empty( $row['id'] ) ) {
                throw new RuntimeException( 'Telegram user row was not persisted.' );
            }
            $user_id = $row['id'];
        }

        $upsert_session = $pdo->prepare(
            'INSERT INTO user_sessions (
                user_id,
                session_token_hash,
                telegram_init_data_hash,
                surface,
                trust_state,
                start_param_hash,
                chat_instance_hash,
                telegram_chat_type,
                ip_hash,
                user_agent_hash,
                last_seen_at,
                expires_at,
                revoked_at
             )
             VALUES (
                :user_id,
                :session_token_hash,
                :telegram_init_data_hash,
                :surface,
                :trust_state,
                :start_param_hash,
                :chat_instance_hash,
                :telegram_chat_type,
                :ip_hash,
                :user_agent_hash,
                :last_seen_at,
                :expires_at,
                NULL
             )
             ON DUPLICATE KEY UPDATE
                user_id = :update_user_id,
                telegram_init_data_hash = :update_telegram_init_data_hash,
                surface = :update_surface,
                trust_state = :update_trust_state,
                start_param_hash = :update_start_param_hash,
                chat_instance_hash = :update_chat_instance_hash,
                telegram_chat_type = :update_telegram_chat_type,
                ip_hash = :update_ip_hash,
                user_agent_hash = :update_user_agent_hash,
                last_seen_at = :update_last_seen_at,
                expires_at = :update_expires_at,
                revoked_at = NULL'
        );
        $params = [
            ':user_id'                         => $user_id,
            ':session_token_hash'              => $session['session_token_hash'],
            ':telegram_init_data_hash'         => $session['telegram_init_data_hash'],
            ':surface'                         => $session['surface'],
            ':trust_state'                     => $session['trust_state'],
            ':start_param_hash'                => $session['start_param_hash'],
            ':chat_instance_hash'              => $session['chat_instance_hash'],
            ':telegram_chat_type'              => $session['chat_type'],
            ':ip_hash'                         => $session['ip_hash'],
            ':user_agent_hash'                 => $session['user_agent_hash'],
            ':last_seen_at'                    => $now,
            ':expires_at'                      => $session['expires_at_mysql'],
            ':update_user_id'                  => $user_id,
            ':update_telegram_init_data_hash'  => $session['telegram_init_data_hash'],
            ':update_surface'                  => $session['surface'],
            ':update_trust_state'              => $session['trust_state'],
            ':update_start_param_hash'         => $session['start_param_hash'],
            ':update_chat_instance_hash'       => $session['chat_instance_hash'],
            ':update_telegram_chat_type'       => $session['chat_type'],
            ':update_ip_hash'                  => $session['ip_hash'],
            ':update_user_agent_hash'          => $session['user_agent_hash'],
            ':update_last_seen_at'             => $now,
            ':update_expires_at'               => $session['expires_at_mysql'],
        ];
        $upsert_session->execute( $params );

        $pdo->commit();
    } catch ( Throwable $error ) {
        if ( $pdo->inTransaction() ) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

/**
 * Persists a local-only session record for development without a database.
 *
 * @param array $session
 * @param array $settings
 * @return array
 */
function tonbankcard_api_store_local_session_record( array $session, array $settings ) {
    $path = $settings['local_session_store_path'];
    $dir = dirname( $path );
    if ( ! is_dir( $dir ) && ! mkdir( $dir, 0700, TRUE ) ) {
        return [
            'ok'      => FALSE,
            'storage' => 'local_file',
        ];
    }

    $store = [
        'users'    => [],
        'sessions' => [],
    ];
    if ( is_readable( $path ) ) {
        $decoded = json_decode( (string) file_get_contents( $path ), TRUE );
        if ( is_array( $decoded ) ) {
            $store = array_merge( $store, $decoded );
        }
    }

    if ( null !== $session['telegram_user_id'] ) {
        $store['users'][ $session['telegram_user_id'] ] = [
            'telegram_user_id'       => $session['telegram_user_id'],
            'telegram_language_code' => $session['telegram_language_code'],
            'telegram_is_premium'    => $session['telegram_is_premium'],
            'last_seen_at'           => tonbankcard_api_iso_datetime( time() ),
        ];
    }

    $stored_session = $session;
    unset( $stored_session['session_token'] );
    unset( $stored_session['start_param'] );
    $store['sessions'][ $session['session_token_hash'] ] = $stored_session;

    $written = file_put_contents( $path, json_encode( $store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE ), LOCK_EX );
    if ( FALSE === $written ) {
        return [
            'ok'      => FALSE,
            'storage' => 'local_file',
        ];
    }
    @chmod( $path, 0600 );

    return [
        'ok'      => TRUE,
        'storage' => 'local_file',
    ];
}

/**
 * Builds the minimum session payload returned to the frontend.
 *
 * @param array $session
 * @return array
 */
function tonbankcard_api_session_response_payload( array $session ) {
    return [
        'session' => [
            'state'      => $session['trust_state'],
            'source'     => $session['source'],
            'surface'    => $session['surface'],
            'expires_at' => $session['expires_at_iso'],
        ],
        'user'    => null === $session['telegram_user_id'] ? null : [
            'telegram_user_id' => (string) $session['telegram_user_id'],
            'language_code'    => $session['telegram_language_code'],
            'is_premium'       => (bool) $session['telegram_is_premium'],
        ],
        'launch'  => [
            'start_param'           => $session['start_param'],
            'chat_type'             => $session['chat_type'],
            'chat_instance_present' => (bool) $session['chat_instance_present'],
        ],
    ];
}

/**
 * Returns a Set-Cookie header for the server session token.
 *
 * @param array $session
 * @param array $runtime
 * @param array $settings
 * @return string
 */
function tonbankcard_api_session_cookie_header( array $session, array $runtime, array $settings ) {
    $cookie = 'tonbankcard_session=' . rawurlencode( $session['session_token'] )
        . '; Path=/'
        . '; Max-Age=' . (int) $settings['session_ttl_seconds']
        . '; Expires=' . gmdate( 'D, d M Y H:i:s', $session['expires_at_timestamp'] ) . ' GMT'
        . '; HttpOnly'
        . '; SameSite=Lax';

    $active_url = isset( $runtime['urls']['active'] ) ? (string) $runtime['urls']['active'] : '';
    if ( 0 === strpos( $active_url, 'https://' ) ) {
        $cookie .= '; Secure';
    }

    return $cookie;
}

/**
 * Reads an existing session token cookie when it matches the server format.
 *
 * @param array $request
 * @return string|null
 */
function tonbankcard_api_session_token_from_request( array $request ) {
    if ( empty( $request['headers']['cookie'] ) ) {
        return null;
    }

    foreach ( explode( ';', $request['headers']['cookie'] ) as $cookie ) {
        $parts = explode( '=', trim( $cookie ), 2 );
        if ( 2 !== count( $parts ) ) {
            continue;
        }

        if ( 'tonbankcard_session' !== $parts[0] ) {
            continue;
        }

        $token = rawurldecode( $parts[1] );
        if ( preg_match( '/^[A-Fa-f0-9]{64}$/', $token ) ) {
            return strtolower( $token );
        }
    }

    return null;
}

/**
 * Creates a new random session token.
 *
 * @return string
 */
function tonbankcard_api_new_session_token() {
    try {
        return bin2hex( random_bytes( 32 ) );
    } catch ( Exception $exception ) {
        return hash( 'sha256', uniqid( 'tonbankcard_session_', TRUE ) );
    }
}

/**
 * Hashes optional sensitive values for durable storage.
 *
 * @param string|null $value
 * @return string|null
 */
function tonbankcard_api_optional_hash( $value ) {
    if ( null === $value || '' === $value ) {
        return null;
    }

    return hash( 'sha256', (string) $value );
}

/**
 * Returns the best-effort request IP address.
 *
 * @param array $request
 * @return string|null
 */
function tonbankcard_api_request_ip( array $request ) {
    if ( ! empty( $request['headers']['x-forwarded-for'] ) ) {
        $parts = explode( ',', $request['headers']['x-forwarded-for'] );
        return trim( $parts[0] );
    }

    return isset( $request['headers']['x-real-ip'] ) ? trim( $request['headers']['x-real-ip'] ) : null;
}

/**
 * Formats a UTC timestamp for MySQL DATETIME(6).
 *
 * @param int $timestamp
 * @return string
 */
function tonbankcard_api_mysql_datetime( int $timestamp ) {
    return gmdate( 'Y-m-d H:i:s', $timestamp ) . '.000000';
}

/**
 * Formats a UTC timestamp for JSON responses.
 *
 * @param int $timestamp
 * @return string
 */
function tonbankcard_api_iso_datetime( int $timestamp ) {
    return gmdate( 'Y-m-d\TH:i:s\Z', $timestamp );
}

/**
 * Returns configured rate limit policy without exposing identity keys.
 *
 * @param array $config
 * @return array
 */
function tonbankcard_api_rate_limit_context( array $config ) {
    $settings = tonbankcard_api_rate_limit_settings( $config );
    $anonymous = isset( $settings['policies']['anonymous_web'] ) ? $settings['policies']['anonymous_web'] : [ 'window_seconds' => 60, 'max_requests' => 60 ];

    return [
        'enabled'        => ! empty( $settings['enabled'] ),
        'state'          => empty( $settings['enabled'] ) ? 'not_enforced' : 'enforced',
        'window_seconds' => isset( $anonymous['window_seconds'] ) ? (int) $anonymous['window_seconds'] : 60,
        'max_requests'   => isset( $anonymous['max_requests'] ) ? (int) $anonymous['max_requests'] : 60,
        'policies'       => array_keys( $settings['policies'] ),
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
        'groq'      => function_exists( 'tonbankcard_api_ai_provider_health_check' )
            ? tonbankcard_api_ai_provider_health_check( $runtime, $config )
            : [
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
