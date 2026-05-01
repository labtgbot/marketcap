<?php
/**
 * TONBANKCARD public TON ecosystem route.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$frontend_options['ton']['title'] = __( 'TON Ecosystem' );
$frontend_options['ton']['apiBaseUrl'] = site_url( 'api/ton/assets' );

?>
<v-container tag="section" id="ton" class="mt-8 mb-16 pa-4 pa-sm-6">
    <div class="ton-route-header mb-6">
        <div>
            <h1 class="text-h4 text-sm-h4 mb-2">
                <?php echo esc_html( $frontend_options['ton']['title'] ); ?>
            </h1>
            <p class="text-body-1 mb-0">
                <?php echo esc_html( 'Curated Toncoin, jettons, stablecoins, DeFi venues, wallets, and infrastructure for TONBANKCARD market context.' ); ?>
            </p>
        </div>
        <div class="ton-route-summary">
            <div>
                <span class="text-caption text--secondary"><?php echo esc_html( 'Assets' ); ?></span>
                <strong v-text="tonAssets.length"></strong>
            </div>
            <div>
                <span class="text-caption text--secondary"><?php echo esc_html( 'Verified' ); ?></span>
                <strong v-text="verifiedCount"></strong>
            </div>
            <div>
                <span class="text-caption text--secondary"><?php echo esc_html( 'Review' ); ?></span>
                <strong v-text="unverifiedCount"></strong>
            </div>
        </div>
        <div class="ton-route-actions">
            <v-btn outlined color="primary" @click="shareTonMovers">
                <v-icon left>mdi-share-variant</v-icon>
                <?php echo esc_html( __( 'Share' ) ); ?>
            </v-btn>
        </div>
    </div>

    <v-alert v-if="curationError" type="warning" dense text class="mb-4" v-text="curationError"></v-alert>

    <gc-share-card class="mb-6" dense :card="tonShareCard"></gc-share-card>

    <div class="ton-filter-bar mb-6">
        <div class="ton-filter-group">
            <span class="text-caption text--secondary"><?php echo esc_html( 'Categories' ); ?></span>
            <v-chip
                v-for="category in categoryList"
                :key="'category-' + category.id"
                small
                label
                class="ton-filter-chip"
                :class="{'v-chip--active': tonFilters.category === category.id}"
                :to="filterRoute('category', tonFilters.category === category.id ? '' : category.id)"
            >
                <v-icon left small v-text="category.icon"></v-icon>
                <span v-text="category.title"></span>
            </v-chip>
        </div>
        <div class="ton-filter-group">
            <span class="text-caption text--secondary"><?php echo esc_html( 'Lists' ); ?></span>
            <v-chip
                v-for="list in ecosystemLists"
                :key="'list-' + list.id"
                small
                label
                class="ton-filter-chip"
                :class="{'v-chip--active': tonFilters.list === list.id}"
                :to="filterRoute('list', tonFilters.list === list.id ? '' : list.id)"
            >
                <v-icon left small>mdi-playlist-star</v-icon>
                <span v-text="list.title"></span>
            </v-chip>
        </div>
        <div class="ton-filter-group">
            <span class="text-caption text--secondary"><?php echo esc_html( 'Tags' ); ?></span>
            <v-chip
                v-for="tag in visibleTags"
                :key="'tag-' + tag"
                small
                label
                class="ton-filter-chip"
                :class="{'v-chip--active': tonFilters.tag === tag}"
                :to="filterRoute('tag', tonFilters.tag === tag ? '' : tag)"
            >
                <v-icon left small>mdi-tag-outline</v-icon>
                <span v-text="tag"></span>
            </v-chip>
        </div>
        <div class="ton-filter-group">
            <span class="text-caption text--secondary"><?php echo esc_html( 'Verification' ); ?></span>
            <v-chip
                v-for="state in ['verified', 'curated', 'unverified']"
                :key="'state-' + state"
                small
                label
                class="ton-filter-chip"
                :class="{'v-chip--active': tonFilters.state === state}"
                :color="tonFilters.state === state ? stateColor(state) : undefined"
                :to="filterRoute('state', tonFilters.state === state ? '' : state)"
            >
                <v-icon left small v-text="stateIcon(state)"></v-icon>
                <span v-text="stateLabel(state)"></span>
            </v-chip>
            <v-btn v-if="hasActiveFilters" small text :to="clearFiltersRoute()">
                <?php echo esc_html( 'Clear' ); ?>
            </v-btn>
        </div>
    </div>

    <v-row class="ai-insight-grid mb-6">
        <v-col cols="12">
            <gc-ai-insight-card
                title="<?php echo esc_attr( __( 'AI TON ecosystem pulse' ) ); ?>"
                icon="mdi-diamond-stone"
                :context="tonInsightContext"
                source-route="ton_ecosystem"
            ></gc-ai-insight-card>
        </v-col>
    </v-row>

    <div class="ton-section-heading mb-3">
        <h2 class="text-h6 text-sm-h5 mb-1"><?php echo esc_html( 'Curated TON assets' ); ?></h2>
        <span class="text-caption text--secondary">
            <?php echo esc_html( 'Updated:' ); ?> <span v-text="curationUpdatedLabel"></span>
        </span>
    </div>

    <v-progress-linear v-if="loadingCuration" indeterminate color="primary" class="mb-4"></v-progress-linear>

    <v-row class="ton-asset-grid">
        <v-col v-for="asset in filteredAssets" :key="asset.id" cols="12" sm="6" lg="4">
            <v-card
                outlined
                class="ton-asset-card fill-height"
                :class="{'ton-asset-unverified': asset.verification_state === 'unverified'}"
            >
                <v-card-title class="ton-asset-title">
                    <div class="min-width-0">
                        <div class="text-subtitle-1 font-weight-bold" v-text="asset.name"></div>
                        <div class="text-caption text--secondary text-uppercase" v-text="asset.symbol || asset.id"></div>
                    </div>
                    <v-chip
                        x-small
                        label
                        class="ton-verification-chip"
                        :class="'ton-verification-chip--' + asset.verification_state"
                        :color="stateColor(asset.verification_state)"
                        text-color="white"
                    >
                        <v-icon left x-small v-text="stateIcon(asset.verification_state)"></v-icon>
                        <span v-text="stateLabel(asset.verification_state)"></span>
                    </v-chip>
                </v-card-title>
                <v-card-text>
                    <div class="ton-asset-meta mb-3">
                        <v-chip x-small label color="primary" outlined v-text="asset.categoryLabel"></v-chip>
                        <v-chip v-if="asset.featured" x-small label color="secondary" outlined>
                            <?php echo esc_html( 'Featured' ); ?>
                        </v-chip>
                    </div>
                    <p class="text-body-2 mb-3" v-text="asset.description"></p>
                    <div class="ton-asset-market-grid" v-if="asset.market">
                        <div>
                            <span class="text-caption text--secondary"><?php echo esc_html( 'Price' ); ?></span>
                            <strong v-text="$root.priceFormat(asset.market.current_price)"></strong>
                        </div>
                        <div>
                            <span class="text-caption text--secondary"><?php echo esc_html( '24h' ); ?></span>
                            <strong :class="$root.changeColorClass(asset.market.price_change_percentage_24h_in_currency)" v-text="$root.changeFormat(asset.market.price_change_percentage_24h_in_currency)"></strong>
                        </div>
                        <div>
                            <span class="text-caption text--secondary"><?php echo esc_html( 'Market cap' ); ?></span>
                            <strong v-text="$root.marketCapFormat(asset.market.market_cap)"></strong>
                        </div>
                    </div>
                    <div class="ton-asset-tags mt-3">
                        <v-chip
                            v-for="tag in (asset.tags || []).slice(0, 4)"
                            :key="asset.id + '-' + tag"
                            x-small
                            label
                            outlined
                            :to="filterRoute('tag', tag)"
                            v-text="tag"
                        ></v-chip>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-btn small text :to="assetRoute(asset)">
                        <v-icon left small>mdi-open-in-new</v-icon>
                        <?php echo esc_html( 'Open' ); ?>
                    </v-btn>
                    <v-spacer></v-spacer>
                    <v-btn small text :to="marketRoute(asset.category)">
                        <v-icon left small>mdi-table-search</v-icon>
                        <?php echo esc_html( 'Markets' ); ?>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-col>
    </v-row>

    <v-alert v-if="!loadingCuration && !filteredAssets.length" type="info" dense text class="mt-4">
        <?php echo esc_html( 'No TON assets match the active filters.' ); ?>
    </v-alert>
</v-container>
