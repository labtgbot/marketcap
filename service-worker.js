'use strict';

const CACHE_VERSION = 'tonbankcard-pwa-v1';
const SHELL_CACHE = `${CACHE_VERSION}-shell`;
const RUNTIME_CACHE = `${CACHE_VERSION}-runtime`;
const OFFLINE_URL = '/offline.html';
const SHELL_ASSETS = [
    '/',
    OFFLINE_URL,
    '/site.webmanifest',
    '/assets/css/style.css',
    '/assets/js/app.js',
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
    if (url.origin !== self.location.origin || url.pathname === '/api' || url.pathname.indexOf('/api/') === 0) return;

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

function networkFirstNavigation(request) {
    return fetch(request)
        .then(response => {
            const copy = response.clone();
            caches.open(RUNTIME_CACHE).then(cache => cache.put(request, copy));
            return response;
        })
        .catch(() => caches.match(OFFLINE_URL).then(response => response || caches.match('/')));
}

function staleWhileRevalidate(request) {
    return caches.match(request).then(cached => {
        const refresh = fetch(request)
            .then(response => {
                const copy = response.clone();
                caches.open(RUNTIME_CACHE).then(cache => cache.put(request, copy));
                return response;
            })
            .catch(() => cached);

        return cached || refresh;
    });
}
