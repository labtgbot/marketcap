<?php
/**
 * -------------------------------------------------------------------------
 * GECKO CLIENT
 * -------------------------------------------------------------------------
 * @package     Gecko Client
 * @author      RunCoders
 * @license     Envato Market Regular License (https://1.envato.market/regular-license)
 * @copyright   Copyright (c) 2021 RunCoders (https://runcoders.net)
 * @since	    1.0.0
 */

/*
| -------------------------------------------------------------------------
| CONSTANTS
| -------------------------------------------------------------------------
*/
require_once __DIR__ . '/constants.php';

/*
| -------------------------------------------------------------------------
| VENDOR (VERSIONS)
| -------------------------------------------------------------------------
*/
require_once __DIR__ . '/vendor.php';

/*
| -------------------------------------------------------------------------
| SHOW ERRORS
| -------------------------------------------------------------------------
*/
if ( GECKO_CLIENT_DISPLAY_ERRORS ) {
    error_reporting( -1 );
    ini_set( 'display_errors', 1 );
}

/*
| -------------------------------------------------------------------------
| CONFIGURATION
| -------------------------------------------------------------------------
*/

/**
 * @var array $site
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/site.php';
/**
 * @var array $vuetify
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/vuetify.php';
/**
 * @var array $navigation
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/navigation.php';
/**
 * @var array $footer
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/footer.php';
/**
 * @var array $translation
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/translation.php';
/**
 * @var array $coingecko
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/coingecko.php';
/**
 * @var array $routes
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/routes.php';
/**
 * @var array $formats
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/formats.php';
/**
 * @var array $links
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/links.php';

/*
| -------------------------------------------------------------------------
| FUNCTIONS
| -------------------------------------------------------------------------
*/
require_once __DIR__ . '/functions.php';

/*
| -------------------------------------------------------------------------
| VALIDATE CONFIGURATION
| -------------------------------------------------------------------------
*/
$invalid_configs = validate_constants();
$invalid_configs += validate_site_configs();
$invalid_configs += validate_vuetify_configs();
// if any config invalid show "Configuration Errors" view
if ( ! empty( $invalid_configs ) ) {
    header( 'cache-control: no-cache', TRUE, 500 );
    include GECKO_CLIENT_VIEWS_DIR . '/configuration-errors.php';
    die;
}

/*
| -------------------------------------------------------------------------
| GLOBAL VARIABLES
| -------------------------------------------------------------------------
*/
$enabled_routes   = get_enabled_routes();
$frontend_options = [];

/*
| -------------------------------------------------------------------------
| COMPONENTS LIST
| -------------------------------------------------------------------------
*/
$components = [
    'cookies-dialog',
    'currency-chart',
    'currency-converter',
    'disclaimer-message',
    'exchange-chart',
    'page-loader',
    'search-bar',
    'stats-bar',
    'trending-coins',
];

/*
| -------------------------------------------------------------------------
| APP VIEW
| -------------------------------------------------------------------------
*/

require_once GECKO_CLIENT_VIEWS_DIR . '/app.php';