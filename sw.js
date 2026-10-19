/**
 * CurtissPOS - Progressive Web App Service Worker
 * Version: 1.0.0
 * Provides offline caching, network-first dynamic proxy, and instant app installability.
 */

const CACHE_NAME = 'curtisspos-shell-v1';
const STATIC_ASSETS = [
    './manifest.json',
    './assets/css/style.css',
    './assets/css/components.css',
    './assets/css/nav.css',
    './assets/css/curtiss_select.css',
    './assets/js/nav.js',
    './assets/js/curtiss_select.js',
    './assets/js/pwa.js',
    './assets/icons/icon-192x192.png',
    './assets/icons/icon-512x512.png',
    './assets/icons/icon-maskable-192x192.png',
    './assets/icons/icon-maskable-512x512.png',
    './assets/icons/favicon-32x32.png',
    './assets/icons/favicon.png',
    './offline.html'
];

// Install Event: Pre-cache core shell assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[CurtissPOS SW] Pre-caching non-fatal warning:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate Event: Clean up stale caches and claim clients immediately
self.addEventListener('activate', (event) => {
    event.waitUntil(
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

// Fetch Event: Network-First for HTML/PHP/APIs, Cache-First for static assets
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip non-GET requests (POST, PUT, DELETE mutations pass directly to backend)
    if (request.method !== 'GET') {
        return;
    }

    // Static Assets (CSS, JS, Fonts, Images) -> Stale While Revalidate / Cache-First
    if (
        url.pathname.includes('/assets/') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.json')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    // Update cache in background
                    fetch(request).then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            caches.open(CACHE_NAME).then((cache) => cache.put(request, networkResponse));
                        }
                    }).catch(() => {});
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, responseClone));
                    }
                    return networkResponse;
                });
            })
        );
        return;
    }

    // Dynamic Pages (PHP endpoints) -> Network First with Offline Fallback
    event.respondWith(
        fetch(request)
            .then((networkResponse) => {
                return networkResponse;
            })
            .catch(() => {
                // If offline and navigating to a page, serve offline.html
                if (request.mode === 'navigate' || (request.headers.get('accept') && request.headers.get('accept').includes('text/html'))) {
                    return caches.match('./offline.html');
                }
            })
    );
});
