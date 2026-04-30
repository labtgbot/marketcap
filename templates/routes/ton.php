<?php
/**
 * -------------------------------------------------------------------------
 * GECKO CLIENT
 * -------------------------------------------------------------------------
 * @package     Gecko Client
 * @author      RunCoders
 * @license     Envato Market Regular License (https://1.envato.market/regular-license)
 * @copyright   Copyright (c) 2021 RunCoders (https://runcoders.net)
 * @since	    1.0.0
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$frontend_options['ton']['title'] = __( 'TON Ecosystem' );

?>
<section class="py-8 py-sm-10">
    <v-container id="ton" fluid>
        <h1 class="text-h4 text-sm-h3 font-weight-bold mb-4">
            <?php echo esc_html( $frontend_options['ton']['title'] ); ?>
        </h1>
        <v-row dense>
            <v-col cols="12" md="6">
                <v-card outlined>
                    <v-card-title class="text-subtitle-1 font-weight-bold">
                        <v-icon left color="primary">mdi-diamond-stone</v-icon>
                        <?php echo esc_html( __( 'Toncoin' ) ); ?>
                    </v-card-title>
                    <v-card-text>
                        <?php echo esc_html( __( 'Toncoin anchors the first TON view. Curated jettons, DeFi, stablecoins, wallets, and infrastructure appear as market data is connected.' ) ); ?>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn text color="primary" :to="{name:'currency', params:{id:'toncoin'}}">
                            <?php echo esc_html( __( 'Open Toncoin' ) ); ?>
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
            <v-col cols="12" md="6">
                <v-card outlined>
                    <v-card-title class="text-subtitle-1 font-weight-bold">
                        <v-icon left color="primary">mdi-pulse</v-icon>
                        <?php echo esc_html( __( 'Market Pulse' ) ); ?>
                    </v-card-title>
                    <v-card-text>
                        <?php echo esc_html( __( 'TON movers stay visible on Market Pulse with the rest of the live market context.' ) ); ?>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn text color="primary" :to="{name:'currencies'}">
                            <?php echo esc_html( __( 'Back to Market Pulse' ) ); ?>
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</section>
