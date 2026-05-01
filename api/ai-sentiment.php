<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 AI SENTIMENT INGESTION PIPELINE
 * -------------------------------------------------------------------------
 * Deterministic market, trend, watchlist, and curated TON signal scoring used
 * as structured input for the AI provider layer.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Handles deterministic AI sentiment input requests.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param array $provider
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_ai_sentiment_handle( array $request, array $runtime, array $config, array $provider, string $request_id, array $headers ) {
    if ( 'POST' !== $request['method'] ) {
        return tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers );
    }

    $settings = tonbankcard_api_ai_sentiment_settings( $config );
    $validation = tonbankcard_api_ai_sentiment_validate_payload( tonbankcard_api_json_body( $request ), $settings );
    if ( empty( $validation['ok'] ) ) {
        return tonbankcard_api_error_response(
            400,
            isset( $validation['code'] ) ? $validation['code'] : 'invalid_sentiment_request',
            isset( $validation['message'] ) ? $validation['message'] : 'The sentiment input request is invalid.',
            isset( $validation['details'] ) && is_array( $validation['details'] ) ? $validation['details'] : [],
            $request_id,
            $headers
        );
    }

    $input = $validation['input'];
    $cache_context = tonbankcard_api_ai_sentiment_cache_context( $input, $settings, $runtime, $config, $provider );
    $cached = tonbankcard_api_ai_sentiment_cache_read( $cache_context, $runtime, $config );
    if ( null !== $cached ) {
        return tonbankcard_api_success_response(
            $cached['data'],
            $request_id,
            $headers,
            200,
            tonbankcard_api_ai_sentiment_meta( $cached['data'], $settings, $provider, $cache_context, 'hit' )
        );
    }

    $data = tonbankcard_api_ai_sentiment_build_inputs( $input, $settings, $runtime, $config, $provider, $cache_context );
    tonbankcard_api_ai_sentiment_cache_store( $cache_context, $runtime, $config, $data );

    return tonbankcard_api_success_response(
        $data,
        $request_id,
        $headers,
        200,
        tonbankcard_api_ai_sentiment_meta( $data, $settings, $provider, $cache_context, $cache_context['enabled'] ? 'miss' : 'bypass' )
    );
}

/**
 * Returns sentiment pipeline settings.
 *
 * @param array $config
 * @return array
 */
function tonbankcard_api_ai_sentiment_settings( array $config ) {
    $ai = isset( $config['ai'] ) && is_array( $config['ai'] ) ? $config['ai'] : [];
    $sentiment = isset( $ai['sentiment'] ) && is_array( $ai['sentiment'] ) ? $ai['sentiment'] : [];
    $intervals = isset( $sentiment['source_refresh_intervals'] ) && is_array( $sentiment['source_refresh_intervals'] )
        ? $sentiment['source_refresh_intervals']
        : [];
    $weights = isset( $sentiment['weights'] ) && is_array( $sentiment['weights'] ) ? $sentiment['weights'] : [];

    return [
        'pipeline_version'         => isset( $sentiment['pipeline_version'] ) && '' !== trim( (string) $sentiment['pipeline_version'] ) ? (string) $sentiment['pipeline_version'] : 'v1',
        'cache_ttl_seconds'        => isset( $sentiment['cache_ttl_seconds'] ) ? max( 0, (int) $sentiment['cache_ttl_seconds'] ) : 300,
        'max_coin_ids'             => isset( $sentiment['max_coin_ids'] ) ? max( 1, (int) $sentiment['max_coin_ids'] ) : 12,
        'max_watchlist_coin_ids'   => isset( $sentiment['max_watchlist_coin_ids'] ) ? max( 1, (int) $sentiment['max_watchlist_coin_ids'] ) : 80,
        'default_coin_ids'         => tonbankcard_api_ai_sentiment_coin_ids(
            isset( $sentiment['default_coin_ids'] ) ? $sentiment['default_coin_ids'] : [ 'toncoin', 'bitcoin', 'ethereum', 'tether' ],
            12
        ),
        'source_refresh_intervals' => array_merge(
            [
                'market_movement'         => 60,
                'volume_spike'            => 60,
                'global_market'           => 300,
                'trend_ranking'           => 900,
                'watchlist_concentration' => 300,
                'curated_ton_ecosystem'   => 86400,
            ],
            array_map( 'intval', $intervals )
        ),
        'weights'                  => array_merge(
            [
                'market_movement'         => 0.35,
                'volume_spike'            => 0.20,
                'global_market'           => 0.10,
                'trend_ranking'           => 0.20,
                'watchlist_concentration' => 0.08,
                'curated_ton_ecosystem'   => 0.07,
            ],
            $weights
        ),
        'curated_ton_assets'       => isset( $sentiment['curated_ton_assets'] ) && is_array( $sentiment['curated_ton_assets'] )
            ? $sentiment['curated_ton_assets']
            : [
                [
                    'coin_id' => 'toncoin',
                    'symbol'  => 'TON',
                    'label'   => 'Native TON ecosystem asset',
                    'tags'    => [ 'ton_ecosystem', 'native_ton' ],
                ],
                [
                    'coin_id' => 'tether',
                    'symbol'  => 'USDT',
                    'label'   => 'USDT on TON curated asset',
                    'tags'    => [ 'ton_ecosystem', 'stablecoin', 'jetton' ],
                ],
            ],
    ];
}

/**
 * Validates and normalizes a sentiment request payload.
 *
 * @param array $payload
 * @param array $settings
 * @return array
 */
