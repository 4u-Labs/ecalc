const CACHE_NAME = 'powercalc-v1.0.7';
const ASSETS = [
    './',
    './index.html',
    './app.html',
    './style.css',
    './app.js',
    './manifest.json',
    './privacy.html',
    './terms.html',
    './support.html',
    './assets/icon16.png',
    './assets/icon48.png',
    './assets/icon128.png',
    './assets/icon192.png',
    './assets/icon512.png'
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS);
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    if (e.request.url.startsWith(self.location.origin)) {
        e.respondWith(
            caches.match(e.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(e.request).then((networkResponse) => {
                    return caches.open(CACHE_NAME).then((cache) => {
                        if (e.request.method === 'GET') {
                            cache.put(e.request, networkResponse.clone());
                        }
                        return networkResponse;
                    });
                });
            })
        );
    }
});
