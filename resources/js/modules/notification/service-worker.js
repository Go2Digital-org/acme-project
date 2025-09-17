/**
 * ACME Corp Notification Service Worker
 * 
 * Handles push notifications, background sync, and offline notification caching
 * for the ACME Corp CSR platform.
 */

const NOTIFICATION_CACHE_NAME = 'acme-notifications-v1';
const NOTIFICATION_API_URL = '/api/notifications';

// Install service worker
self.addEventListener('install', function(event) {
    console.log('[SW] Installing notification service worker');
    
    event.waitUntil(
        caches.open(NOTIFICATION_CACHE_NAME).then(function(cache) {
            return cache.addAll([
                '/icons/notification-192.png',
                '/icons/notification-512.png',
                '/icons/badge-72.png',
                '/sounds/notification.mp3'
            ]);
        })
    );
    
    self.skipWaiting();
});

// Activate service worker
self.addEventListener('activate', function(event) {
    console.log('[SW] Activating notification service worker');
    
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.filter(function(cacheName) {
                    return cacheName.startsWith('acme-notifications-') && 
                           cacheName !== NOTIFICATION_CACHE_NAME;
                }).map(function(cacheName) {
                    return caches.delete(cacheName);
                })
            );
        })
    );
    
    return self.clients.claim();
});

