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

?>
<v-container class="gc-page-loader" v-if="loading">
    <v-row class="fill-height" align-content="center" justify="center">
        <v-col cols="6">
            <v-progress-linear indeterminate height="8"></v-progress-linear>
        </v-col>
    </v-row>
</v-container>
