self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open('pwa-cache-v1').then((cache) => {
            return cache.addAll([
                '/',
                '/assets/pwa/tdblogo129.webp',
                '/assets/pwa/tdblogo512.webp',
                '/assets/pwa/manifest.json',
                '/assets/css/style.css', // Add other static files
                '/assets/js/app.js'
            ]);
        })
    );
    console.log('Service Worker installed.');
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request);
        })
    );
});
