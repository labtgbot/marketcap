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


$public_meta = tonbankcard_public_route_meta();
$linked_data = tonbankcard_public_linked_data( $public_meta );
$public_image_url = empty( $public_meta['image'] ) ? '' : get_file_url_for_display( $public_meta['image'] );
$runtime = isset( $GLOBALS['runtime_config'] ) ? $GLOBALS['runtime_config'] : tonbankcard_runtime_config();
$yandex_metrica = isset( $runtime['analytics']['yandex_metrica'] ) && is_array( $runtime['analytics']['yandex_metrica'] )
    ? $runtime['analytics']['yandex_metrica']
    : [];
$yandex_metrica_counter_id = isset( $yandex_metrica['counter_id'] ) ? preg_replace( '/\D+/', '', (string) $yandex_metrica['counter_id'] ) : '';
$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '/';
$router_base_path = function_exists( 'router_base' ) ? (string) parse_url( router_base(), PHP_URL_PATH ) : '';
$router_base_path = '/' === $router_base_path ? '' : rtrim( $router_base_path, '/' );
$admin_base_path = $router_base_path . '/admin';
$is_admin_page = $request_path === $admin_base_path || 0 === strpos( $request_path, $admin_base_path . '/' );
$should_render_yandex_metrica = ! empty( $yandex_metrica['enabled'] ) && '' !== $yandex_metrica_counter_id && ! $is_admin_page;

