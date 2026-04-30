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
| INTERVALS
| -------------------------------------------------------------------------
| TYPE: array[]
| DESCRIPTION: Intervals details.
|
| -------------------------------------------------------------------------
| EXPLANATION OF INTERVAL PARAMETERS
| -------------------------------------------------------------------------
|
|   ['value']   (int)       Interval days
|   ['text']    (string)    Interval title
|
*/
$frontend_options['exchange']['intervals'] = [
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
];

/*
| -------------------------------------------------------------------------
| DEFAULT INTERVAL
| -------------------------------------------------------------------------
| TYPE: int
| DESCRIPTION: Selected interval (value) by default.
|
*/
$frontend_options['exchange']['defaultInterval'] = 7;

/*
| -------------------------------------------------------------------------
| ECHART OPTIONS
| -------------------------------------------------------------------------
| TYPE: array
| DESCRIPTION: ECharts instance options.
| REFERENCE: https://echarts.apache.org/en/option.html
|
*/
$frontend_options['exchange']['echartOptions'] = [
    'animationDuration' => 500,
    'grid' => [
        'top' => 30,
        'left' => 0,
        'right' => 0,
        'bottom' => 0,
    ],
    'tooltip' => [
        'trigger' => 'axis',
        'axisPointer' => [
            'type' => 'none',
        ]
    ],
    'xAxis' => [
        'type' => 'category',
        'show' => FALSE,
        'boundaryGap' => FALSE,
    ],
    'yAxis' => [
        'type' => 'value',
        'show' => FALSE,
        'min' => 'dataMin',
    ],
    'series' => [
        [
            'name' => __( 'Volume' ),
            'type' => 'line',
            'lineStyle' => [ 'width' => 0 ],
            'showSymbol' => FALSE,
            'symbolSize' => 10,
            'itemStyle' => [
                'color' => '#00BCD4',
            ],
            'areaStyle' => [
                'opacity' => 1
            ],
            'encode' => [
                'x' => 0,
                'y' => 1,
            ]
        ],
    ],
];

/*
| -------------------------------------------------------------------------
| TABLE HEADERS
| -------------------------------------------------------------------------
| TYPE: array[]
| DESCRIPTION: Data table header and column options.
| REFERENCE: https://vuetifyjs.com/en/api/v-data-table/#props-headers
|
| -------------------------------------------------------------------------
| EXPLANATION OF HEADER PARAMETERS
| -------------------------------------------------------------------------
|
|   ['text']        (string)            The header title
|   ['name']        (string)            The property name to get value
|   ['sortable']    (bool)              set TRUE to allow column sorting
|   ['align']       (string)            Defines the column text alignment. Can be 'start', 'center' or 'end'.
|   ['width']       (int|float|string)  The header width
|   ['show']        (bool)              set TRUE to show column
|
*/
$frontend_options['exchange']['tableHeaders'] = [
    [
        'text'      => __( 'Pair' ),
        'value'     => 'pair',
        'sortable'  => TRUE,
        'align'     => 'start',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Price' ),
        'value'     => 'lastUSD',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Volume' ),
        'value'     => 'volumeUSD',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Spread' ),
        'value'     => 'bid_ask_spread_percentage',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Trust' ),
        'value'     => 'trustScore',
        'sortable'  => TRUE,
        'align'     => 'center',
        'show'      => TRUE,
    ],
];

