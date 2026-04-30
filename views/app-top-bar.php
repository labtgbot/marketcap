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
 */

?>
<v-app-bar app flat absolute dark color="primary darken-1">
    <?php
        /*
         * NAV ICON BUTTON
         */
        ?>
        <v-app-bar-nav-icon @click.stop="navigationDrawerModel=!navigationDrawerModel" aria-label="navigation"></v-app-bar-nav-icon>
        <?php

        /*
         * LOGO IMAGE
         *
         * See "LOGO" in "config/site.php"
         */
        if ( ! empty( $site['logo'] ) ) {
            ?>
            <router-link to="/" v-if="!navigationDrawerModel">
                <v-avatar tile class="mx-2 d-none d-sm-flex" size="32" to="/">
                    <v-img src="<?php echo esc_url( get_file_url_for_display( $site['logo'] ) ); ?>"></v-img>
                </v-avatar>
            </router-link>
            <?php
        }

        /*
         * WEBSITE NAME
         *
         * See "NAME" in "config/site.php"
         */
        ?>
        <v-toolbar-title v-if="!navigationDrawerModel" class="d-none d-lg-block">
            <router-link to="/" class="link-no-style font-weight-medium">
                <?php echo esc_html( $site['name'] ); ?>
            </router-link>
        </v-toolbar-title>
        <?php

        /*
         * SEARCH BAR COMPONENT
         */
        ?>
        <gc-search-bar class="mx-auto"></gc-search-bar>
        <?php

        /*
         * VS CURRENCY DIALOG
         */
        ?>
        <v-dialog v-model="vsCurrencyBarDialogModel" scrollable max-width="400px">
            <template v-slot:activator="{ on, attrs }">
                <v-btn text rounded v-bind="attrs" v-on="on" class="d-none d-sm-inline-flex">
                    {{ vsCurrency.id }}
                </v-btn>
            </template>
            <template v-slot:default="vsCurrencyBarDialog">
                <v-card>
                    <v-card-title>
                        <?php echo esc_html( __('Select Currency' ) ); ?>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text style="height: 300px;">
                        <v-radio-group v-model="vsCurrencyId" column>
                            <v-radio
                                v-for="currency in supportedVsCurrencies"
                                :key="currency.id"
                                :label="currency.name + ' (' + currency.unit + ')'"
                                :value="currency.id"
                            >
                            </v-radio>
                        </v-radio-group>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions>
                        <v-btn text @click="vsCurrencyBarDialogModel=false"><?php echo esc_html( __('Close' ) ); ?></v-btn>
                    </v-card-actions>
                </v-card>
            </template>
        </v-dialog>
        <?php

        /*
         * THEME TOGGLE BUTTON
         */
        ?>
        <v-btn icon @click.stop="darkTheme=!darkTheme" class="d-none d-sm-inline-flex">
            <v-icon>{{ darkTheme ? 'mdi-white-balance-sunny' : 'mdi-brightness-2' }}</v-icon>
        </v-btn>

</v-app-bar>