?>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="color-scheme" content="light dark" />
    <meta name="robots" content="<?php echo esc_attr( $public_meta['robots'] ); ?>" />
    <link rel="canonical" href="<?php echo esc_url( $public_meta['canonical_url'] ); ?>" />
    <?php
        /*
         * Per-language hreflang alternates (one per supported UI language plus
         * x-default), derived from the translation registry so crawlers can
         * discover and serve every localized variant. See #167.
         */
        foreach ( tonbankcard_seo_hreflang_alternates( $public_meta['canonical_url'] ) as $hreflang_alternate ) :
        ?>
    <link rel="alternate" hreflang="<?php echo esc_attr( $hreflang_alternate['hreflang'] ); ?>" href="<?php echo esc_url( $hreflang_alternate['href'] ); ?>" />
    <?php endforeach; ?>
    <meta property="og:type" content="<?php echo esc_attr( $public_meta['og_type'] ); ?>" />
    <meta property="og:url" content="<?php echo esc_url( $public_meta['canonical_url'] ); ?>" />
    <meta property="og:locale" content="en_US" />

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
        <title><?php echo esc_html( $public_meta['full_title'] ); ?></title>
        <meta name="application-name" content="<?php echo esc_attr( $site['name'] ); ?>" />
        <meta name="twitter:title" content="<?php echo esc_attr( $public_meta['full_title'] ); ?>" />
        <meta property="og:title" content="<?php echo esc_attr( $public_meta['full_title'] ); ?>" />
        <?php

        /*
         * See "DESCRIPTION" in "config/site.php"
         */
        ?>
        <meta name="description" content="<?php echo esc_attr( $public_meta['description'] ); ?>" />
        <meta name="twitter:description" content="<?php echo esc_attr( $public_meta['description'] ); ?>" />
        <meta property="og:description" content="<?php echo esc_attr( $public_meta['description'] ); ?>" />
        <?php

        /*
         * See "THEME COLOR" in "config/site.php"
         */
        if ( ! empty( $site['theme_color'] ) ) {
            ?>
            <meta id="tbc-theme-color" name="theme-color" content="<?php echo esc_attr( $site['theme_color'] ); ?>" />
            <?php
        }

        if ( ! empty( $GLOBALS['v2']['manifest'] ) ) {
            ?>
            <link rel="manifest" href="<?php echo esc_url( get_file_url_for_display( $GLOBALS['v2']['manifest'] ) ); ?>" />
            <meta name="mobile-web-app-capable" content="yes" />
            <meta name="apple-mobile-web-app-capable" content="yes" />
            <meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( $site['name'] ); ?>" />
            <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
            <?php
        }

        if ( 'telegram' === TONBANKCARD_PROFILE ) {
            ?>
            <script src="https://telegram.org/js/telegram-web-app.js"></script>
            <?php
        }

        // Add Preconnect Tags
        if ( GECKO_CLIENT_PRECONNECT ) {
            // CoinGecko image asset origin
            ?>
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
            $favicon_extension = strtolower( pathinfo( parse_url( $site['favicon'], PHP_URL_PATH ), PATHINFO_EXTENSION ) );
            $favicon_type      = 'image/x-icon';
            if ( 'svg' === $favicon_extension ) {
                $favicon_type = 'image/svg+xml';
            } elseif ( 'png' === $favicon_extension ) {
                $favicon_type = 'image/png';
            }
            ?>
            <link rel="shortcut icon" type="<?php echo esc_attr( $favicon_type ); ?>" href="<?php echo esc_url( get_file_url_for_display( $site['favicon'] ) ); ?>" />
            <?php
        }

        /*
         * See "ICONS" in "config/site.php"
         */
        if ( ! empty( $site['icons'] ) ) {
            foreach ( $site['icons'] as $sizes => $href ) {
                if ( ! empty( $href ) ) {
                    $icon_extension = strtolower( pathinfo( parse_url( $href, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
                    $icon_type      = 'image/png';
                    if ( 'svg' === $icon_extension ) {
                        $icon_type = 'image/svg+xml';
                    } elseif ( 'ico' === $icon_extension ) {
                        $icon_type = 'image/x-icon';
                    }
                    ?>
                    <link rel="icon" type="<?php echo esc_attr( $icon_type ); ?>" sizes="<?php echo esc_attr( $sizes ); ?>" href="<?php echo esc_url( get_file_url_for_display( $href ) ); ?>" />
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
        if ( ! empty( $public_image_url ) ) {
            ?>
            <meta property="og:image" content="<?php echo esc_url( $public_image_url ); ?>" />
            <?php if ( 0 === strpos( $public_image_url, 'https://' ) ) : ?>
            <meta property="og:image:secure_url" content="<?php echo esc_url( $public_image_url ); ?>" />
            <?php endif; ?>
            <meta property="og:image:width" content="<?php echo esc_attr( $public_meta['image_width'] ); ?>" />
            <meta property="og:image:height" content="<?php echo esc_attr( $public_meta['image_height'] ); ?>" />
            <meta property="og:image:alt" content="<?php echo esc_attr( $public_meta['image_alt'] ); ?>" />
            <?php
        }

        /*
         * See "TWITTER CARD" in "config/site.php"
         */
        if ( ! empty( $site['twitter_card'] ) ) {
            ?>
            <meta name="twitter:card" content="<?php echo esc_attr( $site['twitter_card'] ); ?>" />
            <?php
        }

        /*
         * See "TWITTER SITE" in "config/site.php"
         */
        if ( ! empty( $site['twitter_site'] ) ) {
            ?>
            <meta name="twitter:site" content="<?php echo esc_attr( $site['twitter_site'] ); ?>" />
            <?php
        }

        /*
         * See "TWITTER CREATOR" in "config/site.php"
         */
        if ( ! empty( $site['twitter_creator'] ) ) {
            ?>
            <meta name="twitter:creator" content="<?php echo esc_attr( $site['twitter_creator'] ); ?>" />
            <?php
        }

        /*
         * See "TWITTER IMAGE" in "config/site.php"
         */
        if ( ! empty( $site['twitter_image'] ) || ! empty( $public_meta['image'] ) ) {
            $twitter_image = empty( $site['twitter_image'] ) ? $public_image_url : get_file_url_for_display( $site['twitter_image'] );
            ?>
            <meta name="twitter:image" content="<?php echo esc_url( $twitter_image ); ?>" />
            <meta name="twitter:image:alt" content="<?php echo esc_attr( $public_meta['image_alt'] ); ?>" />
            <?php
        }

        /*
         * Print Linked Data (JSON-LD)
         */
        ?>
        <script type="application/ld+json"><?php echo json_encode( $linked_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>
        <?php if ( $should_render_yandex_metrica ) : ?>
        <!-- Yandex.Metrika counter -->
        <script>
            window.tonbankcardYandexMetrica = {
                enabled: true,
                counterId: <?php echo esc_attr( $yandex_metrica_counter_id ); ?>,
                lastTrackedUrl: window.location.href
            };
            (function(m,e,t,r,i,k,a){
                m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
                m[i].l=1*new Date();
                for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
                k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
            })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js', 'ym');

            window.ym(<?php echo esc_attr( $yandex_metrica_counter_id ); ?>, 'init', {defer:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", accurateTrackBounce:true, trackLinks:true});
            window.ym(<?php echo esc_attr( $yandex_metrica_counter_id ); ?>, 'hit', window.location.href, {title: document.title, referer: document.referrer});
        </script>
        <noscript><div><img src="https://mc.yandex.ru/watch/<?php echo esc_attr( $yandex_metrica_counter_id ); ?>" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
        <!-- /Yandex.Metrika counter -->
        <?php endif; ?>
        <?php

    ?>
</head>
