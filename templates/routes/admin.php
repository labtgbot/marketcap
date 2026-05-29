<?php
/**
 * -------------------------------------------------------------------------
 * GECKO CLIENT
 * -------------------------------------------------------------------------
 * @package     Gecko Client
 * @author      RunCoders
 * @license     Envato Market Regular License (https://1.envato.market/regular-license)
 * @copyright   Copyright (c) 2021 RunCoders (https://runcoders.net)
 * @since       1.0.0
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$frontend_options['admin']['title']      = __( 'Admin Panel' );
$frontend_options['admin']['apiBaseUrl'] = site_url( 'api/admin' );

?>
<section class="admin-shell py-6 py-sm-8">
    <v-container id="admin" fluid>
        <div class="admin-header mb-4">
            <div>
                <h1 class="text-h5 text-sm-h4 font-weight-bold mb-2">
                    <?php echo esc_html( $frontend_options['admin']['title'] ); ?>
                </h1>
                <div class="admin-status-strip">
                    <v-chip small label class="mr-2 mb-2" :color="authenticated ? 'success' : 'warning'" text-color="white">
                        <v-icon small left>{{ authenticated ? 'mdi-shield-check-outline' : 'mdi-shield-lock-outline' }}</v-icon>
                        {{ authenticated ? actor.role : 'locked' }}
                    </v-chip>
                    <v-chip small label class="mr-2 mb-2">
                        <v-icon small left>mdi-database-cog-outline</v-icon>
                        {{ storeLabel }}
                    </v-chip>
                    <v-chip small label class="mr-2 mb-2" :color="canWrite ? 'primary' : 'secondary'" text-color="white">
                        <v-icon small left>{{ canWrite ? 'mdi-pencil-lock-open-outline' : 'mdi-eye-outline' }}</v-icon>
                        {{ canWrite ? 'write enabled' : 'read only' }}
                    </v-chip>
                </div>
            </div>
            <div class="admin-header-actions">
                <v-btn v-if="authenticated" outlined color="primary" :loading="loading" @click="loadConfig">
                    <v-icon left>mdi-refresh</v-icon>
                    <?php echo esc_html( __( 'Refresh' ) ); ?>
                </v-btn>
                <v-btn v-if="authenticated" text color="secondary" @click="logout">
                    <v-icon left>mdi-logout</v-icon>
                    <?php echo esc_html( __( 'Lock' ) ); ?>
                </v-btn>
            </div>
        </div>

        <v-alert v-if="notice" :type="noticeType" dense text class="mb-4">
            {{ notice }}
        </v-alert>

        <v-card v-if="!authenticated" class="admin-login-card" outlined>
            <v-card-title class="text-subtitle-1 font-weight-bold">
                <v-icon left color="primary">mdi-shield-key-outline</v-icon>
                <?php echo esc_html( __( 'Admin Access' ) ); ?>
            </v-card-title>
            <v-card-text>
                <v-text-field
                    v-model.trim="loginToken"
                    class="admin-secret-input"
                    label="<?php echo esc_attr( __( 'Admin token' ) ); ?>"
                    type="password"
                    outlined
                    dense
                    hide-details="auto"
                    autocomplete="off"
                    @keyup.enter="login"
                ></v-text-field>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary" depressed :loading="loading" @click="login">
                    <v-icon left>mdi-login</v-icon>
                    <?php echo esc_html( __( 'Unlock' ) ); ?>
                </v-btn>
            </v-card-actions>
        </v-card>

        <template v-else>
            <v-tabs class="admin-tabs mb-4" show-arrows>
                <v-tab :to="{name:'admin'}" exact>
                    <v-icon left>mdi-view-dashboard-outline</v-icon>
                    <?php echo esc_html( __( 'Overview' ) ); ?>
                </v-tab>
                <v-tab :to="{name:'admin-providers'}">
                    <v-icon left>mdi-cloud-cog-outline</v-icon>
                    <?php echo esc_html( __( 'Providers' ) ); ?>
                </v-tab>
                <v-tab :to="{name:'admin-feature-flags'}">
                    <v-icon left>mdi-toggle-switch-outline</v-icon>
                    <?php echo esc_html( __( 'Flags' ) ); ?>
                </v-tab>
                <v-tab :to="{name:'admin-mini-app'}">
                    <v-icon left>mdi-cellphone-cog</v-icon>
                    <?php echo esc_html( __( 'Mini App Setup' ) ); ?>
                </v-tab>
                <v-tab :to="{name:'admin-ton-assets'}">
                    <v-icon left>mdi-diamond-stone</v-icon>
                    <?php echo esc_html( __( 'TON Assets' ) ); ?>
                </v-tab>
                <v-tab :to="{name:'admin-legal-copy'}">
                    <v-icon left>mdi-file-document-edit-outline</v-icon>
                    <?php echo esc_html( __( 'Legal Copy' ) ); ?>
                </v-tab>
                <v-tab :to="{name:'admin-alerts'}">
                    <v-icon left>mdi-bell-cog-outline</v-icon>
                    <?php echo esc_html( __( 'Operations' ) ); ?>
                </v-tab>
                <v-tab :to="{name:'admin-audit-log'}">
                    <v-icon left>mdi-clipboard-text-clock-outline</v-icon>
                    <?php echo esc_html( __( 'Audit' ) ); ?>
                </v-tab>
            </v-tabs>

            <v-progress-linear v-if="loading" indeterminate color="primary" height="3" class="mb-4" aria-label="<?php echo esc_attr( __( 'Loading' ) ); ?>"></v-progress-linear>

            <v-row dense v-if="activeSection === 'overview'">
                <v-col cols="12" md="3" v-for="metric in overviewMetrics" :key="metric.label">
                    <v-card outlined class="admin-metric-card">
                        <v-card-text>
                            <div class="text-caption text--secondary" v-text="metric.label"></div>
                            <div class="text-h6 font-weight-bold" v-text="metric.value"></div>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" lg="7">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-toggle-switch-outline</v-icon>
                            <?php echo esc_html( __( 'Feature Flags' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <div class="admin-flag-grid">
                                <v-switch
                                    v-for="flag in flagDefinitions"
                                    :key="flag.key"
                                    v-model="featureFlags[flag.key]"
                                    :disabled="!canWrite || saving"
                                    dense
                                    inset
                                    hide-details
                                    :label="flag.label"
                                ></v-switch>
                            </div>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="primary" depressed :disabled="!canWrite" :loading="saving" @click="saveFeatureFlags">
                                <v-icon left>mdi-content-save-outline</v-icon>
                                <?php echo esc_html( __( 'Save Flags' ) ); ?>
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-col>
                <v-col cols="12" lg="5">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-cached</v-icon>
                            <?php echo esc_html( __( 'Cache Control' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <v-select
                                v-model="operations.cache.market_stale_mode"
                                :items="cacheModeOptions"
                                label="<?php echo esc_attr( __( 'Stale mode' ) ); ?>"
                                outlined
                                dense
                                hide-details="auto"
                                :disabled="!canWrite"
                            ></v-select>
                            <v-text-field
                                v-model.trim="cacheScope"
                                label="<?php echo esc_attr( __( 'Purge scope' ) ); ?>"
                                outlined
                                dense
                                hide-details="auto"
                                class="mt-3"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <div class="admin-meta-line mt-3">
                                {{ cacheStatusLabel }}
                            </div>
                        </v-card-text>
                        <v-card-actions>
                            <v-btn outlined color="primary" :disabled="!canWrite" :loading="saving" @click="saveOperations">
                                <v-icon left>mdi-content-save-outline</v-icon>
                                <?php echo esc_html( __( 'Save Operations' ) ); ?>
                            </v-btn>
                            <v-spacer></v-spacer>
                            <v-btn color="warning" depressed :disabled="!canWrite" :loading="saving" @click="purgeCache">
                                <v-icon left>mdi-delete-sweep-outline</v-icon>
                                <?php echo esc_html( __( 'Purge' ) ); ?>
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-col>
            </v-row>

            <v-row dense v-if="activeSection === 'providers'">
                <v-col cols="12" lg="6">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-robot-outline</v-icon>
                            <?php echo esc_html( __( 'AI Provider' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <v-text-field
                                v-model.trim="providers.groq.model_id"
                                label="<?php echo esc_attr( __( 'Groq model' ) ); ?>"
                                outlined
                                dense
                                hide-details="auto"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <v-text-field
                                v-model.trim="providerSecrets.groq.api_key"
                                class="admin-secret-input mt-3"
                                label="<?php echo esc_attr( __( 'Groq API key' ) ); ?>"
                                type="password"
                                outlined
                                dense
                                hide-details="auto"
                                autocomplete="off"
                                :placeholder="secretPlaceholder(providers.groq.api_key)"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <div class="admin-meta-line mt-2">Key: {{ secretStatus(providers.groq.api_key) }}</div>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" lg="6">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-chart-line</v-icon>
                            <?php echo esc_html( __( 'Market Provider' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <v-select
                                v-model="providers.coingecko.api_plan"
                                :items="coingeckoPlans"
                                label="<?php echo esc_attr( __( 'CoinGecko plan' ) ); ?>"
                                outlined
                                dense
                                hide-details="auto"
                                :disabled="!canWrite"
                            ></v-select>
                            <v-text-field
                                v-model.trim="providerSecrets.coingecko.api_key"
                                class="admin-secret-input mt-3"
                                label="<?php echo esc_attr( __( 'CoinGecko API key' ) ); ?>"
                                type="password"
                                outlined
                                dense
                                hide-details="auto"
                                autocomplete="off"
                                :placeholder="secretPlaceholder(providers.coingecko.api_key)"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <div class="admin-meta-line mt-2">Key: {{ secretStatus(providers.coingecko.api_key) }}</div>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" lg="6">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-database-sync-outline</v-icon>
                            <?php echo esc_html( __( 'Upstash Redis' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <v-select
                                v-model="providers.upstash.status"
                                :items="providerStatusOptions"
                                label="<?php echo esc_attr( __( 'Status' ) ); ?>"
                                outlined
                                dense
                                hide-details="auto"
                                :disabled="!canWrite"
                            ></v-select>
                            <v-text-field
                                v-model.trim="providerSecrets.upstash.rest_token"
                                class="admin-secret-input mt-3"
                                label="<?php echo esc_attr( __( 'REST token' ) ); ?>"
                                type="password"
                                outlined
                                dense
                                hide-details="auto"
                                autocomplete="off"
                                :placeholder="secretPlaceholder(providers.upstash.rest_token)"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <div class="admin-meta-line mt-2">REST URL: {{ providers.upstash.rest_url_configured ? 'configured' : 'not configured' }}</div>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" lg="6">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-diamond-stone</v-icon>
                            <?php echo esc_html( __( 'TON API' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <v-text-field
                                v-model.trim="providerSecrets.tonapi.api_key"
                                class="admin-secret-input"
                                label="<?php echo esc_attr( __( 'TON API key' ) ); ?>"
                                type="password"
                                outlined
                                dense
                                hide-details="auto"
                                autocomplete="off"
                                :placeholder="secretPlaceholder(providers.tonapi.api_key)"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <div class="admin-meta-line mt-2">Key: {{ secretStatus(providers.tonapi.api_key) }}</div>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" lg="6">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-swap-horizontal-circle-outline</v-icon>
                            <?php echo esc_html( __( 'ChangeNOW' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <v-text-field
                                v-model.trim="providers.changenow.link_id"
                                label="<?php echo esc_attr( __( 'Link id' ) ); ?>"
                                outlined
                                dense
                                hide-details="auto"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <v-text-field
                                v-model.trim="providers.changenow.listing_url"
                                label="<?php echo esc_attr( __( 'Listing URL (shown when coin is not listed)' ) ); ?>"
                                outlined
                                dense
                                hide-details="auto"
                                class="mt-3"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <v-text-field
                                v-model.trim="providers.telegram.bot_username"
                                label="<?php echo esc_attr( __( 'Telegram bot username' ) ); ?>"
                                outlined
                                dense
                                hide-details="auto"
                                class="mt-3"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <v-text-field
                                v-model.trim="providerSecrets.telegram.bot_token"
                                class="admin-secret-input mt-3"
                                label="<?php echo esc_attr( __( 'Telegram bot token' ) ); ?>"
                                type="password"
                                outlined
                                dense
                                hide-details="auto"
                                autocomplete="off"
                                :placeholder="secretPlaceholder(providers.telegram.bot_token)"
                                :disabled="!canWrite"
                            ></v-text-field>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" lg="6">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-chart-areaspline</v-icon>
                            <?php echo esc_html( __( 'Yandex Metrica' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <v-row dense>
                                <v-col cols="12" sm="4">
                                    <v-switch v-model="analytics.yandex_metrica.enabled" label="<?php echo esc_attr( __( 'Enabled' ) ); ?>" inset hide-details :disabled="!canWrite"></v-switch>
                                </v-col>
                                <v-col cols="12" sm="8">
                                    <v-text-field v-model.trim="analytics.yandex_metrica.counter_id" label="<?php echo esc_attr( __( 'Counter ID' ) ); ?>" outlined dense hide-details="auto" inputmode="numeric" :disabled="!canWrite"></v-text-field>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12">
                    <div class="admin-sticky-actions">
                        <v-btn color="primary" depressed :disabled="!canWrite" :loading="saving" @click="saveProviders">
                            <v-icon left>mdi-content-save-outline</v-icon>
                            <?php echo esc_html( __( 'Save Providers' ) ); ?>
                        </v-btn>
                    </div>
                </v-col>
            </v-row>

            <v-card v-if="activeSection === 'flags'" outlined>
                <v-card-title class="text-subtitle-1 font-weight-bold">
                    <v-icon left color="primary">mdi-toggle-switch-outline</v-icon>
                    <?php echo esc_html( __( 'Feature Flags' ) ); ?>
                </v-card-title>
                <v-card-text>
                    <div class="admin-flag-grid">
                        <v-switch
                            v-for="flag in flagDefinitions"
                            :key="flag.key"
                            v-model="featureFlags[flag.key]"
                            :disabled="!canWrite || saving"
                            inset
                            hide-details
                            :label="flag.label"
                        ></v-switch>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" depressed :disabled="!canWrite" :loading="saving" @click="saveFeatureFlags">
                        <v-icon left>mdi-content-save-outline</v-icon>
                        <?php echo esc_html( __( 'Save Flags' ) ); ?>
                    </v-btn>
                </v-card-actions>
            </v-card>

            <v-row dense v-if="activeSection === 'mini-app'">
                <v-col cols="12" lg="6">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-web-cog</v-icon>
                            <?php echo esc_html( __( 'Runtime And URLs' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <v-select
                                v-model="miniApp.runtime.profile"
                                :items="runtimeProfileOptions"
                                label="<?php echo esc_attr( __( 'Runtime profile' ) ); ?>"
                                outlined
                                dense
                                hide-details="auto"
                                :disabled="!canWrite"
                            ></v-select>
                            <v-text-field
                                v-model.trim="miniApp.runtime.public_base_url"
                                label="<?php echo esc_attr( __( 'Public website URL' ) ); ?>"
                                outlined
                                dense
                                hide-details="auto"
                                class="mt-3"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <v-text-field
                                v-model.trim="miniApp.runtime.telegram_base_url"
                                label="<?php echo esc_attr( __( 'Telegram Mini App URL' ) ); ?>"
                                outlined
                                dense
                                hide-details="auto"
                                class="mt-3"
                                :disabled="!canWrite"
                            ></v-text-field>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" lg="6">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-telegram</v-icon>
                            <?php echo esc_html( __( 'Telegram Bot' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <v-text-field
                                v-model.trim="miniApp.telegram.bot_username"
                                label="<?php echo esc_attr( __( 'Bot username' ) ); ?>"
                                outlined
                                dense
                                hide-details="auto"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <v-text-field
                                v-model.trim="miniAppSecrets.bot_token"
                                class="admin-secret-input mt-3"
                                label="<?php echo esc_attr( __( 'Bot token' ) ); ?>"
                                type="password"
                                outlined
                                dense
                                hide-details="auto"
                                autocomplete="off"
                                :placeholder="secretPlaceholder(miniApp.telegram.bot_token)"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <div class="admin-telegram-setup-row mt-3">
                                <v-btn
                                    outlined
                                    small
                                    color="primary"
                                    :disabled="!telegramSetupAvailable"
                                    :loading="telegramSetupLoading"
                                    @click="setupMiniAppTelegram"
                                >
                                    <v-icon left>mdi-robot-outline</v-icon>
                                    <?php echo esc_html( __( 'Check Bot' ) ); ?>
                                </v-btn>
                                <span class="admin-meta-line">
                                    <?php echo esc_html( __( 'Setup' ) ); ?>: {{ telegramSetupStatusLabel }}
                                </span>
                            </div>
                            <v-text-field
                                v-model.trim="miniAppSecrets.webhook_secret"
                                class="admin-secret-input mt-3"
                                label="<?php echo esc_attr( __( 'Webhook secret' ) ); ?>"
                                type="password"
                                outlined
                                dense
                                hide-details="auto"
                                autocomplete="off"
                                :placeholder="secretPlaceholder(miniApp.telegram.webhook_secret)"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <div class="admin-meta-line mt-3">
                                <?php echo esc_html( __( 'Launch URL' ) ); ?>:
                                <a v-if="miniApp.launch.telegram_launch_url" :href="miniApp.launch.telegram_launch_url" target="_blank" rel="noopener">{{ miniApp.launch.telegram_launch_url }}</a>
                                <span v-else>not ready</span>
                            </div>
                            <div class="admin-meta-line mt-2">
                                <?php echo esc_html( __( 'Webhook URL' ) ); ?>:
                                <code>{{ miniApp.launch.webhook_url || 'not ready' }}</code>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" lg="6">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-bell-badge-outline</v-icon>
                            <?php echo esc_html( __( 'Alerts And Launch Features' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <div class="admin-flag-grid">
                                <v-switch v-model="miniApp.features.alerts" label="<?php echo esc_attr( __( 'Smart alerts' ) ); ?>" inset hide-details :disabled="!canWrite"></v-switch>
                                <v-switch v-model="miniApp.features.premium" label="<?php echo esc_attr( __( 'Premium' ) ); ?>" inset hide-details :disabled="!canWrite"></v-switch>
                                <v-switch v-model="miniApp.features.referrals" label="<?php echo esc_attr( __( 'Referrals' ) ); ?>" inset hide-details :disabled="!canWrite"></v-switch>
                                <v-switch v-model="miniApp.features.ton_connect" label="<?php echo esc_attr( __( 'TON Connect' ) ); ?>" inset hide-details :disabled="!canWrite"></v-switch>
                            </div>
                            <v-text-field
                                v-model.trim="miniAppSecrets.alert_worker_token"
                                class="admin-secret-input mt-3"
                                label="<?php echo esc_attr( __( 'Alert worker token' ) ); ?>"
                                type="password"
                                outlined
                                dense
                                hide-details="auto"
                                autocomplete="off"
                                :placeholder="secretPlaceholder(miniApp.alerts.worker_token)"
                                :disabled="!canWrite"
                            ></v-text-field>
                            <v-row dense class="mt-1">
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model.number="miniApp.alerts.max_alerts_per_user" label="<?php echo esc_attr( __( 'Max alerts' ) ); ?>" type="number" min="1" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model.number="miniApp.alerts.default_frequency_cap_seconds" label="<?php echo esc_attr( __( 'Frequency cap' ) ); ?>" type="number" min="300" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model.number="miniApp.alerts.default_max_deliveries_per_day" label="<?php echo esc_attr( __( 'Daily deliveries' ) ); ?>" type="number" min="1" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model.number="miniApp.alerts.evaluation_interval_seconds" label="<?php echo esc_attr( __( 'Evaluate every' ) ); ?>" type="number" min="60" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" lg="6">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-star-four-points-outline</v-icon>
                            <?php echo esc_html( __( 'Telegram Stars Premium' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <v-row dense>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model.trim="miniApp.premium.plan_code" label="<?php echo esc_attr( __( 'Plan code' ) ); ?>" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model.number="miniApp.premium.monthly_stars" label="<?php echo esc_attr( __( 'Monthly Stars' ) ); ?>" type="number" min="1" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model.number="miniApp.premium.subscription_period_seconds" label="<?php echo esc_attr( __( 'Period seconds' ) ); ?>" type="number" min="2592000" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model.trim="miniAppSecrets.premium_signing_secret"
                                        class="admin-secret-input"
                                        label="<?php echo esc_attr( __( 'Signing secret' ) ); ?>"
                                        type="password"
                                        outlined
                                        dense
                                        hide-details="auto"
                                        autocomplete="off"
                                        :placeholder="secretPlaceholder(miniApp.premium.signing_secret)"
                                        :disabled="!canWrite"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" lg="7">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-rocket-launch-outline</v-icon>
                            <?php echo esc_html( __( 'Launch Commands' ) ); ?>
                        </v-card-title>
                        <v-card-text>
                            <v-textarea
                                v-model="miniApp.launch.set_webhook_command"
                                label="<?php echo esc_attr( __( 'Register Webhook' ) ); ?>"
                                outlined
                                dense
                                readonly
                                rows="3"
                                hide-details="auto"
                                class="admin-command-output"
                            ></v-textarea>
                            <v-textarea
                                v-model="miniApp.launch.alert_worker_command"
                                label="<?php echo esc_attr( __( 'Alert worker check' ) ); ?>"
                                outlined
                                dense
                                readonly
                                rows="2"
                                hide-details="auto"
                                class="admin-command-output mt-3"
                            ></v-textarea>
                        </v-card-text>
                        <v-card-actions class="admin-command-actions">
                            <v-btn outlined color="primary" :disabled="!miniApp.launch.set_webhook_command" @click="copyText(miniApp.launch.set_webhook_command, 'Webhook command copied.')">
                                <v-icon left>mdi-content-copy</v-icon>
                                <?php echo esc_html( __( 'Register Webhook' ) ); ?>
                            </v-btn>
                            <v-spacer></v-spacer>
                            <v-btn color="primary" depressed :disabled="!canWrite" :loading="saving" @click="saveMiniApp">
                                <v-icon left>mdi-content-save-outline</v-icon>
                                <?php echo esc_html( __( 'Save Mini App Setup' ) ); ?>
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-col>
                <v-col cols="12" lg="5">
                    <v-card outlined>
                        <v-card-title class="text-subtitle-1 font-weight-bold">
                            <v-icon left color="primary">mdi-clipboard-check-outline</v-icon>
                            <?php echo esc_html( __( 'Readiness' ) ); ?>
                        </v-card-title>
                        <v-list dense>
                            <v-list-item v-for="item in miniAppReadiness" :key="item.key">
                                <v-list-item-icon>
                                    <v-icon :color="item.status === 'ok' ? 'success' : 'warning'">{{ item.status === 'ok' ? 'mdi-check-circle-outline' : 'mdi-alert-circle-outline' }}</v-icon>
                                </v-list-item-icon>
                                <v-list-item-content>
                                    <v-list-item-title>{{ item.label }}</v-list-item-title>
                                    <v-list-item-subtitle>{{ item.message }}</v-list-item-subtitle>
                                </v-list-item-content>
                            </v-list-item>
                        </v-list>
                    </v-card>
                </v-col>
            </v-row>

            <v-card v-if="activeSection === 'ton-assets'" outlined>
                <v-card-title class="text-subtitle-1 font-weight-bold">
                    <v-icon left color="primary">mdi-diamond-stone</v-icon>
                    <?php echo esc_html( __( 'Curated TON Assets' ) ); ?>
                    <v-spacer></v-spacer>
                    <v-btn icon color="primary" :disabled="!canWrite" @click="addTonAsset">
                        <v-icon>mdi-plus-circle-outline</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-alert type="info" outlined dense class="mb-4">
                        <?php echo esc_html( __( 'Priority controls display order. Assets with higher priority appear first. Use 900+ for top positions (top 10 use 700–930).' ) ); ?>
                    </v-alert>
                    <v-alert v-if="!content.ton_assets.length" type="info" outlined dense>
                        <?php echo esc_html( __( 'No curated TON assets.' ) ); ?>
                    </v-alert>
                    <v-row dense>
                        <v-col cols="12" lg="6" v-for="(asset, index) in content.ton_assets" :key="asset.local_id || index">
                            <div class="admin-ton-card" :class="{'admin-ton-card--top10': (asset.priority || 0) >= 700}">
                                <div class="admin-ton-header text-subtitle-2 font-weight-bold">
                                    <v-icon v-if="(asset.priority || 0) >= 700" small color="warning" class="mr-1">mdi-star</v-icon>
                                    {{ asset.name || 'TON asset' }}
                                    <span class="text-caption text--secondary ml-1" v-if="asset.priority">({{ asset.priority }})</span>
                                    <v-spacer></v-spacer>
                                    <v-btn icon small :disabled="!canWrite || index === 0" @click="moveTonAssetUp(index)" title="<?php echo esc_attr( __( 'Move up' ) ); ?>">
                                        <v-icon small>mdi-arrow-up</v-icon>
                                    </v-btn>
                                    <v-btn icon small :disabled="!canWrite || index === content.ton_assets.length - 1" @click="moveTonAssetDown(index)" title="<?php echo esc_attr( __( 'Move down' ) ); ?>">
                                        <v-icon small>mdi-arrow-down</v-icon>
                                    </v-btn>
                                    <v-btn icon small color="secondary" :disabled="!canWrite" @click="removeTonAsset(index)">
                                        <v-icon>mdi-delete-outline</v-icon>
                                    </v-btn>
                                </div>
                                <div class="admin-ton-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <v-text-field v-model.trim="asset.id" label="<?php echo esc_attr( __( 'Internal ID' ) ); ?>" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <v-text-field v-model.trim="asset.coin_id" label="<?php echo esc_attr( __( 'CoinGecko Coin ID' ) ); ?>" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <v-text-field v-model.trim="asset.symbol" label="<?php echo esc_attr( __( 'Symbol' ) ); ?>" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <v-text-field v-model.trim="asset.name" label="<?php echo esc_attr( __( 'Name' ) ); ?>" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                                        </v-col>
                                        <v-col cols="12" sm="4">
                                            <v-select v-model="asset.category" :items="tonCategories" label="<?php echo esc_attr( __( 'Category' ) ); ?>" outlined dense hide-details="auto" :disabled="!canWrite"></v-select>
                                        </v-col>
                                        <v-col cols="12" sm="4">
                                            <v-select v-model="asset.verification_state" :items="tonVerificationStates" label="<?php echo esc_attr( __( 'Verification' ) ); ?>" outlined dense hide-details="auto" :disabled="!canWrite"></v-select>
                                        </v-col>
                                        <v-col cols="12" sm="4">
                                            <v-text-field v-model.number="asset.priority" label="<?php echo esc_attr( __( 'Priority' ) ); ?>" type="number" min="0" max="1000" outlined dense hide-details="auto" :disabled="!canWrite" class="admin-ton-priority-field"></v-text-field>
                                        </v-col>
                                        <v-col cols="12">
                                            <v-combobox v-model="asset.tags" label="<?php echo esc_attr( __( 'Tags' ) ); ?>" outlined dense hide-details="auto" multiple small-chips deletable-chips :disabled="!canWrite"></v-combobox>
                                        </v-col>
                                    </v-row>
                                </div>
                            </div>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" depressed :disabled="!canWrite" :loading="saving" @click="saveContent">
                        <v-icon left>mdi-content-save-outline</v-icon>
                        <?php echo esc_html( __( 'Save Assets' ) ); ?>
                    </v-btn>
                </v-card-actions>
            </v-card>

            <v-card v-if="activeSection === 'legal-copy'" outlined>
                <v-card-title class="text-subtitle-1 font-weight-bold">
                    <v-icon left color="primary">mdi-file-document-edit-outline</v-icon>
                    <?php echo esc_html( __( 'Legal Copy' ) ); ?>
                </v-card-title>
                <v-card-text>
                    <v-textarea
                        v-for="copy in legalCopyDefinitions"
                        :key="copy.key"
                        v-model.trim="content.legal_copy[copy.key]"
                        :label="copy.label"
                        outlined
                        rows="3"
                        auto-grow
                        hide-details="auto"
                        class="mb-3"
                        :disabled="!canWrite"
                    ></v-textarea>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" depressed :disabled="!canWrite" :loading="saving" @click="saveContent">
                        <v-icon left>mdi-content-save-outline</v-icon>
                        <?php echo esc_html( __( 'Save Copy' ) ); ?>
                    </v-btn>
                </v-card-actions>
            </v-card>

            <v-card v-if="activeSection === 'operations'" outlined>
                <v-card-title class="text-subtitle-1 font-weight-bold">
                    <v-icon left color="primary">mdi-bell-cog-outline</v-icon>
                    <?php echo esc_html( __( 'Alert And Achievement Settings' ) ); ?>
                </v-card-title>
                <v-card-text>
                    <v-row dense>
                        <v-col cols="12" sm="6" md="3">
                            <v-text-field v-model.number="operations.alert_thresholds.max_alerts_per_user" label="<?php echo esc_attr( __( 'Max alerts' ) ); ?>" type="number" min="1" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="6" md="3">
                            <v-text-field v-model.number="operations.alert_thresholds.default_frequency_cap_seconds" label="<?php echo esc_attr( __( 'Frequency cap' ) ); ?>" type="number" min="60" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="6" md="3">
                            <v-text-field v-model.number="operations.alert_thresholds.default_max_deliveries_per_day" label="<?php echo esc_attr( __( 'Daily deliveries' ) ); ?>" type="number" min="1" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="6" md="3">
                            <v-text-field v-model.number="operations.alert_thresholds.evaluation_interval_seconds" label="<?php echo esc_attr( __( 'Evaluate every' ) ); ?>" type="number" min="60" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                        </v-col>
                    </v-row>
                    <v-divider class="my-4"></v-divider>
                    <v-row dense>
                        <v-col cols="12" sm="6" md="3">
                            <v-switch v-model="operations.achievements.enabled" label="<?php echo esc_attr( __( 'Achievements' ) ); ?>" inset hide-details :disabled="!canWrite"></v-switch>
                        </v-col>
                        <v-col cols="12" sm="6" md="3">
                            <v-text-field v-model.number="operations.achievements.daily_streak_points" label="<?php echo esc_attr( __( 'Streak points' ) ); ?>" type="number" min="0" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="6" md="3">
                            <v-text-field v-model.number="operations.achievements.share_points" label="<?php echo esc_attr( __( 'Share points' ) ); ?>" type="number" min="0" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="6" md="3">
                            <v-text-field v-model.number="operations.achievements.alert_points" label="<?php echo esc_attr( __( 'Alert points' ) ); ?>" type="number" min="0" outlined dense hide-details="auto" :disabled="!canWrite"></v-text-field>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" depressed :disabled="!canWrite" :loading="saving" @click="saveOperations">
                        <v-icon left>mdi-content-save-outline</v-icon>
                        <?php echo esc_html( __( 'Save Operations' ) ); ?>
                    </v-btn>
                </v-card-actions>
            </v-card>

            <v-card v-if="activeSection === 'audit-log'" outlined>
                <v-card-title class="text-subtitle-1 font-weight-bold">
                    <v-icon left color="primary">mdi-clipboard-text-clock-outline</v-icon>
                    <?php echo esc_html( __( 'Audit Log' ) ); ?>
                </v-card-title>
                <v-data-table
                    :headers="auditHeaders"
                    :items="auditLog"
                    :items-per-page="10"
                    dense
                    class="admin-audit-table"
                >
                    <template v-slot:item.actor="{ item }">
                        <span>{{ item.actor && item.actor.role ? item.actor.role : 'unknown' }}</span>
                    </template>
                    <template v-slot:item.created_at="{ item }">
                        <span>{{ formatDate(item.created_at) }}</span>
                    </template>
                    <template v-slot:item.request_id="{ item }">
                        <code>{{ item.request_id }}</code>
                    </template>
                </v-data-table>
            </v-card>
        </template>
    </v-container>
</section>
