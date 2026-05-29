<?php
/**
 * -------------------------------------------------------------------------
 * GECKO CLIENT
 * -------------------------------------------------------------------------
 * Loads translation dictionaries for every language shipped in this directory.
 *
 * Each `<code>.php` file (except this index) is a dictionary keyed by the
 * canonical English source string. The dictionaries are discovered
 * dynamically rather than listed here, so dropping in a new `<code>.php`
 * file automatically registers the language across the whole app: the
 * language switcher, request-time language resolution, and the SEO hreflang
 * signals in the page head and the sitemap (see #167).
 *
 * English (`en`) is always loaded first so it remains the canonical fallback
 * dictionary relied on by `config/translation.php` and `__()`.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$translations = [];

// Load English first so it is the canonical fallback dictionary even if the
// directory scan below would otherwise order it differently.
$en_file = __DIR__ . '/en.php';
if ( is_file( $en_file ) ) {
    $en = require $en_file;
    if ( is_array( $en ) ) {
        $translations['en'] = $en;
    }
}

$files = glob( __DIR__ . '/*.php' );
if ( ! is_array( $files ) ) {
    $files = [];
}
sort( $files );

foreach ( $files as $file ) {
    $code = strtolower( basename( $file, '.php' ) );

    if ( 'index' === $code || 'en' === $code ) {
        continue;
    }

    // Only accept ISO 639-style codes (optionally with a region subtag) so a
    // stray helper file in this directory cannot masquerade as a language.
    if ( ! preg_match( '/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/', $code ) ) {
        continue;
    }

    $dictionary = require $file;
    if ( is_array( $dictionary ) ) {
        $translations[ $code ] = $dictionary;
    }
}

return $translations;
