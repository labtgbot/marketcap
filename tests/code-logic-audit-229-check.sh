#!/usr/bin/env sh
set -eu

failures=0
doc=docs/code-logic-audit-2026-06-12.md

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
assert_file docs/code-logic-audit-2026.md
assert_file api/admin.php
assert_file api/router.php
assert_file api/cache.php
assert_file api/search.php
assert_file api/ton.php
assert_file service-worker.js

assert_contains "$doc" '^# Code-Logic & Security Audit - Post-Stage 7 Follow-up$' 'the audit title'
assert_contains "$doc" 'issue \*\*#229\*\*' 'the source issue reference'
assert_contains "$doc" 'Stage 8 tracking epic \*\*#231\*\*' 'the Stage 8 tracking epic'
assert_contains "$doc" 'Stage 7' 'the previous hardening baseline'
assert_contains "$doc" '#183 through #204' 'the closed Stage 7 issue range'
assert_contains "$doc" '#205 through #226' 'the merged Stage 7 PR range'
assert_contains "$doc" '#227 and' 'the dependency follow-up PR references'
assert_contains "$doc" '#228' 'the latest dependency follow-up PR reference'

assert_contains "$doc" 'api/admin\.php:2523' 'the admin env formatter evidence'
assert_contains "$doc" 'config/runtime\.php:18-60' 'the dotenv parser evidence'
assert_contains "$doc" 'install/includes/installer\.php:1014-1025' 'the safer installer formatter comparison'
assert_contains "$doc" 'api/router\.php:68-87' 'the global API body-read evidence'
assert_contains "$doc" 'api/cache\.php:555-560' 'the admin rate-limit identity evidence'
assert_contains "$doc" 'api/cache\.php:563-571' 'the session rate-limit identity evidence'
assert_contains "$doc" 'api/search\.php:133-140' 'the search token query evidence'
assert_contains "$doc" 'api/ton\.php:861-868' 'the TON curation token query evidence'
assert_contains "$doc" 'service-worker\.js:60-67' 'the service-worker navigation cache evidence'

assert_contains "$doc" '\| F1 \| P1 \| Admin/Security \|' 'finding F1 summary'
assert_contains "$doc" '\| F2 \| P1 \| API/Security \|' 'finding F2 summary'
assert_contains "$doc" '\| F3 \| P1 \| API/Security \|' 'finding F3 summary'
assert_contains "$doc" '\| F4 \| P2 \| Ops/Security \|' 'finding F4 summary'
assert_contains "$doc" '\| F5 \| P3 \| PWA/Reliability \|' 'finding F5 summary'

assert_contains "$doc" '#232' 'the admin env injection follow-up issue'
assert_contains "$doc" '#233' 'the API body limit follow-up issue'
assert_contains "$doc" '#234' 'the rate-limit identity follow-up issue'
assert_contains "$doc" '#235' 'the worker token URL follow-up issue'
assert_contains "$doc" '#236' 'the service-worker cache follow-up issue'
assert_contains "$doc" 'stage-8-post-hardening' 'the Stage 8 label'
assert_contains "$doc" 'Definition of Done' 'the audit definition of done'

assert_contains README.md 'docs/code-logic-audit-2026-06-12\.md' 'the post-Stage 7 audit documentation link'
assert_contains package.json '"test:code-logic-audit-229"' 'the audit npm script'
assert_contains package.json 'test:code-logic-audit-229' 'the aggregate audit test wiring'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Code-logic audit #229 documentation check passed.'
