#!/usr/bin/env sh
set -eu

failures=0
dependabot_config=.github/dependabot.yml
pinned_failures_file=$(mktemp)

cleanup() {
    rm -f "$pinned_failures_file"
}
trap cleanup EXIT HUP INT TERM

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
        fail "$file does not include $description"
    fi
}

assert_file .github/workflows/ci.yml
assert_file "$dependabot_config"

for workflow in .github/workflows/*.yml .github/workflows/*.yaml; do
    if [ ! -f "$workflow" ]; then
        continue
    fi

    awk -v file="$workflow" '
        /^[[:space:]]*uses:[[:space:]]*/ {
            ref = $0
            sub(/^[[:space:]]*uses:[[:space:]]*/, "", ref)
            sub(/[[:space:]]+#.*/, "", ref)
            sub(/[[:space:]]+$/, "", ref)
            gsub(/^"/, "", ref)
            gsub(/"$/, "", ref)
            gsub(/^\047/, "", ref)
            gsub(/\047$/, "", ref)

            if (ref ~ /^actions\// || ref ~ /^\.\// || ref ~ /^docker:\/\//) {
                next
            }

            if (ref !~ /^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+(\/[^@[:space:]]+)?@[0-9a-f]{40}$/) {
                printf "%s:%d uses mutable third-party action ref %s\n", file, FNR, ref
            }
        }
    ' "$workflow" >> "$pinned_failures_file"
done

if [ -s "$pinned_failures_file" ]; then
    while IFS= read -r failure; do
        fail "$failure"
    done < "$pinned_failures_file"
fi

assert_contains "$dependabot_config" '^[[:space:]]*version:[[:space:]]*2[[:space:]]*$' 'Dependabot version 2 configuration'
assert_contains "$dependabot_config" 'package-ecosystem:[[:space:]]*"github-actions"' 'GitHub Actions version updates'
assert_contains "$dependabot_config" 'directory:[[:space:]]*"/"' 'the root workflow directory for GitHub Actions updates'
assert_contains "$dependabot_config" 'interval:[[:space:]]*"weekly"' 'a weekly GitHub Actions update schedule'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'GitHub Actions supply-chain check passed.'
