#!/usr/bin/env sh
set -eu

failures=0
host=${WEB_ACCESS_HOST:-127.0.0.1}
port=${WEB_ACCESS_PORT:-8892}
base_url=${WEB_ACCESS_BASE_URL:-http://$host:$port}
log_dir=test-logs
server_log=$log_dir/web-access-control-php-server.log
server_pid=

fail() {
    printf '%s\n' "FAIL: $1" >&2
    failures=$((failures + 1))
}

cleanup() {
    if [ -n "$server_pid" ] && kill -0 "$server_pid" 2>/dev/null; then
        kill "$server_pid" 2>/dev/null || true
        wait "$server_pid" 2>/dev/null || true
    fi
}

http_status() {
    curl -s -o /dev/null -w '%{http_code}' "$base_url$1" || printf '000'
}

assert_status() {
    path=$1
    expected=$2
    description=$3
    status=$(http_status "$path")

    if [ "$status" != "$expected" ]; then
        fail "$description should return HTTP $expected for $path, got $status"
    fi
}

assert_forbidden_or_missing() {
    path=$1
    description=$2
    status=$(http_status "$path")

    case "$status" in
        403|404)
            ;;
        *)
            fail "$description should return HTTP 403/404 for $path, got $status"
            ;;
    esac
}

wait_for_server() {
    attempts=0

    while [ "$attempts" -lt 15 ]; do
        if [ "$(http_status '/')" = "200" ]; then
            return 0
        fi

        attempts=$((attempts + 1))
        sleep 1
    done

    fail "PHP development server did not become ready at $base_url; see $server_log"
}

mkdir -p "$log_dir"
: > "$server_log"
trap cleanup EXIT INT TERM

TONBANKCARD_PROFILE=local \
TONBANKCARD_BASE_URL="$base_url/" \
TONBANKCARD_LOCAL_BASE_URL="$base_url/" \
php -S "$host:$port" dev/php/router.php > "$server_log" 2>&1 &
server_pid=$!

wait_for_server

assert_forbidden_or_missing '/gecko-client.zip' 'root source archive'
assert_forbidden_or_missing '/database/migrate.php' 'database migration runner'
assert_forbidden_or_missing '/database/migrations/0001_v2_core_schema.up.sql' 'database schema migration'
assert_forbidden_or_missing '/docs/v2-security-privacy-compliance.md' 'internal documentation'
assert_forbidden_or_missing '/docs/screenshots/admin-panel.png' 'internal documentation screenshot'
assert_forbidden_or_missing '/README.md' 'repository markdown file'
assert_forbidden_or_missing '/tests/security-compliance-check.sh' 'test harness source'
assert_forbidden_or_missing '/experiments/repro-issue-104.php' 'experiment harness source'
assert_forbidden_or_missing '/dev/php/router.php' 'development router source'
assert_forbidden_or_missing '/install/index.php' 'hosting installer entrypoint'

assert_status '/assets/js/app.js' 200 'public JavaScript asset'
assert_status '/assets/css/style.css' 200 'public CSS asset'
assert_status '/assets/images/tonbankcard-logo.svg' 200 'public image asset'
assert_status '/site.webmanifest' 200 'public web manifest'
assert_status '/service-worker.js' 200 'public service worker'
assert_status '/markets' 200 'SPA history-mode route'
assert_status '/api/health' 200 'public API health endpoint'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Web access control check passed.'
