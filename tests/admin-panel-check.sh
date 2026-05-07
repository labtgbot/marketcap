#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-admin-panel.md

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

    if grep -Eq "$pattern" "$file"; then
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

assert_file api/admin.php
assert_file "$doc"
assert_file templates/routes/admin.php
assert_file dev/js/src/routes/admin.js

assert_contains "$doc" '^# TONBANKCARD V2 Admin Panel$' 'the admin panel documentation title'
assert_contains "$doc" 'Issue: \[#35\]' 'the issue reference'
assert_contains "$doc" '/api/admin/feature-flags' 'feature flag admin endpoint'
assert_contains "$doc" 'support role' 'read-only support role behavior'
assert_contains "$doc" 'Secrets are write-only' 'write-only secret handling'
assert_contains "$doc" 'audit log' 'admin audit log behavior'
assert_contains README.md 'docs/v2-admin-panel\.md' 'the admin panel documentation link'
assert_contains .env.example '^TONBANKCARD_ADMIN_STORE=' 'the admin store environment variable'
assert_contains .env.example '^TONBANKCARD_ADMIN_TOKEN=' 'the admin token environment variable'
assert_contains .env.example '^TONBANKCARD_ADMIN_SUPPORT_TOKEN=' 'the support token environment variable'
assert_contains package.json '"test:admin-panel"' 'the admin panel npm script'
assert_contains package.json 'test:admin-panel' 'the aggregate admin panel check'
assert_contains api/router.php '/api/admin/feature-flags' 'the API route index entry'
assert_contains api/router.php 'tonbankcard_api_admin_handle' 'admin route dispatch'
assert_contains config/runtime.php 'TONBANKCARD_ADMIN_STORE' 'runtime admin store integration'
assert_contains config/routes.php "'admin'" 'admin route registration'
assert_contains config/routes-v2.php "'admin'" 'admin route metadata'
assert_contains dev/js/source.json '"routes/admin.js"' 'admin route source bundle entry'
assert_contains templates/routes/admin.php 'admin-secret-input' 'write-only secret field markup'
assert_contains templates/routes/admin.php 'Yandex Metrica' 'Yandex Metrica admin controls'
assert_contains views/app-head.php 'mc\.yandex\.ru/metrika/tag\.js' 'Yandex Metrica public embed'
assert_contains assets/css/style.css 'admin-shell' 'admin panel styling'

php_check 'Yandex Metrica controls should be rendered inside the Providers section before Operations starts' \
    php <<'PHP'
<?php
$template = file_get_contents( 'templates/routes/admin.php' );
$providers = strpos( $template, "activeSection === 'providers'" );
$metrica = strpos( $template, 'Yandex Metrica' );
$operations = strpos( $template, "activeSection === 'operations'" );
if ( FALSE === $providers || FALSE === $metrica || FALSE === $operations || ! ( $providers < $metrica && $metrica < $operations ) ) {
    fwrite( STDERR, "Yandex Metrica controls are not inside the Providers section\n" );
    exit( 1 );
}
PHP

php_check 'Admin API should require admin auth, enforce read-only support, audit writes, merge runtime flags, and redact secrets' \
    env -i PATH="$PATH" \
        TONBANKCARD_ADMIN_STORE="$(mktemp)" \
        TEST_ENV_FILE="$PWD/.env" \
        TONBANKCARD_ADMIN_TOKEN='owner-secret-token' \
        TONBANKCARD_ADMIN_SUPPORT_TOKEN='support-secret-token' \
        php <<'PHP'
<?php
$env_path = (string) getenv( 'TEST_ENV_FILE' );
$previous_env = is_file( $env_path ) ? file_get_contents( $env_path ) : null;
file_put_contents(
    $env_path,
    implode(
        "\n",
        [
            'TONBANKCARD_FEATURE_AI=true',
            'TONBANKCARD_FEATURE_ALERTS=true',
            'TONBANKCARD_FEATURE_WIDGET=true',
            'TONBANKCARD_FEATURE_CHANGENOW=true',
            'COINGECKO_API_PLAN=demo',
            'COINGECKO_API_KEY=old-demo-key',
            'GROQ_MODEL_ID=old-model',
            'GROQ_API_KEY=old-groq-key',
            'TONBANKCARD_BOT_USERNAME=old_bot',
            'TONBANKCARD_BOT_TOKEN=old-bot-token',
            'CHANGENOW_LINK_ID=old-link',
        ]
    ) . "\n"
);
register_shutdown_function(
    function () use ( $env_path, $previous_env ) {
        if ( null === $previous_env ) {
            @unlink( $env_path );
            return;
        }
        file_put_contents( $env_path, $previous_env );
    }
);

