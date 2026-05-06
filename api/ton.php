<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 TON ECOSYSTEM CURATION API
 * -------------------------------------------------------------------------
 * Server-owned TON asset taxonomy and manual curation workflow. Operators can
 * update the configured JSON store without deploying application code, while
 * the public site receives a normalized read model for routes and filters.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Returns TRUE when a normalized path belongs to the TON ecosystem API surface.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_ton_is_request( string $path ) {
    return '/api/ton' === $path || 0 === strpos( $path, '/api/ton/' );
}

/**
 * Returns documented TON ecosystem API routes.
 *
 * @return array
 */
function tonbankcard_api_ton_routes() {
    return [
        '/api/ton',
        '/api/ton/assets',
        '/api/ton/lookup',
    ];
}

/**
 * Handles a TON ecosystem API request.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_ton_handle( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $path = tonbankcard_api_normalize_path( $request['path'] );

    if ( '/api/ton' === $path || '/api/ton/' === $path ) {
        if ( 'GET' !== $request['method'] ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            [
                'service' => 'tonbankcard-ton-ecosystem',
                'routes'  => tonbankcard_api_ton_routes(),
            ],
            $request_id,
            $headers,
            200,
            tonbankcard_api_ton_meta( tonbankcard_api_ton_load_state( $runtime, $config ), [], 0 )
        );
    }

    if ( '/api/ton/lookup' === $path ) {
        if ( 'GET' !== $request['method'] ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        $query  = isset( $request['query'] ) && is_array( $request['query'] ) ? $request['query'] : [];
        $result = tonbankcard_api_ton_lookup_jetton( $query, $request_id, $headers );
        return $result;
    }

    if ( '/api/ton/assets' !== $path ) {
        return tonbankcard_api_error_response(
            404,
            'not_found',
            'No TON ecosystem route matches the request.',
            [ 'path' => $path ],
            $request_id,
            $headers
        );
    }

    if ( 'GET' === $request['method'] ) {
        $filters = tonbankcard_api_ton_filters( isset( $request['query'] ) ? $request['query'] : [] );
        $state = tonbankcard_api_ton_load_state( $runtime, $config );
        $assets = tonbankcard_api_ton_filter_assets( $state['assets'], $filters );

        return tonbankcard_api_success_response(
            tonbankcard_api_ton_payload( $state, $assets, $filters ),
            $request_id,
            $headers,
            200,
            tonbankcard_api_ton_meta( $state, $filters, count( $assets ) )
        );
    }

    if ( 'POST' === $request['method'] || 'PUT' === $request['method'] ) {
        if ( ! tonbankcard_api_ton_curation_allowed( $request, $runtime, $config ) ) {
            return tonbankcard_api_error_response(
                403,
                'ton_curation_forbidden',
                'TON curation writes require the configured curation token outside local development.',
                [ 'config' => 'TONBANKCARD_TON_CURATION_TOKEN' ],
                $request_id,
                $headers
            );
        }

        $body = tonbankcard_api_json_body( $request );
        if ( ! is_array( $body ) ) {
            return tonbankcard_api_error_response(
                400,
                'invalid_ton_curation_payload',
                'TON curation payload must be a JSON object.',
                [ 'expected' => [ 'assets', 'categories', 'lists' ] ],
                $request_id,
                $headers
            );
        }

        $saved = tonbankcard_api_ton_save_manual_curation( $body, $runtime, $config );
        if ( empty( $saved['ok'] ) ) {
            return tonbankcard_api_error_response(
                isset( $saved['status'] ) ? (int) $saved['status'] : 500,
                isset( $saved['code'] ) ? (string) $saved['code'] : 'ton_curation_write_failed',
                isset( $saved['message'] ) ? (string) $saved['message'] : 'TON curation could not be written.',
                isset( $saved['details'] ) && is_array( $saved['details'] ) ? $saved['details'] : [],
                $request_id,
                $headers
            );
        }

        $filters = [];
        $state = tonbankcard_api_ton_load_state( $runtime, $config );
        return tonbankcard_api_success_response(
            tonbankcard_api_ton_payload( $state, $state['assets'], $filters ),
            $request_id,
            $headers,
            200,
            tonbankcard_api_ton_meta( $state, $filters, count( $state['assets'] ) )
        );
    }

    return tonbankcard_api_method_not_allowed_response( [ 'GET', 'POST', 'PUT', 'OPTIONS' ], $request_id, $headers );
}

/**
 * Returns TON curation settings.
 *
 * @param array $config
 * @return array
 */
function tonbankcard_api_ton_settings( array $config ) {
    $ton = isset( $config['ton_ecosystem'] ) && is_array( $config['ton_ecosystem'] ) ? $config['ton_ecosystem'] : [];

    return array_merge(
        [
            'curation_store_path' => '',
            'curation_token'      => '',
            'default_categories'  => tonbankcard_api_ton_default_categories(),
            'default_lists'       => tonbankcard_api_ton_default_lists(),
            'default_assets'      => tonbankcard_api_ton_default_assets(),
        ],
        $ton
    );
}

/**
 * Returns built-in TON ecosystem categories.
 *
 * @return array
 */
function tonbankcard_api_ton_default_categories() {
    return [
        'native' => [
            'id'          => 'native',
            'title'       => 'Toncoin',
            'description' => 'Native TON network asset and settlement layer.',
            'icon'        => 'mdi-diamond-stone',
            'tag'         => 'native_ton',
        ],
        'stablecoin' => [
            'id'          => 'stablecoin',
            'title'       => 'Stablecoins',
            'description' => 'TON-issued or TON-routed stable value assets.',
            'icon'        => 'mdi-cash-check',
            'tag'         => 'stablecoin',
        ],
        'jetton' => [
            'id'          => 'jetton',
            'title'       => 'Jettons',
            'description' => 'Fungible TON jettons tracked for discovery.',
            'icon'        => 'mdi-layers-triple',
            'tag'         => 'jetton',
        ],
        'defi' => [
            'id'          => 'defi',
            'title'       => 'DeFi',
            'description' => 'TON DeFi protocols, swaps, liquidity, and yield venues.',
            'icon'        => 'mdi-bank-transfer',
            'tag'         => 'defi',
        ],
        'wallet' => [
            'id'          => 'wallet',
            'title'       => 'Wallets',
            'description' => 'Wallet and custody surfaces used by TON users.',
            'icon'        => 'mdi-wallet-outline',
            'tag'         => 'wallet',
        ],
        'infrastructure' => [
            'id'          => 'infrastructure',
            'title'       => 'Infrastructure',
            'description' => 'APIs, explorers, tooling, and network infrastructure.',
            'icon'        => 'mdi-server-network',
            'tag'         => 'infrastructure',
        ],
        'community' => [
            'id'          => 'community',
            'title'       => 'Community',
            'description' => 'Community assets that need visible verification context.',
            'icon'        => 'mdi-account-group-outline',
            'tag'         => 'community',
        ],
    ];
}

