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
| RUNTIME CONFIGURATION
| -------------------------------------------------------------------------
*/
require_once GECKO_CLIENT_CONFIG_DIR . '/runtime.php';

tonbankcard_load_env_file( GECKO_CLIENT_DIR . '/.env' );

$GLOBALS['runtime_config'] = tonbankcard_runtime_config();

define( 'TONBANKCARD_PROFILE', $GLOBALS['runtime_config']['profile'] );

/*
| -------------------------------------------------------------------------
| ERRORS
| -------------------------------------------------------------------------
*/
define( 'GECKO_CLIENT_DISPLAY_ERRORS', (bool) $GLOBALS['runtime_config']['debug'] );

/*
| -------------------------------------------------------------------------
| ENVIRONMENT
| -------------------------------------------------------------------------
| TONBANKCARD_PROFILE:
|   'local'          Local development defaults
|   'staging'        Public staging deployment
|   'production'     Production public website
|   'telegram'       Telegram Mini App deployment
|
| GECKO_CLIENT_ENV is kept for upstream Gecko Client compatibility:
|   'development'    Enables dev tools for local profile
|   'production'     Used by staging, production, and telegram profiles
| -------------------------------------------------------------------------
*/
define( 'GECKO_CLIENT_ENV', $GLOBALS['runtime_config']['gecko_env'] );

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
define( 'GECKO_CLIENT_APP_MINIFIED', (bool) $GLOBALS['runtime_config']['assets']['app_minified'] );

define( 'GECKO_CLIENT_PRECONNECT', (bool) $GLOBALS['runtime_config']['assets']['preconnect'] );

define( 'GECKO_CLIENT_CDN', (bool) $GLOBALS['runtime_config']['assets']['cdn'] );

/*
| -------------------------------------------------------------------------
| SITEMAP
| -------------------------------------------------------------------------
| TONBANKCARD_SITEMAP_MAX_URLS:
|   Maximum number of URLs in a single section sitemap before it is split
|   across paginated files (sitemap-coins-1.xml, …). Capped at the
|   sitemaps.org hard limit of 50,000 URLs per file.
|
| TONBANKCARD_SITEMAP_CACHE_TTL:
|   Fresh TTL (seconds) for cached sitemap documents in the shared Upstash
|   Redis store. 0 disables sitemap caching (render on every request).
|
| TONBANKCARD_SITEMAP_CACHE_STALE_TTL:
|   Additional stale-while-revalidate window (seconds) after the fresh TTL
|   expires, so crawlers keep getting a document while it is regenerated.
| -------------------------------------------------------------------------
*/
define( 'TONBANKCARD_SITEMAP_MAX_URLS', tonbankcard_env_int( 'TONBANKCARD_SITEMAP_MAX_URLS', 50000, 1, 50000 ) );

define( 'TONBANKCARD_SITEMAP_CACHE_TTL', tonbankcard_env_int( 'TONBANKCARD_SITEMAP_CACHE_TTL', 3600, 0, 86400 ) );

define( 'TONBANKCARD_SITEMAP_CACHE_STALE_TTL', tonbankcard_env_int( 'TONBANKCARD_SITEMAP_CACHE_STALE_TTL', 86400, 0, 604800 ) );
