<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 MARKET DATA GATEWAY
 * -------------------------------------------------------------------------
 * Server-owned CoinGecko gateway used by browser and Telegram Mini App
 * clients. Provider credentials stay in runtime configuration and are never
 * sent to browser JavaScript.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

if ( ! function_exists( 'tonbankcard_api_cache_settings' ) ) {
    require_once __DIR__ . '/cache.php';
}

/**
 * Returns TRUE when a normalized path belongs to the market API surface.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_market_is_request( string $path ) {
    return '/api/market' === $path || 0 === strpos( $path, '/api/market/' );
}

/**
 * Returns documented market gateway routes.
 *
 * @return array
 */
function tonbankcard_api_market_routes() {
    return [
        '/api/market',
        '/api/market/global',
        '/api/market/coins/list',
        '/api/market/coins/markets',
        '/api/market/coins/{id}',
        '/api/market/coins/{id}/market_chart',
        '/api/market/coins/{id}/market_chart/range',
        '/api/market/coins/{id}/tickers',
        '/api/market/exchanges',
        '/api/market/exchanges/list',
        '/api/market/exchanges/{id}',
        '/api/market/exchanges/{id}/tickers',
        '/api/market/exchanges/{id}/volume_chart',
        '/api/market/search',
        '/api/market/search/trending',
        '/api/market/finance_platforms',
        '/api/market/finance_products',
        '/api/market/derivatives',
    ];
}

/**
 * Handles a market API request using the shared API envelope helpers.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_market_handle( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    if ( 'GET' !== $request['method'] ) {
        return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
    }

    $relative_path = tonbankcard_api_market_relative_path( tonbankcard_api_normalize_path( $request['path'] ) );
    if ( '' === $relative_path ) {
        return tonbankcard_api_success_response(
            [
                'service'  => 'tonbankcard-market-gateway',
                'provider' => 'coingecko',
                'routes'   => tonbankcard_api_market_routes(),
            ],
            $request_id,
            $headers,
            200,
            tonbankcard_api_market_base_meta( $runtime, $config, time() )
        );
    }

    $route = tonbankcard_api_market_route_for_path( $relative_path );
    if ( null === $route ) {
        return tonbankcard_api_error_response(
            404,
            'not_found',
            'No market data route matches the request.',
            [ 'path' => '/api/market/' . $relative_path ],
            $request_id,
            $headers
        );
    }

    $provider = tonbankcard_api_market_provider_config( $runtime, $config );
    $query    = tonbankcard_api_market_sanitize_query(
        isset( $request['query'] ) ? $request['query'] : [],
        $route
    );
    $fetched_at = time();
    $cache_context = tonbankcard_api_market_cache_context( $route, $query, $provider, $runtime, $config, $request_id );
    $cached = tonbankcard_api_market_cache_read( $cache_context, $runtime, $config, $route, $provider, $request_id, $headers );
    if ( null !== $cached ) {
        return $cached;
    }

    $provider_started_at = microtime( TRUE );
    tonbankcard_observability_log(
        $runtime,
        $config,
        'debug',
        'market.provider_request',
        [
            'request_id'    => $request_id,
            'provider'      => $provider['name'],
            'provider_plan' => $provider['plan'],
            'gateway_path'  => $route['gateway_path'],
            'upstream_path' => $route['upstream_path'],
            'query_keys'    => array_values( array_keys( $query ) ),
        ]
    );
    $upstream_response = tonbankcard_api_market_fetch( $route['upstream_path'], $query, $provider, $config );

    $response = tonbankcard_api_market_response(
        $upstream_response,
        $route,
        $provider,
        $request_id,
        $headers,
        $fetched_at,
        $cache_context,
        $runtime,
        $config,
        $query
    );
    tonbankcard_api_market_log_provider_response(
        $upstream_response,
        $route,
        $provider,
        $request_id,
        $response,
        $runtime,
        $config,
        max( 0, (int) round( ( microtime( TRUE ) - $provider_started_at ) * 1000 ) )
    );

    return $response;
}

/**
 * Returns the relative route after /api/market.
 *
 * @param string $path
 * @return string
 */
function tonbankcard_api_market_relative_path( string $path ) {
    if ( '/api/market' === $path ) {
        return '';
    }

    return trim( substr( $path, strlen( '/api/market/' ) ), '/' );
}

/**
 * Maps supported public gateway paths to CoinGecko upstream paths.
 *
 * @param string $relative_path
 * @return array|null
 */
function tonbankcard_api_market_route_for_path( string $relative_path ) {
    $relative_path = trim( $relative_path, '/' );

    $exact_routes = [
        'global'            => 'global',
        'coins/list'        => 'coins/list',
        'coins/markets'     => 'coins/markets',
        'exchanges'         => 'exchanges',
        'exchanges/list'    => 'exchanges/list',
        'search'            => 'search',
        'search/trending'   => 'search/trending',
        'finance_platforms' => 'finance_platforms',
        'finance_products'  => 'finance_products',
        'derivatives'       => 'derivatives',
    ];

    if ( isset( $exact_routes[ $relative_path ] ) ) {
        return [
            'gateway_path'  => '/api/market/' . $relative_path,
            'upstream_path' => $exact_routes[ $relative_path ],
        ];
    }

    $segments = explode( '/', $relative_path );
    if ( 'coins' === $segments[0] && isset( $segments[1] ) && tonbankcard_api_market_safe_segment( $segments[1] ) ) {
        if ( 2 === count( $segments ) ) {
            return [
                'gateway_path'  => '/api/market/' . $relative_path,
                'upstream_path' => 'coins/' . $segments[1],
            ];
        }

        if ( 3 === count( $segments ) && 'tickers' === $segments[2] ) {
            return [
                'gateway_path'  => '/api/market/' . $relative_path,
                'upstream_path' => 'coins/' . $segments[1] . '/tickers',
            ];
        }

        if ( 3 === count( $segments ) && 'market_chart' === $segments[2] ) {
            return [
                'gateway_path'  => '/api/market/' . $relative_path,
                'upstream_path' => 'coins/' . $segments[1] . '/market_chart',
            ];
        }

        if ( 4 === count( $segments ) && 'market_chart' === $segments[2] && 'range' === $segments[3] ) {
            return [
                'gateway_path'  => '/api/market/' . $relative_path,
                'upstream_path' => 'coins/' . $segments[1] . '/market_chart/range',
            ];
        }
    }

    if ( 'exchanges' === $segments[0] && isset( $segments[1] ) && tonbankcard_api_market_safe_segment( $segments[1] ) ) {
        if ( 2 === count( $segments ) ) {
            return [
                'gateway_path'  => '/api/market/' . $relative_path,
                'upstream_path' => 'exchanges/' . $segments[1],
            ];
        }

        if ( 3 === count( $segments ) && 'tickers' === $segments[2] ) {
            return [
                'gateway_path'  => '/api/market/' . $relative_path,
                'upstream_path' => 'exchanges/' . $segments[1] . '/tickers',
            ];
        }

        if ( 3 === count( $segments ) && 'volume_chart' === $segments[2] ) {
            return [
                'gateway_path'  => '/api/market/' . $relative_path,
                'upstream_path' => 'exchanges/' . $segments[1] . '/volume_chart',
            ];
        }
    }

    return null;
}

