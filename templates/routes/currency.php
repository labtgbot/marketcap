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
| EXPLORERS
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to display "Explorers" menu.
|
*/
$route_currency['explorers'] = TRUE;

/*
| -------------------------------------------------------------------------
| CONTRACTS
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to display "Contracts" menu.
|
*/
$route_currency['contracts'] = TRUE;

/*
| -------------------------------------------------------------------------
| LINKS
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to display "Links" menu.
|
*/
$route_currency['links'] = TRUE;

/*
| -------------------------------------------------------------------------
| SCORES
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to display "Scores" menu.
|
*/
$route_currency['scores'] = TRUE;

/*
| -------------------------------------------------------------------------
| CONVERTER
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to display currency converter component.
|
*/
$route_currency['converter'] = TRUE;

/*
| -------------------------------------------------------------------------
| TABS
| -------------------------------------------------------------------------
| TYPE: array[]
| DESCRIPTION: Table details.
|
| -------------------------------------------------------------------------
| EXPLANATION OF TAB PARAMETERS
| -------------------------------------------------------------------------
|
|   ['text']        (string)    Tab unique identifier
|   ['text']        (string)    Tab title
|   ['icon']        (string)    Icon class name (mdi-*). Icons: https://materialdesignicons.com/
|   ['template']    (string)    Tab template file path
|   ['show']        (bool)      Set TRUE to show tab
|
*/
$route_currency['tabs'] = [
    [
        'key' => 'chart',
        'text' => __( 'Chart' ),
        'icon' => 'mdi-chart-line',
        'template' => GECKO_CLIENT_TEMPLATES_DIR . '/routes/currency-chart.php',
        'show' => TRUE,
    ],
    [
        'key' => 'market',
        'text' => __( 'Market' ),
        'icon' => 'mdi-format-list-bulleted',
        'template' => GECKO_CLIENT_TEMPLATES_DIR . '/routes/currency-market.php',
        'show' => TRUE,
    ],
    [
        'key' => 'historical',
        'text' => __( 'Historical Data' ),
        'icon' => 'mdi-history',
        'template' => GECKO_CLIENT_TEMPLATES_DIR . '/routes/currency-historical.php',
        'show' => TRUE,
    ],
];

/*
| -------------------------------------------------------------------------
| TABS (KEYS)
| -------------------------------------------------------------------------
| TYPE: array
| DESCRIPTION: Enabled tabs keys list (auto-generated from $route_currency['tabs']).
|
*/
$frontend_options['currency']['tabs'] = [];
foreach ( $route_currency['tabs'] as $tab ) {
    if ( ! empty( $tab['show'] ) ) {
        $frontend_options['currency']['tabs'][] = $tab['key'];
    }
}

