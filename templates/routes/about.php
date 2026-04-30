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
| -------------------------------------------------------------------------
| TITLE
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Route title.
|
*/
$frontend_options['about']['title'] = sprintf( '%s %s', __( 'About' ), $site['name'] );

$route_about['pillars'] = [
    [
        'title' => 'Market pulse',
        'text'  => 'Track prices, market cap, volume, trending assets, and TON ecosystem context from a public website surface that remains useful without a wallet connection.',
    ],
    [
        'title' => 'Telegram-first workflows',
        'text'  => 'The V2 roadmap connects public market pages with Telegram Mini App journeys for watchlists, alerts, sharing, referrals, and support through the TONBANKCARD bot.',
    ],
    [
        'title' => 'Responsible intelligence',
        'text'  => 'AI summaries, alerts, and swap widgets are planned as decision-support tools with source freshness, risk framing, and no investment-advice claims.',
    ],
];

$route_about['localization'] = [
    [
        'language' => 'English',
        'status'   => 'Primary public copy for the website and Telegram Mini App.',
    ],
    [
        'language' => 'Russian',
        'status'   => 'Placeholder copy track for Telegram RU community links and later localized product pages.',
    ],
];

$route_about['official_links'] = [
    [
        'label' => 'Channel',
        'url'   => 'https://t.me/tonbankcard',
    ],
    [
        'label' => 'Channel RU',
        'url'   => 'https://t.me/tonbankcard_ru',
    ],
    [
        'label' => 'Chat',
        'url'   => 'https://t.me/tonbankcard_chat',
    ],
    [
        'label' => 'Chat RU',
        'url'   => 'https://t.me/tonbankcard_chat_ru',
    ],
    [
        'label' => 'X',
        'url'   => 'https://twitter.com/tonbankcard',
    ],
    [
        'label' => 'VK',
        'url'   => 'https://vk.com/tonbankcard',
    ],
    [
        'label' => 'YouTube',
        'url'   => 'https://www.youtube.com/@tonbankcard',
    ],
];

?>
<v-container tag="section" id="about" class="mt-8 mb-16 pa-4 pa-sm-6">

    <div id="about-intro">
        <h2 class="text-h5 text-sm-h4 mb-5 text-center">
            <?php echo esc_html( $frontend_options['about']['title'] ); ?>
        </h2>
        <p>
            <?php echo esc_html( 'TONBANKCARD Crypto Tracker is the public market-data and market pulse surface for TONBANKCARD V2. It keeps the useful market routes from the current tracker while moving the product toward TON ecosystem discovery, Telegram-native watchlists, alert delivery, shareable market context, and safe exchange-widget entry points.' ); ?>
        </p>
        <p>
            <?php echo esc_html( 'The product is built for casual market viewers, TON users, active traders, Telegram group admins, and TONBANKCARD operators. Market data and generated summaries are informational only; users should verify sources and make independent financial decisions.' ); ?>
        </p>
    </div>

    <v-row class="mt-10">
        <?php foreach ( $route_about['pillars'] as $pillar ) : ?>
            <v-col cols="12" md="4">
                <h3 class="text-h6 mb-2"><?php echo esc_html( $pillar['title'] ); ?></h3>
                <p><?php echo esc_html( $pillar['text'] ); ?></p>
            </v-col>
        <?php endforeach; ?>
    </v-row>

    <div id="about-trust" class="mt-10">
        <h3 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Trust and data boundaries' ); ?>
        </h3>
        <ul>
            <li><?php echo esc_html( 'Coin and exchange data may be delayed, incomplete, revised, or unavailable when upstream providers fail.' ); ?></li>
            <li><?php echo esc_html( 'AI summaries and alert explanations must stay factual, include risk context, and avoid buy, sell, hold, leverage, or position-sizing recommendations.' ); ?></li>
            <li><?php echo esc_html( 'Swap widgets and external exchange links are third-party services. TONBANKCARD does not custody funds or execute trades from this website.' ); ?></li>
            <li><?php echo esc_html( 'Telegram and future wallet-aware features must keep secrets server-side and expose only the minimum data needed for the selected workflow.' ); ?></li>
        </ul>
    </div>

    <div id="about-localization" class="mt-10">
        <h3 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Localization placeholders' ); ?>
        </h3>
        <v-row>
            <?php foreach ( $route_about['localization'] as $locale ) : ?>
                <v-col cols="12" sm="6">
                    <strong><?php echo esc_html( $locale['language'] ); ?></strong>
                    <p><?php echo esc_html( $locale['status'] ); ?></p>
                </v-col>
            <?php endforeach; ?>
        </v-row>
    </div>

    <div id="about-official-links" class="mt-10">
        <h3 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( 'Official channels' ); ?>
        </h3>
        <v-row>
            <?php foreach ( $route_about['official_links'] as $link ) : ?>
                <v-col cols="12" sm="6" md="4">
                    <a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html( $link['label'] ); ?>
                    </a>
                </v-col>
            <?php endforeach; ?>
        </v-row>
    </div>

</v-container>
