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


/*
 * LINKED DATA
 */
$linked_data = [
    '@context' => 'http://schema.org',
    '@type' => 'WebSite',
    'name' => $site['name'],
    'url' => site_url(),
];

?>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="canonical" href="<?php echo esc_url( site_url() ); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo esc_url( site_url() ); ?>" />

    <?php

        /*
         * See "NAME" in "config/site.php"
         */
        ?>
        <meta content="<?php echo esc_attr( $site['name'] ); ?>" property="og:site_name" />
        <?php

        /*
         * See "TITLE" in "config/site.php"
         */
        ?>
        <title><?php echo esc_html( $site['title'] ); ?></title>
        <meta content="<?php echo esc_attr( $site['title'] ); ?>" name="twitter:title" />
        <meta content="<?php echo esc_attr( $site['title'] ); ?>" property="og:title" />
        <?php

        /*
         * See "DESCRIPTION" in "config/site.php"
         */
        ?>
        <meta content="<?php echo esc_attr( $site['description'] ); ?>" name="description" />
        <meta content="<?php echo esc_attr( $site['description'] ); ?>" name="twitter:description" />
        <meta content="<?php echo esc_attr( $site['description'] ); ?>" property="og:description" />
        <?php

        /*
         * See "THEME COLOR" in "config/site.php"
         */
        if ( ! empty( $site['theme_color'] ) ) {
            ?>
            <meta content="<?php echo esc_attr( $site['theme_color'] ); ?>" name="theme-color" />
            <?php
        }

        // Add Preconnect Tags
        if ( GECKO_CLIENT_PRECONNECT ) {
            // CoinGecko Origins
            ?>
            <link rel="preconnect" href="https://api.coingecko.com" />
            <link rel="preconnect" href="https://assets.coingecko.com" />
            <?php
        }

        // Use jsDelivr CDN and Google Fonts to serve assets and decrease loading time
        if ( GECKO_CLIENT_CDN ) {
            // CDNs preconnect
            if ( GECKO_CLIENT_PRECONNECT ) {
                ?>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
                <link rel="preconnect" href="https://cdn.jsdelivr.net" />
                <?php
            }
            ?>
            <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&display=swap" rel="stylesheet" />
            <link href="<?php echo esc_url( sprintf( 'https://cdn.jsdelivr.net/npm/@mdi/font@%s/css/materialdesignicons.min.css', MDI_VERSION ) ); ?>" rel="stylesheet" />
            <link href="<?php echo esc_url( sprintf( 'https://cdn.jsdelivr.net/npm/vuetify@%s/dist/vuetify.min.css', VUETIFY_VERSION ) ); ?>" rel="stylesheet" />
            <?php
        }
        // local assets
        else {
            ?>
            <link href="<?php echo esc_url( vendor_url( 'roboto/roboto.min.css?v=' . ROBOTO_VERSION ) ); ?>" rel="stylesheet" />
            <link href="<?php echo esc_url( vendor_url( 'mdi/css/materialdesignicons.min.css?v=' . MDI_VERSION ) ); ?>" rel="stylesheet" />
            <link href="<?php echo esc_url( vendor_url( 'vuetify/vuetify.min.css?v=' . VUETIFY_VERSION ) ); ?>" rel="stylesheet" />
            <?php
        }

        /*
         * Custom stylesheet file
         */
        ?>
        <link href="<?php echo esc_url( get_file_url_for_display( 'assets/css/style.css' ) ); ?>" rel="stylesheet" />
        <?php

        /*
         * See "FAVICON" in "config/site.php"
         */
        if ( ! empty( $site['favicon'] ) ) {
            ?>
            <link rel="shortcut icon" type="image/x-icon" href="<?php echo esc_url( get_file_url_for_display( $site['favicon'] ) ); ?>" />
            <?php
        }

        /*
         * See "ICONS" in "config/site.php"
         */
        if ( ! empty( $site['icons'] ) ) {
            foreach ( $site['icons'] as $sizes => $href ) {
                if ( ! empty( $href ) ) {
                    ?>
                    <link rel="icon" type="image/png" sizes="<?php echo esc_attr( $sizes ); ?>" href="<?php echo esc_url( get_file_url_for_display( $href ) ); ?>" />
                    <?php
                }
            }
        }

        /*
         * See "APPLE TOUCH ICONS" in "config/site.php"
         */
        if ( ! empty( $site['apple_touch_icons'] ) ) {
            foreach ( $site['apple_touch_icons'] as $sizes => $href ) {
                if ( ! empty( $href ) ) {
                    ?>
                    <link rel="apple-touch-icon" sizes="<?php echo esc_attr( $sizes ); ?>" href="<?php echo esc_url( get_file_url_for_display( $href ) ); ?>" />
                    <?php
                }
            }
        }

        /*
         * See "OPEN GRAPH IMAGE" in "config/site.php"
         */
        if ( ! empty( $site['og_image'] ) ) {
            ?>
            <meta content="<?php echo esc_url( get_file_url_for_display( $site['og_image'] ) ); ?>" property="og:image" />
            <?php
        }

        /*
         * See "TWITTER CARD" in "config/site.php"
         */
        if ( ! empty( $site['twitter_card'] ) ) {
            ?>
            <meta content="<?php echo esc_attr( $site['twitter_card'] ); ?>" name="twitter:card" />
            <?php
        }

        /*
         * See "TWITTER SITE" in "config/site.php"
         */
        if ( ! empty( $site['twitter_site'] ) ) {
            ?>
            <meta content="<?php echo esc_attr( $site['twitter_site'] ); ?>" name="twitter:site" />
            <?php
        }

        /*
         * See "TWITTER CREATOR" in "config/site.php"
         */
        if ( ! empty( $site['twitter_creator'] ) ) {
            ?>
            <meta content="<?php echo esc_attr( $site['twitter_creator'] ); ?>" name="twitter:creator" />
            <?php
        }

        /*
         * See "TWITTER IMAGE" in "config/site.php"
         */
        if ( ! empty( $site['twitter_image'] ) ) {
            ?>
            <meta content="<?php echo esc_url( get_file_url_for_display( $site['twitter_image'] ) ); ?>" name="twitter:image" />
            <?php
        }

        /*
         * Print Linked Data (JSON-LD)
         */
        ?>
        <script type="application/ld+json"><?php echo json_encode( $linked_data ); ?></script>
        <?php

    ?>
</head>
