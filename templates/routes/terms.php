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
 * @var array $routes
 */

/*
| -------------------------------------------------------------------------
| TITLE
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Route title.
|
*/
$frontend_options['terms']['title'] = __( 'Terms' );

/*
| -------------------------------------------------------------------------
| NAME
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Website name.
|
*/
$route_terms['name'] = $site['name'];

/*
| -------------------------------------------------------------------------
| SITE URL
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Homepage URL.
|
*/
$route_terms['site_url'] = site_url();

/*
| -------------------------------------------------------------------------
| COOKIES
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Cookies" section.
|
*/
$route_terms['cookies'] = TRUE;

/*
| -------------------------------------------------------------------------
| PRIVACY POLICY URL
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Privacy policy URL. Default to "privacy-policy" route URL if enabled.
|
*/
$route_terms['privacy_policy_url'] = empty( $routes['privacy-policy']['enabled'] ) ? '' : site_url( $routes['privacy-policy']['path'] );

/*
| -------------------------------------------------------------------------
| COOKIES POLICY URL
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Cookies policy URL. Default to "cookies-policy" route URL if enabled.
|
*/
$route_terms['cookies_policy_url'] = empty( $routes['cookies-policy']['enabled'] ) ? '' : site_url( $routes['cookies-policy']['path'] );

/*
| -------------------------------------------------------------------------
| LICENSE
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "License" section.
|
*/
$route_terms['license'] = TRUE;

/*
| -------------------------------------------------------------------------
| IFRAMES
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "iFrames" section.
|
*/
$route_terms['iframes'] = TRUE;

/*
| -------------------------------------------------------------------------
| RESERVATION RIGHTS
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Reservation of Rights" section.
|
*/
$route_terms['reservation_rights'] = TRUE;

/*
| -------------------------------------------------------------------------
| REMOVAL LINKS
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Removal of links from our website" section.
|
*/
$route_terms['removal_links'] = TRUE;

/*
| -------------------------------------------------------------------------
| DISCLAIMER
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Removal of links from our website" section.
|
*/
$route_terms['disclaimer'] = TRUE;

