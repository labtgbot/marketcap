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
 * @var array $navigation
 */

?>
<v-navigation-drawer app v-model="navigationDrawerModel"<?php if ( ! empty( $site['rtl'] ) ) echo  ' right'; ?>>
    <?php
    /*
     * NAVIGATION HEADER
     */
    ?>
    <template v-slot:prepend>
        <v-list-item class="px-2">
            <?php
            /*
             * See "LOGO" in "config/site.php"
             */
            if ( ! empty( $site['logo'] ) ) {
                ?>
                <v-list-item-avatar tile>
                    <v-img src="<?php echo esc_url( get_file_url_for_display( $site['logo'] ) ); ?>"></v-img>
                </v-list-item-avatar>
                <?php
            }

            /*
             * See "NAME" in "config/site.php"
             */
            ?>
            <v-list-item-title class="text-h6">
                <?php echo esc_html( $site['name'] ); ?>
            </v-list-item-title>

        </v-list-item>
    </template>

    <v-divider></v-divider>

    <?php
    /*
     * NAVIGATION ITEMS
     */
    ?>
    <v-list dense>
        <?php
        /*
         * See "ITEMS" in "config/navigation.php"
         */
        foreach ( $navigation['items'] as $item ) {
            // add divider before item
            if ( ! empty( $item['divider'] ) && 'before' === $item['divider'] ) {
                ?>
                <v-divider></v-divider>
                <?php
            }

            // open list group when has submenu items
            if ( ! empty( $item['items'] ) ) {
                ?>
                <v-list-group no-action color <?php attr( 'prepend-icon', $item['icon'], ! empty( $item['icon'] ) ); ?>>
                    <template v-slot:activator>
                        <v-list-item-content>
                            <v-list-item-title><?php echo esc_html( $item['text'] ); ?></v-list-item-title>
                        </v-list-item-content>
                    </template>
                <?php
            }

            // print item(s)
            $list_items = empty( $item['items'] ) ? [ $item ] : $item['items'];
            foreach ( $list_items as $list_item ) {
                ?>
                <v-list-item <?php link_attrs( $list_item ); ?>>
                    <?php if ( ! empty( $list_item['icon'] ) ) : ?>
                        <v-list-item-icon>
                            <v-icon><?php echo esc_html( $list_item['icon'] ); ?></v-icon>
                        </v-list-item-icon>
                    <?php endif; ?>

                    <?php if ( ! empty( $list_item['text'] ) ) : ?>
                        <v-list-item-content>
                            <v-list-item-title><?php echo esc_html( $list_item['text'] ); ?></v-list-item-title>
                        </v-list-item-content>
                    <?php endif; ?>
                </v-list-item>
                <?php
            }

            // close list group when has submenu items
            if ( ! empty( $item['items'] ) ) {
                ?>
                </v-list-group>
                <?php
            }

            // add divider after item
            if ( ! empty( $item['divider'] ) && 'after' === $item['divider'] ) {
                ?>
                <v-divider></v-divider>
                <?php
            }
        }
        ?>
    </v-list>

    <v-divider></v-divider>

    <?php
    /*
     * NAVIGATION SETTINGS MENU
     */
    ?>
    <v-list dense>
        <v-list-group prepend-icon="mdi-cog" color>
            <template v-slot:activator>
                <v-list-item-content>
                    <v-list-item-title><?php echo esc_html( __( 'Settings' ) ); ?></v-list-item-title>
                </v-list-item-content>
            </template>

            <?php
            /*
             * VS CURRENCY DIALOG
             */
            ?>
            <v-dialog v-model="vsCurrencyNavDialogModel" scrollable max-width="400px">
                <template v-slot:activator="{ on, attrs }">
                    <v-list-item v-bind="attrs" v-on="on">
                        <v-list-item-icon>
                            <v-icon>mdi-cash-multiple</v-icon>
                        </v-list-item-icon>
                        <v-list-item-content>
                            <v-list-item-title>
                                {{ vsCurrency.name }} ({{ vsCurrency.unit }})
                            </v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>
                </template>
                <template v-slot:default="vsCurrencyNavDialog">
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
                            <v-btn text @click="vsCurrencyNavDialogModel=false"><?php echo esc_html( __('Close' ) ); ?></v-btn>
                        </v-card-actions>
                    </v-card>
                </template>
            </v-dialog>

            <?php
            /*
             * THEME TOGGLE ITEM
             */
            ?>
            <v-list-item @click.stop="darkTheme=!darkTheme">
                <v-list-item-icon>
                    <v-icon>mdi-invert-colors</v-icon>
                </v-list-item-icon>
                <v-list-item-content>
                    <v-list-item-title>
                        {{ darkTheme ? '<?php echo esc_html( __( 'Dark Theme' ) ); ?>' : '<?php echo esc_html( __( 'Light Theme' ) ); ?>' }}
                    </v-list-item-title>
                </v-list-item-content>
            </v-list-item>

        </v-list-group>

    </v-list>
</v-navigation-drawer>
