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
$component_disclaimer_message['text'] = 'The information provided on this website does not constitute investment advice, financial advice, trading advice, or any other sort of advice and you should not treat any of the website\'s content as such. We does not recommend that any cryptocurrency should be bought, sold, or held by you. Do conduct your own due diligence and consult your financial advisor before making any investment decisions.';

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
