#!/usr/bin/env sh
# Exercises the strict release-gate mode added to tests/changelog-check.sh.
#
#   1. Default run (no release context) must pass with the current Unreleased-only
#      changelog.
#   2. A release tag (GITHUB_REF_NAME=v2.0.0) must pass because the changelog
#      already carries a dated `## [2.0.0]` section.
#   3. A release tag for a version with no dated section (v9.9.9) must FAIL.
#   4. A release version that disagrees with package.json must FAIL.
set -u

repo_root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$repo_root" || exit 1

pass=0
fail=0

expect() {
    description=$1
    expected=$2 # 0 = should pass, 1 = should fail
    shift 2
    if env "$@" sh tests/changelog-check.sh >/dev/null 2>&1; then
        actual=0
    else
        actual=1
    fi
    if [ "$actual" -eq "$expected" ]; then
        printf 'ok   - %s\n' "$description"
        pass=$((pass + 1))
    else
        printf 'NOT OK - %s (expected exit %d, got %d)\n' "$description" "$expected" "$actual"
        fail=$((fail + 1))
    fi
}

expect "default run passes (Unreleased section is enough)" 0
expect "release tag v2.0.0 passes (dated section exists)" 0 GITHUB_REF_NAME=v2.0.0
expect "release tag v9.9.9 fails (no dated section)" 1 GITHUB_REF_NAME=v9.9.9
expect "explicit version 2.0.0 passes" 0 CHANGELOG_RELEASE_VERSION=2.0.0
expect "mismatched release version 9.9.9 fails" 1 CHANGELOG_RELEASE_VERSION=9.9.9
expect "non-release ref (main) passes" 0 GITHUB_REF_NAME=main

printf '\n%d passed, %d failed\n' "$pass" "$fail"
[ "$fail" -eq 0 ]
