#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-database-schema-and-migrations.md
up_migration=database/migrations/0001_v2_core_schema.up.sql
down_migration=database/migrations/0001_v2_core_schema.down.sql
stage9_up_migration=database/migrations/0011_stage9_reliability_hardening.up.sql
stage9_down_migration=database/migrations/0011_stage9_reliability_hardening.down.sql
runner=database/migrate.php

fail() {
    printf '%s\n' "FAIL: $1" >&2
    failures=$((failures + 1))
}

assert_file() {
    if [ ! -f "$1" ]; then
        fail "Missing required file: $1"
    fi
}

assert_executable() {
    if [ ! -x "$1" ]; then
        fail "Expected executable file: $1"
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
        fail "$file does not document $description"
    fi
}

assert_file "$doc"
assert_file "$up_migration"
assert_file "$down_migration"
assert_file "$stage9_up_migration"
assert_file "$stage9_down_migration"
assert_file "$runner"
assert_executable "$runner"

assert_contains "$doc" '^# TONBANKCARD V2 Database Schema and Migrations$' 'the database schema title'
assert_contains "$doc" 'Issue: \[#11\]' 'the issue reference'
assert_contains "$doc" '^## Data Minimization$' 'data minimization rules'
assert_contains "$doc" '^## Entity Model$' 'the entity model'
assert_contains "$doc" 'Telegram user identity' 'Telegram user identity storage'
assert_contains "$doc" 'session records' 'session records'
assert_contains "$doc" 'watchlist entries' 'watchlist entries'
assert_contains "$doc" 'alert rules' 'alert rules'
assert_contains "$doc" 'referral attribution' 'referral attribution'
assert_contains "$doc" 'AI insight cache metadata' 'AI insight cache metadata'
assert_contains "$doc" 'provider settings' 'provider settings'
assert_contains "$doc" 'admin users' 'admin users'
assert_contains "$doc" 'premium entitlements' 'premium entitlements'
assert_contains "$doc" '0011_stage9_reliability_hardening' 'Stage 9 reliability hardening migration'
assert_contains "$doc" '^## Query Paths and Indexes$' 'query paths and indexes'
assert_contains "$doc" 'watchlists by user' 'watchlist query path'
assert_contains "$doc" 'active alerts by coin' 'alert query path'
assert_contains "$doc" 'referrals by campaign' 'referral query path'
assert_contains "$doc" 'audit logs by actor' 'audit log actor query path'
assert_contains "$doc" '^## Migration Runner Conventions$' 'migration runner conventions'
assert_contains "$doc" 'database/migrations/0001_v2_core_schema.up.sql' 'the up migration path'
assert_contains "$doc" 'database/migrations/0001_v2_core_schema.down.sql' 'the down migration path'
assert_contains "$doc" 'php database/migrate.php up' 'the local migration command'
assert_contains "$doc" '^## Local Empty Database Setup$' 'local empty database setup'
assert_contains "$doc" '^## Backup and Restore Expectations$' 'backup and restore expectations'
assert_contains "$doc" '^## Retention Policy$' 'retention policy'
assert_contains "$doc" '^## Acceptance Criteria Mapping$' 'acceptance criteria mapping'

for table in \
    schema_migrations \
    users \
    user_sessions \
    watchlists \
    watchlist_entries \
    alert_rules \
    alert_deliveries \
    referral_campaigns \
    referral_attributions \
    ai_insight_cache \
    provider_settings \
    feature_flags \
    admin_users \
    admin_audit_logs \
    premium_entitlements
do
    assert_contains "$up_migration" "CREATE TABLE IF NOT EXISTS \`$table\`" "the $table table"
done

assert_contains "$up_migration" 'UNIQUE KEY `uniq_users_telegram_user_id`' 'unique Telegram user identity'
assert_contains "$up_migration" 'KEY `idx_watchlists_user' 'watchlist user index'
assert_contains "$up_migration" 'KEY `idx_watchlist_entries_coin' 'watchlist coin index'
assert_contains "$up_migration" 'KEY `idx_alert_rules_active_coin' 'active alert coin index'
assert_contains "$up_migration" 'KEY `idx_referral_attributions_campaign' 'referral campaign index'
assert_contains "$up_migration" 'KEY `idx_admin_audit_logs_actor' 'admin audit actor index'
assert_contains "$up_migration" 'KEY `idx_admin_audit_logs_subject' 'admin audit subject index'
assert_contains "$up_migration" 'secret_ref' 'secret references instead of stored provider secrets'
assert_contains "$up_migration" 'telegram_init_data_hash' 'hashed Telegram initData metadata'
assert_contains "$up_migration" 'cache_key_hash' 'hashed AI cache keys'
assert_contains "$stage9_up_migration" 'UNIQUE KEY `uniq_premium_payment_events_charge_event`' 'unique premium charge event key'
assert_contains "$stage9_up_migration" 'evaluation_claim_token' 'alert rule evaluation claim token'
assert_contains "$stage9_up_migration" 'retry_claim_token' 'alert retry claim token'
assert_contains "$stage9_up_migration" 'idx_alert_rules_evaluation_claim' 'alert rule evaluation claim index'
assert_contains "$stage9_up_migration" 'idx_alert_deliveries_claim' 'alert retry claim index'
assert_contains "$stage9_up_migration" 'idx_alert_deliveries_retry' 'queued alert retry index'

for table in \
    premium_entitlements \
    admin_audit_logs \
    admin_users \
    feature_flags \
    provider_settings \
    ai_insight_cache \
    referral_attributions \
    referral_campaigns \
    alert_deliveries \
    alert_rules \
    watchlist_entries \
    watchlists \
    user_sessions \
    users
do
    assert_contains "$down_migration" "DROP TABLE IF EXISTS \`$table\`" "rollback for the $table table"
done

if [ -f "$runner" ]; then
    dry_run_output=$(php "$runner" dry-run 2>&1 || true)
    if ! printf '%s\n' "$dry_run_output" | grep -Eq '0001_v2_core_schema'; then
        fail "database/migrate.php dry-run does not list the V2 core schema migration"
    fi
fi

assert_contains package.json '"test:database"' 'the database schema npm script'
assert_contains package.json 'test:database' 'the aggregate database schema check'
assert_contains README.md 'docs/v2-database-schema-and-migrations\.md' 'the database schema documentation link'
assert_contains README.md 'php database/migrate\.php up' 'the local database initialization command'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Database schema and migration check passed.'
