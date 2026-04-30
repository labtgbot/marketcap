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
| VERSION
| -------------------------------------------------------------------------
*/
define( 'GECKO_CLIENT_VERSION', '1.2.0' );

/*
| -------------------------------------------------------------------------
| PATHS
| -------------------------------------------------------------------------
*/
define( 'GECKO_CLIENT_DIR', __DIR__ );

define( 'GECKO_CLIENT_CONFIG_DIR', __DIR__ . '/config' );

define( 'GECKO_CLIENT_VIEWS_DIR', __DIR__ . '/views');

define( 'GECKO_CLIENT_TEMPLATES_DIR', __DIR__ . '/templates' );

define( 'GECKO_CLIENT_DEV_DIR', __DIR__ . '/dev' );

define( 'GECKO_CLIENT_ASSETS_DIR', __DIR__ . '/assets' );

/*
| -------------------------------------------------------------------------
| ERRORS
| -------------------------------------------------------------------------
*/
define( 'GECKO_CLIENT_DISPLAY_ERRORS', TRUE );

/*
| -------------------------------------------------------------------------
| ENVIRONMENT
| -------------------------------------------------------------------------
| GECKO_CLIENT_ENV:
|   'production'    For live websites
|   'development'   Enables dev tools for localhost
| -------------------------------------------------------------------------
*/
define( 'GECKO_CLIENT_ENV', 'production' );

/*
| -------------------------------------------------------------------------
| OPTIMIZATIONS
| -------------------------------------------------------------------------
| GECKO_CLIENT_APP_MINIFIED: TRUE -> Loads app javascript minified version
|
| GECKO_CLIENT_PRECONNECT: TRUE -> Adds preconnect meta tags
|
| GECKO_CLIENT_CDN: TRUE -> Uses jsDelivr CDN to load dependencies assets
| -------------------------------------------------------------------------
*/
define( 'GECKO_CLIENT_APP_MINIFIED', TRUE );

define( 'GECKO_CLIENT_PRECONNECT', TRUE );

define( 'GECKO_CLIENT_CDN', FALSE );