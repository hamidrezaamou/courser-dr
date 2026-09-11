/* PWA service worker — v{{ $version }} */
'use strict';

const CACHE_VERSION = @json('patient-archive-v'.$version);
const STATIC_CACHE = CACHE_VERSION + '-static';
const OFFLINE_URL = '/offline';

const PRECACHE = [
    '/pwa/icon-192.png',
    '/pwa/icon-512.png',
    '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => Promise.all(
                PRECACHE.map((url) => cache.add(url).catch(function () { return null; }))
            ))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key.startsWith('patient-archive-') && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => caches.match(OFFLINE_URL).then((cached) => cached || fetch(req)))
        );
        return;
    }

    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') || url.pathname.startsWith('/pwa/')) {
        event.respondWith(
            caches.match(req).then((cached) => cached || fetch(req).then((res) => {
                if (res && res.status === 200) {
                    const copy = res.clone();
                    caches.open(STATIC_CACHE).then((cache) => cache.put(req, copy));
                }
                return res;
            }).catch(() => cached))
        );
    }
});
