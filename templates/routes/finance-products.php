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
$frontend_options['finance-products']['title'] = __( 'Finance Products' );

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
$frontend_options['finance-products']['tableHeaders'] = [
    [
        'text'      => __( 'Platform' ),
        'value'     => 'platform',
        'sortable'  => TRUE,
        'align'     => 'start',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Identifier' ),
        'value'     => 'identifier',
        'sortable'  => TRUE,
        'align'     => 'start',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Supply Rate' ),
        'value'     => 'supply_rate_percentage',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Borrow Rate' ),
        'value'     => 'borrow_rate_percentage',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => TRUE,
    ],
    [
        'text'      => __( 'Start Date' ),
        'value'     => 'start_at',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => FALSE,
    ],
    [
        'text'      => __( 'End Date' ),
        'value'     => 'end_at',
        'sortable'  => TRUE,
        'align'     => 'end',
        'show'      => FALSE,
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
$frontend_options['finance-products']['perPage'] = 100;

/*
| -------------------------------------------------------------------------
| DOTS
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show CeFi/DeFi indication dots.
|
*/
$route_finance_products['dots'] = 0;

?>
<section class="mt-8 mb-16">
    <v-container id="finance-products">
        <h1 class="v-heading text-h4 text-sm-h4 mb-8">
            <?php echo esc_html( $frontend_options['finance-products']['title'] ); ?>
        </h1>

        <v-data-table
            id="finance-products-table"
            :headers="tableHeaders"
            :items="products"
            :loading="loading"
            :disable-pagination="true"
            :hide-default-footer="true"
        >
            <template v-slot:item.platform="{ item }">
                <v-chip :ripple="false" color="transparent" :href="platformUrl(item.platform)" target="_blank" rel="noopener">
                    <span class="font-weight-medium" v-text="item.platform"></span>
                    <?php if ( ! empty( $route_finance_products['dots'] ) ) : ?>
                        <v-avatar>
                            <v-icon :color="platformColor(item.platform)">mdi-circle</v-icon>
                        </v-avatar>
                    <?php endif; ?>
                </v-chip>
            </template>
            <template v-slot:item.identifier="{ item }">
                <v-chip class="product-identifier font-weight-medium" :ripple="false" :href="platformUrl(item.platform)" target="_blank" rel="noopener">
                    <span class="text-truncate" v-text="item.identifier"></span>
                </v-chip>
            </template>
            <template v-slot:item.supply_rate_percentage="{ item }">
                <span class="font-weight-medium" v-text="$root.rateFormat(item.supply_rate_percentage)"></span>
            </template>
            <template v-slot:item.borrow_rate_percentage="{ item }">
                <span class="font-weight-medium" v-text="$root.rateFormat(item.borrow_rate_percentage)"></span>
            </template>
            <template v-slot:item.start_at="{ item }">
                {{ $root.dateFormatFromTimestamp(item.start_at) }}
            </template>
            <template v-slot:item.end_at="{ item }">
                {{ $root.dateFormatFromTimestamp(item.end_at) }}
            </template>
        </v-data-table>

        <div v-if="!loading && loadMore">
            <v-btn block text large :loading="loadMoreLoading" @click="fetchMoreProducts">
                <?php echo esc_html( __( 'Load More' ) ); ?>
            </v-btn>
        </div>

    </v-container>
</section>