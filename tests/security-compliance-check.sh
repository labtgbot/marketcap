#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-security-privacy-compliance.md

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
        fail "$file does not include $description"
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
assert_file functions.php
assert_file api/router.php
assert_file config/api.php
assert_file dev/js/src/initial.js
assert_file .htaccess

assert_contains "$doc" '^# TONBANKCARD V2 Security, Privacy, and Compliance Hardening$' 'the launch-hardening title'
assert_contains "$doc" 'Issue: \[#38\]' 'the issue reference'
assert_contains "$doc" 'Content-Security-Policy' 'the CSP launch control'
assert_contains "$doc" 'CSRF' 'the CSRF launch control'
assert_contains "$doc" 'Sensitive endpoint access matrix' 'the sensitive endpoint access matrix'
assert_contains "$doc" 'Secret rotation plan' 'the secret rotation plan'
assert_contains "$doc" 'Privacy controls' 'the privacy controls'
assert_contains "$doc" 'Telegram Mini App' 'Telegram Mini App compliance notes'
assert_contains "$doc" 'TON Connect' 'TON Connect compliance notes'
assert_contains "$doc" 'CoinGecko' 'CoinGecko attribution notes'
assert_contains "$doc" 'AI' 'AI disclaimer notes'
assert_contains "$doc" 'ChangeNOW' 'ChangeNOW widget disclosure notes'
assert_contains "$doc" 'Manual abuse checklist' 'the manual abuse checklist'
assert_contains "$doc" 'tests/security-compliance-check\.sh' 'the security test convention'

assert_contains README.md 'docs/v2-security-privacy-compliance\.md' 'the security and compliance documentation link'
assert_contains package.json '"test:security-compliance"' 'the security and compliance npm script'
assert_contains package.json 'test:security-compliance' 'the aggregate security and compliance check'
assert_contains .htaccess 'X-Content-Type-Options' 'static asset nosniff header'
assert_contains .htaccess 'Referrer-Policy' 'static asset referrer policy header'
assert_contains .htaccess 'Permissions-Policy' 'static asset permissions policy header'
assert_contains functions.php 'Content-Security-Policy' 'the public CSP header helper'
assert_contains api/router.php 'X-TONBANKCARD-CSRF' 'the CSRF request header'
assert_contains config/api.php 'X-TONBANKCARD-CSRF' 'the CORS allow-list CSRF header'
assert_contains dev/js/src/initial.js 'csrf_token' 'frontend CSRF token capture'

php_check 'public security headers should include CSP and browser hardening directives' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require __DIR__ . '/functions.php';

$headers = tonbankcard_security_headers( 'html' );

foreach ( [ 'Content-Security-Policy', 'X-Content-Type-Options', 'Referrer-Policy', 'Permissions-Policy' ] as $name ) {
    if ( empty( $headers[ $name ] ) ) {
        fwrite( STDERR, "Missing $name security header\n" );
        exit( 1 );
    }
}

$csp = $headers['Content-Security-Policy'];
foreach ( [ "default-src 'self'", "object-src 'none'", "base-uri 'self'", 'frame-ancestors', 'telegram.org', 'changenow.io' ] as $directive ) {
    if ( FALSE === strpos( $csp, $directive ) ) {
        fwrite( STDERR, "CSP is missing required directive/source: $directive\n" );
        exit( 1 );
    }
}

if ( 'nosniff' !== $headers['X-Content-Type-Options'] ) {
    fwrite( STDERR, "X-Content-Type-Options should be nosniff\n" );
    exit( 1 );
}
PHP

