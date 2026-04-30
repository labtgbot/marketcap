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

/**
 * @var array $site
 */

/*
| -------------------------------------------------------------------------
| FORMATS
| -------------------------------------------------------------------------
|
| This script uses ECMAScript Internationalization API (Intl) to format numbers,
| percentages, dates and other bits of information.
|
| -------------------------------------------------------------------------
| REFERENCE
| -------------------------------------------------------------------------
|
| Intl Docs: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl
|
| For the moment the formatters being used are:
|   Intl.DateTimeFormat   https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl/DateTimeFormat/DateTimeFormat
|   Intl.NumberFormat     https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl/NumberFormat/NumberFormat
|
| -------------------------------------------------------------------------
| EXPLANATION OF FORMAT PARAMETERS
| -------------------------------------------------------------------------
|
|   ['locale']   (string)  The language code.
|                            REFERENCE: https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/lang
|   ['options']  (array)   The formatter specific options (detailed in reference links above)
|
*/


/*
| -------------------------------------------------------------------------
| DATE
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.DateTimeFormat
|
| TARGETS:
| - Historical data in currency page
| - Genesis date in currency info
| - Expire date of derivatives
| - Start and end dates of finance products
|
*/
$formats['date'] = [
    'locale' => $site['lang'],
    'options' => [
        'year' => 'numeric',
        'month' => 'short',
        'day' => 'numeric',
    ],
];

/*
| -------------------------------------------------------------------------
| PRICE
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Current, 24h low and 24h high prices of cryptocurrencies
| - Historical data in currency page
| - Current and index prices of derivatives
|
*/
$formats['price'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'currency',
        'currencyDisplay' => 'narrowSymbol',
        'minimumSignificantDigits' => 4,
        'maximumSignificantDigits' => 6,
        'maximumFractionDigits' => 8,
    ],
];

/*
| -------------------------------------------------------------------------
| MARKET CAP
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Market caps and fully diluted valuation of cryptocurrencies
| - Historical data in currency page
|
*/
$formats['marketCap'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'currency',
        'currencyDisplay' => 'narrowSymbol',
        'maximumFractionDigits' => 0,
        'minimumFractionDigits' => 0,
    ],
];

/*
| -------------------------------------------------------------------------
| VOLUME
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Trading volume of cryptocurrencies
| - Historical data in currency page
| - Trading volume of exchanges
| - Trading volume and open interest of derivatives
|
*/
$formats['volume'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'currency',
        'currencyDisplay' => 'narrowSymbol',
        'maximumFractionDigits' => 0,
        'minimumFractionDigits' => 0,
    ],
];

/*
| -------------------------------------------------------------------------
| BIG NUMBER
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Circulating and max supplies of cryptocurrencies
|
*/
$formats['bigNumber'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'decimal',
        'maximumFractionDigits' => 0,
    ],
];

/*
| -------------------------------------------------------------------------
| CHANGE
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Prices percent changes of cryptocurrencies
| - Price percent change of derivatives
|
*/
$formats['change'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'percent',
        'minimumFractionDigits' => 2,
    ],
];

/*
| -------------------------------------------------------------------------
| DOMINANCE
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Dominance percentage of top cryptocurrencies
|
*/
$formats['dominance'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'percent',
        'minimumFractionDigits' => 1,
    ],
];

/*
| -------------------------------------------------------------------------
| RATE
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Supply and borrow rates of finance products
| - Funding rate of derivatives
|
*/
$formats['rate'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'percent',
        'minimumFractionDigits' => 3,
    ],
];

/*
| -------------------------------------------------------------------------
| RATIO
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Volume/Market Cap. ratio of cryptocurrencies
|
*/
$formats['ratio'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'decimal',
        'maximumFractionDigits' => 3,
    ],
];

/*
| -------------------------------------------------------------------------
| SPREAD
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Bid/Ask spread percentage of tickers
| - Bid/Ask spread percentage of derivatives
|
*/
$formats['spread'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'percent',
        'minimumFractionDigits' => 3,
    ],
];

/*
| -------------------------------------------------------------------------
| BASIS
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Basis percentage of derivatives
|
*/
$formats['basis'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'percent',
        'signDisplay' => 'always',
        'minimumFractionDigits' => 3,
    ],
];

/*
| -------------------------------------------------------------------------
| SCORE
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Liquidity, developer and community scores in currency info
|
*/
$formats['score'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'decimal',
        'maximumFractionDigits' => 2,
    ],
];

/*
| -------------------------------------------------------------------------
| CHART Y AXIS VALUE
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Y axis labels in currency chart
|
*/
$formats['chartYAxisValue'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'currency',
        'notation' => 'compact',
        'currencyDisplay' => 'narrowSymbol',
        'compactDisplay' => 'long',
    ],
];

/*
| -------------------------------------------------------------------------
| CHART DATE HOUR MINUTE
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.DateTimeFormat
|
| TARGETS:
| - X axis labels in currency chart when range is 24h or less
|
*/
$formats['chartDateHourMinute'] = [
    'locale' => $site['lang'],
    'options' => [
        'hour' => '2-digit',
        'minute' => '2-digit',
    ],
];

/*
| -------------------------------------------------------------------------
| CHART DATE MONTH DAY
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.DateTimeFormat
|
| TARGETS:
| - X axis labels in currency chart when range is between 1 day and 30 days
|
*/
$formats['chartDateMonthDay'] = [
    'locale' => $site['lang'],
    'options' => [
        'month' => 'short',
        'day' => 'numeric',
    ],
];

/*
| -------------------------------------------------------------------------
| CHART DATE YEAR MONTH DAY
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.DateTimeFormat
|
| TARGETS:
| - X axis labels in currency chart when range is more than 30 days
|
*/
$formats['chartDateYearMonthDay'] = [
    'locale' => $site['lang'],
    'options' => [
        'year' => 'numeric',
        'month' => 'short',
        'day' => 'numeric',
    ],
];

/*
| -------------------------------------------------------------------------
| CHART TOOLTIP DATE
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.DateTimeFormat
|
| TARGETS:
| - Full date for tooltips in currency chart
|
*/
$formats['chartTooltipDate'] = [
    'locale' => $site['lang'],
    'options' => [
        'year' => 'numeric',
        'month' => 'short',
        'day' => 'numeric',
        'hour' => '2-digit',
        'minute' => '2-digit',
    ],
];

/*
| -------------------------------------------------------------------------
| CONVERTER
| -------------------------------------------------------------------------
|
| FORMATTER: Intl.NumberFormat
|
| TARGETS:
| - Currency converter component
|
*/
$formats['converter'] = [
    'locale' => $site['lang'],
    'options' => [
        'style' => 'decimal',
        'minimumSignificantDigits' => 4,
        'maximumSignificantDigits' => 6,
        'maximumFractionDigits' => 8,
        'useGrouping' => FALSE,
    ],
];