<?php
/**
 * Synthetic performance/load checks for issue #39.
 *
 * The harness keeps provider and storage dependencies in memory so CI measures
 * TONBANKCARD route logic, envelopes, fallback metadata, and helper hot paths
 * without calling external services.
 */

require __DIR__ . '/../constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require GECKO_CLIENT_CONFIG_DIR . '/performance.php';
require __DIR__ . '/../functions.php';
require __DIR__ . '/../api/router.php';

$root = dirname( __DIR__ );
$log_dir = $root . '/test-logs';
if ( ! is_dir( $log_dir ) ) {
    mkdir( $log_dir, 0777, TRUE );
}
$summary_path = $log_dir . '/performance-load-summary.json';
$log_path = $log_dir . '/performance-load-check.log';

function tbc_perf_fail( string $message ) {
    fwrite( STDERR, $message . PHP_EOL );
    exit( 1 );
}

function tbc_perf_assert( $condition, string $message ) {
    if ( ! $condition ) {
        tbc_perf_fail( $message );
    }
}

function tbc_perf_percentile( array $values, float $percentile ) {
    sort( $values, SORT_NUMERIC );
    $count = count( $values );
    if ( 0 === $count ) {
        return 0.0;
    }

    $index = (int) ceil( $percentile / 100 * $count ) - 1;
    $index = max( 0, min( $count - 1, $index ) );
    return $values[ $index ];
}

function tbc_perf_profile( array $performance, string $name ) {
    if ( empty( $performance['load_profiles'][ $name ] ) || ! is_array( $performance['load_profiles'][ $name ] ) ) {
        tbc_perf_fail( 'Missing performance load profile: ' . $name );
    }

    return $performance['load_profiles'][ $name ];
}

function tbc_perf_budget( array $performance, string $key ) {
    if ( ! isset( $performance['budgets'][ $key ] ) ) {
        tbc_perf_fail( 'Missing performance budget: ' . $key );
    }

    return (int) $performance['budgets'][ $key ];
}

function tbc_perf_run_case( string $name, array $profile, int $budget_ms, callable $callback ) {
    $iterations = max( 1, (int) ( isset( $profile['iterations'] ) ? $profile['iterations'] : 1 ) );
    $durations = [];

    for ( $i = 0; $i < $iterations; $i++ ) {
        $started = hrtime( TRUE );
        $callback( $i );
        $durations[] = ( hrtime( TRUE ) - $started ) / 1000000;
    }

    $p50 = tbc_perf_percentile( $durations, 50 );
    $p95 = tbc_perf_percentile( $durations, 95 );
    $max = max( $durations );

    return [
        'name'        => $name,
        'iterations'  => $iterations,
        'budget_ms'   => $budget_ms,
        'p50_ms'      => round( $p50, 3 ),
        'p95_ms'      => round( $p95, 3 ),
        'max_ms'      => round( $max, 3 ),
        'passed'      => $p95 <= $budget_ms,
    ];
}

function tbc_perf_market_row( string $id, string $symbol, string $name, int $rank ) {
    $price = 1000 / max( 1, $rank );
    return [
        'id'                                            => $id,
        'symbol'                                        => $symbol,
        'name'                                          => $name,
        'market_cap_rank'                               => $rank,
        'current_price'                                 => $price,
        'market_cap'                                    => $price * 1000000,
        'total_volume'                                  => $price * 250000,
        'price_change_percentage_24h_in_currency'       => 1.25,
        'price_change_percentage_7d_in_currency'        => 2.5,
        'price_change_percentage_30d_in_currency'       => -0.5,
        'sparkline_in_7d'                               => [ 'price' => [ $price - 1, $price, $price + 1 ] ],
        'last_updated'                                  => '2026-05-02T00:00:00.000Z',
    ];
}

function tbc_perf_coin_detail( string $id = 'bitcoin' ) {
    return [
        'id'         => $id,
        'symbol'     => 'btc',
        'name'       => 'Bitcoin',
        'categories' => [ 'Cryptocurrency' ],
        'image'      => [ 'large' => 'https://example.test/bitcoin.png' ],
        'links'      => [
            'homepage'          => [ 'https://bitcoin.org/' ],
            'blockchain_site'   => [],
            'announcement_url'  => [],
            'official_forum_url' => [],
            'chat_url'          => [],
            'subreddit_url'     => '',
            'twitter_screen_name' => '',
            'facebook_username' => '',
            'repos_url'         => [ 'github' => [], 'bitbucket' => [] ],
        ],
        'market_data' => [
            'current_price'                              => [ 'usd' => 64000 ],
            'price_change_percentage_24h_in_currency'    => [ 'usd' => 1.25 ],
            'high_24h'                                   => [ 'usd' => 65000 ],
            'low_24h'                                    => [ 'usd' => 63000 ],
            'market_cap'                                 => [ 'usd' => 1200000000000 ],
            'market_cap_change_24h_in_currency'          => [ 'usd' => 1000000 ],
            'market_cap_change_percentage_24h_in_currency' => [ 'usd' => 1.2 ],
            'fully_diluted_valuation'                    => [ 'usd' => 1300000000000 ],
            'total_volume'                               => [ 'usd' => 25000000000 ],
            'circulating_supply'                         => 19000000,
            'total_supply'                               => 21000000,
            'last_updated'                               => '2026-05-02T00:00:00.000Z',
        ],
        'last_updated' => '2026-05-02T00:00:00.000Z',
    ];
}

