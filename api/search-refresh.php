<?php
/**
 * CLI entrypoint for scheduled smart search index refresh jobs.
 */

if ( 'cli' !== PHP_SAPI ) {
    http_response_code( 404 );
    exit;
}

$root = dirname( __DIR__ );

require_once $root . '/constants.php';
require_once GECKO_CLIENT_CONFIG_DIR . '/api.php';
require_once $root . '/functions.php';
require_once __DIR__ . '/router.php';

$state = tonbankcard_api_search_refresh_index(
    isset( $GLOBALS['runtime_config'] ) ? $GLOBALS['runtime_config'] : [],
    isset( $api ) ? $api : []
);

echo tonbankcard_api_encode(
    [
        'ok'   => TRUE,
        'data' => tonbankcard_api_search_index_payload( $state ),
        'meta' => array_merge(
            [ 'request_id' => 'cli-search-refresh' ],
            tonbankcard_api_search_meta( $state, '', 0 )
        ),
    ]
) . PHP_EOL;

exit( empty( $state['ok'] ) ? 1 : 0 );
