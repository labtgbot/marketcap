<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 ADVANCED SCREENER API
 * -------------------------------------------------------------------------
 * Backend-owned market opportunity filters and trusted-session screener
 * presets. Market data is loaded through the existing CoinGecko gateway so
 * browser clients do not filter against direct provider calls.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Returns TRUE when the path belongs to the screener API surface.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_screener_is_request( string $path ) {
    return '/api/screener' === $path || 0 === strpos( $path, '/api/screener/' );
}

/**
 * Returns documented screener routes.
 *
 * @return array
 */
function tonbankcard_api_screener_routes() {
    return [
        '/api/screener',
        '/api/screener/markets',
        '/api/screener/presets',
        '/api/screener/presets/{id}',
    ];
}

/**
 * Handles a screener API request.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_screener_handle( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $path = tonbankcard_api_normalize_path( $request['path'] );

    if ( '/api/screener' === $path ) {
        if ( 'GET' !== $request['method'] ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            [
                'service' => 'tonbankcard-advanced-screener',
                'routes'  => tonbankcard_api_screener_routes(),
            ],
            $request_id,
            $headers,
            200,
            [ 'route_group' => 'screener' ]
        );
    }

    if ( '/api/screener/markets' === $path ) {
        if ( 'GET' !== $request['method'] ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_screener_markets_response( $request, $runtime, $config, $request_id, $headers );
    }

    if ( '/api/screener/presets' === $path || 0 === strpos( $path, '/api/screener/presets/' ) ) {
        return tonbankcard_api_screener_presets_response( $request, $runtime, $config, $request_id, $headers );
    }

    return tonbankcard_api_error_response(
        404,
        'not_found',
        'No screener route matches the request.',
        [ 'path' => $path ],
        $request_id,
        $headers
    );
}

/**
 * Builds a filtered market screener response.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_screener_markets_response( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $filters = tonbankcard_api_screener_filters( isset( $request['query'] ) ? $request['query'] : [] );
    if ( '' !== $filters['advanced_range'] && function_exists( 'tonbankcard_api_premium_state_for_request' ) ) {
        $premium_settings = tonbankcard_api_premium_settings( $runtime, $config );
        $premium_state = tonbankcard_api_premium_state_for_request( $request, $runtime, $config, $premium_settings );
        if ( ! tonbankcard_api_premium_limit_allows( $premium_state, 'advanced_ranges', $filters['advanced_range'] ) ) {
            return tonbankcard_api_error_response(
                402,
                'premium_required',
                'This screener range requires a premium entitlement.',
                [
                    'feature'           => 'advanced_ranges',
                    'range'             => $filters['advanced_range'],
                    'allowed_ranges'    => isset( $premium_state['limits']['advanced_ranges'] ) ? $premium_state['limits']['advanced_ranges'] : [],
                    'plan_code'         => isset( $premium_state['plan_code'] ) ? $premium_state['plan_code'] : 'free',
                    'upgrade_plan_code' => isset( $premium_state['upgrade_plan_code'] ) ? $premium_state['upgrade_plan_code'] : 'premium_monthly',
                ],
                $request_id,
                $headers
            );
        }
    }

    $ton_context = tonbankcard_api_screener_ton_context( $runtime, $config, $filters );

    if ( ! empty( $filters['ton_tag'] ) && empty( $ton_context['active_coin_ids'] ) ) {
        return tonbankcard_api_screener_success(
            $filters,
            [],
            [],
            $ton_context,
            [ 'warnings' => [ 'No curated TON assets match the selected TON tag.' ] ],
            $request_id,
            $headers
        );
    }

    $market_response = tonbankcard_api_market_handle(
        [
            'method'  => 'GET',
            'path'    => '/api/market/coins/markets',
            'query'   => tonbankcard_api_screener_market_query( $filters, $ton_context ),
            'headers' => isset( $request['headers'] ) && is_array( $request['headers'] ) ? $request['headers'] : [],
            'body'    => '',
        ],
        $runtime,
        $config,
        $request_id,
        $headers
    );

    if ( 200 !== (int) $market_response['status'] ) {
        return $market_response;
    }

    $market_payload = json_decode( isset( $market_response['body'] ) ? (string) $market_response['body'] : '', TRUE );
    $source_items = isset( $market_payload['data'] ) && is_array( $market_payload['data'] ) ? $market_payload['data'] : [];
    $market_meta = isset( $market_payload['meta'] ) && is_array( $market_payload['meta'] ) ? $market_payload['meta'] : [];

    $items = [];
    foreach ( $source_items as $source_item ) {
        if ( ! is_array( $source_item ) ) {
            continue;
        }

        $item = tonbankcard_api_screener_enrich_item( $source_item, $filters, $ton_context );
        if ( ! tonbankcard_api_screener_item_matches_filters( $item, $filters ) ) {
            continue;
        }
        $items[] = $item;
    }

    $exchange_meta = [];
    if ( '' !== $filters['exchange'] ) {
        $exchange_filtered = tonbankcard_api_screener_filter_exchange( $items, $filters, $runtime, $config, $request_id, $headers );
        $items = $exchange_filtered['items'];
        $exchange_meta = $exchange_filtered['meta'];
    }

    $items = tonbankcard_api_screener_sort_items( $items, $filters['sort'], $filters['direction'] );

    return tonbankcard_api_screener_success(
        $filters,
        $source_items,
        $items,
        $ton_context,
        array_merge( [ 'market_meta' => $market_meta ], $exchange_meta ),
        $request_id,
        $headers
    );
}

/**
 * Returns normalized filters used by the market screener.
 *
 * @param array $query
 * @return array
 */