require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$store_path = (string) getenv( 'TONBANKCARD_ADMIN_STORE' );
@unlink( $store_path );

function call_admin_api( array $request, array $runtime, array $api ) {
    return tonbankcard_api_handle( array_merge( [ 'body' => '' ], $request ), [], $runtime, $api );
}

function json_payload( array $response ) {
    $payload = json_decode( $response['body'], TRUE );
    if ( ! is_array( $payload ) ) {
        fwrite( STDERR, "Response body was not JSON: {$response['body']}\n" );
        exit( 1 );
    }
    return $payload;
}

$runtime = $GLOBALS['runtime_config'];

$response = call_admin_api(
    [
        'method'  => 'GET',
        'path'    => '/api/admin/config',
        'headers' => [ 'x-request-id' => 'admin-anonymous' ],
    ],
    $runtime,
    $api
);
if ( 401 !== $response['status'] ) {
    fwrite( STDERR, 'Anonymous admin config returned ' . $response['status'] . " instead of 401\n" );
    exit( 1 );
}

$response = call_admin_api(
    [
        'method'  => 'GET',
        'path'    => '/api/admin/config',
        'headers' => [
            'x-request-id' => 'admin-telegram-only',
            'x-telegram-init-data' => 'query_id=not-admin',
        ],
    ],
    $runtime,
    $api
);
if ( 401 !== $response['status'] ) {
    fwrite( STDERR, 'Telegram-only admin config returned ' . $response['status'] . " instead of 401\n" );
    exit( 1 );
}

$response = call_admin_api(
    [
        'method'  => 'GET',
        'path'    => '/api/admin/config',
        'headers' => [
            'x-request-id' => 'admin-support-read',
            'x-tonbankcard-admin' => 'support-secret-token',
        ],
    ],
    $runtime,
    $api
);
$payload = json_payload( $response );
if ( 200 !== $response['status'] || TRUE !== $payload['ok'] || 'support' !== $payload['data']['actor']['role'] ) {
    fwrite( STDERR, "Support token could not read admin config\n" );
    exit( 1 );
}
if ( ! empty( $payload['data']['actor']['permissions']['write'] ) ) {
    fwrite( STDERR, "Support actor unexpectedly has write permission\n" );
    exit( 1 );
}

$response = call_admin_api(
    [
        'method'  => 'PUT',
        'path'    => '/api/admin/feature-flags',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'admin-support-write',
            'x-tonbankcard-admin' => 'support-secret-token',
        ],
        'body'    => json_encode( [ 'feature_flags' => [ 'ai' => FALSE ] ] ),
    ],
    $runtime,
    $api
);
if ( 403 !== $response['status'] ) {
    fwrite( STDERR, 'Support feature flag write returned ' . $response['status'] . " instead of 403\n" );
    exit( 1 );
}

$flags = [
    'ai'           => FALSE,
    'alerts'       => FALSE,
    'widget'       => FALSE,
    'ton_connect'  => FALSE,
    'gamification' => FALSE,
    'referrals'    => TRUE,
    'premium'      => TRUE,
];
$response = call_admin_api(
    [
        'method'  => 'PUT',
        'path'    => '/api/admin/feature-flags',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'admin-flags-write',
            'authorization' => 'Bearer owner-secret-token',
        ],
        'body'    => json_encode( [ 'feature_flags' => $flags ] ),
    ],
    $runtime,
    $api
);
$payload = json_payload( $response );
if ( 200 !== $response['status'] || TRUE !== $payload['ok'] ) {
    fwrite( STDERR, "Admin feature flag write failed: {$response['body']}\n" );
    exit( 1 );
}
foreach ( [ 'ai', 'alerts', 'widget', 'ton_connect', 'gamification' ] as $flag ) {
    if ( TRUE === $payload['data']['feature_flags'][ $flag ] ) {
        fwrite( STDERR, "Feature flag {$flag} was not disabled independently\n" );
        exit( 1 );
    }
}
if ( FALSE !== $payload['data']['feature_flags']['changenow'] ) {
    fwrite( STDERR, "Widget flag did not synchronize ChangeNOW runtime control\n" );
    exit( 1 );
}
$env_after_flags = file_get_contents( $env_path );
foreach ( [ 'TONBANKCARD_FEATURE_AI=false', 'TONBANKCARD_FEATURE_ALERTS=false', 'TONBANKCARD_FEATURE_WIDGET=false', 'TONBANKCARD_FEATURE_CHANGENOW=false' ] as $line ) {
    if ( FALSE === strpos( $env_after_flags, $line ) ) {
        fwrite( STDERR, "Admin feature flag save did not update .env line: {$line}\n" );
        exit( 1 );
    }
}

