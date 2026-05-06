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
 *
 * Multi-language translation dictionary.
 *
 * - Keys are the canonical English source strings used by `__()` in PHP and
 *   the Vue runtime.
 * - English values mirror their keys so missing entries fall back transparently.
 * - The active language is selected by `tonbankcard_active_language()` based on
 *   the `tbc_lang` cookie, the `lang` query parameter, the `Accept-Language`
 *   browser header, and the default `en`. Vue receives the resolved dictionary
 *   via `window.GeckoClient.translation`.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * @var array $translations Dictionary keyed by ISO language code.
 */
$translations = require __DIR__ . '/translations/index.php';

$active_language = function_exists( 'tonbankcard_active_language' )
    ? tonbankcard_active_language( $translations )
    : 'en';

if ( isset( $site ) && is_array( $site ) ) {
    $site['lang'] = $active_language;
    $site['rtl']  = function_exists( 'tonbankcard_language_is_rtl' )
        ? tonbankcard_language_is_rtl( $active_language )
        : ( 'ar' === $active_language );
}

$translation = isset( $translations[ $active_language ] )
    ? $translations[ $active_language ]
    : $translations['en'];

$GLOBALS['tonbankcard_translations']    = $translations;
$GLOBALS['tonbankcard_active_language'] = $active_language;