function tonbankcard_api_screener_filters( array $query ) {
    $sort = isset( $query['sort'] ) ? tonbankcard_api_screener_sort_key( $query['sort'] ) : 'market_cap_rank';
    $direction = isset( $query['direction'] ) ? strtolower( trim( (string) $query['direction'] ) ) : 'asc';
    if ( ! in_array( $direction, [ 'asc', 'desc' ], TRUE ) ) {
        $direction = 'asc';
    }

    $watchlist = isset( $query['watchlist'] ) ? strtolower( trim( (string) $query['watchlist'] ) ) : 'all';
    if ( ! in_array( $watchlist, [ 'all', 'watched', 'unwatched' ], TRUE ) ) {
        $watchlist = 'all';
    }

    $vs_currency = isset( $query['vs_currency'] ) ? tonbankcard_api_ton_slug( $query['vs_currency'] ) : 'usd';
    if ( '' === $vs_currency ) {
        $vs_currency = 'usd';
    }

    $filters = [
        'vs_currency'       => substr( $vs_currency, 0, 16 ),
        'page'              => tonbankcard_api_screener_int( isset( $query['page'] ) ? $query['page'] : 1, 1, 20, 1 ),
        'per_page'          => tonbankcard_api_screener_int( isset( $query['per_page'] ) ? $query['per_page'] : 100, 1, 250, 100 ),
        'order'             => tonbankcard_api_screener_market_order( isset( $query['order'] ) ? $query['order'] : '' ),
        'sort'              => $sort,
        'direction'         => $direction,
        'category'          => isset( $query['category'] ) ? tonbankcard_api_ton_slug( $query['category'] ) : '',
        'exchange'          => isset( $query['exchange'] ) ? tonbankcard_api_ton_slug( $query['exchange'] ) : '',
        'ton_tag'           => tonbankcard_api_screener_ton_tag( isset( $query['ton_tag'] ) ? $query['ton_tag'] : ( isset( $query['tag'] ) ? $query['tag'] : '' ) ),
        'watchlist'         => $watchlist,
        'watchlist_ids'     => tonbankcard_api_screener_coin_ids( isset( $query['watchlist_ids'] ) ? $query['watchlist_ids'] : [] ),
        'ids'               => tonbankcard_api_screener_coin_ids( isset( $query['ids'] ) ? $query['ids'] : [] ),
        'market_cap_min'    => tonbankcard_api_screener_number_or_null( isset( $query['market_cap_min'] ) ? $query['market_cap_min'] : null ),
        'market_cap_max'    => tonbankcard_api_screener_number_or_null( isset( $query['market_cap_max'] ) ? $query['market_cap_max'] : null ),
        'volume_min'        => tonbankcard_api_screener_number_or_null( isset( $query['volume_min'] ) ? $query['volume_min'] : null ),
        'volume_max'        => tonbankcard_api_screener_number_or_null( isset( $query['volume_max'] ) ? $query['volume_max'] : null ),
        'rank_min'          => tonbankcard_api_screener_number_or_null( isset( $query['rank_min'] ) ? $query['rank_min'] : null ),
        'rank_max'          => tonbankcard_api_screener_number_or_null( isset( $query['rank_max'] ) ? $query['rank_max'] : null ),
        'change_24h_min'    => tonbankcard_api_screener_number_or_null( isset( $query['change_24h_min'] ) ? $query['change_24h_min'] : null ),
        'change_24h_max'    => tonbankcard_api_screener_number_or_null( isset( $query['change_24h_max'] ) ? $query['change_24h_max'] : null ),
        'change_7d_min'     => tonbankcard_api_screener_number_or_null( isset( $query['change_7d_min'] ) ? $query['change_7d_min'] : null ),
        'change_7d_max'     => tonbankcard_api_screener_number_or_null( isset( $query['change_7d_max'] ) ? $query['change_7d_max'] : null ),
        'change_30d_min'    => tonbankcard_api_screener_number_or_null( isset( $query['change_30d_min'] ) ? $query['change_30d_min'] : null ),
        'change_30d_max'    => tonbankcard_api_screener_number_or_null( isset( $query['change_30d_max'] ) ? $query['change_30d_max'] : null ),
        'sentiment_min'     => tonbankcard_api_screener_number_or_null( isset( $query['sentiment_min'] ) ? $query['sentiment_min'] : null ),
        'sentiment_max'     => tonbankcard_api_screener_number_or_null( isset( $query['sentiment_max'] ) ? $query['sentiment_max'] : null ),
        'advanced_range'    => isset( $query['advanced_range'] ) ? tonbankcard_api_screener_advanced_range( $query['advanced_range'] ) : '',
    ];

    if ( 'ton' === $filters['ton_tag'] ) {
        $filters['ton_tag'] = 'ton_ecosystem';
    }

    return $filters;
}

/**
 * Builds the upstream market query for the cached market gateway.
 *
 * @param array $filters
 * @param array $ton_context
 * @return array
 */
function tonbankcard_api_screener_market_query( array $filters, array $ton_context ) {
    $query = [
        'vs_currency'             => $filters['vs_currency'],
        'per_page'                => $filters['per_page'],
        'page'                    => $filters['page'],
        'order'                   => $filters['order'],
        'price_change_percentage' => '24h,7d,30d',
        'sparkline'               => 'false',
    ];

    if ( '' !== $filters['category'] ) {
        $query['category'] = $filters['category'];
    }

    $ids = ! empty( $filters['ids'] ) ? $filters['ids'] : [];
    if ( ! empty( $ton_context['active_coin_ids'] ) ) {
        $ids = empty( $ids ) ? $ton_context['active_coin_ids'] : array_values( array_intersect( $ids, $ton_context['active_coin_ids'] ) );
    }

    if ( ! empty( $ids ) ) {
        $query['ids'] = implode( ',', array_slice( array_values( array_unique( $ids ) ), 0, 250 ) );
        $query['page'] = 1;
        $query['per_page'] = min( 250, max( 1, count( $ids ) ) );
    }

    return $query;
}

/**
 * Builds TON asset context for result enrichment and TON-tag filters.
 *
 * @param array $runtime
 * @param array $config
 * @param array $filters
 * @return array
 */