/**
 * Returns built-in TON ecosystem lists.
 *
 * @return array
 */
function tonbankcard_api_ton_default_lists() {
    return [
        'featured' => [
            'id'          => 'featured',
            'title'       => 'Featured TON assets',
            'description' => 'Editorially featured TON ecosystem entries.',
        ],
        'trending' => [
            'id'          => 'trending',
            'title'       => 'Trending assets',
            'description' => 'TON assets promoted in trending and discovery views.',
        ],
        'stablecoins' => [
            'id'          => 'stablecoins',
            'title'       => 'Stablecoins',
            'description' => 'TON stablecoin coverage and bridge-aware stable assets.',
        ],
        'defi' => [
            'id'          => 'defi',
            'title'       => 'DeFi venues',
            'description' => 'TON decentralized finance protocols and market venues.',
        ],
        'wallets' => [
            'id'          => 'wallets',
            'title'       => 'Wallets',
            'description' => 'Wallet surfaces used for TON onboarding and tracking.',
        ],
        'infrastructure' => [
            'id'          => 'infrastructure',
            'title'       => 'Infrastructure',
            'description' => 'Network tools, explorers, APIs, and developer rails.',
        ],
        'community_queue' => [
            'id'          => 'community_queue',
            'title'       => 'Community review queue',
            'description' => 'Unverified community assets pending editorial review.',
        ],
    ];
}

/**
 * Returns built-in curated TON ecosystem assets.
 *
 * @return array
 */
