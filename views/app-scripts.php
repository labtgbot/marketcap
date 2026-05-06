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
 * @var array $formats
 * @var array $translation
 * @var array $coingecko
 * @var array $enabled_routes
 * @var array $components
 * @var array $links
 * @var array $frontend_options
 */

/*
| -------------------------------------------------------------------------
| TEMPLATES
| -------------------------------------------------------------------------
*/
/*
 * COMPONENTS
 *
 * Include templates for components
 * See "COMPONENTS" in "index.php"
 */
foreach ( $components as $component ) {
    ?>
    <script id="component-<?php echo $component ?>" type="text/x-template">
        <?php include GECKO_CLIENT_TEMPLATES_DIR . '/components/' . $component . '.php'; ?>
    </script>
    <?php
}
/*
 * ROUTES
 *
 * Include templates for enabled routes
 * See "ROUTES" in "config/routes.php"
 */
foreach ( $enabled_routes as $name => $route ) {
    $template = empty( $route['template'] ) ? $name : $route['template'];
    ?>
    <script id="route-<?php echo $name ?>" type="text/x-template">
        <?php include GECKO_CLIENT_TEMPLATES_DIR . "/routes/" . $template . '.php'; ?>
    </script>
    <?php
}

/*
| -------------------------------------------------------------------------
| FRONTEND DATA
| -------------------------------------------------------------------------
*/
$echarts_asset_url = GECKO_CLIENT_CDN
    ? sprintf( 'https://cdn.jsdelivr.net/npm/echarts@%s/dist/echarts.min.js', ECHARTS_VERSION )
    : vendor_url( 'echarts/echarts.min.js?v=' . ECHARTS_VERSION );

if ( 'development' === GECKO_CLIENT_ENV ) {
    $echarts_asset_url = vendor_url( 'echarts/echarts.js?v=' . ECHARTS_VERSION );
}

$gecko_client = [
    'version'               => GECKO_CLIENT_VERSION,
    'vuetifyOptions'        => vuetify_constructor_options(),
    'translation'           => $translation,
    'lang'                  => function_exists( 'tonbankcard_active_language' ) ? tonbankcard_active_language() : 'en',
    'defaultVsCurrencyId'   => $coingecko['default_vs_currency'],
    'supportedVsCurrencies' => get_enabled_supported_vs_currencies(),
    'routerMode'            => 'history',
    'routerBase'            => router_base(),
    'routesConfig'          => $enabled_routes,
    'options'               => $frontend_options,
    'formats'               => $formats,
    'assets'                => [
        'echartsUrl' => $echarts_asset_url,
    ],
];
// Runtime metadata. Secret values from config/runtime.php are intentionally not
// included in this browser payload.
$runtime = isset( $GLOBALS['runtime_config'] ) ? $GLOBALS['runtime_config'] : tonbankcard_runtime_config();
$gecko_client['runtime'] = [
    'profile' => $runtime['profile'],
    'debug'   => (bool) $runtime['debug'],
    'urls'    => [
        'publicWeb'       => $runtime['urls']['public'],
        'telegramMiniApp' => $runtime['urls']['telegram'],
    ],
    'telegram' => [
        'botUsername' => $runtime['telegram']['bot_username'],
    ],
    'observability' => [
        'clientErrorReporting' => ! empty( $runtime['observability']['client_error_reporting'] ),
        'clientErrorEndpoint'  => site_url( 'api/observability/client-error' ),
        'verboseTracing'       => ! empty( $runtime['observability']['verbose_tracing'] ),
    ],
    'features' => $runtime['feature_flags'],
];
$gecko_client['pwa'] = [
    'serviceWorkerUrl' => site_url( 'service-worker.js' ),
    'offlineUrl'       => site_url( 'offline.html' ),
];
// Website Data
$gecko_client['website'] = [
    'name'           => $site['name'],
    'title'          => $site['title'],
    'titleSeparator' => ' - ',
    'description'    => $site['description'],
];
// CoinGecko
$gecko_client['cg'] = [
    'cache'          => (bool) $coingecko['cache'],
    'timeout'        => (int) $coingecko['timeout'],
    'gatewayBaseUrl' => site_url( 'api/market/' ),
];
// Smart Search
$gecko_client['search'] = [
    'apiBaseUrl'   => site_url( 'api/search' ),
    'defaultLimit' => 12,
    'maxLimit'     => 30,
];
// Watchlist persistence and trusted-session sync endpoint.
$gecko_client['watchlistConfig'] = [
    'apiBaseUrl' => site_url( 'api/watchlist' ),
];
// Smart alerts use local persistence first and upgrade to trusted Telegram
// sessions when Mini App initData is available.
$gecko_client['alertsConfig'] = [
    'apiBaseUrl'      => site_url( 'api/alerts' ),
    'draftStorageKey' => 'TONBANKCARD:alertDraft',
];
// Share cards generate Telegram startapp payloads and can resolve them from
// web links without exposing referral persistence details to the browser.
$gecko_client['shareConfig'] = [
    'apiBaseUrl' => site_url( 'api/share/resolve' ),
];
// Gamification achievement definitions are safe to expose; opt-in state stays
// browser-local until a trusted server sync is added.
$achievement_settings = tonbankcard_api_achievements_settings( $runtime, $api );
$gecko_client['achievementsConfig'] = [
    'apiBaseUrl'  => site_url( 'api/achievements/settings' ),
    'settings'    => $achievement_settings,
    'definitions' => tonbankcard_api_achievements_definitions( $achievement_settings ),
];
// Premium pricing and public entitlement limits are safe to expose; bot tokens
// and payment references remain server-side.
$premium_settings = function_exists( 'tonbankcard_api_premium_settings' )
    ? tonbankcard_api_premium_settings( $runtime, $api )
    : [];
