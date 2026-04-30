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

/*
| -------------------------------------------------------------------------
| CRYPTOCURRENCIES
| -------------------------------------------------------------------------
|
| TYPE: bool
| DESCRIPTION: Set TRUE to show cryptocurrencies counter.
|
*/
$component_stats_bar['cryptocurrencies'] = TRUE;

/*
| -------------------------------------------------------------------------
| EXCHANGES
| -------------------------------------------------------------------------
|
| TYPE: bool
| DESCRIPTION: Set TRUE to show exchanges counter.
|
*/
$component_stats_bar['exchanges'] = TRUE;

/*
| -------------------------------------------------------------------------
| MARKET CAP
| -------------------------------------------------------------------------
|
| TYPE: bool
| DESCRIPTION: Set TRUE to show total market cap.
|
*/
$component_stats_bar['market_cap'] = TRUE;

/*
| -------------------------------------------------------------------------
| VOLUME 24h
| -------------------------------------------------------------------------
|
| TYPE: bool
| DESCRIPTION: Set TRUE to show 24h total trading volume.
|
*/
$component_stats_bar['volume_24h'] = TRUE;

/*
| -------------------------------------------------------------------------
| DOMINANCE
| -------------------------------------------------------------------------
|
| TYPE: bool
| DESCRIPTION: Set TRUE to show main currencies market share (dominance).
|
*/
$component_stats_bar['dominance'] = TRUE;

?>
<v-system-bar class="gc-stats-bar" color="primary darken-3" height="30" dark v-if="$root.global">
    <v-spacer></v-spacer>
    <?php
        /*
         * Total Cryptocurrencies
         */
        if ( ! empty( $component_stats_bar['cryptocurrencies'] ) ) {
            ?>
            <span v-if="$root.totalCryptocurrencies">
                <?php echo esc_html( __( 'Cryptos' ) ); ?>:
                <router-link <?php to_attr( 'currencies' ); ?>>
                    <span class="white--text" v-text="$root.totalCryptocurrencies"></span>
                </router-link>
            </span>
            <?php
        }

        /*
         * Total Exchanges
         */
        if ( ! empty( $component_stats_bar['exchanges'] ) ) {
            ?>
            <span v-if="$root.totalExchanges">
                <?php echo esc_html( __( 'Exchanges' ) ); ?>:
                <router-link <?php to_attr( 'exchanges' ); ?> v-text="$root.totalExchanges"></router-link>
            </span>
            <?php
        }

        /*
         * Total Market Cap
         */
        if ( ! empty( $component_stats_bar['market_cap'] ) ) {
            ?>
            <span v-if="$root.totalMarketCap">
                <?php echo esc_html( __( 'Market Cap' ) ); ?>:
                <router-link <?php to_attr( 'currencies' ); ?> v-text="$root.marketCapFormat($root.totalMarketCap)"></router-link>
            </span>
            <?php
        }

        /*
         * Trading Volume (24h)
         */
        if ( ! empty( $component_stats_bar['volume_24h'] ) ) {

            ?>
            <span v-if="$root.totalVolume24h">
                <?php echo esc_html( __( '24h Vol' ) ); ?>:
                <router-link <?php to_attr( 'currencies' ); ?> v-text="$root.volumeFormat($root.totalVolume24h)"></router-link>
            </span>
            <?php
        }

        /*
         * Dominance Percentage
         */
        if ( ! empty( $component_stats_bar['dominance'] ) ) {
            ?>
            <span v-if="dominanceEntries.length">
                <?php echo esc_html( __( 'Dominance' ) ); ?>:
                <router-link
                    class="text-decoration-none mx-1"
                    v-for="(entry,index) in dominanceEntries"
                    v-if="$root.marketCapPercentage(entry.symbol)"
                    :key="index"
                    :to="entry.route">
                    <span class="text-uppercase">{{ entry.symbol }}:</span>
                    <span v-text="$root.dominanceFormat($root.marketCapPercentage(entry.symbol))"></span>
                </router-link>
            </span>
            <?php
        }
    ?>
    <v-spacer></v-spacer>
</v-system-bar>
