<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 SMART SEARCH API
 * -------------------------------------------------------------------------
 * First-party search endpoint and index builder for website and Telegram Mini
 * App clients. The browser queries /api/search instead of third-party static
 * JSON, while the backend owns provider fetching, ranking, and Redis caching.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Returns TRUE when a normalized path belongs to the smart search API surface.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_search_is_request( string $path ) {
    return '/api/search' === $path || 0 === strpos( $path, '/api/search/' );
}

/**
 * Returns documented search API routes.
 *
 * @return array
 */
function tonbankcard_api_search_routes() {
    return [
        '/api/search',
        '/api/search/refresh',
    ];
}

/**
 * Handles a smart search API request using the shared API envelope helpers.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_search_handle( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $path = tonbankcard_api_normalize_path( $request['path'] );

    if ( '/api/search/refresh' === $path ) {
        if ( 'POST' !== $request['method'] ) {
            return tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers );
        }

        if ( ! tonbankcard_api_search_refresh_allowed( $request, $runtime, $config ) ) {
            return tonbankcard_api_error_response(
                403,
                'search_refresh_forbidden',
                'Search index refresh requires the configured refresh token outside local development.',
                [ 'config' => 'TONBANKCARD_SEARCH_REFRESH_TOKEN' ],
                $request_id,
                $headers
            );
        }

        $state = tonbankcard_api_search_refresh_index( $runtime, $config );
        return tonbankcard_api_success_response(
            tonbankcard_api_search_index_payload( $state ),
            $request_id,
            $headers,
            200,
            tonbankcard_api_search_meta( $state, '', 0 )
        );
    }

    if ( '/api/search' !== $path ) {
        return tonbankcard_api_error_response(
            404,
            'not_found',
            'No smart search route matches the request.',
            [ 'path' => $path ],
            $request_id,
            $headers
        );
    }

    if ( 'GET' !== $request['method'] ) {
        return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
    }

    $settings = tonbankcard_api_search_settings( $config );
    $query = tonbankcard_api_search_query( isset( $request['query']['q'] ) ? $request['query']['q'] : '' );
    $limit = tonbankcard_api_search_limit( isset( $request['query']['limit'] ) ? $request['query']['limit'] : null, $settings );
    $surface = tonbankcard_api_search_surface( isset( $request['query']['surface'] ) ? $request['query']['surface'] : '' );
    $state = tonbankcard_api_search_index( $runtime, $config );
    $results = tonbankcard_api_search_results( isset( $state['index']['entries'] ) ? $state['index']['entries'] : [], $query, $limit, $surface );

    return tonbankcard_api_success_response(
        [
            'query'            => $query,
            'normalized_query' => tonbankcard_api_search_normalize_text( $query ),
            'surface'          => $surface,
            'result_count'     => count( $results ),
            'results'          => $results,
        ],
        $request_id,
        $headers,
        200,
        tonbankcard_api_search_meta( $state, $query, count( $results ) )
    );
}

/**
 * Returns TRUE when the refresh request is allowed.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @return bool
 */
function tonbankcard_api_search_refresh_allowed( array $request, array $runtime, array $config ) {
    $settings = tonbankcard_api_search_settings( $config );
    $configured = isset( $settings['refresh_token'] ) ? trim( (string) $settings['refresh_token'] ) : '';

    if ( '' === $configured && isset( $runtime['profile'] ) && 'local' === $runtime['profile'] ) {
        return TRUE;
    }

    if ( '' === $configured ) {
        return FALSE;
    }

    $provided = '';
    if ( isset( $request['headers']['x-tonbankcard-search-refresh-token'] ) ) {
        $provided = trim( (string) $request['headers']['x-tonbankcard-search-refresh-token'] );
    } elseif ( isset( $request['query']['token'] ) ) {
        $provided = trim( (string) $request['query']['token'] );
    }

    return '' !== $provided && hash_equals( $configured, $provided );
}

/**
 * Builds search configuration with conservative defaults.
 *
 * @param array $config
 * @return array
 */
function tonbankcard_api_search_settings( array $config ) {
    $search = isset( $config['search'] ) && is_array( $config['search'] ) ? $config['search'] : [];

    return array_merge(
        [
            'index_cache_key'         => 'tonbankcard:v2:search:index',
            'index_ttl_seconds'      => 900,
            'default_limit'          => 12,
            'max_limit'              => 30,
            'redis_timeout_seconds'  => 2,
            'refresh_token'          => '',
            'curated_entries'        => tonbankcard_api_search_default_curated_entries(),
        ],
        $search
    );
}

/**
 * Returns the built-in TONBANKCARD search entries.
 *
 * @return array
 */
