#!/usr/bin/env sh
set -eu

# Asserts that the generated sitemap covers the full discoverable universe —
# many coins (not just the three hardcoded route params), every exchange, and
# the TON ecosystem assets — and that the documents are schema-valid with
# absolute URLs and per-language hreflang alternates.
#
# The check runs against the `local` profile, so it uses the bundled,
# offline-safe discovery universe (config/seo-universe.php) and the default
# TON catalog. It therefore makes NO live CoinGecko request and is fully
# deterministic in CI.

root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
schema_dir="$root/tests/schemas"
log_dir="$root/test-logs"
log_file="$log_dir/sitemap-coverage-check.log"
server_log="$log_dir/sitemap-coverage-server.log"
host="${SITEMAP_CHECK_HOST:-127.0.0.1}"
port="${SITEMAP_CHECK_PORT:-8895}"
base_url="http://$host:$port"
server_pid=""
failures=0

# Minimum number of coin URLs we expect — the audit's "Finding 1" was that only
# three hardcoded coins were exposed; the bundled universe lists ~100.
min_coins="${SITEMAP_MIN_COINS:-50}"

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

# Counts <loc> occurrences that point at a path prefix (absolute URLs only).
count_loc_prefix() {
    file=$1
    prefix=$2
    grep -Eo "<loc>$base_url$prefix[^<]*</loc>" "$file" | wc -l | tr -d ' '
}

# Validates that a document is well-formed XML using PHP's libxml.
assert_well_formed() {
    file=$1
    description=$2
    php -r "libxml_use_internal_errors(true); \$d = new DOMDocument(); exit(\$d->load('$file') ? 0 : 1);" \
        || fail "$description is not well-formed XML"
}

# Validates a urlset/sitemapindex document against the *official* sitemaps.org
# 0.9 XSD, bundled offline under tests/schemas/ so the check stays deterministic
# and never reaches out to the network.
#
# The Google hreflang extension emits <xhtml:link> alternates immediately after
# <loc> — a position the strict sitemaps.org 0.9 schema does not permit for
# foreign-namespace elements (its <xsd:any> wildcard only sits at the end of the
# sequence). Those alternates are asserted separately below, so here we strip
# foreign-namespace nodes and validate the *core* sitemap structure (element
# order, <loc> as an absolute URI, lastmod date format, changefreq enum, and the
# 0.0–1.0 priority range) against the real XSD via DOMDocument::schemaValidate().
assert_schema_valid() {
    file=$1
    description=$2
    php -r '
        libxml_use_internal_errors(true);
        $ns = "http://www.sitemaps.org/schemas/sitemap/0.9";
        $doc = new DOMDocument();
        if (! $doc->load($argv[1])) { fwrite(STDERR, "not well-formed XML\n"); exit(1); }
        $root = $doc->documentElement;
        if ($root->namespaceURI !== $ns) { fwrite(STDERR, "unexpected root namespace\n"); exit(2); }
        if (! in_array($root->localName, ["urlset", "sitemapindex"], true)) { fwrite(STDERR, "unexpected root element\n"); exit(3); }
        // Every <loc> must be a non-empty absolute http(s) URL.
        foreach ($doc->getElementsByTagNameNS($ns, "loc") as $loc) {
            $value = trim($loc->textContent);
            if ("" === $value || ! preg_match("#^https?://#", $value)) { fwrite(STDERR, "non-absolute <loc>: $value\n"); exit(4); }
        }
        // Drop foreign-namespace extension nodes (xhtml:link hreflang alternates)
        // so the document validates against the strict official 0.9 schema.
        $xpath = new DOMXPath($doc);
        foreach (iterator_to_array($xpath->query("//*[namespace-uri() != \"" . $ns . "\"]")) as $node) {
            $node->parentNode->removeChild($node);
        }
        $schema = $argv[2] . ("sitemapindex" === $root->localName ? "/siteindex.xsd" : "/sitemap.xsd");
        if (! $doc->schemaValidate($schema)) {
            foreach (libxml_get_errors() as $e) { fwrite(STDERR, "  " . trim($e->message) . "\n"); }
            exit(5);
        }
        exit(0);
    ' "$file" "$schema_dir" 2>>"$log_file" || fail "$description failed sitemaps.org 0.9 schema validation"
}