?>
<v-container tag="section" id="terms" class="mt-8 mb-16 pa-4 pa-sm-6">
    <h1 class="text-h4 text-sm-h3 mb-6">
        <?php echo esc_html( $frontend_options['terms']['title'] ); ?>
    </h1>
    <p>
        <?php echo esc_html( "These terms and conditions outline the rules and regulations for the use of {$route_terms['name']}’s Website, located at \"{$route_terms['site_url']}\"." ); ?>
        <br>
        <?php echo esc_html( "By accessing this website we assume you accept these terms and conditions. Do not continue to use {$route_terms['name']} if you do not agree to take all of the terms and conditions stated on this page." ); ?>
    </p>

    <?php if ( ! empty( $route_terms['cookies'] ) ) : ?>
    <div class="mt-12">
        <h3 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( "Cookies" ); ?>
        </h3>
        <p>
            <?php echo esc_html( "We employ the use of cookies." ); ?>
            <?php
                $has_privacy_url = ! empty( $route_terms['privacy_policy_url'] );
                $has_cookies_url = ! empty( $route_terms['cookies_policy_url'] );
            ?>
            <?php if ( $has_privacy_url || $has_cookies_url) : ?>
                <?php echo esc_html( "By accessing {$route_terms['name']}, you agreed to use cookies in agreement with the {$route_terms['name']}’s" ); ?>
                <?php if ( $has_privacy_url ) : ?>
                    <a href="<?php echo esc_url( $route_terms['privacy_policy_url'] ); ?>"><?php echo esc_html( "Privacy Policy" ); ?></a>
                <?php endif; ?>
                <?php if ( $has_privacy_url && $has_cookies_url ) echo esc_html( 'and' ); ?>
                <?php if ( $has_cookies_url ) : ?>
                    <a href="<?php echo esc_url( $route_terms['cookies_policy_url'] ); ?>"><?php echo esc_html( "Cookies Policy" ); ?></a>
                <?php endif; ?>
                .
            <?php endif; ?>
        </p>
        <p>
            <?php echo esc_html( "Most interactive websites use cookies to let us retrieve the user’s details for each visit. Cookies are used by our website to enable the functionality of certain areas to make it easier for people visiting our website. Some of our affiliate/advertising partners may also use cookies." ); ?>
        </p>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_terms['license'] ) ) : ?>
    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( "License" ); ?>
        </h2>
        <p>
            <?php echo esc_html( "Unless otherwise stated, {$route_terms['name']} and/or its licensors own the intellectual property rights for all material on {$route_terms['name']}." ); ?>
            <?php echo esc_html( "All intellectual property rights are reserved." ); ?>
            <?php echo esc_html( "You may access this from {$route_terms['name']} for your own personal use subjected to restrictions set in these terms and conditions." ); ?>
        </p>
        <p>
            <?php echo esc_html( "You must not:" ); ?>
        </p>
        <ul>
            <li>
                <?php echo esc_html( "Republish material from {$route_terms['name']}" ); ?>
            </li>
            <li>
                <?php echo esc_html( "Sell, rent or sub-license material from {$route_terms['name']}" ); ?>
            </li>
            <li>
                <?php echo esc_html( "Reproduce, duplicate or copy material from {$route_terms['name']}" ); ?>
            </li>
            <li>
                <?php echo esc_html( "Redistribute content from {$route_terms['name']}" ); ?>
            </li>
        </ul>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_terms['iframes'] ) ) : ?>
    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( "iFrames" ); ?>
        </h2>
        <p>
            <?php echo esc_html( "Without prior approval and written permission, you may not create frames around our Webpages that alter in any way the visual presentation or appearance of our Website." ); ?>
        </p>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_terms['reservation_rights'] ) ) : ?>
        <div class="mt-12">
            <h2 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "Reservation of Rights" ); ?>
            </h2>
            <p>
                <?php echo esc_html( "We reserve the right to request that you remove all links or any particular link to our Website." ); ?>
                <?php echo esc_html( "You approve to immediately remove all links to our Website upon request." ); ?>
                <?php echo esc_html( "We also reserve the right to amen these terms and conditions and it’s linking policy at any time." ); ?>
                <?php echo esc_html( "By continuously linking to our Website, you agree to be bound to and follow these linking terms and conditions." ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_terms['removal_links'] ) ) : ?>
        <div class="mt-12">
            <h2 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "Removal of links from our website" ); ?>
            </h2>
            <p>
                <?php echo esc_html( "If you find any link on our Website that is offensive for any reason, you are free to contact and inform us any moment." ); ?>
                <?php echo esc_html( "We will consider requests to remove links but we are not obligated to or so or to respond to you directly." ); ?>
            </p>
            <p>
                <?php echo esc_html( "We do not ensure that the information on this website is correct, we do not warrant its completeness or accuracy; nor do we promise to ensure that the website remains available or that the material on the website is kept up to date." ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_terms['disclaimer'] ) ) : ?>
        <div class="mt-12">
            <h2 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "Disclaimer" ); ?>
            </h2>
            <p>
                <?php echo esc_html( "To the maximum extent permitted by applicable law, we exclude all representations, warranties and conditions relating to our website and the use of this website." ); ?>
                <br>
                <?php echo esc_html( "Nothing in this disclaimer will:" ); ?>
            </p>
            <ul>
                <li>
                    <?php echo esc_html( "limit or exclude our or your liability for death or personal injury;" ); ?>
                </li>
                <li>
                    <?php echo esc_html( "limit or exclude our or your liability for fraud or fraudulent misrepresentation;" ); ?>
                </li>
                <li>
                    <?php echo esc_html( "limit any of our or your liabilities in any way that is not permitted under applicable law; or" ); ?>
                </li>
                <li>
                    <?php echo esc_html( "exclude any of our or your liabilities that may not be excluded under applicable law." ); ?>
                </li>
            </ul>
            <p>
                <?php echo esc_html( "The limitations and prohibitions of liability set in this Section and elsewhere in this disclaimer: (a) are subject to the preceding paragraph; and (b) govern all liabilities arising under the disclaimer, including liabilities arising in contract, in tort and for breach of statutory duty." ); ?>
            </p>
            <p>
                <?php echo esc_html( "As long as the website and the information and services on the website are provided free of charge, we will not be liable for any loss or damage of any nature." ); ?>
            </p>
        </div>
    <?php endif; ?>

</v-container>