function tonbankcard_api_search_default_curated_entries() {
    return [
        [
            'type'          => 'action',
            'id'            => 'trending',
            'title'         => 'Trending coins',
            'subtitle'      => 'Popular market searches',
            'tags'          => [ 'trending', 'market' ],
            'aliases'       => [ 'popular', 'hot', 'movers', 'gainers', 'market pulse' ],
            'popular_score' => 1000,
            'route'         => [
                'name'  => 'currencies',
                'query' => [ 'view' => 'trending' ],
                'path'  => '/?view=trending',
            ],
            'links'         => [
                'web'      => '/?view=trending',
                'telegram' => '/app/search?view=trending',
            ],
        ],
        [
            'type'          => 'action',
            'id'            => 'exchanges',
            'title'         => 'Exchanges',
            'subtitle'      => 'Browse crypto exchanges',
            'tags'          => [ 'exchange', 'market' ],
            'aliases'       => [ 'exchange list', 'markets', 'trading venues' ],
            'popular_score' => 980,
            'route'         => [
                'name' => 'exchanges',
                'path' => '/exchanges',
            ],
            'links'         => [
                'web'      => '/exchanges',
                'telegram' => '/app/search?type=exchange',
            ],
        ],
        [
            'type'          => 'action',
            'id'            => 'ton-ecosystem',
            'title'         => 'TON ecosystem',
            'subtitle'      => 'Toncoin, jettons, DeFi, and stablecoins',
            'tags'          => [ 'ton_ecosystem', 'ton', 'jetton' ],
            'aliases'       => [ 'ton assets', 'ton jettons', 'telegram crypto', 'the open network' ],
            'popular_score' => 970,
            'route'         => [
                'name'  => 'currencies',
                'query' => [ 'tag' => 'ton' ],
                'path'  => '/?tag=ton',
            ],
            'links'         => [
                'web'      => '/?tag=ton',
                'telegram' => '/app/ton',
            ],
        ],
        [
            'type'               => 'coin',
            'id'                 => 'toncoin',
            'coin_id'            => 'toncoin',
            'title'              => 'Toncoin',
            'symbol'             => 'TON',
            'subtitle'           => 'TON',
            'tags'               => [ 'ton_ecosystem', 'native_ton', 'the_open_network' ],
            'aliases'            => [ 'ton', 'the open network', 'telegram open network' ],
            'contract_addresses' => [],
            'popular_score'      => 930,
            'route'              => [
                'name'   => 'currency',
                'params' => [ 'id' => 'toncoin' ],
                'path'   => '/currency/toncoin',
            ],
            'links'              => [
                'web'      => '/currency/toncoin',
                'telegram' => '/app/coin/toncoin',
            ],
        ],
        [
            'type'               => 'ton_asset',
            'id'                 => 'tether-usd-ton',
            'coin_id'            => 'tether',
            'title'              => 'Tether USD on TON',
            'symbol'             => 'USDT',
            'subtitle'           => 'USDT on TON',
            'tags'               => [ 'ton_ecosystem', 'ton_asset', 'jetton', 'stablecoin' ],
            'aliases'            => [ 'usdt ton', 'usd ton', 'tether ton', 'usdt on ton', 'usdtton', 'usdton' ],
            'contract_addresses' => [ 'EQCxE6mUtQJKFnGfaROTKOt1lZbDiiX1kCixRv7Nw2Id_sDs' ],
            'popular_score'      => 920,
            'route'              => [
                'name'   => 'currency',
                'params' => [ 'id' => 'tether' ],
                'query'  => [ 'network' => 'ton' ],
                'path'   => '/currency/tether?network=ton',
            ],
            'links'              => [
                'web'      => '/currency/tether?network=ton',
                'telegram' => '/app/coin/tether?network=ton',
            ],
        ],
    ];
}

/**
 * Returns a bounded user search query.
 *
 * @param mixed $query
 * @return string
 */
function tonbankcard_api_search_query( $query ) {
    $query = trim( preg_replace( '/\s+/', ' ', (string) $query ) );
    return substr( $query, 0, 160 );
}

/**
 * Returns a safe result limit.
 *
 * @param mixed $limit
 * @param array $settings
 * @return int
 */
function tonbankcard_api_search_limit( $limit, array $settings ) {
    $default = isset( $settings['default_limit'] ) ? max( 1, (int) $settings['default_limit'] ) : 12;
    $max = isset( $settings['max_limit'] ) ? max( $default, (int) $settings['max_limit'] ) : 30;

    if ( null === $limit || '' === $limit ) {
        return $default;
    }

    return min( $max, max( 1, (int) $limit ) );
}

/**
 * Returns a supported client surface.
 *
 * @param mixed $surface
 * @return string
 */
function tonbankcard_api_search_surface( $surface ) {
    $surface = strtolower( trim( (string) $surface ) );
    if ( in_array( $surface, [ 'telegram', 'telegram_mini_app' ], TRUE ) ) {
        return 'telegram_mini_app';
    }
    if ( in_array( $surface, [ 'mobile_web', 'public_web', 'web' ], TRUE ) ) {
        return 'public_web';
    }
    return 'public_web';
}

/**
 * Loads or builds the search index.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_search_index( array $runtime, array $config ) {
    $settings = tonbankcard_api_search_settings( $config );
    $cached = tonbankcard_api_search_cache_get( $runtime, $config, $settings );
    if ( ! empty( $cached['ok'] ) && tonbankcard_api_search_valid_index( $cached['index'] ) ) {
        return [
            'ok'           => TRUE,
            'cache_status' => 'hit',
            'index'        => $cached['index'],
            'warnings'     => [],
        ];
    }

    $state = tonbankcard_api_search_refresh_index( $runtime, $config );
    if ( empty( $state['cache_status'] ) ) {
        $state['cache_status'] = ! empty( $cached['configured'] ) ? 'refresh' : 'pass';
    }
    if ( ! empty( $cached['warning'] ) ) {
        $state['warnings'][] = $cached['warning'];
    }

    return $state;
}

/**
 * Rebuilds the search index and stores it in Redis when configured.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_search_refresh_index( array $runtime, array $config ) {
    $settings = tonbankcard_api_search_settings( $config );
    $index = tonbankcard_api_search_build_index( $runtime, $config, $settings );
    $stored = tonbankcard_api_search_cache_set( $runtime, $config, $settings, $index );

    return [
        'ok'           => TRUE,
        'cache_status' => ! empty( $stored['configured'] ) ? ( ! empty( $stored['ok'] ) ? 'refresh' : 'refresh_error' ) : 'pass',
        'index'        => $index,
        'warnings'     => array_values(
            array_filter(
                array_merge(
                    isset( $index['warnings'] ) && is_array( $index['warnings'] ) ? $index['warnings'] : [],
                    empty( $stored['warning'] ) ? [] : [ $stored['warning'] ]
                )
            )
        ),
    ];
}

/**
 * Builds a fresh index from curated data and safe provider endpoints.
 *
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @return array
 */
