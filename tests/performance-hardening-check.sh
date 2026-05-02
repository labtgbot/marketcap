#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-performance-load-reliability.md

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

    if ! grep -Eq -- "$pattern" "$file"; then
        fail "$file does not include $description"
    fi
}

assert_file "$doc"
assert_file .htaccess
assert_file config/performance.php
assert_file tests/performance-load-check.php
assert_file dev/php/router.php

assert_contains "$doc" '^# TONBANKCARD V2 Performance, Load, And Reliability Hardening$' 'the performance hardening title'
assert_contains "$doc" 'Issue: \[#39\]' 'the issue reference'
assert_contains "$doc" 'first_contentful_render_ms' 'first contentful render budget'
assert_contains "$doc" 'app_ready_ms' 'app ready budget'
assert_contains "$doc" 'market_api_p95_ms' 'market API latency budget'
assert_contains "$doc" 'search_api_p95_ms' 'search API latency budget'
assert_contains "$doc" 'coin_detail_api_p95_ms' 'coin detail latency budget'
assert_contains "$doc" 'chart_render_ms' 'chart render budget'
assert_contains "$doc" 'alert_delivery_p95_ms' 'alert delivery budget'
assert_contains "$doc" 'share_card_generation_p95_ms' 'share-card generation budget'
assert_contains "$doc" 'market_pulse' 'market pulse load profile'
assert_contains "$doc" 'smart_search' 'search load profile'
assert_contains "$doc" 'coin_detail' 'coin detail load profile'
assert_contains "$doc" 'alert_evaluation' 'alert evaluation load profile'
assert_contains "$doc" 'share_card_generation' 'share-card load profile'
assert_contains "$doc" 'safe traffic assumptions' 'safe traffic assumptions'
assert_contains "$doc" 'bottlenecks' 'bottleneck notes'
assert_contains "$doc" 'provider outage' 'provider outage behavior'
assert_contains "$doc" 'static asset' 'static asset cache policy'
assert_contains "$doc" 'npm run test:performance' 'the regression command'

assert_contains config/performance.php "'budgets'" 'performance budgets'
assert_contains config/performance.php "'first_contentful_render_ms'" 'first contentful render budget config'
assert_contains config/performance.php "'app_ready_ms'" 'app ready budget config'
assert_contains config/performance.php "'market_api_p95_ms'" 'market API budget config'
assert_contains config/performance.php "'search_api_p95_ms'" 'search API budget config'
assert_contains config/performance.php "'coin_detail_api_p95_ms'" 'coin detail budget config'
assert_contains config/performance.php "'chart_render_ms'" 'chart render budget config'
assert_contains config/performance.php "'alert_delivery_p95_ms'" 'alert delivery budget config'
assert_contains config/performance.php "'share_card_generation_p95_ms'" 'share-card budget config'
assert_contains config/performance.php "'load_profiles'" 'load profile config'
assert_contains config/performance.php "'market_pulse'" 'market pulse profile config'
assert_contains config/performance.php "'smart_search'" 'smart search profile config'
assert_contains config/performance.php "'coin_detail'" 'coin detail profile config'
assert_contains config/performance.php "'alert_evaluation'" 'alert evaluation profile config'
assert_contains config/performance.php "'share_card_generation'" 'share-card profile config'
assert_contains config/performance.php "'static_assets'" 'static asset cache config'
assert_contains config/performance.php "'immutable_max_age_seconds'" 'immutable static asset max-age'

assert_contains .htaccess 'STATIC ASSET CACHE POLICY' 'production static asset cache policy'
assert_contains .htaccess 'TBC_VERSIONED_ASSET' 'production versioned asset cache marker'
assert_contains .htaccess 'max-age=31536000, immutable' 'production immutable asset cache header'
assert_contains .htaccess 'stale-while-revalidate=604800' 'production stale-while-revalidate header'
assert_contains .htaccess 'service-worker\.js' 'production service worker cache exception'

assert_contains functions.php 'function tonbankcard_static_asset_cache_headers' 'static asset cache header helper'
assert_contains functions.php 'immutable' 'immutable static asset policy'
assert_contains dev/php/router.php 'tonbankcard_emit_static_asset_headers' 'local static asset cache header application'
assert_contains dev/php/router.php 'readfile' 'local static asset response path'
assert_contains service-worker.js 'staleWhileRevalidate' 'service worker static asset strategy'
assert_contains docs/v2-cache-rate-limit-coalescing.md 'stale fallback' 'stale provider fallback documentation'
assert_contains docs/v2-cache-rate-limit-coalescing.md 'coalescing' 'request coalescing documentation'
assert_contains docs/v2-charting-and-market-visualization.md 'lazy-load' 'chart lazy loading documentation'

assert_contains tests/performance-load-check.php 'performance-load-summary.json' 'performance load summary artifact'
assert_contains tests/performance-load-check.php 'market_pulse' 'market pulse load case'
assert_contains tests/performance-load-check.php 'smart_search' 'search load case'
assert_contains tests/performance-load-check.php 'coin_detail' 'coin detail load case'
assert_contains tests/performance-load-check.php 'alert_evaluation' 'alert evaluation load case'
assert_contains tests/performance-load-check.php 'share_card_generation' 'share-card load case'
assert_contains package.json '"test:performance"' 'the performance npm script'
assert_contains package.json 'performance-load-check\.php' 'the PHP load harness script'
assert_contains README.md 'npm run test:performance' 'the performance check command'
assert_contains README.md 'docs/v2-performance-load-reliability\.md' 'the performance documentation link'
assert_contains docs/release-checklist.md 'Performance, load, and reliability checkpoint' 'release performance checkpoint'
assert_contains docs/release-checklist.md 'npm run test:performance' 'release performance command'
assert_contains .github/workflows/ci.yml 'npm test' 'CI aggregate command'
assert_contains .github/workflows/ci.yml 'test-logs' 'CI test log artifact upload'

php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/performance.php';
require 'functions.php';

function assert_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assert_true(isset($performance['budgets']['market_api_p95_ms']), 'Missing market API performance budget.');
assert_true(isset($performance['load_profiles']['market_pulse']['iterations']), 'Missing market pulse load profile iterations.');

$versioned = tonbankcard_static_asset_cache_headers('/assets/js/app.min.js?t=123', $performance);
assert_true(isset($versioned['Cache-Control']), 'Versioned asset missing Cache-Control.');
assert_true(false !== strpos($versioned['Cache-Control'], 'max-age=31536000'), 'Versioned asset should use a one-year max-age.');
assert_true(false !== strpos($versioned['Cache-Control'], 'immutable'), 'Versioned asset should be immutable.');

$unversioned = tonbankcard_static_asset_cache_headers('/assets/images/logo.png', $performance);
assert_true(false !== strpos($unversioned['Cache-Control'], 'stale-while-revalidate'), 'Unversioned asset should use stale-while-revalidate.');

$service_worker = tonbankcard_static_asset_cache_headers('/service-worker.js', $performance);
assert_true(false !== strpos($service_worker['Cache-Control'], 'no-cache'), 'Service worker should stay revalidatable.');

$manifest = tonbankcard_static_asset_cache_headers('/site.webmanifest', $performance);
assert_true(false !== strpos($manifest['Cache-Control'], 'max-age=3600'), 'Manifest should use a short public cache.');
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Performance, load, and reliability hardening check passed.'
