<?php
/**
 * Local PHP development router.
 *
 * Lets the built-in PHP server serve existing files directly while sending
 * Vue history-mode routes back through the app bootstrap.
 */

$root = realpath( __DIR__ . '/../..' );
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

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
    return FALSE;
}

require $root . '/index.php';