function tonbankcard_api_ai_sentiment_validate_payload( array $payload, array $settings ) {
    $type = isset( $payload['insight_type'] ) ? strtolower( trim( (string) $payload['insight_type'] ) ) : 'sentiment';
    $allowed_types = [ 'market_summary', 'coin_summary', 'watchlist_digest', 'alert_explanation', 'sentiment' ];
    if ( ! in_array( $type, $allowed_types, TRUE ) ) {
        return [
            'ok'      => FALSE,
            'code'    => 'unsupported_insight_type',
            'message' => 'Sentiment insight_type is not supported.',
            'details' => [ 'allowed' => $allowed_types ],
        ];
    }

    $subject = isset( $payload['subject'] ) ? tonbankcard_api_ai_clean_text( (string) $payload['subject'], 160 ) : '';
    if ( '' === $subject ) {
        $subject = 'Market sentiment';
    }

    $coin_ids = tonbankcard_api_ai_sentiment_coin_ids(
        isset( $payload['coin_ids'] ) ? $payload['coin_ids'] : [],
        isset( $settings['max_coin_ids'] ) ? (int) $settings['max_coin_ids'] : 12
    );
    if ( empty( $coin_ids ) ) {
        $coin_ids = isset( $settings['default_coin_ids'] ) && is_array( $settings['default_coin_ids'] ) ? $settings['default_coin_ids'] : [ 'toncoin' ];
    }

    $watchlist_coin_ids = tonbankcard_api_ai_sentiment_coin_ids(
        isset( $payload['watchlist_coin_ids'] ) ? $payload['watchlist_coin_ids'] : [],
        isset( $settings['max_watchlist_coin_ids'] ) ? (int) $settings['max_watchlist_coin_ids'] : 80
    );

    return [
        'ok'    => TRUE,
        'input' => [
            'insight_type'       => $type,
            'subject'            => $subject,
            'coin_ids'           => $coin_ids,
            'watchlist_coin_ids' => $watchlist_coin_ids,
            'surface'            => isset( $payload['surface'] ) ? tonbankcard_api_ai_sentiment_surface( $payload['surface'] ) : 'public_web',
        ],
    ];
}

/**
 * Sanitizes CoinGecko-style coin id lists.
 *
 * @param mixed $value
 * @param int $max_items
 * @return array
 */
function tonbankcard_api_ai_sentiment_coin_ids( $value, int $max_items ) {
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
        if ( count( $ids ) >= $max_items ) {
            break;
        }
    }

    return $ids;
}

/**
 * Returns a supported surface label.
 *
 * @param mixed $surface
 * @return string
 */