/**
 * Returns TRUE for safe CoinGecko id path segments.
 *
 * @param string $segment
 * @return bool
 */
function tonbankcard_api_market_safe_segment( string $segment ) {
    return 1 === preg_match( '/^[A-Za-z0-9._-]{1,160}$/', $segment );
}

/**
 * Removes client-supplied provider credentials and unsafe query keys.
 *
 * @param array $query
 * @param array|null $route
 * @return array
 */
function tonbankcard_api_market_sanitize_query( array $query, array $route = null ) {
    $sanitized = [];
    $blocked_keys = [
        'api_key',
        'key',
        'x_cg_demo_api_key',
        'x_cg_pro_api_key',
    ];

    foreach ( $query as $key => $value ) {
        $key = (string) $key;
        if ( ! preg_match( '/^[A-Za-z0-9_.-]{1,80}$/', $key ) ) {
            continue;
        }
        $normalized_key = strtolower( $key );
        if ( in_array( $normalized_key, $blocked_keys, TRUE ) ) {
            continue;
        }
        if ( null !== $route && ! in_array( $normalized_key, tonbankcard_api_market_allowed_query_keys( $route ), TRUE ) ) {
            continue;
        }

        $sanitized[ $key ] = tonbankcard_api_market_sanitize_query_value( $value );
    }

    return $sanitized;
}

/**
 * Returns the supported CoinGecko query parameters for one gateway route.
 * Unknown parameters are intentionally dropped before cache-key generation and
 * upstream dispatch so callers cannot force cache misses with junk keys.
 *
 * @param array $route
 * @return array<int,string>
 */
function tonbankcard_api_market_allowed_query_keys( array $route ) {
    $path = isset( $route['upstream_path'] ) ? (string) $route['upstream_path'] : '';
    $exact = [
        'coins/list'        => [ 'include_platform' ],
        'coins/markets'     => [ 'vs_currency', 'ids', 'names', 'symbols', 'include_tokens', 'category', 'order', 'per_page', 'page', 'sparkline', 'price_change_percentage', 'locale', 'precision' ],
        'exchanges'         => [ 'per_page', 'page' ],
        'exchanges/list'    => [],
        'search'            => [ 'query' ],
        'search/trending'   => [],
        'finance_platforms' => [ 'per_page', 'page' ],
        'finance_products'  => [ 'per_page', 'page', 'start_at', 'end_at' ],
        'derivatives'       => [ 'include_tickers' ],
        'global'            => [],
    ];
    if ( isset( $exact[ $path ] ) ) {
        return $exact[ $path ];
    }
    if ( preg_match( '#^coins/[^/]+/market_chart/range$#', $path ) ) {
        return [ 'vs_currency', 'from', 'to', 'precision' ];
    }
    if ( preg_match( '#^coins/[^/]+/market_chart$#', $path ) ) {
        return [ 'vs_currency', 'days', 'interval', 'precision' ];
    }
    if ( preg_match( '#^coins/[^/]+/tickers$#', $path ) ) {
        return [ 'exchange_ids', 'include_exchange_logo', 'page', 'order', 'depth', 'coin_ids' ];
    }
    if ( preg_match( '#^coins/[^/]+$#', $path ) ) {
        return [ 'localization', 'tickers', 'market_data', 'community_data', 'developer_data', 'sparkline', 'include_categories', 'include_max_supply_status' ];
    }
    if ( preg_match( '#^exchanges/[^/]+/tickers$#', $path ) ) {
        return [ 'coin_ids', 'include_exchange_logo', 'page', 'depth', 'order' ];
    }
    if ( preg_match( '#^exchanges/[^/]+/volume_chart$#', $path ) ) {
        return [ 'days' ];
    }
    return [];
}

