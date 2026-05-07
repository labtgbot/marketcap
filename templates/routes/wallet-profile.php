<?php
/**
 * TONBANKCARD public TON Connect wallet profile route.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$frontend_options['wallet-profile']['title'] = __( 'Wallet Profile' );

?>
<v-container tag="section" id="wallet-profile" class="mt-8 mb-16 pa-4 pa-sm-6">
    <div class="wallet-profile-header mb-6">
        <div>
            <h1 class="text-h4 text-sm-h4 mb-2">
                <?php echo esc_html( $frontend_options['wallet-profile']['title'] ); ?>
            </h1>
            <p class="text-body-1 mb-0">
                <?php echo esc_html( __( 'Connect a TON wallet through TON Connect for wallet-aware context. Private keys and seed phrases stay in your wallet.' ) ); ?>
            </p>
        </div>
        <div class="wallet-profile-actions">
            <v-btn
                v-if="!isConnected"
                color="primary"
                depressed
                :loading="isConnecting"
                :disabled="!featureEnabled || isConnecting"
                @click="connectWallet"
            >
                <v-icon left>mdi-link-variant</v-icon>
                <?php echo esc_html( __( 'Connect TON wallet' ) ); ?>
            </v-btn>
            <v-btn v-else outlined color="primary" @click="disconnectWallet">
                <v-icon left>mdi-link-variant-off</v-icon>
                <?php echo esc_html( __( 'Disconnect wallet' ) ); ?>
            </v-btn>
        </div>
    </div>

    <div class="wallet-profile-status mb-6">
        <v-chip label :color="statusColor" :text-color="featureEnabled ? 'white' : undefined">
            <v-icon left small v-text="statusIcon"></v-icon>
            <span v-text="statusLabel"></span>
        </v-chip>
        <v-chip label>
            <v-icon left small>mdi-cellphone</v-icon>
            <span v-text="surfaceLabel"></span>
        </v-chip>
        <v-chip label v-if="isConnected">
            <v-icon left small>mdi-lan</v-icon>
            <span v-text="networkLabel"></span>
        </v-chip>
    </div>

    <v-alert v-if="!featureEnabled" type="warning" outlined class="mb-4">
        <?php echo esc_html( __( 'TON Connect is disabled in this runtime profile.' ) ); ?>
    </v-alert>

    <v-alert v-if="connectionError" type="warning" outlined class="mb-4" v-text="connectionError"></v-alert>

    <v-row dense>
        <v-col cols="12" lg="7">
            <v-card outlined class="wallet-profile-card fill-height">
                <v-card-title class="wallet-profile-card-title">
                    <div>
                        <div class="text-h6"><?php echo esc_html( __( 'Connection' ) ); ?></div>
                        <div class="text-caption text--secondary" v-text="isConnected ? walletName : '<?php echo esc_attr( __( 'No wallet connected' ) ); ?>'"></div>
                    </div>
                    <v-spacer></v-spacer>
                    <v-avatar v-if="isConnected && wallet.image_url" size="40" color="white">
                        <v-img :src="wallet.image_url" :alt="walletName"></v-img>
                    </v-avatar>
                    <v-avatar v-else size="40" color="primary">
                        <v-icon dark>mdi-wallet-outline</v-icon>
                    </v-avatar>
                </v-card-title>
                <v-card-text>
                    <template v-if="isConnected">
                        <div class="wallet-address-box mb-4">
                            <div class="text-caption text--secondary"><?php echo esc_html( __( 'Public address' ) ); ?></div>
                            <div class="wallet-address-value" :title="wallet.address" v-text="shortAddress"></div>
                            <v-btn icon small :aria-label="'Copy wallet address ' + shortAddress" :title="'Copy wallet address ' + shortAddress" @click="copyAddress">
                                <v-icon small>mdi-content-copy</v-icon>
                            </v-btn>
                        </div>

                        <div class="wallet-meta-grid">
                            <div v-for="item in walletMetaItems" :key="item.label">
                                <v-icon small class="mr-1" v-text="item.icon"></v-icon>
                                <span class="text-caption text--secondary" v-text="item.label"></span>
                                <strong v-text="item.value"></strong>
                            </div>
                        </div>

                        <div class="wallet-feature-list mt-4" v-if="supportedFeatures.length">
                            <div class="text-caption text--secondary mb-2"><?php echo esc_html( __( 'Supported wallet metadata' ) ); ?></div>
                            <v-chip v-for="feature in supportedFeatures" :key="feature" small label outlined v-text="featureLabel(feature)"></v-chip>
                        </div>
                    </template>

                    <template v-else>
                        <div class="wallet-empty-state">
                            <v-icon size="40" color="primary">mdi-wallet-plus-outline</v-icon>
                            <div class="text-subtitle-1 font-weight-bold mt-3"><?php echo esc_html( __( 'No wallet connected' ) ); ?></div>
                            <div class="text-body-2 text--secondary">
                                <?php echo esc_html( __( 'TONBANKCARD stores only public wallet profile metadata after a TON Connect session is approved.' ) ); ?>
                            </div>
                        </div>
                    </template>
                </v-card-text>
            </v-card>
        </v-col>

        <v-col cols="12" lg="5">
            <v-card outlined class="wallet-profile-card fill-height">
                <v-card-title class="text-h6">
                    <v-icon left>mdi-shield-lock-outline</v-icon>
                    <?php echo esc_html( __( 'Privacy boundary' ) ); ?>
                </v-card-title>
                <v-card-text>
                    <v-list dense class="wallet-privacy-list">
                        <v-list-item>
                            <v-list-item-icon><v-icon color="primary">mdi-link-variant</v-icon></v-list-item-icon>
                            <v-list-item-content><?php echo esc_html( __( 'Connection uses TON Connect only.' ) ); ?></v-list-item-content>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-icon><v-icon color="primary">mdi-key-remove</v-icon></v-list-item-icon>
                            <v-list-item-content><?php echo esc_html( __( 'Private keys and seed phrases never enter TONBANKCARD.' ) ); ?></v-list-item-content>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-icon><v-icon color="primary">mdi-delete-outline</v-icon></v-list-item-icon>
                            <v-list-item-content><?php echo esc_html( __( 'Disconnect clears the local wallet profile snapshot.' ) ); ?></v-list-item-content>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-icon><v-icon color="primary">mdi-telegram</v-icon></v-list-item-icon>
                            <v-list-item-content><?php echo esc_html( __( 'Telegram Mini App sessions use the same public metadata boundary.' ) ); ?></v-list-item-content>
                        </v-list-item>
                    </v-list>
                    <v-btn text color="primary" :to="privacyRoute">
                        <v-icon left>mdi-file-document-outline</v-icon>
                        <?php echo esc_html( __( 'Privacy Policy' ) ); ?>
                    </v-btn>
                </v-card-text>
            </v-card>
        </v-col>
    </v-row>

    <v-row dense class="mt-3">
        <v-col cols="12" md="5">
            <v-card outlined class="wallet-profile-card fill-height">
                <v-card-title class="text-h6">
                    <v-icon left>mdi-star-outline</v-icon>
                    <?php echo esc_html( __( 'Wallet-aware watchlist' ) ); ?>
                </v-card-title>
                <v-card-text>
                    <div class="wallet-placeholder-stat">
                        <span class="text-caption text--secondary"><?php echo esc_html( __( 'Saved assets' ) ); ?></span>
                        <strong v-text="watchlistCount"></strong>
                    </div>
                    <div class="text-body-2 text--secondary mt-3">
                        <?php echo esc_html( __( 'Read-only watchlist context is prepared for wallet-aware views. Storage:' ) ); ?>
                        <span v-text="watchlistStorageLabel"></span>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-btn text color="primary" :to="{name:'watchlist'}">
                        <v-icon left>mdi-star</v-icon>
                        <?php echo esc_html( __( 'Watchlist' ) ); ?>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-col>

        <v-col cols="12" md="7">
            <v-card outlined class="wallet-profile-card fill-height">
                <v-card-title class="text-h6">
                    <v-icon left>mdi-briefcase-outline</v-icon>
                    <?php echo esc_html( __( 'Portfolio placeholders' ) ); ?>
                </v-card-title>
                <v-card-text>
                    <div class="wallet-portfolio-grid">
                        <div v-for="item in portfolioPlaceholders" :key="item.label">
                            <v-icon color="primary" v-text="item.icon"></v-icon>
                            <strong v-text="item.label"></strong>
                            <span v-text="item.status"></span>
                        </div>
                    </div>
                </v-card-text>
            </v-card>
        </v-col>
    </v-row>
</v-container>
