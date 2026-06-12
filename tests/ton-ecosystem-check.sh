#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-ton-ecosystem-curation.md

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

php_check() {
    description=$1
    shift

    if ! "$@"; then
        fail "$description"
    fi
}

assert_file api/ton.php
assert_file "$doc"
assert_file database/migrations/0005_ton_ecosystem_curation.up.sql
assert_file database/migrations/0005_ton_ecosystem_curation.down.sql
assert_file dev/js/src/routes/screener.js
assert_file dev/js/src/routes/ton-asset.js
assert_file templates/routes/ton-asset.php

assert_contains "$doc" '^# TONBANKCARD V2 TON Ecosystem Curation$' 'the TON ecosystem curation title'
assert_contains "$doc" 'Issue: \[#29\]' 'the issue reference'
assert_contains "$doc" '/api/ton/assets' 'the TON curation API endpoint'
assert_contains "$doc" 'TONBANKCARD_TON_CURATION_FILE' 'the writable curation file configuration'
assert_contains "$doc" 'verified' 'verified asset state'
assert_contains "$doc" 'unverified' 'unverified asset state'
assert_contains "$doc" 'without code deployment' 'the manual curation workflow'
assert_contains README.md 'docs/v2-ton-ecosystem-curation\.md' 'the TON ecosystem documentation link'
assert_contains .env.example '^TONBANKCARD_TON_CURATION_FILE=' 'the TON curation file environment variable'
assert_contains .env.example '^TONBANKCARD_TON_CURATION_TOKEN=' 'the TON curation token environment variable'
assert_contains package.json '"test:ton-ecosystem"' 'the TON ecosystem npm script'
assert_contains package.json 'test:ton-ecosystem' 'the aggregate TON ecosystem check'
assert_contains api/router.php '/api/ton/assets' 'the API route index entry'
assert_contains api/search.php 'tonbankcard_api_ton_search_entries' 'TON curated entries in smart search'
assert_contains dev/js/source.json '"routes/screener.js"' 'the screener route source bundle entry'
assert_contains dev/js/src/routes/ton.js '/api/ton/assets' 'the TON route curation endpoint'
assert_contains dev/js/src/routes/markets.js 'activeTonTag' 'the market table TON tag filter'
assert_contains templates/routes/markets.php 'ton-filter-chip' 'TON filter chips on market tables'
assert_contains templates/routes/screener.php 'ton-screener-filter-chip' 'TON filter chips on screener'
assert_contains templates/routes/ton.php 'ton-verification-chip' 'TON verification visual indicator'
assert_contains templates/routes/ton.php 'ton-admin-add-btn' 'TON catalog inline add control for administrators'
assert_contains templates/routes/ton.php 'ton-admin-edit-btn' 'TON catalog inline edit control for administrators'
assert_contains templates/routes/ton.php 'ton-admin-delete-btn' 'TON catalog inline remove control for administrators'
assert_contains templates/routes/ton.php 'ton-asset-editor' 'TON catalog inline editor dialog'
assert_contains templates/routes/ton-asset.php 'ton-asset-detail' 'TON per-asset catalog page'
assert_contains config/routes.php "'ton-asset'" 'TON per-asset Vue route registration'
assert_contains dev/js/source.json '"routes/ton-asset.js"' 'TON per-asset bundle source entry'
assert_contains dev/js/src/routes/ton-asset.js "name: 'ton-asset'" 'TON per-asset Vue Router definition'
assert_contains dev/js/src/routes/ton.js 'TONBANKCARD:adminToken' 'TON catalog admin token discovery for inline editing'
assert_contains dev/js/src/routes/ton.js 'canEditCuration' 'TON catalog admin write permission gate'
assert_contains dev/js/src/routes/ton.js "name: 'ton-asset'" 'TON catalog asset route navigation'
assert_contains api/ton.php "'ton-asset'" 'TON API exposes the per-asset Vue route name'
assert_contains api/ton.php 'tonbankcard_api_ton_link_type' 'TON API normalizer accepts a link_type field'
assert_contains api/ton.php "'project_category'" 'TON API normalizer exposes the project_category field'
assert_contains assets/css/style.css 'ton-asset-unverified' 'distinct unverified TON asset styling'
assert_contains templates/routes/currencies.php "tonApiBaseUrl" 'Market Pulse exposes the TON curation endpoint to the frontend'
assert_contains dev/js/src/routes/currencies.js 'fetchTonCuration' 'Market Pulse fetches the curated TON ecosystem list'
assert_contains dev/js/src/routes/currencies.js 'tonAssetCoinIds' 'Market Pulse derives TON coin ids from the curation feed'
assert_contains dev/js/src/routes/ton.js "link_type === 'currency'" 'TON catalog routes currency-linked assets to the cryptocurrency page'
assert_contains dev/js/src/routes/ton.js "link_type === 'project'" 'TON catalog routes project-linked assets to the catalog page'
assert_contains dev/js/src/routes/ton.js 'project_category' 'TON catalog editor preserves project_category metadata'
assert_contains dev/js/src/routes/ton-asset.js 'project_category' 'TON per-asset editor preserves project_category metadata'
assert_contains dev/js/src/routes/ton.js 'marketForAsset' 'TON catalog falls back to symbol-based market lookup'
assert_contains templates/routes/ton.php 'tonLinkTypeOptions' 'TON catalog editor exposes the link type selector'
assert_contains templates/routes/ton.php 'tonProjectCategoryOptions' 'TON catalog editor exposes the project category selector'
assert_contains templates/routes/ton-asset.php 'tonLinkTypeOptions' 'TON per-asset editor exposes the link type selector'
assert_contains templates/routes/ton-asset.php 'tonProjectCategoryOptions' 'TON per-asset editor exposes the project category selector'
assert_contains dev/js/src/routes/ton.js "tonEcosystemCategoryId = 'ton-ecosystem'" 'TON catalog references the CoinGecko ton-ecosystem category id'
assert_contains dev/js/src/routes/ton.js 'fetchTonEcosystemCategory' 'TON catalog auto-discovers CoinGecko ton-ecosystem coins'
assert_contains dev/js/src/routes/ton.js 'synthesizeAssetFromMarket' 'TON catalog synthesizes assets from auto-discovered markets'
assert_contains dev/js/src/routes/ton.js 'recomputeTonAssets' 'TON catalog merges curated and discovered assets without duplicates'
assert_contains assets/js/app.js 'fetchTonEcosystemCategory' 'TON catalog auto-discovery is shipped in the generated bundle'

