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

$frontend_options['alerts']['title'] = __( 'Alerts' );

?>
<section class="py-8 py-sm-10">
    <v-container id="alerts" fluid>
        <div class="alerts-header mb-5">
            <div>
                <h1 class="text-h4 text-sm-h3 font-weight-bold mb-2">
                    <?php echo esc_html( $frontend_options['alerts']['title'] ); ?>
                </h1>
                <div class="d-flex flex-wrap align-center">
                    <v-chip small label class="mr-2 mb-2">
                        <v-icon small left>mdi-bell-outline</v-icon>
                        {{ activeCount }} active
                    </v-chip>
                    <v-chip small label class="mr-2 mb-2">
                        <v-icon small left>mdi-pause-circle-outline</v-icon>
                        {{ pausedCount }} paused
                    </v-chip>
                    <v-chip small label class="mr-2 mb-2">
                        <v-icon small left>mdi-database</v-icon>
                        {{ storageModeLabel }}
                    </v-chip>
                    <v-chip small label :color="featureEnabled ? 'success' : 'warning'" text-color="white" class="mb-2">
                        <v-icon small left>{{ featureEnabled ? 'mdi-check-circle-outline' : 'mdi-alert-circle-outline' }}</v-icon>
                        {{ featureEnabled ? 'Delivery enabled' : 'Delivery flag off' }}
                    </v-chip>
                </div>
            </div>
            <div class="alerts-route-actions">
                <v-btn color="primary" depressed :to="{name:'markets'}">
                    <v-icon left>mdi-table-large</v-icon>
                    <?php echo esc_html( __( 'Markets' ) ); ?>
                </v-btn>
                <v-btn outlined color="primary" :to="{name:'watchlist'}">
                    <v-icon left>mdi-star-outline</v-icon>
                    <?php echo esc_html( __( 'Watchlist' ) ); ?>
                </v-btn>
            </div>
        </div>

        <v-row dense>
            <v-col cols="12" lg="5">
                <v-card class="alert-rule-form" outlined>
                    <v-card-title class="text-subtitle-1 font-weight-bold">
                        <v-icon left color="primary">mdi-bell-plus-outline</v-icon>
                        {{ editingId ? 'Edit alert' : 'New alert' }}
                    </v-card-title>
                    <v-card-text>
                        <v-row dense>
                            <v-col cols="12" sm="7">
                                <v-text-field
                                    v-model.trim="form.coin_id"
                                    label="<?php echo esc_attr( __( 'Coin id' ) ); ?>"
                                    outlined
                                    dense
                                    hide-details="auto"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="5">
                                <v-text-field
                                    v-model.trim="form.symbol"
                                    label="<?php echo esc_attr( __( 'Symbol' ) ); ?>"
                                    outlined
                                    dense
                                    hide-details="auto"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model.trim="form.display_name"
                                    label="<?php echo esc_attr( __( 'Name' ) ); ?>"
                                    outlined
                                    dense
                                    hide-details="auto"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="7">
                                <v-select
                                    v-model="form.trigger_type"
                                    :items="triggerTypes"
                                    label="<?php echo esc_attr( __( 'Trigger' ) ); ?>"
                                    outlined
                                    dense
                                    hide-details="auto"
                                ></v-select>
                            </v-col>
                            <v-col cols="5" sm="5">
                                <v-select
                                    v-model="form.operator"
                                    :items="operatorOptions"
                                    label="<?php echo esc_attr( __( 'Operator' ) ); ?>"
                                    outlined
                                    dense
                                    hide-details="auto"
                                ></v-select>
                            </v-col>
                            <v-col cols="7" sm="12">
                                <v-text-field
                                    v-model.number="form.threshold"
                                    :label="thresholdLabel"
                                    type="number"
                                    outlined
                                    dense
                                    hide-details="auto"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model.trim="form.quiet_start"
                                    label="<?php echo esc_attr( __( 'Quiet from' ) ); ?>"
                                    placeholder="22:00"
                                    outlined
                                    dense
                                    hide-details="auto"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model.trim="form.quiet_end"
                                    label="<?php echo esc_attr( __( 'Quiet until' ) ); ?>"
                                    placeholder="06:00"
                                    outlined
                                    dense
                                    hide-details="auto"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model.number="form.frequency_cap_seconds"
                                    label="<?php echo esc_attr( __( 'Frequency cap seconds' ) ); ?>"
                                    type="number"
                                    min="300"
                                    max="86400"
                                    outlined
                                    dense
                                    hide-details="auto"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model.number="form.max_deliveries_per_day"
                                    label="<?php echo esc_attr( __( 'Daily cap' ) ); ?>"
                                    type="number"
                                    min="1"
                                    max="100"
                                    outlined
                                    dense
                                    hide-details="auto"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model.trim="form.timezone"
                                    label="<?php echo esc_attr( __( 'Timezone' ) ); ?>"
                                    outlined
                                    dense
                                    hide-details="auto"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-switch
                                    v-model="form.status"
                                    true-value="active"
                                    false-value="paused"
                                    inset
                                    hide-details
                                    :label="form.status === 'active' ? 'Active' : 'Paused'"
                                ></v-switch>
                            </v-col>
                        </v-row>
                    </v-card-text>
                    <v-card-actions class="alerts-form-actions">
                        <v-btn color="primary" depressed :loading="saving" @click="saveRule">
                            <v-icon left>mdi-content-save-outline</v-icon>
                            {{ saveLabel }}
                        </v-btn>
                        <v-btn outlined color="primary" :loading="testingId === 'form'" @click="testCurrentForm">
                            <v-icon left>mdi-send-check-outline</v-icon>
                            <?php echo esc_html( __( 'Test' ) ); ?>
                        </v-btn>
                        <v-spacer></v-spacer>
                        <v-btn icon :aria-label="'Reset alert form'" title="Reset alert form" @click="resetForm">
                            <v-icon>mdi-refresh</v-icon>
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>

            <v-col cols="12" lg="7">
                <v-alert v-if="!sortedRules.length" type="info" outlined class="mb-4">
                    <?php echo esc_html( __( 'No alert rules yet.' ) ); ?>
                </v-alert>

                <v-alert v-if="detailRule" type="success" outlined class="mb-4">
                    <?php echo esc_html( __( 'Opened alert.' ) ); ?> {{ ruleSymbol(detailRule) }} - {{ deliveryPath(detailRule) }}
                </v-alert>

                <v-alert v-if="testResult" type="success" outlined class="mb-4">
                    <div class="font-weight-bold mb-1"><?php echo esc_html( __( 'Test delivery' ) ); ?></div>
                    <div class="text-body-2" v-text="testResult.text"></div>
                    <div class="text-caption text--secondary mt-1" v-text="testResult.links && testResult.links.mini_app_path"></div>
                </v-alert>

                <div class="alerts-rule-grid">
                    <v-card
                        v-for="rule in sortedRules"
                        :key="rule.id || rule.local_id"
                        class="alert-rule-card"
                        :class="{'alert-rule-card--paused': rule.status === 'paused'}"
                        outlined
                    >
                        <v-card-title class="alert-rule-card-title">
                            <div>
                                <div class="text-subtitle-1 font-weight-bold">
                                    {{ ruleSymbol(rule) }}
                                </div>
                                <div class="text-caption text--secondary">
                                    {{ triggerTypeLabel(rule.trigger_type) }} {{ operatorLabel(rule.operator) }} {{ rule.threshold }}
                                </div>
                            </div>
                            <v-spacer></v-spacer>
                            <v-chip small :color="rule.status === 'active' ? 'success' : 'grey'" text-color="white">
                                {{ rule.status }}
                            </v-chip>
                        </v-card-title>
                        <v-card-text>
                            <div class="alert-rule-meta">
                                <div>
                                    <span class="text-caption text--secondary"><?php echo esc_html( __( 'Coin' ) ); ?></span>
                                    <strong>{{ rule.coin_id }}</strong>
                                </div>
                                <div>
                                    <span class="text-caption text--secondary"><?php echo esc_html( __( 'Cap' ) ); ?></span>
                                    <strong>{{ capLabel(rule.frequency_cap_seconds) }}</strong>
                                </div>
                                <div>
                                    <span class="text-caption text--secondary"><?php echo esc_html( __( 'Daily' ) ); ?></span>
                                    <strong>{{ rule.max_deliveries_per_day }}</strong>
                                </div>
                                <div>
                                    <span class="text-caption text--secondary"><?php echo esc_html( __( 'Route' ) ); ?></span>
                                    <strong>{{ deliveryPath(rule) }}</strong>
                                </div>
                            </div>
                        </v-card-text>
                        <v-card-actions class="alert-rule-actions">
                            <v-btn text color="primary" @click="startEdit(rule)">
                                <v-icon left>mdi-pencil-outline</v-icon>
                                <?php echo esc_html( __( 'Edit' ) ); ?>
                            </v-btn>
                            <v-btn text color="primary" :to="openCoinRoute(rule)">
                                <v-icon left>mdi-open-in-app</v-icon>
                                <?php echo esc_html( __( 'Coin' ) ); ?>
                            </v-btn>
                            <v-spacer></v-spacer>
                            <v-btn icon :loading="testingId === (rule.id || rule.local_id)" :aria-label="'Test alert for ' + ruleSymbol(rule)" :title="'Test alert for ' + ruleSymbol(rule)" @click="testRule(rule)">
                                <v-icon>mdi-send-check-outline</v-icon>
                            </v-btn>
                            <v-btn v-if="rule.status === 'active'" icon :aria-label="'Pause alert for ' + ruleSymbol(rule)" :title="'Pause alert for ' + ruleSymbol(rule)" @click="pauseRule(rule)">
                                <v-icon>mdi-pause</v-icon>
                            </v-btn>
                            <v-btn v-else icon :aria-label="'Resume alert for ' + ruleSymbol(rule)" :title="'Resume alert for ' + ruleSymbol(rule)" @click="resumeRule(rule)">
                                <v-icon>mdi-play</v-icon>
                            </v-btn>
                            <v-btn icon color="error" :aria-label="'Delete alert for ' + ruleSymbol(rule)" :title="'Delete alert for ' + ruleSymbol(rule)" @click="deleteRule(rule)">
                                <v-icon>mdi-delete-outline</v-icon>
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </div>
            </v-col>
        </v-row>

        <v-snackbar v-model="noticeModel" timeout="3500" bottom>
            {{ notice }}
            <template v-slot:action="{ attrs }">
                <v-btn text v-bind="attrs" @click="noticeModel = false">
                    <?php echo esc_html( __( 'Close' ) ); ?>
                </v-btn>
            </template>
        </v-snackbar>
    </v-container>
</section>
