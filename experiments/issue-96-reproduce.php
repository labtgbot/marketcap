<?php
/**
 * Reproduces the production 502 Bad Gateway reported in issue #96.
 *
 * Symptom (live):
 *   GET https://market-cap.tonbankcard.com/api/market/global -> 502
 *   {"error":{"code":"provider_unavailable",
 *             "details":{"provider_plan":"pro","upstream_status":400}}}
 *
 * Root cause:
 *   The market gateway has a heuristic that infers Pro-plan routing from a
 *   `CG-` API key prefix. CoinGecko issues `CG-`-prefixed keys for BOTH the
 *   Demo (Public) plan and the Pro (paid) plan, so the heuristic misroutes
 *   Demo keys to https://pro-api.coingecko.com, which rejects them with 400.
 *
 * This script demonstrates the bug by exercising the gateway with a Demo
 * key while COINGECKO_API_PLAN is explicitly set to "demo". It expects the
 * fixed code to honour the explicit "demo" plan and route the request to
 * the public Demo API endpoint.
 */

if ( PHP_SAPI !== 'cli' ) {
    exit( 1 );
}

chdir( dirname( __DIR__ ) );

putenv( 'COINGECKO_API_PLAN=demo' );
putenv( 'COINGECKO_API_KEY=CG-demo-key-from-coingecko-portal' );
$_ENV['COINGECKO_API_PLAN'] = 'demo';
$_ENV['COINGECKO_API_KEY']  = 'CG-demo-key-from-coingecko-portal';

require __DIR__ . '/../constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/../api/router.php';

$captured = [];

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) use ( &$captured ) {
    $captured = $request;

    return [
        'status'  => 200,
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => '{"data":{"active_cryptocurrencies":12000}}',
    ];
};

tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/market/global',
        'headers' => [],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);

printf( "Plan sent to provider: %s\n", $captured['plan'] );
printf( "URL sent to provider:  %s\n", $captured['url'] );
printf( "Headers: %s\n", json_encode( $captured['headers'] ) );

$expected_url = 'https://api.coingecko.com/api/v3/global';
$expected_header = 'x-cg-demo-api-key';

$ok = TRUE;
if ( 'demo' !== $captured['plan'] ) {
    fwrite( STDERR, "FAIL: expected plan=demo, got plan=" . $captured['plan'] . "\n" );
    $ok = FALSE;
}
if ( $expected_url !== $captured['url'] ) {
    fwrite( STDERR, "FAIL: expected URL=" . $expected_url . ", got URL=" . $captured['url'] . "\n" );
    $ok = FALSE;
}
if ( ! isset( $captured['headers'][ $expected_header ] ) ) {
    fwrite( STDERR, "FAIL: expected header " . $expected_header . " to be set\n" );
    $ok = FALSE;
}
if ( isset( $captured['headers']['x-cg-pro-api-key'] ) ) {
    fwrite( STDERR, "FAIL: x-cg-pro-api-key must NOT be sent for demo plan\n" );
    $ok = FALSE;
}

exit( $ok ? 0 : 1 );
