#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-pwa-telegram-webapp.md

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

    if ! grep -Eq -- "$pattern" "$file"; then
        fail "$file does not include $description"
    fi
}

assert_not_contains() {
    file=$1
    pattern=$2
    description=$3

    if grep -Eq -- "$pattern" "$file"; then
        fail "$file unexpectedly includes $description"
    fi
}

assert_png_size() {
    file=$1
    expected_width=$2
    expected_height=$3

    if [ ! -f "$file" ]; then
        fail "Missing required PNG file: $file"
        return
    fi

    size=$(php -r "\$size = @getimagesize('$file'); if (! \$size) { exit(1); } echo \$size[0] . 'x' . \$size[1];" 2>/dev/null || true)
    if [ "$size" != "${expected_width}x${expected_height}" ]; then
        fail "$file is $size instead of ${expected_width}x${expected_height}"
    fi
}

assert_file "$doc"
assert_file service-worker.js
assert_file offline.html
assert_file site.webmanifest
assert_file assets/images/tonbankcard-icon-512x512.png
assert_file dev/js/src/initial.js
assert_file dev/js/src/router.js
assert_file dev/js/src/vm.js

assert_png_size assets/images/tonbankcard-icon-512x512.png 512 512

assert_contains "$doc" '^# TONBANKCARD V2 PWA and Telegram WebApp Integration$' 'the PWA and Telegram WebApp title'
assert_contains "$doc" 'Issue: \[#26\]' 'the issue reference'
assert_contains "$doc" 'service worker' 'service worker strategy documentation'
assert_contains "$doc" 'offline shell' 'offline shell documentation'
assert_contains "$doc" 'BackButton' 'Telegram BackButton behavior'
assert_contains "$doc" 'MainButton' 'Telegram MainButton behavior'
assert_contains "$doc" 'SecondaryButton' 'Telegram SecondaryButton behavior'
assert_contains "$doc" 'HapticFeedback' 'Telegram haptic behavior'
assert_contains "$doc" 'Playwright screenshots' 'screenshot verification documentation'

assert_contains README.md 'docs/v2-pwa-telegram-webapp\.md' 'the PWA and Telegram WebApp documentation link'
assert_contains package.json '"test:pwa-telegram"' 'the PWA and Telegram npm script'
assert_contains package.json 'test:pwa-telegram' 'the aggregate PWA and Telegram check'

assert_contains site.webmanifest '"id"[[:space:]]*:[[:space:]]*"/"' 'a stable manifest id'
assert_contains site.webmanifest '"display"[[:space:]]*:[[:space:]]*"standalone"' 'standalone PWA display mode'
assert_contains site.webmanifest '"start_url"[[:space:]]*:[[:space:]]*"/"' 'root start URL'
assert_contains site.webmanifest 'tonbankcard-icon-192x192\.png' '192px install icon'
assert_contains site.webmanifest 'tonbankcard-icon-512x512\.png' '512px install icon'
assert_contains site.webmanifest '"purpose"[[:space:]]*:[[:space:]]*"any maskable"' 'maskable install icon purpose'

assert_contains offline.html 'TONBANKCARD Crypto Tracker' 'offline shell branding'
assert_contains offline.html 'offline' 'offline shell message'
assert_contains service-worker.js 'CACHE_VERSION' 'versioned service worker cache'
assert_contains service-worker.js 'offline\.html' 'offline navigation fallback'
assert_contains service-worker.js 'site\.webmanifest' 'manifest pre-cache'
assert_contains service-worker.js '/api/' 'API requests excluded from service worker cache'
assert_contains service-worker.js 'response\.ok' 'successful response cache guard'
assert_contains service-worker.js 'cache-control' 'Cache-Control cacheability guard'
assert_contains service-worker.js 'no-store' 'no-store cache exclusion'
assert_contains service-worker.js 'private' 'private response cache exclusion'
assert_contains service-worker.js '/admin' 'admin route cache exclusion'
assert_contains service-worker.js '/install' 'installer route cache exclusion'
assert_contains service-worker.js "searchParams\.delete\('t'\)" 'versioned asset lookup fallback to precache URL'
assert_not_contains service-worker.js "'/assets/js/app\.js'" 'unused development bundle in production precache'

assert_contains views/app-head.php 'telegram-web-app\.js' 'conditional Telegram WebApp SDK loading'
assert_contains views/app-head.php 'apple-mobile-web-app-capable' 'Apple install metadata'
assert_contains views/app-top-bar.php 'promptPwaInstall' 'install prompt trigger'