function tonbankcard_api_ai_sentiment_surface( $surface ) {
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
 * Builds cache metadata for a sentiment request.
 *
 * @param array $input
 * @param array $settings
 * @param array $runtime
 * @param array $config
 * @param array $provider
 * @return array
 */
function tonbankcard_api_ai_sentiment_cache_context( array $input, array $settings, array $runtime, array $config, array $provider ) {
    $cache_settings = tonbankcard_api_cache_settings( $config );
    $redis = tonbankcard_api_redis_settings( $runtime, $config );
    $ttl = isset( $settings['cache_ttl_seconds'] ) ? max( 0, (int) $settings['cache_ttl_seconds'] ) : 0;
    if ( isset( $cache_settings['ttls']['sentiment_inputs'] ) ) {
        $ttl = max( 0, min( $ttl, (int) $cache_settings['ttls']['sentiment_inputs'] ) );
    }

    $parts = [
        'pipeline_version'         => isset( $settings['pipeline_version'] ) ? $settings['pipeline_version'] : 'v1',
        'prompt_version'           => isset( $provider['prompt_version'] ) ? $provider['prompt_version'] : 'v1',
        'insight_type'             => $input['insight_type'],
        'subject'                  => $input['subject'],
        'coin_ids'                 => $input['coin_ids'],
        'watchlist_coin_ids_hash'  => hash( 'sha256', json_encode( $input['watchlist_coin_ids'], JSON_UNESCAPED_SLASHES ) ),
        'source_refresh_intervals' => isset( $settings['source_refresh_intervals'] ) ? $settings['source_refresh_intervals'] : [],
    ];
    $key = tonbankcard_api_redis_key( $runtime, $config, 'sentiment_inputs', $parts );

    return [
        'enabled'             => ! empty( $cache_settings['enabled'] ) && ! empty( $redis['enabled'] ) && $ttl > 0,
        'ttl_seconds'         => $ttl,
        'stale_ttl_seconds'   => isset( $cache_settings['stale_ttl_seconds'] ) ? (int) $cache_settings['stale_ttl_seconds'] : 0,
        'key'                 => $key,
        'key_hash'            => hash( 'sha256', $key ),
    ];
}

/**
 * Reads a fresh sentiment cache entry.
 *
 * @param array $cache_context
 * @param array $runtime
 * @param array $config
 * @return array|null
 */
function tonbankcard_api_ai_sentiment_cache_read( array $cache_context, array $runtime, array $config ) {
    if ( empty( $cache_context['enabled'] ) ) {
        return null;
    }

    $cached = tonbankcard_api_cache_get( $runtime, $config, $cache_context['key'] );
    if ( isset( $cached['state'] ) && 'hit' === $cached['state'] && isset( $cached['entry']['data'] ) && is_array( $cached['entry']['data'] ) ) {
        return [
            'data' => $cached['entry']['data'],
            'age'  => isset( $cached['cache_age_seconds'] ) ? (int) $cached['cache_age_seconds'] : 0,
        ];
    }

    return null;
}

/**
 * Stores a sentiment cache entry.
 *
 * @param array $cache_context
 * @param array $runtime
 * @param array $config
 * @param array $data
 * @return void
 */
function tonbankcard_api_ai_sentiment_cache_store( array $cache_context, array $runtime, array $config, array $data ) {
    if ( empty( $cache_context['enabled'] ) ) {
        return;
    }

    tonbankcard_api_cache_set(
        $runtime,
        $config,
        $cache_context['key'],
        $data,
        [
            'status'           => 200,
            'path'             => '/api/ai/sentiment-inputs',
            'pipeline_version' => isset( $data['trace']['pipeline_version'] ) ? $data['trace']['pipeline_version'] : 'v1',
            'prompt_version'   => isset( $data['trace']['prompt_version'] ) ? $data['trace']['prompt_version'] : 'v1',
            'market_data_hash' => isset( $data['trace']['market_data_hash'] ) ? $data['trace']['market_data_hash'] : null,
        ],
        isset( $cache_context['ttl_seconds'] ) ? (int) $cache_context['ttl_seconds'] : 0,
        isset( $cache_context['stale_ttl_seconds'] ) ? (int) $cache_context['stale_ttl_seconds'] : 0,
        time()
    );
}

/**
 * Builds deterministic sentiment inputs.
 *
 * @param array $input
 * @param array $settings
 * @param array $runtime
 * @param array $config
 * @param array $provider
 * @param array $cache_context
 * @return array
 */
function tonbankcard_api_ai_sentiment_build_inputs( array $input, array $settings, array $runtime, array $config, array $provider, array $cache_context ) {
    $fetched_at = time();
    $market_provider = tonbankcard_api_market_provider_config( $runtime, $config );
    $sources = [];

    $coins = tonbankcard_api_ai_sentiment_fetch_source(
        'market_movement',
        'coins/markets',
        [
            'vs_currency'             => 'usd',
            'ids'                     => implode( ',', $input['coin_ids'] ),
            'price_change_percentage' => '1h,24h,7d',
        ],
        $market_provider,
        $config,
        $settings,
        $fetched_at
    );
    $sources['market_movement'] = $coins['source'];
    $sources['volume_spike'] = array_merge(
        $coins['source'],
        [
            'name'                     => 'volume_spike',
            'refresh_interval_seconds' => tonbankcard_api_ai_sentiment_refresh_interval( 'volume_spike', $settings ),
            'derived_from'             => 'market_movement',
        ]
    );

    $global = tonbankcard_api_ai_sentiment_fetch_source(
        'global_market',
        'global',
        [],
        $market_provider,
        $config,
        $settings,
        $fetched_at
    );
    $sources['global_market'] = $global['source'];

    $trending = tonbankcard_api_ai_sentiment_fetch_source(
        'trend_ranking',
        'search/trending',
        [],
        $market_provider,
        $config,
        $settings,
        $fetched_at
    );
    $sources['trend_ranking'] = $trending['source'];

    $sources['watchlist_concentration'] = tonbankcard_api_ai_sentiment_manual_source(
        'watchlist_concentration',
        empty( $input['watchlist_coin_ids'] ) ? 'missing' : 'available',
        $settings,
        $fetched_at,
        empty( $input['watchlist_coin_ids'] ) ? 0 : 0.78
    );
    $sources['curated_ton_ecosystem'] = tonbankcard_api_ai_sentiment_manual_source(
        'curated_ton_ecosystem',
        'available',
        $settings,
        $fetched_at,
        0.72
    );

    $signals = array_values(
        array_filter(
            [
                tonbankcard_api_ai_sentiment_market_movement_signal( $coins['data'], $sources['market_movement'], $input['coin_ids'] ),
                tonbankcard_api_ai_sentiment_volume_signal( $coins['data'], $sources['volume_spike'], $input['coin_ids'] ),
                tonbankcard_api_ai_sentiment_global_signal( $global['data'], $sources['global_market'] ),
                tonbankcard_api_ai_sentiment_trend_signal( $trending['data'], $sources['trend_ranking'], $input['coin_ids'] ),
                tonbankcard_api_ai_sentiment_watchlist_signal( $input['coin_ids'], $input['watchlist_coin_ids'], $sources['watchlist_concentration'] ),
                tonbankcard_api_ai_sentiment_curated_ton_signal( $input['coin_ids'], $settings, $sources['curated_ton_ecosystem'] ),
            ]
        )
    );
    $scores = tonbankcard_api_ai_sentiment_aggregate_scores( $signals, $sources, $settings );
    $status = tonbankcard_api_ai_sentiment_status( $sources );
    $market_data_age_seconds = tonbankcard_api_ai_sentiment_max_age( $sources );
    $market_data_updated_at = null === $market_data_age_seconds ? null : gmdate( 'c', max( 0, $fetched_at - $market_data_age_seconds ) );
    $market_data = [
        'sentiment_pipeline_version' => isset( $settings['pipeline_version'] ) ? $settings['pipeline_version'] : 'v1',
        'source_refresh_intervals'   => isset( $settings['source_refresh_intervals'] ) ? $settings['source_refresh_intervals'] : [],
        'status'                     => $status,
        'scores'                     => $scores,
        'signals'                    => $signals,
        'sources'                    => $sources,
        'coin_ids'                   => $input['coin_ids'],
        'watchlist'                  => tonbankcard_api_ai_sentiment_watchlist_context( $input['coin_ids'], $input['watchlist_coin_ids'] ),
        'copyright_policy'           => 'No copyrighted article text is ingested, stored, or exposed by the MVP sentiment pipeline.',
    ];
    $market_data_hash = tonbankcard_api_ai_sentiment_hash( $market_data );
    $trace = [
        'cache_key_hash'       => isset( $cache_context['key_hash'] ) ? $cache_context['key_hash'] : null,
        'market_data_hash'     => $market_data_hash,
        'pipeline_version'     => isset( $settings['pipeline_version'] ) ? $settings['pipeline_version'] : 'v1',
        'prompt_version'       => isset( $provider['prompt_version'] ) ? $provider['prompt_version'] : 'v1',
        'provider'             => isset( $provider['name'] ) ? $provider['name'] : 'groq',
        'model_id'             => isset( $provider['model_id'] ) ? $provider['model_id'] : null,
        'cache_ttl_seconds'    => isset( $cache_context['ttl_seconds'] ) ? (int) $cache_context['ttl_seconds'] : 0,
    ];
    $market_data['trace'] = $trace;

    return [
        'status'                   => $status,
        'subject'                  => $input['subject'],
        'insight_type'             => $input['insight_type'],
        'surface'                  => $input['surface'],
        'scores'                   => $scores,
        'signals'                  => $signals,
        'sources'                  => $sources,
        'source_refresh_intervals' => isset( $settings['source_refresh_intervals'] ) ? $settings['source_refresh_intervals'] : [],
        'ai_context'               => [
            'insight_type'            => $input['insight_type'],
            'subject'                 => $input['subject'],
            'market_data_age_seconds' => null === $market_data_age_seconds ? 0 : $market_data_age_seconds,
            'market_data_updated_at'  => $market_data_updated_at,
            'market_data'             => $market_data,
        ],
        'trace'                    => $trace,
    ];
}

/**
 * Fetches and classifies a provider source.
 *
 * @param string $name
 * @param string $path
 * @param array $query
 * @param array $provider
 * @param array $config
 * @param array $settings
 * @param int $fetched_at
 * @return array
 */
function tonbankcard_api_ai_sentiment_fetch_source( string $name, string $path, array $query, array $provider, array $config, array $settings, int $fetched_at ) {
    $refresh = tonbankcard_api_ai_sentiment_refresh_interval( $name, $settings );
    if ( empty( $provider ) || ! function_exists( 'tonbankcard_api_market_fetch' ) ) {
        return [
            'data'   => [],
            'source' => tonbankcard_api_ai_sentiment_source( $name, 'unavailable', $refresh, $fetched_at, null, 0, 'market_gateway_unavailable' ),
        ];
    }

    $response = tonbankcard_api_market_fetch( $path, $query, $provider, $config );
    if ( ! empty( $response['error'] ) ) {
        return [
            'data'   => [],
            'source' => tonbankcard_api_ai_sentiment_source( $name, 'unavailable', $refresh, $fetched_at, null, 0, (string) $response['error'] ),
        ];
    }

    $status = isset( $response['status'] ) ? (int) $response['status'] : 0;
    if ( 200 > $status || 300 <= $status ) {
        return [
            'data'   => [],
            'source' => tonbankcard_api_ai_sentiment_source( $name, 'unavailable', $refresh, $fetched_at, null, 0, 'provider_http_' . $status ),
        ];
    }

    $data = json_decode( isset( $response['body'] ) ? (string) $response['body'] : '', TRUE );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
        return [
            'data'   => [],
            'source' => tonbankcard_api_ai_sentiment_source( $name, 'unavailable', $refresh, $fetched_at, null, 0, 'provider_invalid_json' ),
        ];
    }

    $last_updated = tonbankcard_api_market_data_timestamp( $data );
    if ( null === $last_updated ) {
        $last_updated = $fetched_at;
    }
    $age = max( 0, $fetched_at - $last_updated );
    $is_stale = $refresh > 0 && $age > ( $refresh * 3 );
    $confidence = tonbankcard_api_ai_sentiment_freshness_confidence( $age, $refresh, $is_stale );

    return [
        'data'   => $data,
        'source' => tonbankcard_api_ai_sentiment_source( $name, $is_stale ? 'stale' : 'available', $refresh, $fetched_at, $last_updated, $confidence, null ),
    ];
}