/**
 * Converts query values to bounded strings CoinGecko accepts.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_market_sanitize_query_value( $value ) {
    if ( is_array( $value ) ) {
        $parts = [];
        foreach ( $value as $part ) {
            $parts[] = tonbankcard_api_market_sanitize_query_value( $part );
        }
        return implode( ',', array_filter( $parts, 'strlen' ) );
    }

    return substr( (string) $value, 0, 2048 );
}

/**
 * Builds safe provider configuration from runtime and API config.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_market_provider_config( array $runtime, array $config ) {
    $market = isset( $config['market_data'] ) && is_array( $config['market_data'] ) ? $config['market_data'] : [];
    $coingecko = isset( $market['coingecko'] ) && is_array( $market['coingecko'] ) ? $market['coingecko'] : [];
    $runtime_provider = isset( $runtime['providers']['coingecko'] ) && is_array( $runtime['providers']['coingecko'] )
        ? $runtime['providers']['coingecko']
        : [];

    $api_key = isset( $coingecko['api_key'] ) ? (string) $coingecko['api_key'] : ( isset( $runtime_provider['api_key'] ) ? (string) $runtime_provider['api_key'] : '' );

    $plan = isset( $coingecko['plan'] ) ? strtolower( (string) $coingecko['plan'] ) : ( isset( $runtime_provider['api_plan'] ) ? strtolower( (string) $runtime_provider['api_plan'] ) : 'demo' );
    if ( ! in_array( $plan, [ 'demo', 'pro' ], TRUE ) ) {
        $plan = 'demo';
    }

    $base_url_key = 'pro' === $plan ? 'pro_base_url' : 'demo_base_url';
    $base_url = isset( $coingecko[ $base_url_key ] ) ? (string) $coingecko[ $base_url_key ] : 'https://api.coingecko.com/api/v3/';

    return [
        'name'               => 'coingecko',
        'plan'               => $plan,
        'base_url'           => rtrim( $base_url, '/' ) . '/',
        'api_key'            => $api_key,
        'api_key_configured' => '' !== trim( $api_key ),
        'api_key_header'     => 'pro' === $plan ? 'x-cg-pro-api-key' : 'x-cg-demo-api-key',
        'timeout_seconds'    => isset( $market['timeout_seconds'] ) ? max( 1, (int) $market['timeout_seconds'] ) : 10,
        'cache_ttl_seconds'  => isset( $market['cache_ttl_seconds'] ) ? max( 0, (int) $market['cache_ttl_seconds'] ) : 0,
        'attribution'        => isset( $market['attribution'] ) && is_array( $market['attribution'] ) ? $market['attribution'] : [
            'name' => 'CoinGecko',
            'url'  => 'https://www.coingecko.com/',
        ],
    ];
}

/**
 * Builds cache metadata for a market gateway request.
 *
 * @param array $route
 * @param array $query
 * @param array $provider
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @return array
 */
function tonbankcard_api_market_cache_context( array $route, array $query, array $provider, array $runtime, array $config, string $request_id ) {
    $settings = tonbankcard_api_cache_settings( $config );
    $redis = tonbankcard_api_redis_settings( $runtime, $config );
    $type = tonbankcard_api_market_cache_type( isset( $route['upstream_path'] ) ? $route['upstream_path'] : '' );
    $ttl = isset( $settings['ttls'][ $type ] ) ? max( 0, (int) $settings['ttls'][ $type ] ) : 0;

    $enabled = ! empty( $settings['enabled'] ) && ! empty( $redis['enabled'] ) && $ttl > 0;
    $normalized_query = tonbankcard_api_market_normalize_cache_query( $query );
    $parts = [
        'provider' => $provider['name'],
        'plan'     => $provider['plan'],
        'route'    => isset( $route['gateway_path'] ) ? $route['gateway_path'] : '',
        'upstream' => isset( $route['upstream_path'] ) ? $route['upstream_path'] : '',
        'query'    => $normalized_query,
    ];
    $cache_key = tonbankcard_api_redis_key( $runtime, $config, 'cache', $parts );
    $lock_key = tonbankcard_api_redis_key( $runtime, $config, 'coalesce', $parts );

    return [
        'enabled'           => $enabled,
        'state'             => $enabled ? 'active' : 'bypass',
        'type'              => $type,
        'ttl_seconds'       => $ttl,
        'stale_ttl_seconds' => $settings['stale_ttl_seconds'],
        'key'               => $cache_key,
        'lock_key'          => $lock_key,
        'request_id'        => $request_id,
        'runtime'           => $runtime,
        'config'            => $config,
        'coalesce'          => $settings['coalesce'],
        'stale'             => null,
    ];
}

/**
 * Returns the configured cache TTL bucket for a CoinGecko path.
 *
 * @param string $upstream_path
 * @return string
 */
function tonbankcard_api_market_cache_type( string $upstream_path ) {
    if ( 'global' === $upstream_path ) {
        return 'global_stats';
    }

    if ( in_array( $upstream_path, [ 'coins/list', 'exchanges/list', 'search', 'search/trending' ], TRUE ) ) {
        return 'search_index';
    }

    if ( preg_match( '#/(market_chart|volume_chart)(/range)?$#', $upstream_path ) ) {
        return 'charts';
    }

    if ( preg_match( '#^(coins|exchanges)/[^/]+$#', $upstream_path ) || 'finance_platforms' === $upstream_path ) {
        return 'coin_metadata';
    }

    return 'live_prices';
}

/**
 * Sorts query keys recursively for stable cache keys.
 *
 * @param array $query
 * @return array
 */
function tonbankcard_api_market_normalize_cache_query( array $query ) {
    ksort( $query, SORT_STRING );
    foreach ( $query as $key => $value ) {
        if ( is_array( $value ) ) {
            $query[ $key ] = tonbankcard_api_market_normalize_cache_query( $value );
        }
    }

    return $query;
}

/**
 * Reads cache, serves hits, tracks stale fallback candidates, and coalesces duplicates.
 *
 * @param array $cache_context
 * @param array $runtime
 * @param array $config
 * @param array $route
 * @param array $provider
 * @param string $request_id
 * @param array $headers
 * @return array|null
 */
