#!/usr/bin/env sh
set -eu

mkdir -p test-logs

log_file=test-logs/php-lint.log
files_file=test-logs/php-lint-files.txt

: > "$log_file"

find . \
    -path './.git' -prune -o \
    -path './node_modules' -prune -o \
    -path './test-logs' -prune -o \
    -type f \
    -name '*.php' \
    -print | sort > "$files_file"

failures=0

while IFS= read -r file; do
    if [ -z "$file" ]; then
        continue
    fi

    if ! php -l "$file" >> "$log_file" 2>&1; then
        printf '%s\n' "FAIL: PHP syntax check failed for $file" >&2
        failures=$((failures + 1))
    fi
done < "$files_file"

if [ "$failures" -gt 0 ]; then
    printf '%s\n' "PHP lint failed. See $log_file for details." >&2
    exit 1
fi

printf '%s\n' "PHP lint passed. Log: $log_file"