/**
 * Builds a manual source state.
 *
 * @param string $name
 * @param string $status
 * @param array $settings
 * @param int $fetched_at
 * @param float $confidence
 * @return array
 */
function tonbankcard_api_ai_sentiment_manual_source( string $name, string $status, array $settings, int $fetched_at, float $confidence ) {
    return tonbankcard_api_ai_sentiment_source(
        $name,
        $status,
        tonbankcard_api_ai_sentiment_refresh_interval( $name, $settings ),
        $fetched_at,
        $fetched_at,
        $confidence,
        'missing' === $status ? 'source_not_provided' : null
    );
}

/**
 * Builds normalized source metadata.
 *
 * @param string $name
 * @param string $status
 * @param int $refresh
 * @param int $fetched_at
 * @param int|null $last_updated
 * @param float $confidence
 * @param string|null $warning
 * @return array
 */
function tonbankcard_api_ai_sentiment_source( string $name, string $status, int $refresh, int $fetched_at, $last_updated, float $confidence, $warning ) {
    $source = [
        'name'                     => $name,
        'status'                   => $status,
        'refresh_interval_seconds' => $refresh,
        'fetched_at'               => gmdate( 'c', $fetched_at ),
        'last_updated_at'          => null === $last_updated ? null : gmdate( 'c', (int) $last_updated ),
        'age_seconds'              => null === $last_updated ? null : max( 0, $fetched_at - (int) $last_updated ),
        'confidence'               => tonbankcard_api_ai_sentiment_round( $confidence ),
    ];
    if ( null !== $warning ) {
        $source['warning'] = $warning;
    }

    return $source;
}

