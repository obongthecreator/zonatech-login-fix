/**
 * ZonaTech NG - Service Worker
 */

const CACHE_NAME = 'zonatech-ng-v2';
const OFFLINE_URL = '/offline.html';

const PRECACHE_URLS = [
    '/',
    '/zonatech-login/',
    '/zonatech-register/',
    '/zonatech-past-questions/',
    '/zonatech-scratch-cards/',
    '/zonatech-nin-service/'
];
// Protected routes (authenticated/sensitive pages) bypass cache; offline access returns a short message.
const PROTECTED_PATHS = new Set(['/zonatech-dashboard/']);
const handleProtectedFetch = (request, requestMode) => fetch(request).catch(() => {
    if (requestMode === 'navigate') {
        return caches.match(OFFLINE_URL);
    }
    return new Response('Network required for authentication. Please check your connection.', {
        status: 503,
        statusText: 'Service Unavailable',
        headers: { 'Content-Type': 'text/plain; charset=UTF-8' }
    });
});

// Install event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Caching app shell');
                return cache.addAll(PRECACHE_URLS);
            })
            .then(() => {
                self.skipWaiting();
            })
    );
});

// Activate event
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName !== CACHE_NAME)
                    .map((cacheName) => caches.delete(cacheName))
            );
        }).then(() => {
            self.clients.claim();
        })
    );
});

// Fetch event
self.addEventListener('fetch', (event) => {
    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }
    
    // Skip admin-ajax requests
    if (event.request.url.includes('admin-ajax.php')) {
        return;
    }
    const requestPath = event.request.url.slice(self.location.origin.length).split('?')[0];
    if (PROTECTED_PATHS.has(requestPath)) {
        event.respondWith(handleProtectedFetch(event.request, event.request.mode));
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                if (response) {
                    return response;
                }
                
                return fetch(event.request)
                    .then((response) => {
                        // Don't cache non-successful responses
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }
                        
                        // Clone the response
                        const responseToCache = response.clone();
                        
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                // Only cache GET requests
                                if (event.request.method === 'GET') {
                                    cache.put(event.request, responseToCache);
                                }
                            });
                        
                        return response;
                    })
                    .catch(() => {
                        // Return offline page for navigation requests
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_URL);
                        }
                    });
            })
    );
});

// Background sync for offline actions
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-data') {
        event.waitUntil(syncData());
    }
});

async function syncData() {
    // Implement sync logic here if needed
    console.log('Syncing data...');
}

// Push notifications (for future use)
self.addEventListener('push', (event) => {
    const options = {
        body: event.data.text(),
        icon: '/wp-content/plugins/zonatech-ng-plugin/assets/images/icon-192.png',
        badge: '/wp-content/plugins/zonatech-ng-plugin/assets/images/icon-72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            { action: 'explore', title: 'View' },
            { action: 'close', title: 'Close' }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification('ZonaTech NG', options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});