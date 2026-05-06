<?php
/**
 * Reproduce issue #104: when admin saves Toncoin without coin_id,
 * the default Toncoin's coin_id is wiped out and market data disappears.
 */

// Set up admin store with a Toncoin entry that lacks coin_id
$tmp_admin_store = sys_get_temp_dir() . '/tonbankcard-marketcap-admin.json';
$dogs_asset = [
    'id'                 => 'dogs',
    'coin_id'            => 'dogs-2',
    'name'               => 'Dogs',
    'symbol'             => 'DOGS',
    'category'           => 'jetton',
    'verification_state' => 'verified',
    'priority'           => 90,
    'tags'               => ['jetton', 'meme', 'ton_asset'],
    'list_ids'           => ['featured'],
    'featured'           => true,
];
$bad_toncoin = [
    'id'                 => 'toncoin',
    'name'               => 'Toncoin',
    'symbol'             => 'TON',
    'category'           => 'native',
    'verification_state' => 'verified',
    'priority'           => 50,
    'tags'               => ['native', 'native_ton', 'infrastructure'],
    'list_ids'           => ['featured'],
    'featured'           => true,
    // NOTE: coin_id is MISSING — this is what causes the bug
];

file_put_contents( $tmp_admin_store, json_encode([
    'content' => [
        'ton_assets' => [ $dogs_asset, $bad_toncoin ],
    ],
]));

putenv( 'TONBANKCARD_ADMIN_STORE=' . $tmp_admin_store );

require __DIR__ . '/../constants.php';
require __DIR__ . '/../functions.php';
require __DIR__ . '/../config/runtime.php';
require __DIR__ . '/../api/ton.php';

// Build the catalog using the same code path the live API uses
$runtime = tonbankcard_runtime_config();
$result = tonbankcard_api_ton_load_state( $runtime, [] );

echo "Source type: {$result['source_type']}\n";
echo "Asset count: " . count($result['assets']) . "\n\n";
foreach ($result['assets'] as $a) {
    $cid = $a['coin_id'] ?? '(none)';
    echo sprintf("- %-20s id=%-15s coin_id=%-25s priority=%d\n",
        $a['name'], $a['id'], $cid, $a['priority']);
}

echo "\nMarket coin IDs that will be requested from CoinGecko:\n";
echo "  " . implode(', ', $result['market_coin_ids'] ?? tonbankcard_api_ton_market_coin_ids($result['assets'])) . "\n";

echo "\nVERDICT: ";
$toncoin = null;
foreach ($result['assets'] as $a) {
    if ($a['id'] === 'toncoin') { $toncoin = $a; break; }
}
if ($toncoin && empty($toncoin['coin_id'])) {
    echo "BUG REPRODUCED — Toncoin has no coin_id, will lose market data.\n";
    exit(1);
} elseif ($toncoin && $toncoin['coin_id'] === 'the-open-network') {
    echo "FIXED — Toncoin retains coin_id 'the-open-network'.\n";
    exit(0);
} else {
    echo "Unexpected state: " . json_encode($toncoin) . "\n";
    exit(2);
}
