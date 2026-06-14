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
assert_contains "$doc" '/api/admin/mini-app' 'Mini App setup admin endpoint'
assert_contains "$doc" '/api/admin/mini-app/telegram-setup' 'automatic Telegram Mini App setup endpoint'
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
assert_contains api/router.php '/api/admin/mini-app' 'the Mini App setup API route index entry'
assert_contains api/router.php 'tonbankcard_api_admin_handle' 'admin route dispatch'
assert_contains config/runtime.php 'TONBANKCARD_ADMIN_STORE' 'runtime admin store integration'
assert_contains config/routes.php "'admin'" 'admin route registration'
assert_contains config/routes.php "'admin-mini-app'" 'Mini App setup admin route registration'
assert_contains config/routes-v2.php "'admin'" 'admin route metadata'
assert_contains config/routes-v2.php "'admin-mini-app'" 'Mini App setup admin route metadata'
assert_contains dev/js/source.json '"routes/admin.js"' 'admin route source bundle entry'
assert_contains dev/js/src/routes/admin.js 'admin-mini-app' 'the Mini App setup client route'
assert_contains dev/js/src/routes/admin.js 'setupMiniAppTelegram' 'automatic Telegram setup client action'
assert_contains templates/routes/admin.php 'admin-secret-input' 'write-only secret field markup'
assert_contains templates/routes/admin.php 'Mini App Setup' 'the Mini App setup tab'
assert_contains templates/routes/admin.php 'Check Bot' 'the Telegram bot check button'
assert_contains templates/routes/admin.php 'Register Webhook' 'the Mini App webhook registration control'
assert_contains templates/routes/admin.php 'Launch URL' 'the Mini App launch URL output'
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

php_check 'Admin persistence should write JSON before mutating .env and lock .env read-modify-write' \
    php <<'PHP'
<?php
$source = file_get_contents( 'api/admin.php' );
$json_write = strpos( $source, 'file_put_contents( $tmp, $json' );
$env_save = strpos( $source, 'tonbankcard_api_admin_save_env_updates( $env_updates )' );
$env_lock = strpos( $source, 'flock( $lock, LOCK_EX )' );
if ( FALSE === $json_write || FALSE === $env_save || ! ( $json_write < $env_save ) ) {
    fwrite( STDERR, "Admin state should persist the JSON store before applying .env updates\n" );
    exit( 1 );
}
if ( FALSE === $env_lock ) {
    fwrite( STDERR, "Admin .env persistence should lock the whole read-modify-write sequence\n" );
    exit( 1 );
}
PHP

