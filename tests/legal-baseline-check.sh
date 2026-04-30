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

assert_file NOTICE
assert_file docs/legal-license-inventory.md
assert_file docs/release-checklist.md
assert_file assets/vendor/roboto/fonts/LICENSE.txt
assert_file assets/vendor/roboto/fonts/COPYRIGHT.txt

assert_contains NOTICE 'TONBANKCARD Crypto Tracker' 'the TONBANKCARD product attribution'
assert_contains NOTICE 'Modifications by TONBANKCARD Team, 2026' 'the TONBANKCARD modification attribution'
assert_contains NOTICE 'Envato Market Regular License' 'the original Gecko Client license notice'
assert_contains NOTICE 'Original copyright notices must not be removed' 'notice preservation rules'

assert_contains docs/legal-license-inventory.md 'Gecko Client' 'Gecko Client provenance'
assert_contains docs/legal-license-inventory.md 'RunCoders' 'RunCoders copyright attribution'
assert_contains docs/legal-license-inventory.md 'Envato Market Regular License' 'Gecko Client license terms'
assert_contains docs/legal-license-inventory.md 'assets/vendor/axios' 'Axios vendor assets'
assert_contains docs/legal-license-inventory.md 'assets/vendor/echarts' 'ECharts vendor assets'
assert_contains docs/legal-license-inventory.md 'assets/vendor/lodash' 'Lodash vendor assets'
assert_contains docs/legal-license-inventory.md 'assets/vendor/mdi' 'Material Design Icons vendor assets'
assert_contains docs/legal-license-inventory.md 'assets/vendor/roboto' 'Roboto vendor assets'
assert_contains docs/legal-license-inventory.md 'assets/vendor/vue' 'Vue vendor assets'
assert_contains docs/legal-license-inventory.md 'assets/vendor/vue-router' 'Vue Router vendor assets'
assert_contains docs/legal-license-inventory.md 'assets/vendor/vuetify' 'Vuetify vendor assets'
assert_contains docs/legal-license-inventory.md 'assets/images' 'image asset provenance'
assert_contains docs/legal-license-inventory.md 'generated bundles' 'generated bundle notice preservation'

assert_contains docs/release-checklist.md 'Legal review checkpoint' 'a release legal review checkpoint'
assert_contains docs/release-checklist.md 'NOTICE' 'NOTICE verification before release'
assert_contains docs/release-checklist.md 'license inventory' 'license inventory verification before release'

gecko_header_count=$(
    (
        grep -R -l 'Envato Market Regular License' \
            functions.php index.php vendor.php constants.php \
            config templates views dev/js/tools 2>/dev/null || true
    ) | wc -l | tr -d ' '
)

if [ "$gecko_header_count" -lt 40 ]; then
    fail "Expected at least 40 Gecko Client source headers, found $gecko_header_count"
fi

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Legal baseline check passed.'
