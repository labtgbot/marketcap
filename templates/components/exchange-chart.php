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
<v-card class="gc-exchange-chart" v-resize="resize">
    <v-card-text class="d-flex justify-end">
        <v-btn-toggle class="d-none d-sm-flex" v-model="selectedInterval" color="primary" mandatory dense group @change="updateChart">
            <v-btn v-for="item in intervals" :key="item.value" :value="item.value" v-text="item.text"></v-btn>
        </v-btn-toggle>
        <v-select class="d-flex d-sm-none" dense filled v-model="selectedInterval" :items="intervals" @change="updateChart"></v-select>
    </v-card-text>
    <div v-if="loading" class="gc-exchange-chart-loader d-flex align-center justify-center">
        <v-progress-circular :size="70" :width="7" indeterminate color="primary"></v-progress-circular>
    </div>
    <div v-else class="gc-exchange-chart-container" ref="chartContainer"></div>
</v-card>