function tonbankcard_api_screener_ton_context( array $runtime, array $config, array $filters ) {
    $state = tonbankcard_api_ton_load_state( $runtime, $config );
    $assets = isset( $state['assets'] ) && is_array( $state['assets'] ) ? $state['assets'] : [];
    $active_assets = [];
    foreach ( $assets as $asset ) {
        if ( ! is_array( $asset ) ) {
            continue;
        }
        if ( '' === $filters['ton_tag'] || ! tonbankcard_api_screener_ton_asset_matches_tag( $asset, $filters['ton_tag'] ) ) {
            continue;
        }
        $active_assets[] = $asset;
    }

    return [
        'state'           => $state,
        'assets'          => $assets,
        'asset_map'       => tonbankcard_api_screener_ton_asset_map( $assets ),
        'active_assets'   => $active_assets,
        'active_coin_ids' => tonbankcard_api_ton_market_coin_ids( $active_assets ),
    ];
}

/**
 * Returns TRUE when a TON curation asset matches a flexible screener tag.
 *
 * @param array $asset
 * @param string $tag
 * @return bool
 */
function tonbankcard_api_screener_ton_asset_matches_tag( array $asset, string $tag ) {
    $tag = tonbankcard_api_screener_ton_tag( $tag );
    if ( '' === $tag ) {
        return TRUE;
    }

    $tags = isset( $asset['tags'] ) && is_array( $asset['tags'] ) ? $asset['tags'] : [];
    $lists = isset( $asset['list_ids'] ) && is_array( $asset['list_ids'] ) ? $asset['list_ids'] : [];
    return in_array( $tag, $tags, TRUE )
        || in_array( $tag, $lists, TRUE )
        || $tag === ( isset( $asset['category'] ) ? (string) $asset['category'] : '' )
        || $tag === ( isset( $asset['verification_state'] ) ? (string) $asset['verification_state'] : '' );
}

/**
 * Maps CoinGecko ids to their best curated TON asset.
 *
 * @param array $assets
 * @return array
 */
function tonbankcard_api_screener_ton_asset_map( array $assets ) {
    $map = [];
    foreach ( $assets as $asset ) {
        if ( ! is_array( $asset ) || empty( $asset['coin_id'] ) ) {
            continue;
        }

        $keys = [ (string) $asset['coin_id'] ];
        if ( ! empty( $asset['id'] ) ) {
            $keys[] = (string) $asset['id'];
        }
        if ( isset( $asset['aliases'] ) && is_array( $asset['aliases'] ) ) {
            foreach ( $asset['aliases'] as $alias ) {
                $alias = strtolower( trim( (string) $alias ) );
                if ( '' !== $alias && preg_match( '/^[a-z0-9._-]+$/', $alias ) ) {
                    $keys[] = $alias;
                }
            }
        }

        $priority = tonbankcard_api_screener_ton_asset_priority( $asset );
        foreach ( array_unique( $keys ) as $key ) {
            if ( ! isset( $map[ $key ] ) || $priority > tonbankcard_api_screener_ton_asset_priority( $map[ $key ] ) ) {
                $map[ $key ] = $asset;
            }
        }
    }

    return $map;
}

/**
 * Returns a sortable priority for TON assets.
 *
 * @param array $asset
 * @return int
 */
function tonbankcard_api_screener_ton_asset_priority( array $asset ) {
    $priority = isset( $asset['priority'] ) ? (int) $asset['priority'] : 0;
    if ( 'verified' === ( isset( $asset['verification_state'] ) ? $asset['verification_state'] : '' ) ) {
        $priority += 1000;
    }
    if ( ! empty( $asset['featured'] ) ) {
        $priority += 100;
    }
    return $priority;
}

/**
 * Enriches a market row with screener-only analytics.
 *
 * @param array $item
 * @param array $filters
 * @param array $ton_context
 * @return array
 */
function tonbankcard_api_screener_enrich_item( array $item, array $filters, array $ton_context ) {
    $coin_id = isset( $item['id'] ) ? (string) $item['id'] : '';
    $ton_asset = isset( $ton_context['asset_map'][ $coin_id ] ) ? $ton_context['asset_map'][ $coin_id ] : null;
    $watched = in_array( $coin_id, $filters['watchlist_ids'], TRUE );
    $sentiment = tonbankcard_api_screener_sentiment( $item, is_array( $ton_asset ) ? $ton_asset : null );

    $item['ton_asset'] = $ton_asset;
    $item['watchlist'] = [
        'watched' => $watched,
        'source'  => empty( $filters['watchlist_ids'] ) ? 'none' : 'client_snapshot',
    ];
    $item['sentiment'] = $sentiment;
    $item['sentiment_score'] = $sentiment['score'];
    $item['exchange_availability'] = [
        'requested' => $filters['exchange'],
        'matched'   => '' === $filters['exchange'] ? null : FALSE,
        'checked'   => FALSE,
    ];

    return $item;
}

/**
 * Returns a deterministic sentiment score from already-fetched market data.
 *
 * @param array $item
 * @param array|null $ton_asset
 * @return array
 */
function tonbankcard_api_screener_sentiment( array $item, $ton_asset = null ) {
    $movement = function_exists( 'tonbankcard_api_ai_sentiment_movement_score' )
        ? tonbankcard_api_ai_sentiment_movement_score( $item )
        : tonbankcard_api_screener_basic_movement_score( $item );
    $volume = tonbankcard_api_screener_volume_pressure_score( $item );
    $ton_bonus = 0.0;
    if ( is_array( $ton_asset ) ) {
        $state = isset( $ton_asset['verification_state'] ) ? (string) $ton_asset['verification_state'] : '';
        $ton_bonus = 'verified' === $state ? 0.08 : ( 'curated' === $state ? 0.04 : -0.03 );
    }

    $score = tonbankcard_api_screener_clamp( ( $movement * 0.72 ) + ( $volume * 0.20 ) + $ton_bonus, -1, 1 );
    $score_int = (int) round( $score * 100 );
    $confidence = 0.68;
    if ( null !== tonbankcard_api_screener_number_field( $item, 'market_cap' ) ) {
        $confidence += 0.08;
    }
    if ( null !== tonbankcard_api_screener_number_field( $item, 'total_volume' ) ) {
        $confidence += 0.08;
    }
    if ( is_array( $ton_asset ) ) {
        $confidence += 0.06;
    }

    return [
        'score'      => $score_int,
        'label'      => tonbankcard_api_screener_sentiment_label( $score_int ),
        'confidence' => round( tonbankcard_api_screener_clamp( $confidence, 0, 1 ), 2 ),
        'source'     => 'deterministic_market_movement',
    ];
}

