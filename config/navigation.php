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
| ITEMS
| -------------------------------------------------------------------------
|
| TYPE: array[]
| DESCRIPTION: Side navigation menu items details.
|
| -------------------------------------------------------------------------
| EXPLANATION OF ITEM PARAMETERS
| -------------------------------------------------------------------------
|
|   ['text']        (string)    Text label
|   ['icon']        (string)    Icon class name (mdi-*). Icons: https://materialdesignicons.com/
|   ['route']       (string)    Route name.
|   ['url']         (string)    Link URL (when not a route)
|   ['external']    (bool)      Set TRUE to open URL in new window (if URL is provided).
|   ['divider']     (string)    Use 'before' or 'after' to add a divider.
|   ['items']       (array[])   Submenu items. Can set text, icon, route/url and external params.
|
*/
$navigation['items'] = [
    [
        'text' => 'Cryptocurrencies',
        'icon' => 'mdi-cash-lock',
        'route' => 'currencies',
    ],
    [
        'text' => 'Exchanges',
        'icon' => 'mdi-swap-vertical-bold',
        'route' => 'exchanges',
    ],
    [
        'text' => 'Derivatives',
        'icon' => 'mdi-progress-clock',
        'route' => 'derivatives',
    ],
    [
        'text' => 'Finance',
        'icon' => 'mdi-currency-usd-off',
        'items' => [
            [
                'text' => 'Platforms',
                'route' => 'finance-platforms',
            ],
            [
                'text' => 'Products',
                'route' => 'finance-products',
            ],
        ],
    ],
    [
        'text' => 'TONBANKCARD',
        'icon' => 'mdi-web',
        'divider' => 'before',
        'items' => [
            [
                'text' => 'Website',
                'icon' => 'mdi-web',
                'url' => 'https://tonbankcard.com/',
                'external' => TRUE,
            ],
            [
                'text' => 'Telegram',
                'icon' => 'mdi-telegram',
                'url' => 'https://t.me/tonbankcard',
                'external' => TRUE,
            ],
            [
                'text' => 'Telegram RU',
                'icon' => 'mdi-telegram',
                'url' => 'https://t.me/tonbankcard_ru',
                'external' => TRUE,
            ],
            [
                'text' => 'Support Bot',
                'icon' => 'mdi-robot',
                'url' => 'https://t.me/tonbankcard_bot',
                'external' => TRUE,
            ],
            [
                'text' => 'YouTube',
                'icon' => 'mdi-youtube',
                'url' => 'https://www.youtube.com/@tonbankcard',
                'external' => TRUE,
            ],
        ],
    ],
];
