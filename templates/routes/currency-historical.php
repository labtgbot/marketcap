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
| EXPLANATION OF HEADER VARIABLES
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
$frontend_options['currency-historical']['tableHeaders'] = [
    [
        'text'      => __( 'Date' ),
        'value'     => 'timestamp',
        'sortable'  => TRUE,
        'align'     => 'start',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Price' ),
        'value'     => 'price',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Market Cap' ),
        'value'     => 'marketCap',
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
];

/*
| -------------------------------------------------------------------------
| PERIOD DAYS
| -------------------------------------------------------------------------
| TYPE: int
|
| DESCRIPTION:
| Used to calculate CoinGecko API query params "from" and "to".
| Must be 120 or more to force 1-day interval.
|
| MIN: 120
|
| DEFAULT: 120
*/
$frontend_options['currency-historical']['periodDays'] = 120;

?>
<v-tab-item key="historical">
    <div id="currency-historical">
        <v-data-table
            id="currency-historical-table"
            :headers="historicalTableHeaders"
            :items="historicalData"
            :mobile-breakpoint="960"
            :disable-pagination="true"
            :hide-default-footer="true"
            :loading="historicalLoading"
        >
            <template v-slot:item.timestamp="{ item }">
                <span class="font-weight-medium" v-text="$root.dateFormat(item.timestamp)"></span>
            </template>
            <template v-slot:item.price="{ item }">
                <span class="font-weight-medium" v-text="$root.priceFormat(item.price)"></span>
            </template>
            <template v-slot:item.marketCap="{ item }">
                <span class="font-weight-medium" v-text="$root.marketCapFormat(item.marketCap)"></span>
            </template>
            <template v-slot:item.volume="{ item }">
                <span class="font-weight-medium" v-text="$root.volumeFormat(item.volume)"></span>
            </template>
        </v-data-table>

        <div v-if="!historicalLoading && historicalLoadMore">
            <v-btn block text large :loading="historicalLoadMoreLoading" @click="fetchMoreHistoricalData">
                <?php echo esc_html( __( 'Load More' ) ); ?>
            </v-btn>
        </div>
    </div>
</v-tab-item>
