#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-product-requirements.md

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

assert_contains "$doc" '^# TONBANKCARD V2 Product Requirements and Information Architecture$' 'the V2 PRD title'
assert_contains "$doc" '^## Goals$' 'product goals'
assert_contains "$doc" '^## Non-Goals$' 'product non-goals'
assert_contains "$doc" '^## Personas$' 'target personas'
assert_contains "$doc" 'Casual market viewer' 'casual market viewer persona'
assert_contains "$doc" 'TON user' 'TON user persona'
assert_contains "$doc" 'Active trader' 'active trader persona'
assert_contains "$doc" 'Telegram group admin' 'Telegram group admin persona'
assert_contains "$doc" 'TONBANKCARD operator' 'TONBANKCARD operator persona'
assert_contains "$doc" '^## Primary User Journeys$' 'primary user journeys'
assert_contains "$doc" 'Market pulse' 'market pulse journey'
assert_contains "$doc" 'Coin detail' 'coin detail journey'
assert_contains "$doc" 'Watchlist' 'watchlist journey'
assert_contains "$doc" 'Alerts' 'alerts journey'
assert_contains "$doc" 'AI insights' 'AI insights journey'
assert_contains "$doc" 'Swap widget' 'swap widget journey'
assert_contains "$doc" 'Share card' 'share card journey'
assert_contains "$doc" 'Referral landing' 'referral landing journey'
assert_contains "$doc" 'Admin configuration' 'admin configuration journey'
assert_contains "$doc" '^## Permissions and Roles$' 'permissions and roles'
assert_contains "$doc" '^## Success Metrics$' 'success metrics'
assert_contains "$doc" '^## Release Phases$' 'release phases'
assert_contains "$doc" '^## Information Architecture$' 'information architecture'
assert_contains "$doc" 'Public website routes' 'public website routes'
assert_contains "$doc" 'Telegram Mini App routes' 'Telegram Mini App routes'
assert_contains "$doc" 'Bot flows' 'bot flows'
assert_contains "$doc" 'Admin routes' 'admin routes'
assert_contains "$doc" '^## MVP Scope Guardrails$' 'MVP scope guardrails'
assert_contains "$doc" '^## Open Decisions$' 'open decisions'
assert_contains "$doc" 'Owner' 'open decision owners'
assert_contains "$doc" 'Deadline' 'open decision deadlines'

assert_contains README.md 'docs/v2-product-requirements.md' 'the V2 PRD link'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Product requirements check passed.'