/**
 * Returns a configured source refresh interval.
 *
 * @param string $name
 * @param array $settings
 * @return int
 */
function tonbankcard_api_ai_sentiment_refresh_interval( string $name, array $settings ) {
    $intervals = isset( $settings['source_refresh_intervals'] ) && is_array( $settings['source_refresh_intervals'] ) ? $settings['source_refresh_intervals'] : [];
    return isset( $intervals[ $name ] ) ? max( 1, (int) $intervals[ $name ] ) : 300;
}

/**
 * Converts source freshness into confidence.
 *
 * @param int $age
 * @param int $refresh
 * @param bool $stale
 * @return float
 */
function tonbankcard_api_ai_sentiment_freshness_confidence( int $age, int $refresh, bool $stale ) {
    if ( $refresh <= 0 ) {
        return $stale ? 0.25 : 0.8;
    }

    $confidence = 1 - min( 1, $age / max( 1, $refresh * 6 ) );
    if ( $stale ) {
        $confidence = min( $confidence, 0.35 );
    }

    return max( 0.15, $confidence );
}

/**
 * Builds the market movement signal.
 *
 * @param array $coins
 * @param array $source
 * @param array $coin_ids
 * @return array|null
 */
function tonbankcard_api_ai_sentiment_market_movement_signal( array $coins, array $source, array $coin_ids ) {
    if ( 'available' !== $source['status'] && 'stale' !== $source['status'] ) {
        return null;
    }

    $items = tonbankcard_api_ai_sentiment_coin_map( $coins );
    $coin_scores = [];
    foreach ( $coin_ids as $coin_id ) {
        if ( empty( $items[ $coin_id ] ) ) {
            continue;
        }
        $coin = $items[ $coin_id ];
        $score = tonbankcard_api_ai_sentiment_movement_score( $coin );
        $coin_scores[] = [
            'coin_id'      => $coin_id,
            'symbol'       => isset( $coin['symbol'] ) ? strtoupper( (string) $coin['symbol'] ) : '',
            'score'        => tonbankcard_api_ai_sentiment_round( $score ),
            'change_1h'    => tonbankcard_api_ai_sentiment_number( $coin, [ 'price_change_percentage_1h_in_currency', 'price_change_percentage_1h' ] ),
            'change_24h'   => tonbankcard_api_ai_sentiment_number( $coin, [ 'price_change_percentage_24h_in_currency', 'price_change_percentage_24h' ] ),
            'change_7d'    => tonbankcard_api_ai_sentiment_number( $coin, [ 'price_change_percentage_7d_in_currency', 'price_change_percentage_7d' ] ),
        ];
    }

    if ( empty( $coin_scores ) ) {
        return null;
    }

    return tonbankcard_api_ai_sentiment_signal(
        'market_movement',
        tonbankcard_api_ai_sentiment_average_column( $coin_scores, 'score' ),
        $source['confidence'],
        'Price movement across configured market assets.',
        [ 'coins' => $coin_scores ]
    );
}

/**
 * Builds the volume spike signal.
 *
 * @param array $coins
 * @param array $source
 * @param array $coin_ids
 * @return array|null
 */
function tonbankcard_api_ai_sentiment_volume_signal( array $coins, array $source, array $coin_ids ) {
    if ( 'available' !== $source['status'] && 'stale' !== $source['status'] ) {
        return null;
    }

    $items = tonbankcard_api_ai_sentiment_coin_map( $coins );
    $coin_scores = [];
    foreach ( $coin_ids as $coin_id ) {
        if ( empty( $items[ $coin_id ] ) ) {
            continue;
        }
        $coin = $items[ $coin_id ];
        $market_cap = tonbankcard_api_ai_sentiment_number( $coin, [ 'market_cap' ] );
        $volume = tonbankcard_api_ai_sentiment_number( $coin, [ 'total_volume' ] );
        $change = tonbankcard_api_ai_sentiment_number( $coin, [ 'price_change_percentage_24h_in_currency', 'price_change_percentage_24h' ] );
        if ( null === $market_cap || $market_cap <= 0 || null === $volume ) {
            continue;
        }
        $ratio = $volume / $market_cap;
        $magnitude = min( 1, $ratio / 0.20 );
        $direction = null !== $change && $change < 0 ? -1 : 1;
        $coin_scores[] = [
            'coin_id'              => $coin_id,
            'symbol'               => isset( $coin['symbol'] ) ? strtoupper( (string) $coin['symbol'] ) : '',
            'volume_to_market_cap' => tonbankcard_api_ai_sentiment_round( $ratio ),
            'score'                => tonbankcard_api_ai_sentiment_round( $magnitude * $direction ),
        ];
    }

    if ( empty( $coin_scores ) ) {
        return null;
    }

    return tonbankcard_api_ai_sentiment_signal(
        'volume_spike',
        tonbankcard_api_ai_sentiment_average_column( $coin_scores, 'score' ),
        min( 0.88, (float) $source['confidence'] ),
        'Volume pressure approximated from volume-to-market-cap ratios.',
        [ 'coins' => $coin_scores ]
    );
}