function tonbankcard_api_ton_default_assets() {
    return [
        [
            'id'                 => 'toncoin',
            'coin_id'            => 'the-open-network',
            'name'               => 'Toncoin',
            'symbol'             => 'TON',
            'category'           => 'native',
            'verification_state' => 'verified',
            'featured'           => TRUE,
            'priority'           => 930,
            'link_type'          => 'currency',
            'tags'               => [ 'ton_ecosystem', 'native_ton', 'layer_1' ],
            'aliases'            => [ 'TON', 'The Open Network', 'Telegram Open Network', 'toncoin' ],
            'list_ids'           => [ 'featured', 'trending' ],
            'description'        => 'Native asset of The Open Network and the anchor asset for TONBANKCARD market context.',
            'links'              => [
                'web'      => '/currency/the-open-network',
                'telegram' => '/app/coin/the-open-network',
            ],
        ],
        [
            'id'                 => 'tether-usd-ton',
            'coin_id'            => 'tether',
            'name'               => 'Tether USD on TON',
            'symbol'             => 'USDT',
            'category'           => 'stablecoin',
            'verification_state' => 'verified',
            'featured'           => TRUE,
            'priority'           => 920,
            'link_type'          => 'currency',
            'tags'               => [ 'ton_ecosystem', 'ton_asset', 'stablecoin', 'jetton' ],
            'aliases'            => [ 'USDT TON', 'USDT on TON', 'USDTTON', 'Tether TON' ],
            'contract_addresses' => [ 'EQCxE6mUtQJKFnGfaROTKOt1lZbDiiX1kCixRv7Nw2Id_sDs' ],
            'list_ids'           => [ 'featured', 'stablecoins', 'trending' ],
            'description'        => 'USDT liquidity on TON, tracked separately from generic Tether market context.',
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
        [
            'id'                 => 'ston-fi',
            'coin_id'            => 'ston',
            'name'               => 'STON.fi',
            'symbol'             => 'STON',
            'category'           => 'defi',
            'verification_state' => 'curated',
            'featured'           => TRUE,
            'priority'           => 820,
            'tags'               => [ 'ton_ecosystem', 'ton_asset', 'jetton', 'defi', 'dex' ],
            'aliases'            => [ 'STON', 'STONfi', 'STON.fi DEX' ],
            'list_ids'           => [ 'featured', 'defi' ],
            'description'        => 'TON DeFi market venue included in curated ecosystem discovery.',
        ],
        [
            'id'                 => 'dedust',
            'coin_id'            => 'dedust',
            'name'               => 'DeDust',
            'symbol'             => 'DUST',
            'category'           => 'defi',
            'verification_state' => 'curated',
            'featured'           => FALSE,
            'priority'           => 760,
            'tags'               => [ 'ton_ecosystem', 'ton_asset', 'jetton', 'defi', 'dex' ],
            'aliases'            => [ 'DUST', 'DeDust DEX' ],
            'list_ids'           => [ 'defi' ],
            'description'        => 'TON DeFi venue tracked as curated ecosystem infrastructure.',
        ],
        [
            'id'                 => 'notcoin',
            'coin_id'            => 'notcoin',
            'name'               => 'Notcoin',
            'symbol'             => 'NOT',
            'category'           => 'jetton',
            'verification_state' => 'curated',
            'featured'           => TRUE,
            'priority'           => 780,
            'tags'               => [ 'ton_ecosystem', 'ton_asset', 'jetton', 'community' ],
            'aliases'            => [ 'NOT', 'Telegram game token' ],
            'list_ids'           => [ 'featured', 'trending' ],
            'description'        => 'Telegram-native TON jetton tracked for ecosystem discovery.',
        ],
        [
            'id'                 => 'tonkeeper',
            'name'               => 'Tonkeeper',
            'symbol'             => 'WALLET',
            'category'           => 'wallet',
            'verification_state' => 'curated',
            'featured'           => TRUE,
            'priority'           => 700,
            'link_type'          => 'project',
            'project_category'   => 'wallet',
            'tags'               => [ 'ton_ecosystem', 'wallet' ],
            'aliases'            => [ 'TON wallet', 'Tonkeeper wallet' ],
            'list_ids'           => [ 'featured', 'wallets' ],
            'description'        => 'Wallet surface included for TON user onboarding and future TON Connect context.',
            'links'              => [
                'web'      => '/ton?list=wallets',
                'telegram' => '/app/ton?list=wallets',
            ],
        ],
        [
            'id'                 => 'tonapi',
            'name'               => 'TON API',
            'symbol'             => 'API',
            'category'           => 'infrastructure',
            'verification_state' => 'curated',
            'featured'           => FALSE,
            'priority'           => 660,
            'link_type'          => 'project',
            'project_category'   => 'infrastructure',
            'tags'               => [ 'ton_ecosystem', 'infrastructure', 'developer_tools' ],
            'aliases'            => [ 'TON API', 'TON developer API' ],
            'list_ids'           => [ 'infrastructure' ],
            'description'        => 'Infrastructure entry for TON data, tooling, and explorer integrations.',
            'links'              => [
                'web'      => '/ton?list=infrastructure',
                'telegram' => '/app/ton?list=infrastructure',
            ],
        ],
    ];
}

/**
 * Returns normalized state from defaults plus manual curation store.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_ton_load_state( array $runtime, array $config ) {
    $settings = tonbankcard_api_ton_settings( $config );
    $warnings = [];
    $store = tonbankcard_api_ton_read_store( $settings, $warnings );

    $categories = tonbankcard_api_ton_normalize_categories( isset( $settings['default_categories'] ) && is_array( $settings['default_categories'] ) ? $settings['default_categories'] : [] );
    $lists = tonbankcard_api_ton_normalize_lists( isset( $settings['default_lists'] ) && is_array( $settings['default_lists'] ) ? $settings['default_lists'] : [] );
    $assets = [];

    foreach ( isset( $settings['default_assets'] ) && is_array( $settings['default_assets'] ) ? $settings['default_assets'] : [] as $asset ) {
        tonbankcard_api_ton_put_asset( $assets, tonbankcard_api_ton_normalize_asset( $asset ) );
    }

    if ( isset( $store['categories'] ) && is_array( $store['categories'] ) ) {
        $categories = array_merge( $categories, tonbankcard_api_ton_normalize_categories( $store['categories'] ) );
    }
    if ( isset( $store['lists'] ) && is_array( $store['lists'] ) ) {
        $lists = array_merge( $lists, tonbankcard_api_ton_normalize_lists( $store['lists'] ) );
    }
    if ( isset( $store['assets'] ) && is_array( $store['assets'] ) ) {
        foreach ( $store['assets'] as $asset ) {
            tonbankcard_api_ton_put_asset( $assets, tonbankcard_api_ton_normalize_asset( is_array( $asset ) ? $asset : [] ) );
        }
    }
    $admin_assets = tonbankcard_api_ton_admin_assets();
    if ( ! empty( $admin_assets ) ) {
        foreach ( $admin_assets as $asset ) {
            tonbankcard_api_ton_put_asset( $assets, tonbankcard_api_ton_normalize_asset( is_array( $asset ) ? $asset : [] ) );
        }
    }

    $excluded_ids = tonbankcard_api_ton_admin_excluded_ids();
    if ( ! empty( $excluded_ids ) ) {
        foreach ( $excluded_ids as $excluded_id ) {
            unset( $assets[ $excluded_id ] );
        }
    }

    // Derive excluded coin_ids so the client can filter auto-discovered assets.
    $excluded_coin_ids = [];
    foreach ( $excluded_ids as $ex_id ) {
        // Discovered assets have ids like "coin-<coin_id>".
        if ( 0 === strpos( $ex_id, 'coin-' ) ) {
            $excluded_coin_ids[] = substr( $ex_id, 5 );
        }
    }

    uasort(
        $assets,
        function ( $left, $right ) {
            $priority_delta = ( isset( $right['priority'] ) ? (int) $right['priority'] : 0 ) - ( isset( $left['priority'] ) ? (int) $left['priority'] : 0 );
            if ( 0 !== $priority_delta ) {
                return $priority_delta;
            }
            return strcmp( $left['name'], $right['name'] );
        }
    );

    $source_type = 'default';
    if ( ! empty( $store ) && ! empty( $admin_assets ) ) {
        $source_type = 'default_plus_manual_admin';
    } elseif ( ! empty( $store ) ) {
        $source_type = 'default_plus_manual';
    } elseif ( ! empty( $admin_assets ) ) {
        $source_type = 'default_plus_admin';
    }

    return [
        'version'            => 1,
        'source'             => 'tonbankcard-ton-curation',
        'source_type'        => $source_type,
        'built_at'           => gmdate( 'c' ),
        'updated_at'         => isset( $store['updated_at'] ) ? (string) $store['updated_at'] : null,
        'store_configured'   => '' !== trim( (string) $settings['curation_store_path'] ),
        'store_loaded'       => ! empty( $store ),
        'admin_store_loaded' => ! empty( $admin_assets ),
        'warnings'           => $warnings,
        'categories'         => $categories,
        'lists'              => $lists,
        'assets'              => array_values( $assets ),
        'excluded_asset_ids'  => $excluded_ids,
        'excluded_coin_ids'   => array_values( $excluded_coin_ids ),
    ];
}

/**
 * Returns admin-managed TON assets from the safe admin store.
 *
 * @return array
 */
function tonbankcard_api_ton_admin_assets() {
    if ( ! function_exists( 'tonbankcard_runtime_admin_store' ) ) {
        return [];
    }

    $store = tonbankcard_runtime_admin_store();
    if ( empty( $store['content']['ton_assets'] ) || ! is_array( $store['content']['ton_assets'] ) ) {
        return [];
    }

    return array_values( $store['content']['ton_assets'] );
}

/**
 * Returns admin-excluded TON asset IDs from the safe admin store.
 *
 * @return array
 */
function tonbankcard_api_ton_admin_excluded_ids() {
    if ( ! function_exists( 'tonbankcard_runtime_admin_store' ) ) {
        return [];
    }

    $store = tonbankcard_runtime_admin_store();
    if ( empty( $store['content']['ton_excluded_asset_ids'] ) || ! is_array( $store['content']['ton_excluded_asset_ids'] ) ) {
        return [];
    }

    return array_values( $store['content']['ton_excluded_asset_ids'] );
}

/**
 * Adds or replaces an asset in a normalized asset map.
 *
 * When an asset with the same id already exists (typically a built-in default),
 * incoming fields override existing ones — but a missing `coin_id` on the
 * incoming asset does not wipe out a known coin_id from the prior entry.
 * Without this, an admin-saved Toncoin without coin_id strips
 * `the-open-network` and the card loses its CoinGecko market data.
 *
 * @param array $assets
 * @param array $asset
 * @return void
 */
function tonbankcard_api_ton_put_asset( array &$assets, array $asset ) {
    if ( empty( $asset['id'] ) || empty( $asset['name'] ) ) {
        return;
    }

    if ( isset( $assets[ $asset['id'] ] ) && empty( $asset['coin_id'] ) && ! empty( $assets[ $asset['id'] ]['coin_id'] ) ) {
        $asset['coin_id'] = $assets[ $asset['id'] ]['coin_id'];
        if ( empty( $asset['link_type'] ) || 'project' === $asset['link_type'] ) {
            $asset['link_type'] = 'currency';
        }
    }

    $assets[ $asset['id'] ] = $asset;
}

/**
 * Reads the configured manual curation JSON store.
 *
 * @param array $settings
 * @param array $warnings
 * @return array
 */
function tonbankcard_api_ton_read_store( array $settings, array &$warnings ) {
    $path = trim( (string) ( isset( $settings['curation_store_path'] ) ? $settings['curation_store_path'] : '' ) );
    if ( '' === $path || ! is_file( $path ) ) {
        return [];
    }

    $raw = file_get_contents( $path );
    if ( FALSE === $raw || '' === trim( $raw ) ) {
        return [];
    }

    $decoded = json_decode( $raw, TRUE );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
        $warnings[] = 'ton_curation_store_invalid_json';
        return [];
    }

    return $decoded;
}

