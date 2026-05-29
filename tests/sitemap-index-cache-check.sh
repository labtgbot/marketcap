#!/usr/bin/env sh
set -eu

# Asserts the scalability/caching guarantees for sitemap delivery (issue #168):
#
#   * `robots.txt` advertises the sitemap index as its primary `Sitemap:` line.
#   * `sitemap_index.xml` references section sitemaps and paginates large
#     sections automatically so no single file exceeds the configured URL cap.
#   * Section sitemaps and the index carry an `ETag` and a data-derived
#     `Last-Modified`, and answer conditional requests with `304 Not Modified`.
#   * `<lastmod>` for data-derived sections reflects live data freshness
#     (today, UTC) rather than source-file mtimes.
#
# The check runs against the `local` profile (bundled, offline-safe universe)
# with a deliberately small `TONBANKCARD_SITEMAP_MAX_URLS` so pagination is
# exercised deterministically without needing tens of thousands of URLs.

root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
log_dir="$root/test-logs"
log_file="$log_dir/sitemap-index-cache-check.log"
server_log="$log_dir/sitemap-index-cache-server.log"
host="${SITEMAP_CHECK_HOST:-127.0.0.1}"
port="${SITEMAP_CHECK_PORT:-8896}"
base_url="http://$host:$port"
server_pid=""
failures=0

# Small enough that the ~100-coin bundled universe splits into several pages.
max_urls="${SITEMAP_MAX_URLS:-20}"

mkdir -p "$log_dir"
: > "$log_file"
: > "$server_log"

log() {
    printf '%s\n' "$1" | tee -a "$log_file"
}

fail() {
    log "FAIL: $1"
    failures=$((failures + 1))
}

cleanup() {
    if [ -n "$server_pid" ] && kill -0 "$server_pid" 2>/dev/null; then
        kill "$server_pid" 2>/dev/null || true
        wait "$server_pid" 2>/dev/null || true
    fi
}
trap cleanup EXIT INT TERM

start_server() {
    log "Starting PHP server at $base_url (TONBANKCARD_SITEMAP_MAX_URLS=$max_urls)"
    (
        cd "$root"
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_BASE_URL="$base_url/" \
        TONBANKCARD_LOCAL_BASE_URL="$base_url/" \
        TONBANKCARD_CDN=false \
        TONBANKCARD_SITEMAP_MAX_URLS="$max_urls" \
        php -S "$host:$port" dev/php/router.php
    ) > "$server_log" 2>&1 &
    server_pid=$!
}

wait_for_server() {
    attempts=0
    while [ "$attempts" -lt 60 ]; do
        if php -r "\$status = @file_get_contents('$base_url/'); exit(FALSE === \$status ? 1 : 0);" >/dev/null 2>&1; then
            log "PHP server is ready."
            return
        fi
        attempts=$((attempts + 1))
        sleep 1
    done

    fail "PHP server did not become ready at $base_url. See $server_log"
}

fetch_path() {
    path=$1
    output=$2
    php -r "\$body = @file_get_contents('$base_url$path'); if (FALSE === \$body) { exit(1); } file_put_contents('$output', \$body);" || fail "Could not fetch $path"
}

# Probes a path and prints `status=`, `etag=`, `lastmod=` lines. Accepts an
# optional request header so conditional-request behaviour can be exercised.
# Arguments are passed via argv to avoid shell interpolation into PHP source.
http_probe() {
    php -r '
        $url = $argv[1];
        $request_header = isset( $argv[2] ) ? $argv[2] : "";
        $http = [ "method" => "GET", "ignore_errors" => true, "follow_location" => 0, "timeout" => 10 ];
        if ( "" !== $request_header ) { $http["header"] = $request_header; }
        $ctx = stream_context_create( [ "http" => $http ] );
        @file_get_contents( $url, false, $ctx );
        $status = 0; $etag = ""; $lastmod = "";
        if ( isset( $http_response_header ) ) {
            foreach ( $http_response_header as $line ) {
                if ( preg_match( "#^HTTP/\\S+\\s+(\\d{3})#", $line, $m ) ) { $status = (int) $m[1]; }
                if ( 0 === stripos( $line, "ETag:" ) ) { $etag = trim( substr( $line, 5 ) ); }
                if ( 0 === stripos( $line, "Last-Modified:" ) ) { $lastmod = trim( substr( $line, 14 ) ); }
            }
        }
        echo "status=$status\n";
        echo "etag=$etag\n";
        echo "lastmod=$lastmod\n";
    ' "$base_url$1" "${2:-}"
}

probe_field() {
    printf '%s\n' "$1" | grep "^$2=" | head -n1 | cut -d= -f2-
}

assert_contains() {
    file=$1
    pattern=$2
    description=$3
    if ! grep -Eq "$pattern" "$file"; then
        fail "$file does not include $description"
    fi
}

assert_well_formed() {
    file=$1
    description=$2
    php -r "libxml_use_internal_errors(true); \$d = new DOMDocument(); exit(\$d->load('$file') ? 0 : 1);" \
        || fail "$description is not well-formed XML"
}

count_loc() {
    grep -Eo '<loc>[^<]*</loc>' "$1" | wc -l | tr -d ' '
}

start_server
wait_for_server

index_file="$log_dir/index-cache-sitemap_index.xml"
robots_file="$log_dir/index-cache-robots.txt"

fetch_path "/sitemap_index.xml" "$index_file"
fetch_path "/robots.txt" "$robots_file"

assert_well_formed "$index_file" 'sitemap index'

