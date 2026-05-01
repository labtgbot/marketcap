<?php
/**
 * TONBANKCARD public TON ecosystem route.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$frontend_options['ton']['title'] = __( 'TON Ecosystem' );
$frontend_options['ton']['tonCoinIds'] = [ 'toncoin' ];

$route_ton_sections = [
    [
        'title' => 'Toncoin and core assets',
        'text'  => 'Follow Toncoin as the anchor asset for TONBANKCARD market context while V2 expands coverage for jettons, stablecoins, DeFi venues, and wallet-aware flows.',
    ],
    [
        'title' => 'Telegram-native discovery',
        'text'  => 'Public TON pages stay shareable on the web and can later open compact Mini App views for watchlists, alerts, referrals, and group context.',
    ],
    [
        'title' => 'Risk-aware market context',
        'text'  => 'TON ecosystem content must keep source freshness, liquidity, smart-contract, bridge, and third-party exchange risks visible before users act.',
    ],
];

?>
<v-container tag="section" id="ton" class="mt-8 mb-16 pa-4 pa-sm-6">
    <h1 class="text-h4 text-sm-h4 mb-4">
        <?php echo esc_html( $frontend_options['ton']['title'] ); ?>
    </h1>
    <p class="text-body-1">
        <?php echo esc_html( 'The TON ecosystem route is the public website entry point for TONBANKCARD V2 coverage of Toncoin, Telegram-native market discovery, and future curated TON asset lists.' ); ?>
    </p>

    <v-row class="mt-6 ai-insight-grid">
        <v-col cols="12">
            <gc-ai-insight-card
                title="<?php echo esc_attr( __( 'AI TON ecosystem pulse' ) ); ?>"
                icon="mdi-diamond-stone"
                :context="tonInsightContext"
                source-route="ton_ecosystem"
            ></gc-ai-insight-card>
        </v-col>
    </v-row>

    <v-row class="mt-6">
        <?php foreach ( $route_ton_sections as $section ) : ?>
            <v-col cols="12" md="4">
                <h2 class="text-h6 mb-2"><?php echo esc_html( $section['title'] ); ?></h2>
                <p><?php echo esc_html( $section['text'] ); ?></p>
            </v-col>
        <?php endforeach; ?>
    </v-row>

    <v-divider class="my-8"></v-divider>

    <h2 class="text-h6 text-sm-h5 mb-3">
        <?php echo esc_html( 'Current public routes' ); ?>
    </h2>
    <p>
        <?php echo esc_html( 'Use Market Pulse, the market table, and coin detail pages for live market data while TON-specific lists, tags, and filters are added in later V2 work.' ); ?>
    </p>
    <v-btn color="primary" depressed :to="{name:'currencies'}">
        <v-icon left>mdi-pulse</v-icon>
        <?php echo esc_html( 'Open Market Pulse' ); ?>
    </v-btn>
    <v-btn text <?php to_attr( 'markets' ); ?>>
        <v-icon left>mdi-chart-line</v-icon>
        <?php echo esc_html( 'Open markets' ); ?>
    </v-btn>
    <v-btn text :to="{name:'coins',params:{id:'toncoin'}}">
        <v-icon left>mdi-diamond-stone</v-icon>
        <?php echo esc_html( 'View Toncoin' ); ?>
    </v-btn>
</v-container>