function tonbankcard_api_market_cache_read( array &$cache_context, array $runtime, array $config, array $route, array $provider, string $request_id, array $headers ) {
    if ( empty( $cache_context['enabled'] ) ) {
        tonbankcard_api_metric_increment( $runtime, $config, 'cache.bypass' );
        return null;
    }

    $cached = tonbankcard_api_cache_get( $runtime, $config, $cache_context['key'] );
    $state = isset( $cached['state'] ) ? $cached['state'] : 'miss';
    if ( 'hit' === $state ) {
        tonbankcard_api_metric_increment( $runtime, $config, 'cache.hit' );
        return tonbankcard_api_market_cache_response( $cached, $route, $provider, $request_id, $headers, $cache_context, [ 'cache_status' => 'hit' ] );
    }

    if ( 'stale' === $state ) {
        $cache_context['stale'] = $cached;
        tonbankcard_api_metric_increment( $runtime, $config, 'cache.stale' );
        tonbankcard_api_metric_set( $runtime, $config, 'cache.stale_age_seconds', isset( $cached['stale_age_seconds'] ) ? (int) $cached['stale_age_seconds'] : 0 );
    } elseif ( in_array( $state, [ 'miss', 'expired' ], TRUE ) ) {
        tonbankcard_api_metric_increment( $runtime, $config, 'cache.miss' );
    } else {
        tonbankcard_api_metric_increment( $runtime, $config, 'cache.bypass' );
        return null;
    }

    if ( empty( $cache_context['coalesce']['enabled'] ) ) {
        return null;
    }

    $owner = $cache_context['request_id'];
    $acquired = tonbankcard_api_cache_lock_acquire(
        $runtime,
        $config,
        $cache_context['lock_key'],
        $owner,
        isset( $cache_context['coalesce']['lock_ttl_seconds'] ) ? (int) $cache_context['coalesce']['lock_ttl_seconds'] : 15
    );
    $cache_context['lock_acquired'] = $acquired;

    if ( $acquired ) {
        return null;
    }

    $filled = tonbankcard_api_cache_wait_for_fill(
        $runtime,
        $config,
        $cache_context['key'],
        isset( $cache_context['coalesce']['wait_ms'] ) ? (int) $cache_context['coalesce']['wait_ms'] : 250,
        isset( $cache_context['coalesce']['poll_ms'] ) ? (int) $cache_context['coalesce']['poll_ms'] : 25
    );
    if ( isset( $filled['state'] ) && 'hit' === $filled['state'] ) {
        tonbankcard_api_metric_increment( $runtime, $config, 'cache.hit' );
        return tonbankcard_api_market_cache_response(
            $filled,
            $route,
            $provider,
            $request_id,
            $headers,
            $cache_context,
            [
                'cache_status' => 'hit',
                'coalesced'    => TRUE,
            ]
        );
    }

    return null;
}

/**
 * Fetches a CoinGecko upstream endpoint.
 *
 * @param string $upstream_path
 * @param array $query
 * @param array $provider
 * @param array $config
 * @return array
 */
function tonbankcard_api_market_fetch( string $upstream_path, array $query, array $provider, array $config ) {
    $url = $provider['base_url'] . ltrim( $upstream_path, '/' );
    if ( ! empty( $query ) ) {
        $url .= '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
    }

    $headers = [
        'Accept'     => 'application/json',
        'User-Agent' => 'TONBANKCARD-MarketGateway/' . GECKO_CLIENT_VERSION,
    ];

    if ( ! empty( $provider['api_key_configured'] ) ) {
        $headers[ $provider['api_key_header'] ] = $provider['api_key'];
    }

    $transport_request = [
        'method'          => 'GET',
        'url'             => $url,
        'path'            => $upstream_path,
        'query'           => $query,
        'headers'         => $headers,
        'timeout_seconds' => $provider['timeout_seconds'],
        'provider'        => $provider['name'],
        'plan'            => $provider['plan'],
    ];

    if ( isset( $config['market_data']['transport'] ) && is_callable( $config['market_data']['transport'] ) ) {
        try {
            $response = call_user_func( $config['market_data']['transport'], $transport_request );
            return is_array( $response ) ? $response : [
                'error'   => 'transport_error',
                'message' => 'Market data transport returned an invalid response.',
            ];
        } catch ( Throwable $exception ) {
            return [
                'error'   => 'transport_error',
                'message' => 'Market data transport failed.',
            ];
        }
    }

    if ( function_exists( 'curl_init' ) ) {
        return tonbankcard_api_market_fetch_with_curl( $transport_request );
    }

    return tonbankcard_api_market_fetch_with_stream( $transport_request );
}

/**
 * Fetches with cURL when the extension is available.
 *
 * @param array $request
 * @return array
 */
function tonbankcard_api_market_fetch_with_curl( array $request ) {
    $handle = curl_init( $request['url'] );
    if ( FALSE === $handle ) {
        return [
            'error'   => 'transport_error',
            'message' => 'Market data transport could not start.',
        ];
    }

    $response_headers = [];
    $header_lines = [];
    foreach ( $request['headers'] as $name => $value ) {
        $header_lines[] = $name . ': ' . $value;
    }

    curl_setopt_array(
        $handle,
        [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => (int) $request['timeout_seconds'],
            CURLOPT_CONNECTTIMEOUT => min( 5, (int) $request['timeout_seconds'] ),
            CURLOPT_HTTPHEADER     => $header_lines,
            CURLOPT_HEADERFUNCTION => function ( $curl, $line ) use ( &$response_headers ) {
                $length = strlen( $line );
                $line = trim( $line );
                if ( '' === $line || 0 === stripos( $line, 'HTTP/' ) ) {
                    return $length;
                }

                $separator = strpos( $line, ':' );
                if ( FALSE !== $separator ) {
                    $name = strtolower( trim( substr( $line, 0, $separator ) ) );
                    $response_headers[ $name ] = trim( substr( $line, $separator + 1 ) );
                }

                return $length;
            },
        ]
    );

    $body = curl_exec( $handle );
    $errno = curl_errno( $handle );
    $status = (int) curl_getinfo( $handle, CURLINFO_HTTP_CODE );
    $message = curl_error( $handle );
    curl_close( $handle );

    if ( 0 !== $errno ) {
        return [
            'error'   => defined( 'CURLE_OPERATION_TIMEDOUT' ) && CURLE_OPERATION_TIMEDOUT === $errno ? 'timeout' : 'transport_error',
            'message' => $message,
            'status'  => $status,
            'headers' => $response_headers,
        ];
    }

    return [
        'status'  => $status,
        'headers' => $response_headers,
        'body'    => FALSE === $body ? '' : $body,
    ];
}

/**
 * Fetches with PHP streams when cURL is unavailable.
 *
 * @param array $request
 * @return array
 */
function tonbankcard_api_market_fetch_with_stream( array $request ) {
    $header_lines = [];
    foreach ( $request['headers'] as $name => $value ) {
        $header_lines[] = $name . ': ' . $value;
    }

    $context = stream_context_create(
        [
            'http' => [
                'method'        => 'GET',
                'header'        => implode( "\r\n", $header_lines ),
                'timeout'       => (int) $request['timeout_seconds'],
                'ignore_errors' => TRUE,
            ],
        ]
    );

    $body = @file_get_contents( $request['url'], FALSE, $context );
    $headers = tonbankcard_api_market_parse_header_lines( isset( $http_response_header ) ? $http_response_header : [] );

    if ( FALSE === $body ) {
        return [
            'error'   => 'transport_error',
            'message' => 'Market data stream transport failed.',
            'status'  => isset( $headers[':status'] ) ? (int) $headers[':status'] : 0,
            'headers' => $headers,
        ];
    }

    return [
        'status'  => isset( $headers[':status'] ) ? (int) $headers[':status'] : 200,
        'headers' => $headers,
        'body'    => $body,
    ];
}

