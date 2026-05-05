<?php
// Quick end-to-end test for the delete/exclude fix.
// Simulates: admin adds asset, deletes built-in default, checks results.

define('GECKO_CLIENT_VERSION', '1.2.0');
define('GECKO_CLIENT_DIR', '/tmp/gh-issue-solver-1777975464058');
define('GECKO_CLIENT_LOG_LEVEL', 'none');

require GECKO_CLIENT_DIR . '/vendor.php';
require GECKO_CLIENT_DIR . '/functions.php';
require GECKO_CLIENT_DIR . '/api/ton.php';
require GECKO_CLIENT_DIR . '/api/admin.php';

// Set up temp admin store
$store_path = sys_get_temp_dir() . '/test-admin-store-' . getmypid() . '.json';
$env_path   = GECKO_CLIENT_DIR . '/.env';

putenv('TONBANKCARD_ADMIN_TOKEN=test-token-abc');
putenv('TONBANKCARD_ADMIN_STORE=' . $store_path);

$runtime = ['profile' => 'local', 'admin' => ['store_path' => $store_path]];
$config  = [];

// Step 1: Write exclusion of 'toncoin' (a built-in default)
$content_payload = [
    'ton_assets'             => [],
    'ton_excluded_asset_ids' => ['toncoin'],
];
$state = tonbankcard_api_admin_load_state($runtime, $config);
$state['content'] = tonbankcard_api_admin_merge_content($state['content'], $content_payload);
$saved = tonbankcard_api_admin_save_state($state, $runtime, $config);

if (empty($saved['ok'])) {
    echo "FAIL: could not save state: " . $saved['message'] . "\n";
    exit(1);
}

// Step 2: Load TON assets – toncoin should be excluded
$ton_state = tonbankcard_api_ton_load_state($runtime, $config);
$ids = array_column($ton_state['assets'], 'id');

if (in_array('toncoin', $ids, true)) {
    echo "FAIL: 'toncoin' still present after exclusion. IDs: " . implode(', ', $ids) . "\n";
    exit(1);
}

echo "PASS: 'toncoin' is excluded. Remaining IDs: " . implode(', ', $ids) . "\n";

// Step 3: Verify content round-trips excluded list
$reloaded = tonbankcard_api_admin_load_state($runtime, $config);
$excluded = $reloaded['content']['ton_excluded_asset_ids'] ?? [];
if (!in_array('toncoin', $excluded, true)) {
    echo "FAIL: excluded list not persisted. Got: " . implode(', ', $excluded) . "\n";
    exit(1);
}
echo "PASS: excluded list persisted correctly.\n";

@unlink($store_path);
echo "All checks passed.\n";
