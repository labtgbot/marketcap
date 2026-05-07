<?php
/**
 * -------------------------------------------------------------------------
 * GECKO CLIENT
 * -------------------------------------------------------------------------
 * @package     Gecko Client
 * @author      RunCoders
 * @license     Envato Market Regular License (https://1.envato.market)
 * @copyright   Copyright (c) 2021 RunCoders
 * @since	    1.0.0
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$frontend_options['premium']['title'] = __( 'Premium' );

?>
<section class="py-8 py-sm-10">
    <v-container id="premium" fluid>
        <div class="premium-header mb-5">
            <div>
                <h1 class="text-h4 text-sm-h3 font-weight-bold mb-2">
                    <?php echo esc_html( $frontend_options['premium']['title'] ); ?>
                </h1>
                <div class="d-flex flex-wrap align-center">
                    <v-chip small label class="mr-2 mb-2">
                        <v-icon small left>mdi-star-four-points-outline</v-icon>
                        {{ entitlementLabel }}
                    </v-chip>
                    <v-chip small label class="mr-2 mb-2">
                        <v-icon small left>mdi-telegram</v-icon>
                        <?php echo esc_html( __( 'Telegram Stars' ) ); ?>
                    </v-chip>
                    <v-chip small label :color="checkoutEnabled ? 'success' : 'warning'" text-color="white" class="mb-2">
                        <v-icon small left>{{ checkoutEnabled ? 'mdi-check-circle-outline' : 'mdi-alert-circle-outline' }}</v-icon>
                        {{ checkoutEnabled ? '<?php echo esc_attr( __( 'Checkout enabled' ) ); ?>' : '<?php echo esc_attr( __( 'Checkout flag off' ) ); ?>' }}
                    </v-chip>
                </div>
            </div>
            <div class="premium-route-actions">
                <v-btn color="primary" depressed :loading="loading" @click="refreshEntitlement">
                    <v-icon left>mdi-refresh</v-icon>
                    <?php echo esc_html( __( 'Refresh' ) ); ?>
                </v-btn>
                <v-btn outlined color="primary" :to="{name:'alerts'}">
                    <v-icon left>mdi-bell-outline</v-icon>
                    <?php echo esc_html( __( 'Alerts' ) ); ?>
                </v-btn>
                <v-btn outlined color="primary" :to="{name:'watchlist'}">
                    <v-icon left>mdi-star-outline</v-icon>
                    <?php echo esc_html( __( 'Watchlist' ) ); ?>
                </v-btn>
            </div>
        </div>

        <v-alert v-if="notice" :type="noticeType" outlined class="mb-5">
            {{ notice }}
        </v-alert>

        <v-row dense class="premium-plan-grid">
            <v-col v-for="plan in plans" :key="plan.code" cols="12" md="6">
                <v-card class="premium-plan-card" :class="{'premium-plan-card--active': isCurrentPlan(plan)}" outlined>
                    <v-card-title class="premium-plan-title">
                        <div>
                            <div class="text-subtitle-1 font-weight-bold">{{ plan.name }}</div>
                            <div class="text-caption text--secondary">{{ plan.recurring ? '<?php echo esc_attr( __( 'Monthly subscription' ) ); ?>' : '<?php echo esc_attr( __( 'Starter plan' ) ); ?>' }}</div>
                        </div>
                        <v-spacer></v-spacer>
                        <v-icon color="primary">{{ plan.recurring ? 'mdi-star-four-points-outline' : 'mdi-lock-open-variant-outline' }}</v-icon>
                    </v-card-title>
                    <v-card-text>
                        <div class="premium-plan-price mb-4">
                            <span class="premium-plan-price-value">{{ priceLabel(plan) }}</span>
                            <span class="text-caption text--secondary" v-if="plan.recurring"><?php echo esc_html( __( '/ month' ) ); ?></span>
                        </div>

                        <div class="premium-benefit-grid">
                            <div class="premium-benefit-item" v-for="benefit in limitRows(plan)" :key="plan.code + benefit.key">
                                <v-icon small color="primary">{{ benefit.icon }}</v-icon>
                                <div>
                                    <div class="text-caption text--secondary">{{ benefit.label }}</div>
                                    <div class="font-weight-medium">{{ benefit.value }}</div>
                                </div>
                            </div>
                        </div>
                    </v-card-text>
                    <v-card-actions class="premium-plan-actions">
                        <v-btn
                            v-if="plan.price_stars > 0"
                            color="primary"
                            depressed
                            :loading="checkoutPlanCode === plan.code"
                            :disabled="!checkoutEnabled"
                            @click="startCheckout(plan)"
                        >
                            <v-icon left>mdi-telegram</v-icon>
                            <?php echo esc_html( __( 'Stars checkout' ) ); ?>
                        </v-btn>
                        <v-chip v-else small label>
                            <v-icon small left>mdi-check-circle-outline</v-icon>
                            <?php echo esc_html( __( 'Included' ) ); ?>
                        </v-chip>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>

        <v-card class="premium-status-panel mt-5" outlined>
            <v-card-title class="text-subtitle-1 font-weight-bold">
                <v-icon left color="primary">mdi-account-star-outline</v-icon>
                <?php echo esc_html( __( 'Current entitlement' ) ); ?>
            </v-card-title>
            <v-card-text>
                <v-row dense>
                    <v-col cols="12" sm="6" md="3">
                        <div class="text-caption text--secondary"><?php echo esc_html( __( 'Plan' ) ); ?></div>
                        <div class="font-weight-medium">{{ entitlement.plan_code || 'free' }}</div>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                        <div class="text-caption text--secondary"><?php echo esc_html( __( 'Status' ) ); ?></div>
                        <div class="font-weight-medium">{{ entitlement.status || 'free' }}</div>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                        <div class="text-caption text--secondary"><?php echo esc_html( __( 'Renews or expires' ) ); ?></div>
                        <div class="font-weight-medium">{{ entitlementExpiresLabel }}</div>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                        <div class="text-caption text--secondary"><?php echo esc_html( __( 'Priority refresh' ) ); ?></div>
                        <div class="font-weight-medium">{{ priorityRefreshLabel }}</div>
                    </v-col>
                </v-row>
            </v-card-text>
            <v-card-actions class="premium-status-actions">
                <v-btn
                    outlined
                    color="primary"
                    :disabled="!canCancel"
                    :loading="canceling"
                    @click="cancelEntitlement"
                >
                    <v-icon left>mdi-cancel</v-icon>
                    <?php echo esc_html( __( 'Cancel renewal' ) ); ?>
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn text color="primary" :to="{name:'screener', query:{advanced_range:'90d'}}">
                    <v-icon left>mdi-table-search</v-icon>
                    <?php echo esc_html( __( 'Advanced ranges' ) ); ?>
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-container>
</section>