/**
 * Builds the global market signal.
 *
 * @param array $global
 * @param array $source
 * @return array|null
 */
function tonbankcard_api_ai_sentiment_global_signal( array $global, array $source ) {
    if ( 'available' !== $source['status'] && 'stale' !== $source['status'] ) {
        return null;
    }

    $data = isset( $global['data'] ) && is_array( $global['data'] ) ? $global['data'] : $global;
    $change = tonbankcard_api_ai_sentiment_number( $data, [ 'market_cap_change_percentage_24h_usd' ] );
    if ( null === $change ) {
        return null;
    }

    return tonbankcard_api_ai_sentiment_signal(
        'global_market',
        tonbankcard_api_ai_sentiment_clamp( $change / 12, -1, 1 ),
        min( 0.75, (float) $source['confidence'] ),
        'Global crypto market capitalization change.',
        [ 'market_cap_change_percentage_24h_usd' => tonbankcard_api_ai_sentiment_round( $change ) ]
    );
}

/**
 * Builds the trend ranking signal.
 *
 * @param array $trending
 * @param array $source
 * @param array $coin_ids
 * @return array|null
 */
function tonbankcard_api_ai_sentiment_trend_signal( array $trending, array $source, array $coin_ids ) {
    if ( 'available' !== $source['status'] && 'stale' !== $source['status'] ) {
        return null;
    }

    $ranks = [];
    if ( isset( $trending['coins'] ) && is_array( $trending['coins'] ) ) {
        foreach ( $trending['coins'] as $index => $entry ) {
            $item = isset( $entry['item'] ) && is_array( $entry['item'] ) ? $entry['item'] : $entry;
            if ( is_array( $item ) && ! empty( $item['id'] ) ) {
                $ranks[ strtolower( (string) $item['id'] ) ] = $index + 1;
            }
        }
    }

    $matches = [];
    foreach ( $coin_ids as $coin_id ) {
        if ( ! isset( $ranks[ $coin_id ] ) ) {
            continue;
        }
        $rank = (int) $ranks[ $coin_id ];
        $matches[] = [
            'coin_id' => $coin_id,
            'rank'    => $rank,
            'score'   => tonbankcard_api_ai_sentiment_round( max( 0.15, 1 - ( ( $rank - 1 ) * 0.12 ) ) ),
        ];
    }

    if ( empty( $matches ) ) {
        return tonbankcard_api_ai_sentiment_signal(
            'trend_ranking',
            0,
            min( 0.45, (float) $source['confidence'] ),
            'Configured assets are absent from current trending data.',
            [ 'matches' => [] ]
        );
    }

    return tonbankcard_api_ai_sentiment_signal(
        'trend_ranking',
        tonbankcard_api_ai_sentiment_average_column( $matches, 'score' ),
        min( 0.82, (float) $source['confidence'] ),
        'Configured assets appear in current trend rankings.',
        [ 'matches' => $matches ]
    );
}

/**
 * Builds the watchlist concentration signal.
 *
 * @param array $coin_ids
 * @param array $watchlist_coin_ids
 * @param array $source
 * @return array|null
 */
function tonbankcard_api_ai_sentiment_watchlist_signal( array $coin_ids, array $watchlist_coin_ids, array $source ) {
    if ( 'available' !== $source['status'] || empty( $watchlist_coin_ids ) ) {
        return null;
    }

    $matches = array_values( array_intersect( $coin_ids, $watchlist_coin_ids ) );
    $concentration = count( $coin_ids ) > 0 ? count( $matches ) / count( $coin_ids ) : 0;

    return tonbankcard_api_ai_sentiment_signal(
        'watchlist_concentration',
        tonbankcard_api_ai_sentiment_clamp( $concentration, 0, 1 ),
        (float) $source['confidence'],
        'Configured assets overlap with the provided watchlist snapshot.',
        [
            'matched_coin_ids' => $matches,
            'watched_count'    => count( $watchlist_coin_ids ),
            'concentration'    => tonbankcard_api_ai_sentiment_round( $concentration ),
        ]
    );
}

/**
 * Builds the curated TON ecosystem signal.
 *
 * @param array $coin_ids
 * @param array $settings
 * @param array $source
 * @return array|null
 */
function tonbankcard_api_ai_sentiment_curated_ton_signal( array $coin_ids, array $settings, array $source ) {
    if ( 'available' !== $source['status'] ) {
        return null;
    }

    $curated = [];
    foreach ( isset( $settings['curated_ton_assets'] ) && is_array( $settings['curated_ton_assets'] ) ? $settings['curated_ton_assets'] : [] as $asset ) {
        if ( ! is_array( $asset ) || empty( $asset['coin_id'] ) ) {
            continue;
        }
        $coin_id = strtolower( (string) $asset['coin_id'] );
        if ( in_array( $coin_id, $coin_ids, TRUE ) ) {
            $curated[] = [
                'coin_id' => $coin_id,
                'symbol'  => isset( $asset['symbol'] ) ? strtoupper( (string) $asset['symbol'] ) : '',
                'label'   => isset( $asset['label'] ) ? tonbankcard_api_ai_clean_text( (string) $asset['label'], 120 ) : 'Curated TON ecosystem asset',
                'tags'    => isset( $asset['tags'] ) && is_array( $asset['tags'] ) ? array_values( array_slice( $asset['tags'], 0, 8 ) ) : [],
            ];
        }
    }

    if ( empty( $curated ) ) {
        return tonbankcard_api_ai_sentiment_signal(
            'curated_ton_ecosystem',
            0,
            0.35,
            'Configured assets do not match curated TON ecosystem entries.',
            [ 'matched_assets' => [] ]
        );
    }

    return tonbankcard_api_ai_sentiment_signal(
        'curated_ton_ecosystem',
        min( 1, count( $curated ) / max( 1, count( $coin_ids ) ) ),
        (float) $source['confidence'],
        'Configured assets match curated TON ecosystem entries.',
        [ 'matched_assets' => $curated ]
    );
}

