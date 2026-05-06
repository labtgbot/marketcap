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
$frontend_options['markets']['title'] = __( 'Cryptocurrency Markets' );
$frontend_options['markets']['tonApiBaseUrl'] = site_url( 'api/ton/assets' );
$frontend_options['markets']['tonFilters'] = [
    [
        'tag'   => 'ton_ecosystem',
        'label' => 'TON ecosystem',
        'icon'  => 'mdi-diamond-stone',
    ],
    [
        'tag'   => 'stablecoin',
        'label' => 'TON stablecoins',
        'icon'  => 'mdi-cash-check',
    ],
    [
        'tag'   => 'jetton',
        'label' => 'Jettons',
        'icon'  => 'mdi-layers-triple',
    ],
    [
        'tag'   => 'defi',
        'label' => 'TON DeFi',
        'icon'  => 'mdi-bank-transfer',
    ],
    [
        'tag'   => 'unverified',
        'label' => 'Review queue',
        'icon'  => 'mdi-alert-circle-outline',
    ],
];

/*
| -------------------------------------------------------------------------
| PRICE CHANGES
| -------------------------------------------------------------------------
| TYPE: array
| DESCRIPTION: Values passed to CoinGecko API query param "price_change_percentage".
|
*/
$frontend_options['markets']['priceChanges'] = [
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
$frontend_options['markets']['tableHeaders'] = [
    [
        'text' => '#',
        'value' => 'market_cap_rank',
        'sortable' => TRUE,
        'align' => 'start',
        'width' => 60,
        'show' => TRUE, // always hide in xs (600px)
    ],
    [
        'text' => 'Name',
        'value' => 'name',
        'sortable' => TRUE,
        'align' => 'start',
        'width' => 170,
        'cellClass' => 'markets-name-col',
        'show' => TRUE,
    ],
    [
        'text' => 'Watchlist',
        'value' => 'watchlist',
        'sortable' => FALSE,
        'align' => 'center',
        'width' => 56,
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
        'width' => 100,
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
$frontend_options['markets']['perPage'] = 100;

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
$frontend_options['markets']['order'] = 'market_cap_desc';

?>
<section class="mt-8 mb-16">
    <v-container id="markets" fluid>
        <h1 class="text-center text-h4 text-sm-h4 mb-8">
            <?php echo esc_html( $frontend_options['markets']['title'] ); ?>
        </h1>

        <div class="ton-filter-bar ton-market-filter-bar mb-5">
            <div class="ton-filter-group">
                <span class="text-caption text--secondary"><?php echo esc_html( __( 'TON filters' ) ); ?></span>
                <v-chip
                    v-for="chip in tonFilterChips"
                    :key="'market-ton-' + chip.tag"
                    small
                    label
                    class="ton-filter-chip"
                    :class="{'v-chip--active': activeTonTag === chip.tag}"
                    :color="activeTonTag === chip.tag ? 'primary' : undefined"
                    :to="tonFilterRoute(activeTonTag === chip.tag ? '' : chip.tag)"
                >
                    <v-icon left small v-text="chip.icon || 'mdi-tag-outline'"></v-icon>
                    <span v-text="chip.label"></span>
                </v-chip>
                <v-btn v-if="activeTonTag" small text :to="clearTonFilterRoute()">
                    <?php echo esc_html( __( 'Clear' ) ); ?>
                </v-btn>
            </div>
            <v-alert v-if="tonFilterError" type="warning" dense text class="mb-0" v-text="tonFilterError"></v-alert>
            <div v-if="activeTonTag" class="text-caption text--secondary">
                <?php echo esc_html( __( 'Showing' ) ); ?> <span v-text="activeTonFilterLabel"></span>
            </div>
        </div>

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
                            <v-chip
                                v-if="item.tonAsset"
                                x-small
                                label
                                class="ton-verification-chip ml-1"
                                :class="'ton-verification-chip--' + item.tonAsset.verification_state"
                                :color="tonStateColor(item.tonAsset)"
                                text-color="white"
                                v-text="tonStateLabel(item.tonAsset)"
                            ></v-chip>
                        </div>
                    </div>
                </router-link>
                <router-link :to="item.route" class="markets-coin-cell d-none d-sm-inline-flex">
                    <v-avatar v-if="item.image" color="white" size="28">
                        <v-img :src="item.image" :alt="item.name" :title="item.name"></v-img>
                    </v-avatar>
                    <div class="min-width-0">
                        <div class="font-weight-medium text-truncate">
                            {{ item.name }}
                            <span class="text--secondary text-uppercase" v-text="item.symbol"></span>
                        </div>
                        <v-chip
                            v-if="item.tonAsset"
                            x-small
                            label
                            class="ton-verification-chip"
                            :class="'ton-verification-chip--' + item.tonAsset.verification_state"
                            :color="tonStateColor(item.tonAsset)"
                            text-color="white"
                            v-text="tonStateLabel(item.tonAsset)"
                        ></v-chip>
                    </div>
                </router-link>
            </template>
            <template v-slot:item.watchlist="{ item }">
                <v-btn
                    icon
                    small
                    class="watchlist-icon-button"
                    :color="isWatched(item) ? 'primary' : undefined"
                    :aria-label="watchlistLabel(item)"
                    :title="watchlistLabel(item)"
                    @click.stop.prevent="toggleWatchlist(item)"
                >
                    <v-icon v-text="watchlistIcon(item)"></v-icon>
                </v-btn>
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