# robots.txt must advertise the index as its first (primary) Sitemap line.
primary_sitemap=$(grep -i '^Sitemap:' "$robots_file" | head -n1 | tr -d '\r')
log "Primary robots sitemap line: $primary_sitemap"
case "$primary_sitemap" in
    *"/sitemap_index.xml") : ;;
    *) fail "robots.txt does not advertise the sitemap index first (got: $primary_sitemap)" ;;
esac

# Pagination: with the small URL cap the coin and exchange sections must split
# into numbered files referenced by the index.
assert_contains "$index_file" '<loc>[^<]*/sitemap-coins-1\.xml</loc>' 'first paginated coins sitemap'
assert_contains "$index_file" '<loc>[^<]*/sitemap-coins-2\.xml</loc>' 'second paginated coins sitemap'
assert_contains "$index_file" '<loc>[^<]*/sitemap-exchanges-1\.xml</loc>' 'first paginated exchanges sitemap'
assert_contains "$index_file" '<loc>[^<]*/sitemap-ton\.xml</loc>' 'TON sitemap'

# Every referenced section file must be well-formed and stay within the cap.
section_names=$(grep -Eo '<loc>[^<]*/sitemap-[A-Za-z0-9_-]+\.xml</loc>' "$index_file" \
    | sed -E 's#.*/(sitemap-[A-Za-z0-9_-]+\.xml)</loc>#\1#')
for name in $section_names; do
    section_file="$log_dir/index-cache-$name"
    fetch_path "/$name" "$section_file"
    assert_well_formed "$section_file" "$name"
    urls=$(count_loc "$section_file")
    if [ "$urls" -gt "$max_urls" ]; then
        fail "$name exposes $urls URLs, exceeding the $max_urls cap"
    fi
done
log "Verified $(printf '%s\n' "$section_names" | wc -l | tr -d ' ') section sitemaps stay within the $max_urls-URL cap."

# Conditional-request support: ETag + Last-Modified, and 304 revalidation.
probe=$(http_probe "/sitemap-coins-1.xml")
status=$(probe_field "$probe" status)
etag=$(probe_field "$probe" etag)
lastmod=$(probe_field "$probe" lastmod)
log "sitemap-coins-1.xml -> status=$status etag=$etag lastmod=$lastmod"

if [ "$status" != "200" ]; then
    fail "sitemap-coins-1.xml returned HTTP $status instead of 200"
fi
if [ -z "$etag" ]; then
    fail "sitemap-coins-1.xml is missing an ETag header"
fi
if [ -z "$lastmod" ]; then
    fail "sitemap-coins-1.xml is missing a Last-Modified header"
fi

# A matching If-None-Match must yield 304 Not Modified.
if [ -n "$etag" ]; then
    cond=$(http_probe "/sitemap-coins-1.xml" "If-None-Match: $etag")
    cond_status=$(probe_field "$cond" status)
    log "If-None-Match revalidation -> status=$cond_status"
    if [ "$cond_status" != "304" ]; then
        fail "matching If-None-Match returned HTTP $cond_status instead of 304"
    fi
fi

# A stale ETag must NOT be honoured (fresh 200 with a body).
stale=$(http_probe "/sitemap-coins-1.xml" 'If-None-Match: "deadbeef"')
stale_status=$(probe_field "$stale" status)
log "Stale If-None-Match -> status=$stale_status"
if [ "$stale_status" != "200" ]; then
    fail "non-matching If-None-Match returned HTTP $stale_status instead of 200"
fi

# A future If-Modified-Since must yield 304.
future=$(http_probe "/sitemap-coins-1.xml" 'If-Modified-Since: Tue, 01 Jan 2030 00:00:00 GMT')
future_status=$(probe_field "$future" status)
log "Future If-Modified-Since -> status=$future_status"
if [ "$future_status" != "304" ]; then
    fail "future If-Modified-Since returned HTTP $future_status instead of 304"
fi

# The index itself must also revalidate.
index_probe=$(http_probe "/sitemap_index.xml")
index_etag=$(probe_field "$index_probe" etag)
if [ -z "$index_etag" ]; then
    fail "sitemap_index.xml is missing an ETag header"
else
    index_cond=$(http_probe "/sitemap_index.xml" "If-None-Match: $index_etag")
    index_cond_status=$(probe_field "$index_cond" status)
    if [ "$index_cond_status" != "304" ]; then
        fail "sitemap_index.xml matching If-None-Match returned HTTP $index_cond_status instead of 304"
    fi
fi

# Data-derived <lastmod>: the coins section reflects live data freshness
# (today, UTC) rather than a source-file mtime. Accept yesterday too so the
# check is not flaky across the midnight boundary.
coins_lastmod=$(grep -Eo '<lastmod>[0-9]{4}-[0-9]{2}-[0-9]{2}</lastmod>' "$log_dir/index-cache-sitemap-coins-1.xml" | head -n1 | sed -E 's#</?lastmod>##g')
today=$(date -u +%Y-%m-%d)
yesterday=$(date -u -d 'yesterday' +%Y-%m-%d 2>/dev/null || date -u -v-1d +%Y-%m-%d 2>/dev/null || echo "$today")
log "Coins section <lastmod>=$coins_lastmod (today=$today)"
if [ -z "$coins_lastmod" ]; then
    fail "coins sitemap has no valid <lastmod> date"
elif [ "$coins_lastmod" != "$today" ] && [ "$coins_lastmod" != "$yesterday" ]; then
    fail "coins <lastmod> ($coins_lastmod) is not data-derived (expected $today or $yesterday)"
fi

if [ "$failures" -gt 0 ]; then
    log "Sitemap index/cache check failed. See $log_file and $server_log"
    exit 1
fi

log "Sitemap index/cache check passed. Logs: $log_file, $server_log"
