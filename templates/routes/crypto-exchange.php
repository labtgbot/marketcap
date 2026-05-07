<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD CRYPTO EXCHANGE
 * -------------------------------------------------------------------------
 * First-party exchange route backed by the configured ChangeNOW partner
 * widget.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$runtime = isset( $GLOBALS['runtime_config'] ) && is_array( $GLOBALS['runtime_config'] ) ? $GLOBALS['runtime_config'] : tonbankcard_runtime_config();
$changenow_link_id = '';
if ( ! empty( $runtime['providers']['changenow']['link_id'] ) ) {
    $changenow_link_id = trim( (string) $runtime['providers']['changenow']['link_id'] );
}
$frontend_options['crypto-exchange'] = [
    'title'            => __( 'Crypto Exchange' ),
    'widgetBaseUrl'    => 'https://changenow.io/embeds/exchange-widget/v2/widget.html',
    'stepperScriptUrl' => 'https://changenow.io/embeds/exchange-widget/v2/stepper-connector.js',
    'linkId'           => $changenow_link_id,
    'defaultFrom'      => 'ton',
    'defaultTo'        => 'usdtton',
    'defaults'         => [
        'FAQ'             => 'true',
        'amount'          => '1',
        'amountFiat'      => '',
        'backgroundColor' => 'f6fafd',
        'horizontal'      => 'false',
        'isFiat'          => 'false',
        'lang'            => 'en-EN',
        'locales'         => 'true',
        'logo'            => 'false',
        'primaryColor'    => '1bb2da',
        'toTheMoon'       => 'false',
    ],
    'assetLabels'      => [
        'ton'     => 'TON',
        'usdtton' => 'USDT on TON',
        'btc'     => 'BTC',
        'eth'     => 'ETH',
        'usdt'    => 'USDT',
    ],
    'presets'          => [
        [
            'label' => 'TON to USDT on TON',
            'from'  => 'ton',
            'to'    => 'usdtton',
            'asset' => 'toncoin',
        ],
        [
            'label' => 'BTC to USDT on TON',
            'from'  => 'btc',
            'to'    => 'usdtton',
            'asset' => 'bitcoin',
        ],
        [
            'label' => 'ETH to USDT on TON',
            'from'  => 'eth',
            'to'    => 'usdtton',
            'asset' => 'ethereum',
        ],
        [
            'label' => 'USDT on TON to TON',
            'from'  => 'usdtton',
            'to'    => 'ton',
            'asset' => 'tether-usd-ton',
        ],
    ],
];

