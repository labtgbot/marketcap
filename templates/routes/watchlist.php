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

$frontend_options['watchlist']['title'] = __( 'Watchlist' );

?>
<section class="py-8 py-sm-10">
    <v-container id="watchlist" fluid>
        <div class="watchlist-header mb-5">
            <div>
                <h1 class="text-h4 text-sm-h3 font-weight-bold mb-2">
                    <?php echo esc_html( $frontend_options['watchlist']['title'] ); ?>
                </h1>
                <div class="d-flex flex-wrap align-center">
                    <v-chip small label class="mr-2 mb-2">
                        <v-icon small left>mdi-star</v-icon>
                        {{ entries.length }} <?php echo esc_html( __( 'assets' ) ); ?>
                    </v-chip>
                    <v-chip small label class="mr-2 mb-2">
                        <v-icon small left>mdi-database</v-icon>
                        {{ storageModeLabel }}
                    </v-chip>
                    <v-chip small label :color="isStale ? 'warning' : 'success'" text-color="white" class="mb-2" v-if="!isEmpty && marketMeta">
                        <v-icon small left>mdi-clock-check-outline</v-icon>
                        {{ freshnessLabel }}
                    </v-chip>
                </div>
            </div>
            <div class="watchlist-route-actions">
                <v-btn color="primary" depressed :to="{name:'markets'}">
                    <v-icon left>mdi-table-large</v-icon>
                    <?php echo esc_html( __( 'Markets' ) ); ?>
                </v-btn>
                <v-btn outlined color="primary" :to="{name:'currencies'}">
                    <v-icon left>mdi-pulse</v-icon>
                    <?php echo esc_html( __( 'Market Pulse' ) ); ?>
                </v-btn>
                <v-btn outlined color="primary" @click="shareWatchlist">
                    <v-icon left>mdi-share-variant</v-icon>
                    <?php echo esc_html( __( 'Share' ) ); ?>
                </v-btn>
            </div>
        </div>

        <gc-share-card class="mb-4" dense :card="watchlistShareCard"></gc-share-card>

        <div class="watchlist-toolbar mb-4" v-if="!isEmpty">
            <v-select
                v-model="sortKey"
                :items="sortOptions"
                label="<?php echo esc_attr( __( 'Sort' ) ); ?>"
                dense
                outlined
                hide-details
                class="watchlist-sort-control"
            ></v-select>
            <v-btn
                icon
                class="watchlist-icon-button"
                :aria-label="sortDirection === 'asc' ? '<?php echo esc_attr( __( 'Sort descending' ) ); ?>' : '<?php echo esc_attr( __( 'Sort ascending' ) ); ?>'"
                :title="sortDirection === 'asc' ? '<?php echo esc_attr( __( 'Sort descending' ) ); ?>' : '<?php echo esc_attr( __( 'Sort ascending' ) ); ?>'"
                @click="toggleSortDirection"
            >
                <v-icon v-text="sortIcon()"></v-icon>
            </v-btn>
        </div>

        <v-progress-linear v-if="loading" indeterminate color="primary" height="3" class="mb-4" aria-label="<?php echo esc_attr( __( 'Loading' ) ); ?>"></v-progress-linear>

        <v-alert v-if="marketError && !isEmpty" type="warning" outlined class="mb-4">
            <?php echo esc_html( __( 'Market data is temporarily unavailable. Saved watchlist entries remain visible.' ) ); ?>
        </v-alert>

        <v-alert v-else-if="isStale && !isEmpty" type="warning" outlined class="mb-4">
            <?php echo esc_html( __( 'Market data is stale. Use the freshness label before acting on prices.' ) ); ?>
        </v-alert>

        <v-alert v-if="!loading && isEmpty" type="info" outlined class="mb-4">
            <?php echo esc_html( __( 'No watched coins yet. Add coins from Market Pulse, market rows, search results, or coin detail pages.' ) ); ?>
        </v-alert>

        <v-row class="mb-4 ai-insight-grid" v-if="!isEmpty">
            <v-col cols="12">
                <gc-ai-insight-card
                    title="<?php echo esc_attr( __( 'AI watchlist digest' ) ); ?>"
                    icon="mdi-star-check-outline"
                    :context="watchlistInsightContext"
                    source-route="watchlist"
                ></gc-ai-insight-card>
            </v-col>
        </v-row>

        <v-row dense v-if="!isEmpty">
            <v-col cols="12" sm="6" lg="4" v-for="currency in sortedCurrencies" :key="currency.id">
                <v-card class="watchlist-coin-card" outlined>
                    <v-card-title class="text-subtitle-1 font-weight-bold">
                        <v-avatar size="36" color="white" class="mr-3">
                            <v-img v-if="currency.image" :src="currency.image" :alt="currency.name"></v-img>
                            <span v-else class="text-uppercase" v-text="currency.symbol || currency.id.charAt(0)"></span>
                        </v-avatar>
                        <div class="watchlist-coin-title">
                            <div class="text-truncate" v-text="currency.name"></div>
                            <div class="text-caption text--secondary text-uppercase" v-text="currency.watchlist_symbol || currency.symbol"></div>
                        </div>
                        <v-spacer></v-spacer>
                        <v-btn
                            icon
                            small
                            class="watchlist-icon-button"
                            color="primary"
                            :aria-label="'Remove ' + currency.name + ' from Watchlist'"
                            :title="'Remove ' + currency.name + ' from Watchlist'"
                            @click="removeFromWatchlist(currency)"
                        >
                            <v-icon>mdi-star</v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-card-text>
                        <div class="watchlist-market-grid">
                            <div>
                                <div class="text-caption text--secondary"><?php echo esc_html( __( 'Price' ) ); ?></div>
                                <div class="font-weight-bold" v-text="priceLabel(currency.current_price)"></div>
                            </div>
                            <div>
                                <div class="text-caption text--secondary"><?php echo esc_html( __( '24h' ) ); ?></div>
                                <div class="font-weight-bold" :class="$root.changeColorClass(currency.price_change_percentage_24h_in_currency)">
                                    {{ changeLabel(currency.price_change_percentage_24h_in_currency) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-caption text--secondary"><?php echo esc_html( __( 'Market cap' ) ); ?></div>
                                <div class="font-weight-bold" v-text="marketCapLabel(currency.market_cap)"></div>
                            </div>
                            <div>
                                <div class="text-caption text--secondary"><?php echo esc_html( __( 'Rank' ) ); ?></div>
                                <div class="font-weight-bold">{{ currency.market_cap_rank ? '#' + currency.market_cap_rank : 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="text-caption text--secondary mt-3">
                            <?php echo esc_html( __( 'Added' ) ); ?> {{ relativeTime(currency.added_at) }}
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn text color="primary" :to="currency.route">
                            <v-icon left>mdi-open-in-app</v-icon>
                            <?php echo esc_html( __( 'Open' ) ); ?>
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</section>
