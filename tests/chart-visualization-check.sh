#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-charting-and-market-visualization.md

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

assert_not_contains() {
    file=$1
    pattern=$2
    description=$3

    if [ ! -f "$file" ]; then
        fail "Cannot inspect missing file: $file"
        return
    fi

    if grep -Eq -- "$pattern" "$file"; then
        fail "$file still includes $description"
    fi
}

assert_file "$doc"
assert_file dev/js/src/initial.js
assert_file dev/js/src/components/currency-chart.js
assert_file templates/components/currency-chart.php
assert_file templates/routes/currency-chart.php
assert_file views/app-scripts.php
assert_file assets/css/style.css
assert_file tests/browser-smoke.js

assert_contains "$doc" '^# TONBANKCARD V2 Charting And Market Visualization$' 'the charting decision title'
assert_contains "$doc" 'Issue: \[#25\]' 'the issue reference'
assert_contains "$doc" 'ECharts' 'the ECharts rendering decision'
assert_contains "$doc" 'Chart.js' 'the Chart.js decision context'
assert_contains "$doc" 'lazy-load' 'the lazy-loading implementation note'
assert_contains "$doc" 'stale' 'stale data behavior'
assert_contains "$doc" 'accessible summary' 'accessible chart summary behavior'

assert_contains views/app-scripts.php 'echartsUrl' 'frontend ECharts asset URL'
assert_not_contains views/app-scripts.php '<script src="[^"]*echarts' 'an eager ECharts script tag'
assert_contains dev/js/src/initial.js 'loadECharts' 'the lazy ECharts loader'
assert_contains dev/js/src/echarts-dark.js 'registerEChartsDarkTheme' 'deferred dark theme registration'

assert_contains templates/routes/currency-chart.php "'volume'" 'volume chart view option'
assert_contains templates/routes/currency-chart.php "'dominance'" 'dominance chart view option'
assert_contains templates/routes/currency-chart.php "'relativePerformance'" 'relative-performance chart view option'

assert_contains dev/js/src/components/currency-chart.js 'relativePerformance' 'relative-performance calculations'
assert_contains dev/js/src/components/currency-chart.js 'dominance' 'dominance calculations'
assert_contains dev/js/src/components/currency-chart.js 'CoinGecko\.metaGet' 'chart freshness metadata lookup'
assert_contains dev/js/src/components/currency-chart.js 'chartSummary' 'accessible chart summary generation'
assert_contains dev/js/src/components/currency-chart.js 'loadECharts' 'lazy chart runtime usage'

assert_contains templates/components/currency-chart.php 'tbc-skeleton' 'skeleton chart loading state'
assert_contains templates/components/currency-chart.php 'Market chart data is stale' 'stale chart message'
assert_contains templates/components/currency-chart.php 'role="img"' 'chart image role'
assert_contains templates/components/currency-chart.php 'aria-describedby' 'chart summary association'
assert_contains templates/components/currency-chart.php 'chartSummary' 'screen reader chart summary'
assert_contains templates/components/currency-chart.php 'Retry' 'chart failure retry control'

assert_contains assets/css/style.css '\.gc-currency-chart-shell' 'stable chart shell dimensions'
assert_contains assets/css/style.css '\.gc-currency-chart-summary' 'chart summary stat styling'
assert_contains assets/css/style.css 'max-width: 599px' 'mobile chart fit rules'

assert_contains tests/browser-smoke.js 'checkCoinChartVisualization' 'browser chart visualization coverage'
assert_contains tests/browser-smoke.js 'checkCoinChartFailureFallback' 'browser chart failure fallback coverage'

assert_contains package.json '"test:charts"' 'the chart visualization npm script'
assert_contains package.json 'test:charts' 'the aggregate chart visualization check'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Chart visualization check passed.'
