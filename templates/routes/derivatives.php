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
| TITLE
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Route title.
|
*/
$frontend_options['derivatives']['title'] = __( 'Derivatives' );

/*
| -------------------------------------------------------------------------
| TYPES
| -------------------------------------------------------------------------
| TYPE: array[]
| DESCRIPTION: Types details.
|
| -------------------------------------------------------------------------
| EXPLANATION OF TYPE PARAMETERS
| -------------------------------------------------------------------------
|
|   ['value']   (string)    Type name
|   ['text']    (string)    Type title
|
*/
$frontend_options['derivatives']['types'] = [
    [
        'value' => 'all',
        'text' => __( 'All' ),
    ],
    [
        'value' => 'futures',
        'text' => __( 'Futures' ),
    ],
    [
        'value' => 'perpetual',
        'text' => __( 'Perpetual' ),
    ],
];

/*
| -------------------------------------------------------------------------
| DEFAULT TYPE
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Selected type (name/value) by default.
|
*/
$frontend_options['derivatives']['defaultType'] = 'all';

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
|   ['sortable']    (bool)              Set TRUE to allow column sorting
|   ['align']       (string)            Defines the column text alignment. Can be 'start', 'center' or 'end'.
|   ['width']       (int|float|string)  The header width
|   ['show']        (bool)              Set TRUE to show column
|   ['type']        (null|string)       Show column when specific type is selected (use type value).
|
*/
$frontend_options['derivatives']['tableHeaders'] = [
    [
        'text'      => __( 'Market' ),
        'value'     => 'market',
        'sortable'  => TRUE,
        'filterable' => TRUE,
        'align'     => 'start',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Symbol' ),
        'value'     => 'symbol',
        'sortable'  => TRUE,
        'filterable' => TRUE,
        'align'     => 'start',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Price' ),
        'value'     => 'price',
        'sortable'  => TRUE,
        'filterable' => FALSE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( '24h %' ),
        'value'     => 'price_percentage_change_24h',
        'sortable'  => TRUE,
        'filterable' => FALSE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Type' ),
        'value'     => 'contract_type',
        'sortable'  => TRUE,
        'filterable' => TRUE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Index Price' ),
        'value'     => 'index',
        'sortable'  => TRUE,
        'filterable' => FALSE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Basis' ),
        'value'     => 'basis',
        'sortable'  => TRUE,
        'filterable' => FALSE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Spread' ),
        'value'     => 'spread',
        'sortable'  => TRUE,
        'filterable' => FALSE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Funding rate' ),
        'value'     => 'funding_rate',
        'sortable'  => TRUE,
        'filterable' => FALSE,
        'align'     => 'end',
        'show'      => TRUE,
        'type' => 'perpetual',
    ],
    [
        'text'      => __( 'Expire Date' ),
        'value'     => 'expired_at',
        'sortable'  => TRUE,
        'filterable' => FALSE,
        'align'     => 'end',
        'show'      => TRUE,
        'type' => 'futures',
    ],
    [
        'text'      => __( 'Open Interest' ),
        'value'     => 'open_interest',
        'sortable'  => TRUE,
        'filterable' => FALSE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Volume 24h' ),
        'value'     => 'volume_24h',
        'sortable'  => TRUE,
        'filterable' => FALSE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
];


/*
| -------------------------------------------------------------------------
| TABLE FOOTER PROPS
| -------------------------------------------------------------------------
| TYPE: array
| DESCRIPTION: Data table footer properties.
| REFERENCE: https://vuetifyjs.com/en/api/v-data-footer/#api-props
|
*/
$frontend_options['derivatives']['tableFooterProps'] = [
    'items-per-page-options' => [ 10, 25, 50, 100 ],
];

?>
<section class="mt-8 mb-16">
    <v-container id="derivatives" fluid>
        <h1 class="text-center text-h4 text-sm-h4 mb-8">
            <?php echo esc_html( $frontend_options['derivatives']['title'] ); ?>
        </h1>

        <v-card class="elevation-0">
            <v-card-text>
                <v-row justify="end">
                    <v-col cols="12" sm="4" md="auto">
                        <v-text-field
                            v-model="search"
                            append-icon="mdi-magnify"
                            label="<?php echo esc_attr( __( 'Search' ) ); ?>"
                            single-line
                            hide-details
                        ></v-text-field>
                    </v-col>
                    <v-col md="auto">
                        <v-select
                            v-model="selectedMarket"
                            :items="markets"
                            label="<?php echo esc_attr( __( 'Market' ) ); ?>"
                        ></v-select>
                    </v-col>
                    <v-col md="auto">
                        <v-select
                            v-model="selectedType"
                            :items="types"
                            label="<?php echo esc_attr( __( 'Type' ) ); ?>"
                        ></v-select>
                    </v-col>
                    <v-col cols="auto" class="align-self-center">
                        <v-btn text icon @click="clearFilters" :disabled="!isFiltered">
                            <v-icon>mdi-filter-off</v-icon>
                        </v-btn>
                    </v-col>
                </v-row>
            </v-card-text>

        </v-card>


        <v-data-table
            id="derivatives-table"
            :headers="tableHeaders"
            :footer-props="tableFooterProps"
            :items="items"
            :items-per-page="25"
            :mobile-breakpoint="0"
            :loading="loading"
            :search="search"
            sort-by="volume_24h"
            :sort-desc="true"
        >
            <template v-slot:item.market="{ item }">
                <v-chip color="transparent" class="font-weight-medium" v-text="item.market" :href="getMarketUrl(item.market)" target="_blank" rel="noopener"></v-chip>
            </template>
            <template v-slot:item.symbol="{ item }">
                <v-chip v-if="item.symbol" class="font-weight-medium" v-text="item.symbol" :href="getMarketUrl(item.market)" target="_blank" rel="noopener"></v-chip>
            </template>
            <template v-slot:item.contract_type="{ item }">
                <span class="text-capitalize" v-text="item.contract_type"></span>
            </template>
            <template v-slot:item.price="{ item }">
                <span class="font-weight-medium" v-text="$root.priceTargetFormat(item.price)"></span>
            </template>
            <template v-slot:item.price_percentage_change_24h="{ item }">
                <span class="font-weight-bold" :class="$root.changeColorClass(item.price_percentage_change_24h)">
                    <v-icon :color="$root.changeColor(item.price_percentage_change_24h)" v-text="$root.changeIcon(item.price_percentage_change_24h)"></v-icon>
                    {{ $root.changeFormat(item.price_percentage_change_24h) }}
                </span>
            </template>
            <template v-slot:item.index="{ item }">
                <span class="font-weight-medium" v-text="$root.priceTargetFormat(item.index)"></span>
            </template>
            <template v-slot:item.basis="{ item }">
                <span class="font-weight-medium" v-text="$root.basisFormat(item.basis)"></span>
            </template>
            <template v-slot:item.spread="{ item }">
                <span class="font-weight-medium" v-text="$root.spreadFormat(item.spread)"></span>
            </template>
            <template v-slot:item.funding_rate="{ item }">
                <span class="font-weight-medium" v-text="$root.rateFormat(item.funding_rate)"></span>
            </template>
            <template v-slot:item.expired_at="{ item }">
                {{ $root.dateFormatFromTimestamp(item.expired_at) }}
            </template>
            <template v-slot:item.volume_24h="{ item }">
                <span class="font-weight-medium" v-text="$root.volumeTargetFormat(item.volume_24h)"></span>
            </template>
            <template v-slot:item.open_interest="{ item }">
                <span class="font-weight-medium" v-text="$root.volumeTargetFormat(item.open_interest)"></span>
            </template>
        </v-data-table>

    </v-container>
</section>