function tonbankcard_api_search_build_index( array $runtime, array $config, array $settings ) {
    $entries = [];
    $warnings = [];
    $provider = function_exists( 'tonbankcard_api_market_provider_config' )
        ? tonbankcard_api_market_provider_config( $runtime, $config )
        : [];

    foreach ( isset( $settings['curated_entries'] ) && is_array( $settings['curated_entries'] ) ? $settings['curated_entries'] : [] as $entry ) {
        tonbankcard_api_search_put_entry( $entries, tonbankcard_api_search_prepare_entry( $entry ) );
    }

    $coins = tonbankcard_api_search_fetch_provider_json( 'coins/list', [ 'include_platform' => 'true' ], $provider, $config, $warnings );
    foreach ( $coins as $coin ) {
        if ( ! is_array( $coin ) ) {
            continue;
        }
        tonbankcard_api_search_put_entry( $entries, tonbankcard_api_search_coin_entry( $coin ) );
    }

    $exchanges = tonbankcard_api_search_fetch_provider_json( 'exchanges/list', [], $provider, $config, $warnings );
    foreach ( $exchanges as $exchange ) {
        if ( ! is_array( $exchange ) ) {
            continue;
        }
        tonbankcard_api_search_put_entry( $entries, tonbankcard_api_search_exchange_entry( $exchange ) );
    }

    $categories = tonbankcard_api_search_fetch_provider_json( 'coins/categories/list', [], $provider, $config, $warnings );
    foreach ( $categories as $category ) {
        if ( ! is_array( $category ) ) {
            continue;
        }
        tonbankcard_api_search_put_entry( $entries, tonbankcard_api_search_category_entry( $category ) );
    }

    $trending = tonbankcard_api_search_fetch_provider_json( 'search/trending', [], $provider, $config, $warnings );
    if ( isset( $trending['coins'] ) && is_array( $trending['coins'] ) ) {
        foreach ( $trending['coins'] as $rank => $trend ) {
            $item = isset( $trend['item'] ) && is_array( $trend['item'] ) ? $trend['item'] : $trend;
            if ( is_array( $item ) ) {
                tonbankcard_api_search_put_entry( $entries, tonbankcard_api_search_trending_coin_entry( $item, $rank ) );
            }
        }
    }
    if ( isset( $trending['exchanges'] ) && is_array( $trending['exchanges'] ) ) {
        foreach ( $trending['exchanges'] as $rank => $exchange ) {
            if ( is_array( $exchange ) ) {
                tonbankcard_api_search_put_entry( $entries, tonbankcard_api_search_trending_exchange_entry( $exchange, $rank ) );
            }
        }
    }

    return [
        'version'       => 1,
        'source'        => 'tonbankcard-search',
        'built_at'      => gmdate( 'c' ),
        'entry_count'   => count( $entries ),
        'warnings'      => $warnings,
        'entries'       => array_values( $entries ),
    ];
}

/**
 * Fetches a provider JSON endpoint for index building.
 *
 * @param string $path
 * @param array $query
 * @param array $provider
 * @param array $config
 * @param array $warnings
 * @return array
 */
function tonbankcard_api_search_fetch_provider_json( string $path, array $query, array $provider, array $config, array &$warnings ) {
    if ( empty( $provider ) || ! function_exists( 'tonbankcard_api_market_fetch' ) ) {
        $warnings[] = 'market_gateway_unavailable:' . $path;
        return [];
    }

    $response = tonbankcard_api_market_fetch( $path, $query, $provider, $config );
    if ( ! empty( $response['error'] ) ) {
        $warnings[] = 'provider_error:' . $path;
        return [];
    }

    $status = isset( $response['status'] ) ? (int) $response['status'] : 0;
    if ( 200 > $status || 300 <= $status ) {
        $warnings[] = 'provider_http_' . $status . ':' . $path;
        return [];
    }

    $data = json_decode( isset( $response['body'] ) ? (string) $response['body'] : '', TRUE );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
        $warnings[] = 'provider_invalid_json:' . $path;
        return [];
    }

    return $data;
}

/**
 * Adds or merges an entry into the index map.
 *
 * @param array $entries
 * @param array $entry
 * @return void
 */
function tonbankcard_api_search_put_entry( array &$entries, array $entry ) {
    if ( empty( $entry['type'] ) || empty( $entry['id'] ) || empty( $entry['title'] ) ) {
        return;
    }

    $key = $entry['type'] . ':' . $entry['id'];
    if ( ! isset( $entries[ $key ] ) ) {
        $entries[ $key ] = tonbankcard_api_search_prepare_entry( $entry );
        return;
    }

    $existing = $entries[ $key ];
    foreach ( [ 'aliases', 'tags', 'contract_addresses' ] as $field ) {
        $existing[ $field ] = tonbankcard_api_search_unique_values(
            array_merge(
                isset( $existing[ $field ] ) && is_array( $existing[ $field ] ) ? $existing[ $field ] : [],
                isset( $entry[ $field ] ) && is_array( $entry[ $field ] ) ? $entry[ $field ] : []
            )
        );
    }

    foreach ( [ 'image', 'market_cap_rank', 'coin_id', 'exchange_id', 'category_id', 'subtitle', 'symbol' ] as $field ) {
        if ( empty( $existing[ $field ] ) && ! empty( $entry[ $field ] ) ) {
            $existing[ $field ] = $entry[ $field ];
        }
    }

    $existing['popular_score'] = max(
        isset( $existing['popular_score'] ) ? (int) $existing['popular_score'] : 0,
        isset( $entry['popular_score'] ) ? (int) $entry['popular_score'] : 0
    );
    $entries[ $key ] = tonbankcard_api_search_prepare_entry( $existing );
}

/**
 * Normalizes optional entry fields.
 *
 * @param array $entry
 * @return array
 */
function tonbankcard_api_search_prepare_entry( array $entry ) {
    $entry['id'] = isset( $entry['id'] ) ? (string) $entry['id'] : '';
    $entry['type'] = isset( $entry['type'] ) ? (string) $entry['type'] : '';
    $entry['title'] = isset( $entry['title'] ) ? (string) $entry['title'] : '';
    $entry['subtitle'] = isset( $entry['subtitle'] ) ? (string) $entry['subtitle'] : ( isset( $entry['symbol'] ) ? (string) $entry['symbol'] : '' );
    $entry['symbol'] = isset( $entry['symbol'] ) ? strtoupper( (string) $entry['symbol'] ) : '';
    $entry['aliases'] = tonbankcard_api_search_unique_values( isset( $entry['aliases'] ) && is_array( $entry['aliases'] ) ? $entry['aliases'] : [] );
    $entry['tags'] = tonbankcard_api_search_unique_values( isset( $entry['tags'] ) && is_array( $entry['tags'] ) ? $entry['tags'] : [] );
    $entry['contract_addresses'] = tonbankcard_api_search_unique_values( isset( $entry['contract_addresses'] ) && is_array( $entry['contract_addresses'] ) ? $entry['contract_addresses'] : [] );
    $entry['popular_score'] = isset( $entry['popular_score'] ) ? max( 0, (int) $entry['popular_score'] ) : 0;
    $entry['route'] = isset( $entry['route'] ) && is_array( $entry['route'] ) ? $entry['route'] : [ 'path' => '/' ];
    $entry['links'] = isset( $entry['links'] ) && is_array( $entry['links'] ) ? $entry['links'] : [ 'web' => '/', 'telegram' => '/app' ];

    return $entry;
}

