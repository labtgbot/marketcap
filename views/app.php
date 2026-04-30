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

/**
 * @var array $site
 * @var array $footer
 */

?>
<!DOCTYPE html>
<html lang="<?php echo empty( $site['lang'] ) ? 'en' : esc_attr( $site['lang'] ); ?>">
<?php require_once GECKO_CLIENT_VIEWS_DIR . '/app-head.php'; ?>
<body>
<div id="app-wrapper">
    <v-app>
        <?php
            require_once GECKO_CLIENT_VIEWS_DIR . '/app-navigation.php';
            require_once GECKO_CLIENT_VIEWS_DIR . '/app-top-bar.php';
        ?>
        <v-main>
            <?php
                /*
                 * STATS BAR COMPONENT
                 */
                ?>
                <gc-stats-bar></gc-stats-bar>
                <?php

                /*
                 * TRENDING COINS COMPONENT
                 */
                ?>
                <gc-trending-coins></gc-trending-coins>
                <?php

                /*
                 * VUE ROUTER VIEW
                 */
                ?>
                <router-view></router-view>
                <?php

                /*
                 * COPIED SNACKBAR
                 */
                ?>
                <v-snackbar id="copied-snackbar" v-model="copiedModel" top right timeout="2000">
                    <v-icon left>mdi-check</v-icon>
                    <?php echo esc_html( __( 'Copied' ) ); ?>
                    <template v-slot:action="{ attrs }">
                        <v-btn icon v-bind="attrs" @click="copiedModel = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </template>
                </v-snackbar>
                <?php

                /*
                 * DISCLAIMER MESSAGE COMPONENT
                 *
                 * Enabled by "DISCLAIMER" in "config/site.php"
                 */
                if ( ! empty( $site['disclaimer'] ) ) {
                    ?>
                    <v-divider></v-divider>
                    <gc-disclaimer-message></gc-disclaimer-message>
                    <?php
                }
            ?>
        </v-main>
        <?php
            /*
             * APP FOOTER
             *
             * See "SHOW" in "config/footer.php"
             */
            if ( ! empty( $footer['show'] ) ) {
                require_once GECKO_CLIENT_VIEWS_DIR . '/app-footer.php';
            }

            /*
             * COOKIES DIALOG COMPONENT
             *
             * Enabled by "COOKIES WARNING" in "config/site.php"
             */
            if ( ! empty( $site['cookies_warning'] ) ) {
                ?>
                <gc-cookies-dialog></gc-cookies-dialog>
                <?php
            }
        ?>
    </v-app>
</div>
<?php require_once GECKO_CLIENT_VIEWS_DIR . '/app-scripts.php'; ?>
</body>
</html>