/**
 * Returns a movement score when the AI sentiment helper is unavailable.
 *
 * @param array $item
 * @return float
 */
function tonbankcard_api_screener_basic_movement_score( array $item ) {
    $change_24h = tonbankcard_api_screener_number_field( $item, 'price_change_percentage_24h_in_currency' );
    $change_7d = tonbankcard_api_screener_number_field( $item, 'price_change_percentage_7d_in_currency' );
    $change_30d = tonbankcard_api_screener_number_field( $item, 'price_change_percentage_30d_in_currency' );
    $score = 0.0;
    $weight = 0.0;
    if ( null !== $change_24h ) {
        $score += tonbankcard_api_screener_clamp( $change_24h / 12, -1, 1 ) * 0.45;
        $weight += 0.45;
    }
    if ( null !== $change_7d ) {
        $score += tonbankcard_api_screener_clamp( $change_7d / 30, -1, 1 ) * 0.35;
        $weight += 0.35;
    }
    if ( null !== $change_30d ) {
        $score += tonbankcard_api_screener_clamp( $change_30d / 80, -1, 1 ) * 0.20;
        $weight += 0.20;
    }

    return $weight > 0 ? $score / $weight : 0.0;
}

/**
 * Returns a signed volume pressure score.
 *
 * @param array $item
 * @return float
 */
function tonbankcard_api_screener_volume_pressure_score( array $item ) {
    $market_cap = tonbankcard_api_screener_number_field( $item, 'market_cap' );
    $volume = tonbankcard_api_screener_number_field( $item, 'total_volume' );
    if ( null === $market_cap || $market_cap <= 0 || null === $volume ) {
        return 0.0;
    }

    $change = tonbankcard_api_screener_number_field( $item, 'price_change_percentage_24h_in_currency' );
    $direction = null !== $change && $change < 0 ? -1 : 1;
    return tonbankcard_api_screener_clamp( ( $volume / $market_cap ) / 0.20, 0, 1 ) * $direction;
}

/**
 * Applies non-provider filters to an enriched item.
 *
 * @param array $item
 * @param array $filters
 * @return bool
 */
function tonbankcard_api_screener_item_matches_filters( array $item, array $filters ) {
    $checks = [
        [ 'market_cap', 'market_cap_min', 'market_cap_max' ],
        [ 'total_volume', 'volume_min', 'volume_max' ],
        [ 'market_cap_rank', 'rank_min', 'rank_max' ],
        [ 'price_change_percentage_24h_in_currency', 'change_24h_min', 'change_24h_max' ],
        [ 'price_change_percentage_7d_in_currency', 'change_7d_min', 'change_7d_max' ],
        [ 'price_change_percentage_30d_in_currency', 'change_30d_min', 'change_30d_max' ],
        [ 'sentiment_score', 'sentiment_min', 'sentiment_max' ],
    ];

    foreach ( $checks as $check ) {
        $value = tonbankcard_api_screener_number_field( $item, $check[0] );
        if ( null !== $filters[ $check[1] ] && ( null === $value || $value < $filters[ $check[1] ] ) ) {
            return FALSE;
        }
        if ( null !== $filters[ $check[2] ] && ( null === $value || $value > $filters[ $check[2] ] ) ) {
            return FALSE;
        }
    }

    if ( 'watched' === $filters['watchlist'] && empty( $item['watchlist']['watched'] ) ) {
        return FALSE;
    }
    if ( 'unwatched' === $filters['watchlist'] && ! empty( $item['watchlist']['watched'] ) ) {
        return FALSE;
    }

    return TRUE;
}

/**
 * Filters items by exchange availability using cached coin tickers.
 *
 * @param array $items
 * @param array $filters
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_screener_filter_exchange( array $items, array $filters, array $runtime, array $config, string $request_id, array $headers ) {
    $limit = 40;
    $checked = 0;
    $warnings = [];
    $filtered = [];

    foreach ( $items as $item ) {
        if ( $checked >= $limit ) {
            $warnings[] = 'Exchange availability checks are capped to keep provider usage bounded.';
            break;
        }

        $coin_id = isset( $item['id'] ) ? (string) $item['id'] : '';
        if ( ! preg_match( '/^[A-Za-z0-9._-]{1,96}$/', $coin_id ) ) {
            continue;
        }

        $checked++;
        $matched = tonbankcard_api_screener_exchange_matches( $coin_id, $filters['exchange'], $runtime, $config, $request_id, $headers );
        $item['exchange_availability'] = [
            'requested' => $filters['exchange'],
            'matched'   => $matched,
            'checked'   => TRUE,
        ];
        if ( $matched ) {
            $filtered[] = $item;
        }
    }

    return [
        'items' => $filtered,
        'meta'  => [
            'exchange_filter' => [
                'exchange' => $filters['exchange'],
                'checked'  => $checked,
                'matched'  => count( $filtered ),
            ],
            'warnings' => $warnings,
        ],
    ];
}

/**
 * Returns TRUE when CoinGecko tickers show the requested exchange.
 *
 * @param string $coin_id
 * @param string $exchange
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return bool
 */
function tonbankcard_api_screener_exchange_matches( string $coin_id, string $exchange, array $runtime, array $config, string $request_id, array $headers ) {
    $response = tonbankcard_api_market_handle(
        [
            'method'  => 'GET',
            'path'    => '/api/market/coins/' . $coin_id . '/tickers',
            'query'   => [ 'exchange_ids' => $exchange ],
            'headers' => [],
            'body'    => '',
        ],
        $runtime,
        $config,
        $request_id,
        $headers
    );

    if ( 200 !== (int) $response['status'] ) {
        return FALSE;
    }

    $payload = json_decode( isset( $response['body'] ) ? (string) $response['body'] : '', TRUE );
    $tickers = isset( $payload['data']['tickers'] ) && is_array( $payload['data']['tickers'] ) ? $payload['data']['tickers'] : [];
    foreach ( $tickers as $ticker ) {
        if ( ! is_array( $ticker ) ) {
            continue;
        }
        $identifier = tonbankcard_api_ton_slug( isset( $ticker['market']['identifier'] ) ? $ticker['market']['identifier'] : '' );
        $name = tonbankcard_api_ton_slug( isset( $ticker['market']['name'] ) ? $ticker['market']['name'] : '' );
        if ( $exchange === $identifier || $exchange === $name ) {
            return TRUE;
        }
    }

    return FALSE;
}