php_check 'API responses should inherit non-CSP security headers' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/health',
        'headers' => [ 'x-request-id' => 'security-headers-test' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

foreach ( [ 'X-Content-Type-Options', 'Referrer-Policy', 'Permissions-Policy' ] as $name ) {
    if ( empty( $response['headers'][ $name ] ) ) {
        fwrite( STDERR, "API response missing $name\n" );
        exit( 1 );
    }
}

if ( isset( $response['headers']['Content-Security-Policy'] ) ) {
    fwrite( STDERR, "JSON API responses should not emit the HTML CSP\n" );
    exit( 1 );
}
PHP

php_check 'trusted cookie write requests should require a matching CSRF header' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/api/router.php';

$token = str_repeat( 'a', 64 );
$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/watchlist',
        'headers' => [
            'content-type' => 'application/json',
            'cookie'       => 'tonbankcard_session=' . $token,
        ],
        'body'    => json_encode( [ 'entries' => [] ], JSON_UNESCAPED_SLASHES ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

$payload = json_decode( $response['body'], TRUE );
if ( 403 !== $response['status'] || ! is_array( $payload ) || 'csrf_token_required' !== $payload['error']['code'] ) {
    fwrite( STDERR, 'Expected csrf_token_required, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}
PHP

php_check 'valid CSRF headers and bearer tokens should pass the CSRF gate' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/api/router.php';

$token = str_repeat( 'b', 64 );
$csrf = tonbankcard_api_csrf_token( $token );
$cookie_response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/watchlist',
        'headers' => [
            'content-type'       => 'application/json',
            'cookie'             => 'tonbankcard_session=' . $token,
            'x-tonbankcard-csrf' => $csrf,
        ],
        'body'    => json_encode( [ 'entries' => [] ], JSON_UNESCAPED_SLASHES ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

$cookie_payload = json_decode( $cookie_response['body'], TRUE );
if ( 403 === $cookie_response['status'] || ( is_array( $cookie_payload ) && isset( $cookie_payload['error']['code'] ) && 0 === strpos( $cookie_payload['error']['code'], 'csrf_token_' ) ) ) {
    fwrite( STDERR, 'Valid CSRF token was rejected: ' . $cookie_response['body'] . "\n" );
    exit( 1 );
}

$bearer_response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/watchlist',
        'headers' => [
            'content-type'  => 'application/json',
            'authorization' => 'Bearer ' . str_repeat( 'c', 64 ),
        ],
        'body'    => json_encode( [ 'entries' => [] ], JSON_UNESCAPED_SLASHES ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);

$bearer_payload = json_decode( $bearer_response['body'], TRUE );
if ( 403 === $bearer_response['status'] || ( is_array( $bearer_payload ) && isset( $bearer_payload['error']['code'] ) && 0 === strpos( $bearer_payload['error']['code'], 'csrf_token_' ) ) ) {
    fwrite( STDERR, 'Bearer session token should not require cookie CSRF: ' . $bearer_response['body'] . "\n" );
    exit( 1 );
}
PHP

php_check 'session responses should expose a CSRF token without exposing the session cookie value' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/api/router.php';

$store = sys_get_temp_dir() . '/tonbankcard-test-security-session.json';
@unlink( $store );

$runtime = $GLOBALS['runtime_config'];
$runtime['profile'] = 'local';
$runtime['urls']['active'] = 'http://localhost:8888/';
$config = $api;
$config['telegram_session']['local_session_store_path'] = $store;

$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/telegram/session',
        'headers' => [ 'content-type' => 'application/json' ],
        'body'    => '{}',
    ],
    [],
    $runtime,
    $config
);

if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected local session response, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}

$payload = json_decode( $response['body'], TRUE );
$csrf = isset( $payload['data']['session']['csrf_token'] ) ? (string) $payload['data']['session']['csrf_token'] : '';
if ( ! preg_match( '/^[a-f0-9]{64}$/', $csrf ) ) {
    fwrite( STDERR, "Session response did not expose a safe CSRF token\n" );
    exit( 1 );
}

$set_cookie = isset( $response['headers']['Set-Cookie'] ) ? (string) $response['headers']['Set-Cookie'] : '';
if ( FALSE === strpos( $set_cookie, 'HttpOnly' ) || FALSE === strpos( $set_cookie, 'SameSite=Lax' ) ) {
    fwrite( STDERR, "Session cookie is missing HttpOnly or SameSite=Lax\n" );
    exit( 1 );
}

if ( preg_match( '/tonbankcard_session=([A-Fa-f0-9]{64})/', $set_cookie, $matches ) && FALSE !== strpos( $response['body'], strtolower( $matches[1] ) ) ) {
    fwrite( STDERR, "Session response leaked the cookie session token\n" );
    exit( 1 );
}
PHP

php_check 'admin writes should require an owner admin role' \
    env -i PATH="$PATH" \
        TONBANKCARD_ADMIN_TOKEN=owner-secret \
        TONBANKCARD_ADMIN_SUPPORT_TOKEN=support-secret \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/api/router.php';

$runtime = $GLOBALS['runtime_config'];
$runtime['admin']['store_path'] = sys_get_temp_dir() . '/tonbankcard-test-security-admin.json';
@unlink( $runtime['admin']['store_path'] );

$response = tonbankcard_api_handle(
    [
        'method'  => 'PUT',
        'path'    => '/api/admin/feature-flags',
        'headers' => [
            'content-type'  => 'application/json',
            'authorization' => 'Bearer support-secret',
        ],
        'body'    => json_encode( [ 'feature_flags' => [ 'ai' => TRUE ] ], JSON_UNESCAPED_SLASHES ),
    ],
    [],
    $runtime,
    $api
);

$payload = json_decode( $response['body'], TRUE );
if ( 403 !== $response['status'] || ! is_array( $payload ) || 'admin_write_forbidden' !== $payload['error']['code'] ) {
    fwrite( STDERR, 'Expected admin_write_forbidden for support writes, got ' . $response['status'] . ': ' . $response['body'] . "\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Security and compliance check passed.'
