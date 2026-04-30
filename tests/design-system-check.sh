#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-responsive-design-system.md

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
assert_file assets/css/style.css
assert_file config/vuetify.php
assert_file dev/js/src/initial.js
assert_file dev/js/src/vm.js

assert_contains "$doc" '^# TONBANKCARD V2 Responsive Design System$' 'the responsive design system title'
assert_contains "$doc" 'Issue: \[#19\]' 'the issue reference'
assert_contains "$doc" 'TONBANKCARD brand tokens' 'TONBANKCARD brand token documentation'
assert_contains "$doc" 'Telegram theme parameters' 'Telegram theme parameter documentation'
assert_contains "$doc" 'Semantic market colors' 'semantic market colors'
assert_contains "$doc" 'Typography' 'typography rules'
assert_contains "$doc" 'Spacing' 'spacing rules'
assert_contains "$doc" 'Tables' 'table rules'
assert_contains "$doc" 'Cards' 'card rules'
assert_contains "$doc" 'Buttons' 'button rules'
assert_contains "$doc" 'Tabs' 'tab rules'
assert_contains "$doc" 'Forms' 'form rules'
assert_contains "$doc" 'Charts' 'chart rules'
assert_contains "$doc" 'Skeleton states' 'skeleton state rules'
assert_contains "$doc" 'safe-area' 'safe-area rules'
assert_contains "$doc" 'bottom bar' 'bottom-bar rules'
assert_contains "$doc" '360px' '360px mobile fit requirement'
assert_contains "$doc" 'desktop' 'desktop density requirement'
assert_contains "$doc" 'focus-visible' 'keyboard focus state'
assert_contains "$doc" 'screen reader' 'screen reader labeling guidance'
assert_contains "$doc" 'prefers-reduced-motion' 'reduced motion behavior'

assert_contains README.md 'docs/v2-responsive-design-system\.md' 'the design system documentation link'
assert_contains package.json '"test:design-system"' 'the design system npm script'
assert_contains package.json 'test:design-system' 'the aggregate design system check'

assert_contains config/vuetify.php '#1BB2DA|#1bb2da' 'the TONBANKCARD primary brand color'
assert_contains config/vuetify.php '#12A978|#12a978' 'positive market semantic color'
assert_contains config/vuetify.php '#D84A4A|#d84a4a' 'negative market semantic color'

assert_contains views/app-head.php 'viewport-fit=cover' 'Telegram safe-area viewport support'
assert_contains views/app-head.php 'color-scheme' 'light and dark browser color scheme metadata'

assert_contains assets/css/style.css '--tbc-brand-ton' 'TONBANKCARD brand CSS token'
assert_contains assets/css/style.css '--tbc-market-up' 'positive market CSS token'
assert_contains assets/css/style.css '--tbc-market-down' 'negative market CSS token'
assert_contains assets/css/style.css '--tbc-tg-bg' 'Telegram background CSS token'
assert_contains assets/css/style.css 'env\(safe-area-inset-bottom' 'safe-area bottom spacing'
assert_contains assets/css/style.css '\.tbc-bottom-bar|\.v-bottom-navigation' 'bottom bar safe-area rule'
assert_contains assets/css/style.css 'focus-visible' 'keyboard focus styles'
assert_contains assets/css/style.css '\.tbc-sr-only' 'screen-reader-only helper'
assert_contains assets/css/style.css '\.tbc-skeleton' 'skeleton loading state'
assert_contains assets/css/style.css 'prefers-reduced-motion' 'reduced motion media query'
assert_contains assets/css/style.css 'max-width: 360px' '360px mobile breakpoint'

assert_contains dev/js/src/initial.js 'telegramThemeParamMap' 'Telegram theme parameter map'
assert_contains dev/js/src/initial.js 'themeChanged' 'Telegram themeChanged event handling'
assert_contains dev/js/src/initial.js 'setHeaderColor' 'Telegram native header color sync'
assert_contains dev/js/src/vm.js 'syncTelegramTheme' 'Vue theme sync with Telegram'
assert_contains dev/js/src/vm.js 'applyTelegramVuetifyTheme' 'Vuetify theme sync with Telegram'

assert_contains templates/components/search-bar.php 'aria-label' 'search screen reader label'
assert_contains views/app-top-bar.php 'aria-label' 'top-bar button screen reader labels'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Design system check passed.'