assert_contains dev/js/src/initial.js 'GeckoClient\.pwa' 'PWA browser adapter'
assert_contains dev/js/src/initial.js 'beforeinstallprompt' 'install prompt capture'
assert_contains dev/js/src/initial.js 'serviceWorker\.register' 'service worker registration'
assert_contains dev/js/src/initial.js 'BackButton' 'Telegram BackButton wrapper'
assert_contains dev/js/src/initial.js 'MainButton' 'Telegram MainButton wrapper'
assert_contains dev/js/src/initial.js 'SecondaryButton' 'Telegram SecondaryButton wrapper'
assert_contains dev/js/src/initial.js 'HapticFeedback' 'Telegram HapticFeedback wrapper'
assert_contains dev/js/src/initial.js 'showPopup' 'Telegram popup wrapper'
assert_contains dev/js/src/initial.js 'shareToStory' 'Telegram story sharing wrapper'
assert_contains dev/js/src/initial.js 'requestFullscreen' 'Telegram fullscreen wrapper'
assert_contains dev/js/src/initial.js 'viewportChanged' 'Telegram viewport event handling'
assert_contains dev/js/src/initial.js 'safeAreaChanged' 'Telegram safe-area event handling'
assert_contains dev/js/src/router.js 'updateBackButton' 'Telegram back button route synchronization'
assert_contains dev/js/src/vm.js 'pwaInstallAvailable' 'Vue install prompt state'
assert_contains dev/js/src/vm.js 'promptPwaInstall' 'Vue install prompt method'
assert_contains tests/browser-smoke.js 'pwa-mobile-web\.png' 'mobile web Playwright screenshot'
assert_contains tests/browser-smoke.js 'pwa-telegram-webview\.png' 'Telegram-like Playwright screenshot'

if ! node <<'JS'
const fs = require('fs');
const vm = require('vm');

const listeners = {};
const sandbox = {
    self: {
        location: {origin: 'https://marketcap.example.test'},
        addEventListener: (name, fn) => {
            listeners[name] = fn;
        },
        skipWaiting: () => Promise.resolve(),
        clients: {claim: () => Promise.resolve()},
    },
    caches: {
        open: () => Promise.resolve({addAll: () => Promise.resolve(), put: () => Promise.resolve()}),
        keys: () => Promise.resolve([]),
        delete: () => Promise.resolve(true),
        match: () => Promise.resolve(null),
    },
    fetch: () => Promise.resolve(response(true, 'public, max-age=60')),
    URL,
    Promise,
};

function response(ok, cacheControl) {
    return {
        ok,
        headers: {
            get: name => String(name).toLowerCase() === 'cache-control' ? cacheControl : '',
        },
        clone() {
            return response(ok, cacheControl);
        },
    };
}

vm.runInNewContext(fs.readFileSync('service-worker.js', 'utf8'), sandbox);

if (typeof sandbox.isRuntimeCacheableResponse !== 'function') {
    throw new Error('service worker does not expose isRuntimeCacheableResponse');
}
if (typeof sandbox.isSensitiveRuntimePath !== 'function') {
    throw new Error('service worker does not expose isSensitiveRuntimePath');
}
if (!sandbox.isRuntimeCacheableResponse(response(true, 'public, max-age=60'))) {
    throw new Error('cacheable public success response was rejected');
}
if (sandbox.isRuntimeCacheableResponse(response(false, 'public, max-age=60'))) {
    throw new Error('non-OK response was accepted for runtime cache');
}
if (sandbox.isRuntimeCacheableResponse(response(true, 'no-store'))) {
    throw new Error('no-store response was accepted for runtime cache');
}
if (sandbox.isRuntimeCacheableResponse(response(true, 'private, max-age=60'))) {
    throw new Error('private response was accepted for runtime cache');
}
for (const pathname of ['/api/search', '/admin', '/admin/settings', '/install', '/install/index.php']) {
    if (!sandbox.isSensitiveRuntimePath(pathname)) {
        throw new Error(`${pathname} was not treated as sensitive`);
    }
}
if (sandbox.isSensitiveRuntimePath('/currency/bitcoin')) {
    throw new Error('public currency route was treated as sensitive');
}
JS
then
    fail 'Service worker runtime cache policy does not reject unsafe responses or sensitive routes'
fi

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'PWA and Telegram WebApp check passed.'
