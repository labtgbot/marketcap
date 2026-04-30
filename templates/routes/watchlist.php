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

$frontend_options['watchlist']['title'] = __( 'Watchlist' );

?>
<section class="py-8 py-sm-10">
    <v-container id="watchlist" fluid>
        <h1 class="text-h4 text-sm-h3 font-weight-bold mb-4">
            <?php echo esc_html( $frontend_options['watchlist']['title'] ); ?>
        </h1>
        <v-alert type="info" outlined>
            <?php echo esc_html( __( 'No watched coins yet. Market Pulse still shows movers and trending assets while your list is empty.' ) ); ?>
        </v-alert>
        <v-btn color="primary" depressed :to="{name:'currencies'}">
            <v-icon left>mdi-arrow-left</v-icon>
            <?php echo esc_html( __( 'Back to Market Pulse' ) ); ?>
        </v-btn>
    </v-container>
</section>
