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
<div class="gc-currency-chart" v-resize="resize">
    <v-row class="my-4">
        <v-col class="d-none d-sm-flex">
            <v-btn-toggle v-model="selectedSeries" color="primary" mandatory dense group @change="updateChart">
                <v-btn v-for="item in series" :key="item.value" :value="item.value" v-text="item.text"></v-btn>
            </v-btn-toggle>
        </v-col>
        <v-col class="d-sm-none">
            <v-select dense filled v-model="selectedSeries" :items="series" @change="updateChart"></v-select>
        </v-col>
        <v-spacer class="d-none d-sm-flex"></v-spacer>
        <v-col class="justify-end d-none d-sm-flex">
            <v-btn-toggle v-model="selectedInterval" color="primary" mandatory dense group @change="updateChart">
                <v-btn v-for="item in intervals" :key="item.value" :value="item.value" v-text="item.text"></v-btn>
            </v-btn-toggle>
        </v-col>
        <v-col class="d-sm-none">
            <v-select dense filled v-model="selectedInterval" :items="intervals" @change="updateChart"></v-select>
        </v-col>
    </v-row>

    <div v-if="loading" class="gc-currency-chart-loader d-flex align-center justify-center">
        <v-progress-circular :size="70" :width="7" indeterminate color="primary"></v-progress-circular>
    </div>
    <div v-else class="gc-currency-chart-container" ref="chartContainer"></div>
</div>