# Issue #113: server-side exclusion list exposed to client for discovered-asset filtering
assert_contains dev/js/src/routes/ton.js 'excludedAssetIds' 'TON catalog stores excluded asset ids from API response'
assert_contains dev/js/src/routes/ton.js 'excludedCoinIds' 'TON catalog stores excluded coin ids from API response'
assert_contains api/ton.php 'excluded_coin_ids' 'TON API exposes excluded_coin_ids derived from excluded asset ids'

# Issue #113: duplicate guard in editor save
assert_contains dev/js/src/routes/ton.js 'duplicateByCoinId' 'TON catalog editor blocks duplicate coin_id on save'

# Issue #113: TON blockchain jetton lookup via TON API
assert_contains api/ton.php 'tonbankcard_api_ton_lookup_jetton' 'TON API exposes the jetton lookup handler'
assert_contains api/ton.php "'/api/ton/lookup'" 'TON API registers the /api/ton/lookup route'
assert_contains dev/js/src/routes/ton.js 'lookupTonJetton' 'TON catalog editor exposes jetton lookup from TON blockchain'
assert_contains templates/routes/ton.php 'editorLookupContract' 'TON catalog editor template exposes the contract address lookup field'
assert_contains assets/js/app.js 'lookupTonJetton' 'TON jetton lookup is shipped in the generated bundle'