function tbc_perf_market_transport( array $request ) {
    $path = isset( $request['path'] ) ? (string) $request['path'] : '';

    if ( 'global' === $path ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode(
                [
                    'data' => [
                        'active_cryptocurrencies' => 12000,
                        'total_market_cap'        => [ 'usd' => 2500000000000 ],
                        'total_volume'            => [ 'usd' => 90000000000 ],
                        'market_cap_percentage'   => [ 'btc' => 52.5 ],
                    ],
                    'updated_at' => 1777680000,
                ],
                JSON_UNESCAPED_SLASHES
            ),
        ];
    }

    if ( 'coins/markets' === $path ) {
        $rows = [];
        foreach ( [
            [ 'bitcoin', 'btc', 'Bitcoin' ],
            [ 'ethereum', 'eth', 'Ethereum' ],
            [ 'toncoin', 'ton', 'Toncoin' ],
            [ 'tether', 'usdt', 'Tether' ],
            [ 'notcoin', 'not', 'Notcoin' ],
            [ 'dogs-2', 'dogs', 'Dogs' ],
            [ 'ston', 'ston', 'STON' ],
            [ 'dedust', 'scale', 'DeDust' ],
        ] as $index => $coin ) {
            $rows[] = tbc_perf_market_row( $coin[0], $coin[1], $coin[2], $index + 1 );
        }

        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode( $rows, JSON_UNESCAPED_SLASHES ),
        ];
    }

    if ( 'coins/bitcoin' === $path ) {
        return [
            'status'  => 200,
            'headers' => [ 'content-type' => 'application/json' ],
            'body'    => json_encode( tbc_perf_coin_detail(), JSON_UNESCAPED_SLASHES ),
        ];
    }

    return [
        'status'  => 200,
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => '[]',
    ];
}

function tbc_perf_api_request( string $path, array $config ) {
    $response = tonbankcard_api_handle(
        [
            'method'  => 'GET',
            'path'    => strtok( $path, '?' ),
            'query'   => [],
            'headers' => [ 'x-request-id' => 'perf-' . md5( $path ) ],
            'body'    => '',
        ],
        [],
        $GLOBALS['runtime_config'],
        $config
    );

    tbc_perf_assert( 200 === $response['status'], 'Expected ' . $path . ' to return 200, got ' . $response['status'] );
    $payload = json_decode( $response['body'], TRUE );
    tbc_perf_assert( is_array( $payload ) && ! empty( $payload['ok'] ), 'Expected successful API envelope for ' . $path );

    return $payload;
}

tbc_perf_assert( isset( $performance ) && is_array( $performance ), 'Missing performance configuration.' );

$test_api = $api;
$test_api['cache']['enabled'] = FALSE;
$test_api['rate_limit']['enabled'] = FALSE;
$test_api['market_data']['transport'] = 'tbc_perf_market_transport';

$results = [];
$results[] = tbc_perf_run_case(
    'market_pulse',
    tbc_perf_profile( $performance, 'market_pulse' ),
    tbc_perf_budget( $performance, 'market_api_p95_ms' ),
    function () use ( $test_api ) {
        tbc_perf_api_request( '/api/market/global', $test_api );
        tbc_perf_api_request( '/api/market/coins/markets', $test_api );
    }
);

$results[] = tbc_perf_run_case(
    'smart_search',
    tbc_perf_profile( $performance, 'smart_search' ),
    tbc_perf_budget( $performance, 'search_api_p95_ms' ),
    function () use ( $test_api ) {
        $response = tonbankcard_api_handle(
            [
                'method'  => 'GET',
                'path'    => '/api/search',
                'query'   => [ 'q' => 'ton', 'limit' => 8 ],
                'headers' => [ 'x-request-id' => 'perf-search' ],
                'body'    => '',
            ],
            [],
            $GLOBALS['runtime_config'],
            $test_api
        );
        tbc_perf_assert( 200 === $response['status'], 'Expected search API to return 200.' );
        $payload = json_decode( $response['body'], TRUE );
        tbc_perf_assert( ! empty( $payload['data']['results'] ), 'Expected search API results.' );
    }
);

