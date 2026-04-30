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
|   ['show']        (bool)              Set TRUE to show column
|
*/
$frontend_options['currency-market']['tableHeaders'] = [
    [
        'text'      => __( 'Exchange' ),
        'value'     => 'exchangeName',
        'sortable'  => TRUE,
        'align'     => 'start',
        'show'      => TRUE,
    ],
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
        'value'     => 'volume',
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
        'align'     => 'end',
        'show'      => TRUE,
    ],
];

?>
<v-tab-item key="market">
    <div id="currency-market">
        <v-data-table
            id="currency-market-table"
            :headers="marketTableHeaders"
            :items="marketTickers"
            :mobile-breakpoint="960"
            :disable-pagination="true"
            :hide-default-footer="true"
            :loading="marketLoading"
        >
            <template v-slot:item.exchangeName="{ item }">
                <v-chip color="transparent" :to="item.exchangeRoute">
                    <v-avatar left v-if="item.exchangeLogo" color="white">
                        <v-img :src="item.exchangeLogo" :alt="item.exchangeName" :title="item.exchangeName"></v-img>
                    </v-avatar>
                    <span class="font-weight-medium" v-text="item.exchangeName"></span>
                </v-chip>
            </template>
            <template v-slot:item.pair="{ item }">
                <a class="font-weight-medium" :title="item.pair" v-text="item.pairDisplay" :href="item.trade_url" target="_blank" rel="noopener"></a>
            </template>
            <template v-slot:item.lastUSD="{ item }">
                <span class="font-weight-medium" v-text="item.lastFormatted"></span>
            </template>
            <template v-slot:item.volume="{ item }">
                <span class="font-weight-medium" v-text="item.volumeFormatted"></span>
            </template>
            <template v-slot:item.bid_ask_spread_percentage="{ item }">
                <span class="font-weight-medium" v-text="item.spreadFormatted"></span>
            </template>
            <template v-slot:item.trustScore="{ item }">
                <v-chip small :ripple="false" :color="item.trustColor" :text-color="item.trustTextColor" v-text="item.trustText"></v-chip>
            </template>
        </v-data-table>

        <div v-if="!marketLoading && marketLoadMore">
            <v-btn block text large :loading="marketLoadingMore" @click="fetchMoreTickers">
                <?php echo esc_html( __( 'Load More' ) ); ?>
            </v-btn>
        </div>
    </div>
</v-tab-item>