# The bundled, offline copies of the official sitemaps.org 0.9 schemas must be
# present; without them the schema validation below would silently degrade.
for schema in sitemap.xsd siteindex.xsd; do
    if [ ! -f "$schema_dir/$schema" ]; then
        fail "missing bundled schema $schema_dir/$schema"
    fi
done

start_server
wait_for_server

sitemap_index="$log_dir/coverage-sitemap_index.xml"
sitemap_combined="$log_dir/coverage-sitemap.xml"
sitemap_coins="$log_dir/coverage-sitemap-coins.xml"
sitemap_exchanges="$log_dir/coverage-sitemap-exchanges.xml"
sitemap_ton="$log_dir/coverage-sitemap-ton.xml"

fetch_path "/sitemap_index.xml" "$sitemap_index"
fetch_path "/sitemap.xml" "$sitemap_combined"
fetch_path "/sitemap-coins.xml" "$sitemap_coins"
fetch_path "/sitemap-exchanges.xml" "$sitemap_exchanges"
fetch_path "/sitemap-ton.xml" "$sitemap_ton"

# Schema validity / well-formedness.
assert_well_formed "$sitemap_index" 'sitemap index'
assert_schema_valid "$sitemap_index" 'sitemap index'
assert_schema_valid "$sitemap_combined" 'combined sitemap'
assert_schema_valid "$sitemap_coins" 'coins sitemap'
assert_schema_valid "$sitemap_exchanges" 'exchanges sitemap'
assert_schema_valid "$sitemap_ton" 'TON sitemap'

# The index references each section file.
assert_contains "$sitemap_index" "<loc>$base_url/sitemap-coins\\.xml</loc>" 'coins section in sitemap index'
assert_contains "$sitemap_index" "<loc>$base_url/sitemap-exchanges\\.xml</loc>" 'exchanges section in sitemap index'
assert_contains "$sitemap_index" "<loc>$base_url/sitemap-ton\\.xml</loc>" 'TON section in sitemap index'

# Coverage: many coins (audit Finding 1 — not just three hardcoded ones).
coin_count=$(count_loc_prefix "$sitemap_coins" "/coins/")
log "Discovered $coin_count coin URLs (minimum expected: $min_coins)."
if [ "$coin_count" -lt "$min_coins" ]; then
    fail "coins sitemap exposes only $coin_count URLs (expected >= $min_coins)"
fi

# Coverage: exchanges and TON assets are present (audit Finding 2).
exchange_count=$(count_loc_prefix "$sitemap_exchanges" "/exchange/")
log "Discovered $exchange_count exchange URLs."
if [ "$exchange_count" -lt 5 ]; then
    fail "exchanges sitemap exposes only $exchange_count URLs (expected >= 5)"
fi

ton_count=$(grep -Eo "<loc>$base_url/[^<]*</loc>" "$sitemap_ton" | wc -l | tr -d ' ')
log "Discovered $ton_count TON asset URLs."
if [ "$ton_count" -lt 1 ]; then
    fail "TON sitemap exposes no URLs (expected >= 1)"
fi

# A few well-known coins must be present.
assert_contains "$sitemap_coins" "<loc>$base_url/coins/bitcoin</loc>" 'Bitcoin coin URL'
assert_contains "$sitemap_coins" "<loc>$base_url/coins/ethereum</loc>" 'Ethereum coin URL'
assert_contains "$sitemap_coins" "<loc>$base_url/coins/the-open-network</loc>" 'TON coin URL'

# Absolute-URL enforcement is covered by assert_schema_valid above (every <loc>
# must match ^https?://).

# hreflang alternates present for crawler internationalization.
assert_contains "$sitemap_coins" '<xhtml:link rel="alternate" hreflang="en" href="[^"]+"/>' 'coin hreflang alternate (en)'
assert_contains "$sitemap_coins" '<xhtml:link rel="alternate" hreflang="ru" href="[^"]+"/>' 'coin hreflang alternate (ru)'
assert_contains "$sitemap_coins" '<xhtml:link rel="alternate" hreflang="ar" href="[^"]+"/>' 'coin hreflang alternate (ar)'
assert_contains "$sitemap_coins" '<xhtml:link rel="alternate" hreflang="x-default" href="[^"]+"/>' 'coin hreflang x-default alternate'

if [ "$failures" -gt 0 ]; then
    log "Sitemap coverage check failed. See $log_file and $server_log"
    exit 1
fi

log "Sitemap coverage check passed. Logs: $log_file, $server_log"
