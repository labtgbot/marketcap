#!/usr/bin/env sh
set -eu

failures=0
doc=docs/adr/0001-v2-migration-architecture.md

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

assert_contains "$doc" '^# ADR 0001: V2 Migration Architecture$' 'the V2 migration ADR title'
assert_contains "$doc" '^Status: Accepted$' 'an accepted architecture decision'
assert_contains "$doc" 'Issue: \[#9\]' 'the issue reference'
assert_contains "$doc" 'parallel V2 route migration' 'the chosen migration path'
assert_contains "$doc" 'incremental replacement' 'the incremental replacement option'
assert_contains "$doc" 'full rewrite' 'the full rewrite option'
assert_contains "$doc" '^## Decision Drivers$' 'decision drivers'
assert_contains "$doc" '^## Options Considered$' 'options considered'
assert_contains "$doc" '^## Folder Structure$' 'folder structure'
assert_contains "$doc" 'templates/v2' 'PHP template organization'
assert_contains "$doc" 'assets/v2/js/alpine' 'Alpine component organization'
assert_contains "$doc" 'Tailwind CDN' 'Tailwind CDN configuration'
assert_contains "$doc" 'Chart.js' 'Chart.js module organization'
assert_contains "$doc" '^## Build Strategy$' 'build strategy'
assert_contains "$doc" '^## Routing Rules$' 'routing rules'
assert_contains "$doc" '/currency/:id' 'existing coin route compatibility'
assert_contains "$doc" '/coins/:id' 'V2 canonical coin route'
assert_contains "$doc" '/app/coin/:id' 'Telegram Mini App coin route'
assert_contains "$doc" 'public website SEO' 'public website SEO support'
assert_contains "$doc" 'Telegram Mini App webviews' 'Telegram Mini App webview support'
assert_contains "$doc" 'existing data routes' 'existing data route compatibility'
assert_contains "$doc" '^## Risks and Mitigations$' 'risks and mitigations'
assert_contains "$doc" 'CDN dependencies' 'CDN dependency risks'
assert_contains "$doc" 'offline/PWA' 'offline and PWA risks'
assert_contains "$doc" '^## Rollback Strategy$' 'rollback strategy'
assert_contains "$doc" '^## Acceptance Criteria Mapping$' 'acceptance criteria mapping'

assert_contains README.md 'docs/adr/0001-v2-migration-architecture\.md' 'the V2 migration architecture ADR link'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Architecture decision check passed.'
