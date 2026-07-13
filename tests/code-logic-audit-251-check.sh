#!/usr/bin/env sh
set -eu

failures=0
doc=docs/code-logic-audit-2026-07-13.md

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
assert_file docs/code-logic-audit-2026-06-13.md
assert_file functions.php
assert_file config/routes-v2.php
assert_file config/seo-universe.php
assert_file views/app-head.php
assert_file api/market.php
assert_file api/alerts.php
assert_file api/observability.php
assert_file api/ton.php
assert_file api/premium.php
assert_file api/search.php
assert_file dev/js/src/initial.js
assert_file service-worker.js
assert_file dev/js/src/components/currency-converter.js
assert_file tests/generated-bundle-check.js

assert_contains "$doc" '^# Accessibility, SEO & Code-Logic Audit - Stage 10$' 'the audit title'
assert_contains "$doc" 'issue \*\*#251\*\*' 'the source issue reference'
assert_contains "$doc" 'epic \*\*#253\*\*' 'the Stage 10 tracking epic'
assert_contains "$doc" 'docs/code-logic-audit-2026-06-13\.md' 'the previous Stage 9 audit baseline'
assert_contains "$doc" '#183 through #204' 'the closed Stage 7 issue range'
assert_contains "$doc" '#231 through #236' 'the closed Stage 8 issue range'
assert_contains "$doc" '#241 through #248' 'the closed Stage 9 issue range'
assert_contains "$doc" '#240' 'the Stage 9 tracking epic reference'

assert_contains "$doc" 'functions\.php:521' 'the admin route meta gap evidence'
assert_contains "$doc" 'config/routes-v2\.php:226' 'the admin route noindex evidence'
assert_contains "$doc" 'functions\.php:1604' 'the robots.txt admin disallow evidence'
assert_contains "$doc" 'functions\.php:918' 'the hreflang localized url evidence'
assert_contains "$doc" 'functions\.php:1015' 'the sitemap live-ids degradation evidence'
assert_contains "$doc" 'views/app-head\.php:56' 'the hardcoded og:locale evidence'
assert_contains "$doc" 'api/alerts\.php:757' 'the queued alert marker evidence'
assert_contains "$doc" 'api/market\.php:365' 'the market cache key evidence'
assert_contains "$doc" 'api/observability\.php:426' 'the blocking forward evidence'
assert_contains "$doc" 'api/ton\.php:699' 'the jetton metadata sanitization evidence'
assert_contains "$doc" 'api/premium\.php:981' 'the payment amount validation evidence'
assert_contains "$doc" 'api/search\.php:744' 'the search scoring cost evidence'
assert_contains "$doc" 'dev/js/src/initial\.js:900' 'the currencyFormat NaN evidence'
assert_contains "$doc" 'service-worker\.js:22' 'the precache versioned url evidence'
assert_contains "$doc" 'dev/js/src/components/currency-converter\.js:60' 'the converter parseFloat evidence'
assert_contains "$doc" 'tests/generated-bundle-check\.js:95' 'the bundle validation gap evidence'

assert_contains "$doc" '\| F1 \| P2 \| SEO \|' 'finding F1 summary'
assert_contains "$doc" '\| F4 \| P2 \| SEO/Sitemap \|' 'finding F4 summary'
assert_contains "$doc" '\| F5 \| P2 \| Accessibility \|' 'finding F5 summary'
assert_contains "$doc" '\| F8 \| P2 \| Reliability \|' 'finding F8 summary'
assert_contains "$doc" '\| F11 \| P3 \| Security \|' 'finding F11 summary'
assert_contains "$doc" '\| F14 \| P2 \| Frontend \|' 'finding F14 summary'
assert_contains "$doc" '\| F17 \| P3 \| CI \|' 'finding F17 summary'

assert_contains "$doc" '#254' 'the admin indexing follow-up issue'
assert_contains "$doc" '#255' 'the robots.txt admin follow-up issue'
assert_contains "$doc" '#256' 'the hreflang canonical follow-up issue'
assert_contains "$doc" '#257' 'the sitemap coverage follow-up issue'
assert_contains "$doc" '#258' 'the skip-link follow-up issue'
assert_contains "$doc" '#259' 'the icon-button follow-up issue'
assert_contains "$doc" '#260' 'the localized aria-label follow-up issue'
assert_contains "$doc" '#261' 'the queued alert follow-up issue'
assert_contains "$doc" '#262' 'the market cache key follow-up issue'
assert_contains "$doc" '#263' 'the observability forward follow-up issue'
assert_contains "$doc" '#264' 'the jetton metadata follow-up issue'
assert_contains "$doc" '#265' 'the payment amount follow-up issue'
assert_contains "$doc" '#266' 'the search scoring follow-up issue'
assert_contains "$doc" '#267' 'the currencyFormat follow-up issue'
assert_contains "$doc" '#268' 'the service-worker precache follow-up issue'
assert_contains "$doc" '#269' 'the currency converter follow-up issue'
assert_contains "$doc" '#270' 'the bundle validation follow-up issue'
assert_contains "$doc" 'stage-10-availability-audit' 'the Stage 10 label'
assert_contains "$doc" 'Definition of Done' 'the audit definition of done'

assert_contains README.md 'docs/code-logic-audit-2026-07-13\.md' 'the Stage 10 audit documentation link'
assert_contains package.json '"test:code-logic-audit-251"' 'the audit npm script'
assert_contains package.json 'test:code-logic-audit-251' 'the aggregate audit test wiring'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Code-logic audit #251 documentation check passed.'
