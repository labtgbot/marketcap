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
$frontend_options['exchanges']['title'] = __( 'Exchanges' );

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
$frontend_options['exchanges']['tableHeaders'] = [
    [
        'text'      => '#',
        'value'     => 'trust_score_rank',
        'sortable'  => TRUE,
        'align'     => 'start',
        'width'     => 80,
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Name' ),
        'value'     => 'name',
        'sortable'  => TRUE,
        'align'     => 'start',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Trust Score' ),
        'value'     => 'trustScore',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Volume 24h' ),
        'value'     => 'trade_volume_24h_btc',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Volume 24h (Normalized)' ),
        'value'     => 'trade_volume_24h_btc_normalized',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => FALSE,
    ],
    [
        'text'      => __( 'Since' ),
        'value'     => 'year_established',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Country' ),
        'value'     => 'country',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
];

/*
| -------------------------------------------------------------------------
| PER PAGE
| -------------------------------------------------------------------------
| TYPE: int
| DESCRIPTION: CoinGecko API query param "per_page".
| MAX: 250
| DEFAULT: 100
|
*/
$frontend_options['exchanges']['perPage'] = 100;

?>
<section class="mt-8 mb-16">
    <v-container id="exchanges" fluid>
        <h1 class="text-center text-h4 text-sm-h4 mb-8">
            <?php echo esc_html( $frontend_options['exchanges']['title'] ); ?>
        </h1>

        <v-data-table
            id="exchanges-table"
            :headers="tableHeaders"
            :items="exchanges"
            :mobile-breakpoint="960"
            :disable-pagination="true"
            :hide-default-footer="true"
            :loading="loading"
            @click:row="toExchange"
        >
            <template v-slot:item.name="{ item }">
                <v-chip color="transparent" :ripple="false" :to="item.route">
                    <v-avatar left v-if="item.image" color="white">
                        <v-img :src="item.image" :alt="item.name" :title="item.name"></v-img>
                    </v-avatar>
                    <span class="font-weight-medium" v-text="item.name"></span>
                </v-chip>
            </template>
            <template v-slot:item.trustScore="{ item }">
                <v-chip small :ripple="false" :to="item.route" :color="item.trustColor" :text-color="item.trustTextColor" v-text="item.trustText"></v-chip>
            </template>
            <template v-slot:item.trade_volume_24h_btc="{ item }">
                <span class="font-weight-medium" v-text="item.volume24hFormatted"></span>
            </template>
            <template v-slot:item.trade_volume_24h_btc_normalized="{ item }">
                <span class="font-weight-medium" v-text="item.volume24hNormalizedFormatted"></span>
            </template>
            <template v-slot:item.year_established="{ item }">
                {{ item.year_established }}
            </template>
            <template v-slot:item.country="{ item }">
                {{ item.country }}
            </template>
        </v-data-table>

        <div v-if="!loading && loadMore">
            <v-btn block text large :loading="loadMoreLoading" @click="fetchMoreExchanges">
                <?php echo esc_html( __( 'Load More' ) ); ?>
            </v-btn>
        </div>

    </v-container>
</section>

