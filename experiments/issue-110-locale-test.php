<?php
/**
 * Smoke test for issue #110 multi-language helpers.
 *
 * Run with: php experiments/issue-110-locale-test.php
 */

require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../functions.php';

$failures = 0;

function expect( $label, $expected, $actual ) {
    global $failures;
    $ok = $expected === $actual;
    if ( $ok ) {
        echo "PASS  $label\n";
    } else {
        echo "FAIL  $label\n";
        echo "      expected: " . var_export( $expected, true ) . "\n";
        echo "      actual:   " . var_export( $actual, true ) . "\n";
        $failures++;
    }
}

// Supported languages
$supported = tonbankcard_supported_languages();
expect( 'supported languages contain en', true, isset( $supported['en'] ) );
expect( 'supported languages contain ru', true, isset( $supported['ru'] ) );
expect( 'supported languages contain fr', true, isset( $supported['fr'] ) );
expect( 'supported languages contain ar', true, isset( $supported['ar'] ) );
expect( 'supported languages contain zh', true, isset( $supported['zh'] ) );

// RTL detection
expect( 'ar is RTL', true, tonbankcard_language_is_rtl( 'ar' ) );
expect( 'en is not RTL', false, tonbankcard_language_is_rtl( 'en' ) );

// Normalize
expect( 'normalize EN-US to en', 'en', tonbankcard_normalize_language( 'EN-US' ) );
expect( 'normalize ru-RU to ru', 'ru', tonbankcard_normalize_language( 'ru-RU' ) );
expect( 'normalize unknown to en', 'en', tonbankcard_normalize_language( 'xx' ) );
expect( 'normalize zh-Hans to zh', 'zh', tonbankcard_normalize_language( 'zh-Hans' ) );

// Accept-Language parsing
expect( 'accept ru,en;q=0.5 -> ru', 'ru', tonbankcard_language_from_accept_header( 'ru,en;q=0.5' ) );
expect( 'accept fr-FR,fr;q=0.9,en;q=0.8 -> fr', 'fr', tonbankcard_language_from_accept_header( 'fr-FR,fr;q=0.9,en;q=0.8' ) );
expect( 'accept zh-CN -> zh', 'zh', tonbankcard_language_from_accept_header( 'zh-CN' ) );
expect( 'accept empty -> null', null, tonbankcard_language_from_accept_header( '' ) );

// Active language resolution: $_GET wins
$_GET = [ 'lang' => 'fr' ];
$_COOKIE = [ 'tbc_lang' => 'ru' ];
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'zh';
// Active language is statically cached, so call against translations to retrigger.
$reflect = function () { return tonbankcard_active_language(); };
expect( 'active language uses GET param', 'fr', $reflect() );

// Safe redirect path
expect( 'relative path /foo passes', '/foo', tonbankcard_safe_redirect_path( '/foo' ) );
expect( 'protocol-relative // is rejected', null, tonbankcard_safe_redirect_path( '//evil.test/' ) );
expect( 'absolute external URL rejected', null, tonbankcard_safe_redirect_path( 'https://evil.test/x' ) );
$_SERVER['HTTP_HOST'] = 'localhost:8080';
expect( 'absolute same-origin URL accepted', '/path?x=1', tonbankcard_safe_redirect_path( 'http://localhost:8080/path?x=1' ) );

echo "\n";
if ( 0 === $failures ) {
    echo "OK - all assertions passed\n";
    exit( 0 );
}
echo "FAIL - $failures assertion(s) failed\n";
exit( 1 );