/**
 * Builds a coin entry from CoinGecko list data.
 *
 * @param array $coin
 * @return array
 */
function tonbankcard_api_search_coin_entry( array $coin ) {
    $id = isset( $coin['id'] ) ? (string) $coin['id'] : '';
    $symbol = isset( $coin['symbol'] ) ? strtoupper( (string) $coin['symbol'] ) : '';
    $name = isset( $coin['name'] ) ? (string) $coin['name'] : $id;
    $platforms = isset( $coin['platforms'] ) && is_array( $coin['platforms'] ) ? $coin['platforms'] : [];
    $contracts = [];
    $tags = [];
    $aliases = [];

    foreach ( $platforms as $platform => $address ) {
        if ( '' === trim( (string) $address ) ) {
            continue;
        }
        $contracts[] = (string) $address;
        $aliases[] = (string) $platform;
        if ( in_array( strtolower( (string) $platform ), [ 'the-open-network', 'ton', 'toncoin' ], TRUE ) || FALSE !== strpos( strtolower( (string) $platform ), 'ton' ) ) {
            $tags[] = 'ton_ecosystem';
            $tags[] = 'ton_asset';
            $aliases[] = $symbol . ' TON';
            $aliases[] = $name . ' TON';
        }
    }

    return [
        'type'               => 'coin',
        'id'                 => $id,
        'coin_id'            => $id,
        'title'              => $name,
        'symbol'             => $symbol,
        'subtitle'           => $symbol,
        'tags'               => $tags,
        'aliases'            => $aliases,
        'contract_addresses' => $contracts,
        'route'              => [
            'name'   => 'currency',
            'params' => [ 'id' => $id ],
            'path'   => '/currency/' . rawurlencode( $id ),
        ],
        'links'              => [
            'web'      => '/currency/' . rawurlencode( $id ),
            'telegram' => '/app/coin/' . rawurlencode( $id ),
        ],
    ];
}

/**
 * Builds an exchange entry from CoinGecko list data.
 *
 * @param array $exchange
 * @return array
 */
function tonbankcard_api_search_exchange_entry( array $exchange ) {
    $id = isset( $exchange['id'] ) ? (string) $exchange['id'] : tonbankcard_api_search_slug( isset( $exchange['name'] ) ? $exchange['name'] : '' );
    $name = isset( $exchange['name'] ) ? (string) $exchange['name'] : $id;

    return [
        'type'        => 'exchange',
        'id'          => $id,
        'exchange_id' => $id,
        'title'       => $name,
        'subtitle'    => 'Exchange',
        'tags'        => [ 'exchange' ],
        'aliases'     => [ $name . ' exchange' ],
        'route'       => [
            'name'   => 'exchange',
            'params' => [ 'id' => $id ],
            'path'   => '/exchange/' . rawurlencode( $id ),
        ],
        'links'       => [
            'web'      => '/exchange/' . rawurlencode( $id ),
            'telegram' => '/app/search?type=exchange&id=' . rawurlencode( $id ),
        ],
    ];
}

/**
 * Builds a category entry.
 *
 * @param array $category
 * @return array
 */
function tonbankcard_api_search_category_entry( array $category ) {
    $id = isset( $category['category_id'] ) ? (string) $category['category_id'] : ( isset( $category['id'] ) ? (string) $category['id'] : tonbankcard_api_search_slug( isset( $category['name'] ) ? $category['name'] : '' ) );
    $name = isset( $category['name'] ) ? (string) $category['name'] : $id;

    return [
        'type'        => 'category',
        'id'          => $id,
        'category_id' => $id,
        'title'       => $name,
        'subtitle'    => 'Category',
        'tags'        => [ 'category' ],
        'aliases'     => [ $name . ' category' ],
        'route'       => [
            'name'  => 'currencies',
            'query' => [ 'category' => $id ],
            'path'  => '/?category=' . rawurlencode( $id ),
        ],
        'links'       => [
            'web'      => '/?category=' . rawurlencode( $id ),
            'telegram' => '/app/search?category=' . rawurlencode( $id ),
        ],
    ];
}

/**
 * Builds or boosts a trending coin entry.
 *
 * @param array $coin
 * @param int $rank
 * @return array
 */
function tonbankcard_api_search_trending_coin_entry( array $coin, int $rank ) {
    $entry = tonbankcard_api_search_coin_entry( $coin );
    $entry['tags'][] = 'trending';
    $entry['aliases'][] = 'trending';
    $entry['popular_score'] = max( 1, 900 - ( $rank * 20 ) );
    if ( ! empty( $coin['small'] ) ) {
        $entry['image'] = (string) $coin['small'];
    } elseif ( ! empty( $coin['thumb'] ) ) {
        $entry['image'] = (string) $coin['thumb'];
    }
    if ( ! empty( $coin['market_cap_rank'] ) ) {
        $entry['market_cap_rank'] = (int) $coin['market_cap_rank'];
    }

    return $entry;
}

/**
 * Builds or boosts a trending exchange entry.
 *
 * @param array $exchange
 * @param int $rank
 * @return array
 */
function tonbankcard_api_search_trending_exchange_entry( array $exchange, int $rank ) {
    $entry = tonbankcard_api_search_exchange_entry( $exchange );
    $entry['tags'][] = 'trending';
    $entry['aliases'][] = 'trending exchange';
    $entry['popular_score'] = max( 1, 850 - ( $rank * 20 ) );
    if ( ! empty( $exchange['image'] ) ) {
        $entry['image'] = (string) $exchange['image'];
    }

    return $entry;
}

/**
 * Returns ranked search results.
 *
 * @param array $entries
 * @param string $query
 * @param int $limit
 * @param string $surface
 * @return array
 */
