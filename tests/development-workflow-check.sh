#!/usr/bin/env sh
set -eu

failures=0

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

assert_file README.md
assert_file package.json
assert_file dev/php/router.php
assert_file tests/php-lint.sh
assert_file tests/generated-bundle-check.js
assert_file tests/browser-smoke.js
assert_file .github/workflows/ci.yml

assert_contains README.md 'php -S 127\.0\.0\.1:8888 dev/php/router\.php' 'the local PHP router startup command'
assert_contains README.md 'http://127\.0\.0\.1:8888/' 'the local home URL'
assert_contains README.md 'http://127\.0\.0\.1:8888/currency/bitcoin' 'the local coin detail smoke URL'
assert_contains README.md 'http://127\.0\.0\.1:8888/exchanges' 'the local exchanges smoke URL'
assert_contains README.md 'npm run lint:php' 'the PHP lint command'
assert_contains README.md 'npm run validate:bundle' 'the generated bundle validation command'
assert_contains README.md 'npm run test:smoke' 'the browser smoke test command'
assert_contains README.md 'test-logs' 'where check logs are written'
assert_contains README.md 'Troubleshooting' 'local troubleshooting guidance'

assert_contains package.json '"lint:php"' 'the PHP lint npm script'
assert_contains package.json '"validate:bundle"' 'the generated bundle npm script'
assert_contains package.json '"test:smoke"' 'the browser smoke npm script'
assert_contains package.json '"test"' 'the aggregate local check script'

assert_contains .github/workflows/ci.yml 'npm ci' 'dependency installation in CI'
assert_contains .github/workflows/ci.yml 'npx playwright install --with-deps chromium' 'browser installation in CI'
assert_contains .github/workflows/ci.yml 'npm test' 'the aggregate local checks in CI'
assert_contains .github/workflows/ci.yml 'test-logs' 'CI log artifact collection'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Development workflow check passed.'