$results[] = tbc_perf_run_case(
    'coin_detail',
    tbc_perf_profile( $performance, 'coin_detail' ),
    tbc_perf_budget( $performance, 'coin_detail_api_p95_ms' ),
    function () use ( $test_api ) {
        $payload = tbc_perf_api_request( '/api/market/coins/bitcoin', $test_api );
        tbc_perf_assert( 'bitcoin' === $payload['data']['id'], 'Expected coin detail payload.' );
    }
);

$alert_settings = tonbankcard_api_alerts_settings(
    [ 'feature_flags' => [ 'alerts' => TRUE ], 'bot_username' => 'MarketCapBot' ],
    [
        'alerts' => [
            'default_frequency_cap_seconds' => 900,
            'default_max_deliveries_per_day' => 8,
            'max_alerts_per_user' => 20,
        ],
    ]
);
$alert_rule = [
    'id'                     => 42,
    'coin_id'                => 'toncoin',
    'symbol'                 => 'TON',
    'trigger_type'           => 'price_cross',
    'operator'               => 'gte',
    'threshold_value'        => 5.00,
    'status'                 => 'active',
    'frequency_cap_seconds'  => 900,
    'max_deliveries_per_day' => 8,
    'quiet_start'            => null,
    'quiet_end'              => null,
    'timezone'               => 'UTC',
    'last_triggered_at'      => null,
];
$results[] = tbc_perf_run_case(
    'alert_evaluation',
    tbc_perf_profile( $performance, 'alert_evaluation' ),
    tbc_perf_budget( $performance, 'alert_delivery_p95_ms' ),
    function () use ( $alert_rule, $alert_settings ) {
        $event = tonbankcard_api_alerts_evaluate_rule( $alert_rule, [ 'current_price' => 5.25 ], $alert_settings, strtotime( '2026-05-02 00:00:00 UTC' ) );
        tbc_perf_assert( ! empty( $event['triggered'] ), 'Expected alert rule to trigger.' );
        $delivery = tonbankcard_api_alerts_delivery_payload( $alert_rule, $event, $alert_settings, FALSE );
        tbc_perf_assert( false !== strpos( $delivery['links']['telegram_deep_link'], 'startapp=alert_42' ), 'Expected alert delivery deep link.' );
    }
);

$results[] = tbc_perf_run_case(
    'share_card_generation',
    tbc_perf_profile( $performance, 'share_card_generation' ),
    tbc_perf_budget( $performance, 'share_card_generation_p95_ms' ),
    function () {
        $start_param = tonbankcard_api_share_build_start_param(
            [
                'route'    => '/currency/bitcoin',
                'campaign' => 'coin-price',
                'inviter'  => '1001',
                'context'  => 'coin_price',
            ]
        );
        $parsed = tonbankcard_api_share_parse_start_param( $start_param );
        tbc_perf_assert( ! empty( $parsed['ok'] ), 'Expected generated share-card payload to parse.' );
        tbc_perf_assert( '/currency/bitcoin' === $parsed['payload']['route'], 'Expected share route to round-trip.' );
    }
);

$failed = array_values(
    array_filter(
        $results,
        function ( $result ) {
            return empty( $result['passed'] );
        }
    )
);
$summary = [
    'generated_at' => gmdate( DATE_ATOM ),
    'issue'        => 39,
    'results'      => $results,
    'passed'       => empty( $failed ),
];

file_put_contents( $summary_path, json_encode( $summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL );
file_put_contents(
    $log_path,
    implode(
        PHP_EOL,
        array_map(
            function ( $result ) {
                return sprintf(
                    '%s p50=%sms p95=%sms max=%sms budget=%sms %s',
                    $result['name'],
                    $result['p50_ms'],
                    $result['p95_ms'],
                    $result['max_ms'],
                    $result['budget_ms'],
                    $result['passed'] ? 'PASS' : 'FAIL'
                );
            },
            $results
        )
    ) . PHP_EOL
);

if ( ! empty( $failed ) ) {
    foreach ( $failed as $result ) {
        fwrite( STDERR, sprintf( "%s exceeded p95 budget: %sms > %sms\n", $result['name'], $result['p95_ms'], $result['budget_ms'] ) );
    }
    exit( 1 );
}

printf( "Performance load check passed. Summary: %s\n", $summary_path );
