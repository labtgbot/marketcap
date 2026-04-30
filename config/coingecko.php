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
| CACHE
| -------------------------------------------------------------------------
| TYPE: string
|
| DESCRIPTION: Set TRUE to store data in current window and avoid repeated
| requests and decrease loading time.
*/
$coingecko['cache'] = TRUE;

/*
| -------------------------------------------------------------------------
| TIMEOUT
| -------------------------------------------------------------------------
| TYPE: int
| DESCRIPTION: Max waiting time in milliseconds before abort requests.
| DEFAULT: 30000
|
*/
$coingecko['timeout'] = 30000;

/*
| -------------------------------------------------------------------------
| DEFAULT VS CURRENCY
| -------------------------------------------------------------------------
| TYPE: string
|
| DESCRIPTION:
| The pre-selected currency for prices, market caps and volumes values.
| CoinGecko calls it "Vs Currency", so we also use that term.
| Visitors can change this currency and the browser will retain their preference.
| Preference will be saved in navigator "Local Storage" with "GeckoClient:vs_currency" key.
| Use id from currencies available in "SUPPORTED VS CURRENCIES" below.
|
*/
$coingecko['default_vs_currency'] = 'usd';

/*
| -------------------------------------------------------------------------
| SUPPORTED VS CURRENCIES
| -------------------------------------------------------------------------
|
| TYPE: array[]
|
| DESCRIPTION:
| List of currencies available for displaying prices.
| It is not requested on demand, so that you can customize and restrict the
| this list and save loading time.
| You can change the order to put more important currencies on top.
|
| Source: https://api.coingecko.com/api/v3/exchange_rates
|
|
| -------------------------------------------------------------------------
| EXPLANATION OF CURRENCY PARAMETERS
| -------------------------------------------------------------------------
|
|   ['id']       (string)   The id for the currency.
|   ['name']     (string)   The name for the currency.
|   ['unit']     (string)   The unit/symbol for the currency.
|   ['type']     (string)   Defines if the currency is 'crypto', 'fiat' or 'commodity'.
|   ['enabled']  (bool)     Set TRUE to make currency available.
|
*/
$coingecko['supported_vs_currencies'] = [
    [
        'id'      => 'usd',
        'name'    => 'US Dollar',
        'unit'    => '$',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'eur',
        'name'    => 'Euro',
        'unit'    => '€',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'gbp',
        'name'    => 'British Pound Sterling',
        'unit'    => '£',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'btc',
        'name'    => 'Bitcoin',
        'unit'    => 'BTC',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'eth',
        'name'    => 'Ether',
        'unit'    => 'ETH',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'aed',
        'name'    => 'United Arab Emirates Dirham',
        'unit'    => 'DH',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'ars',
        'name'    => 'Argentine Peso',
        'unit'    => '$',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'aud',
        'name'    => 'Australian Dollar',
        'unit'    => 'A$',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'bdt',
        'name'    => 'Bangladeshi Taka',
        'unit'    => '৳',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'bhd',
        'name'    => 'Bahraini Dinar',
        'unit'    => 'BD',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'bmd',
        'name'    => 'Bermudian Dollar',
        'unit'    => '$',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'brl',
        'name'    => 'Brazil Real',
        'unit'    => 'R$',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'cad',
        'name'    => 'Canadian Dollar',
        'unit'    => 'CA$',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'chf',
        'name'    => 'Swiss Franc',
        'unit'    => 'Fr.',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'clp',
        'name'    => 'Chilean Peso',
        'unit'    => 'CLP$',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'cny',
        'name'    => 'Chinese Yuan',
        'unit'    => '¥',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'czk',
        'name'    => 'Czech Koruna',
        'unit'    => 'Kč',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'dkk',
        'name'    => 'Danish Krone',
        'unit'    => 'kr.',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'hkd',
        'name'    => 'Hong Kong Dollar',
        'unit'    => 'HK$',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'huf',
        'name'    => 'Hungarian Forint',
        'unit'    => 'Ft',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'idr',
        'name'    => 'Indonesian Rupiah',
        'unit'    => 'Rp',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'ils',
        'name'    => 'Israeli New Shekel',
        'unit'    => '₪',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'inr',
        'name'    => 'Indian Rupee',
        'unit'    => '₹',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'jpy',
        'name'    => 'Japanese Yen',
        'unit'    => '¥',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'krw',
        'name'    => 'South Korean Won',
        'unit'    => '₩',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'kwd',
        'name'    => 'Kuwaiti Dinar',
        'unit'    => 'KD',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'lkr',
        'name'    => 'Sri Lankan Rupee',
        'unit'    => 'Rs',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'mmk',
        'name'    => 'Burmese Kyat',
        'unit'    => 'K',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'mxn',
        'name'    => 'Mexican Peso',
        'unit'    => 'MX$',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'myr',
        'name'    => 'Malaysian Ringgit',
        'unit'    => 'RM',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'ngn',
        'name'    => 'Nigerian Naira',
        'unit'    => '₦',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'nok',
        'name'    => 'Norwegian Krone',
        'unit'    => 'kr',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'nzd',
        'name'    => 'New Zealand Dollar',
        'unit'    => 'NZ$',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'php',
        'name'    => 'Philippine Peso',
        'unit'    => '₱',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'pkr',
        'name'    => 'Pakistani Rupee',
        'unit'    => '₨',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'pln',
        'name'    => 'Polish Zloty',
        'unit'    => 'zł',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'rub',
        'name'    => 'Russian Ruble',
        'unit'    => '₽',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'sar',
        'name'    => 'Saudi Riyal',
        'unit'    => 'SR',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'sek',
        'name'    => 'Swedish Krona',
        'unit'    => 'kr',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'sgd',
        'name'    => 'Singapore Dollar',
        'unit'    => 'S$',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'thb',
        'name'    => 'Thai Baht',
        'unit'    => '฿',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'try',
        'name'    => 'Turkish Lira',
        'unit'    => '₺',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'twd',
        'name'    => 'New Taiwan Dollar',
        'unit'    => 'NT$',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'uah',
        'name'    => 'Ukrainian hryvnia',
        'unit'    => '₴',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'vef',
        'name'    => 'Venezuelan bolívar fuerte',
        'unit'    => 'Bs.F',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'vnd',
        'name'    => 'Vietnamese đồng',
        'unit'    => '₫',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'zar',
        'name'    => 'South African Rand',
        'unit'    => 'R',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'xdr',
        'name'    => 'IMF Special Drawing Rights',
        'unit'    => 'XDR',
        'type'    => 'fiat',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'bits',
        'name'    => 'Bits',
        'unit'    => 'μBTC',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'sats',
        'name'    => 'Satoshi',
        'unit'    => 'sats',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'ltc',
        'name'    => 'Litecoin',
        'unit'    => 'LTC',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'bch',
        'name'    => 'Bitcoin Cash',
        'unit'    => 'BCH',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'bnb',
        'name'    => 'Binance Coin',
        'unit'    => 'BNB',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'eos',
        'name'    => 'EOS',
        'unit'    => 'EOS',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'xrp',
        'name'    => 'XRP',
        'unit'    => 'XRP',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'xlm',
        'name'    => 'Lumens',
        'unit'    => 'XLM',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'link',
        'name'    => 'Chainlink',
        'unit'    => 'LINK',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'dot',
        'name'    => 'Polkadot',
        'unit'    => 'DOT',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'yfi',
        'name'    => 'Yearn.finance',
        'unit'    => 'YFI',
        'type'    => 'crypto',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'xag',
        'name'    => 'Silver',
        'unit'    => 'XAG',
        'type'    => 'commodity',
        'enabled' => TRUE,
    ],
    [
        'id'      => 'xau',
        'name'    => 'Gold',
        'unit'    => 'XAU',
        'type'    => 'commodity',
        'enabled' => TRUE,
    ],
];
