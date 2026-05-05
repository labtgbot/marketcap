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

$routes['markets'] = [
    'path' => '/markets',
    'enabled' => TRUE,
];

$routes['currency'] = [
    'path' => '/currency/:id',
    // always enabled
];

$routes['coins'] = [
    'path'     => '/coins/:id',
    'enabled'  => TRUE,
    'template' => 'currency',
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

$routes['ton'] = [
    'path' => '/ton',
    'enabled' => TRUE,
];

$routes['ton-asset'] = [
    'path' => '/ton/asset/:id',
    'enabled' => TRUE,
];

$routes['crypto-exchange'] = [
    'path' => '/crypto-exchange',
    'enabled' => TRUE,
];

$routes['watchlist'] = [
    'path' => '/watchlist',
    'enabled' => TRUE,
];

$routes['alerts'] = [
    'path' => '/alerts',
    'enabled' => TRUE,
];

$routes['achievements'] = [
    'path' => '/achievements',
    'enabled' => TRUE,
];

$routes['premium'] = [
    'path' => '/premium',
    'enabled' => TRUE,
];

$routes['app-alerts'] = [
    'path' => '/app/alerts',
    'enabled' => TRUE,
    'template' => 'alerts',
];

$routes['app-alert-detail'] = [
    'path' => '/app/alert/:id',
    'enabled' => TRUE,
    'template' => 'alerts',
];

$routes['app-premium'] = [
    'path' => '/app/premium',
    'enabled' => TRUE,
    'template' => 'premium',
];

$routes['wallet-profile'] = [
    'path' => '/wallet',
    'enabled' => TRUE,
];

$routes['settings'] = [
    'path' => '/settings',
    'enabled' => TRUE,
    'template' => 'wallet-profile',
];

$routes['screener'] = [
    'path' => '/screener',
    'enabled' => TRUE,
];

$routes['admin'] = [
    'path' => '/admin',
    'enabled' => TRUE,
];

$routes['admin-providers'] = [
    'path' => '/admin/providers',
    'enabled' => TRUE,
    'template' => 'admin',
];

$routes['admin-feature-flags'] = [
    'path' => '/admin/feature-flags',
    'enabled' => TRUE,
    'template' => 'admin',
];

$routes['admin-ton-assets'] = [
    'path' => '/admin/ton-assets',
    'enabled' => TRUE,
    'template' => 'admin',
];

$routes['admin-legal-copy'] = [
    'path' => '/admin/legal-copy',
    'enabled' => TRUE,
    'template' => 'admin',
];

$routes['admin-alerts'] = [
    'path' => '/admin/alerts',
    'enabled' => TRUE,
    'template' => 'admin',
];

$routes['admin-cache'] = [
    'path' => '/admin/cache',
    'enabled' => TRUE,
    'template' => 'admin',
];

$routes['admin-audit-log'] = [
    'path' => '/admin/audit-log',
    'enabled' => TRUE,
    'template' => 'admin',
];

$routes['about'] = [
    'path' => '/about',
    'enabled' => TRUE,
];

$routes['support'] = [
    'path' => '/support',
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