/**
 * Builds a normalized signal.
 *
 * @param string $kind
 * @param float $score
 * @param float $confidence
 * @param string $description
 * @param array $details
 * @return array
 */
function tonbankcard_api_ai_sentiment_signal( string $kind, float $score, float $confidence, string $description, array $details ) {
    $score = tonbankcard_api_ai_sentiment_clamp( $score, -1, 1 );

    return [
        'kind'        => $kind,
        'score'       => tonbankcard_api_ai_sentiment_round( $score ),
        'sentiment'   => tonbankcard_api_ai_sentiment_label( $score, FALSE ),
        'confidence'  => tonbankcard_api_ai_sentiment_round( tonbankcard_api_ai_sentiment_clamp( $confidence, 0, 1 ) ),
        'description' => $description,
        'details'     => $details,
    ];
}

/**
 * Aggregates signals into overall scores.
 *
 * @param array $signals
 * @param array $sources
 * @param array $settings
 * @return array
 */
function tonbankcard_api_ai_sentiment_aggregate_scores( array $signals, array $sources, array $settings ) {
    $weights = isset( $settings['weights'] ) && is_array( $settings['weights'] ) ? $settings['weights'] : [];
    $weighted_score = 0.0;
    $weighted_confidence = 0.0;
    $weight_total = 0.0;
    $positive = FALSE;
    $negative = FALSE;

    foreach ( $signals as $signal ) {
        $kind = isset( $signal['kind'] ) ? (string) $signal['kind'] : '';
        $weight = isset( $weights[ $kind ] ) ? max( 0, (float) $weights[ $kind ] ) : 0.1;
        $confidence = isset( $signal['confidence'] ) ? tonbankcard_api_ai_sentiment_clamp( (float) $signal['confidence'], 0, 1 ) : 0;
        $score = isset( $signal['score'] ) ? tonbankcard_api_ai_sentiment_clamp( (float) $signal['score'], -1, 1 ) : 0;
        $effective_weight = $weight * max( 0.05, $confidence );
        $weighted_score += $score * $effective_weight;
        $weighted_confidence += $confidence * $weight;
        $weight_total += $weight;
        if ( $score >= 0.25 ) {
            $positive = TRUE;
        }
        if ( $score <= -0.25 ) {
            $negative = TRUE;
        }
    }

    $available_sources = 0;
    foreach ( $sources as $source ) {
        if ( isset( $source['status'] ) && 'available' === $source['status'] ) {
            $available_sources++;
        }
    }
    $source_completeness = count( $sources ) > 0 ? $available_sources / count( $sources ) : 0;
    $overall_score = $weight_total > 0 ? $weighted_score / $weight_total : 0;
    $confidence = $weight_total > 0 ? $weighted_confidence / $weight_total : 0;
    $confidence = ( $confidence * 0.75 ) + ( $source_completeness * 0.25 );

    return [
        'overall' => [
            'score'      => tonbankcard_api_ai_sentiment_round( $overall_score ),
            'sentiment'  => tonbankcard_api_ai_sentiment_label( $overall_score, $positive && $negative ),
            'confidence' => tonbankcard_api_ai_sentiment_round( tonbankcard_api_ai_sentiment_clamp( $confidence, 0, 1 ) ),
        ],
        'source_completeness' => tonbankcard_api_ai_sentiment_round( $source_completeness ),
        'signal_count'        => count( $signals ),
    ];
}

/**
 * Returns a response status from source states.
 *
 * @param array $sources
 * @return string
 */
function tonbankcard_api_ai_sentiment_status( array $sources ) {
    foreach ( $sources as $source ) {
        if ( ! isset( $source['status'] ) || 'available' !== $source['status'] ) {
            return 'partial';
        }
    }

    return 'ready';
}

/**
 * Returns max source age for AI context citation.
 *
 * @param array $sources
 * @return int|null
 */
function tonbankcard_api_ai_sentiment_max_age( array $sources ) {
    $max = null;
    foreach ( $sources as $source ) {
        if ( isset( $source['age_seconds'] ) && null !== $source['age_seconds'] ) {
            $age = (int) $source['age_seconds'];
            if ( null === $max || $age > $max ) {
                $max = $age;
            }
        }
    }

    return $max;
}

/**
 * Builds watchlist context metadata.
 *
 * @param array $coin_ids
 * @param array $watchlist_coin_ids
 * @return array
 */
function tonbankcard_api_ai_sentiment_watchlist_context( array $coin_ids, array $watchlist_coin_ids ) {
    $matches = array_values( array_intersect( $coin_ids, $watchlist_coin_ids ) );

    return [
        'provided'        => ! empty( $watchlist_coin_ids ),
        'coin_count'      => count( $watchlist_coin_ids ),
        'matched_coin_ids' => $matches,
        'concentration'   => count( $coin_ids ) > 0 ? tonbankcard_api_ai_sentiment_round( count( $matches ) / count( $coin_ids ) ) : 0,
    ];
}

