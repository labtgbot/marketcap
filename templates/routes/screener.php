<?php
/**
 * TONBANKCARD public screener route.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$frontend_options['screener']['title'] = __( 'Crypto Screener' );

$route_screener_filters = [
    'Market capitalization',
    '24h, 7d, and 30d movement',
    'Volume and liquidity',
    'Category and TON ecosystem tags',
    'Freshness, alerts, and sentiment signals',
];

?>
<v-container tag="section" id="screener" class="mt-8 mb-16 pa-4 pa-sm-6">
    <h1 class="text-h4 text-sm-h4 mb-4">
        <?php echo esc_html( $frontend_options['screener']['title'] ); ?>
    </h1>
    <p class="text-body-1">
        <?php echo esc_html( 'The screener route defines the public website surface for advanced market filtering. It currently points users to the live market table while V2 filter controls and saved presets are built.' ); ?>
    </p>

    <v-row class="mt-6">
        <v-col cols="12" md="7">
            <h2 class="text-h6 mb-3"><?php echo esc_html( 'Planned filter coverage' ); ?></h2>
            <v-list dense>
                <?php foreach ( $route_screener_filters as $filter ) : ?>
                    <v-list-item>
                        <v-list-item-icon>
                            <v-icon color="primary">mdi-filter-variant</v-icon>
                        </v-list-item-icon>
                        <v-list-item-content>
                            <v-list-item-title><?php echo esc_html( $filter ); ?></v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>
                <?php endforeach; ?>
            </v-list>
        </v-col>
        <v-col cols="12" md="5">
            <h2 class="text-h6 mb-3"><?php echo esc_html( 'Available now' ); ?></h2>
            <p>
                <?php echo esc_html( 'Sort the market table by rank, price, percentage movement, market capitalization, volume, and circulating supply without requiring Telegram sign-in.' ); ?>
            </p>
            <v-btn color="primary" depressed <?php to_attr( 'markets' ); ?>>
                <v-icon left>mdi-table-search</v-icon>
                <?php echo esc_html( 'Open market table' ); ?>
            </v-btn>
        </v-col>
    </v-row>
</v-container>