/**
 * Looks up a TON jetton by contract address using the public TON API.
 *
 * Returns a success response with name, symbol, decimals, and description
 * pre-filled from the jetton metadata, or an error response if the lookup fails.
 *
 * @param array  $query
 * @param string $request_id
 * @param array  $headers
 * @return array
 */
function tonbankcard_api_ton_lookup_jetton( array $query, string $request_id, array $headers ) {
    $contract = trim( (string) ( isset( $query['contract'] ) ? $query['contract'] : '' ) );
    if ( '' === $contract ) {
        return tonbankcard_api_error_response(
            400,
            'ton_lookup_missing_contract',
            'Provide a contract address via the contract query parameter.',
            [ 'example' => '/api/ton/lookup?contract=EQCxE6mUtQJKFnGfaROTKOt1lZbDiiX1kCixRv7Nw2Id_sDs' ],
            $request_id,
            $headers
        );
    }

    // Basic sanity-check: TON contract addresses are base64url or hex strings.
    if ( ! preg_match( '/^[A-Za-z0-9_:+\/=-]{20,80}$/', $contract ) ) {
        return tonbankcard_api_error_response(
            400,
            'ton_lookup_invalid_contract',
            'The contract address format is not recognized.',
            [ 'contract' => $contract ],
            $request_id,
            $headers
        );
    }

    $url     = 'https://tonapi.io/v2/jettons/' . rawurlencode( $contract );
    $context = stream_context_create( [
        'http' => [
            'method'          => 'GET',
            'timeout'         => 8,
            'follow_location' => 1,
            'header'          => "Accept: application/json\r\nUser-Agent: TONBANKCARD/2\r\n",
        ],
        'ssl'  => [ 'verify_peer' => TRUE ],
    ] );

    $raw = @file_get_contents( $url, FALSE, $context );
    if ( FALSE === $raw || '' === trim( $raw ) ) {
        return tonbankcard_api_error_response(
            502,
            'ton_lookup_upstream_unavailable',
            'The TON API did not respond. Try again later.',
            [ 'contract' => $contract ],
            $request_id,
            $headers
        );
    }

    $data = json_decode( $raw, TRUE );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
        return tonbankcard_api_error_response(
            502,
            'ton_lookup_upstream_invalid',
            'The TON API returned an unexpected response.',
            [ 'contract' => $contract ],
            $request_id,
            $headers
        );
    }

    if ( ! empty( $data['error'] ) ) {
        return tonbankcard_api_error_response(
            404,
            'ton_lookup_not_found',
            'Jetton not found on the TON network.',
            [ 'contract' => $contract, 'detail' => (string) $data['error'] ],
            $request_id,
            $headers
        );
    }

    $meta        = isset( $data['metadata'] ) && is_array( $data['metadata'] ) ? $data['metadata'] : [];
    $name        = trim( (string) ( isset( $meta['name'] ) ? $meta['name'] : ( isset( $data['name'] ) ? $data['name'] : '' ) ) );
    $symbol      = strtoupper( trim( (string) ( isset( $meta['symbol'] ) ? $meta['symbol'] : ( isset( $data['symbol'] ) ? $data['symbol'] : '' ) ) ) );
    $decimals    = isset( $data['decimals'] ) ? (int) $data['decimals'] : ( isset( $meta['decimals'] ) ? (int) $meta['decimals'] : 9 );
    $description = trim( (string) ( isset( $meta['description'] ) ? $meta['description'] : '' ) );
    $image       = trim( (string) ( isset( $meta['image'] ) ? $meta['image'] : ( isset( $meta['image_data'] ) ? $meta['image_data'] : '' ) ) );
    $total_supply = isset( $data['total_supply'] ) ? (string) $data['total_supply'] : '';
    $admin_addr  = isset( $data['admin'] ) && is_array( $data['admin'] ) && isset( $data['admin']['address'] )
        ? trim( (string) $data['admin']['address'] ) : '';

    if ( '' === $name && '' === $symbol ) {
        return tonbankcard_api_error_response(
            404,
            'ton_lookup_incomplete',
            'Jetton metadata is incomplete or not yet indexed.',
            [ 'contract' => $contract ],
            $request_id,
            $headers
        );
    }

    $slug_base = '' !== $name ? $name : $symbol;
    $suggested_id = tonbankcard_api_ton_slug( $slug_base );

    return tonbankcard_api_success_response(
        [
            'contract'     => $contract,
            'suggested_id' => $suggested_id,
            'name'         => $name,
            'symbol'       => $symbol,
            'decimals'     => $decimals,
            'description'  => $description,
            'image'        => $image,
            'total_supply' => $total_supply,
            'admin_address' => $admin_addr,
            'source'       => 'tonapi',
        ],
        $request_id,
        $headers
    );
}

