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
|
| TYPE: string
| DESCRIPTION: Dialog title.
|
*/
$component_cookies_dialog['title'] = 'We Use Cookies';

/*
| -------------------------------------------------------------------------
| MESSAGE
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Dialog message.
|
*/
$component_cookies_dialog['message'] = 'By continuing to browse or by clicking "Accept", you agree to the storing of cookies on your device to improve user experience and for analytical purposes.';

/*
| -------------------------------------------------------------------------
| BUTTON ACCEPT TEXT
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Text for accept button.
|
*/
$component_cookies_dialog['button_accept_text'] = 'Accept';

/*
| -------------------------------------------------------------------------
| BUTTON POLICY SHOW
| -------------------------------------------------------------------------
|
| TYPE: bool
| DESCRIPTION: Set TRUE to show policy button.
|
*/
$component_cookies_dialog['button_policy_show'] = ! empty( $routes['cookies-policy']['enabled'] );

/*
| -------------------------------------------------------------------------
| BUTTON POLICY TEXT
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: The text for cookies dialog reject button.
|
*/
$component_cookies_dialog['button_policy_text'] = 'Cookies Policy';

?>

<v-dialog class="gc-cookies-dialog" v-model="dialogModel" max-width="600">
    <v-card>
        <v-card-title class="text-h5">
            <?php echo esc_html( $component_cookies_dialog['title'] ); ?>
            <v-spacer></v-spacer>
            <v-btn icon @click="close">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>

        <v-card-text>
            <?php echo esc_html( $component_cookies_dialog['message'] ); ?>
        </v-card-text>

        <v-card-actions>
            <v-spacer></v-spacer>

            <?php if ( ! empty( $component_cookies_dialog['button_policy_show'] ) ) : ?>
                <v-btn text @click="close" <?php to_attr( 'cookies-policy' ); ?>>
                    <?php echo esc_html( $component_cookies_dialog['button_policy_text'] ); ?>
                </v-btn>
            <?php endif; ?>

            <v-btn color="green" dark @click="accept">
                <?php echo esc_html( $component_cookies_dialog['button_accept_text'] ); ?>
            </v-btn>
        </v-card-actions>
    </v-card>
</v-dialog>
