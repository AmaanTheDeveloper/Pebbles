/* Pebbles offline shell — caches only safe static assets. Never caches API/admin/auth. */

/**
 * Build-friendly cache version.
 * Bump CACHE_RELEASE when the offline shell contract changes.
 * Include the SW script URL query (?v=) when present so deploys can bust caches
 * without editing this file (e.g. /sw.js?v=20260825).
 */
const CACHE_RELEASE = 'v2';
const CACHE_QUERY = new URL(self.location.href).searchParams.get('v');
const CACHE_VERSION = `pebbles-offline-${CACHE_RELEASE}${CACHE_QUERY ? `-${CACHE_QUERY}` : ''}`;
const SHELL_URLS = ['/offline.html'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE_VERSION)
            .then((cache) => cache.addAll(SHELL_URLS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key))),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

function isUnsafeRequest(request, url) {
    if (request.method !== 'GET') {
        return true;
    }

    if (url.origin !== self.location.origin) {
        return true;
    }

    const path = url.pathname;

    if (path.startsWith('/api/') || path === '/api') {
        return true;
    }

    if (path.startsWith('/admin')) {
        return true;
    }

    if (path.startsWith('/sanctum/')) {
        return true;
    }

    if (path.startsWith('/storage/') && path.includes('payment')) {
        return true;
    }

    return false;
}

function isBuildAsset(url) {
    return url.pathname.startsWith('/build/');
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (isUnsafeRequest(request, url)) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => response)
                .catch(async () => {
                    const cached = await caches.match('/offline.html');
                    return cached || Response.error();
                }),
        );
        return;
    }

    if (isBuildAsset(url) || url.pathname === '/offline.html') {
        event.respondWith(
            caches.open(CACHE_VERSION).then(async (cache) => {
                const cached = await cache.match(request);
                if (cached) {
                    return cached;
                }

                try {
                    const response = await fetch(request);
                    if (response.ok) {
                        cache.put(request, response.clone());
                    }
                    return response;
                } catch (error) {
                    return cached || Response.error();
                }
            }),
        );
    }
});