function tonbankcard_api_search_results( array $entries, string $query, int $limit, string $surface ) {
    $normalized_query = tonbankcard_api_search_normalize_text( $query );
    $tokens = tonbankcard_api_search_tokens( $query );
    $scored = [];

    foreach ( $entries as $entry ) {
        if ( ! is_array( $entry ) ) {
            continue;
        }
        $score = tonbankcard_api_search_score_entry( $entry, $normalized_query, $tokens );
        if ( $score <= 0 ) {
            continue;
        }
        $entry['_score'] = $score;
        $scored[] = $entry;
    }

    usort(
        $scored,
        function ( $left, $right ) {
            if ( $left['_score'] === $right['_score'] ) {
                $left_popular = isset( $left['popular_score'] ) ? (int) $left['popular_score'] : 0;
                $right_popular = isset( $right['popular_score'] ) ? (int) $right['popular_score'] : 0;
                if ( $left_popular === $right_popular ) {
                    return strcmp( $left['title'], $right['title'] );
                }
                return $right_popular - $left_popular;
            }
            return $right['_score'] - $left['_score'];
        }
    );

    $ranked_entries = tonbankcard_api_search_with_crypto_exchange_actions( $scored, $normalized_query );

    $results = [];
    foreach ( array_slice( $ranked_entries, 0, $limit ) as $rank => $entry ) {
        $results[] = tonbankcard_api_search_public_result( $entry, $rank + 1, $query, $surface );
    }

    return $results;
}

/**
 * Inserts a first-party exchange action after the highest ranked matched asset.
 *
 * @param array $entries
 * @param string $normalized_query
 * @return array
 */
function tonbankcard_api_search_with_crypto_exchange_actions( array $entries, string $normalized_query ) {
    if ( '' === $normalized_query ) {
        return $entries;
    }

    $ranked = [];
    $inserted = FALSE;

    foreach ( $entries as $entry ) {
        $ranked[] = $entry;

        if ( $inserted || ! tonbankcard_api_search_crypto_exchange_source_entry( $entry ) ) {
            continue;
        }

        $action = tonbankcard_api_search_crypto_exchange_action_entry( $entry );
        if ( empty( $action ) ) {
            continue;
        }

        $ranked[] = $action;
        $inserted = TRUE;
    }

    return $ranked;
}

/**
 * Returns TRUE when a search entry can seed the first-party exchange action.
 *
 * @param array $entry
 * @return bool
 */
function tonbankcard_api_search_crypto_exchange_source_entry( array $entry ) {
    $type = isset( $entry['type'] ) ? (string) $entry['type'] : '';

    return in_array( $type, [ 'coin', 'ton_asset' ], TRUE )
        && ! empty( $entry['id'] )
        && ! empty( $entry['title'] );
}

/**
 * Builds the first-party crypto exchange action for a matched asset.
 *
 * @param array $entry
 * @return array
 */
function tonbankcard_api_search_crypto_exchange_action_entry( array $entry ) {
    $pair = tonbankcard_api_search_crypto_exchange_pair( $entry );
    if ( empty( $pair['from'] ) || empty( $pair['to'] ) ) {
        return [];
    }

    $asset_id = (string) $entry['id'];
    $title = 'Exchange ' . (string) $entry['title'];
    $subtitle = tonbankcard_api_search_crypto_exchange_asset_label( $pair['from'] ) . ' to ' . tonbankcard_api_search_crypto_exchange_asset_label( $pair['to'] );
    $web_path = '/crypto-exchange?from=' . rawurlencode( $pair['from'] ) . '&to=' . rawurlencode( $pair['to'] ) . '&asset=' . rawurlencode( $asset_id );
    $telegram_path = '/app/exchange?from=' . rawurlencode( $pair['from'] ) . '&to=' . rawurlencode( $pair['to'] ) . '&asset=' . rawurlencode( $asset_id );

    return [
        'type'          => 'action',
        'id'            => 'exchange-' . $asset_id,
        'coin_id'       => isset( $entry['coin_id'] ) ? (string) $entry['coin_id'] : $asset_id,
        'title'         => $title,
        'subtitle'      => $subtitle,
        'tags'          => [ 'exchange', 'swap', 'changenow', 'tonbankcard' ],
        'aliases'       => [ 'swap ' . (string) $entry['title'], 'buy ' . (string) $entry['title'], 'exchange ' . (string) $entry['title'] ],
        'popular_score' => isset( $entry['popular_score'] ) ? max( 0, (int) $entry['popular_score'] - 1 ) : 0,
        'route'         => [
            'name'  => 'crypto-exchange',
            'query' => [
                'from'  => $pair['from'],
                'to'    => $pair['to'],
                'asset' => $asset_id,
            ],
            'path'  => $web_path,
        ],
        'links'         => [
            'web'      => $web_path,
            'telegram' => $telegram_path,
        ],
    ];
}

/**
 * Returns the ChangeNOW pair for a matched search entry.
 *
 * @param array $entry
 * @return array
 */
function tonbankcard_api_search_crypto_exchange_pair( array $entry ) {
    $asset = tonbankcard_api_search_crypto_exchange_symbol( $entry );
    if ( '' === $asset ) {
        return [];
    }

    if ( 'usdtton' === $asset ) {
        return [
            'from' => 'ton',
            'to'   => 'usdtton',
        ];
    }

    return [
        'from' => $asset,
        'to'   => 'usdtton',
    ];
}

/**
 * Derives a ChangeNOW currency code from a search entry.
 *
 * @param array $entry
 * @return string
 */
function tonbankcard_api_search_crypto_exchange_symbol( array $entry ) {
    $id = isset( $entry['id'] ) ? strtolower( (string) $entry['id'] ) : '';
    $symbol = tonbankcard_api_search_crypto_exchange_token( isset( $entry['symbol'] ) ? (string) $entry['symbol'] : '' );
    $tags = isset( $entry['tags'] ) && is_array( $entry['tags'] ) ? array_map( 'strtolower', $entry['tags'] ) : [];
    $aliases = isset( $entry['aliases'] ) && is_array( $entry['aliases'] ) ? array_map( 'strtolower', $entry['aliases'] ) : [];

    if (
        'tether-usd-ton' === $id
        || in_array( 'usdtton', $aliases, TRUE )
        || ( in_array( 'ton_asset', $tags, TRUE ) && in_array( $symbol, [ 'usdt', 'usd' ], TRUE ) )
    ) {
        return 'usdtton';
    }

    if ( 'toncoin' === $id || 'ton' === $symbol ) {
        return 'ton';
    }

    if ( '' !== $symbol ) {
        return $symbol;
    }

    return tonbankcard_api_search_crypto_exchange_token( $id );
}

/**
 * Sanitizes a ChangeNOW currency code.
 *
 * @param string $value
 * @return string
 */
