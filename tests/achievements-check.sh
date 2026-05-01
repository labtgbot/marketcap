#!/usr/bin/env sh
set -eu

assert_file() {
  if [ ! -f "$1" ]; then
    echo "Missing expected file: $1" >&2
    exit 1
  fi
}

assert_contains() {
  file="$1"
  needle="$2"
  if ! grep -F "$needle" "$file" >/dev/null 2>&1; then
    echo "Expected $file to contain: $needle" >&2
    exit 1
  fi
}

assert_file docs/v2-gamification-achievements.md
assert_contains docs/v2-gamification-achievements.md "# TONBANKCARD V2 Gamification Achievements"
assert_contains docs/v2-gamification-achievements.md "Issue: [#34]"
assert_contains docs/v2-gamification-achievements.md "first_watchlist"
assert_contains docs/v2-gamification-achievements.md "first_alert"
assert_contains docs/v2-gamification-achievements.md "weekly_market_check"
assert_contains docs/v2-gamification-achievements.md "ton_explorer"
assert_contains docs/v2-gamification-achievements.md "share_milestone"
assert_contains docs/v2-gamification-achievements.md "caught_market_movement"
assert_contains docs/v2-gamification-achievements.md "opt-in"
assert_contains docs/v2-gamification-achievements.md "dismiss"
assert_contains docs/v2-gamification-achievements.md "haptics"
assert_contains docs/v2-gamification-achievements.md "badges"
assert_contains docs/v2-gamification-achievements.md "shareable achievement cards"
assert_contains docs/v2-gamification-achievements.md "admin controls"
assert_contains docs/v2-gamification-achievements.md "TONBANKCARD_FEATURE_GAMIFICATION=true"
assert_contains docs/v2-gamification-achievements.md "TONBANKCARD_ACHIEVEMENT_WEEKLY_CHECK_DAYS"
assert_contains docs/v2-gamification-achievements.md "tests/achievements-check.sh"

assert_contains README.md "docs/v2-gamification-achievements.md"
assert_contains package.json "\"test:achievements\""
assert_contains package.json "tests/achievements-check.sh"

assert_file api/achievements.php
assert_contains api/router.php "require_once __DIR__ . '/achievements.php';"
assert_contains api/router.php "'/api/achievements/settings'"
assert_contains api/router.php "tonbankcard_api_achievements_is_request"
assert_contains api/router.php "tonbankcard_api_achievements_handle"
assert_contains api/achievements.php "function tonbankcard_api_achievements_settings"
assert_contains api/achievements.php "function tonbankcard_api_achievements_definitions"
assert_contains api/achievements.php "function tonbankcard_api_achievements_calculate_streak"
assert_contains api/achievements.php "first_watchlist"
assert_contains api/achievements.php "weekly_market_check"
assert_contains api/achievements.php "caught_market_movement"

assert_contains config/api.php "TONBANKCARD_ACHIEVEMENT_WEEKLY_CHECK_DAYS"
assert_contains config/api.php "TONBANKCARD_ACHIEVEMENT_SHARE_MILESTONE_COUNT"
assert_contains config/api.php "TONBANKCARD_ACHIEVEMENT_MOVEMENT_THRESHOLD_PERCENT"
assert_contains config/api.php "'achievements'"
assert_contains .env.example "TONBANKCARD_ACHIEVEMENT_WEEKLY_CHECK_DAYS"
assert_contains .env.example "TONBANKCARD_ACHIEVEMENT_SHARE_MILESTONE_COUNT"
assert_contains .env.example "TONBANKCARD_ACHIEVEMENT_MOVEMENT_THRESHOLD_PERCENT"

assert_file database/migrations/0009_gamification_achievements.up.sql
assert_file database/migrations/0009_gamification_achievements.down.sql
assert_contains database/migrations/0009_gamification_achievements.up.sql "achievement_events"
assert_contains database/migrations/0009_gamification_achievements.up.sql "user_achievements"
assert_contains database/migrations/0009_gamification_achievements.up.sql "achievement_prompt_dismissals"
assert_contains database/migrations/0009_gamification_achievements.up.sql "feature_flags"
assert_contains database/migrations/0009_gamification_achievements.up.sql "gamification"
assert_contains database/migrations/0009_gamification_achievements.up.sql "weekly_market_check"
assert_contains docs/v2-database-schema-and-migrations.md "0009_gamification_achievements"
assert_contains docs/v2-database-schema-and-migrations.md "achievement_events"
assert_contains docs/v2-database-schema-and-migrations.md "achievement_prompt_dismissals"

assert_file templates/routes/achievements.php
assert_file dev/js/src/achievements.js
assert_file dev/js/src/routes/achievements.js
assert_contains config/routes.php "'achievements'"
assert_contains config/routes.php "'/achievements'"
assert_contains config/routes-v2.php "'achievements'"
assert_contains config/navigation.php "'Achievements'"
assert_contains views/app-scripts.php "achievementsConfig"
assert_contains dev/js/source.json "\"achievements.js\""
assert_contains dev/js/source.json "\"routes/achievements.js\""
assert_contains templates/routes/achievements.php "achievement-badge-grid"
assert_contains templates/routes/achievements.php "achievement-prompt"
assert_contains assets/css/style.css "achievement-badge-grid"
assert_contains assets/css/style.css "achievement-control-grid"

