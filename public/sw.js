const CACHE_NAME = 'salary-app-cache-v1';
const urlsToCache = [
  // Halaman utama
  './',
  // Aset statis dasar
  './assets/css/app.css',
  './assets/js/app.js',
  './assets/favicon/favicon.ico',
  './assets/favicon/apple-touch-icon.png',
  './assets/favicon/favicon-96x96.png',
  './assets/favicon/favicon.svg',
  './assets/favicon/web-app-manifest-192x192.png',
  './assets/favicon/web-app-manifest-512x512.png',
];

// Install Event - Caching assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

// Activate Event - Clean up old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Fetch Event - Network First Strategy
self.addEventListener('fetch', event => {
  // Hanya proses GET requests
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Simpan salinan ke cache jika response valid (opsional untuk dynamic PWA)
        // Kita hanya implement network-first fallback ke cache
        if (response.status === 200) {
           const responseClone = response.clone();
           caches.open(CACHE_NAME).then(cache => {
             cache.put(event.request, responseClone);
           });
        }
        return response;
      })
      .catch(() => {
        // Jika offline, ambil dari cache
        return caches.match(event.request);
      })
  );
});