function tonbankcard_api_search_crypto_exchange_token( string $value ) {
    return preg_replace( '/[^a-z0-9]/', '', strtolower( $value ) );
}

/**
 * Returns a compact asset label for the exchange pair.
 *
 * @param string $asset
 * @return string
 */
function tonbankcard_api_search_crypto_exchange_asset_label( string $asset ) {
    $labels = [
        'ton'     => 'TON',
        'usdtton' => 'USDT on TON',
        'btc'     => 'BTC',
        'eth'     => 'ETH',
        'usdt'    => 'USDT',
    ];

    return isset( $labels[ $asset ] ) ? $labels[ $asset ] : strtoupper( $asset );
}

/**
 * Scores an entry for the normalized query.
 *
 * @param array $entry
 * @param string $normalized_query
 * @param array $query_tokens
 * @return int
 */
function tonbankcard_api_search_score_entry( array $entry, string $normalized_query, array $query_tokens ) {
    $popular = isset( $entry['popular_score'] ) ? (int) $entry['popular_score'] : 0;
    if ( '' === $normalized_query ) {
        return $popular > 0 ? $popular : 0;
    }

    $score = 0;
    $fields = tonbankcard_api_search_entry_fields( $entry );
    $haystack = tonbankcard_api_search_normalize_text( implode( ' ', $fields ) );
    $entry_tokens = tonbankcard_api_search_tokens( implode( ' ', $fields ) );
    $symbol = tonbankcard_api_search_normalize_text( isset( $entry['symbol'] ) ? $entry['symbol'] : '' );
    $title = tonbankcard_api_search_normalize_text( isset( $entry['title'] ) ? $entry['title'] : '' );
    $id = tonbankcard_api_search_normalize_text( isset( $entry['id'] ) ? $entry['id'] : '' );

    if ( $normalized_query === $symbol && '' !== $symbol ) {
        $score += 1000;
    }
    if ( $normalized_query === $title || $normalized_query === $id ) {
        $score += 850;
    }
    if ( '' !== $symbol && 0 === strpos( $symbol, $normalized_query ) ) {
        $score += 650;
    }
    if ( 0 === strpos( $title, $normalized_query ) || 0 === strpos( $id, $normalized_query ) ) {
        $score += 520;
    }
    if ( FALSE !== strpos( $haystack, $normalized_query ) ) {
        $score += 300;
    }

    $contract_query = strtolower( preg_replace( '/\s+/', '', $normalized_query ) );
    foreach ( isset( $entry['contract_addresses'] ) && is_array( $entry['contract_addresses'] ) ? $entry['contract_addresses'] : [] as $address ) {
        $contract = strtolower( trim( (string) $address ) );
        if ( '' === $contract || '' === $contract_query ) {
            continue;
        }
        if ( $contract_query === $contract ) {
            $score += 1300;
        } elseif ( 0 === strpos( $contract, $contract_query ) ) {
            $score += 900;
        } elseif ( FALSE !== strpos( $contract, $contract_query ) ) {
            $score += 550;
        }
    }

    $matched_tokens = 0;
    foreach ( $query_tokens as $token ) {
        $token_score = tonbankcard_api_search_token_score( $token, $entry_tokens );
        if ( $token_score > 0 ) {
            $matched_tokens++;
            $score += $token_score;
        }
    }
    if ( ! empty( $query_tokens ) && count( $query_tokens ) === $matched_tokens ) {
        $score += 180;
    }

    if ( in_array( 'ton', $query_tokens, TRUE ) && ! empty( $entry['tags'] ) && in_array( 'ton_ecosystem', $entry['tags'], TRUE ) ) {
        $score += 220;
    }
    if ( 'ton_asset' === ( isset( $entry['type'] ) ? $entry['type'] : '' ) ) {
        $score += 60;
    }

    return $score + min( 120, (int) floor( $popular / 10 ) );
}

/**
 * Returns searchable fields for an entry.
 *
 * @param array $entry
 * @return array
 */
function tonbankcard_api_search_entry_fields( array $entry ) {
    $fields = [
        isset( $entry['id'] ) ? $entry['id'] : '',
        isset( $entry['title'] ) ? $entry['title'] : '',
        isset( $entry['subtitle'] ) ? $entry['subtitle'] : '',
        isset( $entry['symbol'] ) ? $entry['symbol'] : '',
    ];

    foreach ( [ 'aliases', 'tags', 'contract_addresses' ] as $field ) {
        foreach ( isset( $entry[ $field ] ) && is_array( $entry[ $field ] ) ? $entry[ $field ] : [] as $value ) {
            $fields[] = (string) $value;
        }
    }

    return $fields;
}

/**
 * Scores a single query token against entry tokens.
 *
 * @param string $query_token
 * @param array $entry_tokens
 * @return int
 */
function tonbankcard_api_search_token_score( string $query_token, array $entry_tokens ) {
    foreach ( $entry_tokens as $entry_token ) {
        if ( $query_token === $entry_token ) {
            return 120;
        }
    }

    foreach ( $entry_tokens as $entry_token ) {
        if ( 0 === strpos( $entry_token, $query_token ) ) {
            return 85;
        }
    }

    foreach ( $entry_tokens as $entry_token ) {
        if ( FALSE !== strpos( $entry_token, $query_token ) ) {
            return 45;
        }
    }

    foreach ( $entry_tokens as $entry_token ) {
        if ( strlen( $query_token ) < 4 || strlen( $entry_token ) > 48 ) {
            continue;
        }
        $distance = levenshtein( $query_token, $entry_token );
        if ( $distance <= ( strlen( $query_token ) <= 5 ? 1 : 2 ) ) {
            return 65 - ( $distance * 10 );
        }
    }

    return 0;
}

/**
 * Builds a public search result payload.
 *
 * @param array $entry
 * @param int $rank
 * @param string $query
 * @param string $surface
 * @return array
 */