/**
 * Saves a manual curation payload to the configured JSON store.
 *
 * @param array $body
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_ton_save_manual_curation( array $body, array $runtime, array $config ) {
    $settings = tonbankcard_api_ton_settings( $config );
    $path = trim( (string) ( isset( $settings['curation_store_path'] ) ? $settings['curation_store_path'] : '' ) );
    if ( '' === $path ) {
        return [
            'ok'      => FALSE,
            'status'  => 503,
            'code'    => 'ton_curation_store_unconfigured',
            'message' => 'Set TONBANKCARD_TON_CURATION_FILE before writing TON curation data.',
        ];
    }

    $normalized = [
        'version'    => 1,
        'updated_at' => gmdate( 'c' ),
        'source'     => 'manual_curation',
        'categories' => tonbankcard_api_ton_normalize_categories( isset( $body['categories'] ) && is_array( $body['categories'] ) ? $body['categories'] : [] ),
        'lists'      => tonbankcard_api_ton_normalize_lists( isset( $body['lists'] ) && is_array( $body['lists'] ) ? $body['lists'] : [] ),
        'assets'     => [],
    ];

    foreach ( isset( $body['assets'] ) && is_array( $body['assets'] ) ? $body['assets'] : [] as $asset ) {
        $normalized_asset = tonbankcard_api_ton_normalize_asset( is_array( $asset ) ? $asset : [] );
        if ( ! empty( $normalized_asset['id'] ) && ! empty( $normalized_asset['name'] ) ) {
            $normalized['assets'][] = $normalized_asset;
        }
    }

    if ( empty( $normalized['assets'] ) && empty( $normalized['categories'] ) && empty( $normalized['lists'] ) ) {
        return [
            'ok'      => FALSE,
            'status'  => 400,
            'code'    => 'ton_curation_payload_empty',
            'message' => 'TON curation writes must include at least one valid asset, category, or list.',
        ];
    }

    $dir = dirname( $path );
    if ( ! is_dir( $dir ) && ! mkdir( $dir, 0775, TRUE ) && ! is_dir( $dir ) ) {
        return [
            'ok'      => FALSE,
            'status'  => 500,
            'code'    => 'ton_curation_store_directory_failed',
            'message' => 'TON curation store directory could not be created.',
        ];
    }

    $json = json_encode( $normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    if ( FALSE === $json ) {
        return [
            'ok'      => FALSE,
            'status'  => 500,
            'code'    => 'ton_curation_encode_failed',
            'message' => 'TON curation payload could not be encoded.',
        ];
    }

    $tmp = $path . '.tmp.' . getmypid();
    if ( FALSE === file_put_contents( $tmp, $json . "\n", LOCK_EX ) || ! rename( $tmp, $path ) ) {
        if ( is_file( $tmp ) ) {
            @unlink( $tmp );
        }
        return [
            'ok'      => FALSE,
            'status'  => 500,
            'code'    => 'ton_curation_write_failed',
            'message' => 'TON curation store could not be written.',
        ];
    }

    return [ 'ok' => TRUE ];
}

/**
 * Returns TRUE when a manual curation write is authorized.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @return bool
 */
function tonbankcard_api_ton_curation_allowed( array $request, array $runtime, array $config ) {
    $settings = tonbankcard_api_ton_settings( $config );
    $configured = trim( (string) ( isset( $settings['curation_token'] ) ? $settings['curation_token'] : '' ) );

    if ( '' === $configured && isset( $runtime['profile'] ) && 'local' === $runtime['profile'] ) {
        return TRUE;
    }

    if ( '' === $configured ) {
        return FALSE;
    }

    $provided = '';
    if ( isset( $request['headers']['x-tonbankcard-ton-curation-token'] ) ) {
        $provided = trim( (string) $request['headers']['x-tonbankcard-ton-curation-token'] );
    } elseif ( isset( $request['query']['token'] ) ) {
        $provided = trim( (string) $request['query']['token'] );
    }

    return '' !== $provided && hash_equals( $configured, $provided );
}

/**
 * Returns normalized API filters.
 *
 * @param array $query
 * @return array
 */
function tonbankcard_api_ton_filters( array $query ) {
    return [
        'tag'      => tonbankcard_api_ton_slug( isset( $query['tag'] ) ? $query['tag'] : '' ),
        'category' => tonbankcard_api_ton_slug( isset( $query['category'] ) ? $query['category'] : '' ),
        'state'    => tonbankcard_api_ton_slug( isset( $query['state'] ) ? $query['state'] : '' ),
        'list'     => tonbankcard_api_ton_slug( isset( $query['list'] ) ? $query['list'] : '' ),
        'q'        => tonbankcard_api_ton_query( isset( $query['q'] ) ? $query['q'] : '' ),
        'featured' => isset( $query['featured'] ) ? tonbankcard_api_ton_bool_filter( $query['featured'] ) : null,
    ];
}

/**
 * Filters normalized TON assets.
 *
 * @param array $assets
 * @param array $filters
 * @return array
 */
function tonbankcard_api_ton_filter_assets( array $assets, array $filters ) {
    $filtered = [];
    foreach ( $assets as $asset ) {
        if ( ! tonbankcard_api_ton_asset_matches_filters( $asset, $filters ) ) {
            continue;
        }
        $filtered[] = $asset;
    }
    return $filtered;
}

/**
 * Returns TRUE when an asset matches the requested filters.
 *
 * @param array $asset
 * @param array $filters
 * @return bool
 */
