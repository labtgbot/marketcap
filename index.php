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
} else {
    ini_set( 'display_errors', 0 );
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
 * @var array $v2
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/v2.php';
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
 * @var array $routes_v2
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/routes-v2.php';
/**
 * @var array $formats
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/formats.php';
/**
 * @var array $links
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/links.php';
/**
 * @var array $api
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/api.php';
/**
 * @var array $performance
 */
require_once GECKO_CLIENT_CONFIG_DIR . '/performance.php';

/*
| -------------------------------------------------------------------------
| FUNCTIONS
| -------------------------------------------------------------------------
*/
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/api/router.php';

/*
| -------------------------------------------------------------------------
| LANGUAGE DETECTION AND OVERRIDE
| -------------------------------------------------------------------------
|
| Detect the active UI language from:
|   1. The "tbc_lang" cookie (set when user manually switches language)
|   2. The browser's Accept-Language header (auto-detect on first visit)
|   3. Default: English ('en')
|
| When a non-English language is active, merge its translations over the
| base English array so any missing keys fall back to English.
|
*/
if ( ! empty( $site['supported_languages'] ) && is_array( $site['supported_languages'] ) ) {
    $active_lang = tonbankcard_detect_language( $site['supported_languages'], 'en' );
    if ( 'en' !== $active_lang ) {
        $lang_file = GECKO_CLIENT_CONFIG_DIR . '/languages/' . $active_lang . '.php';
        if ( file_exists( $lang_file ) ) {
            $orig_translation = $translation;
            require $lang_file;
            $translation = array_merge( $orig_translation, $translation );
            unset( $orig_translation );
        }
    }
    $site['lang']        = $active_lang;
    $site['rtl']         = ! empty( $site['supported_languages'][ $active_lang ]['rtl'] );
    $site['active_lang'] = $active_lang;
} else {
    $site['active_lang'] = $site['lang'];
}

/*
| -------------------------------------------------------------------------
| VALIDATE CONFIGURATION
| -------------------------------------------------------------------------
*/
$invalid_configs = array_merge(
    validate_constants(),
    validate_runtime_config(),
    validate_site_configs(),
    validate_vuetify_configs()
);
// API requests return JSON errors instead of the HTML configuration screen.
if ( tonbankcard_api_is_request() ) {
    tonbankcard_api_dispatch( $invalid_configs );
}

tonbankcard_emit_security_headers( 'html' );

// if any config invalid show "Configuration Errors" view
if ( ! empty( $invalid_configs ) ) {
    header( 'cache-control: no-cache', TRUE, 500 );
    include GECKO_CLIENT_VIEWS_DIR . '/configuration-errors.php';
    die;
}

tonbankcard_dispatch_public_seo_assets();

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
    'ai-insight-card',
    'cookies-dialog',
    'currency-chart',
    'currency-converter',
    'currency-exchange-widget',
    'disclaimer-message',
    'exchange-chart',
    'page-loader',
    'search-bar',
    'share-card',
    'stats-bar',
    'trending-coins',
];

/*
| -------------------------------------------------------------------------
| APP VIEW
| -------------------------------------------------------------------------
*/

require_once GECKO_CLIENT_VIEWS_DIR . '/app.php';