function tonbankcard_api_search_public_result( array $entry, int $rank, string $query, string $surface ) {
    $result = [
        'searchId'           => $entry['type'] . ':' . $entry['id'],
        'type'               => $entry['type'],
        'id'                 => $entry['id'],
        'title'              => $entry['title'],
        'name'               => $entry['title'],
        'subtitle'           => isset( $entry['subtitle'] ) ? $entry['subtitle'] : '',
        'symbol'             => isset( $entry['symbol'] ) ? $entry['symbol'] : '',
        'rank'               => $rank,
        'score'              => isset( $entry['_score'] ) ? (int) $entry['_score'] : 0,
        'tags'               => isset( $entry['tags'] ) && is_array( $entry['tags'] ) ? array_values( $entry['tags'] ) : [],
        'contract_addresses' => isset( $entry['contract_addresses'] ) && is_array( $entry['contract_addresses'] ) ? array_values( $entry['contract_addresses'] ) : [],
        'route'              => isset( $entry['route'] ) && is_array( $entry['route'] ) ? $entry['route'] : [ 'path' => '/' ],
        'links'              => isset( $entry['links'] ) && is_array( $entry['links'] ) ? $entry['links'] : [ 'web' => '/', 'telegram' => '/app' ],
    ];

    foreach ( [ 'coin_id', 'exchange_id', 'category_id', 'image', 'market_cap_rank' ] as $field ) {
        if ( isset( $entry[ $field ] ) && '' !== $entry[ $field ] ) {
            $result[ $field ] = $entry[ $field ];
        }
    }
    if ( ! empty( $result['image'] ) ) {
        $result['large'] = $result['image'];
    }

    $result['analytics'] = [
        'event_name'          => 'search_result_selected',
        'result_type'         => $result['type'],
        'coin_id'             => isset( $result['coin_id'] ) ? $result['coin_id'] : ( 'coin' === $result['type'] ? $result['id'] : null ),
        'exchange_id'         => isset( $result['exchange_id'] ) ? $result['exchange_id'] : ( 'exchange' === $result['type'] ? $result['id'] : null ),
        'rank'                => $rank,
        'query_length_bucket' => tonbankcard_api_search_query_length_bucket( $query ),
        'surface'             => $surface,
    ];

    return $result;
}

/**
 * Returns search response metadata.
 *
 * @param array $state
 * @param string $query
 * @param int $result_count
 * @return array
 */
function tonbankcard_api_search_meta( array $state, string $query, int $result_count ) {
    $index = isset( $state['index'] ) && is_array( $state['index'] ) ? $state['index'] : [];

    return [
        'search' => [
            'cache_status'        => isset( $state['cache_status'] ) ? $state['cache_status'] : 'pass',
            'index_built_at'      => isset( $index['built_at'] ) ? $index['built_at'] : null,
            'index_entry_count'   => isset( $index['entry_count'] ) ? (int) $index['entry_count'] : ( isset( $index['entries'] ) && is_array( $index['entries'] ) ? count( $index['entries'] ) : 0 ),
            'query_length_bucket' => tonbankcard_api_search_query_length_bucket( $query ),
            'result_count'        => $result_count,
            'warnings'            => isset( $state['warnings'] ) && is_array( $state['warnings'] ) ? $state['warnings'] : [],
        ],
    ];
}

/**
 * Returns a compact index refresh payload.
 *
 * @param array $state
 * @return array
 */
function tonbankcard_api_search_index_payload( array $state ) {
    $index = isset( $state['index'] ) && is_array( $state['index'] ) ? $state['index'] : [];

    return [
        'version'       => isset( $index['version'] ) ? $index['version'] : 1,
        'source'        => isset( $index['source'] ) ? $index['source'] : 'tonbankcard-search',
        'built_at'      => isset( $index['built_at'] ) ? $index['built_at'] : null,
        'entry_count'   => isset( $index['entry_count'] ) ? (int) $index['entry_count'] : 0,
        'cache_status'  => isset( $state['cache_status'] ) ? $state['cache_status'] : 'pass',
        'warnings'      => isset( $state['warnings'] ) && is_array( $state['warnings'] ) ? $state['warnings'] : [],
    ];
}

/**
 * Reads the search index from Upstash Redis REST when configured.
 *
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @return array
 */
function tonbankcard_api_search_cache_get( array $runtime, array $config, array $settings ) {
    if ( ! tonbankcard_api_search_redis_configured( $runtime ) ) {
        return [ 'ok' => FALSE, 'configured' => FALSE ];
    }

    $response = tonbankcard_api_search_redis_command( [ 'GET', $settings['index_cache_key'] ], $runtime, $config, $settings );
    if ( empty( $response['ok'] ) || null === $response['result'] || '' === $response['result'] ) {
        return [ 'ok' => FALSE, 'configured' => TRUE ];
    }

    $index = json_decode( (string) $response['result'], TRUE );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $index ) ) {
        return [ 'ok' => FALSE, 'configured' => TRUE, 'warning' => 'redis_invalid_index' ];
    }

    return [ 'ok' => TRUE, 'configured' => TRUE, 'index' => $index ];
}

/**
 * Writes the search index to Upstash Redis REST when configured.
 *
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @param array $index
 * @return array
 */
function tonbankcard_api_search_cache_set( array $runtime, array $config, array $settings, array $index ) {
    if ( ! tonbankcard_api_search_redis_configured( $runtime ) ) {
        return [ 'ok' => FALSE, 'configured' => FALSE ];
    }

    $encoded = json_encode( $index, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE );
    if ( FALSE === $encoded ) {
        return [ 'ok' => FALSE, 'configured' => TRUE, 'warning' => 'search_index_encode_failed' ];
    }

    $ttl = isset( $settings['index_ttl_seconds'] ) ? max( 60, (int) $settings['index_ttl_seconds'] ) : 900;
    $response = tonbankcard_api_search_redis_command( [ 'SET', $settings['index_cache_key'], $encoded, 'EX', $ttl ], $runtime, $config, $settings );
    if ( empty( $response['ok'] ) ) {
        return [ 'ok' => FALSE, 'configured' => TRUE, 'warning' => 'redis_write_failed' ];
    }

    return [ 'ok' => TRUE, 'configured' => TRUE ];
}

/**
 * Returns TRUE when Upstash Redis REST is configured.
 *
 * @param array $runtime
 * @return bool
 */
function tonbankcard_api_search_redis_configured( array $runtime ) {
    $upstash = isset( $runtime['providers']['upstash'] ) && is_array( $runtime['providers']['upstash'] ) ? $runtime['providers']['upstash'] : [];
    return ! empty( $upstash['rest_url'] ) && ! empty( $upstash['rest_token'] );
}

/**
 * Sends a Redis command through Upstash REST.
 *
 * @param array $command
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @return array
 */
