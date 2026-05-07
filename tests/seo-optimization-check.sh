#!/usr/bin/env sh
set -eu

root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
log_dir="$root/test-logs"
log_file="$log_dir/seo-optimization-check.log"
server_log="$log_dir/seo-optimization-server.log"
host="${SEO_CHECK_HOST:-127.0.0.1}"
port="${SEO_CHECK_PORT:-8893}"
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

robots_txt="$log_dir/robots.txt"
sitemap_xml="$log_dir/sitemap.xml"
home_html="$log_dir/seo-home.html"
coin_html="$log_dir/seo-coin.html"
currency_html="$log_dir/seo-currency-alias.html"
derivatives_html="$log_dir/seo-derivatives.html"
finance_html="$log_dir/seo-finance-platforms.html"
watchlist_html="$log_dir/seo-watchlist.html"
settings_html="$log_dir/seo-settings.html"

fetch_path "/robots.txt" "$robots_txt"
fetch_path "/sitemap.xml" "$sitemap_xml"
fetch_path "/" "$home_html"
fetch_path "/coins/bitcoin" "$coin_html"
fetch_path "/currency/bitcoin" "$currency_html"
fetch_path "/derivatives" "$derivatives_html"
fetch_path "/finance-platforms" "$finance_html"
fetch_path "/watchlist" "$watchlist_html"
fetch_path "/settings" "$settings_html"

assert_status_ok "/robots.txt"
assert_status_ok "/sitemap.xml"
assert_status_ok "/coins/bitcoin"
assert_status_ok "/currency/bitcoin"
assert_status_ok "/derivatives"
assert_status_ok "/finance-platforms"
assert_status_ok "/watchlist"
assert_status_ok "/settings"

assert_contains "$robots_txt" '^User-agent: \*$' 'robots user agent rule'
assert_contains "$robots_txt" '^Disallow: /api/$' 'API crawl disallow rule'
assert_contains "$robots_txt" '^Disallow: /admin/$' 'admin crawl disallow rule'
assert_contains "$robots_txt" '^Clean-param: utm_source&utm_medium&utm_campaign&utm_term&utm_content&yclid&gclid&fbclid /$' 'Yandex tracking parameter cleanup rule'
assert_contains "$robots_txt" "^Sitemap: $base_url/sitemap\\.xml$" 'sitemap discovery URL'

assert_contains "$sitemap_xml" '<urlset xmlns="http://www\.sitemaps\.org/schemas/sitemap/0\.9">' 'XML sitemap urlset'
assert_contains "$sitemap_xml" '<lastmod>[0-9]{4}-[0-9]{2}-[0-9]{2}</lastmod>' 'sitemap lastmod values'
assert_contains "$sitemap_xml" "<loc>$base_url/</loc>" 'home sitemap URL'
assert_contains "$sitemap_xml" "<loc>$base_url/markets</loc>" 'markets sitemap URL'
assert_contains "$sitemap_xml" "<loc>$base_url/coins/bitcoin</loc>" 'Bitcoin canonical coin URL'
assert_contains "$sitemap_xml" "<loc>$base_url/coins/ethereum</loc>" 'Ethereum canonical coin URL'
assert_contains "$sitemap_xml" "<loc>$base_url/coins/toncoin</loc>" 'Toncoin canonical coin URL'
assert_contains "$sitemap_xml" "<loc>$base_url/derivatives</loc>" 'derivatives sitemap URL'
assert_contains "$sitemap_xml" "<loc>$base_url/finance-platforms</loc>" 'finance platforms sitemap URL'
assert_contains "$sitemap_xml" "<loc>$base_url/watchlist</loc>" 'watchlist sitemap URL'
assert_not_contains "$sitemap_xml" '<loc>[^<]*/currency/bitcoin</loc>' 'duplicate legacy currency URL'
assert_not_contains "$sitemap_xml" '<loc>[^<]*/settings</loc>' 'private settings sitemap URL'

assert_contains "$home_html" '<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1"' 'advanced robots directives'
assert_contains "$home_html" '<meta property="og:image:alt" content="TONBANKCARD Crypto Tracker market dashboard preview"' 'Open Graph image alt text'
assert_contains "$home_html" '<meta property="og:image:width" content="1200"' 'Open Graph image width'
assert_contains "$home_html" '<meta name="twitter:image:alt" content="TONBANKCARD Crypto Tracker market dashboard preview"' 'Twitter image alt text'
assert_contains "$home_html" '"@type":"Organization"' 'organization structured data'
assert_contains "$home_html" '"@type":"BreadcrumbList"' 'breadcrumb structured data'
assert_contains "$home_html" '"@type":"WebApplication"' 'web application structured data'

assert_contains "$coin_html" '<link rel="canonical" href="http://127\.0\.0\.1:8893/coins/bitcoin"' 'canonical coin URL'
assert_contains "$coin_html" '"@type":"BreadcrumbList"' 'coin breadcrumb structured data'
assert_contains "$coin_html" '"name":"Bitcoin"' 'coin breadcrumb label'

assert_contains "$currency_html" '<link rel="canonical" href="http://127\.0\.0\.1:8893/coins/bitcoin"' 'legacy currency canonical URL'
assert_contains "$derivatives_html" '<title>Crypto Derivatives - TONBANKCARD Crypto Tracker' 'derivatives route title'
assert_contains "$finance_html" '<title>Crypto Finance Platforms - TONBANKCARD Crypto Tracker' 'finance route title'
assert_contains "$watchlist_html" '<title>Crypto Watchlist - TONBANKCARD Crypto Tracker' 'watchlist route title'
assert_contains "$watchlist_html" '<link rel="canonical" href="http://127\.0\.0\.1:8893/watchlist"' 'watchlist canonical URL'
assert_contains "$settings_html" '<meta name="robots" content="noindex,nofollow"' 'settings noindex policy'

if [ "$failures" -gt 0 ]; then
    log "SEO optimization check failed. See $log_file and $server_log"
    exit 1
fi

log "SEO optimization check passed. Logs: $log_file, $server_log"