# Issue #113: priority field for top-10 asset sorting
assert_contains dev/js/src/routes/ton.js 'priority' 'TON catalog editor supports the priority field'
assert_contains templates/routes/ton.php 'priority' 'TON catalog editor template exposes the priority field'
assert_contains templates/routes/admin.php 'moveTonAssetUp' 'Admin panel TON assets section exposes move-up reorder control'
assert_contains dev/js/src/routes/admin.js 'moveTonAssetUp' 'Admin JS exposes moveTonAssetUp method for TON asset reordering'
assert_contains dev/js/src/routes/admin.js 'moveTonAssetDown' 'Admin JS exposes moveTonAssetDown method for TON asset reordering'

php_check 'TON curation API should expose defaults, persist manual curation, and preserve verification states' \
    env -i PATH="$PATH" \
        TONBANKCARD_TON_CURATION_FILE="$(mktemp -d "${TMPDIR:-/tmp}/tonbankcard-ton-curation.XXXXXX")/curation.json" \
        TONBANKCARD_TON_CURATION_TOKEN='ton-curation-secret' \
        php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$store_path = (string) getenv( 'TONBANKCARD_TON_CURATION_FILE' );
@unlink( $store_path );
register_shutdown_function(
    function () use ( $store_path ) {
        @unlink( $store_path );
        @rmdir( dirname( $store_path ) );
    }
);

$request = [
    'method'  => 'GET',
    'path'    => '/api/ton/assets?tag=stablecoin',
    'headers' => [ 'x-request-id' => 'ton-defaults' ],
    'body'    => '',
];
$response = tonbankcard_api_handle( $request, [], $GLOBALS['runtime_config'], $api );
if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected default TON curation response 200, got ' . $response['status'] . "\n" );
    exit( 1 );
}
$payload = json_decode( $response['body'], TRUE );
if ( ! is_array( $payload ) || TRUE !== $payload['ok'] ) {
    fwrite( STDERR, "Default TON curation response is not a success envelope\n" );
    exit( 1 );
}
$assets = $payload['data']['assets'];
if ( empty( $assets ) || 'tether-usd-ton' !== $assets[0]['id'] || 'verified' !== $assets[0]['verification_state'] ) {
    fwrite( STDERR, "Stablecoin filter did not return verified USDT on TON first\n" );
    exit( 1 );
}
if ( empty( $payload['data']['categories']['stablecoin'] ) || empty( $payload['data']['lists']['featured'] ) ) {
    fwrite( STDERR, "TON curation response is missing categories or lists\n" );
    exit( 1 );
}

