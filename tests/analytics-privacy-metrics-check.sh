#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-analytics-privacy-metrics.md

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
        fail "$file does not document $description"
    fi
}

assert_file "$doc"

assert_contains "$doc" '^# TONBANKCARD V2 Analytics, Privacy, and Success Metrics Baseline$' 'the analytics baseline title'
assert_contains "$doc" 'Issue: \[#10\]' 'the issue reference'
assert_contains "$doc" '^## Measurement Principles$' 'measurement principles'
assert_contains "$doc" '^## Identity Rules$' 'identity rules'
assert_contains "$doc" 'anonymous website analytics' 'anonymous website analytics'
assert_contains "$doc" 'authenticated Telegram sessions' 'authenticated Telegram sessions'
assert_contains "$doc" 'server-validated Telegram `initData`' 'server-validated Telegram initData'
assert_contains "$doc" '^## Event Taxonomy$' 'event taxonomy'
assert_contains "$doc" 'search_opened' 'search event'
assert_contains "$doc" 'watchlist_added' 'watchlist add event'
assert_contains "$doc" 'alert_created' 'alert create event'
assert_contains "$doc" 'share_started' 'share event'
assert_contains "$doc" 'referral_opened' 'referral open event'
assert_contains "$doc" 'swap_widget_opened' 'swap widget event'
assert_contains "$doc" 'ai_insight_viewed' 'AI insight event'
assert_contains "$doc" 'ton_viewed' 'TON view event'
assert_contains "$doc" 'premium_conversion_completed' 'premium conversion event'
assert_contains "$doc" '^## Retention Windows$' 'retention windows'
assert_contains "$doc" '^## Sensitive Data Classification$' 'sensitive data classification'
assert_contains "$doc" 'Telegram user data' 'Telegram user data privacy rules'
assert_contains "$doc" 'wallet addresses' 'wallet address privacy rules'
assert_contains "$doc" 'AI prompts' 'AI prompt privacy rules'
assert_contains "$doc" 'admin audit logs' 'admin audit log privacy rules'
assert_contains "$doc" 'excluded from client logs' 'client log exclusions'
assert_contains "$doc" '^## KPI Definitions$' 'KPI definitions'
assert_contains "$doc" 'Activation' 'activation KPI'
assert_contains "$doc" 'Retention' 'retention KPI'
assert_contains "$doc" 'Virality' 'virality KPI'
assert_contains "$doc" 'Alert usefulness' 'alert usefulness KPI'
assert_contains "$doc" 'Performance' 'performance KPI'
assert_contains "$doc" '^## Dashboard Requirements$' 'dashboard requirements'
assert_contains "$doc" '^## Implementation Contract$' 'implementation contract'
assert_contains "$doc" '^## Acceptance Criteria Mapping$' 'acceptance criteria mapping'

assert_contains README.md 'docs/v2-analytics-privacy-metrics\.md' 'the analytics baseline link'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Analytics, privacy, and metrics check passed.'