assert_contains dev/js/src/initial.js "achievement_opted_in"
assert_contains dev/js/src/initial.js "achievement_unlocked"
assert_contains dev/js/src/initial.js "achievement_dismissed"
assert_contains dev/js/src/initial.js "achievement_shared"
assert_contains dev/js/src/initial.js "achievement_id"
assert_contains dev/js/src/initial.js "streak_timezone"
assert_contains dev/js/src/watchlist.js "watchlist_added"
assert_contains dev/js/src/watchlist.js "GeckoClient.achievements"
assert_contains dev/js/src/alerts.js "alert_created"
assert_contains dev/js/src/alerts.js "GeckoClient.achievements"
assert_contains dev/js/src/share.js "share_started"
assert_contains dev/js/src/share.js "GeckoClient.achievements"
assert_contains dev/js/src/router.js "trackRoute"
assert_contains dev/js/src/routes/currencies.js "trackMarketMovement"
assert_contains dev/js/src/routes/ton.js "ton_viewed"

php <<'PHP'
<?php
require 'constants.php';
require GECKO_CLIENT_CONFIG_DIR . '/api.php';
require __DIR__ . '/api/router.php';

function assert_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$definitions = tonbankcard_api_achievements_definitions();
$ids = array_column($definitions, 'id');
foreach (['first_watchlist', 'first_alert', 'weekly_market_check', 'ton_explorer', 'share_milestone', 'caught_market_movement'] as $id) {
    assert_true(in_array($id, $ids, true), 'Missing achievement definition: ' . $id);
}

$settings = tonbankcard_api_achievements_settings(
    ['feature_flags' => ['gamification' => true]],
    [
        'achievements' => [
            'weekly_check_days' => 0,
            'share_milestone_count' => 250,
            'movement_threshold_percent' => 0.2,
            'max_prompts_per_session' => 0,
        ],
    ]
);
assert_true($settings['enabled'] === true, 'Gamification settings should reflect enabled feature flag.');
assert_true($settings['weekly_check_days'] === 2, 'Weekly market check days should be clamped to the minimum.');
assert_true($settings['share_milestone_count'] === 100, 'Share milestone should be clamped to the maximum.');
assert_true(abs($settings['movement_threshold_percent'] - 1.0) < 0.0001, 'Movement threshold should be clamped to the minimum.');
assert_true($settings['max_prompts_per_session'] === 1, 'Prompt cap should be clamped to the minimum.');

$nyStreak = tonbankcard_api_achievements_calculate_streak(
    [
        '2026-05-05T03:30:00Z',
        '2026-05-06T03:30:00Z',
        '2026-05-07T03:30:00Z',
        '2026-05-08T02:30:00Z',
    ],
    'America/New_York',
    strtotime('2026-05-08 03:30:00 UTC')
);
assert_true($nyStreak['timezone'] === 'America/New_York', 'New York timezone should be preserved.');
assert_true($nyStreak['current_count'] === 4, 'New York streak should count local calendar days across UTC boundaries.');
assert_true($nyStreak['last_local_date'] === '2026-05-07', 'New York streak should end on the latest local date.');

$tokyoStreak = tonbankcard_api_achievements_calculate_streak(
    [
        ['occurred_at' => '2026-05-01T15:15:00Z'],
        ['occurred_at' => '2026-05-02T15:15:00Z'],
        ['occurred_at' => '2026-05-03T15:15:00Z'],
    ],
    'Asia/Tokyo',
    strtotime('2026-05-04 15:30:00 UTC')
);
assert_true($tokyoStreak['timezone'] === 'Asia/Tokyo', 'Tokyo timezone should be preserved.');
assert_true($tokyoStreak['current_count'] === 3, 'Tokyo streak should count consecutive local days.');
assert_true($tokyoStreak['last_local_date'] === '2026-05-04', 'Tokyo streak should use local dates, not UTC dates.');

$response = tonbankcard_api_handle(
    [
        'method' => 'GET',
        'path' => '/api/achievements/settings',
        'headers' => [],
    ],
    [],
    ['feature_flags' => ['gamification' => true]],
    [
        'achievements' => [
            'weekly_check_days' => 7,
            'share_milestone_count' => 3,
            'movement_threshold_percent' => 7.5,
            'max_prompts_per_session' => 1,
        ],
    ]
);
$body = json_decode($response['body'], true);
$data = $body['data'] ?? [];
assert_true($response['status'] === 200, 'Achievements settings endpoint should return OK.');
assert_true(($data['settings']['enabled'] ?? null) === true, 'Settings payload should expose enabled state.');
assert_true(count($data['definitions'] ?? []) === 6, 'Settings payload should include all achievement definitions.');
assert_true(($data['admin_controls']['feature_flag'] ?? '') === 'gamification', 'Settings payload should identify admin feature flag.');
assert_true(isset($data['admin_controls']['tunable_env']['TONBANKCARD_ACHIEVEMENT_WEEKLY_CHECK_DAYS']), 'Settings payload should expose tunable admin controls.');

$disabled = tonbankcard_api_handle(
    [
        'method' => 'GET',
        'path' => '/api/achievements/settings',
        'headers' => [],
    ],
    [],
    ['feature_flags' => ['gamification' => false]],
    ['achievements' => []]
);
$disabledBody = json_decode($disabled['body'], true);
assert_true($disabled['status'] === 200, 'Disabled gamification should still expose safe public settings.');
assert_true(($disabledBody['data']['settings']['enabled'] ?? true) === false, 'Disabled feature flag should be visible in settings.');
PHP

echo "Gamification achievements check passed."