?>
<section class="mt-8 mb-16">
    <v-container id="crypto-exchange" class="gc-crypto-exchange" fluid>
        <v-row align="start" class="mb-4">
            <v-col cols="12" lg="7">
                <div class="overline primary--text font-weight-bold">TONBANKCARD</div>
                <h1 class="text-h4 text-sm-h3 mb-3">
                    <?php echo esc_html( __( 'Crypto Exchange' ) ); ?>
                </h1>
                <p class="text-body-1 text--secondary gc-crypto-exchange-lede">
                    <?php echo esc_html( __( 'TONBANKCARD partner exchange route for TON and supported crypto assets.' ) ); ?>
                </p>
                <v-chip-group column class="gc-crypto-exchange-status">
                    <v-chip label outlined :ripple="false">
                        <v-icon left>mdi-shield-check-outline</v-icon>
                        <?php echo esc_html( __( 'Partner widget' ) ); ?>
                    </v-chip>
                    <v-chip label outlined :ripple="false">
                        <v-icon left>mdi-swap-horizontal</v-icon>
                        <span v-text="pairLabel"></span>
                    </v-chip>
                </v-chip-group>
            </v-col>
            <v-col cols="12" lg="5">
                <v-alert dense text type="info" class="mb-0">
                    <?php echo esc_html( __( 'Rates, fees, limits, KYC rules, and transaction execution are provided by ChangeNOW. Verify the asset and network before sending funds.' ) ); ?>
                </v-alert>
            </v-col>
        </v-row>

        <v-row class="gc-crypto-exchange-controls" align="end">
            <v-col cols="12" sm="5" md="4">
                <v-text-field
                    v-model="fromInput"
                    label="<?php echo esc_attr( __( 'From' ) ); ?>"
                    prepend-inner-icon="mdi-arrow-up-circle-outline"
                    dense
                    outlined
                    hide-details
                    spellcheck="false"
                    autocapitalize="characters"
                    @change="syncRoute"
                    @keyup.enter="syncRoute"
                ></v-text-field>
            </v-col>
            <v-col cols="12" sm="2" md="1" class="gc-crypto-exchange-swap-col">
                <v-btn icon color="primary" class="gc-crypto-exchange-swap" aria-label="<?php echo esc_attr( __( 'Swap pair' ) ); ?>" @click="swapPair">
                    <v-icon>mdi-swap-horizontal</v-icon>
                </v-btn>
            </v-col>
            <v-col cols="12" sm="5" md="4">
                <v-text-field
                    v-model="toInput"
                    label="<?php echo esc_attr( __( 'To' ) ); ?>"
                    prepend-inner-icon="mdi-arrow-down-circle-outline"
                    dense
                    outlined
                    hide-details
                    spellcheck="false"
                    autocapitalize="characters"
                    @change="syncRoute"
                    @keyup.enter="syncRoute"
                ></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
                <v-btn block color="primary" depressed @click="syncRoute">
                    <v-icon left>mdi-refresh</v-icon>
                    <?php echo esc_html( __( 'Update pair' ) ); ?>
                </v-btn>
            </v-col>
        </v-row>

        <v-chip-group column class="gc-crypto-exchange-presets mt-3 mb-6">
            <v-chip
                v-for="preset in presets"
                :key="preset.from + '-' + preset.to"
                label
                outlined
                @click="applyPreset(preset)"
            >
                {{ preset.label }}
            </v-chip>
        </v-chip-group>

        <v-row>
            <v-col cols="12" lg="8">
                <v-card outlined class="gc-crypto-exchange-widget-card">
                    <iframe
                        id="iframe-widget"
                        class="gc-crypto-exchange-widget"
                        :src="widgetSrc"
                        title="<?php echo esc_attr( __( 'TONBANKCARD crypto exchange widget' ) ); ?>"
                        allow="clipboard-write"
                        referrerpolicy="strict-origin-when-cross-origin"
                    ></iframe>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-list two-line class="gc-crypto-exchange-summary">
                    <v-list-item>
                        <v-list-item-icon>
                            <v-icon color="primary">mdi-magnify</v-icon>
                        </v-list-item-icon>
                        <v-list-item-content>
                            <v-list-item-title><?php echo esc_html( __( 'Search-linked pair' ) ); ?></v-list-item-title>
                            <v-list-item-subtitle v-text="pairLabel"></v-list-item-subtitle>
                        </v-list-item-content>
                    </v-list-item>
                    <v-divider></v-divider>
                    <v-list-item>
                        <v-list-item-icon>
                            <v-icon color="primary">mdi-link-variant</v-icon>
                        </v-list-item-icon>
                        <v-list-item-content>
                            <v-list-item-title><?php echo esc_html( __( 'Shareable route' ) ); ?></v-list-item-title>
                            <v-list-item-subtitle v-text="$route.fullPath"></v-list-item-subtitle>
                        </v-list-item-content>
                    </v-list-item>
                    <v-divider></v-divider>
                    <v-list-item>
                        <v-list-item-icon>
                            <v-icon color="primary">mdi-open-in-new</v-icon>
                        </v-list-item-icon>
                        <v-list-item-content>
                            <v-list-item-title><?php echo esc_html( __( 'Provider' ) ); ?></v-list-item-title>
                            <v-list-item-subtitle>ChangeNOW</v-list-item-subtitle>
                        </v-list-item-content>
                    </v-list-item>
                </v-list>
            </v-col>
        </v-row>
    </v-container>
</section>
