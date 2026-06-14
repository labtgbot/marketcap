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

function tonbankcard_dev_path_is_denied( $path ) {
    $decoded_path = rawurldecode( '/' . ltrim( (string) $path, '/' ) );
    $relative_path = ltrim( $decoded_path, '/' );
    $normalized_path = strtolower( $relative_path );

    if ( 0 !== stripos( $decoded_path, '/.well-known/' ) && preg_match( '#(^|/)\.#', $decoded_path ) ) {
        return TRUE;
    }

    if ( 'dev/js/tools/bundle.php' === $normalized_path ) {
        return FALSE;
    }

    if ( preg_match( '#^(install|database|docs|tests|experiments|dev)(/|$)#i', $relative_path ) ) {
        return TRUE;
    }

    return 1 === preg_match( '#\.(zip|sql|md)$#i', $relative_path );
}

if ( tonbankcard_dev_path_is_denied( $path ) ) {
    http_response_code( 403 );
    header( 'Content-Type: text/plain; charset=utf-8' );
    header( 'X-Content-Type-Options: nosniff' );
    echo 'Forbidden';
    return TRUE;
}

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
    && is_dir( $file )
    && is_file( $file . '/index.php' )
) {
    return FALSE;
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
