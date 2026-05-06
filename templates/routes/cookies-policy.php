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
$frontend_options['cookies-policy']['title'] = __( 'Cookies Policy' );

$route_cookies_policy['name'] = $site['name'];
$route_cookies_policy['privacy_policy_url'] = empty( $routes['privacy-policy']['enabled'] ) ? '' : site_url( $routes['privacy-policy']['path'] );

?>
<v-container tag="section" id="cookies-policy" class="mt-8 mb-16 pa-4 pa-sm-6">
    <h1 class="text-h4 text-sm-h3 mb-8">
        <?php echo esc_html( $frontend_options['cookies-policy']['title'] ); ?>
    </h1>

    <p>
        <?php echo esc_html( sprintf( __( 'This policy explains how %s Crypto Tracker uses cookies, local storage, and similar browser storage for the public website and planned Telegram Mini App surfaces.' ), $route_cookies_policy['name'] ) ); ?>
        <?php echo esc_html( __( 'It should be read together with the privacy policy.' ) ); ?>
        <?php if ( ! empty( $route_cookies_policy['privacy_policy_url'] ) ) : ?>
            <a href="<?php echo esc_url( $route_cookies_policy['privacy_policy_url'] ); ?>"><?php echo esc_html( __( 'Privacy Policy' ) ); ?></a>.
        <?php endif; ?>
    </p>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( __( 'Storage categories' ) ); ?>
        </h2>
        <ul>
            <li>
                <strong><?php echo esc_html( __( 'Strictly necessary:' ) ); ?></strong>
                <?php echo esc_html( __( ' values needed to run the site, route the app, preserve consent state, protect sessions, and keep the interface stable.' ) ); ?>
            </li>
            <li>
                <strong><?php echo esc_html( __( 'Functional:' ) ); ?></strong>
                <?php echo esc_html( __( ' preferences such as theme, display currency, recently selected routes, local watchlist state, and alert draft state.' ) ); ?>
            </li>
            <li>
                <strong><?php echo esc_html( __( 'Analytics:' ) ); ?></strong>
                <?php echo esc_html( __( ' privacy-reviewed deployment data used to understand market pulse views, search, coin opens, watchlist actions, alerts, share events, and provider health.' ) ); ?>
            </li>
            <li>
                <strong><?php echo esc_html( __( 'Third-party:' ) ); ?></strong>
                <?php echo esc_html( __( ' storage that may be set by market data providers, Telegram, AI providers, exchange-widget providers, video embeds, or browser security services when those integrations are enabled.' ) ); ?>
            </li>
        </ul>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( __( 'Local storage' ) ); ?>
        </h2>
        <p>
            <?php echo esc_html( __( 'The app uses local storage to keep user-facing preferences on the same device. This can include theme, display currency, consent timestamp, local watchlist selections, and future alert or Mini App settings. Clearing browser data removes this state from the device.' ) ); ?>
        </p>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( __( 'Telegram and Mini App contexts' ) ); ?>
        </h2>
        <p>
            <?php echo esc_html( __( 'Telegram webviews may provide theme, language, viewport, and startapp context. Trusted Telegram session validation belongs on the server side, and bot-delivered alerts should include settings or unsubscribe paths when alert features are enabled.' ) ); ?>
        </p>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( __( 'Localization placeholders' ) ); ?>
        </h2>
        <p>
            <?php echo esc_html( __( 'English is the primary policy copy for the current public website. Russian policy copy is tracked as a placeholder for Telegram RU community links and later localized route content. Until Russian copy is reviewed and published, English controls where translations differ.' ) ); ?>
        </p>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( __( 'Managing storage' ) ); ?>
        </h2>
        <p>
            <?php echo esc_html( __( 'You can clear cookies and local storage through browser settings. Some strictly necessary storage is required for core website operation; disabling all storage can break settings, route state, local watchlists, alerts, or consent controls.' ) ); ?>
        </p>
    </div>
</v-container>
