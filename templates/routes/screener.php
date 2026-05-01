<?php
/**
 * TONBANKCARD public advanced screener route.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$frontend_options['screener']['title'] = __( 'Crypto Screener' );
$frontend_options['screener']['apiBaseUrl'] = site_url( 'api/screener/markets' );
$frontend_options['screener']['presetsApiBaseUrl'] = site_url( 'api/screener/presets' );
$frontend_options['screener']['perPage'] = 100;
$frontend_options['screener']['tableHeaders'] = [
    [
        'text'     => '#',
        'value'    => 'market_cap_rank',
        'sortable' => TRUE,
        'align'    => 'start',
        'width'    => 72,
        'show'     => TRUE,
    ],
    [
        'text'     => 'Name',
        'value'    => 'name',
        'sortable' => TRUE,
        'align'    => 'start',
        'show'     => TRUE,
    ],
    [
        'text'     => 'Watch',
        'value'    => 'watchlist',
        'sortable' => FALSE,
        'align'    => 'center',
        'width'    => 72,
        'show'     => TRUE,
    ],
    [
        'text'     => 'Price',
        'value'    => 'current_price',
        'sortable' => TRUE,
        'align'    => 'end',
        'show'     => TRUE,
    ],
    [
        'text'     => '24h',
        'value'    => 'price_change_percentage_24h_in_currency',
        'sortable' => TRUE,
        'align'    => 'end',
        'show'     => TRUE,
    ],
    [
        'text'     => '7d',
        'value'    => 'price_change_percentage_7d_in_currency',
        'sortable' => TRUE,
        'align'    => 'end',
        'show'     => TRUE,
    ],
    [
        'text'     => '30d',
        'value'    => 'price_change_percentage_30d_in_currency',
        'sortable' => TRUE,
        'align'    => 'end',
        'show'     => TRUE,
    ],
    [
        'text'     => 'Market Cap',
        'value'    => 'market_cap',
        'sortable' => TRUE,
        'align'    => 'end',
        'show'     => TRUE,
    ],
    [
        'text'     => 'Volume',
        'value'    => 'total_volume',
        'sortable' => TRUE,
        'align'    => 'end',
        'show'     => TRUE,
    ],
    [
        'text'     => 'Sentiment',
        'value'    => 'sentiment_score',
        'sortable' => TRUE,
        'align'    => 'end',
        'show'     => TRUE,
    ],
    [
        'text'     => 'Exchange',
        'value'    => 'exchange_available',
        'sortable' => TRUE,
        'align'    => 'center',
        'show'     => TRUE,
    ],
    [
        'text'     => 'TON',
        'value'    => 'ton_asset',
        'sortable' => FALSE,
        'align'    => 'center',
        'show'     => TRUE,
    ],
];
$frontend_options['screener']['categoryOptions'] = [
    [ 'text' => 'All categories', 'value' => '' ],
    [ 'text' => 'Stablecoins', 'value' => 'stablecoins' ],
    [ 'text' => 'DeFi', 'value' => 'decentralized-finance-defi' ],
    [ 'text' => 'Layer 1', 'value' => 'layer-1' ],
    [ 'text' => 'Exchange tokens', 'value' => 'exchange-based-tokens' ],
    [ 'text' => 'Gaming', 'value' => 'gaming' ],
];
$frontend_options['screener']['exchangeOptions'] = [
    [ 'text' => 'Any exchange', 'value' => '' ],
    [ 'text' => 'Binance', 'value' => 'binance' ],
    [ 'text' => 'Bybit', 'value' => 'bybit_spot' ],
    [ 'text' => 'OKX', 'value' => 'okex' ],
    [ 'text' => 'KuCoin', 'value' => 'kucoin' ],
    [ 'text' => 'Gate.io', 'value' => 'gate' ],
];
$frontend_options['screener']['tonTagOptions'] = [
    [ 'text' => 'All assets', 'value' => '' ],
    [ 'text' => 'TON ecosystem', 'value' => 'ton_ecosystem' ],
    [ 'text' => 'Stablecoins', 'value' => 'stablecoin' ],
    [ 'text' => 'Jettons', 'value' => 'jetton' ],
    [ 'text' => 'TON DeFi', 'value' => 'defi' ],
    [ 'text' => 'Wallets', 'value' => 'wallet' ],
    [ 'text' => 'Gaming', 'value' => 'gaming' ],
    [ 'text' => 'Verified', 'value' => 'verified' ],
    [ 'text' => 'Review queue', 'value' => 'unverified' ],
];

?>
<section id="screener" class="mt-6 mb-16">
    <v-container fluid>
        <div class="screener-header mb-4">
            <div class="screener-title-block">
                <h1 class="text-h4 text-sm-h4 mb-1">
                    <?php echo esc_html( $frontend_options['screener']['title'] ); ?>
                </h1>
                <div class="text-body-2 text--secondary" v-text="resultSummaryText"></div>
            </div>
            <div class="screener-actions">
                <v-btn class="d-md-none" icon :aria-label="'Open filters'" title="Open filters" @click="filterDrawer = true">
                    <v-icon>mdi-filter-variant</v-icon>
                </v-btn>
                <v-btn icon :aria-label="'Refresh screener'" title="Refresh screener" :loading="loading" @click="fetchScreenerResults">
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </div>
        </div>

        <v-alert v-if="errorMessage" type="warning" dense text class="mb-4" v-text="errorMessage"></v-alert>

        <v-navigation-drawer
            v-model="filterDrawer"
            temporary
            right
            fixed
            width="360"
            class="screener-filter-drawer"
        >
            <div class="screener-drawer-header">
                <strong><?php echo esc_html( 'Filters' ); ?></strong>
                <v-btn icon small :aria-label="'Close filters'" title="Close filters" @click="filterDrawer = false">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </div>
            <div class="screener-drawer-body">
                <div class="screener-filter-grid">
                    <v-select dense outlined hide-details="auto" label="<?php echo esc_attr( 'Category' ); ?>" :items="categoryOptions" v-model="filters.category"></v-select>
                    <v-select dense outlined hide-details="auto" label="<?php echo esc_attr( 'Exchange' ); ?>" :items="exchangeOptions" v-model="filters.exchange"></v-select>
                    <v-select dense outlined hide-details="auto" label="<?php echo esc_attr( 'TON tag' ); ?>" :items="tonTagOptions" v-model="filters.ton_tag"></v-select>
                    <v-select dense outlined hide-details="auto" label="<?php echo esc_attr( 'Watchlist' ); ?>" :items="watchlistFilterOptions" v-model="filters.watchlist"></v-select>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Market cap min' ); ?>" v-model="filters.market_cap_min"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Market cap max' ); ?>" v-model="filters.market_cap_max"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Volume min' ); ?>" v-model="filters.volume_min"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Volume max' ); ?>" v-model="filters.volume_max"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Rank max' ); ?>" v-model="filters.rank_max"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( '24h min %' ); ?>" v-model="filters.change_24h_min"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( '7d min %' ); ?>" v-model="filters.change_7d_min"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( '30d min %' ); ?>" v-model="filters.change_30d_min"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Sentiment min' ); ?>" v-model="filters.sentiment_min"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Sentiment max' ); ?>" v-model="filters.sentiment_max"></v-text-field>
                </div>
                <div class="screener-filter-actions mt-4">
                    <v-btn color="primary" depressed @click="applyFilters">
                        <v-icon left>mdi-check</v-icon>
                        <?php echo esc_html( 'Apply' ); ?>
                    </v-btn>
                    <v-btn text @click="resetFilters">
                        <v-icon left>mdi-filter-remove-outline</v-icon>
                        <?php echo esc_html( 'Reset' ); ?>
                    </v-btn>
                </div>
            </div>
        </v-navigation-drawer>

        <div class="screener-shell">
            <aside class="screener-filter-panel d-none d-md-block">
                <div class="screener-panel-heading">
                    <strong><?php echo esc_html( 'Filters' ); ?></strong>
                    <v-btn small text :disabled="!hasActiveFilters" @click="resetFilters">
                        <v-icon left small>mdi-filter-remove-outline</v-icon>
                        <?php echo esc_html( 'Reset' ); ?>
                    </v-btn>
                </div>
                <div class="screener-filter-grid">
                    <v-select dense outlined hide-details="auto" label="<?php echo esc_attr( 'Category' ); ?>" :items="categoryOptions" v-model="filters.category" @change="applyFilters"></v-select>
                    <v-select dense outlined hide-details="auto" label="<?php echo esc_attr( 'Exchange' ); ?>" :items="exchangeOptions" v-model="filters.exchange" @change="applyFilters"></v-select>
                    <v-select dense outlined hide-details="auto" label="<?php echo esc_attr( 'TON tag' ); ?>" :items="tonTagOptions" v-model="filters.ton_tag" @change="applyFilters"></v-select>
                    <v-select dense outlined hide-details="auto" label="<?php echo esc_attr( 'Watchlist' ); ?>" :items="watchlistFilterOptions" v-model="filters.watchlist" @change="applyFilters"></v-select>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Market cap min' ); ?>" v-model="filters.market_cap_min" @keyup.enter="applyFilters" @blur="applyFilters"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Market cap max' ); ?>" v-model="filters.market_cap_max" @keyup.enter="applyFilters" @blur="applyFilters"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Volume min' ); ?>" v-model="filters.volume_min" @keyup.enter="applyFilters" @blur="applyFilters"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Volume max' ); ?>" v-model="filters.volume_max" @keyup.enter="applyFilters" @blur="applyFilters"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Rank max' ); ?>" v-model="filters.rank_max" @keyup.enter="applyFilters" @blur="applyFilters"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( '24h min %' ); ?>" v-model="filters.change_24h_min" @keyup.enter="applyFilters" @blur="applyFilters"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( '7d min %' ); ?>" v-model="filters.change_7d_min" @keyup.enter="applyFilters" @blur="applyFilters"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( '30d min %' ); ?>" v-model="filters.change_30d_min" @keyup.enter="applyFilters" @blur="applyFilters"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Sentiment min' ); ?>" v-model="filters.sentiment_min" @keyup.enter="applyFilters" @blur="applyFilters"></v-text-field>
                    <v-text-field dense outlined clearable hide-details="auto" type="number" label="<?php echo esc_attr( 'Sentiment max' ); ?>" v-model="filters.sentiment_max" @keyup.enter="applyFilters" @blur="applyFilters"></v-text-field>
                </div>
                <v-btn block color="primary" depressed class="mt-4" :loading="loading" @click="applyFilters">
                    <v-icon left>mdi-table-search</v-icon>
                    <?php echo esc_html( 'Run Screen' ); ?>
                </v-btn>
            </aside>

            <div class="screener-main">
                <div class="screener-summary-strip mb-3">
                    <div class="screener-stat">
                        <span><?php echo esc_html( 'Matches' ); ?></span>
                        <strong v-text="summary.result_count || 0"></strong>
                    </div>
                    <div class="screener-stat">
                        <span><?php echo esc_html( 'Watched' ); ?></span>
                        <strong v-text="summary.watched_count || 0"></strong>
                    </div>
                    <div class="screener-stat">
                        <span><?php echo esc_html( 'TON' ); ?></span>
                        <strong v-text="summary.ton_count || 0"></strong>
                    </div>
                    <div class="screener-stat">
                        <span><?php echo esc_html( 'Sentiment' ); ?></span>
                        <strong v-text="sentimentSummary"></strong>
                    </div>
                </div>

                <div class="screener-presets mb-3">
                    <v-select
                        dense
                        outlined
                        hide-details="auto"
                        class="screener-preset-select"
                        label="<?php echo esc_attr( 'Saved presets' ); ?>"
                        :items="presetOptions"
                        :disabled="!presetSyncEnabled || !presets.length"
                        v-model="selectedPresetId"
                        @change="applyPreset"
                    ></v-select>
                    <v-text-field
                        dense
                        outlined
                        hide-details="auto"
                        class="screener-preset-name"
                        label="<?php echo esc_attr( 'Preset name' ); ?>"
                        v-model="presetName"
                        :disabled="!presetSyncEnabled"
                        @keyup.enter="savePreset"
                    ></v-text-field>
                    <v-btn icon :disabled="!presetSyncEnabled || !presetName" :loading="savingPreset" title="Save preset" :aria-label="'Save preset'" @click="savePreset">
                        <v-icon>mdi-content-save-outline</v-icon>
                    </v-btn>
                    <v-btn icon :disabled="!presetSyncEnabled || !selectedPresetId" :loading="deletingPreset" title="Delete preset" :aria-label="'Delete preset'" @click="deletePreset">
                        <v-icon>mdi-delete-outline</v-icon>
                    </v-btn>
                    <span class="text-caption text--secondary" v-text="presetStatusText"></span>
                </div>

                <div class="screener-active-filters mb-3" v-if="activeFilterChips.length">
                    <v-chip
                        v-for="chip in activeFilterChips"
                        :key="'screener-filter-' + chip.key"
                        small
                        label
                        class="ton-screener-filter-chip"
                        close
                        @click:close="clearFilter(chip.key)"
                    >
                        <v-icon left small v-text="chip.icon"></v-icon>
                        <span v-text="chip.label"></span>
                    </v-chip>
                </div>

                <v-alert v-if="!loading && !items.length" type="info" dense text class="screener-empty-state mb-3">
                    <span v-text="emptyFilterSummary"></span>
                </v-alert>

                <v-data-table
                    id="screener-results-table"
                    class="screener-results-table"
                    :headers="tableHeaders"
                    :items="items"
                    :mobile-breakpoint="0"
                    :disable-pagination="true"
                    :hide-default-footer="true"
                    :loading="loading"
                    :sort-by.sync="sortBy"
                    :sort-desc.sync="sortDesc"
                    dense
                    @update:sort-by="updateSort"
                    @update:sort-desc="updateSortDirection"
                    @click:row="openCurrency"
                >
                    <template v-slot:item.name="{ item }">
                        <router-link :to="currencyRoute(item)" class="screener-coin-cell">
                            <v-avatar v-if="item.image" size="28" color="white">
                                <v-img :src="item.image" :alt="item.name" :title="item.name"></v-img>
                            </v-avatar>
                            <div class="min-width-0">
                                <div class="font-weight-medium text-truncate" v-text="item.name"></div>
                                <div class="text-caption text--secondary text-uppercase" v-text="item.symbol"></div>
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
                        <span class="font-weight-bold" :class="$root.changeColorClass(item.price_change_percentage_24h_in_currency)" v-text="$root.changeFormat(item.price_change_percentage_24h_in_currency)"></span>
                    </template>
                    <template v-slot:item.price_change_percentage_7d_in_currency="{ item }">
                        <span class="font-weight-bold" :class="$root.changeColorClass(item.price_change_percentage_7d_in_currency)" v-text="$root.changeFormat(item.price_change_percentage_7d_in_currency)"></span>
                    </template>
                    <template v-slot:item.price_change_percentage_30d_in_currency="{ item }">
                        <span class="font-weight-bold" :class="$root.changeColorClass(item.price_change_percentage_30d_in_currency)" v-text="$root.changeFormat(item.price_change_percentage_30d_in_currency)"></span>
                    </template>
                    <template v-slot:item.market_cap="{ item }">
                        <span class="font-weight-medium" v-text="$root.marketCapFormat(item.market_cap)"></span>
                    </template>
                    <template v-slot:item.total_volume="{ item }">
                        <span class="font-weight-medium" v-text="$root.volumeFormat(item.total_volume)"></span>
                    </template>
                    <template v-slot:item.sentiment_score="{ item }">
                        <v-chip x-small label :color="sentimentColor(item.sentiment_score)" text-color="white">
                            <v-icon left x-small>mdi-chart-bell-curve</v-icon>
                            <span v-text="sentimentLabel(item)"></span>
                        </v-chip>
                    </template>
                    <template v-slot:item.exchange_available="{ item }">
                        <v-icon small :color="exchangeIconColor(item)" :title="exchangeLabel(item)" v-text="exchangeIcon(item)"></v-icon>
                    </template>
                    <template v-slot:item.ton_asset="{ item }">
                        <v-chip v-if="item.ton_asset" x-small label class="ton-verification-chip" :class="'ton-verification-chip--' + item.ton_asset.verification_state" :color="tonStateColor(item.ton_asset)" text-color="white">
                            <v-icon left x-small v-text="tonStateIcon(item.ton_asset)"></v-icon>
                            <span v-text="tonStateLabel(item.ton_asset)"></span>
                        </v-chip>
                        <span v-else class="text-caption text--secondary"><?php echo esc_html( 'None' ); ?></span>
                    </template>
                    <template v-slot:no-data>
                        <span v-text="emptyFilterSummary"></span>
                    </template>
                </v-data-table>
            </div>
        </div>
    </v-container>
</section>