?>
<section class="mt-8 mb-16">
    <gc-page-loader :loading="loading"></gc-page-loader>

    <v-container id="currency" v-if="currency && !loading">
        <v-row align="center">
            <?php
            /*
             * Logo and labels
             */
            ?>
            <v-col cols="12" sm="6" md="4">
                <h1 class="text-h4 text-sm-h4">
                    <v-avatar tile size="48" v-if="currency.image && currency.image.large">
                        <img :src="currency.image.large" :alt="currency.name">
                    </v-avatar>
                    {{ currency.name }}
                    <v-chip label small class="text-uppercase" v-text="currency.symbol"></v-chip>
                </h1>

                <v-chip-group column class="my-2">
                    <v-chip label outlined :ripple="false" v-if="currency.market_cap_rank">
                        <?php echo esc_html( __( 'Rank' ) ); ?> #{{ currency.market_cap_rank }}
                    </v-chip>
                    <v-chip label outlined :ripple="false" v-text="currency.category" v-if="currency.category"></v-chip>
                    <v-chip label outlined :ripple="false" v-text="currency.hashing_algorithm" v-if="currency.hashing_algorithm"></v-chip>
                </v-chip-group>

                <div id="currency-info-menus">
                    <?php

                    /*
                     * Explorers Menu
                     */
                    if ( ! empty( $route_currency['explorers'] ) ) {
                        ?>
                        <v-menu offset-y v-if="currency.explorerUrls.length">
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn depressed small color="primary" v-bind="attrs" v-on="on">
                                    <v-icon left>mdi-database-search</v-icon>
                                    <?php echo esc_html( __( 'Explorers' ) ); ?>
                                </v-btn>
                            </template>
                            <v-list dense>
                                <v-list-item v-for="(url, index) in currency.explorerUrls" :key="index" :href="url" target="_blank" rel="noopener">
                                    <v-list-item-title>
                                        <v-icon left small>mdi-open-in-new</v-icon>
                                        {{ $root.hostFromUrl(url) }}
                                    </v-list-item-title>
                                </v-list-item>
                            </v-list>
                        </v-menu>
                        <?php
                    }

                    /*
                     * Contracts Menu
                     */
                    if ( ! empty( $route_currency['contracts'] ) ) {
                        ?>
                        <v-menu offset-y :close-on-content-click="false" v-if="currency.platformList.length">
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn depressed small color="primary" v-bind="attrs" v-on="on">
                                    <v-icon left>mdi-script-text-key</v-icon>
                                    <?php echo esc_html( __( 'Contracts' ) ); ?>
                                </v-btn>
                            </template>
                            <v-list dense>
                                <v-list-item v-for="(contract, index) in currency.platformList" :key="index">
                                    <v-list-item-content>
                                        <v-list-item-title v-text="contract[0]"></v-list-item-title>
                                        <v-list-item-subtitle class="font-family-monospace" v-text="contract[1]"></v-list-item-subtitle>
                                    </v-list-item-content>
                                    <v-list-item-action>
                                        <v-btn x-small icon @click="$root.copyToClipboard(contract[1])">
                                            <v-icon>mdi-content-copy</v-icon>
                                        </v-btn>
                                    </v-list-item-action>
                                </v-list-item>
                            </v-list>
                        </v-menu>
                        <?php
                    }

                    /*
                     * Links Menu
                     */
                    if ( ! empty( $route_currency['links'] ) ) {
                        ?>
                        <v-menu offset-y>
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn depressed small color="primary" v-bind="attrs" v-on="on">
                                    <v-icon left>mdi-link-variant</v-icon>
                                    <?php echo esc_html( __( 'Links' ) ); ?>
                                </v-btn>
                            </template>
                            <v-list dense>
                                <v-list-item v-if="currency.url" :href="currency.url" target="_blank" rel="noopener">
                                    <v-list-item-title>
                                        <v-icon left small>mdi-open-in-new</v-icon>
                                        <?php echo esc_html( __( 'Website' ) ); ?>
                                    </v-list-item-title>
                                </v-list-item>

                                <v-list-item v-if="currency.bitcointalkUrl" :href="currency.bitcointalkUrl" target="_blank" rel="noopener">
                                    <v-list-item-title>
                                        <v-icon left small>mdi-open-in-new</v-icon>
                                        <?php echo esc_html( __( 'BitcoinTalk' ) ); ?>
                                    </v-list-item-title>
                                </v-list-item>

                                <v-subheader v-if="currency.twitterUrl || currency.facebookUrl || currency.redditUrl">
                                    <?php echo __( 'Social' ); ?>
                                </v-subheader>
                                <v-list-item v-if="currency.twitterUrl" :href="currency.twitterUrl" target="_blank" rel="noopener">
                                    <v-list-item-title>
                                        <v-icon left small color="#1DA1F2">mdi-twitter</v-icon>
                                        {{ $root.pathFromUrl(currency.twitterUrl, true) }}
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item v-if="currency.facebookUrl" :href="currency.facebookUrl" target="_blank" rel="noopener">
                                    <v-list-item-title>
                                        <v-icon left small color="#1876F2">mdi-facebook</v-icon>
                                        {{ $root.pathFromUrl(currency.facebookUrl, true) }}
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item v-if="currency.redditUrl" :href="currency.redditUrl" target="_blank" rel="noopener">
                                    <v-list-item-title>
                                        <v-icon left small color="#FF4500">mdi-reddit</v-icon>
                                        {{ $root.pathFromUrl(currency.redditUrl, true) }}
                                    </v-list-item-title>
                                </v-list-item>

                                <v-subheader v-if="currency.chatUrls.length || currency.forumUrls.length">
                                    <?php echo __( 'Chats & Forums' ); ?>
                                </v-subheader>
                                <v-list-item v-if="currency.chatUrls.length" v-for="(url,index) in currency.chatUrls" :key="'chat-'+index" :href="url" target="_blank" rel="noopener">
                                    <v-list-item-title>
                                        <v-icon left small>mdi-open-in-new</v-icon>
                                        {{ $root.hostFromUrl(url) }}
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item v-if="currency.forumUrls.length" v-for="(url,index) in currency.forumUrls" :key="'forum-'+index" :href="url" target="_blank" rel="noopener">
                                    <v-list-item-title>
                                        <v-icon left small>mdi-open-in-new</v-icon>
                                        {{ $root.hostFromUrl(url) }}
                                    </v-list-item-title>
                                </v-list-item>

                                <v-subheader v-if="currency.githubUrls.length">
                                    <?php echo __( 'GitHub' ); ?>
                                </v-subheader>
                                <v-list-item v-if="currency.githubUrls.length" v-for="(url,index) in currency.githubUrls" :key="'github-'+index" :href="url" target="_blank" rel="noopener">
                                    <v-list-item-title>
                                        <v-icon left small>mdi-github</v-icon>
                                        {{ $root.pathFromUrl(url, true) }}
                                    </v-list-item-title>
                                </v-list-item>
                            </v-list>
                        </v-menu>
                        <?php
                    }

                    /*
                     * Scores Menu
                     */
                    if ( ! empty( $route_currency['scores'] ) ) {
                        ?>
                        <v-menu offset-y v-if="">
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn depressed small color="primary" v-bind="attrs" v-on="on">
                                    <v-icon left>mdi-numeric</v-icon>
                                    <?php echo esc_html( __( 'Scores' ) ); ?>
                                </v-btn>
                            </template>
                            <v-list dense>
                                <v-list-item v-if="currency.coingecko_score">
                                    <v-list-item-title>
                                        <?php echo esc_html( __( 'CoinGecko Score' ) )?>: <strong>{{ $root.scoreFormat(currency.coingecko_score) }}</strong>
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item v-if="currency.liquidity_score">
                                    <v-list-item-title>
                                        <?php echo esc_html( __( 'Liquidity Score' ) )?>: <strong>{{ $root.scoreFormat(currency.liquidity_score) }}</strong>
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item v-if="currency.developer_score">
                                    <v-list-item-title>
                                        <?php echo esc_html( __( 'Developer Score' ) )?>: <strong>{{ $root.scoreFormat(currency.developer_score) }}</strong>
                                    </v-list-item-title>
                                </v-list-item>
                                <v-list-item v-if="currency.community_score">
                                    <v-list-item-title>
                                        <?php echo esc_html( __( 'Community Score' ) )?>: <strong>{{ $root.scoreFormat(currency.community_score) }}</strong>
                                    </v-list-item-title>
                                </v-list-item>
                            </v-list>
                        </v-menu>
                        <?php
                    }


                    ?>
                </div>
            </v-col>

            <v-col cols="12" sm="6" md="8" v-if="currency.currentPrice">
                <v-row>
                    <?php
                    /*
                     * Price and 24h change
                     */
                    ?>
                    <v-col cols="12" md="6" class="text-sm-right text-md-center">
                        <div class="font-weight-medium mb-2">
                            {{ currency.name }} <?php echo esc_html( __( 'Price' ) ); ?> <span class="text-uppercase">({{ currency.symbol }})</span>
                        </div>
                        <h2 id="currency-price" class="text-h4 text-md-h3">
                            {{ $root.priceFormat(currency.currentPrice) }}
                            <v-chip
                                label
                                v-if="currency.change24hPercent"
                                :color="$root.changeColor(currency.change24hPercent)"
                                :text-color="$root.changeTextColor(currency.change24hPercent)"
                                class="font-weight-medium"
                            >
                                <v-icon class="mx-1" large v-text="$root.changeIcon(currency.change24hPercent)"></v-icon>
                                {{ $root.changeFormat(currency.change24hPercent) }}
                            </v-chip>
                        </h2>
                    </v-col>

                    <?php
                    /*
                     * 24h High & Low
                     */
                    ?>
                    <v-col cols="12" md="6" v-if="currency.high24h && currency.low24h && currency.high24h !== currency.low24h">
                        <div class="d-flex justify-end">
                            <span class="ma-1 font-weight-medium" v-text="$root.priceFormat(currency.high24h)"></span>
                            <v-chip label outlined small class="ma-1"><?php echo esc_html( __( '24h High' ) ); ?></v-chip>
                        </div>
                        <div class="d-flex">
                            <v-slider
                                track-color="grey"
                                :color="$root.changeColor(currency.change24hPercent)"
                                :value="$root.progressValue(currency.currentPrice, currency.high24h, currency.low24h)"
                                readonly
                                dense
                                hide-details
                            ></v-slider>
                        </div>
                        <div class="d-flex justify-start">
                            <v-chip label outlined small class="ma-1"><?php echo esc_html( __( '24h Low' ) ); ?></v-chip>
                            <span class="ma-1 font-weight-medium" v-text="$root.priceFormat(currency.low24h)"></span>
                        </div>
                    </v-col>
                </v-row>
            </v-col>
        </v-row>

        <?php
        /*
         * Converter
         */
        if ( ! empty( $route_currency['converter'] ) ) {
            ?>
            <div class="my-6">
                <v-divider></v-divider>

                <gc-currency-converter
                    :base-symbol="currency.symbol"
                    :quote-symbol="$root.vsCurrencyId"
                    :rate="currency.currentPrice"
                    :buy-href="currency.url"
                    :sell-href="currency.url"
                    class="py-4"
                ></gc-currency-converter>

                <v-divider></v-divider>
            </div>
            <?php
        }
        ?>

        <?php
        /*
         * Indicators & Information
         */
        ?>
        <v-row class="my-6">
            <v-col cols="6" sm="4" md="3" v-if="currency.marketCap">
                <div class="font-weight-medium text--secondary"><?php echo esc_html( __( 'Market Cap' ) ); ?></div>
                <div class="font-weight-bold" v-text="$root.marketCapFormat(currency.marketCap)"></div>
                <div
                    class="font-weight-bold"
                    :class="$root.changeColorClass(currency.marketCapChange24hPercent)"
                    v-text="$root.marketCapFormat(currency.marketCapChange24h)"
                ></div>

            </v-col>
            <v-col cols="6" sm="4" md="3" v-if="currency.fullyDilutedValuation">
                <div class="font-weight-medium text--secondary"><?php echo esc_html( __( 'Fully Diluted Market Cap' ) ); ?></div>
                <div class="font-weight-bold" v-text="$root.marketCapFormat(currency.fullyDilutedValuation)"></div>
            </v-col>
            <v-col cols="6" sm="4" md="3" v-if="currency.totalVolume">
                <div class="font-weight-medium text--secondary"><?php echo esc_html( __( 'Volume 24h' ) ); ?></div>
                <div class="font-weight-bold" v-text="$root.marketCapFormat(currency.totalVolume)"></div>
            </v-col>
            <v-col cols="6" sm="4" md="3" v-if="currency.volumePerMarketCap">
                <div class="font-weight-medium text--secondary"><?php echo esc_html( __( 'Volume / Market Cap' ) ); ?></div>
                <div class="font-weight-bold" v-text="$root.ratioFormat(currency.volumePerMarketCap)"></div>
            </v-col>
            <v-col cols="6" sm="4" md="3" v-if="currency.circulatingSupply">
                <div class="font-weight-medium text--secondary"><?php echo esc_html( __( 'Circulating Supply' ) ); ?></div>
                <div>
                    <span class="font-weight-bold">
                        {{ $root.bigNumberFormat(currency.circulatingSupply) }}
                        <span class="text-uppercase" v-text="currency.symbol"></span>
                    </span>
                    <v-chip x-small label class="font-weight-medium" v-if="currency.totalSupply">
                        {{ $root.progressValue(currency.circulatingSupply, currency.totalSupply, 0, true) }}%
                    </v-chip>
                </div>
                <div class="d-flex align-content-space-between">
                    <v-progress-linear
                        v-if="currency.totalSupply"
                        height="8"
                        :value="$root.progressValue(currency.circulatingSupply, currency.totalSupply)"
                    >
                    </v-progress-linear>
                </div>
            </v-col>
            <v-col cols="6" sm="4" md="3" v-if="currency.totalSupply">
                <div class="font-weight-medium text--secondary"><?php echo esc_html( __( 'Max Supply' ) ); ?></div>
                <div class="font-weight-bold">
                    {{ $root.bigNumberFormat(currency.totalSupply) }}
                    <span class="text-uppercase" v-text="currency.symbol"></span>
                </div>
            </v-col>
            <v-col cols="6" sm="4" md="3" v-if="currency.genesis_date">
                <div class="font-weight-medium text--secondary"><?php echo esc_html( __( 'Genesis Date' ) ); ?></div>
                <div class="font-weight-bold" v-text="$root.dateFormat(currency.genesis_date)"></div>
            </v-col>
            <v-col cols="6" sm="4" md="3" v-if="currency.block_time_in_minutes">
                <div class="font-weight-medium text--secondary"><?php echo esc_html( __( 'Block Time' ) ); ?></div>
                <div class="font-weight-bold">
                    {{ currency.block_time_in_minutes }} <?php echo esc_html( __( 'Minutes' ) ); ?>
                </div>
            </v-col>
        </v-row>

        <v-divider></v-divider>

        <?php
        /*
         * Chart, Market & Historical Data tabs
         */
        ?>
        <v-card flat>
            <v-tabs v-model="tabsModel" grow :right="$root.isMobileUserAgent" :show-arrows="!$root.isMobileUserAgent">
                <?php
                    foreach ( $route_currency['tabs'] as $tab ) {
                        if ( ! empty( $tab['show'] ) ) {
                            ?>
                            <v-tab key="<?php echo esc_attr( $tab['key'] ); ?>" value="<?php echo esc_attr( $tab['key'] ); ?>">
                                <?php if ( ! empty( $tab['icon'] ) ): ?>
                                    <v-icon left><?php echo esc_html( $tab['icon'] ); ?></v-icon>
                                <?php endif; ?>
                                <?php echo esc_html( $tab['text'] ); ?>
                            </v-tab>
                            <?php
                        }
                    }
                ?>
            </v-tabs>
            <v-card-text>
                <v-tabs-items v-model="tabsModel">
                    <?php
                        foreach ( $route_currency['tabs'] as $tab ) {
                            if ( ! empty( $tab['show'] ) && ! empty( $tab['template'] ) ) {
                                include $tab['template'];
                            }
                        }
                    ?>
                </v-tabs-items>
            </v-card-text>
        </v-card>

    </v-container>
</section>
