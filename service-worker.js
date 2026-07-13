'use strict';

const CACHE_VERSION = 'tonbankcard-pwa-v1';
const SHELL_CACHE = `${CACHE_VERSION}-shell`;
const RUNTIME_CACHE = `${CACHE_VERSION}-runtime`;
const OFFLINE_URL = '/offline.html';
const SENSITIVE_RUNTIME_PATHS = [
    '/api',
    '/api/',
    '/admin',
    '/install',
    '/database',
    '/dev',
    '/tests',
];
const SHELL_ASSETS = [
    '/',
    OFFLINE_URL,
    '/site.webmanifest',
    '/assets/css/style.css',
    '/assets/js/app.min.js',
    '/assets/images/favicon.ico',
    '/assets/images/tonbankcard-icon-192x192.png',
    '/assets/images/tonbankcard-icon-512x512.png',
    '/assets/images/tonbankcard-apple-touch-icon.png',
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(SHELL_CACHE)
            .then(cache => cache.addAll(SHELL_ASSETS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys
                .filter(key => key.indexOf(CACHE_VERSION) !== 0)
                .map(key => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin || isSensitiveRuntimePath(url.pathname)) return;

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstNavigation(request));
        return;
    }

    if (isStaticAsset(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request));
    }
});

function isStaticAsset(pathname) {
    return /\.(?:css|js|png|jpg|jpeg|gif|svg|ico|webmanifest|woff2?|ttf|eot)$/i.test(pathname);
}

function isSensitiveRuntimePath(pathname) {
    const normalized = pathname === '/' ? '/' : pathname.replace(/\/+$/, '');
    return SENSITIVE_RUNTIME_PATHS.some(path => (
        normalized === path.replace(/\/+$/, '') ||
        normalized.indexOf(`${path.replace(/\/+$/, '')}/`) === 0
    ));
}

function isRuntimeCacheableResponse(response) {
    if (!response || !response.ok) return false;

    const cacheControl = (response.headers.get('cache-control') || '').toLowerCase();
    return !/(^|,\s*)(no-store|no-cache|private)(\s|,|=|$)/.test(cacheControl);
}

function networkFirstNavigation(request) {
    return fetch(request)
        .then(response => {
            if (isRuntimeCacheableResponse(response)) {
                const copy = response.clone();
                caches.open(RUNTIME_CACHE).then(cache => cache.put(request, copy));
            }
            return response;
        })
        .catch(() => caches.match(request).then(cached => cached || caches.match(OFFLINE_URL).then(response => response || caches.match('/'))));
}

function staleWhileRevalidate(request) {
    return caches.match(request).then(cached => {
        if (!cached) {
            const unversioned = new URL(request.url);
            unversioned.searchParams.delete('t');
            return caches.match(unversioned.toString()).then(preCached => staleWhileRevalidateWithCached(request, preCached));
        }
        return staleWhileRevalidateWithCached(request, cached);
    });
}

function staleWhileRevalidateWithCached(request, cached) {
        const refresh = fetch(request)
            .then(response => {
                if (isRuntimeCacheableResponse(response)) {
                    const copy = response.clone();
                    caches.open(RUNTIME_CACHE).then(cache => cache.put(request, copy));
                }
                return response;
            })
            .catch(() => cached);

        return cached || refresh;
}
