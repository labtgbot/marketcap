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
| BUTTON BUY TEXT
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Header icon class name from Material Design Icons (mdi-*).
|
*/
$component_currency_converter['button_buy_text'] = __( 'Buy' );

/*
| -------------------------------------------------------------------------
| BUTTON SELL TEXT
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Header text.
|
*/
$component_currency_converter['button_sell_text'] = __( 'Sell' );

?>
<v-row class="gc-currency-converter" dense justify="center" v-if="isValid">
    <v-col v-if="buy" cols="4" sm="auto" align-self="center" order="2" order-sm="1">
        <v-btn color="high" block depressed class="high_text--text" :href="buyHref" target="_blank" rel="noopener">
            <?php echo esc_html( $component_currency_converter['button_buy_text'] ); ?>
        </v-btn>
    </v-col>
    <v-col cols="8" sm="4" md="3" lg="2" order="1">
        <v-text-field
                :name="randomName()"
                outlined
                dense
                v-model="baseModel"
                :suffix="baseSymbol | uppercase"
                hide-details
                @input="baseUpdated"
                type="number"
        ></v-text-field>
    </v-col>
    <v-col cols="8" sm="4" md="3" lg="2" order="3">
        <v-text-field
                :name="randomName()"
                outlined
                dense
                v-model="quoteModel"
                :suffix="quoteSymbol | uppercase"
                hide-details
                @input="quoteUpdated"
                type="number"
        ></v-text-field>
    </v-col>
    <v-col v-if="sell" cols="4" sm="auto" align-self="center" order="4">
        <v-btn color="low" block depressed class="low_text--text" :href="sellHref" target="_blank" rel="noopener">
            <?php echo esc_html( $component_currency_converter['button_sell_text'] ); ?>
        </v-btn>
    </v-col>
</v-row>
