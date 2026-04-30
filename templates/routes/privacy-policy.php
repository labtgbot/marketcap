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
$frontend_options['privacy-policy']['title'] = __( 'Privacy Policy' );

$route_privacy_policy['name'] = $site['name'];
$route_privacy_policy['host'] = parse_url( site_url(), PHP_URL_HOST );
$route_privacy_policy['cookies_policy_url'] = empty( $routes['cookies-policy']['enabled'] ) ? '' : site_url( $routes['cookies-policy']['path'] );
$route_privacy_policy['official_privacy_policy_url'] = 'https://tonbankcard.com/doc/Privacy_Policy_TONBANKCARD_en.pdf';
$route_privacy_policy['official_privacy_policy_ru_url'] = 'https://tonbankcard.com/doc/Privacy_Policy_TONBANKCARD_ru.pdf';

?>
<v-container tag="section" id="privacy-policy" class="mt-8 mb-16 pa-4 pa-sm-6">
    <h1 class="text-h4 text-sm-h3 mb-8">
        <?php echo esc_html( $frontend_options['privacy-policy']['title'] ); ?>
    </h1>

    <p>
        <?php echo esc_html( "This Privacy Policy explains how {$route_privacy_policy['name']} Crypto Tracker handles information for the public website at {$route_privacy_policy['host']} and planned Telegram Mini App features." ); ?>
        <?php echo esc_html( 'The current product baseline is a market-data website with browser-side preferences. V2 feature flags will add Telegram sessions, watchlists, alerts, AI summaries, exchange-widget configuration, and future wallet-aware surfaces only when those workflows are enabled.' ); ?>
    </p>
    <p>
        <?php echo esc_html( 'Official TONBANKCARD privacy documents are available in ' ); ?>
        <a href="<?php echo esc_url( $route_privacy_policy['official_privacy_policy_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( 'English' ); ?></a>
        <?php echo esc_html( ' and ' ); ?>
        <a href="<?php echo esc_url( $route_privacy_policy['official_privacy_policy_ru_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( 'Russian' ); ?></a>
        <?php echo esc_html( '. This page summarizes the website and planned marketcap application data boundaries for reviewers.' ); ?>
    </p>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Information we may process' ); ?>
        </h2>
        <ul>
            <li><?php echo esc_html( 'Website usage data such as requested pages, device/browser metadata, approximate network information, timestamps, and error logs needed to run and secure the service.' ); ?></li>
            <li><?php echo esc_html( 'Local preferences such as theme, display currency, consent state, recently viewed routes, local watchlist selections, and alert drafts stored in browser storage when available.' ); ?></li>
            <li><?php echo esc_html( 'Telegram data such as user or chat identifiers, startapp payloads, bot command context, language hints, and Mini App session validation data when a Telegram workflow is used.' ); ?></li>
            <li><?php echo esc_html( 'Alert settings such as asset identifiers, thresholds, delivery preferences, quiet hours, status, and event history when alerts are enabled.' ); ?></li>
            <li><?php echo esc_html( 'AI prompt context, source snippets, generated summaries, feedback labels, cache metadata, and safety-review signals when AI features are enabled.' ); ?></li>
            <li><?php echo esc_html( 'Wallet-related public addresses or network identifiers only when a future wallet-aware feature is explicitly enabled by the user. Private keys and seed phrases must never be requested or stored.' ); ?></li>
        </ul>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'How we use information' ); ?>
        </h2>
        <ul>
            <li><?php echo esc_html( 'Operate market pages, search, charts, exchange pages, settings, and legal pages.' ); ?></li>
            <li><?php echo esc_html( 'Remember user preferences and restore local watchlist or alert draft state.' ); ?></li>
            <li><?php echo esc_html( 'Validate trusted Telegram sessions and route users back into the right Mini App, bot, alert, or referral flow.' ); ?></li>
            <li><?php echo esc_html( 'Deliver alerts, support unsubscribe/settings controls, and diagnose delivery failures.' ); ?></li>
            <li><?php echo esc_html( 'Generate or cache AI summaries only with the minimum market context needed for the selected feature.' ); ?></li>
            <li><?php echo esc_html( 'Run privacy-aware analytics for activation, search, coin opens, watchlist actions, alert actions, share events, and provider health.' ); ?></li>
            <li><?php echo esc_html( 'Prevent abuse, protect service availability, investigate errors, and comply with applicable obligations.' ); ?></li>
        </ul>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Providers and third parties' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'The service may use market data providers such as CoinGecko, Telegram platform APIs, AI providers such as Groq when configured, hosting/logging providers, analytics systems, and exchange-widget providers such as ChangeNOW. Provider requests are limited to the feature being used, but each provider may process data under its own terms and policies.' ); ?>
        </p>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Cookies and local storage' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'The browser may store necessary preferences in cookies or local storage. These values help remember consent, theme, display currency, and local feature state. Analytics cookies are optional deployment features and should not be enabled without the corresponding privacy review.' ); ?>
        </p>
        <?php if ( ! empty( $route_privacy_policy['cookies_policy_url'] ) ) : ?>
            <p>
                <?php echo esc_html( 'Read the ' ); ?>
                <a href="<?php echo esc_url( $route_privacy_policy['cookies_policy_url'] ); ?>"><?php echo esc_html( 'Cookies Policy' ); ?></a>
                <?php echo esc_html( ' for storage categories and localization placeholders.' ); ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Retention and controls' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'Local browser data can be cleared through browser settings. Telegram users should be able to reach alert settings, unsubscribe controls, and support paths from bot messages or Mini App routes as those features ship. Operational logs and cached provider data should be retained only as long as needed for security, debugging, compliance, and product-quality analysis.' ); ?>
        </p>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Security boundaries' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'Secrets such as bot tokens, provider API keys, database credentials, and premium entitlement checks belong on the server side. Users should never enter private keys, seed phrases, or exchange credentials into TONBANKCARD Crypto Tracker.' ); ?>
        </p>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Children' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'The service is not directed to children. If you believe a child provided personal information through the website, Telegram, alerts, or support workflows, contact support so the data can be reviewed and removed where appropriate.' ); ?>
        </p>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Policy updates' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'This policy will be updated as V2 features, analytics events, Telegram flows, AI provider settings, wallet-aware features, and localization coverage are implemented.' ); ?>
        </p>
    </div>
</v-container>
