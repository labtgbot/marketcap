<?php
/**
 * TONBANKCARD public screener route.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$frontend_options['screener']['title'] = __( 'Crypto Screener' );
$frontend_options['screener']['tonApiBaseUrl'] = site_url( 'api/ton/assets' );

?>
<v-container tag="section" id="screener" class="mt-8 mb-16 pa-4 pa-sm-6">
    <div class="ton-route-header mb-6">
        <div>
            <h1 class="text-h4 text-sm-h4 mb-2">
                <?php echo esc_html( $frontend_options['screener']['title'] ); ?>
            </h1>
            <p class="text-body-1 mb-0">
                <?php echo esc_html( 'Filter curated TON ecosystem assets by category, tag, and verification state before opening market tables.' ); ?>
            </p>
        </div>
        <div class="ton-route-summary">
            <div>
                <span class="text-caption text--secondary"><?php echo esc_html( 'Verified' ); ?></span>
                <strong v-text="verifiedCount"></strong>
            </div>
            <div>
                <span class="text-caption text--secondary"><?php echo esc_html( 'Curated' ); ?></span>
                <strong v-text="curatedCount"></strong>
            </div>
            <div>
                <span class="text-caption text--secondary"><?php echo esc_html( 'Review' ); ?></span>
                <strong v-text="unverifiedCount"></strong>
            </div>
        </div>
    </div>

    <v-alert v-if="tonFilterError" type="warning" dense text class="mb-4" v-text="tonFilterError"></v-alert>

    <div class="ton-filter-bar mb-6">
        <div class="ton-filter-group">
            <span class="text-caption text--secondary"><?php echo esc_html( 'Categories' ); ?></span>
            <v-chip
                v-for="category in categoryFilters"
                :key="'screener-category-' + category.id"
                small
                label
                class="ton-screener-filter-chip"
                :class="{'v-chip--active': filters.category === category.id}"
                :to="filterRoute('category', filters.category === category.id ? '' : category.id)"
            >
                <v-icon left small v-text="category.icon"></v-icon>
                <span v-text="category.title"></span>
            </v-chip>
        </div>
        <div class="ton-filter-group">
            <span class="text-caption text--secondary"><?php echo esc_html( 'Tags' ); ?></span>
            <v-chip
                v-for="tag in tagFilters"
                :key="'screener-tag-' + tag"
                small
                label
                class="ton-screener-filter-chip"
                :class="{'v-chip--active': filters.tag === tag}"
                :to="filterRoute('tag', filters.tag === tag ? '' : tag)"
            >
                <v-icon left small>mdi-tag-outline</v-icon>
                <span v-text="tag"></span>
            </v-chip>
        </div>
        <div class="ton-filter-group">
            <span class="text-caption text--secondary"><?php echo esc_html( 'Verification' ); ?></span>
            <v-chip
                v-for="state in ['verified', 'curated', 'unverified']"
                :key="'screener-state-' + state"
                small
                label
                class="ton-screener-filter-chip"
                :class="{'v-chip--active': filters.state === state}"
                :color="filters.state === state ? stateColor(state) : undefined"
                :to="filterRoute('state', filters.state === state ? '' : state)"
            >
                <v-icon left small v-text="stateIcon(state)"></v-icon>
                <span v-text="stateLabel(state)"></span>
            </v-chip>
            <v-btn v-if="hasActiveFilters" small text :to="clearFiltersRoute()">
                <?php echo esc_html( 'Clear' ); ?>
            </v-btn>
        </div>
    </div>

    <v-progress-linear v-if="loadingTonFilters" indeterminate color="primary" class="mb-4"></v-progress-linear>

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
                        <v-chip x-small label color="primary" outlined v-text="categoryTitle(asset.category)"></v-chip>
                        <v-chip v-if="asset.featured" x-small label color="secondary" outlined>
                            <?php echo esc_html( 'Featured' ); ?>
                        </v-chip>
                    </div>
                    <p class="text-body-2 mb-3" v-text="asset.description"></p>
                    <div class="ton-asset-tags">
                        <v-chip
                            v-for="tag in (asset.tags || []).slice(0, 5)"
                            :key="asset.id + '-screener-' + tag"
                            x-small
                            label
                            outlined
                            :to="filterRoute('tag', tag)"
                            v-text="tag"
                        ></v-chip>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-btn small text :to="searchRoute(asset)">
                        <v-icon left small>mdi-diamond-stone</v-icon>
                        <?php echo esc_html( 'TON' ); ?>
                    </v-btn>
                    <v-spacer></v-spacer>
                    <v-btn small text :to="marketRoute(asset)">
                        <v-icon left small>mdi-table-search</v-icon>
                        <?php echo esc_html( 'Markets' ); ?>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-col>
    </v-row>

    <v-alert v-if="!loadingTonFilters && !filteredAssets.length" type="info" dense text class="mt-4">
        <?php echo esc_html( 'No TON assets match the active screener filters.' ); ?>
    </v-alert>
</v-container>
