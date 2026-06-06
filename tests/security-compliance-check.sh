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

js_check() {
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
assert_file tests/url-scheme-check.js
assert_file .htaccess

assert_contains "$doc" '^# TONBANKCARD V2 Security, Privacy, and Compliance Hardening$' 'the launch-hardening title'
assert_contains "$doc" 'Issue: \[#38\]' 'the issue reference'
assert_contains "$doc" 'Content-Security-Policy' 'the CSP launch control'
assert_contains "$doc" 'Strict-Transport-Security' 'the HSTS launch control'
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
assert_contains .htaccess 'Strict-Transport-Security' 'the Apache HSTS header'
assert_contains .htaccess 'RewriteCond %\{HTTPS\} !=on' 'the active Apache HTTPS redirect condition'
assert_contains .htaccess 'RewriteRule \^ https://%\{HTTP_HOST\}%\{REQUEST_URI\}' 'the active Apache HTTPS redirect target'
assert_contains .htaccess 'X-Content-Type-Options' 'static asset nosniff header'
assert_contains .htaccess 'Referrer-Policy' 'static asset referrer policy header'
assert_contains .htaccess 'Permissions-Policy' 'static asset permissions policy header'
assert_contains .htaccess 'X-Frame-Options' 'static asset clickjacking fallback header'
assert_contains .htaccess 'BLOCK SENSITIVE SOURCE AND INTERNAL FILES' 'sensitive source request protection section'
assert_contains .htaccess 'install\|database\|docs\|tests\|dev' 'sensitive directory request deny-list'
assert_contains .htaccess 'zip\|sql\|md' 'sensitive file extension request deny-list'
assert_contains functions.php 'Content-Security-Policy' 'the public CSP header helper'
assert_contains api/router.php 'X-TONBANKCARD-CSRF' 'the CSRF request header'
assert_contains config/api.php 'X-TONBANKCARD-CSRF' 'the CORS allow-list CSRF header'
assert_contains dev/js/src/initial.js 'csrf_token' 'frontend CSRF token capture'

php_check 'public HTTPS security headers should include CSP, HSTS, and browser hardening directives' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=production \
        TONBANKCARD_PUBLIC_BASE_URL='https://marketcap.example.com/' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require __DIR__ . '/functions.php';

$headers = tonbankcard_security_headers( 'html' );

foreach ( [ 'Content-Security-Policy', 'Strict-Transport-Security', 'X-Frame-Options', 'X-Content-Type-Options', 'Referrer-Policy', 'Permissions-Policy' ] as $name ) {
    if ( empty( $headers[ $name ] ) ) {
        fwrite( STDERR, "Missing $name security header\n" );
        exit( 1 );
    }
}

$csp = $headers['Content-Security-Policy'];
foreach ( [ "default-src 'self'", "object-src 'none'", "base-uri 'self'", 'frame-ancestors', 'telegram.org', 'changenow.io', 'mc.yandex.ru', 'mc.yandex.com', 'mc.webvisor.com', 'mc.webvisor.org', 'yastatic.net', 'child-src', 'frame-src', 'blob:', 'wss://mc.yandex.ru', 'wss://mc.yandex.com' ] as $directive ) {
    if ( FALSE === strpos( $csp, $directive ) ) {
        fwrite( STDERR, "CSP is missing required directive/source: $directive\n" );
        exit( 1 );
    }
}

$script_src = '';
foreach ( explode( ';', $csp ) as $directive ) {
    $directive = trim( $directive );
    if ( 0 === strpos( $directive, 'script-src ' ) ) {
        $script_src = $directive;
        break;
    }
}
if ( '' === $script_src ) {
    fwrite( STDERR, "CSP is missing script-src\n" );
    exit( 1 );
}
if ( FALSE !== strpos( $script_src, "'unsafe-inline'" ) ) {
    fwrite( STDERR, "script-src must not allow unsafe-inline: $script_src\n" );
    exit( 1 );
}
if ( ! preg_match( "/'nonce-[A-Fa-f0-9]{32}'/", $script_src ) ) {
    fwrite( STDERR, "script-src is missing a per-response nonce: $script_src\n" );
    exit( 1 );
}
if ( ! function_exists( 'tonbankcard_csp_nonce' ) ) {
    fwrite( STDERR, "Missing tonbankcard_csp_nonce helper\n" );
    exit( 1 );
}
$nonce = tonbankcard_csp_nonce();
if ( ! preg_match( '/^[A-Fa-f0-9]{32}$/', $nonce ) || $nonce !== tonbankcard_csp_nonce() ) {
    fwrite( STDERR, "CSP nonce should be stable per request and 128-bit hex\n" );
    exit( 1 );
}
if ( 'SAMEORIGIN' !== $headers['X-Frame-Options'] ) {
    fwrite( STDERR, "X-Frame-Options should be SAMEORIGIN\n" );
    exit( 1 );
}
if ( 'nosniff' !== $headers['X-Content-Type-Options'] ) {
    fwrite( STDERR, "X-Content-Type-Options should be nosniff\n" );
    exit( 1 );
}

if ( 'max-age=63072000; includeSubDomains; preload' !== $headers['Strict-Transport-Security'] ) {
    fwrite( STDERR, "Unexpected HSTS header: " . $headers['Strict-Transport-Security'] . "\n" );
    exit( 1 );
}
PHP

php_check 'API responses should inherit non-CSP security headers' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=production \
        TONBANKCARD_PUBLIC_BASE_URL='https://marketcap.example.com/' \
        php <<'PHP'
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

foreach ( [ 'Strict-Transport-Security', 'X-Frame-Options', 'X-Content-Type-Options', 'Referrer-Policy', 'Permissions-Policy' ] as $name ) {
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

php_check 'forced HTTP requests should resolve to the HTTPS URL for redirect' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=production \
        TONBANKCARD_PUBLIC_BASE_URL='https://marketcap.example.com/' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require __DIR__ . '/functions.php';

if ( ! function_exists( 'tonbankcard_https_redirect_url' ) ) {
    fwrite( STDERR, "Missing tonbankcard_https_redirect_url helper\n" );
    exit( 1 );
}

$redirect_url = tonbankcard_https_redirect_url(
    $GLOBALS['runtime_config'],
    [
        'HTTPS'       => 'off',
        'HTTP_HOST'   => 'marketcap.example.com',
        'REQUEST_URI' => '/markets?tab=ton',
    ]
);

if ( 'https://marketcap.example.com/markets?tab=ton' !== $redirect_url ) {
    fwrite( STDERR, "Unexpected HTTPS redirect URL: " . var_export( $redirect_url, TRUE ) . "\n" );
    exit( 1 );
}

$local_redirect = tonbankcard_https_redirect_url(
    [
        'security' => [ 'force_https' => TRUE ],
    ],
    [
        'HTTPS'       => 'off',
        'HTTP_HOST'   => 'localhost:8888',
        'REQUEST_URI' => '/markets',
    ]
);

if ( null !== $local_redirect ) {
    fwrite( STDERR, "Localhost should not be force-redirected: " . $local_redirect . "\n" );
    exit( 1 );
}
PHP

php_check 'production session cookies should stay Secure even when the active request arrived over HTTP' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=production \
        TONBANKCARD_PUBLIC_BASE_URL='http://marketcap.example.com/' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/api/router.php';

$cookie = tonbankcard_api_session_cookie_header(
    [
        'session_token'        => str_repeat( 'a', 64 ),
        'expires_at_timestamp' => time() + 3600,
    ],
    $GLOBALS['runtime_config'],
    [ 'session_ttl_seconds' => 3600 ]
);

if ( FALSE === strpos( $cookie, '; Secure' ) ) {
    fwrite( STDERR, "Production session cookie is missing Secure: $cookie\n" );
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

$store_dir = sys_get_temp_dir() . '/tonbankcard-test-security-session-' . getmypid();
if ( ! is_dir( $store_dir ) && ! mkdir( $store_dir, 0700, TRUE ) ) {
    fwrite( STDERR, "Could not create private session store directory\n" );
    exit( 1 );
}
chmod( $store_dir, 0700 );
$store = $store_dir . '/sessions.json';
@unlink( $store );
register_shutdown_function(
    function () use ( $store, $store_dir ) {
        @unlink( $store );
        @rmdir( $store_dir );
    }
);

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
$admin_store_dir = sys_get_temp_dir() . '/tonbankcard-test-security-admin-' . getmypid();
if ( ! is_dir( $admin_store_dir ) && ! mkdir( $admin_store_dir, 0700, TRUE ) ) {
    fwrite( STDERR, "Could not create private admin store directory\n" );
    exit( 1 );
}
chmod( $admin_store_dir, 0700 );
$runtime['admin']['store_path'] = $admin_store_dir . '/admin.json';
@unlink( $runtime['admin']['store_path'] );
register_shutdown_function(
    function () use ( $runtime, $admin_store_dir ) {
        @unlink( $runtime['admin']['store_path'] );
        @rmdir( $admin_store_dir );
    }
);

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

assert_contains views/app-head.php 'JSON_HEX_TAG' 'JSON-LD output should HTML-escape tag characters'
assert_contains views/app-head.php 'tonbankcard_csp_nonce' 'CSP nonce attributes for inline head scripts'
assert_contains views/app-scripts.php 'tonbankcard_csp_nonce' 'CSP nonce attributes for app template and bootstrap scripts'
assert_contains "$doc" 'per-response CSP nonce' 'the script-src nonce hardening note'

if grep -Eq 'onclick=' views/configuration-errors.php; then
    fail 'configuration error page still uses inline JavaScript event handlers'
fi

if grep -Eq 'application/ld\+json.*JSON_UNESCAPED_SLASHES' views/app-head.php; then
    fail 'views/app-head.php still emits JSON-LD with JSON_UNESCAPED_SLASHES (allows </script> breakout)'
fi

php_check 'JSON-LD output should not allow a </script> breakout via the :id route parameter' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require __DIR__ . '/functions.php';

/*
 * Reproduces issue #186: a /coins/:id slug carrying "</script>" markup must not
 * break out of the server-rendered JSON-LD <script> block (views/app-head.php).
 */
$payload_id = rawurldecode( '%3C%2Fscript%3E%3Cscript%3Ealert(document.domain)%3C%2Fscript%3E' );

// Defense-in-depth: tonbankcard_slug_title() must strip markup characters.
$title = tonbankcard_slug_title( $payload_id );
if ( FALSE !== strpos( $title, '<' ) || FALSE !== strpos( $title, '>' ) || FALSE !== strpos( $title, '/' ) ) {
    fwrite( STDERR, "tonbankcard_slug_title leaked markup characters: $title\n" );
    exit( 1 );
}

// Primary defense: the encoder flags used by views/app-head.php must HTML-escape
// tag characters even when the subject still contains raw markup.
$meta = tonbankcard_public_route_meta( '/coins/' . $payload_id );
$meta['subject'] = $payload_id;
$meta['title']   = $payload_id;
$linked_data     = tonbankcard_public_linked_data( $meta );
$json = json_encode( $linked_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE );

if ( FALSE !== stripos( $json, '</script>' ) || FALSE !== stripos( $json, '<script>' ) ) {
    fwrite( STDERR, "JSON-LD output can break out of the <script> block: $json\n" );
    exit( 1 );
}
PHP

php_check 'locale-set redirects should reject slash-backslash open redirects' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require __DIR__ . '/functions.php';

$_SERVER['HTTP_HOST'] = 'example.test';

$rejected = [
    '/\\evil.com',
    '/\\\\evil.com',
    '\\evil.com',
    'https://example.test/\\evil.com',
    "//evil.com",
    "/ok\r\nLocation: https://evil.test",
];

foreach ( $rejected as $target ) {
    $result = tonbankcard_safe_redirect_path( $target );
    if ( null !== $result ) {
        fwrite( STDERR, "Unsafe redirect target was accepted: $target => $result\n" );
        exit( 1 );
    }
}

$accepted = [
    '/markets'                                => '/markets',
    '/markets?tab=ton'                        => '/markets?tab=ton',
    'https://example.test/currencies?page=2'  => '/currencies?page=2',
];

foreach ( $accepted as $target => $expected ) {
    $result = tonbankcard_safe_redirect_path( $target );
    if ( $expected !== $result ) {
        fwrite( STDERR, "Safe redirect target changed: $target => " . var_export( $result, TRUE ) . "\n" );
        exit( 1 );
    }
}
PHP

php_check 'locale-set cookie writes should require a same-origin request source' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/site.php';
require __DIR__ . '/functions.php';

$cases = [
    [
        'server'   => [
            'HTTP_HOST'    => 'example.test',
            'HTTP_ORIGIN'  => 'https://example.test',
        ],
        'expected' => TRUE,
    ],
    [
        'server'   => [
            'HTTP_HOST'    => 'example.test',
            'HTTP_REFERER' => 'https://example.test/markets',
        ],
        'expected' => TRUE,
    ],
    [
        'server'   => [
            'HTTP_HOST'    => 'example.test',
            'HTTP_ORIGIN'  => 'https://evil.test',
        ],
        'expected' => FALSE,
    ],
    [
        'server'   => [
            'HTTP_HOST'    => 'example.test',
            'HTTP_REFERER' => 'https://evil.test/attack',
        ],
        'expected' => FALSE,
    ],
    [
        'server'   => [
            'HTTP_HOST' => 'example.test',
        ],
        'expected' => FALSE,
    ],
];

foreach ( $cases as $case ) {
    $_SERVER = $case['server'];
    $result = tonbankcard_locale_set_request_is_same_origin();
    if ( $case['expected'] !== $result ) {
        fwrite( STDERR, 'Unexpected same-origin result for ' . json_encode( $case['server'], JSON_UNESCAPED_SLASHES ) . ': ' . var_export( $result, TRUE ) . "\n" );
        exit( 1 );
    }
}
PHP

js_check 'frontend URL helpers should reject dangerous schemes before href/navigation sinks' \
    node tests/url-scheme-check.js

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Security and compliance check passed.'
