#!/usr/bin/env sh
set -eu

failures=0
doc=docs/hosting-installation.md

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

assert_file "$doc"
assert_file README.md
assert_file .env.example
assert_file .htaccess
assert_file database/migrate.php
assert_file docs/runtime-configuration.md
assert_file docs/v2-database-schema-and-migrations.md

assert_contains "$doc" '^# TONBANKCARD Hosting Installation Guide$' 'the hosting installation guide title'
assert_contains "$doc" 'Issue: \[#82\]' 'the issue reference'
assert_contains "$doc" 'PHP 8\.1\+' 'the PHP 8.1+ requirement'
assert_contains "$doc" 'MySQL or MariaDB' 'the MySQL or MariaDB requirement'
assert_contains "$doc" '^## Prerequisites$' 'hosting prerequisites'
assert_contains "$doc" 'pdo_mysql' 'the PDO MySQL extension requirement'
assert_contains "$doc" 'curl' 'the curl extension requirement'
assert_contains "$doc" 'mod_rewrite' 'Apache rewrite guidance'
assert_contains "$doc" 'web root' 'web root placement guidance'
assert_contains "$doc" '^## Step-by-step Installation Plan$' 'the step-by-step installation plan'
assert_contains "$doc" 'git clone' 'source checkout instructions'
assert_contains "$doc" 'cp \.env\.example \.env' 'environment file creation'
assert_contains "$doc" 'TONBANKCARD_PROFILE=production' 'production profile configuration'
assert_contains "$doc" 'TONBANKCARD_PUBLIC_BASE_URL' 'public base URL configuration'
assert_contains "$doc" 'MYSQL_DSN' 'database DSN configuration'
assert_contains "$doc" 'MYSQL_USER' 'database user configuration'
assert_contains "$doc" 'MYSQL_PASSWORD' 'database password configuration'
assert_contains "$doc" 'MYSQL_SSL_CA' 'database TLS CA configuration'
assert_contains "$doc" 'MYSQL_SSL_VERIFY_SERVER_CERT' 'database TLS verification configuration'
assert_contains "$doc" 'CREATE DATABASE marketcap' 'database creation SQL'
assert_contains "$doc" 'php database/migrate\.php dry-run' 'migration dry-run command'
assert_contains "$doc" 'php database/migrate\.php up' 'migration apply command'
assert_contains "$doc" '/api/health' 'health check verification'
assert_contains "$doc" '/api/ready' 'readiness check verification'
assert_contains "$doc" 'cron' 'scheduled job guidance'
assert_contains "$doc" 'TONBANKCARD_ALERT_WORKER_TOKEN' 'alert worker token configuration'
assert_contains "$doc" 'TONBANKCARD_SEARCH_REFRESH_TOKEN' 'search refresh token configuration'
assert_contains "$doc" 'backup' 'backup guidance'
assert_contains "$doc" 'rollback' 'rollback guidance'
assert_contains "$doc" '^## Troubleshooting$' 'hosting troubleshooting'

assert_contains .htaccess '\(\^\|/\)\\\.' 'dotfile request protection'
assert_contains README.md 'docs/hosting-installation\.md' 'the hosting installation documentation link'
assert_contains README.md 'npm run test:hosting-installation' 'the hosting installation npm check'
assert_contains package.json '"test:hosting-installation"' 'the hosting installation npm script'
assert_contains package.json 'tests/hosting-installation-check\.sh' 'the hosting installation shell check'
assert_contains package.json 'test:hosting-installation' 'the aggregate hosting installation check'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Hosting installation check passed.'