$reloaded = tonbankcard_runtime_config();
if (
    TRUE === $reloaded['feature_flags']['ai'] ||
    TRUE === $reloaded['feature_flags']['alerts'] ||
    TRUE === $reloaded['feature_flags']['widget'] ||
    TRUE === $reloaded['feature_flags']['ton_connect'] ||
    TRUE === $reloaded['feature_flags']['gamification'] ||
    TRUE === $reloaded['feature_flags']['changenow']
) {
    fwrite( STDERR, "Runtime feature flags did not merge admin-store overrides\n" );
    exit( 1 );
}

$response = call_admin_api(
    [
        'method'  => 'PUT',
        'path'    => '/api/admin/providers',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'admin-provider-write',
            'x-tonbankcard-admin' => 'owner-secret-token',
        ],
        'body'    => json_encode(
            [
                'providers' => [
                    'groq' => [
                        'model_id' => 'llama-3.3-70b-versatile',
                        'api_key'  => 'groq-secret-value-that-must-not-return',
                    ],
                    'coingecko' => [
                        'api_plan' => 'pro',
                        'api_key'  => 'coingecko-secret-value-that-must-not-return',
                    ],
                    'upstash' => [
                        'status'     => 'enabled',
                        'rest_token' => 'redis-secret-value-that-must-not-return',
                    ],
                    'telegram' => [
                        'bot_username' => 'tonbankcard_admin_bot',
                        'bot_token'    => 'telegram-secret-value-that-must-not-return',
                    ],
                    'changenow' => [
                        'link_id' => '3cc0024a18fd9d',
                    ],
                ],
            ]
        ),
    ],
    $runtime,
    $api
);
$payload = json_payload( $response );
if ( 200 !== $response['status'] || TRUE !== $payload['ok'] ) {
    fwrite( STDERR, "Admin provider write failed: {$response['body']}\n" );
    exit( 1 );
}
$body = $response['body'];
foreach ( [ 'groq-secret-value-that-must-not-return', 'coingecko-secret-value-that-must-not-return', 'redis-secret-value-that-must-not-return' ] as $secret ) {
    if ( FALSE !== strpos( $body, $secret ) ) {
        fwrite( STDERR, "Admin provider response leaked a submitted secret\n" );
        exit( 1 );
    }
}
if ( empty( $payload['data']['providers']['groq']['api_key']['configured'] ) || '[redacted]' !== $payload['data']['providers']['groq']['api_key']['display_value'] ) {
    fwrite( STDERR, "Groq secret metadata was not returned as a redacted configured value\n" );
    exit( 1 );
}
$env_after_providers = file_get_contents( $env_path );
foreach (
    [
        'COINGECKO_API_PLAN=pro',
        'COINGECKO_API_KEY=coingecko-secret-value-that-must-not-return',
        'GROQ_API_KEY=groq-secret-value-that-must-not-return',
        'GROQ_MODEL_ID=llama-3.3-70b-versatile',
        'UPSTASH_REDIS_REST_TOKEN=redis-secret-value-that-must-not-return',
        'TONBANKCARD_BOT_USERNAME=tonbankcard_admin_bot',
        'TONBANKCARD_BOT_TOKEN=telegram-secret-value-that-must-not-return',
        'CHANGENOW_LINK_ID=3cc0024a18fd9d',
    ] as $line
) {
    if ( FALSE === strpos( $env_after_providers, $line ) ) {
        fwrite( STDERR, "Admin provider save did not update .env line: {$line}\n" );
        exit( 1 );
    }
}
$reloaded_after_providers = tonbankcard_runtime_config();
if (
    'pro' !== $reloaded_after_providers['providers']['coingecko']['api_plan'] ||
    'coingecko-secret-value-that-must-not-return' !== $reloaded_after_providers['providers']['coingecko']['api_key'] ||
    'groq-secret-value-that-must-not-return' !== $reloaded_after_providers['providers']['groq']['api_key'] ||
    'llama-3.3-70b-versatile' !== $reloaded_after_providers['providers']['groq']['model_id'] ||
    'telegram-secret-value-that-must-not-return' !== $reloaded_after_providers['telegram']['bot_token'] ||
    '3cc0024a18fd9d' !== $reloaded_after_providers['providers']['changenow']['link_id']
) {
    fwrite( STDERR, "Runtime config did not reload provider values saved through admin .env persistence\n" );
    exit( 1 );
}
if ( 'old-link' === $reloaded_after_providers['providers']['changenow']['link_id'] ) {
    fwrite( STDERR, "Admin ChangeNOW link_id did not override the previous static/env value\n" );
    exit( 1 );
}

