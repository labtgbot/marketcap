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
        YANDEX_METRICA_ENABLED=true \
        YANDEX_METRICA_COUNTER_ID=109107032 \
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
alerts_html="$log_dir/public-shell-alerts.html"
support_html="$log_dir/public-shell-support.html"
admin_html="$log_dir/public-shell-admin.html"
robots_txt="$log_dir/public-shell-robots.txt"
sitemap_xml="$log_dir/public-shell-sitemap.xml"

fetch_path "/" "$home_html"
fetch_path "/markets" "$markets_html"
fetch_path "/coins/bitcoin" "$coin_html"
fetch_path "/ton" "$ton_html"
fetch_path "/screener" "$screener_html"
fetch_path "/alerts" "$alerts_html"
fetch_path "/support" "$support_html"
fetch_path "/admin" "$admin_html"
fetch_path "/robots.txt" "$robots_txt"
fetch_path "/sitemap.xml" "$sitemap_xml"

assert_status_ok "/"
assert_status_ok "/markets"
assert_status_ok "/coins/bitcoin"
assert_status_ok "/ton"
assert_status_ok "/screener"
assert_status_ok "/alerts"
assert_status_ok "/support"
assert_status_ok "/admin"
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
assert_contains "$home_html" 'mc\.yandex\.ru/metrika/tag\.js' 'Yandex Metrica script on the home route'
assert_contains "$home_html" 'ym\(109107032' 'Yandex Metrica initialization on the home route'
assert_contains "$home_html" 'mc\.yandex\.ru/watch/109107032' 'Yandex Metrica noscript pixel on the home route'
assert_contains "$home_html" 'public-web-navigation' 'public website navigation shell'
assert_not_contains "$home_html" 'telegram-webapp-navigation|Telegram.WebApp|telegram-adapter' 'Telegram-only controls in normal browser shell'

assert_contains "$markets_html" '<title>Crypto Markets - TONBANKCARD Crypto Tracker' 'markets route title'
assert_contains "$markets_html" '<link rel="canonical" href="http://127\.0\.0\.1:8891/markets"' 'markets canonical URL'
assert_contains "$markets_html" '"path":"\\/markets"' 'markets route in frontend registry'
assert_contains "$markets_html" 'mc\.yandex\.ru/metrika/tag\.js' 'Yandex Metrica script on the markets route'

assert_contains "$coin_html" '<title>Bitcoin Price, Chart, and Market Data - TONBANKCARD Crypto Tracker' 'coin route title'
assert_contains "$coin_html" '<link rel="canonical" href="http://127\.0\.0\.1:8891/coins/bitcoin"' 'coin canonical URL'
assert_contains "$coin_html" '<meta property="og:type" content="article"' 'coin Open Graph article type'
assert_contains "$coin_html" '"@type":"FinancialProduct"' 'coin structured data'
assert_contains "$coin_html" '"@type":"BreadcrumbList"' 'coin breadcrumb structured data'
assert_contains "$coin_html" '"path":"\\/coins\\/:id"' 'canonical coin route in frontend registry'
assert_contains "$coin_html" 'mc\.yandex\.ru/metrika/tag\.js' 'Yandex Metrica script on the coin store route'

assert_contains "$ton_html" '<title>TON Ecosystem - TONBANKCARD Crypto Tracker' 'TON route title'
assert_contains "$ton_html" '<link rel="canonical" href="http://127\.0\.0\.1:8891/ton"' 'TON route canonical URL'
assert_contains "$ton_html" 'route-ton' 'TON route template'
assert_contains "$ton_html" 'mc\.yandex\.ru/metrika/tag\.js' 'Yandex Metrica script on the TON Ecosystem route'

assert_contains "$screener_html" '<title>Crypto Screener - TONBANKCARD Crypto Tracker' 'screener route title'
assert_contains "$screener_html" '<link rel="canonical" href="http://127\.0\.0\.1:8891/screener"' 'screener route canonical URL'
assert_contains "$screener_html" 'route-screener' 'screener route template'
assert_contains "$screener_html" 'mc\.yandex\.ru/metrika/tag\.js' 'Yandex Metrica script on the screener route'