function tonbankcard_api_ton_asset_matches_filters( array $asset, array $filters ) {
    $tag = isset( $filters['tag'] ) ? $filters['tag'] : '';
    if ( 'ton' === $tag ) {
        $tag = 'ton_ecosystem';
    }
    if ( '' !== $tag && ! in_array( $tag, isset( $asset['tags'] ) && is_array( $asset['tags'] ) ? $asset['tags'] : [], TRUE ) ) {
        return FALSE;
    }

    if ( ! empty( $filters['category'] ) && $filters['category'] !== ( isset( $asset['category'] ) ? $asset['category'] : '' ) ) {
        return FALSE;
    }

    if ( ! empty( $filters['state'] ) && $filters['state'] !== ( isset( $asset['verification_state'] ) ? $asset['verification_state'] : '' ) ) {
        return FALSE;
    }

    if ( ! empty( $filters['list'] ) && ! in_array( $filters['list'], isset( $asset['list_ids'] ) && is_array( $asset['list_ids'] ) ? $asset['list_ids'] : [], TRUE ) ) {
        return FALSE;
    }

    if ( null !== $filters['featured'] && (bool) $filters['featured'] !== ! empty( $asset['featured'] ) ) {
        return FALSE;
    }

    if ( '' !== $filters['q'] ) {
        $haystack = strtolower(
            implode(
                ' ',
                array_merge(
                    [
                        isset( $asset['id'] ) ? $asset['id'] : '',
                        isset( $asset['name'] ) ? $asset['name'] : '',
                        isset( $asset['symbol'] ) ? $asset['symbol'] : '',
                        isset( $asset['description'] ) ? $asset['description'] : '',
                    ],
                    isset( $asset['tags'] ) && is_array( $asset['tags'] ) ? $asset['tags'] : [],
                    isset( $asset['aliases'] ) && is_array( $asset['aliases'] ) ? $asset['aliases'] : []
                )
            )
        );
        if ( FALSE === strpos( $haystack, strtolower( $filters['q'] ) ) ) {
            return FALSE;
        }
    }

    return TRUE;
}

/**
 * Builds a public TON curation payload.
 *
 * @param array $state
 * @param array $assets
 * @param array $filters
 * @return array
 */
function tonbankcard_api_ton_payload( array $state, array $assets, array $filters ) {
    return [
        'version'             => isset( $state['version'] ) ? (int) $state['version'] : 1,
        'source'              => isset( $state['source'] ) ? $state['source'] : 'tonbankcard-ton-curation',
        'source_type'         => isset( $state['source_type'] ) ? $state['source_type'] : 'default',
        'built_at'            => isset( $state['built_at'] ) ? $state['built_at'] : gmdate( 'c' ),
        'updated_at'          => isset( $state['updated_at'] ) ? $state['updated_at'] : null,
        'filters'             => $filters,
        'asset_count'         => count( $assets ),
        'market_coin_ids'     => tonbankcard_api_ton_market_coin_ids( $assets ),
        'categories'          => isset( $state['categories'] ) && is_array( $state['categories'] ) ? $state['categories'] : [],
        'lists'               => isset( $state['lists'] ) && is_array( $state['lists'] ) ? $state['lists'] : [],
        'assets'              => array_values( $assets ),
        'excluded_asset_ids'  => isset( $state['excluded_asset_ids'] ) && is_array( $state['excluded_asset_ids'] ) ? $state['excluded_asset_ids'] : [],
        'excluded_coin_ids'   => isset( $state['excluded_coin_ids'] ) && is_array( $state['excluded_coin_ids'] ) ? $state['excluded_coin_ids'] : [],
    ];
}

/**
 * Builds safe TON API metadata.
 *
 * @param array $state
 * @param array $filters
 * @param int $result_count
 * @return array
 */
function tonbankcard_api_ton_meta( array $state, array $filters, int $result_count ) {
    return [
        'ton_ecosystem' => [
            'source_type'        => isset( $state['source_type'] ) ? $state['source_type'] : 'default',
            'updated_at'         => isset( $state['updated_at'] ) ? $state['updated_at'] : null,
            'built_at'           => isset( $state['built_at'] ) ? $state['built_at'] : null,
            'store_configured'   => ! empty( $state['store_configured'] ),
            'store_loaded'       => ! empty( $state['store_loaded'] ),
            'admin_store_loaded' => ! empty( $state['admin_store_loaded'] ),
            'manual_curation'    => TRUE,
            'result_count'       => $result_count,
            'filters'            => $filters,
            'warnings'           => isset( $state['warnings'] ) && is_array( $state['warnings'] ) ? array_values( $state['warnings'] ) : [],
        ],
    ];
}

/**
 * Returns CoinGecko ids available for market snapshots.
 *
 * @param array $assets
 * @return array
 */
function tonbankcard_api_ton_market_coin_ids( array $assets ) {
    $ids = [];
    foreach ( $assets as $asset ) {
        if ( ! empty( $asset['coin_id'] ) ) {
            $ids[] = (string) $asset['coin_id'];
        }
    }
    return tonbankcard_api_ton_unique_values( $ids );
}

/**
 * Returns search entries derived from TON curation.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_ton_search_entries( array $runtime, array $config ) {
    $state = tonbankcard_api_ton_load_state( $runtime, $config );
    $entries = [];

    foreach ( isset( $state['assets'] ) && is_array( $state['assets'] ) ? $state['assets'] : [] as $asset ) {
        if ( empty( $asset['visible'] ) ) {
            continue;
        }
        $id = isset( $asset['id'] ) ? (string) $asset['id'] : '';
        $coin_id = isset( $asset['coin_id'] ) ? (string) $asset['coin_id'] : '';
        $type = 'toncoin' === $id && 'the-open-network' === $coin_id ? 'coin' : 'ton_asset';
        $entry = [
            'type'                 => $type,
            'id'                   => 'coin' === $type && '' !== $coin_id ? $coin_id : $id,
            'coin_id'              => $coin_id,
            'title'                => isset( $asset['name'] ) ? (string) $asset['name'] : $id,
            'symbol'               => isset( $asset['symbol'] ) ? (string) $asset['symbol'] : '',
            'subtitle'             => tonbankcard_api_ton_search_subtitle( $asset ),
            'tags'                 => isset( $asset['tags'] ) && is_array( $asset['tags'] ) ? $asset['tags'] : [],
            'aliases'              => isset( $asset['aliases'] ) && is_array( $asset['aliases'] ) ? $asset['aliases'] : [],
            'contract_addresses'   => isset( $asset['contract_addresses'] ) && is_array( $asset['contract_addresses'] ) ? $asset['contract_addresses'] : [],
            'popular_score'        => isset( $asset['priority'] ) ? (int) $asset['priority'] : 0,
            'route'                => isset( $asset['route'] ) && is_array( $asset['route'] ) ? $asset['route'] : [ 'name' => 'ton', 'path' => '/ton' ],
            'links'                => isset( $asset['links'] ) && is_array( $asset['links'] ) ? $asset['links'] : [ 'web' => '/ton', 'telegram' => '/app/ton' ],
            'category'             => isset( $asset['category'] ) ? $asset['category'] : '',
            'category_id'          => isset( $asset['category'] ) ? $asset['category'] : '',
            'verification_state'   => isset( $asset['verification_state'] ) ? $asset['verification_state'] : 'unverified',
            'verified'             => ! empty( $asset['verified'] ),
            'curated'              => ! empty( $asset['curated'] ),
            'featured'             => ! empty( $asset['featured'] ),
            'list_ids'             => isset( $asset['list_ids'] ) && is_array( $asset['list_ids'] ) ? $asset['list_ids'] : [],
        ];
        $entries[] = $entry;
    }

    return $entries;
}

/**
 * Returns a search result subtitle for a TON asset.
 *
 * @param array $asset
 * @return string
 */
