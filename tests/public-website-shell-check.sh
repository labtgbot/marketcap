#!/usr/bin/env sh
set -eu

root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
log_dir="$root/test-logs"
log_file="$log_dir/public-website-shell-check.log"
server_log="$log_dir/public-website-shell-server.log"
host="${PUBLIC_SHELL_HOST:-127.0.0.1}"
port="${PUBLIC_SHELL_PORT:-8891}"
base_url="http://$host:$port"
server_pid=""
failures=0

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
    log "Starting PHP server at $base_url"
    (
        cd "$root"
        TONBANKCARD_PROFILE=local \
        TONBANKCARD_BASE_URL="$base_url/" \
        TONBANKCARD_LOCAL_BASE_URL="$base_url/" \
        TONBANKCARD_CDN=false \
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

assert_contains() {
    file=$1
    pattern=$2
    description=$3

    if ! grep -Eq "$pattern" "$file"; then
        fail "$file does not include $description"
    fi
}

assert_not_contains() {
    file=$1
    pattern=$2
    description=$3

    if grep -Eq "$pattern" "$file"; then
        fail "$file unexpectedly includes $description"
    fi
}

assert_status_ok() {
    path=$1
    code=$(php -r "\$headers = @get_headers('$base_url$path'); if (! \$headers) { echo '000'; exit; } if (preg_match('/ ([0-9]{3}) /', \$headers[0], \$m)) { echo \$m[1]; } else { echo '000'; }")
    if [ "$code" != "200" ]; then
        fail "$path returned HTTP $code instead of 200"
    fi
}

start_server
wait_for_server

home_html="$log_dir/public-shell-home.html"
markets_html="$log_dir/public-shell-markets.html"
coin_html="$log_dir/public-shell-coin.html"
ton_html="$log_dir/public-shell-ton.html"
screener_html="$log_dir/public-shell-screener.html"
support_html="$log_dir/public-shell-support.html"
robots_txt="$log_dir/public-shell-robots.txt"
sitemap_xml="$log_dir/public-shell-sitemap.xml"

fetch_path "/" "$home_html"
fetch_path "/markets" "$markets_html"
fetch_path "/coins/bitcoin" "$coin_html"
fetch_path "/ton" "$ton_html"
fetch_path "/screener" "$screener_html"
fetch_path "/support" "$support_html"
fetch_path "/robots.txt" "$robots_txt"
fetch_path "/sitemap.xml" "$sitemap_xml"

assert_status_ok "/"
assert_status_ok "/markets"
assert_status_ok "/coins/bitcoin"
assert_status_ok "/ton"
assert_status_ok "/screener"
assert_status_ok "/support"
assert_status_ok "/robots.txt"
assert_status_ok "/sitemap.xml"

assert_contains "$home_html" '<title>Market Overview - TONBANKCARD Crypto Tracker' 'home route title'
assert_contains "$home_html" '<link rel="canonical" href="http://127\.0\.0\.1:8891/"' 'home canonical URL'
assert_contains "$home_html" '<link rel="alternate" hreflang="en" href="http://127\.0\.0\.1:8891/"' 'English alternate URL'
assert_contains "$home_html" '<link rel="alternate" hreflang="x-default" href="http://127\.0\.0\.1:8891/"' 'default alternate URL'
assert_contains "$home_html" '<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1"' 'indexable robots policy'
assert_contains "$home_html" '<meta property="og:url" content="http://127\.0\.0\.1:8891/"' 'home Open Graph URL'
assert_contains "$home_html" '<meta property="og:locale" content="en_US"' 'Open Graph locale'
assert_contains "$home_html" '<meta name="twitter:card" content="summary_large_image"' 'large Twitter card metadata'
assert_contains "$home_html" '<link rel="manifest" href="http://127\.0\.0\.1:8891/site\.webmanifest' 'web app manifest link'
assert_contains "$home_html" 'application/ld\+json' 'JSON-LD structured data'
assert_contains "$home_html" '"@type":"WebSite"' 'website structured data'
assert_contains "$home_html" '"@type":"Organization"' 'organization structured data'
assert_contains "$home_html" '"@type":"SiteNavigationElement"' 'site navigation structured data'
assert_contains "$home_html" 'public-web-navigation' 'public website navigation shell'
assert_not_contains "$home_html" 'telegram-webapp-navigation|Telegram.WebApp|telegram-adapter' 'Telegram-only controls in normal browser shell'

assert_contains "$markets_html" '<title>Crypto Markets - TONBANKCARD Crypto Tracker' 'markets route title'
assert_contains "$markets_html" '<link rel="canonical" href="http://127\.0\.0\.1:8891/markets"' 'markets canonical URL'
assert_contains "$markets_html" '"path":"\\/markets"' 'markets route in frontend registry'

assert_contains "$coin_html" '<title>Bitcoin Price, Chart, and Market Data - TONBANKCARD Crypto Tracker' 'coin route title'
assert_contains "$coin_html" '<link rel="canonical" href="http://127\.0\.0\.1:8891/coins/bitcoin"' 'coin canonical URL'
assert_contains "$coin_html" '<meta property="og:type" content="article"' 'coin Open Graph article type'
assert_contains "$coin_html" '"@type":"FinancialProduct"' 'coin structured data'
assert_contains "$coin_html" '"@type":"BreadcrumbList"' 'coin breadcrumb structured data'
assert_contains "$coin_html" '"path":"\\/coins\\/:id"' 'canonical coin route in frontend registry'

assert_contains "$ton_html" '<title>TON Ecosystem - TONBANKCARD Crypto Tracker' 'TON route title'
assert_contains "$ton_html" '<link rel="canonical" href="http://127\.0\.0\.1:8891/ton"' 'TON route canonical URL'
assert_contains "$ton_html" 'route-ton' 'TON route template'

assert_contains "$screener_html" '<title>Crypto Screener - TONBANKCARD Crypto Tracker' 'screener route title'
assert_contains "$screener_html" '<link rel="canonical" href="http://127\.0\.0\.1:8891/screener"' 'screener route canonical URL'
assert_contains "$screener_html" 'route-screener' 'screener route template'

assert_contains "$support_html" '<title>Support - TONBANKCARD Crypto Tracker' 'support route title'
assert_contains "$support_html" '<link rel="canonical" href="http://127\.0\.0\.1:8891/support"' 'support route canonical URL'
assert_contains "$support_html" 'route-support' 'support route template'

assert_contains "$robots_txt" '^User-agent: \*$' 'robots user agent directive'
assert_contains "$robots_txt" '^Allow: /$' 'robots allow directive'
assert_contains "$robots_txt" '^Sitemap: http://127\.0\.0\.1:8891/sitemap\.xml$' 'sitemap pointer in robots.txt'

assert_contains "$sitemap_xml" '<urlset xmlns="http://www\.sitemaps\.org/schemas/sitemap/0\.9">' 'sitemap urlset'
assert_contains "$sitemap_xml" '<loc>http://127\.0\.0\.1:8891/markets</loc>' 'markets sitemap URL'
assert_contains "$sitemap_xml" '<loc>http://127\.0\.0\.1:8891/ton</loc>' 'TON sitemap URL'
assert_contains "$sitemap_xml" '<changefreq>hourly</changefreq>' 'sitemap change frequency'
assert_not_contains "$sitemap_xml" ':id' 'dynamic route placeholders in sitemap'

if [ "$failures" -gt 0 ]; then
    log "Public website shell check failed. See $log_file and $server_log"
    exit 1
fi

log "Public website shell check passed. Logs: $log_file, $server_log"