assert_contains "$alerts_html" '<title>Smart Alerts - TONBANKCARD Crypto Tracker' 'alerts route title'
assert_contains "$alerts_html" '<link rel="canonical" href="http://127\.0\.0\.1:8891/alerts"' 'alerts route canonical URL'
assert_contains "$alerts_html" 'route-alerts' 'alerts route template'

assert_contains "$support_html" '<title>Support - TONBANKCARD Crypto Tracker' 'support route title'
assert_contains "$support_html" '<link rel="canonical" href="http://127\.0\.0\.1:8891/support"' 'support route canonical URL'
assert_contains "$support_html" 'route-support' 'support route template'

assert_not_contains "$admin_html" 'mc\.yandex\.ru/metrika/tag\.js' 'Yandex Metrica script on the admin route'
assert_not_contains "$admin_html" 'ym\(109107032' 'Yandex Metrica initialization on the admin route'

YANDEX_METRICA_HTML="$markets_html" node <<'NODE'
const fs = require('fs');
const vm = require('vm');

const html = fs.readFileSync(process.env.YANDEX_METRICA_HTML, 'utf8');
const match = html.match(/<!-- Yandex\.Metrika counter -->\s*<script\b(?=[^>]*\bnonce=)[^>]*>([\s\S]*?)<\/script>/);
if (!match) {
  throw new Error('Yandex Metrica script block with CSP nonce was not found');
}

const insertedScripts = [];
const firstScript = { parentNode: { insertBefore: (script) => insertedScripts.push(script) } };
const document = {
  referrer: 'https://example.test/referrer',
  scripts: [firstScript],
  createElement: () => ({}),
  getElementsByTagName: () => [firstScript],
};
const context = {
  window: {},
  document,
  location: { href: 'http://127.0.0.1:8891/markets' },
  Date,
};
context.window.document = document;
context.window.location = context.location;

vm.runInNewContext(match[1], context);

if (insertedScripts.length !== 1) {
  throw new Error(`Expected one inserted Yandex Metrica script, got ${insertedScripts.length}`);
}
if (insertedScripts[0].src !== 'https://mc.yandex.ru/metrika/tag.js') {
  throw new Error(`Yandex Metrica script should use canonical tag.js URL, got ${insertedScripts[0].src}`);
}
if (insertedScripts[0].async !== 1) {
  throw new Error('Yandex Metrica script should load asynchronously');
}
if (!context.window.ym || !Array.isArray(context.window.ym.a)) {
  throw new Error('Yandex Metrica loader did not queue ym calls');
}

const initCall = context.window.ym.a.find((args) => args[0] === 109107032 && args[1] === 'init');
if (!initCall) {
  throw new Error('Yandex Metrica init call was not queued for counter 109107032');
}

const options = initCall[2] || {};
for (const option of ['trackLinks', 'accurateTrackBounce', 'webvisor', 'clickmap', 'defer']) {
  if (options[option] !== true) {
    throw new Error(`Yandex Metrica init option ${option} should be true`);
  }
}
const hitCall = context.window.ym.a.find((args) => args[0] === 109107032 && args[1] === 'hit');
if (!hitCall) {
  throw new Error('Yandex Metrica initial pageview hit was not queued');
}
if (hitCall[2] !== context.location.href) {
  throw new Error(`Yandex Metrica hit URL should match location.href, got ${hitCall[2]}`);
}
const hitOptions = hitCall[3] || {};
if (hitOptions.referer !== document.referrer) {
  throw new Error(`Yandex Metrica hit referer should match document.referrer, got ${hitOptions.referer}`);
}
if (hitOptions.title !== undefined && typeof hitOptions.title !== 'string') {
  throw new Error('Yandex Metrica hit title should be a string when provided');
}
if (!context.window.tonbankcardYandexMetrica || context.window.tonbankcardYandexMetrica.lastTrackedUrl !== context.location.href) {
  throw new Error('Yandex Metrica browser state should remember the initial tracked URL');
}
NODE