function tonbankcard_api_ton_search_subtitle( array $asset ) {
    $symbol = isset( $asset['symbol'] ) ? strtoupper( (string) $asset['symbol'] ) : '';
    $state = isset( $asset['verification_state'] ) ? (string) $asset['verification_state'] : 'unverified';
    $category = isset( $asset['category'] ) ? (string) $asset['category'] : '';
    $label = trim( strtoupper( $symbol ) . ' ' . ucfirst( $category ) );

    return trim( $label ) . ' - ' . ucfirst( $state ) . ' TON asset';
}

/**
 * Normalizes category maps and lists.
 *
 * @param array $categories
 * @return array
 */
function tonbankcard_api_ton_normalize_categories( array $categories ) {
    $normalized = [];
    foreach ( $categories as $key => $category ) {
        if ( ! is_array( $category ) ) {
            continue;
        }
        $id = tonbankcard_api_ton_slug( isset( $category['id'] ) ? $category['id'] : $key );
        if ( '' === $id ) {
            continue;
        }
        $normalized[ $id ] = [
            'id'          => $id,
            'title'       => isset( $category['title'] ) ? (string) $category['title'] : ucwords( str_replace( '_', ' ', $id ) ),
            'description' => isset( $category['description'] ) ? (string) $category['description'] : '',
            'icon'        => isset( $category['icon'] ) ? (string) $category['icon'] : 'mdi-tag-outline',
            'tag'         => tonbankcard_api_ton_slug( isset( $category['tag'] ) ? $category['tag'] : $id ),
        ];
    }
    return $normalized;
}

/**
 * Normalizes ecosystem list maps and lists.
 *
 * @param array $lists
 * @return array
 */
function tonbankcard_api_ton_normalize_lists( array $lists ) {
    $normalized = [];
    foreach ( $lists as $key => $list ) {
        if ( ! is_array( $list ) ) {
            continue;
        }
        $id = tonbankcard_api_ton_slug( isset( $list['id'] ) ? $list['id'] : $key );
        if ( '' === $id ) {
            continue;
        }
        $normalized[ $id ] = [
            'id'          => $id,
            'title'       => isset( $list['title'] ) ? (string) $list['title'] : ucwords( str_replace( '_', ' ', $id ) ),
            'description' => isset( $list['description'] ) ? (string) $list['description'] : '',
        ];
    }
    return $normalized;
}

/**
 * Normalizes a TON asset entry.
 *
 * @param array $asset
 * @return array
 */
function tonbankcard_api_ton_normalize_asset( array $asset ) {
    $id = tonbankcard_api_ton_slug( isset( $asset['id'] ) ? $asset['id'] : ( isset( $asset['name'] ) ? $asset['name'] : '' ) );
    $name = trim( (string) ( isset( $asset['name'] ) ? $asset['name'] : $id ) );
    $coin_id = tonbankcard_api_ton_safe_id( isset( $asset['coin_id'] ) ? $asset['coin_id'] : '' );
    $category = tonbankcard_api_ton_slug( isset( $asset['category'] ) ? $asset['category'] : 'jetton' );
    $state = tonbankcard_api_ton_verification_state( isset( $asset['verification_state'] ) ? $asset['verification_state'] : ( isset( $asset['state'] ) ? $asset['state'] : 'unverified' ) );
    $link_type = tonbankcard_api_ton_link_type( isset( $asset['link_type'] ) ? $asset['link_type'] : '', $coin_id );
    $project_category = tonbankcard_api_ton_slug( isset( $asset['project_category'] ) ? $asset['project_category'] : '' );
    $tags = tonbankcard_api_ton_unique_values(
        array_merge(
            [ 'ton_ecosystem', $category ],
            tonbankcard_api_ton_values( isset( $asset['tags'] ) ? $asset['tags'] : [] )
        )
    );

    $normalized = [
        'id'                 => $id,
        'coin_id'            => $coin_id,
        'name'               => '' === $name ? $id : $name,
        'symbol'             => strtoupper( trim( (string) ( isset( $asset['symbol'] ) ? $asset['symbol'] : '' ) ) ),
        'category'           => $category,
        'verification_state' => $state,
        'verified'           => 'verified' === $state,
        'curated'            => in_array( $state, [ 'verified', 'curated' ], TRUE ),
        'featured'           => ! empty( $asset['featured'] ),
        'visible'            => ! array_key_exists( 'visible', $asset ) || ! empty( $asset['visible'] ),
        'priority'           => isset( $asset['priority'] ) ? max( 0, (int) $asset['priority'] ) : 0,
        'link_type'          => $link_type,
        'project_category'   => $project_category,
        'tags'               => $tags,
        'aliases'            => tonbankcard_api_ton_unique_values( tonbankcard_api_ton_values( isset( $asset['aliases'] ) ? $asset['aliases'] : [] ) ),
        'list_ids'           => tonbankcard_api_ton_unique_values( tonbankcard_api_ton_values( isset( $asset['list_ids'] ) ? $asset['list_ids'] : [] ) ),
        'contract_addresses' => tonbankcard_api_ton_unique_values( tonbankcard_api_ton_values( isset( $asset['contract_addresses'] ) ? $asset['contract_addresses'] : [] ) ),
        'description'        => isset( $asset['description'] ) ? trim( (string) $asset['description'] ) : '',
    ];

    if ( '' === $normalized['coin_id'] ) {
        unset( $normalized['coin_id'] );
    }
    if ( '' === $normalized['project_category'] ) {
        unset( $normalized['project_category'] );
    }
    if ( empty( $normalized['list_ids'] ) && ! empty( $normalized['featured'] ) ) {
        $normalized['list_ids'] = [ 'featured' ];
    }

    $normalized['route'] = tonbankcard_api_ton_asset_route( $asset, $normalized );
    $normalized['links'] = tonbankcard_api_ton_asset_links( $asset, $normalized );

    return $normalized;
}

