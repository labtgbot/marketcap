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
| CURRENCIES
| -------------------------------------------------------------------------
| TYPE: array
| DESCRIPTION: Currencies custom URLs.
| KEY: Currency ID. For example: 'binancecoin' for Binance Coin ("/currency/binancecoin").
| VALUE: Custom URL.
|
| -------------------------------------------------------------------------
| EXAMPLE:
| -------------------------------------------------------------------------
|
|   $links['currencies'] = [
|       'bitcoin'  => 'https://www.binance.com/en/trade/BTC_USDT?ref=40765122',
|       'ethereum' => 'https://www.binance.com/en/trade/ETH_USDT?ref=40765122',
|   ];
|
*/
$links['currencies'] = [

];

/*
| -------------------------------------------------------------------------
| EXCHANGES
| -------------------------------------------------------------------------
| TYPE: array
| DESCRIPTION: Exchange custom URLs. Tickers will not be replaced.
| KEY: Exchange ID. For example: 'gdax' for Coinbase Exchange ("/exchange/gdax").
| VALUE: Custom URL.
|
| -------------------------------------------------------------------------
| EXAMPLE:
| -------------------------------------------------------------------------
|
|   $links['exchanges'] = [
|       'binance'  => 'https://www.binance.com/en/register?ref=40765122',
|       'bitfinex' => 'https://bitfinex.com/?refcode=27pDIjdk4',
|   ];
|
*/
$links['exchanges'] = [

];

/*
| -------------------------------------------------------------------------
| FINANCE PLATFORMS
| -------------------------------------------------------------------------
| TYPE: array
| DESCRIPTION: Finance platform custom URLs.
| KEY: Platform name. For example: 'Binance Savings'.
| VALUE: Custom URL.
|
| -------------------------------------------------------------------------
| EXAMPLE:
| -------------------------------------------------------------------------
|
|   $links['finance_platforms'] = [
|       'Binance Savings' => 'https://www.binance.com/en/savings?ref=40765122',
|       'Binance Staking' => 'https://www.binance.com/en/pos?ref=40765122',
|   ];
|
*/
$links['finance_platforms'] = [

];

/*
| -------------------------------------------------------------------------
| DERIVATIVES MARKETS
| -------------------------------------------------------------------------
| TYPE: array
|
| DESCRIPTION:
| Derivatives Market custom URLs.
| CoinGecko API does not provide URLs in /v3/derivatives endpoint response,
| so these with the only ones available.
|
| KEY: Market name. For example: 'Binance (Futures)'.
|
| VALUE: Custom URL.
|
| -------------------------------------------------------------------------
| EXAMPLE:
| -------------------------------------------------------------------------
|
|   $links['derivatives_markets'] = [
|       'Binance (Futures)' => 'https://www.binance.com/en/futures?ref=40765122',
|       'Huobi Futures' => 'https://futures.huobi.com/?invite_code=9t3n2223',
|   ];
|
*/
$links['derivatives_markets'] = [

];