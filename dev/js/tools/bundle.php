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
| BUNDLE TOOL
| -------------------------------------------------------------------------
| Read Documentation > Development
|
| Notes:
| - To enabled this the GECKO_CLIENT_ENV in "constants.php" should be set to 'development'.
| - If any file in "dev/js/source.json" is missing it will send an alert.
| - Not recommended for live websites (load time will be affected).
|
*/

/*
| -------------------------------------------------------------------------
| CONSTANTS
| -------------------------------------------------------------------------
*/
require_once __DIR__ . '/../../../constants.php';

/*
| -------------------------------------------------------------------------
| HEADERS
| -------------------------------------------------------------------------
*/
header( 'content-type: application/javascript; charset=utf-8' );
header( 'cache-control: no-cache' );
header( 'pragma: no-cache' );


// Not in development environment
if ( GECKO_CLIENT_ENV !== 'development' ) {
    printf( 'alert("Not in development environment\n\nSet in constants.php:\ndefine( \'GECKO_CLIENT_ENV\', \'development\' );")' );
    exit;
}

$source_json_path = GECKO_CLIENT_DEV_DIR . '/js/source.json';
// source.json not found
if ( ! file_exists( $source_json_path ) ) {
    printf( 'alert("\'source.json\' not found...")' );
    exit;
}

/*
| -------------------------------------------------------------------------
| COLLECT AND BUNDLE
| -------------------------------------------------------------------------
*/

$src_files = json_decode( file_get_contents( $source_json_path ), true );
$output    = '';
$not_found = [];

foreach ( $src_files as $file ) {
    $file_path = GECKO_CLIENT_DEV_DIR . '/js/src/' . $file;
    if ( file_exists( $file_path ) ) {
        $output .= sprintf( "/*  BEGIN %s  */\n", $file );
        $output .= file_get_contents( $file_path );
        $output .= sprintf( "/*  END %s  */\n", $file );
    } else {
        $not_found[] = $file;
    }
}

/*
| -------------------------------------------------------------------------
| RESPONSE
| -------------------------------------------------------------------------
*/
if ( empty( $not_found ) ) {
    echo $output;
} else {
    printf( 'alert("%s:\n%s")', 'Missing Files', implode( "\n", $not_found ) );
}