/**
 * Normalizes the link target type chosen by curators.
 *
 * @param mixed  $value   Raw link_type value from the curation source.
 * @param string $coin_id Resolved CoinGecko id, when present.
 * @return string         Either 'currency' or 'project'.
 */
function tonbankcard_api_ton_link_type( $value, $coin_id ) {
    $value = strtolower( trim( (string) $value ) );
    if ( 'currency' === $value || 'coin' === $value || 'market' === $value ) {
        return 'currency';
    }
    if ( 'project' === $value || 'asset' === $value || 'catalog' === $value ) {
        return 'project';
    }
    return '' !== (string) $coin_id ? 'currency' : 'project';
}

/**
 * Returns a safe route for a normalized TON asset.
 *
 * @param array $raw
 * @param array $asset
 * @return array
 */
function tonbankcard_api_ton_asset_route( array $raw, array $asset ) {
    $link_type = isset( $asset['link_type'] ) ? (string) $asset['link_type'] : '';

    if ( 'currency' === $link_type && ! empty( $asset['coin_id'] ) ) {
        return [
            'name'   => 'currency',
            'params' => [ 'id' => $asset['coin_id'] ],
            'path'   => '/currency/' . rawurlencode( $asset['coin_id'] ),
        ];
    }

    if ( 'project' === $link_type && ! empty( $asset['id'] ) ) {
        return [
            'name'   => 'ton-asset',
            'params' => [ 'id' => $asset['id'] ],
            'path'   => '/ton/asset/' . rawurlencode( $asset['id'] ),
        ];
    }

    if ( isset( $raw['route'] ) && is_array( $raw['route'] ) && ! empty( $raw['route']['path'] ) ) {
        return $raw['route'];
    }

    if ( ! empty( $asset['id'] ) ) {
        return [
            'name'   => 'ton-asset',
            'params' => [ 'id' => $asset['id'] ],
            'path'   => '/ton/asset/' . rawurlencode( $asset['id'] ),
        ];
    }

    if ( ! empty( $asset['coin_id'] ) ) {
        return [
            'name'   => 'currency',
            'params' => [ 'id' => $asset['coin_id'] ],
            'path'   => '/currency/' . rawurlencode( $asset['coin_id'] ),
        ];
    }

    return [
        'name'  => 'ton',
        'query' => [ 'category' => $asset['category'] ],
        'path'  => '/ton?category=' . rawurlencode( $asset['category'] ),
    ];
}

/**
 * Returns safe links for a normalized TON asset.
 *
 * @param array $raw
 * @param array $asset
 * @return array
 */
function tonbankcard_api_ton_asset_links( array $raw, array $asset ) {
    if ( isset( $raw['links'] ) && is_array( $raw['links'] ) ) {
        return array_merge( [ 'web' => '/ton', 'telegram' => '/app/ton' ], $raw['links'] );
    }

    $path = isset( $asset['route']['path'] ) ? $asset['route']['path'] : '/ton';
    return [
        'web'      => $path,
        'telegram' => 0 === strpos( $path, '/currency/' )
            ? str_replace( '/currency/', '/app/coin/', $path )
            : '/app/ton',
    ];
}

/**
 * Returns a normalized verification state.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_ton_verification_state( $value ) {
    $state = tonbankcard_api_ton_slug( $value );
    return in_array( $state, [ 'verified', 'curated', 'unverified' ], TRUE ) ? $state : 'unverified';
}

/**
 * Returns a safe CoinGecko-like id.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_ton_safe_id( $value ) {
    $value = trim( (string) $value );
    return 1 === preg_match( '/^[A-Za-z0-9._-]{1,160}$/', $value ) ? $value : '';
}

/**
 * Returns a safe slug token.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_ton_slug( $value ) {
    $slug = strtolower( trim( (string) $value ) );
    $slug = preg_replace( '/[^a-z0-9._-]+/', '_', $slug );
    $slug = trim( (string) $slug, '._-' );
    return substr( $slug, 0, 80 );
}

/**
 * Returns a bounded text query.
 *
 * @param mixed $value
 * @return string
 */
function tonbankcard_api_ton_query( $value ) {
    $query = trim( preg_replace( '/\s+/', ' ', (string) $value ) );
    return substr( $query, 0, 120 );
}

/**
 * Returns nullable boolean filter value.
 *
 * @param mixed $value
 * @return bool|null
 */
function tonbankcard_api_ton_bool_filter( $value ) {
    $value = strtolower( trim( (string) $value ) );
    if ( in_array( $value, [ '1', 'true', 'yes', 'featured' ], TRUE ) ) {
        return TRUE;
    }
    if ( in_array( $value, [ '0', 'false', 'no' ], TRUE ) ) {
        return FALSE;
    }
    return null;
}

/**
 * Normalizes a field to a list of non-empty strings.
 *
 * @param mixed $values
 * @return array
 */
function tonbankcard_api_ton_values( $values ) {
    if ( ! is_array( $values ) ) {
        $values = '' === trim( (string) $values ) ? [] : [ $values ];
    }

    $normalized = [];
    foreach ( $values as $value ) {
        $value = trim( (string) $value );
        if ( '' !== $value ) {
            $normalized[] = $value;
        }
    }
    return $normalized;
}

/**
 * Returns stable unique values.
 *
 * @param array $values
 * @return array
 */
function tonbankcard_api_ton_unique_values( array $values ) {
    $seen = [];
    $unique = [];
    foreach ( $values as $value ) {
        $key = strtolower( trim( (string) $value ) );
        if ( '' === $key || isset( $seen[ $key ] ) ) {
            continue;
        }
        $seen[ $key ] = TRUE;
        $unique[] = trim( (string) $value );
    }
    return $unique;
}