$gecko_client['premiumConfig'] = [
    'apiBaseUrl' => site_url( 'api/premium' ),
    'settings'   => ! empty( $premium_settings ) ? tonbankcard_api_premium_public_settings( $premium_settings ) : [],
    'plans'      => ! empty( $premium_settings ) ? tonbankcard_api_premium_public_plans( $premium_settings ) : [],
];
// TON Connect wallet profile. The SDK is loaded lazily on user action; private
// keys and wallet secrets never enter this browser payload.
$gecko_client['tonConnect'] = [
    'enabled'     => ! empty( $runtime['feature_flags']['ton_connect'] ),
    'manifestUrl' => site_url( 'tonconnect-manifest.json' ),
    'sdkUrl'      => 'https://unpkg.com/@tonconnect/ui@2.4.4/dist/tonconnect-ui.min.js',
    'storageKey'  => 'TONBANKCARD:ton-connect-wallet:v1',
];
// AI insight and feedback endpoints. Provider secrets and raw prompts stay server-side.
$gecko_client['aiConfig'] = [
    'apiBaseUrl'    => site_url( 'api/ai' ),
    'feedbackTypes' => [ 'helpful', 'stale', 'wrong', 'unsafe' ],
    'timeoutMs'     => 12000,
    'retry'         => [
        'maxAttempts'      => 3,
        'baseDelayMs'      => 600,
        'maxDelayMs'       => 4000,
        'retryableReasons' => [
            'provider_unavailable',
            'provider_timeout',
            'provider_rate_limited',
            'provider_invalid_json',
            'schema_validation_failed',
            'client_unavailable',
        ],
    ],
];
// Custom Links
$gecko_client['links'] = [
    'currencies'         => (object) $links['currencies'],
    'exchanges'          => (object) $links['exchanges'],
    'financePlatforms'   => (object) $links['finance_platforms'],
    'derivativesMarkets' => (object) $links['derivatives_markets'],
];
// Passing app data as "window.GeckoClient" object
?>
<script>
//<![CDATA[
window.GeckoClient = <?php echo json_encode( $gecko_client ); ?>;
//]]>
</script>

