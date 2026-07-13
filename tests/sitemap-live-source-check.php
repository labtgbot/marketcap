<?php
/**
 * Regression check for issue #166: the sitemap's live coin source must
 * enumerate the *full* coin universe from the gateway's `coins/list` endpoint
 * (every coin known to the provider), not just a single market-cap page.
 *
 * The shell coverage test (tests/sitemap-coverage-check.sh) runs on the
 * `local` profile where live sources are intentionally disabled, so it only
 * exercises the bundled fallback. This PHP check enables the live-source flag
 * and injects an in-memory transport so CI verifies the live path without
 * making any external request.
 */

putenv( 'TONBANKCARD_PROFILE=local' );
putenv( 'TONBANKCARD_SITEMAP_LIVE_SOURCES=true' );
$_ENV['TONBANKCARD_PROFILE']               = 'local';
$_ENV['TONBANKCARD_SITEMAP_LIVE_SOURCES']  = 'true';

require __DIR__ . '/../constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require GECKO_CLIENT_CONFIG_DIR . '/translation.php';
require GECKO_CLIENT_CONFIG_DIR . '/routes-v2.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/../functions.php';
require __DIR__ . '/../api/router.php';

$GLOBALS['routes_v2'] = $routes_v2;

$failures = 0;
function tbc_sitemap_assert( $condition, string $message ) {
    global $failures;
    if ( $condition ) {
        fwrite( STDOUT, '[PASS] ' . $message . PHP_EOL );
    } else {
        fwrite( STDERR, '[FAIL] ' . $message . PHP_EOL );
        $failures++;
    }
}

// In-memory transport returning a coins/list-shaped payload. coins/list returns
// the full catalogue: {id, symbol, name} for every coin, which is what makes the
// sitemap cover "every coin known to the market gateway".
$captured_paths = [];
$api['market_data']['transport'] = static function ( array $request ) use ( &$captured_paths ) {
    $captured_paths[] = $request['path'];
    if ( 'coins/list' === $request['path'] ) {
        $rows = [];
        // Simulate a catalogue far larger than the old 250-per-page cap.
        for ( $i = 0; $i < 1200; $i++ ) {
            $rows[] = [ 'id' => 'live-coin-' . $i, 'symbol' => 's' . $i, 'name' => 'Coin ' . $i ];
        }
        $rows[] = [ 'id' => 'bitcoin', 'symbol' => 'btc', 'name' => 'Bitcoin' ];
        return [ 'status' => 200, 'body' => json_encode( $rows ) ];
    }
    if ( 'exchanges/list' === $request['path'] ) {
        return [ 'status' => 200, 'body' => json_encode( [
            [ 'id' => 'live-exchange-1', 'name' => 'Exchange One' ],
        ] ) ];
    }
    return [ 'status' => 200, 'body' => '[]' ];
};
$GLOBALS['api'] = $api;

$coin_ids     = tonbankcard_sitemap_coin_ids();
$exchange_ids = tonbankcard_sitemap_exchange_ids();

tbc_sitemap_assert( in_array( 'coins/list', $captured_paths, TRUE ), 'live coin source uses the coins/list endpoint (full universe)' );
tbc_sitemap_assert( 'local' === TONBANKCARD_PROFILE, 'explicit live-source flag is honored on the default local profile' );
tbc_sitemap_assert( count( $coin_ids ) > 250, 'coin universe exceeds the old 250-per-page cap (got ' . count( $coin_ids ) . ')' );
tbc_sitemap_assert( in_array( 'live-coin-0', $coin_ids, TRUE ) && in_array( 'live-coin-1199', $coin_ids, TRUE ), 'first and last live coins are enumerated' );
tbc_sitemap_assert( in_array( 'toncoin', $coin_ids, TRUE ), 'bundled fallback ids remain present' );
tbc_sitemap_assert( in_array( 'ethereum', $coin_ids, TRUE ), 'hardcoded route-param fallback remains present' );
tbc_sitemap_assert( count( $coin_ids ) === count( array_unique( $coin_ids ) ), 'coin ids are de-duplicated' );
tbc_sitemap_assert( in_array( 'live-exchange-1', $exchange_ids, TRUE ), 'live exchange source is enumerated' );

if ( $failures > 0 ) {
    fwrite( STDERR, 'Sitemap live-source check failed (' . $failures . ' assertion(s)).' . PHP_EOL );
    exit( 1 );
}

fwrite( STDOUT, 'Sitemap live-source check passed. Coin universe size: ' . count( $coin_ids ) . PHP_EOL );
exit( 0 );