/**
 * Builds the final success response for screener markets.
 *
 * @param array $filters
 * @param array $source_items
 * @param array $items
 * @param array $ton_context
 * @param array $source_meta
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_screener_success( array $filters, array $source_items, array $items, array $ton_context, array $source_meta, string $request_id, array $headers ) {
    $market_meta = isset( $source_meta['market_meta'] ) && is_array( $source_meta['market_meta'] ) ? $source_meta['market_meta'] : [];
    $warnings = isset( $source_meta['warnings'] ) && is_array( $source_meta['warnings'] ) ? $source_meta['warnings'] : [];
    $summary = [
        'source_count'       => count( $source_items ),
        'result_count'       => count( $items ),
        'watched_count'      => count( array_filter( $items, function ( $item ) { return ! empty( $item['watchlist']['watched'] ); } ) ),
        'ton_count'          => count( array_filter( $items, function ( $item ) { return ! empty( $item['ton_asset'] ); } ) ),
        'average_sentiment'  => tonbankcard_api_screener_average_sentiment( $items ),
        'csv_export_enabled' => FALSE,
    ];

    $meta = array_merge(
        $market_meta,
        [
            'route_group' => 'screener',
            'screener'    => [
                'filters'            => tonbankcard_api_screener_public_filters( $filters ),
                'source_count'       => count( $source_items ),
                'result_count'       => count( $items ),
                'ton_active_assets'  => count( isset( $ton_context['active_assets'] ) ? $ton_context['active_assets'] : [] ),
                'exchange_filter'    => isset( $source_meta['exchange_filter'] ) ? $source_meta['exchange_filter'] : null,
                'warnings'           => array_values( array_unique( $warnings ) ),
                'csv_export_enabled' => FALSE,
            ],
        ]
    );

    return tonbankcard_api_success_response(
        [
            'filters'        => tonbankcard_api_screener_public_filters( $filters ),
            'sort'           => [
                'key'       => $filters['sort'],
                'direction' => $filters['direction'],
            ],
            'summary'        => $summary,
            'filter_options' => tonbankcard_api_screener_filter_options( $ton_context ),
            'items'          => array_values( $items ),
        ],
        $request_id,
        $headers,
        200,
        $meta
    );
}

/**
 * Handles saved preset requests.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_screener_presets_response( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $path = tonbankcard_api_normalize_path( $request['path'] );
    $is_collection = '/api/screener/presets' === $path;
    $preset_id = tonbankcard_api_screener_preset_id_from_path( $path );
    if ( ! $is_collection && null === $preset_id ) {
        return tonbankcard_api_error_response(
            404,
            'screener_preset_not_found',
            'No saved preset matches the request.',
            [ 'path' => $path ],
            $request_id,
            $headers
        );
    }

    $session = tonbankcard_api_screener_trusted_session( $request, $runtime );
    if ( empty( $session['ok'] ) ) {
        if ( isset( $session['reason'] ) && 'storage_unavailable' === $session['reason'] ) {
            return tonbankcard_api_error_response(
                503,
                'screener_storage_unavailable',
                'Trusted screener presets require the configured MySQL session store.',
                [ 'storage' => 'mysql' ],
                $request_id,
                $headers
            );
        }

        return tonbankcard_api_error_response(
            401,
            'screener_session_required',
            'Saved screener presets require a validated Telegram session.',
            [ 'session' => 'telegram_validated' ],
            $request_id,
            $headers
        );
    }

    $pdo = $session['pdo'];
    $user_id = (int) $session['user_id'];
    $method = strtoupper( $request['method'] );

    if ( 'GET' === $method && $is_collection ) {
        return tonbankcard_api_success_response(
            [ 'presets' => tonbankcard_api_screener_presets_for_user( $pdo, $user_id ) ],
            $request_id,
            $headers,
            200,
            [ 'route_group' => 'screener' ]
        );
    }

    if ( 'POST' === $method && $is_collection ) {
        $preset = tonbankcard_api_screener_normalize_preset_payload( tonbankcard_api_json_body( $request ) );
        if ( empty( $preset['ok'] ) ) {
            return tonbankcard_api_error_response(
                400,
                $preset['code'],
                $preset['message'],
                $preset['details'],
                $request_id,
                $headers
            );
        }

        $saved = tonbankcard_api_screener_upsert_preset( $pdo, $user_id, $preset['preset'] );
        return tonbankcard_api_success_response(
            [ 'preset' => $saved, 'presets' => tonbankcard_api_screener_presets_for_user( $pdo, $user_id ) ],
            $request_id,
            $headers,
            200,
            [ 'route_group' => 'screener' ]
        );
    }

    if ( 'PUT' === $method && null !== $preset_id ) {
        $preset = tonbankcard_api_screener_normalize_preset_payload( tonbankcard_api_json_body( $request ) );
        if ( empty( $preset['ok'] ) ) {
            return tonbankcard_api_error_response(
                400,
                $preset['code'],
                $preset['message'],
                $preset['details'],
                $request_id,
                $headers
            );
        }

        $updated = tonbankcard_api_screener_update_preset( $pdo, $user_id, $preset_id, $preset['preset'] );
        if ( null === $updated ) {
            return tonbankcard_api_error_response( 404, 'screener_preset_not_found', 'No saved preset matches the request.', [ 'id' => $preset_id ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            [ 'preset' => $updated, 'presets' => tonbankcard_api_screener_presets_for_user( $pdo, $user_id ) ],
            $request_id,
            $headers,
            200,
            [ 'route_group' => 'screener' ]
        );
    }

    if ( 'DELETE' === $method && null !== $preset_id ) {
        tonbankcard_api_screener_delete_preset( $pdo, $user_id, $preset_id );
        return tonbankcard_api_success_response(
            [ 'deleted' => TRUE, 'presets' => tonbankcard_api_screener_presets_for_user( $pdo, $user_id ) ],
            $request_id,
            $headers,
            200,
            [ 'route_group' => 'screener' ]
        );
    }

    $allowed = $is_collection ? [ 'GET', 'POST', 'OPTIONS' ] : [ 'PUT', 'DELETE', 'OPTIONS' ];
    return tonbankcard_api_method_not_allowed_response( $allowed, $request_id, $headers );
}

/**
 * Loads a trusted Telegram session and database handle.
 *
 * @param array $request
 * @param array $runtime
 * @return array
 */
