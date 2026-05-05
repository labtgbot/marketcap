<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD CHANGE NOW WIDGET
 * -------------------------------------------------------------------------
 * @package     Gecko Client
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

?>
<v-card
    class="currency-exchange-widget"
    :data-widget-status="widgetStatus"
    outlined
>
    <v-card-title class="currency-exchange-widget-title">
        <v-icon left>mdi-swap-horizontal</v-icon>
        <?php echo esc_html( __( 'Exchange' ) ); ?>
        <v-spacer></v-spacer>
        <v-chip x-small label outlined color="primary" v-text="providerName"></v-chip>
    </v-card-title>
    <v-card-subtitle v-if="isReady">
        {{ assetLabel }} &rarr; {{ targetLabel }}
    </v-card-subtitle>
    <v-card-text>
        <div v-if="isReady" class="currency-exchange-widget-frame">
            <iframe
                id="iframe-widget"
                :src="iframeSrc"
                :title="iframeTitle"
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"
                allow="clipboard-read; clipboard-write"
            ></iframe>
        </div>
        <v-alert v-else-if="widgetStatus === 'disabled'" type="info" outlined text dense class="mb-0">
            <?php echo esc_html( __( 'ChangeNOW exchange widget is disabled for this environment.' ) ); ?>
        </v-alert>
        <v-alert v-else type="info" outlined text dense class="mb-0">
            <?php echo esc_html( __( 'ChangeNOW does not list this asset for the embedded widget yet.' ) ); ?>
        </v-alert>
        <div class="currency-exchange-widget-disclaimer text-caption text--secondary mt-3">
            <?php echo esc_html( __( 'Third-party exchange availability, rates, fees, KYC rules, and execution are controlled by the provider.' ) ); ?>
        </div>
    </v-card-text>
</v-card>
