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
 * @var array $footer
 */

?>
<v-footer dark color="primary">
    <v-row justify="center" no-gutters>
        <?php
            /*
             * FOOTER LINKS
             *
             * See "LINKS" in "config/footer.php"
             */
            if ( ! empty( $footer['links'] ) ) {
                foreach ( $footer['links'] as $link ) {
                    $link_has_text = ! empty( $link['text'] );
                    $link_has_icon = ! empty( $link['icon'] );
                    $link_attrs    = [];

                    if ( $link_has_text ) {
                        $link_attrs[] = 'text';
                        $link_attrs[] = 'rounded';
                    } elseif ( $link_has_icon ) {
                        $link_attrs[] = 'icon';
                    } else { // do not show if empty
                        continue;
                    }

                    ?>
                    <v-btn small class="my-2 mx-1" <?php link_attrs( $link, $link_attrs ); ?>>
                        <?php
                        if ( $link_has_text ) {
                            echo esc_html( $link['text'] );
                        }
                        if ( $link_has_icon ) {
                            ?>
                            <v-icon small<?php if ( $link_has_text ) echo ' right'; ?>>
                                <?php echo esc_html( $link['icon'] ); ?>
                            </v-icon>
                            <?php
                        }
                        ?>
                    </v-btn>
                    <?php
                }
            }

            /*
             * FOOTER COPYRIGHTS
             *
             * See "COPYRIGHTS" in "config/footer.php"
             */
            if ( ! empty( $footer['copyrights'] ) ) {
                ?>
                <v-col class="py-2 text-center" cols="12">
                    <span class="caption"><?php echo esc_html( $footer['copyrights'] ); ?></span>
                </v-col>
                <?php
            }
        ?>
    </v-row>
</v-footer>
