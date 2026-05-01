<?php
/**
 * TONBANKCARD shareable market card component template.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

?>
<v-card class="share-market-card" :class="{'share-market-card--dense': dense}" outlined>
    <v-card-title class="share-market-card-title">
        <div class="min-width-0">
            <div class="text-subtitle-1 font-weight-bold text-truncate" v-text="normalizedCard.title"></div>
            <div class="text-caption text--secondary text-truncate" v-if="normalizedCard.subtitle" v-text="normalizedCard.subtitle"></div>
        </div>
        <v-spacer></v-spacer>
        <v-btn icon color="primary" :loading="sharing" :aria-label="shareLabel" :title="shareLabel" @click="shareCard">
            <v-icon>mdi-share-variant</v-icon>
        </v-btn>
    </v-card-title>
    <v-card-text>
        <p class="share-market-card-body mb-3" v-if="normalizedCard.body" v-text="normalizedCard.body"></p>
        <div class="share-market-card-metrics" v-if="metrics.length">
            <div v-for="metric in metrics" :key="metric.label">
                <span class="text-caption text--secondary" v-text="metric.label"></span>
                <strong v-text="metric.value"></strong>
            </div>
        </div>
        <div class="share-market-card-footer mt-3">
            <v-chip x-small label outlined>
                <v-icon left x-small>mdi-clock-check-outline</v-icon>
                {{ normalizedCard.freshness || 'Data freshness unavailable' }}
            </v-chip>
            <v-chip x-small label outlined color="primary">
                <v-icon left x-small>mdi-shield-check-outline</v-icon>
                <?php echo esc_html( __( 'Not financial advice' ) ); ?>
            </v-chip>
            <v-chip x-small label color="success" text-color="white" v-if="copied">
                <?php echo esc_html( __( 'Ready' ) ); ?>
            </v-chip>
        </div>
    </v-card-text>
</v-card>
