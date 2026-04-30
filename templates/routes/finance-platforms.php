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

/*
| -------------------------------------------------------------------------
| TITLE
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Route title.
|
*/
$frontend_options['finance-platforms']['title'] = __( 'Finance Platforms' );

/*
| -------------------------------------------------------------------------
| TYPES
| -------------------------------------------------------------------------
| TYPE: array[]
| DESCRIPTION: Types details.
|
| -------------------------------------------------------------------------
| EXPLANATION OF SERIES PARAMETERS
| -------------------------------------------------------------------------
|
|   ['value']   (string)    Type id
|   ['text']    (string)    Type title
|
*/
$frontend_options['finance-platforms']['types'] = [
    [
        'value' => 'all',
        'text'  => __( 'All' ),
    ],
    [
        'value' => 'defi',
        'text'  => __( 'Decentralized' ),
    ],
    [
        'value' => 'cefi',
        'text'  => __( 'Centralized' ),
    ]
];

/*
| -------------------------------------------------------------------------
| SORT
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Set TRUE to sort platforms by name (asc).
|
*/
$frontend_options['finance-platforms']['sort'] = TRUE;

/*
| -------------------------------------------------------------------------
| DOTS
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show CeFi/DeFi indication dots.
|
*/
$route_finance_platforms['dots'] = TRUE;

?>
<section class="mt-8 mb-16">
    <gc-page-loader :loading="loading"></gc-page-loader>

    <v-container id="finance-platforms" v-if="!loading">
        <h1 class="v-heading text-h4 text-sm-h4 mb-8">
            <?php echo esc_html( $frontend_options['finance-platforms']['title'] ); ?>
        </h1>

        <v-row>
            <v-col cols="auto">
                <v-select :items="types" v-model="selectedType" label="<?php echo esc_attr( __( 'Type' ) ); ?>"></v-select>
            </v-col>
        </v-row>

        <v-row id="finance-platforms-grid">
            <v-col v-for="(platform,index) in filteredPlatforms" :key="index" cols="12" sm="6" md="4" lg="3">
                <v-card>
                    <v-card-title v-text="platform.name"></v-card-title>
                    <v-card-subtitle>
                        <?php
                            if ( empty( $route_finance_platforms['dots'] ) ) {
                                ?>
                                <span v-text="platform.catLabel"></span>
                                <?php
                            } else {
                                ?>
                                <v-badge :color="platform.color" dot inline>
                                    <span v-text="platform.catLabel"></span>
                                </v-badge>
                                <?php
                            }
                        ?>
                    </v-card-subtitle>
                    <v-card-actions>
                        <v-btn text color="primary" :href="platform.url" target="_blank" rel="noopener">
                            <?php echo esc_html( __( 'Platform' ) ); ?>
                            <v-icon right>mdi-open-in-new</v-icon>
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>

    </v-container>
</section>