<?php
/*
| -------------------------------------------------------------------------
| JAVASCRIPT FILES
| -------------------------------------------------------------------------
*/
if ( 'development' === GECKO_CLIENT_ENV ) {
    // DEVELOPMENT
    // using "dev/js/tools/bundle.php" to serve "dev/js/src" all together
    ?>
    <script src="<?php echo esc_url( vendor_url( 'lodash/lodash.js?v=' . LODASH_VERSION ) ); ?>"></script>
    <script src="<?php echo esc_url( vendor_url( 'axios/axios.js?v=' . AXIOS_VERSION ) ); ?>"></script>
    <script src="<?php echo esc_url( vendor_url( 'vue/vue.js?v=' . VUE_VERSION ) ); ?>"></script>
    <script src="<?php echo esc_url( vendor_url( 'vue-router/vue-router.js?v=' . VUE_ROUTER_VERSION ) ); ?>"></script>
    <script src="<?php echo esc_url( vendor_url( 'vuetify/vuetify.js?v=' . VUETIFY_VERSION ) ); ?>"></script>
    <script src="<?php echo esc_url( site_url( 'dev/js/tools/bundle.php?t=' . time() ) ); ?>"></script>
    <?php
} else {
    // CDN ASSETS
    // Use jsDelivr CDN to decrease loading time
    // Choose in "constants.php"
    if ( GECKO_CLIENT_CDN ) {
        ?>
        <script src="<?php echo esc_url( sprintf( 'https://cdn.jsdelivr.net/npm/lodash@%s/lodash.min.js', LODASH_VERSION ) ); ?>"></script>
        <script src="<?php echo esc_url( sprintf( 'https://cdn.jsdelivr.net/npm/axios@%s/dist/axios.min.js', AXIOS_VERSION ) ); ?>"></script>
        <script src="<?php echo esc_url( sprintf( 'https://cdn.jsdelivr.net/npm/vue@%s/dist/vue.min.js', VUE_VERSION ) ); ?>"></script>
        <script src="<?php echo esc_url( sprintf( 'https://cdn.jsdelivr.net/npm/vue-router@%s/dist/vue-router.min.js', VUE_ROUTER_VERSION ) ); ?>"></script>
        <script src="<?php echo esc_url( sprintf( 'https://cdn.jsdelivr.net/npm/vuetify@%s/dist/vuetify.min.js', VUETIFY_VERSION ) ); ?>"></script>
        <?php
    }
    // LOCAL ASSETS
    else {
        ?>
        <script src="<?php echo esc_url( vendor_url( 'lodash/lodash.min.js?v=' . LODASH_VERSION ) ); ?>"></script>
        <script src="<?php echo esc_url( vendor_url( 'axios/axios.min.js?v=' . AXIOS_VERSION ) ); ?>"></script>
        <script src="<?php echo esc_url( vendor_url( 'vue/vue.min.js?v=' . VUE_VERSION ) ); ?>"></script>
        <script src="<?php echo esc_url( vendor_url( 'vue-router/vue-router.min.js?v=' . VUE_ROUTER_VERSION ) ); ?>"></script>
        <script src="<?php echo esc_url( vendor_url( 'vuetify/vuetify.min.js?v=' . VUETIFY_VERSION ) ); ?>"></script>
        <?php
    }

    // FRONTEND APPLICATION
    // Choose unminified or minified in "constants.php"
    if ( GECKO_CLIENT_APP_MINIFIED ) {
        ?>
        <script src="<?php echo esc_url( get_file_url_for_display( 'assets/js/app.min.js' ) ); ?>"></script>
        <?php
    } else {
        ?>
        <script src="<?php echo esc_url( get_file_url_for_display( 'assets/js/app.js' ) ); ?>"></script>
        <?php
    }

}
