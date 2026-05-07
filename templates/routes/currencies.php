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

$frontend_options['currencies']['title'] = __( 'Market Pulse' );
$frontend_options['currencies']['description'] = __( 'Fast market context for TONBANKCARD users across web and Telegram.' );
$frontend_options['currencies']['perPage'] = 50;
$frontend_options['currencies']['priceChanges'] = [
    '24h',
    '7d',
    '30d',
];
$frontend_options['currencies']['order'] = 'market_cap_desc';
$frontend_options['currencies']['tonApiBaseUrl'] = site_url( 'api/ton/assets' );

?>
<section class="market-pulse py-6 py-sm-8">
    <v-container id="market-pulse" fluid>
        <div class="market-pulse-hero mb-5">
            <div>
                <div class="overline primary--text font-weight-bold mb-2">TONBANKCARD</div>
                <h1 class="text-h4 text-sm-h3 font-weight-bold mb-3">
                    <?php echo esc_html( $frontend_options['currencies']['title'] ); ?>
                </h1>
                <p class="text-subtitle-1 text--secondary mb-0">
                    <?php echo esc_html( $frontend_options['currencies']['description'] ); ?>
                </p>
            </div>
            <div class="market-pulse-actions">
                <v-btn color="primary" depressed @click="focusSearch">
                    <v-icon left>mdi-magnify</v-icon>
                    <?php echo esc_html( __( 'Search' ) ); ?>
                </v-btn>
                <v-btn outlined color="primary" @click="shareMarketPulse">
                    <v-icon left>mdi-share-variant</v-icon>
                    <?php echo esc_html( __( 'Share' ) ); ?>
                </v-btn>
                <v-btn :to="{name:'watchlist'}" outlined color="primary">
                    <v-icon left>mdi-star-outline</v-icon>
                    <?php echo esc_html( __( 'Watchlist' ) ); ?>
                </v-btn>
                <v-btn :to="{name:'ton'}" outlined color="primary">
                    <v-icon left>mdi-diamond-stone</v-icon>
                    <?php echo esc_html( __( 'TON view' ) ); ?>
                </v-btn>
                <v-btn :to="{name:'markets'}" text color="primary">
                    <v-icon left>mdi-table-large</v-icon>
                    <?php echo esc_html( __( 'Full table' ) ); ?>
                </v-btn>
            </div>
        </div>

        <gc-share-card class="mb-4" dense :card="marketPulseShareCard"></gc-share-card>

        <div class="d-flex flex-wrap align-center mb-4">
            <v-chip small label :color="freshnessColor" text-color="white" class="mr-2 mb-2">
                <v-icon small left>mdi-clock-check-outline</v-icon>
                {{ freshnessLabel }}
            </v-chip>
            <v-chip small label class="mr-2 mb-2">
                <v-icon small left>mdi-cash-multiple</v-icon>
                {{ $root.vsCurrency.id }}
            </v-chip>
            <v-chip small label class="mb-2" v-if="marketCurrencies.length">
                <v-icon small left>mdi-database-check</v-icon>
                {{ marketCurrencies.length }} <?php echo esc_html( __( 'assets' ) ); ?>
            </v-chip>
        </div>

        <v-progress-linear v-if="loading" indeterminate color="primary" height="3" class="mb-4"></v-progress-linear>

        <v-alert v-if="upstreamError" type="error" outlined class="mb-4">
            <?php echo esc_html( __( 'Upstream market data is unavailable. Try again after the provider recovers.' ) ); ?>
        </v-alert>
        <v-alert v-else-if="partialError" type="warning" outlined class="mb-4">
            <?php echo esc_html( __( 'Some market pulse sections could not refresh. Available sections remain visible.' ) ); ?>
        </v-alert>
        <v-alert v-else-if="isStale" type="warning" outlined class="mb-4">
            <?php echo esc_html( __( 'Market data is stale. Use the freshness label before acting on prices.' ) ); ?>
        </v-alert>

        <v-row dense class="mb-4">
            <v-col cols="12" sm="6" md="3" v-for="stat in globalStats" :key="stat.label">
                <v-sheet class="market-pulse-stat pa-4" rounded outlined>
                    <div class="d-flex align-center justify-space-between mb-2">
                        <span class="text-caption text-uppercase text--secondary" v-text="stat.label"></span>
                        <v-icon color="primary" small v-text="stat.icon"></v-icon>
                    </div>
                    <div class="market-pulse-stat-value text-h6 font-weight-bold" v-text="stat.value || '<?php echo esc_attr( __( 'Loading' ) ); ?>'"></div>
                </v-sheet>
            </v-col>
        </v-row>

        <v-alert v-if="!loading && !upstreamError && !marketCurrencies.length" type="info" outlined class="mb-4">
            <?php echo esc_html( __( 'No market data is available right now.' ) ); ?>
        </v-alert>

        <v-row dense>
            <v-col cols="12" md="6" lg="4">
                <v-card class="market-pulse-panel" outlined>
                    <v-card-title class="text-subtitle-1 font-weight-bold">
                        <v-icon left color="primary">mdi-diamond-stone</v-icon>
                        <?php echo esc_html( __( 'TON ecosystem pulse' ) ); ?>
                        <v-spacer></v-spacer>
                        <v-btn icon small :to="{name:'ton'}" aria-label="TON view">
                            <v-icon>mdi-arrow-right</v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-list dense v-if="tonCurrencies.length">
                        <v-list-item v-for="currency in tonCurrencies" :key="currency.id" :to="currency.route">
                            <v-list-item-avatar size="32" color="white">
                                <v-img v-if="currency.image" :src="currency.image" :alt="currency.name"></v-img>
                            </v-list-item-avatar>
                            <v-list-item-content>
                                <v-list-item-title v-text="currency.name"></v-list-item-title>
                                <v-list-item-subtitle class="text-uppercase" v-text="currency.symbol"></v-list-item-subtitle>
                            </v-list-item-content>
                            <v-list-item-action class="market-pulse-row-actions">
                                <v-btn
                                    icon
                                    small
                                    class="watchlist-icon-button"
                                    :color="isWatched(currency) ? 'primary' : undefined"
                                    :aria-label="watchlistLabel(currency)"
                                    :title="watchlistLabel(currency)"
                                    @click.stop.prevent="toggleWatchlist(currency)"
                                >
                                    <v-icon v-text="watchlistIcon(currency)"></v-icon>
                                </v-btn>
                                <span class="font-weight-bold" :class="$root.changeColorClass(currency.price_change_percentage_24h_in_currency)">
                                    {{ $root.changeFormat(currency.price_change_percentage_24h_in_currency) || 'N/A' }}
                                </span>
                            </v-list-item-action>
                        </v-list-item>
                    </v-list>
                    <v-card-text v-else>
                        <?php echo esc_html( __( 'TON assets will appear here when market data is available.' ) ); ?>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" md="6" lg="4">
                <v-card class="market-pulse-panel" outlined>
                    <v-card-title class="text-subtitle-1 font-weight-bold">
                        <v-icon left color="high">mdi-trending-up</v-icon>
                        <?php echo esc_html( __( 'Top gainers' ) ); ?>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-list dense v-if="topGainers.length">
                        <v-list-item v-for="currency in topGainers" :key="currency.id" :to="currency.route">
                            <v-list-item-avatar size="32" color="white">
                                <v-img v-if="currency.image" :src="currency.image" :alt="currency.name"></v-img>
                            </v-list-item-avatar>
                            <v-list-item-content>
                                <v-list-item-title v-text="currency.name"></v-list-item-title>
                                <v-list-item-subtitle v-text="$root.priceFormat(currency.current_price)"></v-list-item-subtitle>
                            </v-list-item-content>
                            <v-list-item-action class="market-pulse-row-actions">
                                <v-btn
                                    icon
                                    small
                                    class="watchlist-icon-button"
                                    :color="isWatched(currency) ? 'primary' : undefined"
                                    :aria-label="watchlistLabel(currency)"
                                    :title="watchlistLabel(currency)"
                                    @click.stop.prevent="toggleWatchlist(currency)"
                                >
                                    <v-icon v-text="watchlistIcon(currency)"></v-icon>
                                </v-btn>
                                <span class="high--text font-weight-bold">
                                    {{ $root.changeFormat(currency.price_change_percentage_24h_in_currency) }}
                                </span>
                            </v-list-item-action>
                        </v-list-item>
                    </v-list>
                    <v-card-text v-else>
                        <?php echo esc_html( __( 'No positive movers are available.' ) ); ?>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" md="6" lg="4">
                <v-card class="market-pulse-panel" outlined>
                    <v-card-title class="text-subtitle-1 font-weight-bold">
                        <v-icon left color="low">mdi-trending-down</v-icon>
                        <?php echo esc_html( __( 'Top losers' ) ); ?>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-list dense v-if="topLosers.length">
                        <v-list-item v-for="currency in topLosers" :key="currency.id" :to="currency.route">
                            <v-list-item-avatar size="32" color="white">
                                <v-img v-if="currency.image" :src="currency.image" :alt="currency.name"></v-img>
                            </v-list-item-avatar>
                            <v-list-item-content>
                                <v-list-item-title v-text="currency.name"></v-list-item-title>
                                <v-list-item-subtitle v-text="$root.priceFormat(currency.current_price)"></v-list-item-subtitle>
                            </v-list-item-content>
                            <v-list-item-action class="market-pulse-row-actions">
                                <v-btn
                                    icon
                                    small
                                    class="watchlist-icon-button"
                                    :color="isWatched(currency) ? 'primary' : undefined"
                                    :aria-label="watchlistLabel(currency)"
                                    :title="watchlistLabel(currency)"
                                    @click.stop.prevent="toggleWatchlist(currency)"
                                >
                                    <v-icon v-text="watchlistIcon(currency)"></v-icon>
                                </v-btn>
                                <span class="low--text font-weight-bold">
                                    {{ $root.changeFormat(currency.price_change_percentage_24h_in_currency) }}
                                </span>
                            </v-list-item-action>
                        </v-list-item>
                    </v-list>
                    <v-card-text v-else>
                        <?php echo esc_html( __( 'No negative movers are available.' ) ); ?>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" md="6" lg="4">
                <v-card class="market-pulse-panel" outlined>
                    <v-card-title class="text-subtitle-1 font-weight-bold">
                        <v-icon left color="primary">mdi-fire</v-icon>
                        <?php echo esc_html( __( 'Trending coins' ) ); ?>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-list dense v-if="trendingCoins.length">
                        <v-list-item v-for="coin in trendingCoins" :key="coin.id" :to="coin.route">
                            <v-list-item-avatar size="32" color="white">
                                <v-img v-if="coin.small || coin.image" :src="coin.small || coin.image" :alt="coin.name"></v-img>
                            </v-list-item-avatar>
                            <v-list-item-content>
                                <v-list-item-title v-text="coin.name"></v-list-item-title>
                                <v-list-item-subtitle class="text-uppercase" v-text="coin.symbol"></v-list-item-subtitle>
                            </v-list-item-content>
                            <v-list-item-action class="market-pulse-row-actions">
                                <v-btn
                                    icon
                                    small
                                    class="watchlist-icon-button"
                                    :color="isWatched(coin) ? 'primary' : undefined"
                                    :aria-label="watchlistLabel(coin)"
                                    :title="watchlistLabel(coin)"
                                    @click.stop.prevent="toggleWatchlist(coin)"
                                >
                                    <v-icon v-text="watchlistIcon(coin)"></v-icon>
                                </v-btn>
                            </v-list-item-action>
                        </v-list-item>
                    </v-list>
                    <v-card-text v-else>
                        <?php echo esc_html( __( 'Trending coins are loading or temporarily unavailable.' ) ); ?>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" md="6" lg="4">
                <v-card class="market-pulse-panel" outlined>
                    <v-card-title class="text-subtitle-1 font-weight-bold">
                        <v-icon left color="primary">mdi-star-outline</v-icon>
                        <?php echo esc_html( __( 'Watchlist preview' ) ); ?>
                        <v-spacer></v-spacer>
                        <v-btn icon small :to="{name:'watchlist'}" aria-label="Watchlist">
                            <v-icon>mdi-arrow-right</v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-list dense v-if="watchlistCurrencies.length">
                        <v-list-item v-for="currency in watchlistCurrencies" :key="currency.id" :to="currency.route">
                            <v-list-item-avatar size="32" color="white">
                                <v-img v-if="currency.image" :src="currency.image" :alt="currency.name"></v-img>
                            </v-list-item-avatar>
                            <v-list-item-content>
                                <v-list-item-title v-text="currency.name"></v-list-item-title>
                                <v-list-item-subtitle v-text="$root.priceFormat(currency.current_price)"></v-list-item-subtitle>
                            </v-list-item-content>
                            <v-list-item-action class="market-pulse-row-actions">
                                <v-btn
                                    icon
                                    small
                                    class="watchlist-icon-button"
                                    color="primary"
                                    :aria-label="watchlistLabel(currency)"
                                    :title="watchlistLabel(currency)"
                                    @click.stop.prevent="toggleWatchlist(currency)"
                                >
                                    <v-icon v-text="watchlistIcon(currency)"></v-icon>
                                </v-btn>
                            </v-list-item-action>
                        </v-list-item>
                    </v-list>
                    <v-card-text v-else>
                        <div class="text--secondary mb-3">
                            <?php echo esc_html( __( 'No watched coins yet.' ) ); ?>
                        </div>
                        <v-btn small outlined color="primary" :to="{name:'watchlist'}">
                            <?php echo esc_html( __( 'Open watchlist' ) ); ?>
                        </v-btn>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" md="6" lg="4">
                <gc-ai-insight-card
                    class="market-pulse-panel"
                    title="<?php echo esc_attr( __( 'AI market summary' ) ); ?>"
                    icon="mdi-brain"
                    :context="marketInsightContext"
                    source-route="market_pulse"
                ></gc-ai-insight-card>
            </v-col>
        </v-row>
    </v-container>
</section>