node <<'NODE'
const fs = require('fs');
const vm = require('vm');

const source = fs.readFileSync('dev/js/src/router.js', 'utf8');
const afterEachHandlers = [];
class VueRouter {
  constructor() {
    this.afterEach = (handler) => afterEachHandlers.push(handler);
  }
}
const ymCalls = [];
const context = {
  window: {
    location: { href: 'http://127.0.0.1:8891/markets' },
    document: { referrer: 'https://example.test/referrer', title: 'Markets' },
    tonbankcardYandexMetrica: {
      enabled: true,
      counterId: 109107032,
      lastTrackedUrl: 'http://127.0.0.1:8891/',
    },
    ym: (...args) => ymCalls.push(args),
    setTimeout: (callback) => callback(),
  },
  _: { isFunction: (value) => typeof value === 'function' },
  VueRouter,
  GeckoClient: {
    routerMode: 'history',
    routerBase: '/',
    setCanonicalUrl: () => {},
  },
};

vm.runInNewContext(source, context);
if (afterEachHandlers.length !== 1) {
  throw new Error(`Expected one router.afterEach handler, got ${afterEachHandlers.length}`);
}

afterEachHandlers[0]({path: '/markets'}, {path: '/'});

const hitCall = ymCalls.find((args) => args[0] === 109107032 && args[1] === 'hit');
if (!hitCall) {
  throw new Error('SPA route change did not send a Yandex Metrica hit');
}
if (hitCall[2] !== 'http://127.0.0.1:8891/markets') {
  throw new Error(`SPA route hit used an unexpected URL: ${hitCall[2]}`);
}
if ((hitCall[3] || {}).referer !== 'http://127.0.0.1:8891/') {
  throw new Error(`SPA route hit should use the previous route as referer, got ${(hitCall[3] || {}).referer}`);
}
if (context.window.tonbankcardYandexMetrica.lastTrackedUrl !== 'http://127.0.0.1:8891/markets') {
  throw new Error('SPA route tracking did not update the last tracked URL');
}
NODE

assert_contains "$robots_txt" '^User-agent: \*$' 'robots user agent directive'
assert_contains "$robots_txt" '^Allow: /$' 'robots allow directive'
assert_contains "$robots_txt" '^Disallow: /admin/$' 'robots admin disallow directive'
assert_contains "$robots_txt" '^Clean-param: utm_source&utm_medium&utm_campaign&utm_term&utm_content&yclid&gclid&fbclid /$' 'Yandex tracking parameter cleanup directive'
assert_contains "$robots_txt" '^Sitemap: http://127\.0\.0\.1:8891/sitemap\.xml$' 'sitemap pointer in robots.txt'

assert_contains "$sitemap_xml" '<urlset xmlns="http://www\.sitemaps\.org/schemas/sitemap/0\.9" xmlns:xhtml="http://www\.w3\.org/1999/xhtml">' 'sitemap urlset with xhtml namespace'
assert_contains "$sitemap_xml" '<lastmod>[0-9]{4}-[0-9]{2}-[0-9]{2}</lastmod>' 'sitemap lastmod value'
assert_contains "$sitemap_xml" '<loc>http://127\.0\.0\.1:8891/markets</loc>' 'markets sitemap URL'
assert_contains "$sitemap_xml" '<loc>http://127\.0\.0\.1:8891/ton</loc>' 'TON sitemap URL'
assert_contains "$sitemap_xml" '<loc>http://127\.0\.0\.1:8891/alerts</loc>' 'alerts sitemap URL'
assert_contains "$sitemap_xml" '<changefreq>hourly</changefreq>' 'sitemap change frequency'
assert_not_contains "$sitemap_xml" ':id' 'dynamic route placeholders in sitemap'

if [ "$failures" -gt 0 ]; then
    log "Public website shell check failed. See $log_file and $server_log"
    exit 1
fi

log "Public website shell check passed. Logs: $log_file, $server_log"