$manual_payload = [
    'assets' => [
        [
            'id'                 => 'community-jetton-alpha',
            'coin_id'            => 'community-alpha',
            'name'               => 'Community Jetton Alpha',
            'symbol'             => 'ALPHA',
            'category'           => 'jetton',
            'verification_state' => 'unverified',
            'tags'               => [ 'ton_ecosystem', 'jetton', 'community' ],
            'list_ids'           => [ 'community_queue' ],
            'description'        => 'Manual review queue asset.',
            'featured'           => false,
        ],
    ],
];
$response = tonbankcard_api_handle(
    [
        'method'  => 'POST',
        'path'    => '/api/ton/assets',
        'headers' => [
            'content-type' => 'application/json',
            'x-request-id' => 'ton-curation-write',
            'x-tonbankcard-ton-curation-token' => 'ton-curation-secret',
        ],
        'body'    => json_encode( $manual_payload ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);
if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected curation write 200, got ' . $response['status'] . ' body ' . $response['body'] . "\n" );
    exit( 1 );
}

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/ton/assets?state=unverified&tag=community',
        'headers' => [ 'x-request-id' => 'ton-curation-read' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);
$payload = json_decode( $response['body'], TRUE );
if ( 200 !== $response['status'] || TRUE !== $payload['ok'] || 1 !== count( $payload['data']['assets'] ) ) {
    fwrite( STDERR, "Manual curation was not persisted and filterable\n" );
    exit( 1 );
}
$asset = $payload['data']['assets'][0];
if ( 'community-jetton-alpha' !== $asset['id'] || 'unverified' !== $asset['verification_state'] || ! empty( $asset['verified'] ) ) {
    fwrite( STDERR, "Manual unverified asset state was not preserved\n" );
    exit( 1 );
}
if ( ! is_file( $store_path ) ) {
    fwrite( STDERR, "Manual curation did not write the configured store file\n" );
    exit( 1 );
}
@unlink( $store_path );
PHP

php_check 'smart search should filter by curated TON tags and expose verification metadata' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$test_api = $api;
$test_api['market_data']['transport'] = function ( $request ) {
    if ( 'coins/list' === $request['path'] || 'exchanges/list' === $request['path'] || 'coins/categories/list' === $request['path'] ) {
        return [ 'status' => 200, 'headers' => [ 'content-type' => 'application/json' ], 'body' => '[]' ];
    }
    if ( 'search/trending' === $request['path'] ) {
        return [ 'status' => 200, 'headers' => [ 'content-type' => 'application/json' ], 'body' => '{"coins":[],"exchanges":[]}' ];
    }
    fwrite( STDERR, 'Unexpected provider path: ' . $request['path'] . "\n" );
    exit( 1 );
};

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/search?q=TON&tag=stablecoin&limit=5',
        'headers' => [ 'x-request-id' => 'ton-search-tag' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $test_api
);
if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Expected tagged search 200, got ' . $response['status'] . "\n" );
    exit( 1 );
}
$payload = json_decode( $response['body'], TRUE );
$results = $payload['data']['results'];
if ( empty( $results ) || 'tether-usd-ton' !== $results[0]['id'] || 'ton_asset' !== $results[0]['type'] ) {
    fwrite( STDERR, "Tagged TON search did not rank USDT on TON first\n" );
    exit( 1 );
}
if ( 'verified' !== $results[0]['verification_state'] || empty( $results[0]['verified'] ) || empty( $results[0]['curated'] ) ) {
    fwrite( STDERR, "Tagged TON search result is missing verification metadata\n" );
    exit( 1 );
}
if ( 'stablecoin' !== $payload['data']['tag'] || 'stablecoin' !== $payload['meta']['search']['tag'] ) {
    fwrite( STDERR, "Tagged TON search response did not echo safe tag metadata\n" );
    exit( 1 );
}
PHP

php_check 'admin content writes should propagate to the public TON catalog' \
    env -i PATH="$PATH" \
        TONBANKCARD_ADMIN_TOKEN='ton-admin-secret' \
        TONBANKCARD_ADMIN_STORE="$(mktemp -d "${TMPDIR:-/tmp}/tonbankcard-ton-admin.XXXXXX")/admin.json" \
        php <<'PHP'
<?php
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

$payload = [
    'content' => [
        'ton_assets' => [
            [
                'id'                 => 'ton-portal-asset',
                'name'               => 'Portal Curated Asset',
                'symbol'             => 'PORT',
                'category'           => 'jetton',
                'verification_state' => 'curated',
                'description'        => 'Asset added through the admin panel during portal sync.',
                'tags'               => [ 'ton_ecosystem', 'jetton' ],
            ],
        ],
    ],
];