function tonbankcard_api_screener_trusted_session( array $request, array $runtime ) {
    $token = tonbankcard_api_session_token_from_request( $request );
    if ( null === $token && isset( $request['headers']['authorization'] ) ) {
        $authorization = trim( (string) $request['headers']['authorization'] );
        if ( preg_match( '/^Bearer\s+([A-Fa-f0-9]{64})$/', $authorization, $matches ) ) {
            $token = strtolower( $matches[1] );
        }
    }

    if ( null === $token ) {
        return [ 'ok' => FALSE, 'reason' => 'missing_session' ];
    }

    $database_error = null;
    $pdo = tonbankcard_api_session_database_connection( $runtime, $database_error );
    if ( null === $pdo ) {
        return [ 'ok' => FALSE, 'reason' => 'storage_unavailable' ];
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
        return [ 'ok' => FALSE, 'reason' => 'untrusted_session' ];
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
 * Normalizes a saved preset request payload.
 *
 * @param array $payload
 * @return array
 */
function tonbankcard_api_screener_normalize_preset_payload( array $payload ) {
    $name = isset( $payload['name'] ) ? tonbankcard_api_screener_text( $payload['name'], 80 ) : '';
    if ( '' === $name ) {
        return [
            'ok'      => FALSE,
            'code'    => 'invalid_screener_preset_name',
            'message' => 'A saved screener preset needs a name.',
            'details' => [ 'field' => 'name' ],
        ];
    }

    $filters = tonbankcard_api_screener_filters( isset( $payload['filters'] ) && is_array( $payload['filters'] ) ? $payload['filters'] : [] );
    $filters = tonbankcard_api_screener_public_filters( $filters );
    unset( $filters['page'] );
    unset( $filters['per_page'] );
    unset( $filters['watchlist_ids'] );
    unset( $filters['ids'] );

    $sort = isset( $payload['sort']['key'] ) ? tonbankcard_api_screener_sort_key( $payload['sort']['key'] ) : ( isset( $payload['sort_key'] ) ? tonbankcard_api_screener_sort_key( $payload['sort_key'] ) : 'market_cap_rank' );
    $direction = isset( $payload['sort']['direction'] ) ? strtolower( trim( (string) $payload['sort']['direction'] ) ) : ( isset( $payload['sort_direction'] ) ? strtolower( trim( (string) $payload['sort_direction'] ) ) : 'asc' );
    if ( ! in_array( $direction, [ 'asc', 'desc' ], TRUE ) ) {
        $direction = 'asc';
    }

    return [
        'ok'     => TRUE,
        'preset' => [
            'name'           => $name,
            'filters'        => $filters,
            'sort_key'       => $sort,
            'sort_direction' => $direction,
        ],
    ];
}

/**
 * Inserts or replaces a preset by user and name.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param array $preset
 * @return array
 */
function tonbankcard_api_screener_upsert_preset( PDO $pdo, int $user_id, array $preset ) {
    $json = tonbankcard_api_encode( $preset['filters'] );
    $statement = $pdo->prepare(
        "INSERT INTO screener_presets (user_id, name, filters_json, sort_key, sort_direction, deleted_at)
         VALUES (:user_id, :name, :filters_json, :sort_key, :sort_direction, NULL)
         ON DUPLICATE KEY UPDATE
            filters_json = :update_filters_json,
            sort_key = :update_sort_key,
            sort_direction = :update_sort_direction,
            deleted_at = NULL,
            updated_at = UTC_TIMESTAMP(6)"
    );
    $statement->execute(
        [
            ':user_id'               => $user_id,
            ':name'                  => $preset['name'],
            ':filters_json'          => $json,
            ':sort_key'              => $preset['sort_key'],
            ':sort_direction'        => $preset['sort_direction'],
            ':update_filters_json'   => $json,
            ':update_sort_key'       => $preset['sort_key'],
            ':update_sort_direction' => $preset['sort_direction'],
        ]
    );

    return tonbankcard_api_screener_preset_by_name( $pdo, $user_id, $preset['name'] );
}

/**
 * Updates an existing preset by id.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param int $preset_id
 * @param array $preset
 * @return array|null
 */
function tonbankcard_api_screener_update_preset( PDO $pdo, int $user_id, int $preset_id, array $preset ) {
    $statement = $pdo->prepare(
        "UPDATE screener_presets
         SET name = :name,
             filters_json = :filters_json,
             sort_key = :sort_key,
             sort_direction = :sort_direction,
             deleted_at = NULL,
             updated_at = UTC_TIMESTAMP(6)
         WHERE id = :id
           AND user_id = :user_id
           AND deleted_at IS NULL"
    );
    $statement->execute(
        [
            ':id'             => $preset_id,
            ':user_id'        => $user_id,
            ':name'           => $preset['name'],
            ':filters_json'   => tonbankcard_api_encode( $preset['filters'] ),
            ':sort_key'       => $preset['sort_key'],
            ':sort_direction' => $preset['sort_direction'],
        ]
    );

    return tonbankcard_api_screener_preset_by_id( $pdo, $user_id, $preset_id );
}

/**
 * Soft-deletes a preset.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param int $preset_id
 * @return void
 */
function tonbankcard_api_screener_delete_preset( PDO $pdo, int $user_id, int $preset_id ) {
    $statement = $pdo->prepare(
        'UPDATE screener_presets SET deleted_at = UTC_TIMESTAMP(6), updated_at = UTC_TIMESTAMP(6) WHERE id = :id AND user_id = :user_id'
    );
    $statement->execute( [ ':id' => $preset_id, ':user_id' => $user_id ] );
}

/**
 * Returns active presets for a user.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @return array
 */
function tonbankcard_api_screener_presets_for_user( PDO $pdo, int $user_id ) {
    $statement = $pdo->prepare(
        "SELECT id, name, filters_json, sort_key, sort_direction, created_at, updated_at
         FROM screener_presets
         WHERE user_id = :user_id
           AND deleted_at IS NULL
         ORDER BY updated_at DESC, id DESC
         LIMIT 30"
    );
    $statement->execute( [ ':user_id' => $user_id ] );

    $presets = [];
    foreach ( $statement->fetchAll() as $row ) {
        $presets[] = tonbankcard_api_screener_preset_row( $row );
    }

    return $presets;
}

/**
 * Loads a preset by name.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param string $name
 * @return array
 */
function tonbankcard_api_screener_preset_by_name( PDO $pdo, int $user_id, string $name ) {
    $statement = $pdo->prepare(
        "SELECT id, name, filters_json, sort_key, sort_direction, created_at, updated_at
         FROM screener_presets
         WHERE user_id = :user_id
           AND name = :name
           AND deleted_at IS NULL
         LIMIT 1"
    );
    $statement->execute( [ ':user_id' => $user_id, ':name' => $name ] );
    $row = $statement->fetch();

    return tonbankcard_api_screener_preset_row( is_array( $row ) ? $row : [] );
}

/**
 * Loads a preset by id.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param int $preset_id
 * @return array|null
 */
function tonbankcard_api_screener_preset_by_id( PDO $pdo, int $user_id, int $preset_id ) {
    $statement = $pdo->prepare(
        "SELECT id, name, filters_json, sort_key, sort_direction, created_at, updated_at
         FROM screener_presets
         WHERE user_id = :user_id
           AND id = :id
           AND deleted_at IS NULL
         LIMIT 1"
    );
    $statement->execute( [ ':user_id' => $user_id, ':id' => $preset_id ] );
    $row = $statement->fetch();

    return is_array( $row ) ? tonbankcard_api_screener_preset_row( $row ) : null;
}

/**
 * Normalizes a database row for the public preset payload.
 *
 * @param array $row
 * @return array
 */
function tonbankcard_api_screener_preset_row( array $row ) {
    $filters = [];
    if ( isset( $row['filters_json'] ) ) {
        $decoded = json_decode( (string) $row['filters_json'], TRUE );
        $filters = is_array( $decoded ) ? $decoded : [];
    }

    return [
        'id'         => isset( $row['id'] ) ? (int) $row['id'] : 0,
        'name'       => isset( $row['name'] ) ? (string) $row['name'] : '',
        'filters'    => $filters,
        'sort'       => [
            'key'       => isset( $row['sort_key'] ) ? (string) $row['sort_key'] : 'market_cap_rank',
            'direction' => isset( $row['sort_direction'] ) ? (string) $row['sort_direction'] : 'asc',
        ],
        'created_at' => isset( $row['created_at'] ) ? (string) $row['created_at'] : null,
        'updated_at' => isset( $row['updated_at'] ) ? (string) $row['updated_at'] : null,
    ];
}

/**
 * Extracts a preset id from /api/screener/presets/{id}.
 *
 * @param string $path
 * @return int|null
 */
function tonbankcard_api_screener_preset_id_from_path( string $path ) {
    if ( preg_match( '#^/api/screener/presets/([1-9][0-9]{0,18})$#', $path, $matches ) ) {
        return (int) $matches[1];
    }

    return null;
}

/**
 * Returns public filter values.
 *
 * @param array $filters
 * @return array
 */
function tonbankcard_api_screener_public_filters( array $filters ) {
    $public = $filters;
    $public['watchlist_ids'] = array_values( $public['watchlist_ids'] );
    $public['ids'] = array_values( $public['ids'] );
    return $public;
}

/**
 * Returns filter options for the frontend.
 *
 * @param array $ton_context
 * @return array
 */
function tonbankcard_api_screener_filter_options( array $ton_context ) {
    $state = isset( $ton_context['state'] ) && is_array( $ton_context['state'] ) ? $ton_context['state'] : [];
    $assets = isset( $state['assets'] ) && is_array( $state['assets'] ) ? $state['assets'] : [];
    $tags = [];
    foreach ( $assets as $asset ) {
        if ( ! is_array( $asset ) || empty( $asset['tags'] ) || ! is_array( $asset['tags'] ) ) {
            continue;
        }
        foreach ( $asset['tags'] as $tag ) {
            if ( in_array( $tag, [ 'ton_ecosystem', 'ton_asset' ], TRUE ) ) {
                continue;
            }
            $tags[] = $tag;
        }
    }

    return [
        'ton_tags'   => array_values( array_slice( array_unique( $tags ), 0, 20 ) ),
        'categories' => isset( $state['categories'] ) && is_array( $state['categories'] ) ? array_values( $state['categories'] ) : [],
        'exchanges'  => [
            [ 'id' => 'binance', 'name' => 'Binance' ],
            [ 'id' => 'bybit_spot', 'name' => 'Bybit' ],
            [ 'id' => 'okex', 'name' => 'OKX' ],
            [ 'id' => 'kucoin', 'name' => 'KuCoin' ],
            [ 'id' => 'gate', 'name' => 'Gate.io' ],
        ],
    ];
}

/**
 * Sorts screener items by a safe key.
 *
 * @param array $items
 * @param string $sort
 * @param string $direction
 * @return array
 */
function tonbankcard_api_screener_sort_items( array $items, string $sort, string $direction ) {
    usort(
        $items,
        function ( $a, $b ) use ( $sort, $direction ) {
            $a_value = tonbankcard_api_screener_sort_value( $a, $sort );
            $b_value = tonbankcard_api_screener_sort_value( $b, $sort );
            if ( $a_value === $b_value ) {
                return 0;
            }
            if ( null === $a_value ) {
                return 1;
            }
            if ( null === $b_value ) {
                return -1;
            }
            $result = $a_value < $b_value ? -1 : 1;
            return 'desc' === $direction ? -$result : $result;
        }
    );

    return $items;
}

/**
 * Returns a sortable item value.
 *
 * @param array $item
 * @param string $sort
 * @return mixed
 */
function tonbankcard_api_screener_sort_value( array $item, string $sort ) {
    if ( 'name' === $sort ) {
        return strtolower( isset( $item['name'] ) ? (string) $item['name'] : '' );
    }
    if ( 'sentiment_score' === $sort ) {
        return isset( $item['sentiment']['score'] ) ? (float) $item['sentiment']['score'] : null;
    }
    if ( 'exchange_available' === $sort ) {
        return ! empty( $item['exchange_availability']['matched'] ) ? 1 : 0;
    }

    return tonbankcard_api_screener_number_field( $item, $sort );
}

/**
 * Returns a safe sort key.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_screener_sort_key( $value ) {
    $value = (string) $value;
    $aliases = [
        'change_24h' => 'price_change_percentage_24h_in_currency',
        'change_7d' => 'price_change_percentage_7d_in_currency',
        'change_30d' => 'price_change_percentage_30d_in_currency',
        'sentiment' => 'sentiment_score',
    ];
    if ( isset( $aliases[ $value ] ) ) {
        $value = $aliases[ $value ];
    }
    $allowed = [
        'market_cap_rank',
        'name',
        'current_price',
        'market_cap',
        'total_volume',
        'price_change_percentage_24h_in_currency',
        'price_change_percentage_7d_in_currency',
        'price_change_percentage_30d_in_currency',
        'sentiment_score',
        'exchange_available',
    ];

    return in_array( $value, $allowed, TRUE ) ? $value : 'market_cap_rank';
}

/**
 * Returns a safe CoinGecko market order.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_screener_market_order( $value ) {
    $value = strtolower( trim( (string) $value ) );
    $allowed = [ 'market_cap_desc', 'market_cap_asc', 'volume_desc', 'volume_asc', 'id_asc', 'id_desc', 'gecko_desc', 'gecko_asc' ];
    return in_array( $value, $allowed, TRUE ) ? $value : 'market_cap_desc';
}

/**
 * Parses a bounded integer value.
 *
 * @param mixed $value
 * @param int $min
 * @param int $max
 * @param int $default
 * @return int
 */
function tonbankcard_api_screener_int( $value, int $min, int $max, int $default ) {
    if ( ! is_numeric( $value ) ) {
        return $default;
    }

    return max( $min, min( $max, (int) $value ) );
}

/**
 * Parses a nullable numeric filter.
 *
 * @param mixed $value
 * @return float|null
 */
function tonbankcard_api_screener_number_or_null( $value ) {
    if ( null === $value || '' === trim( (string) $value ) || ! is_numeric( $value ) ) {
        return null;
    }

    return (float) $value;
}

/**
 * Returns a numeric item field.
 *
 * @param array $item
 * @param string $key
 * @return float|null
 */
function tonbankcard_api_screener_number_field( array $item, string $key ) {
    if ( ! isset( $item[ $key ] ) || ! is_numeric( $item[ $key ] ) ) {
        return null;
    }

    return (float) $item[ $key ];
}

/**
 * Normalizes a CoinGecko id list.
 *
 * @param mixed $value
 * @param int $max
 * @return array
 */
function tonbankcard_api_screener_coin_ids( $value, int $max = 250 ) {
    if ( is_string( $value ) ) {
        $value = explode( ',', $value );
    }
    if ( ! is_array( $value ) ) {
        return [];
    }

    $ids = [];
    foreach ( $value as $item ) {
        $id = strtolower( trim( (string) $item ) );
        if ( ! preg_match( '/^[a-z0-9._-]{1,96}$/', $id ) ) {
            continue;
        }
        if ( ! in_array( $id, $ids, TRUE ) ) {
            $ids[] = $id;
        }
        if ( count( $ids ) >= $max ) {
            break;
        }
    }

    return $ids;
}

/**
 * Normalizes a TON tag with common shorthand support.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_screener_ton_tag( $value ) {
    $tag = tonbankcard_api_ton_slug( $value );
    return 'ton' === $tag ? 'ton_ecosystem' : $tag;
}

/**
 * Normalizes a premium-gated historical range selector.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_screener_advanced_range( $value ) {
    $range = strtolower( trim( (string) $value ) );
    return preg_match( '/^[0-9]+[hdmy]$/', $range ) ? $range : '';
}

/**
 * Returns bounded plain text.
 *
 * @param mixed $value
 * @param int $max
 * @return string
 */
function tonbankcard_api_screener_text( $value, int $max ) {
    $value = trim( preg_replace( '/\s+/', ' ', (string) $value ) );
    return substr( $value, 0, $max );
}

/**
 * Clamps a number.
 *
 * @param float $value
 * @param float $min
 * @param float $max
 * @return float
 */
function tonbankcard_api_screener_clamp( float $value, float $min, float $max ) {
    return max( $min, min( $max, $value ) );
}

/**
 * Returns a label for the screener sentiment score.
 *
 * @param int $score
 * @return string
 */
function tonbankcard_api_screener_sentiment_label( int $score ) {
    if ( $score >= 35 ) {
        return 'bullish';
    }
    if ( $score <= -35 ) {
        return 'bearish';
    }
    if ( abs( $score ) <= 10 ) {
        return 'neutral';
    }

    return 'mixed';
}

/**
 * Returns average sentiment for the result set.
 *
 * @param array $items
 * @return int|null
 */
function tonbankcard_api_screener_average_sentiment( array $items ) {
    if ( empty( $items ) ) {
        return null;
    }

    $sum = 0;
    $count = 0;
    foreach ( $items as $item ) {
        if ( isset( $item['sentiment']['score'] ) && is_numeric( $item['sentiment']['score'] ) ) {
            $sum += (int) $item['sentiment']['score'];
            $count++;
        }
    }

    return $count > 0 ? (int) round( $sum / $count ) : null;
}
