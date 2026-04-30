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
| SERIES
| -------------------------------------------------------------------------
| TYPE: array[]
| DESCRIPTION: Series details.
|
| -------------------------------------------------------------------------
| EXPLANATION OF SERIES PARAMETERS
| -------------------------------------------------------------------------
|
|   ['value']   (string)    Series id
|   ['text']    (string)    Series title
|
*/
$frontend_options['currency-chart']['series'] = [
    [
        'value' => 'price',
        'text'  => __( 'Price' ),
    ],
    [
        'value' => 'marketCap',
        'text'  => __( 'Market Cap' ),
    ],
];

/*
| -------------------------------------------------------------------------
| DEFAULT SERIES
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Selected series (id/value) by default.
|
*/
$frontend_options['currency-chart']['defaultSeries'] = 'price';

/*
| -------------------------------------------------------------------------
| INTERVALS
| -------------------------------------------------------------------------
| TYPE: array[]
| DESCRIPTION: Intervals details.
|
| -------------------------------------------------------------------------
| EXPLANATION OF INTERVAL PARAMETERS
| -------------------------------------------------------------------------
|
|   ['value']   (int|string)    Interval days or 'max'
|   ['text']    (string)        Interval title
|
*/
$frontend_options['currency-chart']['intervals'] = [
    [
        'value' => 1,
        'text'  => __( '1D' ),
    ],
    [
        'value' => 7,
        'text'  => __( '7D' ),
    ],
    [
        'value' => 30,
        'text'  => __( '1M' ),
    ],
    [
        'value' => 180,
        'text'  => __( '6M' ),
    ],
    [
        'value' => 360,
        'text'  => __( '1Y' ),
    ],
    [
        'value' => 'max',
        'text'  => __( 'All' ),
    ],
];

/*
| -------------------------------------------------------------------------
| DEFAULT INTERVAL
| -------------------------------------------------------------------------
| TYPE: int|string
| DESCRIPTION: Selected interval (value) by default.
|
*/
$frontend_options['currency-chart']['defaultInterval'] = 30;

/*
| -------------------------------------------------------------------------
| ECHART OPTIONS
| -------------------------------------------------------------------------
| TYPE: array
| DESCRIPTION: ECharts instance options.
| REFERENCE: https://echarts.apache.org/en/option.html
|
*/
$frontend_options['currency-chart']['echartOptions'] = [
    'animation' => FALSE,
    'backgroundColor' => 'rgba(0,0,0,0)',
    'tooltip' => [
        'trigger' => 'axis',
        'axisPointer' => [ 'type' => 'cross' ],
        'backgroundColor' => 'rgba(255,255,255,0.9)',
        'confine' => TRUE,
    ],
    'axisPointer' => [
        'link' => [ 'xAxisIndex' => 'all' ],
        'label' => [ 'backgroundColor' => '#777' ],
    ],
    'toolbox' => [
        'feature' => [
            'restore' => [ 'title' => '' ],
            'saveAsImage' => [ 'title' => '' ],
        ],
    ],
    'grid' => [
        [
            'left' => '2%',
            'right' => '2%',
            'bottom' => 200,
            'containLabel' => TRUE,
        ],
        [
            'left' => '2%',
            'right' => '2%',
            'height' => 80,
            'bottom' => 80,
        ],
    ],
    'dataZoom' => [
        [
            'type' => 'inside',
            'xAxisIndex' => [ 0, 1 ],
        ],
        [
            'type' => 'slider',
            'xAxisIndex' => [ 0, 1 ],
            'textStyle' => [ 'color' => '#8392A5' ],
            'handleIcon' => 'path://M10.7,11.9v-1.3H9.3v1.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4v1.3h1.3v-1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7V23h6.6V24.4z M13.3,19.6H6.7v-1.4h6.6V19.6z',
            'dataBackground' => [
                'areaStyle' => [ 'color' => '#8392A5' ],
                'lineStyle' => [
                    'color' => '#8392A5',
                    'opacity' => 0.8,
                ],
            ],
            'brushSelect' => TRUE,
            'bottom' => 10,
        ],
    ],
    'xAxis' => [
        [
            'type' => 'category',
            'scale' => TRUE,
            'boundaryGap' => FALSE,
            'splitLine' => [ 'show' => FAlSE ],
            'axisLabel' => [ 'inside' => FALSE ],
        ],
        [
            'type' => 'category',
            'gridIndex' => 1,
            'scale' => TRUE,
            'boundaryGap' => FALSE,
            'axisTick' => [ 'show' => FALSE ],
            'splitLine' => [ 'show' => FALSE ],
            'axisLabel' => [ 'show' => FALSE ],
        ],
    ],
    'yAxis' => [
        [
           'type' => 'value',
           'axisTick' => [ 'show' => FALSE ],
           'axisLine' => [ 'show' => FALSE ],
           'axisLabel' => [
               'show' => TRUE,
               'inside' => TRUE
           ],
           'scale' => true,
        ],
        [
            'scale' => TRUE,
            'gridIndex' => 1,
            'axisTick' => [ 'show' => FALSE ],
            'axisLine' => [ 'show' => FALSE ],
            'axisLabel' => [ 'show' => FALSE ],
            'splitLine' => [ 'show' => FALSE ],
        ],
    ],
    'series' => [
        [
            'type' => 'line',
            'symbol' => 'none',
            'itemStyle' => [
                'color' => '#00BCD4',
            ],
        ],
        [
            'id' => 'volume',
            'type' => 'bar',
            'name' => __( 'Volume' ),
            'xAxisIndex' => 1,
            'yAxisIndex' => 1,
            'itemStyle' => [
                'color' => '#90A4AE',
            ],
        ]
    ],
];

?>
<v-tab-item key="chart">
    <gc-currency-chart :currency-id="currencyId"></gc-currency-chart>
</v-tab-item>