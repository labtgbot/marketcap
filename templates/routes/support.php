<?php
/**
 * TONBANKCARD public support route.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$frontend_options['support']['title'] = __( 'Support' );

$route_support_links = [
    [
        'label' => __( 'TONBANKCARD chat' ),
        'text'  => __( 'Ask product and market tracker questions in the public Telegram chat.' ),
        'url'   => 'https://t.me/tonbankcard_chat',
    ],
    [
        'label' => __( 'TONBANKCARD chat RU' ),
        'text'  => __( 'Use the Russian-language community chat for localized support.' ),
        'url'   => 'https://t.me/tonbankcard_chat_ru',
    ],
    [
        'label' => __( 'GitHub issues' ),
        'text'  => __( 'Report reproducible website problems with route, browser, and screenshot details.' ),
        'url'   => 'https://github.com/labtgbot/marketcap/issues',
    ],
];

?>
<v-container tag="section" id="support" class="mt-8 mb-16 pa-4 pa-sm-6">
    <h1 class="text-h4 text-sm-h4 mb-4">
        <?php echo esc_html( $frontend_options['support']['title'] ); ?>
    </h1>
    <p class="text-body-1">
        <?php echo esc_html( __( 'Use this route for TONBANKCARD Crypto Tracker support, official contact channels, bug reports, and safety boundaries for market data, alerts, AI summaries, and exchange widgets.' ) ); ?>
    </p>

    <v-row class="mt-6">
        <?php foreach ( $route_support_links as $link ) : ?>
            <v-col cols="12" md="4">
                <h2 class="text-h6 mb-2"><?php echo esc_html( $link['label'] ); ?></h2>
                <p><?php echo esc_html( $link['text'] ); ?></p>
                <v-btn text href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener">
                    <v-icon left>mdi-open-in-new</v-icon>
                    <?php echo esc_html( __( 'Open' ) ); ?>
                </v-btn>
            </v-col>
        <?php endforeach; ?>
    </v-row>

    <v-divider class="my-8"></v-divider>

    <h2 class="text-h6 text-sm-h5 mb-3">
        <?php echo esc_html( __( 'Before reporting an issue' ) ); ?>
    </h2>
    <ul>
        <li><?php echo esc_html( __( 'Include the exact public URL, browser, device width, and whether Telegram was involved.' ) ); ?></li>
        <li><?php echo esc_html( __( 'For market data questions, include the coin or exchange id and the timestamp of the observed value.' ) ); ?></li>
        <li><?php echo esc_html( __( 'Never send wallet seed phrases, private keys, bot tokens, API keys, or exchange account credentials.' ) ); ?></li>
    </ul>
</v-container>
