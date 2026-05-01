<?php
/**
 * TONBANKCARD V2 public website shell defaults.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$v2 = [];

$v2['manifest'] = 'site.webmanifest';

$v2['public_navigation'] = [
    [
        'text'  => 'Markets',
        'route' => 'markets',
    ],
    [
        'text'  => 'TON',
        'route' => 'ton',
    ],
    [
        'text'  => 'Wallet',
        'route' => 'wallet-profile',
    ],
    [
        'text'  => 'Exchanges',
        'route' => 'exchanges',
    ],
    [
        'text'  => 'Screener',
        'route' => 'screener',
    ],
    [
        'text'  => 'About',
        'route' => 'about',
    ],
    [
        'text'  => 'Support',
        'route' => 'support',
    ],
];

$v2['mobile_navigation'] = [
    [
        'text'  => 'Home',
        'icon'  => 'mdi-home',
        'route' => 'currencies',
    ],
    [
        'text'  => 'Markets',
        'icon'  => 'mdi-chart-line',
        'route' => 'markets',
    ],
    [
        'text'  => 'TON',
        'icon'  => 'mdi-diamond-stone',
        'route' => 'ton',
    ],
    [
        'text'  => 'Wallet',
        'icon'  => 'mdi-wallet-outline',
        'route' => 'wallet-profile',
    ],
    [
        'text'  => 'Support',
        'icon'  => 'mdi-lifebuoy',
        'route' => 'support',
    ],
];