?>
<section class="mt-8 mb-16">
    <gc-page-loader :loading="loading"></gc-page-loader>

    <v-container id="exchange" v-if="exchange && !loading">
        <v-row align="center">
            <v-col cols="12" sm="6">
                <?php
                /*
                 * Logo
                 */
                ?>
                <h1 class="text-h4 text-sm-h4">
                    <v-avatar tile size="48" v-if="exchange.image">
                        <img :src="exchange.image" :alt="exchange.name">
                    </v-avatar>
                    {{ exchange.name }}
                </h1>

                <?php
                /*
                 * Labels
                 */
                ?>
                <v-chip-group column class="my-2">
                    <v-chip label outlined :ripple="false" v-if="exchange.trust_score_rank">
                        <?php echo esc_html( __( 'Rank' ) ); ?> #{{ exchange.trust_score_rank }}
                    </v-chip>
                    <v-chip label outlined :ripple="false" v-if="exchange.trust_score">
                        <v-avatar left>
                            <v-icon :color="$root.coinGeckoTrustScoreColor(exchange.trust_score)">mdi-check-decagram</v-icon>
                        </v-avatar>
                        {{ exchange.trust_score }}/10
                    </v-chip>
                    <v-chip label outlined :ripple="false" v-if="exchange.country">
                        <v-avatar left>
                            <v-icon>mdi-map-marker</v-icon>
                        </v-avatar>
                        {{ exchange.country }}
                    </v-chip>
                    <v-chip label outlined :ripple="false" v-if="exchange.year_established">
                        <?php echo esc_html( __( 'Since' ) ); ?> {{ exchange.year_established }}
                    </v-chip>
                </v-chip-group>

                <div id="exchange-info-menus">
                    <?php
                    /*
                     * Links Menu
                     */
                    ?>
                    <v-menu offset-y >
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn depressed small color="primary" v-bind="attrs" v-on="on">
                                <v-icon left>mdi-link-variant</v-icon>
                                <?php echo esc_html( __( 'Links' ) ); ?>
                            </v-btn>
                        </template>
                        <v-list dense>
                            <v-list-item v-if="exchange.url" :href="exchange.url" target="_blank" rel="noopener">
                                <v-list-item-title>
                                    <v-icon left small>mdi-open-in-new</v-icon>
                                    <?php echo esc_html( __( 'Website' ) ); ?>
                                </v-list-item-title>
                            </v-list-item>
                            <v-list-item v-if="exchange.twitterUrl" :href="exchange.twitterUrl" target="_blank" rel="noopener">
                                <v-list-item-title>
                                    <v-icon left small color="#1DA1F2">mdi-twitter</v-icon>
                                    {{ $root.pathFromUrl(exchange.twitterUrl, true) }}
                                </v-list-item-title>
                            </v-list-item>
                            <v-list-item v-if="exchange.facebookUrl" :href="exchange.facebookUrl" target="_blank" rel="noopener">
                                <v-list-item-title>
                                    <v-icon left small color="#1876F2">mdi-facebook</v-icon>
                                    {{ $root.pathFromUrl(exchange.facebookUrl, true) }}
                                </v-list-item-title>
                            </v-list-item>
                            <v-list-item v-if="exchange.redditUrl" :href="exchange.redditUrl" target="_blank" rel="noopener">
                                <v-list-item-title>
                                    <v-icon left small color="#FF4500">mdi-reddit</v-icon>
                                    {{ $root.pathFromUrl(exchange.redditUrl, true) }}
                                </v-list-item-title>
                            </v-list-item>
                            <v-list-item v-if="exchange.telegramUrl" :href="exchange.telegramUrl" target="_blank" rel="noopener">
                                <v-list-item-title>
                                    <v-icon left small color="#0088CC">mdi-telegram</v-icon>
                                    {{ $root.pathFromUrl(exchange.telegramUrl, true) }}
                                </v-list-item-title>
                            </v-list-item>
                            <v-list-item v-if="exchange.otherUrl1" :href="exchange.otherUrl1" target="_blank" rel="noopener">
                                <v-list-item-title>
                                    <v-icon left small>mdi-open-in-new</v-icon>
                                    {{ $root.hostFromUrl(exchange.otherUrl1) }}
                                </v-list-item-title>
                            </v-list-item>
                            <v-list-item v-if="exchange.otherUrl2" :href="exchange.otherUrl2" target="_blank" rel="noopener">
                                <v-list-item-title>
                                    <v-icon left small>mdi-open-in-new</v-icon>
                                    {{ $root.hostFromUrl(exchange.otherUrl2) }}
                                </v-list-item-title>
                            </v-list-item>
                        </v-list>
                    </v-menu>
                </div>
            </v-col>

            <?php
            /*
             * 24h Volume
             */
            ?>
            <v-col cols="12" sm="6" :class="{'text-sm-right':!$root.rtl, 'text-sm-left': $root.rtl}" v-if="exchange.trade_volume_24h_btc">
                <div class="font-weight-medium text--secondary">
                    <?php echo esc_html( __( 'Volume (24h)' ) ); ?>
                </div>
                <h3 class="text-h5 text-sm-h4" v-text="$root.volumeBTCFormat(exchange.trade_volume_24h_btc)"></h3>
            </v-col>
        </v-row>

        <?php
        /*
         * Volume Chart
         */
        ?>
        <gc-exchange-chart :exchange-id="exchangeId" class="my-8"></gc-exchange-chart>

        <?php
        /*
         * Tickers Table
         */
        ?>
        <v-card flat>
            <v-card-title><?php echo esc_html( __( 'Tickers' ) ); ?></v-card-title>

            <v-data-table
                    id="exchange-tickers"
                    :headers="tableHeaders"
                    :items="tickers"
                    :mobile-breakpoint="960"
                    :disable-pagination="true"
                    :hide-default-footer="true"
                    :loading="tickersLoading"
            >
                <template v-slot:item.pair="{ item }">
                    <a class="font-weight-medium" :href="item.trade_url" :title="item.pair" v-text="item.pairDisplay" target="_blank" rel="noopener"></a>
                </template>
                <template v-slot:item.lastUSD="{ item }">
                    <span class="font-weight-medium" v-text="item.lastFormatted"></span>
                </template>
                <template v-slot:item.volumeUSD="{ item }">
                    <span class="font-weight-medium" v-text="item.volumeFormatted"></span>
                </template>
                <template v-slot:item.bid_ask_spread_percentage="{ item }">
                    <span class="font-weight-medium" v-text="item.spreadFormatted"></span>
                </template>
                <template v-slot:item.trustScore="{ item }">
                    <v-chip small :ripple="false" :color="item.trustColor" :text-color="item.trustTextColor" v-text="item.trustText"></v-chip>
                </template>
            </v-data-table>

            <div v-if="!loading && tickersLoadMore">
                <v-btn block text large :loading="tickersLoading" @click="fetchMoreTickers">
                    <?php echo esc_html( __( 'Load More' ) ); ?>
                </v-btn>
            </div>
        </v-card>

    </v-container>
</section>