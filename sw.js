const CACHE_NAME = 'wellcarebd-v1';
const STATIC_ASSETS = [
    '/assets/css/output.css',
    '/assets/fontawesome/css/all.min.css',
    '/font/HindSiliguri-Regular.ttf',
    '/font/HindSiliguri-Bold.ttf',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Only handle same-origin requests
    if (url.origin !== self.location.origin) return;

    // Never intercept admin panel — POST requests, AJAX, auth flows must go straight to server
    if (url.pathname.startsWith('/admin/')) return;

    // Cache-first for static assets (CSS, JS, fonts, images)
    const isStatic = /\.(css|js|ttf|woff2?|svg|webp|png|jpe?g)(\?.*)?$/.test(url.pathname);
    if (isStatic) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                if (cached) return cached;
                return fetch(event.request).then(response => {
                    if (response.ok) {
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, response.clone()));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Network-first for PHP pages — only respondWith if we actually have something to return
    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request).then(cached => {
                if (cached) return cached;
                // No cache hit — return a proper offline response instead of undefined
                return new Response('Offline — please check your connection.', {
                    status: 503,
                    headers: { 'Content-Type': 'text/plain' },
                });
            });
        })
    );
});
