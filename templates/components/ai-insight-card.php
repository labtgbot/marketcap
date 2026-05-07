<?php
/**
 * TONBANKCARD AI insight card component template.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

?>
<v-card class="ai-insight-card" outlined>
    <v-card-title class="text-subtitle-1 font-weight-bold ai-insight-card-title">
        <v-icon left color="primary" v-text="icon"></v-icon>
        <span v-text="title"></span>
        <v-spacer></v-spacer>
        <v-progress-circular
            v-if="loading"
            indeterminate
            size="20"
            width="2"
            color="primary"
        ></v-progress-circular>
    </v-card-title>
    <v-divider></v-divider>

    <v-card-text>
        <div v-if="loading" class="ai-insight-placeholder">
            <div class="tbc-skeleton ai-insight-skeleton-line"></div>
            <div class="tbc-skeleton ai-insight-skeleton-line short"></div>
        </div>

        <template v-else-if="insight">
            <div class="text-subtitle-2 font-weight-bold mb-2" v-text="insight.title"></div>
            <p class="mb-3 ai-insight-summary" v-text="insight.summary"></p>

            <div class="ai-insight-chip-row mb-3">
                <v-chip x-small label :color="sentimentColor(insight.sentiment)" text-color="white">
                    {{ insight.sentiment }}
                </v-chip>
                <v-chip x-small label :color="confidenceColor(insight.confidence)" text-color="white">
                    {{ confidenceLabel(insight.confidence) }}
                </v-chip>
                <v-chip x-small label outlined color="primary">
                    <?php echo esc_html( __( 'Not financial advice' ) ); ?>
                </v-chip>
                <v-chip x-small label outlined>
                    {{ freshnessLabel(insight) }}
                </v-chip>
            </div>

            <div class="ai-insight-list-grid" v-if="insight.drivers.length || insight.risks.length">
                <div v-if="insight.drivers.length">
                    <div class="text-caption text--secondary mb-1"><?php echo esc_html( __( 'Drivers' ) ); ?></div>
                    <ul class="ai-insight-list">
                        <li v-for="driver in insight.drivers" :key="'driver-' + driver" v-text="driver"></li>
                    </ul>
                </div>
                <div v-if="insight.risks.length">
                    <div class="text-caption text--secondary mb-1"><?php echo esc_html( __( 'Risks' ) ); ?></div>
                    <ul class="ai-insight-list">
                        <li v-for="risk in insight.risks" :key="'risk-' + risk" v-text="risk"></li>
                    </ul>
                </div>
            </div>

            <v-alert dense text color="grey darken-1" icon="mdi-help-circle-outline" class="mt-3 mb-0 ai-insight-uncertainty">
                {{ insight.uncertainty }}
            </v-alert>
        </template>

        <div v-else class="text--secondary">
            <div class="font-weight-medium mb-1">
                <?php echo esc_html( __( 'AI insight unavailable' ) ); ?>
            </div>
            <div>
                <?php echo esc_html( __( 'Market data remains available.' ) ); ?>
            </div>
            <div class="text-caption mt-2" v-if="result" v-text="unavailableReason"></div>
            <v-btn
                v-if="canRetry"
                small
                outlined
                color="primary"
                class="mt-3 ai-insight-retry-button"
                :loading="loading"
                @click="retryFetch"
            >
                <v-icon left small>mdi-refresh</v-icon>
                <?php echo esc_html( __( 'Try again' ) ); ?>
            </v-btn>
        </div>
    </v-card-text>

    <v-divider v-if="insight"></v-divider>
    <v-card-actions v-if="insight" class="ai-insight-feedback">
        <v-tooltip bottom v-for="item in feedbackOptions" :key="item.value">
            <template v-slot:activator="{ on, attrs }">
                <v-btn
                    icon
                    small
                    class="ai-insight-feedback-button"
                    :aria-label="item.label"
                    :title="item.label"
                    :loading="feedbackSubmitting && feedbackState === ''"
                    v-bind="attrs"
                    v-on="on"
                    @click="submitFeedback(item.value)"
                >
                    <v-icon small v-text="item.icon"></v-icon>
                </v-btn>
            </template>
            <span v-text="item.label"></span>
        </v-tooltip>
        <v-tooltip bottom>
            <template v-slot:activator="{ on, attrs }">
                <v-btn
                    icon
                    small
                    color="primary"
                    class="ai-insight-feedback-button"
                    aria-label="<?php echo esc_attr( __( 'Share insight' ) ); ?>"
                    title="<?php echo esc_attr( __( 'Share insight' ) ); ?>"
                    v-bind="attrs"
                    v-on="on"
                    @click="shareInsight"
                >
                    <v-icon small>mdi-share-variant</v-icon>
                </v-btn>
            </template>
            <span><?php echo esc_html( __( 'Share insight' ) ); ?></span>
        </v-tooltip>
        <v-spacer></v-spacer>
        <v-chip x-small label color="success" text-color="white" v-if="feedbackState === 'saved'">
            <?php echo esc_html( __( 'Feedback saved' ) ); ?>
        </v-chip>
        <v-chip x-small label color="warning" text-color="white" v-else-if="feedbackState === 'failed'">
            <?php echo esc_html( __( 'Feedback unavailable' ) ); ?>
        </v-chip>
    </v-card-actions>
</v-card>
