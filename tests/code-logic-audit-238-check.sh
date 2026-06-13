#!/usr/bin/env sh
set -eu

failures=0
doc=docs/code-logic-audit-2026-06-13.md

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
assert_file docs/code-logic-audit-2026-06-12.md
assert_file api/router.php
assert_file api/cache.php
assert_file api/premium.php
assert_file api/alerts.php
assert_file api/ai.php
assert_file api/admin.php
assert_file .htaccess
assert_file database/migrations/0010_premium_payment_state.up.sql

assert_contains "$doc" '^# Code-Logic & Security Audit - Post-Stage 8 Follow-up$' 'the audit title'
assert_contains "$doc" 'issue[[:space:]]*\*\*#238\*\*' 'the source issue reference'
assert_contains "$doc" 'Stage 9 tracking epic \*\*#240\*\*' 'the Stage 9 tracking epic'
assert_contains "$doc" 'docs/code-logic-audit-2026-06-12\.md' 'the previous Stage 8 audit baseline'
assert_contains "$doc" '#183 through #204' 'the closed Stage 7 issue range'
assert_contains "$doc" '#231 through #236' 'the closed Stage 8 issue range'
assert_contains "$doc" '#230 and #237' 'the merged Stage 8 PR references'

assert_contains "$doc" '\.htaccess:28' 'the experiments deny-list gap evidence'
assert_contains "$doc" 'experiments/test-delete-fix\.php:6' 'the hardcoded build-path evidence'
assert_contains "$doc" 'api/router\.php:2180' 'the request-IP source evidence'
assert_contains "$doc" 'api/cache\.php:548' 'the rate-limit identity evidence'
assert_contains "$doc" 'api/premium\.php:1088' 'the payment idempotency handler evidence'
assert_contains "$doc" '0010_premium_payment_state\.up\.sql:33' 'the non-unique charge index evidence'
assert_contains "$doc" 'api/alerts\.php:742' 'the alert cursor advance evidence'
assert_contains "$doc" 'api/alerts\.php:1156' 'the dead fingerprint dedup evidence'
assert_contains "$doc" 'api/ai\.php:1610' 'the byte-oriented truncation evidence'
assert_contains "$doc" 'api/admin\.php:2351' 'the non-atomic admin save evidence'
assert_contains "$doc" 'api/cache\.php:730' 'the missing-TTL rate-limit evidence'

assert_contains "$doc" '\| F1 \| P2 \| Security/Ops \|' 'finding F1 summary'
assert_contains "$doc" '\| F2 \| P2 \| API/Security \|' 'finding F2 summary'
assert_contains "$doc" '\| F3 \| P2 \| Payments/Security \|' 'finding F3 summary'
assert_contains "$doc" '\| F4 \| P2 \| Alerts/Reliability \|' 'finding F4 summary'
assert_contains "$doc" '\| F5 \| P3 \| Alerts/Reliability \|' 'finding F5 summary'
assert_contains "$doc" '\| F6 \| P3 \| AI/Reliability \|' 'finding F6 summary'
assert_contains "$doc" '\| F7 \| P3 \| Admin/Reliability \|' 'finding F7 summary'
assert_contains "$doc" '\| F8 \| P3 \| API/Reliability \|' 'finding F8 summary'

assert_contains "$doc" '#241' 'the experiments exposure follow-up issue'
assert_contains "$doc" '#242' 'the rate-limit IP spoofing follow-up issue'
assert_contains "$doc" '#243' 'the payment idempotency follow-up issue'
assert_contains "$doc" '#244' 'the alert delivery loss follow-up issue'
assert_contains "$doc" '#245' 'the alert dedup follow-up issue'
assert_contains "$doc" '#246' 'the UTF-8 truncation follow-up issue'
assert_contains "$doc" '#247' 'the admin save atomicity follow-up issue'
assert_contains "$doc" '#248' 'the rate-limit TTL follow-up issue'
assert_contains "$doc" 'stage-9-deep-audit' 'the Stage 9 label'
assert_contains "$doc" 'Definition of Done' 'the audit definition of done'

assert_contains README.md 'docs/code-logic-audit-2026-06-13\.md' 'the post-Stage 8 audit documentation link'
assert_contains package.json '"test:code-logic-audit-238"' 'the audit npm script'
assert_contains package.json 'test:code-logic-audit-238' 'the aggregate audit test wiring'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Code-logic audit #238 documentation check passed.'