// Handle push notifications
self.addEventListener('push', function(event) {
    console.log('[SW] Push notification received', event);
    
    if (!event.data) {
        console.log('[SW] Push event has no data');
        return;
    }
    
    let data;
    try {
        data = event.data.json();
    } catch (e) {
        console.error('[SW] Invalid push data format', e);
        return;
    }
    
    const options = {
        body: data.body || data.message,
        icon: data.icon || '/icons/notification-192.png',
        badge: data.badge || '/icons/badge-72.png',
        image: data.image,
        vibrate: data.vibrate || [200, 100, 200],
        sound: data.sound || '/sounds/notification.mp3',
        tag: data.tag || `notification-${data.id || Date.now()}`,
        requireInteraction: data.requireInteraction || data.priority === 'high',
        renotify: data.renotify || false,
        silent: data.silent || false,
        timestamp: data.timestamp ? new Date(data.timestamp).getTime() : Date.now(),
        actions: data.actions || [
            {
                action: 'view',
                title: 'View',
                icon: '/icons/view-24.png'
            },
            {
                action: 'dismiss',
                title: 'Dismiss',
                icon: '/icons/dismiss-24.png'
            }
        ],
        data: {
            url: data.url || data.action_url,
            notificationId: data.id || data.notification_id,
            type: data.type,
            campaignId: data.campaign_id,
            donationId: data.donation_id,
            metadata: data.metadata || {}
        }
    };
    
    // Add custom actions based on notification type
    if (data.type === 'donation_confirmation') {
        options.actions = [
            {
                action: 'receipt',
                title: 'Download Receipt',
                icon: '/icons/download-24.png'
            },
            {
                action: 'share',
                title: 'Share',
                icon: '/icons/share-24.png'
            },
            {
                action: 'view',
                title: 'View Campaign',
                icon: '/icons/view-24.png'
            }
        ];
    } else if (data.type === 'campaign_created') {
        options.actions = [
            {
                action: 'view',
                title: 'View Campaign',
                icon: '/icons/view-24.png'
            },
            {
                action: 'donate',
                title: 'Donate Now',
                icon: '/icons/heart-24.png'
            }
        ];
    }
    
    event.waitUntil(
        self.registration.showNotification(data.title, options).then(() => {
            // Track notification display
            trackNotificationEvent('displayed', data.id || data.notification_id);
            
            // Store notification for offline access
            cacheNotification(data);
        })
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', function(event) {
    console.log('[SW] Notification clicked', event);
    
    event.notification.close();
    
    const data = event.notification.data;
    const action = event.action;
    
    // Track click event
    trackNotificationEvent('clicked', data.notificationId, { action: action });
    
    let targetUrl = data.url;
    
    // Handle specific actions
    if (action === 'receipt' && data.donationId) {
        targetUrl = `/donations/${data.donationId}/receipt`;
    } else if (action === 'share' && data.campaignId) {
        targetUrl = `/campaigns/${data.campaignId}/share`;
    } else if (action === 'donate' && data.campaignId) {
        targetUrl = `/campaigns/${data.campaignId}/donate`;
    } else if (action === 'dismiss') {
        // Just track the dismissal, don't open anything
        trackNotificationEvent('dismissed', data.notificationId);
        return;
    }
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            // Try to focus existing window if target URL is already open
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            
            // Open new window
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// Handle notification close events
self.addEventListener('notificationclose', function(event) {
    console.log('[SW] Notification closed', event);
    
    const data = event.notification.data;
    trackNotificationEvent('closed', data.notificationId);
});

// Handle background sync for failed notification tracking
self.addEventListener('sync', function(event) {
    if (event.tag === 'notification-tracking') {
        event.waitUntil(syncNotificationTracking());
    }
});

// Message handling for communication with main thread
self.addEventListener('message', function(event) {
    console.log('[SW] Message received', event);
    
    if (event.data && event.data.type) {
        switch (event.data.type) {
            case 'CACHE_NOTIFICATION':
                cacheNotification(event.data.notification);
                break;
            case 'CLEAR_NOTIFICATIONS':
                clearNotificationCache();
                break;
            case 'GET_CACHED_NOTIFICATIONS':
                getCachedNotifications().then(notifications => {
                    event.ports[0].postMessage(notifications);
                });
                break;
        }
    }
});

/**
 * Track notification events (display, click, dismiss, etc.)
 */
function trackNotificationEvent(eventType, notificationId, additionalData = {}) {
    const trackingData = {
        event_type: eventType,
        notification_id: notificationId,
        timestamp: new Date().toISOString(),
        user_agent: navigator.userAgent,
        ...additionalData
    };
    
    // Try to send tracking data immediately
    fetch(`${NOTIFICATION_API_URL}/track`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(trackingData)
    }).catch(error => {
        console.error('[SW] Failed to track notification event', error);
        
        // Store for background sync if immediate send fails
        storeTrackingDataForSync(trackingData);
    });
}

/**
 * Store tracking data for background sync when offline
 */
function storeTrackingDataForSync(trackingData) {
    caches.open(NOTIFICATION_CACHE_NAME).then(cache => {
        const request = new Request(`tracking-${Date.now()}`, {
            method: 'POST',
            body: JSON.stringify(trackingData)
        });
        cache.put(request, new Response(JSON.stringify(trackingData)));
        
        // Register background sync
        self.registration.sync.register('notification-tracking');
    });
}

/**
 * Sync pending tracking data when connection is restored
 */
function syncNotificationTracking() {
    return caches.open(NOTIFICATION_CACHE_NAME).then(cache => {
        return cache.keys().then(requests => {
            const trackingRequests = requests.filter(request => 
                request.url.includes('tracking-')
            );
            
            return Promise.all(trackingRequests.map(request => {
                return cache.match(request).then(response => {
                    return response.json().then(trackingData => {
                        return fetch(`${NOTIFICATION_API_URL}/track`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(trackingData)
                        }).then(() => {
                            // Remove from cache after successful sync
                            return cache.delete(request);
                        });
                    });
                });
            }));
        });
    });
}

/**
 * Cache notification data for offline access
 */
function cacheNotification(notificationData) {
    const cacheKey = `notification-${notificationData.id || Date.now()}`;
    const cacheData = {
        ...notificationData,
        cached_at: new Date().toISOString()
    };
    
    caches.open(NOTIFICATION_CACHE_NAME).then(cache => {
        const request = new Request(cacheKey);
        const response = new Response(JSON.stringify(cacheData));
        cache.put(request, response);
    });
}

/**
 * Get cached notifications for offline viewing
 */
function getCachedNotifications() {
    return caches.open(NOTIFICATION_CACHE_NAME).then(cache => {
        return cache.keys().then(requests => {
            const notificationRequests = requests.filter(request => 
                request.url.includes('notification-') && 
                !request.url.includes('tracking-')
            );
            
            return Promise.all(notificationRequests.map(request => {
                return cache.match(request).then(response => {
                    return response.json();
                });
            }));
        });
    });
}

/**
 * Clear cached notifications
 */
function clearNotificationCache() {
    caches.open(NOTIFICATION_CACHE_NAME).then(cache => {
        cache.keys().then(requests => {
            const notificationRequests = requests.filter(request => 
                request.url.includes('notification-')
            );
            
            notificationRequests.forEach(request => {
                cache.delete(request);
            });
        });
    });
}