/**
 * Builds response metadata.
 *
 * @param array $data
 * @param array $settings
 * @param array $provider
 * @param array $cache_context
 * @param string $cache_status
 * @return array
 */
function tonbankcard_api_ai_sentiment_meta( array $data, array $settings, array $provider, array $cache_context, string $cache_status ) {
    return [
        'sentiment' => [
            'pipeline_version'         => isset( $settings['pipeline_version'] ) ? $settings['pipeline_version'] : 'v1',
            'source_refresh_intervals' => isset( $settings['source_refresh_intervals'] ) ? $settings['source_refresh_intervals'] : [],
            'prompt_version'           => isset( $provider['prompt_version'] ) ? $provider['prompt_version'] : 'v1',
            'market_data_hash'         => isset( $data['trace']['market_data_hash'] ) ? $data['trace']['market_data_hash'] : null,
        ],
        'cache'     => [
            'cache_status'      => $cache_status,
            'cache_key_hash'    => isset( $cache_context['key_hash'] ) ? $cache_context['key_hash'] : null,
            'cache_ttl_seconds' => isset( $cache_context['ttl_seconds'] ) ? (int) $cache_context['ttl_seconds'] : 0,
        ],
    ];
}

/**
 * Maps raw coin arrays by id.
 *
 * @param array $coins
 * @return array
 */
function tonbankcard_api_ai_sentiment_coin_map( array $coins ) {
    $map = [];
    foreach ( $coins as $coin ) {
        if ( is_array( $coin ) && ! empty( $coin['id'] ) ) {
            $map[ strtolower( (string) $coin['id'] ) ] = $coin;
        }
    }

    return $map;
}

/**
 * Scores price movement.
 *
 * @param array $coin
 * @return float
 */
function tonbankcard_api_ai_sentiment_movement_score( array $coin ) {
    $change_1h = tonbankcard_api_ai_sentiment_number( $coin, [ 'price_change_percentage_1h_in_currency', 'price_change_percentage_1h' ] );
    $change_24h = tonbankcard_api_ai_sentiment_number( $coin, [ 'price_change_percentage_24h_in_currency', 'price_change_percentage_24h' ] );
    $change_7d = tonbankcard_api_ai_sentiment_number( $coin, [ 'price_change_percentage_7d_in_currency', 'price_change_percentage_7d' ] );

    $score = 0.0;
    $weight = 0.0;
    if ( null !== $change_1h ) {
        $score += tonbankcard_api_ai_sentiment_clamp( $change_1h / 5, -1, 1 ) * 0.20;
        $weight += 0.20;
    }
    if ( null !== $change_24h ) {
        $score += tonbankcard_api_ai_sentiment_clamp( $change_24h / 12, -1, 1 ) * 0.55;
        $weight += 0.55;
    }
    if ( null !== $change_7d ) {
        $score += tonbankcard_api_ai_sentiment_clamp( $change_7d / 30, -1, 1 ) * 0.25;
        $weight += 0.25;
    }

    return $weight > 0 ? $score / $weight : 0;
}

/**
 * Returns the first numeric field found.
 *
 * @param array $data
 * @param array $keys
 * @return float|null
 */
function tonbankcard_api_ai_sentiment_number( array $data, array $keys ) {
    foreach ( $keys as $key ) {
        if ( isset( $data[ $key ] ) && is_numeric( $data[ $key ] ) ) {
            return (float) $data[ $key ];
        }
    }

    return null;
}

/**
 * Returns an average over a list column.
 *
 * @param array $items
 * @param string $key
 * @return float
 */
function tonbankcard_api_ai_sentiment_average_column( array $items, string $key ) {
    $values = [];
    foreach ( $items as $item ) {
        if ( isset( $item[ $key ] ) && is_numeric( $item[ $key ] ) ) {
            $values[] = (float) $item[ $key ];
        }
    }
    if ( empty( $values ) ) {
        return 0;
    }

    return array_sum( $values ) / count( $values );
}

/**
 * Returns a sentiment label.
 *
 * @param float $score
 * @param bool $conflicting
 * @return string
 */
function tonbankcard_api_ai_sentiment_label( float $score, bool $conflicting ) {
    if ( $conflicting && abs( $score ) < 0.35 ) {
        return 'mixed';
    }
    if ( $score > 0.18 ) {
        return 'bullish';
    }
    if ( $score < -0.18 ) {
        return 'bearish';
    }

    return $conflicting ? 'mixed' : 'neutral';
}

/**
 * Returns a stable hash for traceability.
 *
 * @param mixed $data
 * @return string
 */
function tonbankcard_api_ai_sentiment_hash( $data ) {
    $encoded = json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE );
    if ( FALSE === $encoded ) {
        $encoded = serialize( $data );
    }

    return hash( 'sha256', $encoded );
}

/**
 * Clamps a number.
 *
 * @param float $value
 * @param float $min
 * @param float $max
 * @return float
 */
function tonbankcard_api_ai_sentiment_clamp( float $value, float $min, float $max ) {
    return min( $max, max( $min, $value ) );
}

/**
 * Rounds public score values.
 *
 * @param float $value
 * @return float
 */
function tonbankcard_api_ai_sentiment_round( float $value ) {
    return round( $value, 4 );
}
