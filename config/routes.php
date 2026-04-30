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

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/*
| -------------------------------------------------------------------------
| ROUTES
| -------------------------------------------------------------------------
|
| TYPE: array[]
| DESCRIPTION: Routes details.
|
| -------------------------------------------------------------------------
| EXPLANATION OF ROUTE PARAMETERS
| -------------------------------------------------------------------------
|
|   ['path']        (string)    Relative URL path
|   ['enabled']     (bool)      Set TRUE to include route template and add it to Vue Router
|
*/

$routes['currencies'] = [
    'path' => '/',
    // always enabled
];

$routes['currency'] = [
    'path' => '/currency/:id',
    // always enabled
];

$routes['exchanges'] = [
    'path' => '/exchanges',
    // always enabled
];

$routes['exchange'] = [
    'path' => '/exchange/:id',
    // always enabled
];

$routes['finance-platforms'] = [
    'path' => '/finance-platforms',
    'enabled' => TRUE,
];

$routes['finance-products'] = [
    'path' => '/finance-products',
    'enabled' => TRUE,
];

$routes['derivatives'] = [
    'path' => '/derivatives',
    'enabled' => TRUE,
];

$routes['about'] = [
    'path' => '/about',
    'enabled' => TRUE,
];

$routes['terms'] = [
    'path' => '/terms',
    'enabled' => TRUE,
];

$routes['privacy-policy'] = [
    'path' => '/privacy-policy',
    'enabled' => TRUE,
];

$routes['cookies-policy'] = [
    'path' => '/cookies-policy',
    'enabled' => TRUE,
];