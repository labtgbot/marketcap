<?php
/**
 * TONBANKCARD per-asset TON ecosystem catalog page.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$frontend_options['ton-asset']['title'] = __( 'TON Ecosystem Asset' );
$frontend_options['ton-asset']['apiBaseUrl'] = site_url( 'api/ton/assets' );

?>
<v-container tag="section" id="ton-asset" class="mt-8 mb-16 pa-4 pa-sm-6">
    <v-btn small text :to="backRoute()" class="mb-4">
        <v-icon left small>mdi-arrow-left</v-icon>
        <?php echo esc_html( __( 'Back to TON catalog' ) ); ?>
    </v-btn>

    <v-progress-linear v-if="loadingCuration" indeterminate color="primary" class="mb-4"></v-progress-linear>

    <v-alert v-if="adminNotice" :type="adminNoticeType" dense text class="mb-4" v-text="adminNotice"></v-alert>
    <v-alert v-if="loadError" type="warning" dense text class="mb-4" v-text="loadError"></v-alert>

    <v-card v-if="asset" outlined class="ton-asset-detail" :class="{'ton-asset-unverified': asset.verification_state === 'unverified'}">
        <v-card-title class="ton-asset-detail-title">
            <div class="min-width-0">
                <h1 class="text-h5 mb-1" v-text="asset.name || asset.id"></h1>
                <div class="text-caption text--secondary text-uppercase" v-text="asset.symbol || asset.id"></div>
            </div>
            <v-spacer></v-spacer>
            <v-chip
                small
                label
                class="ton-verification-chip"
                :class="'ton-verification-chip--' + asset.verification_state"
                :color="stateColor(asset.verification_state)"
                text-color="white"
            >
                <v-icon left small v-text="stateIcon(asset.verification_state)"></v-icon>
                <span v-text="stateLabel(asset.verification_state)"></span>
            </v-chip>
        </v-card-title>
        <v-card-text>
            <div class="ton-asset-meta mb-3">
                <v-chip x-small label color="primary" outlined v-text="categoryTitle(asset.category)"></v-chip>
                <v-chip v-if="asset.featured" x-small label color="secondary" outlined>
                    <?php echo esc_html( __( 'Featured' ) ); ?>
                </v-chip>
            </div>
            <p class="text-body-1 mb-4" v-text="asset.description || '<?php echo esc_attr( __( 'No description has been recorded for this asset yet.' ) ); ?>'"></p>

            <div class="ton-asset-market-grid mb-4" v-if="asset.market">
                <div>
                    <span class="text-caption text--secondary"><?php echo esc_html( __( 'Price' ) ); ?></span>
                    <strong v-text="$root.priceFormat(asset.market.current_price)"></strong>
                </div>
                <div>
                    <span class="text-caption text--secondary"><?php echo esc_html( __( '24h' ) ); ?></span>
                    <strong :class="$root.changeColorClass(asset.market.price_change_percentage_24h_in_currency)" v-text="$root.changeFormat(asset.market.price_change_percentage_24h_in_currency)"></strong>
                </div>
                <div>
                    <span class="text-caption text--secondary"><?php echo esc_html( __( 'Market cap' ) ); ?></span>
                    <strong v-text="$root.marketCapFormat(asset.market.market_cap)"></strong>
                </div>
            </div>

            <div class="ton-asset-tags mb-4">
                <v-chip
                    v-for="tag in (asset.tags || [])"
                    :key="asset.id + '-tag-' + tag"
                    x-small
                    label
                    outlined
                    class="mr-1 mb-1"
                    :to="catalogRoute('tag', tag)"
                    v-text="tag"
                ></v-chip>
            </div>
        </v-card-text>
        <v-card-actions>
            <v-btn v-if="asset.coin_id" small text :to="coinRoute(asset)">
                <v-icon left small>mdi-chart-line</v-icon>
                <?php echo esc_html( __( 'View market' ) ); ?>
            </v-btn>
            <v-btn small text :to="categoryRoute(asset.category)">
                <v-icon left small>mdi-folder-multiple-outline</v-icon>
                <?php echo esc_html( __( 'Browse category' ) ); ?>
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn v-if="canEditCuration" small color="primary" depressed class="ton-admin-edit-btn" @click="openEditor">
                <v-icon left small>mdi-pencil-outline</v-icon>
                <?php echo esc_html( __( 'Edit asset' ) ); ?>
            </v-btn>
            <v-btn v-if="canEditCuration" small text color="error" class="ton-admin-delete-btn" :disabled="adminSaving" @click="deleteAsset">
                <v-icon left small>mdi-delete-outline</v-icon>
                <?php echo esc_html( __( 'Remove' ) ); ?>
            </v-btn>
        </v-card-actions>
    </v-card>

    <v-row class="mt-6 ai-insight-grid" v-if="asset">
        <v-col cols="12">
            <gc-ai-insight-card
                title="<?php echo esc_attr( __( 'AI TON asset insight' ) ); ?>"
                icon="mdi-diamond-stone"
                :context="tonAssetInsightContext"
                source-route="ton_asset"
            ></gc-ai-insight-card>
        </v-col>
    </v-row>

    <v-alert v-else-if="!loadingCuration" type="info" dense text class="mt-4">
        <?php echo esc_html( __( 'This TON asset is not in the catalog yet.' ) ); ?>
    </v-alert>

    <v-dialog v-if="canEditCuration" v-model="editorOpen" max-width="640" persistent>
        <v-card v-if="editorAsset" class="ton-asset-editor">
            <v-card-title class="text-subtitle-1 font-weight-bold">
                <v-icon left color="primary">mdi-pencil-circle-outline</v-icon>
                <?php echo esc_html( __( 'Edit TON asset' ) ); ?>
            </v-card-title>
            <v-card-text>
                <v-row dense>
                    <v-col cols="12" sm="6">
                        <v-text-field v-model.trim="editorAsset.id" label="<?php echo esc_attr( __( 'Asset id' ) ); ?>" outlined dense disabled></v-text-field>
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-text-field v-model.trim="editorAsset.symbol" label="<?php echo esc_attr( __( 'Symbol' ) ); ?>" outlined dense></v-text-field>
                    </v-col>
                    <v-col cols="12">
                        <v-text-field v-model.trim="editorAsset.name" label="<?php echo esc_attr( __( 'Name' ) ); ?>" outlined dense required></v-text-field>
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-select v-model="editorAsset.category" :items="tonCategoryOptions" label="<?php echo esc_attr( __( 'Category' ) ); ?>" outlined dense></v-select>
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-select v-model="editorAsset.verification_state" :items="tonVerificationStateOptions" label="<?php echo esc_attr( __( 'Verification state' ) ); ?>" outlined dense></v-select>
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-select
                            v-model="editorAsset.link_type"
                            :items="tonLinkTypeOptions"
                            item-text="text"
                            item-value="value"
                            label="<?php echo esc_attr( __( 'Card opens' ) ); ?>"
                            hint="<?php echo esc_attr( __( 'Choose between the cryptocurrency page or a separate project catalog page' ) ); ?>"
                            persistent-hint
                            outlined
                            dense
                        ></v-select>
                    </v-col>
                    <v-col cols="12" sm="6" v-if="editorAsset.link_type === 'project'">
                        <v-select v-model="editorAsset.project_category" :items="tonProjectCategoryOptions" label="<?php echo esc_attr( __( 'Project category' ) ); ?>" outlined dense clearable></v-select>
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            v-model.trim="editorAsset.coin_id"
                            label="<?php echo esc_attr( __( 'CoinGecko id (optional)' ) ); ?>"
                            :disabled="editorAsset.link_type === 'project'"
                            outlined
                            dense
                        ></v-text-field>
                    </v-col>
                    <v-col cols="12" sm="6" class="d-flex align-center">
                        <v-switch v-model="editorAsset.featured" inset hide-details label="<?php echo esc_attr( __( 'Featured' ) ); ?>"></v-switch>
                    </v-col>
                    <v-col cols="12">
                        <v-textarea v-model.trim="editorAsset.description" label="<?php echo esc_attr( __( 'Description' ) ); ?>" rows="3" auto-grow outlined dense></v-textarea>
                    </v-col>
                    <v-col cols="12">
                        <v-combobox v-model="editorAsset.tags" label="<?php echo esc_attr( __( 'Tags' ) ); ?>" multiple small-chips deletable-chips outlined dense></v-combobox>
                    </v-col>
                </v-row>
            </v-card-text>
            <v-card-actions>
                <v-btn text :disabled="adminSaving" @click="closeEditor">
                    <?php echo esc_html( __( 'Cancel' ) ); ?>
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn color="primary" depressed :loading="adminSaving" @click="saveEditor">
                    <v-icon left>mdi-content-save-outline</v-icon>
                    <?php echo esc_html( __( 'Save asset' ) ); ?>
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</v-container>
