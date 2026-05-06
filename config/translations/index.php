<?php
/**
 * -------------------------------------------------------------------------
 * GECKO CLIENT
 * -------------------------------------------------------------------------
 * Loads translation dictionaries for all supported languages.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

return [
    'en' => require __DIR__ . '/en.php',
    'ru' => require __DIR__ . '/ru.php',
    'fr' => require __DIR__ . '/fr.php',
    'ar' => require __DIR__ . '/ar.php',
    'zh' => require __DIR__ . '/zh.php',
];
