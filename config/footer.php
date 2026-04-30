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
| SHOW
| -------------------------------------------------------------------------
|
| TYPE: bool
| DESCRIPTION: Set TRUE and footer will be displayed.
|
*/
$footer['show'] = TRUE;

/*
| -------------------------------------------------------------------------
| LINKS
| -------------------------------------------------------------------------
|
| TYPE: array[]
| DESCRIPTION: Footer links details.
|
| -------------------------------------------------------------------------
| EXPLANATION OF LINK PARAMETERS
| -------------------------------------------------------------------------
|
|   ['text']        (string)    Text label
|   ['icon']        (string)    Icon class name (mdi-*). Icons: https://materialdesignicons.com/
|   ['route']       (string)    Route name.
|   ['url']         (string)    Link URL (when not a route)
|   ['external']    (bool)      Set TRUE to open URL in new window (if URL is provided).
|
*/
$footer['links'] = [
    [
        'icon' => 'mdi-facebook',
        'url' => 'https://www.facebook.com/',
        'external' => TRUE,
    ],
    [
        'icon' => 'mdi-twitter',
        'url' => 'https://twitter.com/',
        'external' => TRUE,
    ],
    [
        'icon' => 'mdi-instagram',
        'url' => 'https://www.instagram.com/',
        'external' => TRUE,
    ],
    [
        'text' => 'About Us',
        'route' => 'about',
    ],
    [
        'text' => 'Terms',
        'route' => 'terms',
    ],
    [
        'text' => 'Privacy Policy',
        'route' => 'privacy-policy',
    ],
    [
        'text' => 'Cookies Policy',
        'route' => 'cookies-policy',
    ],
];

/*
| -------------------------------------------------------------------------
| COPYRIGHTS
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: The message display at the bottom of the footer.
|
*/
$footer['copyrights'] = '&copy; 2021 Gecko Client - All rights reserved';