$response = call_admin_api(
    [
        'method'  => 'PUT',
        'path'    => '/api/admin/content',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'admin-content-write',
            'authorization' => 'Bearer owner-secret-token',
        ],
        'body'    => json_encode(
            [
                'content' => [
                    'legal_copy' => [
                        'market_data_disclaimer' => 'Market data is informational only.',
                    ],
                    'ton_assets' => [
                        [
                            'id'                 => 'admin-ton-alpha',
                            'name'               => 'Admin TON Alpha',
                            'symbol'             => 'ATA',
                            'category'           => 'jetton',
                            'verification_state' => 'curated',
                            'tags'               => [ 'ton_ecosystem', 'jetton' ],
                        ],
                    ],
                ],
            ]
        ),
    ],
    $runtime,
    $api
);
if ( 200 !== $response['status'] ) {
    fwrite( STDERR, "Admin content write failed: {$response['body']}\n" );
    exit( 1 );
}
$ton_state = tonbankcard_api_ton_load_state( $runtime, $api );
$ton_asset_ids = array_map(
    function ( $asset ) {
        return isset( $asset['id'] ) ? $asset['id'] : '';
    },
    isset( $ton_state['assets'] ) && is_array( $ton_state['assets'] ) ? $ton_state['assets'] : []
);
if ( ! in_array( 'admin-ton-alpha', $ton_asset_ids, TRUE ) ) {
    fwrite( STDERR, "Admin-managed TON assets were not merged into the TON API read model\n" );
    exit( 1 );
}
if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $value ) {
        return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
    }
}
if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $value ) {
        return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
    }
}
if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $value ) {
        return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
    }
}
if ( ! function_exists( 'vendor_url' ) ) {
    function vendor_url( $path ) {
        return '/vendor/' . ltrim( (string) $path, '/' );
    }
}
if ( ! function_exists( 'get_file_url_for_display' ) ) {
    function get_file_url_for_display( $path ) {
        return '/' . ltrim( (string) $path, '/' );
    }
}
if ( ! function_exists( '__' ) ) {
    function __( string $text ) {
        return $text;
    }
}
ob_start();
include GECKO_CLIENT_TEMPLATES_DIR . '/components/disclaimer-message.php';
$disclaimer_html = ob_get_clean();
if ( FALSE === strpos( $disclaimer_html, 'Market data is informational only.' ) ) {
    fwrite( STDERR, "Admin legal copy was not applied to the disclaimer component\n" );
    exit( 1 );
}

$response = call_admin_api(
    [
        'method'  => 'POST',
        'path'    => '/api/admin/cache/purge',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'admin-cache-purge',
            'authorization' => 'Bearer owner-secret-token',
        ],
        'body'    => json_encode( [ 'scope' => 'market:ton' ] ),
    ],
    $runtime,
    $api
);
if ( 202 !== $response['status'] ) {
    fwrite( STDERR, "Admin cache purge request failed: {$response['body']}\n" );
    exit( 1 );
}

$response = call_admin_api(
    [
        'method'  => 'PUT',
        'path'    => '/api/admin/operations',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'admin-yandex-metrica-write',
            'authorization' => 'Bearer owner-secret-token',
        ],
        'body'    => json_encode(
            [
                'analytics' => [
                    'yandex_metrica' => [
                        'enabled'    => TRUE,
                        'counter_id' => '109107032<script>',
                    ],
                ],
            ]
        ),
    ],
    $runtime,
    $api
);
$payload = json_payload( $response );
if ( 200 !== $response['status'] || '109107032' !== $payload['data']['analytics']['yandex_metrica']['counter_id'] || TRUE !== $payload['data']['analytics']['yandex_metrica']['enabled'] ) {
    fwrite( STDERR, "Admin Yandex Metrica settings were not saved as a sanitized numeric counter\n" );
    exit( 1 );
}
$env_after_metrica = file_get_contents( $env_path );
foreach ( [ 'YANDEX_METRICA_COUNTER_ID=109107032', 'YANDEX_METRICA_ENABLED=true' ] as $line ) {
    if ( FALSE === strpos( $env_after_metrica, $line ) ) {
        fwrite( STDERR, "Admin Yandex Metrica save did not update .env line: {$line}\n" );
        exit( 1 );
    }
}

