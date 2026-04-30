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
| ICON
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Header icon class name (mdi-*). Icons: https://materialdesignicons.com/
|
*/
$component_trending_coins['icon'] = 'mdi-trending-up';

/*
| -------------------------------------------------------------------------
| TITLE
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Header text.
|
*/
$component_trending_coins['title'] = __( 'Trending' );

?>
<section class="gc-trending-coins py-4 py-sm-6"  v-if="coins.length">
    <div class="d-flex flex-column align-center">
        <div class="subtitle-2">
            <v-icon left><?php echo esc_html( $component_trending_coins['icon'] ); ?></v-icon>
            <?php echo esc_html( $component_trending_coins['title'] ); ?>
        </div>
        <div class="coins">
            <v-chip-group active-class="primary--text" center-active :show-arrows="!$root.isMobileUserAgent">
                <v-chip class="pa-3" v-for="coin in coins" :key="coin.id" :to="coin.route">
                    <v-avatar left v-if="coin.small" color="white">
                        <img :src="coin.small" :alt="coin.name">
                    </v-avatar>
                    {{ coin.name }}
                </v-chip>
            </v-chip-group>
        </div>
    </div>
</section>
