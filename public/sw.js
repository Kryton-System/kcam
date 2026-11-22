// KCAM service worker.
// Security rule: only the offline page, the manifest and versioned build assets are cached.
// Authenticated pages and JSON responses are never stored, so private data cannot be read
// back from the cache on a shared phone.
const CACHE_NAME = 'kcam-cache-v2';
const OFFLINE_URL = '/offline';
const STATIC_PREFIX = '/build/';

const PRECACHE_ASSETS = [OFFLINE_URL, '/manifest.json'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((names) =>
      Promise.all(names.filter((name) => name !== CACHE_NAME).map((name) => caches.delete(name)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  if (url.origin !== self.location.origin) {
    return;
  }

  // Pages: always the network, the offline page when there is no connection. Never cached.
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  // Versioned build assets: network first, cached copy when offline.
  if (url.pathname.startsWith(STATIC_PREFIX)) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response && response.status === 200 && response.type === 'basic') {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          }
          return response;
        })
        .catch(() => caches.match(request))
    );
    return;
  }

  // Everything else (JSON, data, uploads) is not handled: the browser goes to the network.
});

// The page asks the worker to drop every cache when the user logs out.
self.addEventListener('message', (event) => {
  if (event.data === 'clear-caches') {
    event.waitUntil(caches.keys().then((names) => Promise.all(names.map((name) => caches.delete(name)))));
  }
});
