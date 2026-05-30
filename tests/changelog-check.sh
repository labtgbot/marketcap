#!/usr/bin/env sh
set -eu

failures=0

fail() {
    printf '%s\n' "FAIL: $1" >&2
    failures=$((failures + 1))
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

if [ ! -f CHANGELOG.md ]; then
    fail "Missing required file: CHANGELOG.md"
fi
if [ ! -f package.json ]; then
    fail "Missing required file: package.json"
fi

assert_contains CHANGELOG.md '^# Changelog$' 'the changelog title'
assert_contains CHANGELOG.md 'Keep a Changelog' 'the Keep a Changelog format reference'
assert_contains CHANGELOG.md 'Semantic Versioning' 'the Semantic Versioning reference'
assert_contains CHANGELOG.md '^## \[Unreleased\]$' 'an Unreleased section'
assert_contains docs/release-checklist.md 'Versioning and changelog' 'the SemVer release gate'

# The changelog must carry an entry for the current package.json version, or keep
# its in-progress notes under the Unreleased heading.
version=""
if [ -f CHANGELOG.md ] && [ -f package.json ]; then
    version=$(grep -E '"version"[[:space:]]*:' package.json | head -n1 | sed -E 's/.*"version"[[:space:]]*:[[:space:]]*"([^"]+)".*/\1/')
    if [ -z "$version" ]; then
        fail "package.json does not declare a version"
    elif ! grep -Eq "^## \[$version\]" CHANGELOG.md && ! grep -Eq '^## \[Unreleased\]' CHANGELOG.md; then
        fail "CHANGELOG.md has no entry for version $version and no Unreleased section"
    fi
fi

# Strict release-gate mode. On release branches/tags an in-progress Unreleased
# section is no longer enough: the changelog must already carry a dated, versioned
# section matching the release. The release version is taken from
# CHANGELOG_RELEASE_VERSION when set, otherwise from a `v`-prefixed tag exposed by
# GitHub Actions as GITHUB_REF_NAME (e.g. the `v2.0.0` tag). A leading `v` is
# tolerated either way, so `v2.0.0` and `2.0.0` are equivalent.
release_version="${CHANGELOG_RELEASE_VERSION:-}"
if [ -z "$release_version" ]; then
    ref_name="${GITHUB_REF_NAME:-}"
    case "$ref_name" in
        v[0-9]*) release_version="$ref_name" ;;
    esac
fi
release_version="${release_version#v}"

if [ -n "$release_version" ] && [ -f CHANGELOG.md ] && [ -f package.json ]; then
    if [ -n "$version" ] && [ "$release_version" != "$version" ]; then
        fail "Release version $release_version does not match package.json version $version"
    fi
    if ! grep -Eq "^## \[$release_version\] - [0-9]{4}-[0-9]{2}-[0-9]{2}" CHANGELOG.md; then
        fail "CHANGELOG.md has no dated release section '## [$release_version] - YYYY-MM-DD' for the release"
    fi
fi

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Changelog check passed.'
