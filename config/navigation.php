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
        'text' => 'Market Pulse',
        'icon' => 'mdi-pulse',
        'route' => 'currencies',
    ],
    [
        'text' => 'Markets',
        'icon' => 'mdi-table-large',
        'route' => 'markets',
    ],
    [
        'text' => 'TON Ecosystem',
        'icon' => 'mdi-diamond-stone',
        'route' => 'ton',
    ],
    [
        'text' => 'Watchlist',
        'icon' => 'mdi-star-outline',
        'route' => 'watchlist',
    ],
    [
        'text' => 'Exchanges',
        'icon' => 'mdi-swap-vertical-bold',
        'route' => 'exchanges',
    ],
    [
        'text' => 'Screener',
        'icon' => 'mdi-table-search',
        'route' => 'screener',
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
                'text' => 'Support',
                'route' => 'support',
            ],
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
                'text' => 'Chat',
                'icon' => 'mdi-telegram',
                'url' => 'https://t.me/tonbankcard_chat',
                'external' => TRUE,
            ],
            [
                'text' => 'Chat RU',
                'icon' => 'mdi-telegram',
                'url' => 'https://t.me/tonbankcard_chat_ru',
                'external' => TRUE,
            ],
            [
                'text' => 'X',
                'icon' => 'mdi-twitter',
                'url' => 'https://twitter.com/tonbankcard',
                'external' => TRUE,
            ],
            [
                'text' => 'VK',
                'icon' => 'mdi-vk',
                'url' => 'https://vk.com/tonbankcard',
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