/**
 * Parses HTTP response header lines into lower-case keys.
 *
 * @param array $lines
 * @return array
 */
function tonbankcard_api_market_parse_header_lines( array $lines ) {
    $headers = [];
    foreach ( $lines as $line ) {
        if ( preg_match( '/^HTTP\/\S+\s+(\d+)/', $line, $matches ) ) {
            $headers[':status'] = (int) $matches[1];
            continue;
        }

        $separator = strpos( $line, ':' );
        if ( FALSE !== $separator ) {
            $headers[ strtolower( trim( substr( $line, 0, $separator ) ) ) ] = trim( substr( $line, $separator + 1 ) );
        }
    }

    return $headers;
}

/**
 * Converts a provider response to the public API envelope.
 *
 * @param array $upstream_response
 * @param array $route
 * @param array $provider
 * @param string $request_id
 * @param array $headers
 * @param int $fetched_at
 * @param array $cache_context
 * @return array
 */
function tonbankcard_api_market_response( array $upstream_response, array $route, array $provider, string $request_id, array $headers, int $fetched_at, array $cache_context = [], array $runtime = [], array $config = [], array $query = [] ) {
    $provider_headers = tonbankcard_api_market_normalize_headers( isset( $upstream_response['headers'] ) && is_array( $upstream_response['headers'] ) ? $upstream_response['headers'] : [] );

    if ( ! empty( $upstream_response['error'] ) ) {
        $fallback = tonbankcard_api_market_stale_fallback_response( $cache_context, $route, $provider, $request_id, $headers );
        if ( null !== $fallback ) {
            return $fallback;
        }

        $error = (string) $upstream_response['error'];
        if ( 'timeout' === $error ) {
            return tonbankcard_api_market_provider_error_response(
                504,
                'provider_timeout',
                'Market data provider timed out.',
                $route,
                $provider,
                isset( $upstream_response['status'] ) ? (int) $upstream_response['status'] : 0,
                $request_id,
                $headers
            );
        }

        return tonbankcard_api_market_provider_error_response(
            502,
            'provider_unavailable',
            'Market data provider request failed.',
            $route,
            $provider,
            isset( $upstream_response['status'] ) ? (int) $upstream_response['status'] : 0,
            $request_id,
            $headers
        );
    }

    $status = isset( $upstream_response['status'] ) ? (int) $upstream_response['status'] : 0;
    if ( 200 > $status || 300 <= $status ) {
        if ( 404 === $status ) {
            $fallback = tonbankcard_api_market_ton_fallback_response( $route, $query, $provider, $request_id, $headers, $fetched_at, $runtime, $config );
            if ( null !== $fallback ) {
                return $fallback;
            }
        }

        $fallback = tonbankcard_api_market_stale_fallback_response( $cache_context, $route, $provider, $request_id, $headers );
        if ( null !== $fallback ) {
            return $fallback;
        }

        $headers = tonbankcard_api_market_copy_provider_headers( $headers, $provider_headers );
        return tonbankcard_api_market_http_error_response( $status, $route, $provider, $request_id, $headers );
    }

    $body = isset( $upstream_response['body'] ) ? (string) $upstream_response['body'] : '';
    $data = json_decode( $body, TRUE );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
        $fallback = tonbankcard_api_market_stale_fallback_response( $cache_context, $route, $provider, $request_id, $headers );
        if ( null !== $fallback ) {
            return $fallback;
        }

        return tonbankcard_api_market_provider_error_response(
            502,
            'provider_invalid_json',
            'Market data provider returned invalid JSON.',
            $route,
            $provider,
            $status,
            $request_id,
            $headers
        );
    }

    $cache_meta = [
        'cache_status'      => ! empty( $cache_context['enabled'] ) ? 'miss' : ( isset( $cache_context['state'] ) ? $cache_context['state'] : 'pass' ),
        'cache_age_seconds' => 0,
        'cache_ttl_seconds' => isset( $cache_context['ttl_seconds'] ) ? (int) $cache_context['ttl_seconds'] : $provider['cache_ttl_seconds'],
    ];
    if ( ! empty( $cache_context['enabled'] ) ) {
        tonbankcard_api_cache_set(
            isset( $cache_context['runtime'] ) ? $cache_context['runtime'] : [],
            isset( $cache_context['config'] ) ? $cache_context['config'] : [],
            $cache_context['key'],
            $data,
            [
                'status' => $status,
                'path'   => $route['upstream_path'],
            ],
            isset( $cache_context['ttl_seconds'] ) ? (int) $cache_context['ttl_seconds'] : 0,
            isset( $cache_context['stale_ttl_seconds'] ) ? (int) $cache_context['stale_ttl_seconds'] : 0,
            $fetched_at
        );
    }

    return tonbankcard_api_success_response(
        $data,
        $request_id,
        $headers,
        200,
        tonbankcard_api_market_response_meta( $route, $provider, $data, $status, $fetched_at, $cache_meta )
    );
}

/**
 * Serves curated TON assets through CoinGecko-shaped coin routes when the
 * upstream provider does not know a manually added jetton id.
 *
 * @param array $route
 * @param array $query
 * @param array $provider
 * @param string $request_id
 * @param array $headers
 * @param int $fetched_at
 * @param array $runtime
 * @param array $config
 * @return array|null
 */