php_check 'Admin API should require admin auth, enforce read-only support, audit writes, merge runtime flags, and redact secrets' \
    env -i PATH="$PATH" \
        TONBANKCARD_ADMIN_STORE="$(mktemp -d "${TMPDIR:-/tmp}/tonbankcard-admin-store.XXXXXX")/admin.json" \
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
register_shutdown_function(
    function () use ( $store_path ) {
        @unlink( $store_path );
        @rmdir( dirname( $store_path ) );
    }
);

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
                    'tonapi' => [
                        'api_key' => 'tonapi-secret-value-that-must-not-return',
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
foreach ( [ 'groq-secret-value-that-must-not-return', 'coingecko-secret-value-that-must-not-return', 'redis-secret-value-that-must-not-return', 'tonapi-secret-value-that-must-not-return' ] as $secret ) {
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
        'TONAPI_API_KEY=tonapi-secret-value-that-must-not-return',
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
    'tonapi-secret-value-that-must-not-return' !== $reloaded_after_providers['providers']['tonapi']['api_key'] ||
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
if ( ! function_exists( 'tonbankcard_csp_nonce' ) ) {
    function tonbankcard_csp_nonce() {
        return str_repeat( 'a', 32 );
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
if ( ! function_exists( 'tonbankcard_seo_hreflang_alternates' ) ) {
    function tonbankcard_seo_hreflang_alternates( $canonical_url ) {
        return [
            [ 'hreflang' => 'en', 'href' => $canonical_url ],
            [ 'hreflang' => 'x-default', 'href' => $canonical_url ],
        ];
    }
}
$_SERVER['REQUEST_URI'] = '/currencies';
ob_start();
include GECKO_CLIENT_VIEWS_DIR . '/app-head.php';
$public_head = ob_get_clean();
if ( FALSE === strpos( $public_head, 'mc.yandex.ru/metrika/tag.js' ) || FALSE === strpos( $public_head, 'window.ym(109107032' ) ) {
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

$telegram_calls = [];
$api_with_telegram_setup = $api;
$api_with_telegram_setup['telegram_bot']['transport'] = function ( $method, array $payload ) use ( &$telegram_calls ) {
    $telegram_calls[] = [
        'method'  => $method,
        'payload' => $payload,
    ];

    if ( 'getMe' === $method ) {
        return [
            'ok'     => TRUE,
            'result' => [
                'id'         => 987654321,
                'is_bot'     => TRUE,
                'first_name' => 'TONBANKCARD Admin',
                'username'   => 'autochecked_bot',
            ],
        ];
    }

    if ( 'getWebhookInfo' === $method ) {
        return [
            'ok'     => TRUE,
            'result' => [
                'url'                  => 'https://miniapp.example.com/api/telegram/bot',
                'pending_update_count' => 0,
                'allowed_updates'      => [ 'message', 'pre_checkout_query', 'inline_query' ],
            ],
        ];
    }

    return [
        'ok'          => TRUE,
        'result'      => TRUE,
        'description' => 'ok',
    ];
};
$response = call_admin_api(
    [
        'method'  => 'POST',
        'path'    => '/api/admin/mini-app/telegram-setup',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'admin-mini-app-telegram-setup',
            'authorization' => 'Bearer owner-secret-token',
        ],
        'body'    => json_encode(
            [
                'mini_app' => [
                    'profile'           => 'telegram',
                    'public_base_url'   => 'https://marketcap.example.com/',
                    'telegram_base_url' => 'https://miniapp.example.com/',
                    'bot_token'         => '123456:auto-check-bot-token-secret',
                    'feature_alerts'    => TRUE,
                    'feature_premium'   => TRUE,
                ],
            ]
        ),
    ],
    $runtime,
    $api_with_telegram_setup
);
$payload = json_payload( $response );
if ( 200 !== $response['status'] || TRUE !== $payload['ok'] ) {
    fwrite( STDERR, "Automatic Telegram Mini App setup failed: {$response['body']}\n" );
    exit( 1 );
}
if (
    'autochecked_bot' !== $payload['data']['mini_app']['telegram']['bot_username'] ||
    empty( $payload['data']['mini_app']['telegram']['bot_token']['configured'] ) ||
    empty( $payload['data']['mini_app']['telegram']['webhook_secret']['configured'] ) ||
    'configured' !== $payload['data']['mini_app']['telegram_setup']['status']
) {
    fwrite( STDERR, "Automatic Telegram setup did not save checked bot metadata and setup status\n" );
    exit( 1 );
}
$telegram_methods = array_map(
    function ( $call ) {
        return $call['method'];
    },
    $telegram_calls
);
$expected_telegram_methods = [ 'getMe', 'setWebhook', 'setMyCommands', 'setChatMenuButton', 'getWebhookInfo' ];
if ( $expected_telegram_methods !== $telegram_methods ) {
    fwrite( STDERR, 'Automatic Telegram setup called unexpected methods: ' . implode( ', ', $telegram_methods ) . "\n" );
    exit( 1 );
}
$set_webhook_payload = $telegram_calls[1]['payload'];
if (
    'https://miniapp.example.com/api/telegram/bot' !== $set_webhook_payload['url'] ||
    ! preg_match( '/^[A-Za-z0-9_-]{32,}$/', $set_webhook_payload['secret_token'] ) ||
    ! in_array( 'message', $set_webhook_payload['allowed_updates'], TRUE ) ||
    ! in_array( 'pre_checkout_query', $set_webhook_payload['allowed_updates'], TRUE ) ||
    ! in_array( 'inline_query', $set_webhook_payload['allowed_updates'], TRUE )
) {
    fwrite( STDERR, "Automatic Telegram setup did not register the expected webhook payload\n" );
    exit( 1 );
}
if (
    empty( $telegram_calls[2]['payload']['commands'][0]['command'] ) ||
    'start' !== $telegram_calls[2]['payload']['commands'][0]['command'] ||
    'web_app' !== $telegram_calls[3]['payload']['menu_button']['type'] ||
    'https://miniapp.example.com/' !== $telegram_calls[3]['payload']['menu_button']['web_app']['url']
) {
    fwrite( STDERR, "Automatic Telegram setup did not register bot commands and Mini App menu button\n" );
    exit( 1 );
}
if (
    FALSE !== strpos( $response['body'], '123456:auto-check-bot-token-secret' ) ||
    FALSE !== strpos( $response['body'], $set_webhook_payload['secret_token'] )
) {
    fwrite( STDERR, "Automatic Telegram setup response leaked bot token or webhook secret\n" );
    exit( 1 );
}
$env_after_telegram_setup = file_get_contents( $env_path );
foreach (
    [
        'TONBANKCARD_BOT_USERNAME=autochecked_bot',
        'TONBANKCARD_BOT_TOKEN=123456:auto-check-bot-token-secret',
        'TONBANKCARD_TELEGRAM_BASE_URL=https://miniapp.example.com/',
    ] as $line
) {
    if ( FALSE === strpos( $env_after_telegram_setup, $line ) ) {
        fwrite( STDERR, "Automatic Telegram setup did not update .env line: {$line}\n" );
        exit( 1 );
    }
}
if ( FALSE === strpos( $env_after_telegram_setup, 'TONBANKCARD_BOT_WEBHOOK_SECRET=' . $set_webhook_payload['secret_token'] ) ) {
    fwrite( STDERR, "Automatic Telegram setup did not persist the generated webhook secret\n" );
    exit( 1 );
}

$response = call_admin_api(
    [
        'method'  => 'PUT',
        'path'    => '/api/admin/mini-app',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'admin-mini-app-write',
            'authorization' => 'Bearer owner-secret-token',
        ],
        'body'    => json_encode(
            [
                'mini_app' => [
                    'profile'                       => 'telegram',
                    'public_base_url'               => 'https://marketcap.example.com/',
                    'telegram_base_url'             => 'https://miniapp.example.com/',
                    'bot_username'                  => '@tonbankcard_admin_bot',
                    'bot_token'                     => '123456:mini-app-bot-token-secret',
                    'webhook_secret'                => 'mini-app-webhook-secret-value',
                    'alert_worker_token'            => 'mini-app-alert-worker-secret-value',
                    'feature_alerts'                => TRUE,
                    'feature_premium'               => TRUE,
                    'feature_referrals'             => TRUE,
                    'premium_plan_code'             => 'premium_monthly',
                    'premium_monthly_stars'         => 299,
                    'premium_subscription_period'   => 2592000,
                    'premium_signing_secret'        => 'mini-app-premium-signing-secret-value',
                ],
            ]
        ),
    ],
    $runtime,
    $api
);
$payload = json_payload( $response );
if ( 200 !== $response['status'] || TRUE !== $payload['ok'] ) {
    fwrite( STDERR, "Admin Mini App setup write failed: {$response['body']}\n" );
    exit( 1 );
}
if (
    'telegram' !== $payload['data']['mini_app']['runtime']['profile'] ||
    'https://marketcap.example.com/' !== $payload['data']['mini_app']['runtime']['public_base_url'] ||
    'https://miniapp.example.com/' !== $payload['data']['mini_app']['runtime']['telegram_base_url'] ||
    'tonbankcard_admin_bot' !== $payload['data']['mini_app']['telegram']['bot_username']
) {
    fwrite( STDERR, "Admin Mini App setup did not normalize runtime URLs and bot username\n" );
    exit( 1 );
}
if (
    empty( $payload['data']['mini_app']['telegram']['bot_token']['configured'] ) ||
    empty( $payload['data']['mini_app']['telegram']['webhook_secret']['configured'] ) ||
    empty( $payload['data']['mini_app']['alerts']['worker_token']['configured'] ) ||
    empty( $payload['data']['mini_app']['premium']['signing_secret']['configured'] )
) {
    fwrite( STDERR, "Admin Mini App setup did not return redacted configured secret metadata\n" );
    exit( 1 );
}
if (
    'https://t.me/tonbankcard_admin_bot?startapp' !== $payload['data']['mini_app']['launch']['telegram_launch_url'] ||
    'https://miniapp.example.com/api/telegram/bot' !== $payload['data']['mini_app']['launch']['webhook_url'] ||
    FALSE === strpos( $payload['data']['mini_app']['launch']['set_webhook_command'], 'setWebhook' )
) {
    fwrite( STDERR, "Admin Mini App setup did not build launch and webhook helpers\n" );
    exit( 1 );
}
$mini_app_body = $response['body'];
foreach ( [ '123456:mini-app-bot-token-secret', 'mini-app-webhook-secret-value', 'mini-app-alert-worker-secret-value', 'mini-app-premium-signing-secret-value' ] as $secret ) {
    if ( FALSE !== strpos( $mini_app_body, $secret ) ) {
        fwrite( STDERR, "Admin Mini App setup response leaked a submitted secret\n" );
        exit( 1 );
    }
}
$env_after_mini_app = file_get_contents( $env_path );
foreach (
    [
        'TONBANKCARD_PROFILE=telegram',
        'TONBANKCARD_PUBLIC_BASE_URL=https://marketcap.example.com/',
        'TONBANKCARD_TELEGRAM_BASE_URL=https://miniapp.example.com/',
        'TONBANKCARD_BOT_USERNAME=tonbankcard_admin_bot',
        'TONBANKCARD_BOT_TOKEN=123456:mini-app-bot-token-secret',
        'TONBANKCARD_BOT_WEBHOOK_SECRET=mini-app-webhook-secret-value',
        'TONBANKCARD_ALERT_WORKER_TOKEN=mini-app-alert-worker-secret-value',
        'TONBANKCARD_FEATURE_ALERTS=true',
        'TONBANKCARD_FEATURE_PREMIUM=true',
        'TONBANKCARD_FEATURE_REFERRALS=true',
        'TONBANKCARD_PREMIUM_PLAN_CODE=premium_monthly',
        'TONBANKCARD_PREMIUM_MONTHLY_STARS=299',
        'TONBANKCARD_PREMIUM_SUBSCRIPTION_PERIOD_SECONDS=2592000',
        'TONBANKCARD_PREMIUM_SIGNING_SECRET=mini-app-premium-signing-secret-value',
    ] as $line
) {
    if ( FALSE === strpos( $env_after_mini_app, $line ) ) {
        fwrite( STDERR, "Admin Mini App setup did not update .env line: {$line}\n" );
        exit( 1 );
    }
}
$reloaded_after_mini_app = tonbankcard_runtime_config();
if (
    'telegram' !== $reloaded_after_mini_app['profile'] ||
    'https://marketcap.example.com/' !== $reloaded_after_mini_app['urls']['public'] ||
    'https://miniapp.example.com/' !== $reloaded_after_mini_app['urls']['telegram'] ||
    'tonbankcard_admin_bot' !== $reloaded_after_mini_app['telegram']['bot_username'] ||
    '123456:mini-app-bot-token-secret' !== $reloaded_after_mini_app['telegram']['bot_token'] ||
    'mini-app-webhook-secret-value' !== $reloaded_after_mini_app['telegram']['webhook_secret'] ||
    TRUE !== $reloaded_after_mini_app['feature_flags']['alerts'] ||
    TRUE !== $reloaded_after_mini_app['feature_flags']['premium'] ||
    TRUE !== $reloaded_after_mini_app['feature_flags']['referrals']
) {
    fwrite( STDERR, "Runtime config did not reload Mini App setup values saved through admin .env persistence\n" );
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
if ( 200 !== $response['status'] || count( $payload['data']['audit_log'] ) < 5 ) {
    fwrite( STDERR, "Admin audit log did not record writes\n" );
    exit( 1 );
}
$audit = json_encode( $payload['data']['audit_log'] );
if ( FALSE === strpos( $audit, 'owner' ) || FALSE === strpos( $audit, 'feature_flags.updated' ) || FALSE === strpos( $audit, 'providers.updated' ) || FALSE === strpos( $audit, 'mini_app.updated' ) || FALSE === strpos( $audit, 'mini_app.telegram_setup' ) || FALSE === strpos( $audit, 'cache.purge_requested' ) ) {
    fwrite( STDERR, "Admin audit log is missing actor, timestamp, or expected actions\n" );
    exit( 1 );
}
foreach ( [ 'groq-secret-value-that-must-not-return', 'coingecko-secret-value-that-must-not-return', 'redis-secret-value-that-must-not-return', '123456:auto-check-bot-token-secret', $set_webhook_payload['secret_token'], '123456:mini-app-bot-token-secret', 'mini-app-webhook-secret-value', 'mini-app-alert-worker-secret-value', 'mini-app-premium-signing-secret-value', 'owner-secret-token' ] as $secret ) {
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
foreach ( [ 'groq-secret-value-that-must-not-return', 'coingecko-secret-value-that-must-not-return', 'redis-secret-value-that-must-not-return', '123456:auto-check-bot-token-secret', $set_webhook_payload['secret_token'], '123456:mini-app-bot-token-secret', 'mini-app-webhook-secret-value', 'mini-app-alert-worker-secret-value', 'mini-app-premium-signing-secret-value' ] as $secret ) {
    if ( FALSE !== strpos( json_encode( $stored ), $secret ) ) {
        fwrite( STDERR, "Admin store persisted a raw secret value\n" );
        exit( 1 );
    }
}

@unlink( $store_path );
PHP

php_check 'Admin .env persistence should escape newlines without creating extra variables' \
    env -i PATH="$PATH" \
        TEST_ENV_FILE="$PWD/.env" \
        php <<'PHP'
<?php
$env_path = (string) getenv( 'TEST_ENV_FILE' );
$previous_env = is_file( $env_path ) ? file_get_contents( $env_path ) : null;
file_put_contents( $env_path, "GROQ_MODEL_ID=old-model\n" );
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

putenv( 'TONBANKCARD_ADMIN_TOKEN' );
unset( $_ENV['TONBANKCARD_ADMIN_TOKEN'], $_SERVER['TONBANKCARD_ADMIN_TOKEN'] );

$malicious = "llama\nTONBANKCARD_ADMIN_TOKEN=attacker-token";
$saved = tonbankcard_api_admin_save_env_updates( [ 'GROQ_MODEL_ID' => $malicious ] );
if ( empty( $saved['ok'] ) ) {
    fwrite( STDERR, "Admin env update failed unexpectedly\n" );
    exit( 1 );
}

$env = (string) file_get_contents( $env_path );
if ( preg_match( '/^TONBANKCARD_ADMIN_TOKEN=attacker-token$/m', $env ) ) {
    fwrite( STDERR, "Admin env update created a new variable through newline injection\n" );
    exit( 1 );
}
if ( FALSE === strpos( $env, 'GROQ_MODEL_ID="llama\\nTONBANKCARD_ADMIN_TOKEN=attacker-token"' ) ) {
    fwrite( STDERR, "Admin env update did not persist a newline-escaped value\n" );
    exit( 1 );
}
if ( FALSE !== strpos( (string) getenv( 'GROQ_MODEL_ID' ), "\n" ) ) {
    fwrite( STDERR, "Admin env update left a raw newline in the live environment value\n" );
    exit( 1 );
}

tonbankcard_load_env_file( $env_path );
if ( 'attacker-token' === getenv( 'TONBANKCARD_ADMIN_TOKEN' ) ) {
    fwrite( STDERR, "Reloading .env materialized an injected admin token\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Admin panel check passed.'
