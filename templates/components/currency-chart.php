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
    <v-row class="gc-currency-chart-toolbar my-4" dense align="center">
        <v-col cols="12" md="7" class="d-none d-sm-flex">
            <v-btn-toggle v-model="selectedSeries" color="primary" mandatory dense group @change="updateChart">
                <v-btn v-for="item in series" :key="item.value" :value="item.value" v-text="item.text"></v-btn>
            </v-btn-toggle>
        </v-col>
        <v-col cols="12" class="d-sm-none">
            <v-select
                dense
                filled
                hide-details
                aria-label="Chart metric"
                v-model="selectedSeries"
                :items="series"
                @change="updateChart"
            ></v-select>
        </v-col>
        <v-spacer class="d-none d-md-flex"></v-spacer>
        <v-col cols="12" md="5" class="justify-md-end d-none d-sm-flex">
            <v-btn-toggle v-model="selectedInterval" color="primary" mandatory dense group @change="updateChart">
                <v-btn v-for="item in intervals" :key="item.value" :value="item.value" v-text="item.text"></v-btn>
            </v-btn-toggle>
        </v-col>
        <v-col cols="12" class="d-sm-none">
            <v-select
                dense
                filled
                hide-details
                aria-label="Chart range"
                v-model="selectedInterval"
                :items="intervals"
                @change="updateChart"
            ></v-select>
        </v-col>
    </v-row>

    <v-alert v-if="error" type="warning" outlined dense class="mb-4">
        <div class="d-flex flex-wrap align-center justify-space-between">
            <span v-text="errorMessage"></span>
            <v-btn small text color="primary" @click="updateChart">
                <?php echo esc_html( __( 'Retry' ) ); ?>
            </v-btn>
        </div>
    </v-alert>
    <v-alert v-else-if="isStale" type="warning" outlined dense class="mb-4">
        <?php echo esc_html( __( 'Market chart data is stale. Use the freshness label before acting on prices.' ) ); ?>
        <span v-if="freshnessLabel"> {{ freshnessLabel }}</span>
    </v-alert>

    <div class="gc-currency-chart-summary mb-3" v-if="summaryStats.length">
        <div class="gc-currency-chart-summary-item" v-for="stat in summaryStats" :key="stat.label">
            <span class="text-caption text-uppercase text--secondary" v-text="stat.label"></span>
            <strong v-text="stat.value"></strong>
        </div>
    </div>

    <div class="gc-currency-chart-shell">
        <div v-if="loading" class="gc-currency-chart-loader tbc-skeleton" aria-hidden="true"></div>
        <div
            v-show="!loading && !error"
            class="gc-currency-chart-container"
            ref="chartContainer"
            role="img"
            :aria-label="chartAriaLabel"
            :aria-describedby="chartSummaryId"
        ></div>
        <div :id="chartSummaryId" class="tbc-sr-only" v-text="chartSummary"></div>
    </div>
</div>
