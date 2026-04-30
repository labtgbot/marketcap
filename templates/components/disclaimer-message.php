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
| DESCRIPTION: Disclaimer title.
|
*/
$component_disclaimer_message['title'] = 'Disclaimer';

/*
| -------------------------------------------------------------------------
| TEXT
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Disclaimer text.
|
*/
$component_disclaimer_message['text'] = 'TONBANKCARD Crypto Tracker provides market data, AI summaries, alerts, and swap widgets for informational purposes only. Prices, liquidity, generated summaries, and alert triggers may be delayed, incomplete, or wrong. Nothing on this website is investment, financial, legal, tax, or trading advice, and TONBANKCARD does not recommend buying, selling, holding, borrowing, lending, swapping, or staking any asset.';

?>
<section class="gc-disclaimer-message caption">
    <?php
        if ( ! empty( $component_disclaimer_message['title'] ) ) {
            ?>
            <strong><?php echo esc_html( $component_disclaimer_message['title'] ); ?>:</strong>
            <?php
        }

        echo esc_html( $component_disclaimer_message['text'] );
    ?>
</section>
