#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-ton-connect-wallet-profile.md

fail() {
    printf '%s\n' "FAIL: $1" >&2
    failures=$((failures + 1))
}

assert_file() {
    if [ ! -f "$1" ]; then
        fail "Missing required file: $1"
    fi
}

assert_contains() {
    file=$1
    pattern=$2
    description=$3

    if [ ! -f "$file" ]; then
        fail "Cannot inspect missing file: $file"
        return
    fi

    if ! grep -Eq "$pattern" "$file"; then
        fail "$file does not contain $description"
    fi
}

assert_not_contains() {
    file=$1
    pattern=$2
    description=$3

    if [ ! -f "$file" ]; then
        fail "Cannot inspect missing file: $file"
        return
    fi

    if grep -Eiq "$pattern" "$file"; then
        fail "$file unexpectedly contains $description"
    fi
}

php_check() {
    description=$1
    shift

    if ! "$@"; then
        fail "$description"
    fi
}

assert_file "$doc"
assert_file dev/js/src/ton-connect.js
assert_file dev/js/src/routes/wallet-profile.js
assert_file templates/routes/wallet-profile.php

assert_contains "$doc" '^# TONBANKCARD V2 TON Connect Wallet Profile$' 'the TON Connect wallet profile title'
assert_contains "$doc" 'Issue: \[#30\]' 'the issue reference'
assert_contains "$doc" '/tonconnect-manifest\.json' 'the TON Connect manifest URL'
assert_contains "$doc" 'TON Connect only' 'the TON Connect-only wallet boundary'
assert_contains "$doc" 'Private keys and seed phrases are never requested' 'the private-key safety statement'
assert_contains "$doc" 'disconnect' 'the disconnect and local clearing workflow'
assert_contains "$doc" 'Telegram Mini App' 'Telegram context coverage'

assert_contains README.md 'docs/v2-ton-connect-wallet-profile\.md' 'the TON Connect documentation link'
assert_contains package.json '"test:ton-connect"' 'the TON Connect npm script'
assert_contains package.json 'test:ton-connect' 'the aggregate TON Connect check'

assert_contains config/runtime.php "'ton_connect'" 'the TON Connect feature flag'
assert_contains config/routes.php "'wallet-profile'" 'the wallet profile app route'
assert_contains config/routes-v2.php "'wallet-profile'" 'the wallet profile public route metadata'
assert_contains config/navigation.php 'Wallet Profile' 'the wallet profile navigation item'
assert_contains config/v2.php 'Wallet' 'the public wallet navigation label'
assert_contains views/app-scripts.php 'tonConnect' 'the browser TON Connect configuration payload'
assert_contains views/app-scripts.php 'tonconnect-manifest\.json' 'the browser manifest URL'
assert_contains views/app-scripts.php '@tonconnect/ui@2\.4\.4' 'the pinned TON Connect UI SDK URL'
assert_contains functions.php 'tonbankcard_ton_connect_manifest' 'the runtime-aware manifest renderer'
assert_contains functions.php '/tonconnect-manifest\.json' 'the manifest dispatch route'
assert_contains dev/js/source.json '"ton-connect\.js"' 'the TON Connect source bundle entry'
assert_contains dev/js/source.json '"routes/wallet-profile\.js"' 'the wallet profile route source bundle entry'
assert_contains templates/routes/wallet-profile.php 'Connect TON wallet' 'the connect action'
assert_contains templates/routes/wallet-profile.php 'Disconnect wallet' 'the disconnect action'
assert_contains templates/routes/wallet-profile.php 'Private keys and seed phrases stay in your wallet' 'the in-app privacy explanation'
assert_not_contains templates/routes/wallet-profile.php '<input|v-text-field|textarea' 'manual secret entry fields'
assert_contains dev/js/src/ton-connect.js 'TON_CONNECT_UI' 'the TON Connect UI SDK global'
assert_contains dev/js/src/ton-connect.js 'connectWallet' 'the TON Connect connect method'
assert_contains dev/js/src/ton-connect.js 'disconnect' 'the TON Connect disconnect method'
assert_contains dev/js/src/ton-connect.js 'TONBANKCARD:ton-connect-wallet:v1' 'the bounded local wallet storage key'
assert_contains dev/js/src/ton-connect.js 'private_key' 'explicit rejection of private key-shaped payloads'
assert_contains tests/browser-smoke.js 'ton-connect-wallet-profile\.png' 'the wallet profile screenshot hook'
assert_contains tests/browser-smoke.js 'TON_CONNECT_UI' 'the Playwright TON Connect SDK stub'
assert_contains tests/browser-smoke.js 'Connect TON wallet' 'browser wallet connect coverage'
assert_contains tests/browser-smoke.js 'Telegram Mini App' 'Telegram wallet profile coverage'
assert_contains assets/css/style.css 'wallet-profile' 'wallet profile styling'

php_check 'TON Connect manifest should expose absolute public metadata without secrets' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_LOCAL_BASE_URL='https://marketcap.test/app/' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require GECKO_CLIENT_CONFIG_DIR . '/routes.php';
require GECKO_CLIENT_CONFIG_DIR . '/routes-v2.php';
require __DIR__ . '/functions.php';

$payload = tonbankcard_ton_connect_manifest();
$manifest = json_decode( $payload, TRUE );
if ( ! is_array( $manifest ) ) {
    fwrite( STDERR, "Manifest is not valid JSON\n" );
    exit( 1 );
}

foreach ( [ 'url', 'name', 'iconUrl', 'termsOfUseUrl', 'privacyPolicyUrl' ] as $field ) {
    if ( empty( $manifest[ $field ] ) || ! is_string( $manifest[ $field ] ) ) {
        fwrite( STDERR, "Manifest missing $field\n" );
        exit( 1 );
    }
}

foreach ( [ 'url', 'iconUrl', 'termsOfUseUrl', 'privacyPolicyUrl' ] as $field ) {
    if ( 0 !== strpos( $manifest[ $field ], 'https://marketcap.test/app/' ) ) {
        fwrite( STDERR, "$field is not rooted at the configured base URL: {$manifest[$field]}\n" );
        exit( 1 );
    }
}

$serialized = json_encode( $manifest );
foreach ( [ 'token', 'secret', 'private', 'seed' ] as $leak ) {
    if ( FALSE !== stripos( $serialized, $leak ) ) {
        fwrite( STDERR, "Manifest leaked forbidden word: $leak\n" );
        exit( 1 );
    }
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'TON Connect wallet profile check passed.'