function tonbankcard_api_search_redis_command( array $command, array $runtime, array $config, array $settings ) {
    $upstash = isset( $runtime['providers']['upstash'] ) && is_array( $runtime['providers']['upstash'] ) ? $runtime['providers']['upstash'] : [];
    $body = json_encode( array_values( $command ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE );
    if ( FALSE === $body ) {
        return [ 'ok' => FALSE, 'result' => null ];
    }

    $request = [
        'method'          => 'POST',
        'url'             => rtrim( (string) $upstash['rest_url'], '/' ),
        'headers'         => [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $upstash['rest_token'],
        ],
        'body'            => $body,
        'timeout_seconds' => isset( $settings['redis_timeout_seconds'] ) ? max( 1, (int) $settings['redis_timeout_seconds'] ) : 2,
    ];

    if ( isset( $config['search']['redis_transport'] ) && is_callable( $config['search']['redis_transport'] ) ) {
        try {
            $response = call_user_func( $config['search']['redis_transport'], $request );
        } catch ( Throwable $exception ) {
            return [ 'ok' => FALSE, 'result' => null ];
        }
    } elseif ( function_exists( 'curl_init' ) ) {
        $response = tonbankcard_api_search_redis_command_with_curl( $request );
    } else {
        $response = tonbankcard_api_search_redis_command_with_stream( $request );
    }

    $status = isset( $response['status'] ) ? (int) $response['status'] : 0;
    if ( 200 > $status || 300 <= $status ) {
        return [ 'ok' => FALSE, 'result' => null ];
    }

    $payload = json_decode( isset( $response['body'] ) ? (string) $response['body'] : '', TRUE );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
        return [ 'ok' => FALSE, 'result' => null ];
    }

    return [
        'ok'     => ! array_key_exists( 'error', $payload ),
        'result' => array_key_exists( 'result', $payload ) ? $payload['result'] : null,
    ];
}

/**
 * Sends a Redis REST request with cURL.
 *
 * @param array $request
 * @return array
 */
function tonbankcard_api_search_redis_command_with_curl( array $request ) {
    $handle = curl_init( $request['url'] );
    if ( FALSE === $handle ) {
        return [ 'status' => 0, 'body' => '' ];
    }

    $headers = [];
    foreach ( $request['headers'] as $name => $value ) {
        $headers[] = $name . ': ' . $value;
    }

    curl_setopt_array(
        $handle,
        [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => (int) $request['timeout_seconds'],
            CURLOPT_CONNECTTIMEOUT => min( 2, (int) $request['timeout_seconds'] ),
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => $request['body'],
            CURLOPT_HTTPHEADER     => $headers,
        ]
    );
    $body = curl_exec( $handle );
    $status = (int) curl_getinfo( $handle, CURLINFO_HTTP_CODE );
    curl_close( $handle );

    return [
        'status' => $status,
        'body'   => FALSE === $body ? '' : $body,
    ];
}

/**
 * Sends a Redis REST request with streams.
 *
 * @param array $request
 * @return array
 */
function tonbankcard_api_search_redis_command_with_stream( array $request ) {
    $headers = [];
    foreach ( $request['headers'] as $name => $value ) {
        $headers[] = $name . ': ' . $value;
    }

    $context = stream_context_create(
        [
            'http' => [
                'method'        => 'POST',
                'header'        => implode( "\r\n", $headers ),
                'content'       => $request['body'],
                'timeout'       => (int) $request['timeout_seconds'],
                'ignore_errors' => TRUE,
            ],
        ]
    );
    $body = @file_get_contents( $request['url'], FALSE, $context );
    $headers = function_exists( 'tonbankcard_api_market_parse_header_lines' )
        ? tonbankcard_api_market_parse_header_lines( isset( $http_response_header ) ? $http_response_header : [] )
        : [];

    return [
        'status' => isset( $headers[':status'] ) ? (int) $headers[':status'] : ( FALSE === $body ? 0 : 200 ),
        'body'   => FALSE === $body ? '' : $body,
    ];
}

/**
 * Returns TRUE when the cached index has the expected shape.
 *
 * @param mixed $index
 * @return bool
 */
function tonbankcard_api_search_valid_index( $index ) {
    return is_array( $index )
        && isset( $index['version'] )
        && isset( $index['entries'] )
        && is_array( $index['entries'] );
}

/**
 * Normalizes free text for matching.
 *
 * @param string $text
 * @return string
 */
function tonbankcard_api_search_normalize_text( string $text ) {
    $text = strtolower( $text );
    $text = preg_replace( '/[^a-z0-9_:-]+/', ' ', $text );
    return trim( preg_replace( '/\s+/', ' ', $text ) );
}

/**
 * Splits searchable text into unique tokens.
 *
 * @param string $text
 * @return array
 */
function tonbankcard_api_search_tokens( string $text ) {
    $normalized = tonbankcard_api_search_normalize_text( $text );
    if ( '' === $normalized ) {
        return [];
    }

    return tonbankcard_api_search_unique_values( preg_split( '/\s+/', $normalized ) );
}

/**
 * Returns a safe slug from text.
 *
 * @param string $text
 * @return string
 */
function tonbankcard_api_search_slug( string $text ) {
    $slug = preg_replace( '/[^a-z0-9]+/', '-', strtolower( $text ) );
    return trim( $slug, '-' );
}

/**
 * Deduplicates scalar values while preserving order.
 *
 * @param array $values
 * @return array
 */
function tonbankcard_api_search_unique_values( array $values ) {
    $unique = [];
    $seen = [];
    foreach ( $values as $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            continue;
        }
        $key = strtolower( $value );
        if ( isset( $seen[ $key ] ) ) {
            continue;
        }
        $seen[ $key ] = TRUE;
        $unique[] = $value;
    }

    return $unique;
}

/**
 * Buckets query length without exposing raw query text to analytics.
 *
 * @param string $query
 * @return string
 */
function tonbankcard_api_search_query_length_bucket( string $query ) {
    $length = strlen( str_replace( ' ', '', tonbankcard_api_search_normalize_text( $query ) ) );
    if ( 0 === $length ) {
        return 'empty';
    }
    if ( $length <= 2 ) {
        return '1-2';
    }
    if ( $length <= 5 ) {
        return '3-5';
    }
    if ( $length <= 10 ) {
        return '6-10';
    }
    return '11-plus';
}
