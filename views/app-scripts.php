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
    ?>
    <script id="route-<?php echo $name ?>" type="text/x-template">
        <?php include GECKO_CLIENT_TEMPLATES_DIR . "/routes/" . $name . '.php'; ?>
    </script>
    <?php
}

/*
| -------------------------------------------------------------------------
| FRONTEND DATA
| -------------------------------------------------------------------------
*/
$gecko_client = [
    'version'               => GECKO_CLIENT_VERSION,
    'vuetifyOptions'        => vuetify_constructor_options(),
    'translation'           => $translation,
    'defaultVsCurrencyId'   => $coingecko['default_vs_currency'],
    'supportedVsCurrencies' => get_enabled_supported_vs_currencies(),
    'routerMode'            => 'history',
    'routerBase'            => router_base(),
    'routesConfig'          => $enabled_routes,
    'options'               => $frontend_options,
    'formats'               => $formats,
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
    'cache'   => (bool) $coingecko['cache'],
    'timeout' => (int) $coingecko['timeout'],
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
    <script src="<?php echo esc_url( vendor_url( 'echarts/echarts.js?v=' . ECHARTS_VERSION ) ); ?>"></script>
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
        <script src="<?php echo esc_url( sprintf( 'https://cdn.jsdelivr.net/npm/echarts@%s/dist/echarts.min.js', ECHARTS_VERSION ) ); ?>"></script>
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
        <script src="<?php echo esc_url( vendor_url( 'echarts/echarts.min.js?v=' . ECHARTS_VERSION ) ); ?>"></script>
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