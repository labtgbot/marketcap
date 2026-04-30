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

$route_terms['name'] = $site['name'];
$route_terms['site_url'] = site_url();
$route_terms['privacy_policy_url'] = empty( $routes['privacy-policy']['enabled'] ) ? '' : site_url( $routes['privacy-policy']['path'] );
$route_terms['cookies_policy_url'] = empty( $routes['cookies-policy']['enabled'] ) ? '' : site_url( $routes['cookies-policy']['path'] );

?>
<v-container tag="section" id="terms" class="mt-8 mb-16 pa-4 pa-sm-6">
    <h1 class="text-h4 text-sm-h3 mb-6">
        <?php echo esc_html( $frontend_options['terms']['title'] ); ?>
    </h1>

    <p>
        <?php echo esc_html( "These terms apply to the use of {$route_terms['name']} Crypto Tracker at {$route_terms['site_url']} and related public website or Telegram Mini App surfaces operated for TONBANKCARD V2." ); ?>
        <?php echo esc_html( 'By using the service, you agree to use it only for lawful, informational, and personal market-research purposes.' ); ?>
    </p>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Informational service only' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'TONBANKCARD Crypto Tracker is not a broker, dealer, exchange, investment adviser, custodian, wallet provider, tax adviser, or legal adviser. The website does not execute trades, hold assets, manage orders, custody funds, or guarantee access to any third-party service.' ); ?>
        </p>
        <p>
            <?php echo esc_html( 'Nothing shown on the website, in Telegram, in generated summaries, in alerts, or in widget prompts is financial, investment, legal, tax, accounting, trading, lending, staking, borrowing, or custody advice.' ); ?>
        </p>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Market data disclosure' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'Provider market data may come from CoinGecko or other providers. Prices, volume, market capitalization, rankings, liquidity, exchange metadata, charts, and derived calculations may be delayed, cached, incomplete, unavailable, or wrong. You are responsible for checking source data before acting on it.' ); ?>
        </p>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'AI summaries and generated content' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'AI summaries, market explanations, sentiment labels, and alert explanations may be generated from provider data, cached data, user settings, or operator-curated context. Generated content can omit relevant facts, misread source data, or become stale. It must not be treated as a recommendation to buy, sell, hold, leverage, borrow, lend, stake, or swap any asset.' ); ?>
        </p>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Alerts' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'Alerts are convenience notifications. They may be delayed, duplicated, missed, paused, or delivered after market conditions have changed. You should not rely on alerts as the only trigger for financial decisions, risk controls, liquidations, or compliance obligations.' ); ?>
        </p>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Swap widgets and third-party services' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'Swap widgets, ChangeNOW links, exchange links, blockchain explorers, Telegram links, and other third-party services are operated by their respective providers. Their terms, fees, execution, availability, asset support, KYC rules, custody model, and transaction risks are not controlled by TONBANKCARD.' ); ?>
        </p>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'User responsibilities' ); ?>
        </h2>
        <ul>
            <li><?php echo esc_html( 'Verify every asset, address, network, provider, fee, and exchange route before sending funds or sharing personal information.' ); ?></li>
            <li><?php echo esc_html( 'Use the service only where crypto market tracking and related third-party services are lawful for you.' ); ?></li>
            <li><?php echo esc_html( 'Do not abuse search, alert, referral, or sharing workflows, and do not attempt to bypass rate limits or feature flags.' ); ?></li>
            <li><?php echo esc_html( 'Keep Telegram accounts, wallets, API keys, and devices secure. TONBANKCARD will never ask for private keys or seed phrases.' ); ?></li>
        </ul>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Privacy, cookies, and localization' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'The privacy and cookies pages describe how the product handles browser preferences, local storage, Telegram context, analytics, AI prompts, alert settings, and future wallet-aware data. English is the primary copy track; Russian copy is tracked as a localization placeholder for Telegram RU community surfaces.' ); ?>
        </p>
        <?php if ( ! empty( $route_terms['privacy_policy_url'] ) || ! empty( $route_terms['cookies_policy_url'] ) ) : ?>
            <p>
                <?php if ( ! empty( $route_terms['privacy_policy_url'] ) ) : ?>
                    <a href="<?php echo esc_url( $route_terms['privacy_policy_url'] ); ?>"><?php echo esc_html( 'Privacy Policy' ); ?></a>
                <?php endif; ?>
                <?php if ( ! empty( $route_terms['privacy_policy_url'] ) && ! empty( $route_terms['cookies_policy_url'] ) ) : ?>
                    <?php echo esc_html( ' and ' ); ?>
                <?php endif; ?>
                <?php if ( ! empty( $route_terms['cookies_policy_url'] ) ) : ?>
                    <a href="<?php echo esc_url( $route_terms['cookies_policy_url'] ); ?>"><?php echo esc_html( 'Cookies Policy' ); ?></a>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="mt-12">
        <h2 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Changes' ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'TONBANKCARD may update these terms as V2 features, providers, routes, widgets, localization, and Telegram workflows change. Continued use after updates means you accept the revised terms.' ); ?>
        </p>
    </div>
</v-container>
