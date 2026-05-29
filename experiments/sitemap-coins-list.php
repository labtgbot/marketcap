<?php
/**
 * Experiment: verify tonbankcard_sitemap_coin_ids() enumerates the full coin
 * universe from the gateway's `coins/list` endpoint (not just a 250-coin page).
 *
 * Runs with the live-source flag enabled and a mock transport that returns a
 * coins/list-shaped payload, then asserts the live ids land in the sitemap on
 * top of the bundled fallback.
 *
 * Usage: php experiments/sitemap-coins-list.php
 */

putenv( 'TONBANKCARD_PROFILE=production' );
putenv( 'TONBANKCARD_SITEMAP_LIVE_SOURCES=true' );
$_ENV['TONBANKCARD_PROFILE'] = 'production';
$_ENV['TONBANKCARD_SITEMAP_LIVE_SOURCES'] = 'true';

require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../vendor.php';
require_once __DIR__ . '/../functions.php';

require_once GECKO_CLIENT_CONFIG_DIR . '/site.php';
require_once GECKO_CLIENT_CONFIG_DIR . '/translation.php';
require_once GECKO_CLIENT_CONFIG_DIR . '/routes-v2.php';
require_once GECKO_CLIENT_CONFIG_DIR . '/api.php';
require_once __DIR__ . '/../api/router.php';

$GLOBALS['routes_v2'] = $routes_v2;

// Mock transport: every supported coins/list path returns three live-only ids.
$captured_paths = [];
$api['market_data']['transport'] = static function ( array $request ) use ( &$captured_paths ) {
    $captured_paths[] = $request['path'];
    if ( 'coins/list' === $request['path'] ) {
        return [
            'status' => 200,
            'body'   => json_encode( [
                [ 'id' => 'live-only-alpha', 'symbol' => 'loa', 'name' => 'Alpha' ],
                [ 'id' => 'live-only-beta', 'symbol' => 'lob', 'name' => 'Beta' ],
                [ 'id' => 'bitcoin', 'symbol' => 'btc', 'name' => 'Bitcoin' ],
            ] ),
        ];
    }
    return [ 'status' => 200, 'body' => '[]' ];
};
$GLOBALS['api'] = $api;

$ids = tonbankcard_sitemap_coin_ids();

$ok = TRUE;
function check( $label, $cond ) {
    global $ok;
    printf( "[%s] %s\n", $cond ? 'PASS' : 'FAIL', $label );
    if ( ! $cond ) { $ok = FALSE; }
}

check( 'coins/list path was requested', in_array( 'coins/list', $captured_paths, TRUE ) );
check( 'live-only-alpha present', in_array( 'live-only-alpha', $ids, TRUE ) );
check( 'live-only-beta present', in_array( 'live-only-beta', $ids, TRUE ) );
check( 'bundled fallback still present (toncoin)', in_array( 'toncoin', $ids, TRUE ) );
check( 'route-param fallback still present (ethereum)', in_array( 'ethereum', $ids, TRUE ) );
check( 'ids are de-duplicated', count( $ids ) === count( array_unique( $ids ) ) );

printf( "Total coin ids: %d\n", count( $ids ) );
exit( $ok ? 0 : 1 );