function tonbankcard_api_market_ton_fallback_response( array $route, array $query, array $provider, string $request_id, array $headers, int $fetched_at, array $runtime, array $config ) {
    if ( empty( $route['upstream_path'] ) || ! function_exists( 'tonbankcard_api_ton_load_state' ) ) {
        return null;
    }

    if ( ! preg_match( '#^coins/([^/]+)(?:/(market_chart)(?:/range)?|/tickers)?$#', (string) $route['upstream_path'], $matches ) ) {
        return null;
    }

    $asset_id = rawurldecode( $matches[1] );
    $asset = tonbankcard_api_market_ton_asset_for_id( $asset_id, $runtime, $config );
    if ( null === $asset ) {
        return null;
    }

    $path = (string) $route['upstream_path'];
    if ( FALSE !== strpos( $path, '/tickers' ) ) {
        $data = [ 'name' => isset( $asset['name'] ) ? $asset['name'] : $asset_id, 'tickers' => [] ];
    } elseif ( FALSE !== strpos( $path, '/market_chart' ) ) {
        $data = [ 'prices' => [], 'market_caps' => [], 'total_volumes' => [] ];
    } else {
        $data = tonbankcard_api_market_ton_coin_detail( $asset, $query, $fetched_at );
    }

    return tonbankcard_api_success_response(
        $data,
        $request_id,
        $headers,
        200,
        tonbankcard_api_market_response_meta(
            $route,
            tonbankcard_api_market_ton_provider_meta( $provider ),
            $data,
            404,
            $fetched_at,
            [
                'cache_status'      => 'ton_curation_fallback',
                'cache_age_seconds' => 0,
                'cache_ttl_seconds' => 0,
            ]
        )
    );
}

/**
 * Finds a visible curated TON asset by its public route id.
 *
 * @param string $id
 * @param array $runtime
 * @param array $config
 * @return array|null
 */
function tonbankcard_api_market_ton_asset_for_id( string $id, array $runtime, array $config ) {
    $id = strtolower( trim( $id ) );
    if ( '' === $id ) {
        return null;
    }

    $state = tonbankcard_api_ton_load_state( $runtime, $config );
    foreach ( isset( $state['assets'] ) && is_array( $state['assets'] ) ? $state['assets'] : [] as $asset ) {
        if ( empty( $asset['visible'] ) ) {
            continue;
        }
        $asset_id = isset( $asset['id'] ) ? strtolower( (string) $asset['id'] ) : '';
        $route_path = isset( $asset['route']['path'] ) ? (string) $asset['route']['path'] : '';
        if ( $id === $asset_id || '/currency/' . rawurlencode( $id ) === $route_path ) {
            return $asset;
        }
    }

    return null;
}

/**
 * Builds a CoinGecko-compatible coin detail payload from TON curation data.
 *
 * @param array $asset
 * @param array $query
 * @param int $fetched_at
 * @return array
 */
function tonbankcard_api_market_ton_coin_detail( array $asset, array $query, int $fetched_at ) {
    $id = isset( $asset['id'] ) ? (string) $asset['id'] : '';
    $symbol = isset( $asset['symbol'] ) ? strtolower( (string) $asset['symbol'] ) : '';
    $name = isset( $asset['name'] ) ? (string) $asset['name'] : $id;
    $description = isset( $asset['description'] ) ? (string) $asset['description'] : '';
    $contract = '';
    if ( ! empty( $asset['contract_addresses'] ) && is_array( $asset['contract_addresses'] ) ) {
        $contract = (string) reset( $asset['contract_addresses'] );
    }

    return [
        'id'                              => $id,
        'symbol'                          => $symbol,
        'name'                            => $name,
        'asset_platform_id'               => 'the-open-network',
        'platforms'                       => '' === $contract ? [] : [ 'the-open-network' => $contract ],
        'detail_platforms'                => '' === $contract ? [] : [
            'the-open-network' => [
                'decimal_place'    => null,
                'contract_address' => $contract,
            ],
        ],
        'categories'                      => [ 'TON ecosystem', isset( $asset['category'] ) ? (string) $asset['category'] : 'jetton' ],
        'description'                     => [ 'en' => $description ],
        'links'                           => [ 'homepage' => [ isset( $asset['links']['web'] ) ? (string) $asset['links']['web'] : '/ton' ] ],
        'image'                           => [ 'thumb' => '', 'small' => '', 'large' => '' ],
        'market_cap_rank'                 => null,
        'coingecko_rank'                  => null,
        'coingecko_score'                 => null,
        'developer_score'                 => null,
        'community_score'                 => null,
        'liquidity_score'                 => null,
        'public_interest_score'           => null,
        'market_data'                     => tonbankcard_api_market_ton_empty_market_data( $query ),
        'last_updated'                    => gmdate( 'c', $fetched_at ),
        'ton_asset'                       => $asset,
    ];
}

/**
 * Builds empty per-currency market data buckets expected by the coin page.
 *
 * @param array $query
 * @return array
 */
function tonbankcard_api_market_ton_empty_market_data( array $query ) {
    $vs = isset( $query['vs_currency'] ) ? strtolower( trim( (string) $query['vs_currency'] ) ) : 'usd';
    if ( '' === $vs || ! preg_match( '/^[a-z0-9._-]{1,30}$/', $vs ) ) {
        $vs = 'usd';
    }
    $empty = [ $vs => null ];

    return [
        'current_price'                                  => $empty,
        'price_change_percentage_24h_in_currency'        => $empty,
        'high_24h'                                       => $empty,
        'low_24h'                                        => $empty,
        'market_cap'                                     => $empty,
        'market_cap_change_24h_in_currency'              => $empty,
        'market_cap_change_percentage_24h_in_currency'   => $empty,
        'fully_diluted_valuation'                        => $empty,
        'total_volume'                                   => $empty,
        'circulating_supply'                             => null,
        'total_supply'                                   => null,
        'max_supply'                                     => null,
    ];
}

/**
 * Marks provider metadata as served from TON curation after a CoinGecko miss.
 *
 * @param array $provider
 * @return array
 */
function tonbankcard_api_market_ton_provider_meta( array $provider ) {
    $provider['fallback'] = 'ton_curation';
    return $provider;
}

/**
 * Builds a success response from a cached market entry.
 *
 * @param array $cached
 * @param array $route
 * @param array $provider
 * @param string $request_id
 * @param array $headers
 * @param array $cache_context
 * @param array $cache_meta
 * @return array
 */
