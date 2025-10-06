// Service Worker for Daily Statement App PWA
// Version: 2.1.0
// Enhanced for better PWA install experience

const CACHE_NAME = 'daily-statement-v2.1.0';
const STATIC_CACHE = 'static-v2.1.0';

// Install event - no caching
self.addEventListener('install', (event) => {
  console.log('Service Worker: Installing...');
  
  // Skip waiting to activate immediately
  self.skipWaiting();
});

// Activate event - clean up old caches if any
self.addEventListener('activate', (event) => {
  console.log('Service Worker: Activating...');
  
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          // Delete any existing caches that don't match current version
          if (cacheName !== CACHE_NAME && cacheName !== STATIC_CACHE) {
            console.log('Service Worker: Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      // Take control of all pages immediately
      return self.clients.claim();
    }).then(() => {
      // Notify all clients that SW is ready
      return self.clients.matchAll().then(clients => {
        clients.forEach(client => {
          client.postMessage({
            type: 'SW_ACTIVATED',
            version: '2.1.0'
          });
        });
      });
    })
  );
});

// Fetch event - always fetch from network (no caching)
self.addEventListener('fetch', (event) => {
  // Only handle GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Always return the network response
        return response;
      })
      .catch((error) => {
        console.log('Service Worker: Fetch failed:', error);
        
        // For navigation requests, show a basic offline page
        if (event.request.mode === 'navigate') {
          return new Response(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Offline - Daily Statement App</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        text-align: center;
                        padding: 2rem;
                        min-height: 100vh;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                    }
                    .offline-icon {
                        font-size: 4rem;
                        margin-bottom: 1rem;
                    }
                    .offline-title {
                        font-size: 2rem;
                        margin-bottom: 1rem;
                    }
                    .offline-message {
                        font-size: 1.1rem;
                        opacity: 0.9;
                        margin-bottom: 2rem;
                    }
                    .retry-button {
                        background: rgba(255, 255, 255, 0.2);
                        border: 2px solid rgba(255, 255, 255, 0.3);
                        color: white;
                        padding: 0.75rem 1.5rem;
                        border-radius: 0.5rem;
                        font-size: 1rem;
                        cursor: pointer;
                        transition: all 0.3s ease;
                    }
                    .retry-button:hover {
                        background: rgba(255, 255, 255, 0.3);
                        border-color: rgba(255, 255, 255, 0.5);
                    }
                </style>
            </head>
            <body>
                <div class="offline-icon">📱</div>
                <h1 class="offline-title">You're Offline</h1>
                <p class="offline-message">
                    Daily Statement App requires an internet connection.<br>
                    Please check your connection and try again.
                </p>
                <button class="retry-button" onclick="window.location.reload()">
                    Try Again
                </button>
            </body>
            </html>
          `, {
            status: 200,
            statusText: 'OK',
            headers: {
              'Content-Type': 'text/html; charset=utf-8'
            }
          });
        }
        
        // For other requests, just throw the error
        throw error;
      })
  );
});

// Background sync event (for future use)
self.addEventListener('sync', (event) => {
  console.log('Service Worker: Background sync:', event.tag);
  
  if (event.tag === 'background-sync') {
    // Handle background sync if needed in the future
    event.waitUntil(doBackgroundSync());
  }
});

// Push notification event (for future use)
self.addEventListener('push', (event) => {
  console.log('Service Worker: Push notification received');
  
  const options = {
    body: event.data ? event.data.text() : 'New notification from Daily Statement App',
    icon: '/icon-192.png',
    badge: '/icon-72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Open App',
        icon: '/icon-192.png'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/icon-192.png'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification('Daily Statement App', options)
  );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
  console.log('Service Worker: Notification clicked');
  
  event.notification.close();
  
  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/')
    );
  }
});

// Helper function for background sync
async function doBackgroundSync() {
  try {
    // Placeholder for background sync logic
    console.log('Service Worker: Performing background sync...');
    return Promise.resolve();
  } catch (error) {
    console.error('Service Worker: Background sync failed:', error);
    throw error;
  }
}

// Message event for communication with main thread
self.addEventListener('message', (event) => {
  console.log('Service Worker: Message received:', event.data);
  
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  // Send response back
  event.ports[0].postMessage({
    type: 'SW_RESPONSE',
    message: 'Service Worker received message'
  });
});
