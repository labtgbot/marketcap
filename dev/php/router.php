<?php
/**
 * Local PHP development router.
 *
 * Lets the built-in PHP server serve existing files directly while sending
 * Vue history-mode routes back through the app bootstrap.
 */

$root = realpath( __DIR__ . '/../..' );
$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/';
$path = parse_url( $request_uri, PHP_URL_PATH );

if ( FALSE === $path ) {
    $path = '/';
}

$file = realpath( $root . $path );

// Keep /api/* requests on the PHP front controller so API JSON contracts,
// CORS handling, and request IDs are exercised during local development.
if ( '/api' === $path || 0 === strpos( $path, '/api/' ) ) {
    require $root . '/index.php';
    return TRUE;
}

if (
    '/' !== $path
    && FALSE !== $file
    && 0 === strpos( $file, $root . DIRECTORY_SEPARATOR )
    && is_file( $file )
) {
    require_once $root . '/constants.php';
    require_once $root . '/functions.php';
    require_once GECKO_CLIENT_CONFIG_DIR . '/performance.php';

    if ( tonbankcard_static_asset_is_cacheable( $path, $performance ) ) {
        tonbankcard_emit_static_asset_headers( $request_uri, $performance );
        header( 'Content-Type: ' . tonbankcard_static_asset_content_type( $path ) );
        header( 'Content-Length: ' . filesize( $file ) );
        readfile( $file );
        return TRUE;
    }

    return FALSE;
}

require $root . '/index.php';