function tonbankcard_api_market_cache_response( array $cached, array $route, array $provider, string $request_id, array $headers, array $cache_context, array $cache_meta ) {
    $entry = isset( $cached['entry'] ) && is_array( $cached['entry'] ) ? $cached['entry'] : [];
    $data = isset( $entry['data'] ) && is_array( $entry['data'] ) ? $entry['data'] : [];
    $stored_at = isset( $entry['stored_at'] ) ? (int) $entry['stored_at'] : time();
    $upstream = isset( $entry['upstream'] ) && is_array( $entry['upstream'] ) ? $entry['upstream'] : [];
    $upstream_status = isset( $upstream['status'] ) ? (int) $upstream['status'] : 200;

    $cache_meta = array_merge(
        [
            'cache_status'      => isset( $cache_meta['cache_status'] ) ? $cache_meta['cache_status'] : 'hit',
            'cache_age_seconds' => isset( $cached['cache_age_seconds'] ) ? (int) $cached['cache_age_seconds'] : 0,
            'stale_age_seconds' => isset( $cached['stale_age_seconds'] ) ? (int) $cached['stale_age_seconds'] : 0,
            'cache_ttl_seconds' => isset( $cache_context['ttl_seconds'] ) ? (int) $cache_context['ttl_seconds'] : $provider['cache_ttl_seconds'],
        ],
        $cache_meta
    );

    return tonbankcard_api_success_response(
        $data,
        $request_id,
        $headers,
        200,
        tonbankcard_api_market_response_meta( $route, $provider, $data, $upstream_status, $stored_at, $cache_meta )
    );
}

/**
 * Serves stale cache data when the upstream provider cannot supply a fresh response.
 *
 * @param array $cache_context
 * @param array $route
 * @param array $provider
 * @param string $request_id
 * @param array $headers
 * @return array|null
 */
function tonbankcard_api_market_stale_fallback_response( array $cache_context, array $route, array $provider, string $request_id, array $headers ) {
    if ( empty( $cache_context['enabled'] ) || empty( $cache_context['stale'] ) || ! is_array( $cache_context['stale'] ) ) {
        return null;
    }

    $runtime = isset( $cache_context['runtime'] ) && is_array( $cache_context['runtime'] ) ? $cache_context['runtime'] : [];
    $config = isset( $cache_context['config'] ) && is_array( $cache_context['config'] ) ? $cache_context['config'] : [];
    tonbankcard_api_metric_increment( $runtime, $config, 'cache.upstream_fallback' );
    tonbankcard_api_metric_set(
        $runtime,
        $config,
        'cache.stale_age_seconds',
        isset( $cache_context['stale']['stale_age_seconds'] ) ? (int) $cache_context['stale']['stale_age_seconds'] : 0
    );

    return tonbankcard_api_market_cache_response(
        $cache_context['stale'],
        $route,
        $provider,
        $request_id,
        $headers,
        $cache_context,
        [
            'cache_status'      => 'stale',
            'upstream_fallback' => TRUE,
        ]
    );
}

/**
 * Logs the provider response using safe trace context.
 *
 * @param array $upstream_response
 * @param array $route
 * @param array $provider
 * @param string $request_id
 * @param array $response
 * @param array $runtime
 * @param array $config
 * @param int $duration_ms
 * @return void
 */
function tonbankcard_api_market_log_provider_response( array $upstream_response, array $route, array $provider, string $request_id, array $response, array $runtime, array $config, int $duration_ms ) {
    $status = isset( $response['status'] ) ? (int) $response['status'] : 500;
    $upstream_status = isset( $upstream_response['status'] ) ? (int) $upstream_response['status'] : 0;
    $provider_error = isset( $upstream_response['error'] ) ? (string) $upstream_response['error'] : null;
    $error_code = function_exists( 'tonbankcard_api_response_error_code' ) ? tonbankcard_api_response_error_code( $response ) : null;
    $provider_headers = tonbankcard_api_market_normalize_headers( isset( $upstream_response['headers'] ) && is_array( $upstream_response['headers'] ) ? $upstream_response['headers'] : [] );
    $level = 500 <= $status ? 'error' : ( 400 <= $status ? 'warning' : 'info' );

    tonbankcard_observability_log(
        $runtime,
        $config,
        $level,
        'market.provider_response',
        [
            'request_id'          => $request_id,
            'provider'            => $provider['name'],
            'provider_plan'       => $provider['plan'],
            'gateway_path'        => $route['gateway_path'],
            'upstream_path'       => $route['upstream_path'],
            'status'              => $status,
            'upstream_status'     => $upstream_status,
            'provider_error'      => $provider_error,
            'error_code'          => $error_code,
            'retry_after_present' => isset( $provider_headers['retry-after'] ),
            'duration_ms'         => $duration_ms,
        ]
    );
}

/**
 * Builds normalized provider HTTP error responses.
 *
 * @param int $status
 * @param array $route
 * @param array $provider
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_market_http_error_response( int $status, array $route, array $provider, string $request_id, array $headers ) {
    if ( 429 === $status ) {
        return tonbankcard_api_market_provider_error_response(
            429,
            'provider_rate_limited',
            'Market data provider rate limit exceeded.',
            $route,
            $provider,
            $status,
            $request_id,
            $headers
        );
    }

    if ( 404 === $status ) {
        return tonbankcard_api_market_provider_error_response(
            404,
            'invalid_symbol',
            'Market data provider could not find the requested coin, exchange, or market symbol.',
            $route,
            $provider,
            $status,
            $request_id,
            $headers
        );
    }

    if ( in_array( $status, [ 401, 403 ], TRUE ) ) {
        return tonbankcard_api_market_provider_error_response(
            502,
            'provider_auth_failed',
            'Market data provider authentication failed.',
            $route,
            $provider,
            $status,
            $request_id,
            $headers
        );
    }

    return tonbankcard_api_market_provider_error_response(
        502,
        'provider_unavailable',
        'Market data provider returned an upstream error.',
        $route,
        $provider,
        $status,
        $request_id,
        $headers
    );
}

/**
 * Builds an error response with safe provider details.
 *
 * @param int $status
 * @param string $code
 * @param string $message
 * @param array $route
 * @param array $provider
 * @param int $upstream_status
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_market_provider_error_response( int $status, string $code, string $message, array $route, array $provider, int $upstream_status, string $request_id, array $headers ) {
    return tonbankcard_api_error_response(
        $status,
        $code,
        $message,
        [
            'provider'        => $provider['name'],
            'provider_plan'   => $provider['plan'],
            'gateway_path'    => $route['gateway_path'],
            'upstream_path'   => $route['upstream_path'],
            'upstream_status' => $upstream_status,
        ],
        $request_id,
        $headers
    );
}

/**
 * Copies selected safe provider headers onto the public response.
 *
 * @param array $headers
 * @param array $provider_headers
 * @return array
 */