$GLOBALS['runtime_config'] = tonbankcard_runtime_config();
$head_constants = [
    'ROBOTO_VERSION' => 'test',
    'MDI_VERSION' => 'test',
    'VUE_VERSION' => 'test',
    'VUE_ROUTER_VERSION' => 'test',
    'VUETIFY_VERSION' => 'test',
];
foreach ( $head_constants as $constant => $value ) {
    if ( ! defined( $constant ) ) {
        define( $constant, $value );
    }
}
$site = [
    'lang' => 'en',
    'name' => 'TONBANKCARD',
    'title' => 'Crypto Tracker',
    'description' => 'Market data',
    'theme_color' => '#111111',
];
if ( ! function_exists( 'tonbankcard_public_route_meta' ) ) {
    function tonbankcard_public_route_meta() {
        return [
            'robots' => 'index,follow',
            'canonical_url' => 'https://example.test/',
            'og_type' => 'website',
            'full_title' => 'TONBANKCARD',
            'description' => 'Market data',
            'image' => '',
            'image_width' => 1200,
            'image_height' => 630,
            'image_alt' => 'TONBANKCARD',
        ];
    }
}
if ( ! function_exists( 'tonbankcard_public_linked_data' ) ) {
    function tonbankcard_public_linked_data( array $meta ) {
        return [ '@type' => 'WebSite', 'name' => $meta['full_title'] ];
    }
}
$_SERVER['REQUEST_URI'] = '/currencies';
ob_start();
include GECKO_CLIENT_VIEWS_DIR . '/app-head.php';
$public_head = ob_get_clean();
if ( FALSE === strpos( $public_head, 'mc.yandex.ru/metrika/tag.js?id=109107032' ) || FALSE === strpos( $public_head, 'ym(109107032' ) ) {
    fwrite( STDERR, "Public pages did not render the configured Yandex Metrica counter\n" );
    exit( 1 );
}
$_SERVER['REQUEST_URI'] = '/admin';
ob_start();
include GECKO_CLIENT_VIEWS_DIR . '/app-head.php';
$admin_head = ob_get_clean();
if ( FALSE !== strpos( $admin_head, 'mc.yandex.ru/metrika/tag.js' ) || FALSE !== strpos( $admin_head, 'ym(109107032' ) ) {
    fwrite( STDERR, "Admin pages unexpectedly rendered the Yandex Metrica counter\n" );
    exit( 1 );
}

$response = call_admin_api(
    [
        'method'  => 'GET',
        'path'    => '/api/admin/audit-log',
        'headers' => [
            'x-request-id' => 'admin-audit-read',
            'authorization' => 'Bearer owner-secret-token',
        ],
    ],
    $runtime,
    $api
);
$payload = json_payload( $response );
if ( 200 !== $response['status'] || count( $payload['data']['audit_log'] ) < 4 ) {
    fwrite( STDERR, "Admin audit log did not record writes\n" );
    exit( 1 );
}
$audit = json_encode( $payload['data']['audit_log'] );
if ( FALSE === strpos( $audit, 'owner' ) || FALSE === strpos( $audit, 'feature_flags.updated' ) || FALSE === strpos( $audit, 'providers.updated' ) || FALSE === strpos( $audit, 'cache.purge_requested' ) ) {
    fwrite( STDERR, "Admin audit log is missing actor, timestamp, or expected actions\n" );
    exit( 1 );
}
foreach ( [ 'groq-secret-value-that-must-not-return', 'coingecko-secret-value-that-must-not-return', 'redis-secret-value-that-must-not-return', 'owner-secret-token' ] as $secret ) {
    if ( FALSE !== strpos( $audit, $secret ) ) {
        fwrite( STDERR, "Admin audit log leaked a submitted secret or token\n" );
        exit( 1 );
    }
}

$stored = json_decode( (string) file_get_contents( $store_path ), TRUE );
if ( ! is_array( $stored ) || empty( $stored['audit_log'][0]['created_at'] ) || empty( $stored['audit_log'][0]['actor']['id'] ) ) {
    fwrite( STDERR, "Admin store did not persist audit entries with actor and timestamp\n" );
    exit( 1 );
}
foreach ( [ 'groq-secret-value-that-must-not-return', 'coingecko-secret-value-that-must-not-return', 'redis-secret-value-that-must-not-return' ] as $secret ) {
    if ( FALSE !== strpos( json_encode( $stored ), $secret ) ) {
        fwrite( STDERR, "Admin store persisted a raw secret value\n" );
        exit( 1 );
    }
}

@unlink( $store_path );
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Admin panel check passed.'