$response = tonbankcard_api_handle(
    [
        'method'  => 'PUT',
        'path'    => '/api/admin/content',
        'headers' => [
            'authorization' => 'Bearer ton-admin-secret',
            'content-type'  => 'application/json',
            'x-request-id'  => 'ton-portal-admin-write',
        ],
        'body'    => json_encode( $payload ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);
if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Admin content write failed: ' . $response['status'] . ' body ' . $response['body'] . "\n" );
    exit( 1 );
}

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/ton/assets?q=portal',
        'headers' => [ 'x-request-id' => 'ton-portal-public-read' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);
$body = json_decode( $response['body'], TRUE );
if ( 200 !== $response['status'] || empty( $body['data']['assets'] ) ) {
    fwrite( STDERR, "Admin curated TON asset is not visible on the public catalog\n" );
    exit( 1 );
}
$found = NULL;
foreach ( $body['data']['assets'] as $asset ) {
    if ( 'ton-portal-asset' === $asset['id'] ) {
        $found = $asset;
        break;
    }
}
if ( ! $found ) {
    fwrite( STDERR, "Admin curated asset id is missing from the public TON catalog\n" );
    exit( 1 );
}
if ( 'ton-asset' !== $found['route']['name'] || '/ton/asset/ton-portal-asset' !== $found['route']['path'] ) {
    fwrite( STDERR, "Admin curated asset is not routed to its dedicated catalog page\n" );
    exit( 1 );
}
@unlink( $store_path );
PHP

php_check 'admin exclusion list should suppress built-in default assets from the public catalog' \
    env -i PATH="$PATH" \
        TONBANKCARD_ADMIN_TOKEN='ton-admin-secret' \
        TONBANKCARD_ADMIN_STORE="$(mktemp -d "${TMPDIR:-/tmp}/tonbankcard-ton-admin.XXXXXX")/admin.json" \
        php <<'PHP'
<?php
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

// Exclude toncoin (built-in default) via admin content write.
$payload = [
    'content' => [
        'ton_excluded_asset_ids' => [ 'toncoin' ],
    ],
];

$response = tonbankcard_api_handle(
    [
        'method'  => 'PUT',
        'path'    => '/api/admin/content',
        'headers' => [
            'authorization' => 'Bearer ton-admin-secret',
            'content-type'  => 'application/json',
            'x-request-id'  => 'ton-exclusion-write',
        ],
        'body'    => json_encode( $payload ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);
if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Admin exclusion write failed: ' . $response['status'] . ' body ' . $response['body'] . "\n" );
    exit( 1 );
}

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/ton/assets',
        'headers' => [ 'x-request-id' => 'ton-exclusion-read' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);
$body = json_decode( $response['body'], TRUE );
if ( 200 !== $response['status'] || ! is_array( $body['data']['assets'] ) ) {
    fwrite( STDERR, "TON assets endpoint failed after exclusion write\n" );
    exit( 1 );
}
foreach ( $body['data']['assets'] as $asset ) {
    if ( 'toncoin' === $asset['id'] ) {
        fwrite( STDERR, "Excluded default asset 'toncoin' still appears in the public TON catalog\n" );
        exit( 1 );
    }
}
@unlink( $store_path );
PHP

php_check 'TON curation API should preserve link_type and project_category through admin writes' \
    env -i PATH="$PATH" \
        TONBANKCARD_ADMIN_TOKEN='ton-admin-secret' \
        TONBANKCARD_ADMIN_STORE="$(mktemp -d "${TMPDIR:-/tmp}/tonbankcard-ton-admin.XXXXXX")/admin.json" \
        php <<'PHP'
<?php
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

$payload = [
    'content' => [
        'ton_assets' => [
            [
                'id'                 => 'ton-currency-link',
                'coin_id'            => 'the-open-network',
                'name'               => 'Currency Linked Asset',
                'symbol'             => 'TON',
                'category'           => 'native',
                'verification_state' => 'verified',
                'link_type'          => 'currency',
                'tags'               => [ 'ton_ecosystem', 'native' ],
            ],
            [
                'id'                 => 'ton-project-link',
                'name'               => 'Project Linked Asset',
                'symbol'             => 'PROJ',
                'category'           => 'jetton',
                'verification_state' => 'curated',
                'link_type'          => 'project',
                'project_category'   => 'wallet',
                'tags'               => [ 'ton_ecosystem' ],
            ],
        ],
    ],
];

$response = tonbankcard_api_handle(
    [
        'method'  => 'PUT',
        'path'    => '/api/admin/content',
        'headers' => [
            'authorization' => 'Bearer ton-admin-secret',
            'content-type'  => 'application/json',
            'x-request-id'  => 'ton-link-type-write',
        ],
        'body'    => json_encode( $payload ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);
if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Admin link_type write failed: ' . $response['status'] . ' body ' . $response['body'] . "\n" );
    exit( 1 );
}

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/ton/assets',
        'headers' => [ 'x-request-id' => 'ton-link-type-read' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);
$body = json_decode( $response['body'], TRUE );
if ( 200 !== $response['status'] || empty( $body['data']['assets'] ) ) {
    fwrite( STDERR, "TON assets endpoint failed after link_type write\n" );
    exit( 1 );
}
$currency_link = NULL;
$project_link = NULL;
foreach ( $body['data']['assets'] as $asset ) {
    if ( 'ton-currency-link' === $asset['id'] ) $currency_link = $asset;
    if ( 'ton-project-link' === $asset['id'] ) $project_link = $asset;
}
if ( ! $currency_link || 'currency' !== $currency_link['link_type'] ) {
    fwrite( STDERR, "Currency-linked asset did not preserve link_type\n" );
    exit( 1 );
}
if ( 'currency' !== $currency_link['route']['name'] || '/currency/the-open-network' !== $currency_link['route']['path'] ) {
    fwrite( STDERR, "Currency-linked asset is not routed to the cryptocurrency page\n" );
    exit( 1 );
}
if ( ! $project_link || 'project' !== $project_link['link_type'] ) {
    fwrite( STDERR, "Project-linked asset did not preserve link_type\n" );
    exit( 1 );
}
if ( 'wallet' !== $project_link['project_category'] ) {
    fwrite( STDERR, "Project-linked asset did not preserve project_category\n" );
    exit( 1 );
}
if ( 'ton-asset' !== $project_link['route']['name'] || '/ton/asset/ton-project-link' !== $project_link['route']['path'] ) {
    fwrite( STDERR, "Project-linked asset is not routed to the per-asset catalog page\n" );
    exit( 1 );
}
@unlink( $store_path );
PHP

php_check 'TON curation defaults should expose Toncoin with currency link_type and a coin_id for market enrichment' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/ton/assets',
        'headers' => [ 'x-request-id' => 'ton-defaults-toncoin' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);
$body = json_decode( $response['body'], TRUE );
if ( 200 !== $response['status'] || empty( $body['data']['assets'] ) ) {
    fwrite( STDERR, "TON defaults endpoint failed\n" );
    exit( 1 );
}
$toncoin = NULL;
foreach ( $body['data']['assets'] as $asset ) {
    if ( 'toncoin' === $asset['id'] ) $toncoin = $asset;
}
if ( ! $toncoin ) {
    fwrite( STDERR, "Toncoin default asset is missing from the catalog\n" );
    exit( 1 );
}
if ( empty( $toncoin['coin_id'] ) ) {
    fwrite( STDERR, "Toncoin default asset is missing coin_id required for market enrichment\n" );
    exit( 1 );
}
if ( 'currency' !== $toncoin['link_type'] ) {
    fwrite( STDERR, "Toncoin default asset is not linked to the cryptocurrency page\n" );
    exit( 1 );
}
if ( 'currency' !== $toncoin['route']['name'] ) {
    fwrite( STDERR, "Toncoin default route does not target the cryptocurrency page\n" );
    exit( 1 );
}
PHP

# Regression for issue #104: an admin override that omits coin_id must not strip
# the default Toncoin's the-open-network coin_id, otherwise the asset card loses
# its CoinGecko market data on the public /ton page.
php_check 'admin override without coin_id should not erase default Toncoin market id' \
    env -i PATH="$PATH" \
        TONBANKCARD_ADMIN_TOKEN='ton-admin-secret' \
        TONBANKCARD_ADMIN_STORE="$(mktemp -d "${TMPDIR:-/tmp}/tonbankcard-ton-admin.XXXXXX")/admin.json" \
        php <<'PHP'
<?php
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

$payload = [
    'content' => [
        'ton_assets' => [
            [
                'id'                 => 'toncoin',
                'name'               => 'Toncoin',
                'symbol'             => 'TON',
                'category'           => 'native',
                'verification_state' => 'verified',
                'tags'               => [ 'native', 'native_ton' ],
                'list_ids'           => [ 'featured' ],
                'featured'           => TRUE,
                // Note: coin_id intentionally omitted to mimic an admin save that
                // dropped the field — issue #104 reproduction scenario.
            ],
        ],
    ],
];

$response = tonbankcard_api_handle(
    [
        'method'  => 'PUT',
        'path'    => '/api/admin/content',
        'headers' => [
            'authorization' => 'Bearer ton-admin-secret',
            'content-type'  => 'application/json',
            'x-request-id'  => 'ton-issue-104-write',
        ],
        'body'    => json_encode( $payload ),
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);
if ( 200 !== $response['status'] ) {
    fwrite( STDERR, 'Admin issue #104 setup write failed: ' . $response['status'] . ' body ' . $response['body'] . "\n" );
    exit( 1 );
}

$response = tonbankcard_api_handle(
    [
        'method'  => 'GET',
        'path'    => '/api/ton/assets',
        'headers' => [ 'x-request-id' => 'ton-issue-104-read' ],
        'body'    => '',
    ],
    [],
    $GLOBALS['runtime_config'],
    $api
);
$body = json_decode( $response['body'], TRUE );
if ( 200 !== $response['status'] || empty( $body['data']['assets'] ) ) {
    fwrite( STDERR, "TON catalog read failed for issue #104 regression\n" );
    exit( 1 );
}
$toncoin = NULL;
foreach ( $body['data']['assets'] as $asset ) {
    if ( 'toncoin' === $asset['id'] ) $toncoin = $asset;
}
if ( ! $toncoin ) {
    fwrite( STDERR, "Toncoin disappeared after admin override (issue #104 regression)\n" );
    exit( 1 );
}
if ( empty( $toncoin['coin_id'] ) || 'the-open-network' !== $toncoin['coin_id'] ) {
    fwrite( STDERR, "Issue #104 regression: admin override without coin_id stripped the-open-network from Toncoin\n" );
    exit( 1 );
}
$market_ids = isset( $body['data']['market_coin_ids'] ) ? $body['data']['market_coin_ids'] : [];
if ( ! in_array( 'the-open-network', $market_ids, TRUE ) ) {
    fwrite( STDERR, "Issue #104 regression: the-open-network missing from market_coin_ids after admin override\n" );
    exit( 1 );
}
@unlink( $store_path );
PHP

php_check 'TON curation authorization should reject query tokens outside local profile' \
    env -i PATH="$PATH" php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

$config = [
    'ton_ecosystem' => [
        'curation_token' => 'ton-curation-secret',
    ],
];

$query_request = [
    'headers' => [],
    'query'   => [ 'token' => 'ton-curation-secret' ],
];
if ( tonbankcard_api_ton_curation_allowed( $query_request, [ 'profile' => 'production' ], $config ) ) {
    fwrite( STDERR, "TON curation accepted a query token outside local profile\n" );
    exit( 1 );
}

$header_request = [
    'headers' => [ 'x-tonbankcard-ton-curation-token' => 'ton-curation-secret' ],
    'query'   => [ 'token' => 'wrong-query-token' ],
];
if ( ! tonbankcard_api_ton_curation_allowed( $header_request, [ 'profile' => 'production' ], $config ) ) {
    fwrite( STDERR, "TON curation rejected a valid header token\n" );
    exit( 1 );
}

if ( ! tonbankcard_api_ton_curation_allowed( $query_request, [ 'profile' => 'local' ], $config ) ) {
    fwrite( STDERR, "TON curation did not preserve local query-token ergonomics\n" );
    exit( 1 );
}
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'TON ecosystem curation check passed.'
