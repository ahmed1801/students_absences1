const cacheName = 'student-registry-v1';
const assets = [
    '/',
    '/index.php',
    '/styles.css',
    '/icons/icon-192.png',
    '/icons/icon-512.png'
];

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(cacheName).then(cache => {
            return cache.addAll(assets);
        })
    );
});

self.addEventListener('fetch', e => {
    e.respondWith(
        caches.match(e.request).then(response => {
            return response || fetch(e.request);
        })
    );
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(keys.filter(key => key !== cacheName)
                .map(key => caches.delete(key))
            );
        })
    );
});