function tonbankcard_api_market_copy_provider_headers( array $headers, array $provider_headers ) {
    if ( isset( $provider_headers['retry-after'] ) ) {
        $headers['Retry-After'] = $provider_headers['retry-after'];
    }

    return $headers;
}

/**
 * Normalizes provider response header names.
 *
 * @param array $headers
 * @return array
 */
function tonbankcard_api_market_normalize_headers( array $headers ) {
    $normalized = [];
    foreach ( $headers as $name => $value ) {
        $normalized[ strtolower( (string) $name ) ] = is_array( $value ) ? implode( ',', $value ) : (string) $value;
    }

    return $normalized;
}

/**
 * Builds base market metadata for index responses.
 *
 * @param array $runtime
 * @param array $config
 * @param int $fetched_at
 * @return array
 */
function tonbankcard_api_market_base_meta( array $runtime, array $config, int $fetched_at ) {
    $provider = tonbankcard_api_market_provider_config( $runtime, $config );

    return [
        'provider' => tonbankcard_api_market_provider_meta( $provider ),
        'freshness' => [
            'fetched_at'        => gmdate( 'c', $fetched_at ),
            'last_updated_at'   => gmdate( 'c', $fetched_at ),
            'cache_age_seconds' => 0,
            'cache_status'      => 'pass',
        ],
    ];
}

/**
 * Builds market response metadata for UI freshness and attribution.
 *
 * @param array $route
 * @param array $provider
 * @param array $data
 * @param int $upstream_status
 * @param int $fetched_at
 * @param array $cache_meta
 * @return array
 */
function tonbankcard_api_market_response_meta( array $route, array $provider, array $data, int $upstream_status, int $fetched_at, array $cache_meta = [] ) {
    $last_updated_at = tonbankcard_api_market_data_timestamp( $data );
    if ( null === $last_updated_at ) {
        $last_updated_at = $fetched_at;
    }

    $freshness = [
        'fetched_at'        => gmdate( 'c', $fetched_at ),
        'last_updated_at'   => gmdate( 'c', $last_updated_at ),
        'cache_age_seconds' => isset( $cache_meta['cache_age_seconds'] ) ? (int) $cache_meta['cache_age_seconds'] : 0,
        'cache_status'      => isset( $cache_meta['cache_status'] ) ? (string) $cache_meta['cache_status'] : 'pass',
        'data_age_seconds'  => max( 0, time() - $last_updated_at ),
        'cache_ttl_seconds' => isset( $cache_meta['cache_ttl_seconds'] ) ? (int) $cache_meta['cache_ttl_seconds'] : $provider['cache_ttl_seconds'],
    ];
    if ( isset( $cache_meta['stale_age_seconds'] ) && 0 < (int) $cache_meta['stale_age_seconds'] ) {
        $freshness['stale_age_seconds'] = (int) $cache_meta['stale_age_seconds'];
    }
    if ( ! empty( $cache_meta['upstream_fallback'] ) ) {
        $freshness['upstream_fallback'] = TRUE;
    }
    if ( ! empty( $cache_meta['coalesced'] ) ) {
        $freshness['coalesced'] = TRUE;
    }

    return [
        'provider' => tonbankcard_api_market_provider_meta( $provider ),
        'freshness' => $freshness,
        'upstream' => [
            'status' => $upstream_status,
            'path'   => $route['upstream_path'],
        ],
    ];
}

/**
 * Returns safe provider metadata.
 *
 * @param array $provider
 * @return array
 */
function tonbankcard_api_market_provider_meta( array $provider ) {
    $meta = [
        'name'          => $provider['name'],
        'plan'          => $provider['plan'],
        'attribution'   => $provider['attribution'],
        'credentialed'  => (bool) $provider['api_key_configured'],
    ];
    if ( ! empty( $provider['fallback'] ) ) {
        $meta['fallback'] = (string) $provider['fallback'];
    }

    return $meta;
}

/**
 * Extracts the most relevant timestamp from CoinGecko payloads.
 *
 * @param mixed $data
 * @return int|null
 */
function tonbankcard_api_market_data_timestamp( $data ) {
    if ( ! is_array( $data ) || empty( $data ) ) {
        return null;
    }

    foreach ( [ 'last_updated', 'last_updated_at', 'updated_at' ] as $key ) {
        if ( isset( $data[ $key ] ) ) {
            $timestamp = tonbankcard_api_market_parse_timestamp( $data[ $key ] );
            if ( null !== $timestamp ) {
                return $timestamp;
            }
        }
    }

    if ( isset( $data['market_data']['last_updated'] ) ) {
        $timestamp = tonbankcard_api_market_parse_timestamp( $data['market_data']['last_updated'] );
        if ( null !== $timestamp ) {
            return $timestamp;
        }
    }

    if ( isset( $data['data']['updated_at'] ) ) {
        $timestamp = tonbankcard_api_market_parse_timestamp( $data['data']['updated_at'] );
        if ( null !== $timestamp ) {
            return $timestamp;
        }
    }

    $first = reset( $data );
    if ( is_array( $first ) ) {
        return tonbankcard_api_market_data_timestamp( $first );
    }

    return null;
}

/**
 * Parses numeric or string timestamps.
 *
 * @param mixed $value
 * @return int|null
 */
function tonbankcard_api_market_parse_timestamp( $value ) {
    if ( is_numeric( $value ) ) {
        $timestamp = (int) $value;
        return $timestamp > 0 ? $timestamp : null;
    }

    if ( is_string( $value ) && '' !== trim( $value ) ) {
        $timestamp = strtotime( $value );
        return FALSE === $timestamp ? null : $timestamp;
    }

    return null;
}
