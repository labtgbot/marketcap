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
$frontend_options['currencies']['title'] = __( 'Cryptocurrencies' );

/*
| -------------------------------------------------------------------------
| PRICE CHANGES
| -------------------------------------------------------------------------
| TYPE: array
| DESCRIPTION: Values passed to CoinGecko API query param "price_change_percentage".
|
*/
$frontend_options['currencies']['priceChanges'] = [
    '24h',
    '7d',
    '30d',
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
$frontend_options['currencies']['tableHeaders'] = [
    [
        'text' => '#',
        'value' => 'market_cap_rank',
        'sortable' => TRUE,
        'align' => 'start',
        'width' => 80,
        'show' => TRUE, // always hide in xs (600px)
    ],
    [
        'text' => 'Name',
        'value' => 'name',
        'sortable' => TRUE,
        'align' => 'start',
        'show' => TRUE,
    ],
    [
        'text' => 'Price',
        'value' => 'current_price',
        'sortable' => TRUE,
        'align' => 'end',
        'show' => TRUE,
    ],
    [
        'text' => '24h %',
        'value' => 'price_change_percentage_24h_in_currency',
        'sortable' => TRUE,
        'align' => 'end',
        'show' => TRUE,
    ],
    [
        'text' => '7d %',
        'value' => 'price_change_percentage_7d_in_currency',
        'sortable' => TRUE,
        'align' => 'end',
        'show' => TRUE,
    ],
    [
        'text' => '30d %',
        'value' => 'price_change_percentage_30d_in_currency',
        'sortable' => TRUE,
        'align' => 'end',
        'show' => TRUE,
    ],
    [
        'text' => 'Market Cap',
        'value' => 'market_cap',
        'sortable' => TRUE,
        'align' => 'end',
        'show' => TRUE,
    ],
    [
        'text' => 'Volume 24h',
        'value' => 'total_volume',
        'sortable' => TRUE,
        'align' => 'end',
        'show' => TRUE,
    ],
    [
        'text' => 'Circulating Supply',
        'value' => 'circulating_supply',
        'sortable' => TRUE,
        'align' => 'end',
        'show' => TRUE,
    ],
    [
        'text' => 'Last 7d',
        'value' => 'last_7d',
        'sortable' => FALSE,
        'align' => 'center',
        'width' => 250,
        'show' => TRUE,
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
$frontend_options['currencies']['perPage'] = 100;

/*
| -------------------------------------------------------------------------
| ORDER
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: CoinGecko API query param order.
| DEFAULT: 'market_cap_desc'
| OPTIONS: 'market_cap_desc', 'market_cap_asc', 'gecko_desc', 'gecko_asc',
|   'volume_asc', 'volume_desc', 'id_asc' or 'id_desc'
|
*/
$frontend_options['currencies']['order'] = 'market_cap_desc';

?>
<section class="mt-8 mb-16">
    <v-container id="currencies" fluid>
        <h1 class="text-center text-h4 text-sm-h4 mb-8">
            <?php echo esc_html( $frontend_options['currencies']['title'] ); ?>
        </h1>

        <v-data-table
            id="currencies-table"
            :headers="tableHeaders"
            :items="currencies"
            :mobile-breakpoint="0"
            :disable-pagination="true"
            :hide-default-footer="true"
            :loading="loading"
            dense
            @click:row="toCurrency"
        >
            <template v-slot:item.name="{ item }">
                <router-link :to="item.route" class="name-xs d-flex align-center d-sm-none">
                    <v-avatar v-if="item.image" left color="white" size="28">
                        <v-img :src="item.image" :alt="item.name" :title="item.name"></v-img>
                    </v-avatar>
                    <div class="c-text d-flex flex-column">
                        <div class="c-name font-weight-medium" v-text="item.name"></div>
                        <div>
                            <v-chip label small :ripple="false" :to="item.route" class="c-rank d-sm-none" v-text="item.market_cap_rank"></v-chip>
                            <span class="c-symbol text-uppercase font-weight-medium text--secondary" v-text="item.symbol"></span>
                        </div>
                    </div>
                </router-link>
                <v-chip :ripple="false" color="transparent" :to="item.route" class="d-none d-sm-inline-flex">
                    <v-avatar left v-if="item.image" color="white">
                        <v-img :src="item.image" :alt="item.name" :title="item.name"></v-img>
                    </v-avatar>
                    <span class="font-weight-medium">
                        {{ item.name }}
                        <span class="text--secondary text-uppercase" v-text="item.symbol"></span>
                    </span>
                </v-chip>
            </template>
            <template v-slot:item.current_price="{ item }">
                <span class="font-weight-medium" v-text="$root.priceFormat(item.current_price)"></span>
            </template>
            <template v-slot:item.price_change_percentage_24h_in_currency="{ item }">
                <span class="font-weight-bold" :class="$root.changeColorClass(item.price_change_percentage_24h_in_currency)">
                    <v-icon
                        :color="$root.changeColor(item.price_change_percentage_24h_in_currency)"
                        v-text="$root.changeIcon(item.price_change_percentage_24h_in_currency)"
                    ></v-icon>
                    {{ $root.changeFormat(item.price_change_percentage_24h_in_currency) }}
                </span>
            </template>
            <template v-slot:item.price_change_percentage_7d_in_currency="{ item }">
                <span class="font-weight-bold" :class="$root.changeColorClass(item.price_change_percentage_7d_in_currency)">
                    <v-icon
                        :color="$root.changeColor(item.price_change_percentage_7d_in_currency)"
                        v-text="$root.changeIcon(item.price_change_percentage_7d_in_currency)"
                    ></v-icon>
                    {{ $root.changeFormat(item.price_change_percentage_7d_in_currency) }}
                </span>
            </template>
            <template v-slot:item.price_change_percentage_30d_in_currency="{ item }">
                <span class="font-weight-bold" :class="$root.changeColorClass(item.price_change_percentage_30d_in_currency)">
                    <v-icon
                        :color="$root.changeColor(item.price_change_percentage_30d_in_currency)"
                        v-text="$root.changeIcon(item.price_change_percentage_30d_in_currency)"
                    ></v-icon>
                    {{ $root.changeFormat(item.price_change_percentage_30d_in_currency) }}
                </span>
            </template>
            <template v-slot:item.market_cap="{ item }">
                <span class="font-weight-medium" v-text="$root.marketCapFormat(item.market_cap)"></span>
            </template>
            <template v-slot:item.total_volume="{ item }">
                <span class="font-weight-medium" v-text="$root.volumeFormat(item.total_volume)"></span>
            </template>
            <template v-slot:item.circulating_supply="{ item }">
                <span class="font-weight-medium">
                    {{ $root.bigNumberFormat(item.circulating_supply) }}
                    <span class="text-uppercase" v-text="item.symbol"></span>
                </span>
            </template>
            <template v-slot:item.last_7d="{ item }">
                <v-sparkline
                    :fill="false"
                    :radius="8"
                    :value="item.sparkline_in_7d.price"
                    :color="$root.changeColor(item.price_change_percentage_7d_in_currency)"
                    style="width: 100%;"
                ></v-sparkline>
            </template>
        </v-data-table>

        <div v-if="!loading && loadMore">
            <v-btn block text large :loading="loadMoreLoading" @click="fetchMoreCurrencies">
                <?php echo esc_html( __( 'Load More' ) ); ?>
            </v-btn>
        </div>

    </v-container>
</section>