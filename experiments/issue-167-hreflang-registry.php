<?php
/**
 * Experiment for issue #167: verify hreflang language list is derived from the
 * translation registry, so adding a dictionary surfaces the language in the
 * SEO signals automatically.
 *
 * Run: php experiments/issue-167-hreflang-registry.php
 */

define( 'GECKO_CLIENT_VERSION', 'experiment' );
define( 'GECKO_CLIENT_CONFIG_DIR', dirname( __DIR__ ) . '/config' );

require dirname( __DIR__ ) . '/functions.php';

function show( $label, $value ) {
    echo $label . ': ' . json_encode( $value, JSON_UNESCAPED_UNICODE ) . "\n";
}

// 1) Default registry (loaded from the directory; global not set).
show( 'registry languages (dir scan)', tonbankcard_translation_registry_languages() );
show( 'supported languages', tonbankcard_supported_languages() );
show( 'seo languages', tonbankcard_seo_languages() );
show( 'hreflang alternates', tonbankcard_seo_hreflang_alternates( 'https://example.test/coins/bitcoin' ) );

// 2) Simulate a runtime registry that gained a new language (e.g. de.php).
$GLOBALS['tonbankcard_translations'] = [
    'en' => [],
    'ru' => [],
    'de' => [],
];
echo "\n--- after registering 'de' in the runtime registry ---\n";
show( 'supported languages', tonbankcard_supported_languages() );
show( 'seo languages', tonbankcard_seo_languages() );
$alts = tonbankcard_seo_hreflang_alternates( 'https://example.test/' );
show( 'hreflang alternates', $alts );

$codes = array_column( $alts, 'hreflang' );
$assert_de = in_array( 'de', $codes, TRUE ) ? 'PASS' : 'FAIL';
echo "assert: new language 'de' present in hreflang => $assert_de\n";
