/**
 * ACME Corp Notification Client
 * 
 * JavaScript client for real-time notification handling via WebSockets,
 * push notifications, and service worker integration.
 */

class NotificationClient {
    constructor(options = {}) {
        this.options = {
            pusherKey: options.pusherKey || window.pusherKey,
            pusherCluster: options.pusherCluster || 'us2',
            userId: options.userId || window.userId,
            debug: options.debug || false,
            enablePush: options.enablePush !== false,
            enableDesktop: options.enableDesktop !== false,
            serviceWorkerPath: options.serviceWorkerPath || '/service-worker.js',
            ...options
        };

        this.pusher = null;
        this.channels = new Map();
        this.serviceWorker = null;
        this.pushSubscription = null;
        this.eventHandlers = new Map();
        this.unreadCount = 0;

        this.init();
    }

    /**
     * Initialize the notification client
     */
    async init() {
        try {
            // Initialize Pusher connection
            await this.initializePusher();

            // Register service worker
            if (this.options.enablePush || this.options.enableDesktop) {
                await this.registerServiceWorker();
            }

            // Request notification permissions
            if (this.options.enableDesktop) {
                await this.requestNotificationPermission();
            }

            // Setup push notifications
            if (this.options.enablePush) {
                await this.setupPushNotifications();
            }

            // Subscribe to user channels
            await this.subscribeToUserChannels();

            // Setup heartbeat
            this.startHeartbeat();

            this.log('Notification client initialized successfully');
        } catch (error) {
            console.error('[NotificationClient] Initialization failed:', error);
        }
    }

    /**
     * Initialize Pusher WebSocket connection
     */
    async initializePusher() {
        if (typeof Pusher === 'undefined') {
            throw new Error('Pusher library not loaded');
        }

        this.pusher = new Pusher(this.options.pusherKey, {
            cluster: this.options.pusherCluster,
            encrypted: true,
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            },
            authEndpoint: '/api/broadcasting/auth',
        });

        // Setup connection event handlers
        this.pusher.connection.bind('connected', () => {
            this.log('Connected to Pusher');
            this.emit('connected');
        });

        this.pusher.connection.bind('disconnected', () => {
            this.log('Disconnected from Pusher');
            this.emit('disconnected');
        });

        this.pusher.connection.bind('error', (error) => {
            console.error('[NotificationClient] Pusher connection error:', error);
            this.emit('error', error);
        });
    }

    /**
     * Register service worker for push notifications
     */
    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            this.log('Service workers not supported');
            return;
        }

        try {
            const registration = await navigator.serviceWorker.register(this.options.serviceWorkerPath);
            this.serviceWorker = registration;
            this.log('Service worker registered');

            // Handle service worker messages
            navigator.serviceWorker.addEventListener('message', (event) => {
                this.handleServiceWorkerMessage(event.data);
            });

        } catch (error) {
            console.error('[NotificationClient] Service worker registration failed:', error);
        }
    }

    /**
     * Request notification permission from user
     */
    async requestNotificationPermission() {
        if (!('Notification' in window)) {
            this.log('Desktop notifications not supported');
            return false;
        }

        if (Notification.permission === 'granted') {
            return true;
        }

        if (Notification.permission === 'denied') {
            this.log('Notification permission denied');
            return false;
        }

        const permission = await Notification.requestPermission();
        this.log(`Notification permission: ${permission}`);
        
        return permission === 'granted';
    }

    /**
     * Setup push notifications subscription
     */
    async setupPushNotifications() {
        if (!this.serviceWorker) {
            this.log('Service worker not available for push notifications');
            return;
        }

        try {
            const subscription = await this.serviceWorker.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(window.vapidPublicKey)
            });

            this.pushSubscription = subscription;

            // Send subscription to server
            await this.sendSubscriptionToServer(subscription);
            this.log('Push notifications setup complete');

        } catch (error) {
            console.error('[NotificationClient] Push notification setup failed:', error);
        }
    }

    /**
     * Subscribe to user-specific notification channels
     */
    async subscribeToUserChannels() {
        if (!this.pusher || !this.options.userId) {
            return;
        }

        // Subscribe to personal notification channel
        const personalChannel = this.pusher.subscribe(`private-user.${this.options.userId}.notifications`);
        this.channels.set('personal', personalChannel);

        // Bind to notification events
        personalChannel.bind('notification.created', (data) => {
            this.handleNotificationCreated(data);
        });

        personalChannel.bind('notification.sent', (data) => {
            this.handleNotificationSent(data);
        });

        personalChannel.bind('notification.read', (data) => {
            this.handleNotificationRead(data);
        });

        personalChannel.bind('notifications.unread_count', (data) => {
            this.handleUnreadCountUpdate(data);
        });

        // Subscribe to desktop notification channel
        if (this.options.enableDesktop) {
            const desktopChannel = this.pusher.subscribe(`private-user.${this.options.userId}.desktop`);
            this.channels.set('desktop', desktopChannel);

            desktopChannel.bind('desktop.notification', (data) => {
                this.showDesktopNotification(data);
            });
        }

        this.log('Subscribed to user channels');
    }

    /**
     * Handle notification created event
     */
    handleNotificationCreated(data) {
        this.log('Notification created:', data);
        
        // Update unread count
        this.updateUnreadCount(this.unreadCount + 1);
        
        // Emit event for application to handle
        this.emit('notification.created', data);
        
        // Show in-app notification if configured
        if (this.options.showInAppNotifications) {
            this.showInAppNotification(data);
        }
    }

    /**
     * Handle notification sent event
     */
    handleNotificationSent(data) {
        this.log('Notification sent:', data);
        this.emit('notification.sent', data);
        
        // Play notification sound if enabled
        if (this.options.playSound) {
            this.playNotificationSound();
        }
    }

    /**
     * Handle notification read event
     */
    handleNotificationRead(data) {
        this.log('Notification read:', data);
        this.emit('notification.read', data);
    }

    /**
     * Handle unread count update
     */
    handleUnreadCountUpdate(data) {
        this.updateUnreadCount(data.unread_count);
        this.emit('unread.count.updated', data);
    }

    /**
     * Update unread notification count
     */
    updateUnreadCount(count) {
        this.unreadCount = count;
        
        // Update favicon badge
        this.updateFaviconBadge(count);
        
        // Update page title
        this.updatePageTitle(count);
        
        // Update UI elements
        document.querySelectorAll('[data-notification-count]').forEach(element => {
            element.textContent = count > 0 ? count : '';
            element.style.display = count > 0 ? 'inline' : 'none';
        });
    }

    /**
     * Show desktop notification
     */
    showDesktopNotification(data) {
        if (Notification.permission !== 'granted') {
            return;
        }

        const notification = new Notification(data.title, {
            body: data.body,
            icon: data.icon,
            badge: data.badge,
            tag: data.tag,
            requireInteraction: data.requireInteraction,
            actions: data.actions,
            data: data.data
        });

        notification.onclick = (event) => {
            event.preventDefault();
            window.focus();
            
            if (data.data && data.data.url) {
                window.location.href = data.data.url;
            }
            
            notification.close();
        };

        // Auto-close after 5 seconds unless interaction required
        if (!data.requireInteraction) {
            setTimeout(() => notification.close(), 5000);
        }
    }

    /**
     * Show in-app notification toast
     */
    showInAppNotification(data) {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.innerHTML = `
            <div class="notification-content">
                <div class="notification-title">${data.title}</div>
                <div class="notification-message">${data.message}</div>
                ${data.actions ? this.renderNotificationActions(data.actions) : ''}
            </div>
            <button class="notification-close">&times;</button>
        `;

        // Add to DOM
        const container = document.querySelector('.notification-container') || document.body;
        container.appendChild(toast);

        // Handle close button
        toast.querySelector('.notification-close').onclick = () => {
            this.removeToast(toast);
        };

        // Handle action buttons
        toast.querySelectorAll('[data-action]').forEach(button => {
            button.onclick = (e) => {
                const action = e.target.dataset.action;
                if (action === 'dismiss') {
                    this.removeToast(toast);
                } else if (data.data && data.data.url) {
                    window.location.href = data.data.url;
                }
            };
        });

        // Auto-remove after timeout
        setTimeout(() => this.removeToast(toast), this.options.toastTimeout || 5000);
    }

    /**
     * Subscribe to campaign-specific notifications
     */
    subscribeToCampaign(campaignId) {
        const channelName = `private-campaign.${campaignId}.notifications`;
        
        if (this.channels.has(channelName)) {
            return this.channels.get(channelName);
        }

        const channel = this.pusher.subscribe(channelName);
        this.channels.set(channelName, channel);

        channel.bind('campaign.updated', (data) => {
            this.emit('campaign.updated', data);
        });

        channel.bind('donation.received', (data) => {
            this.emit('campaign.donation.received', data);
        });

        return channel;
    }

    /**
     * Mark notification as read
     */
    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            if (response.ok) {
                this.log(`Notification ${notificationId} marked as read`);
            }
        } catch (error) {
            console.error('[NotificationClient] Failed to mark notification as read:', error);
        }
    }

    /**
     * Clear all notifications
     */
    async clearAll() {
        try {
            const response = await fetch('/api/notifications/clear-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            if (response.ok) {
                this.updateUnreadCount(0);
                this.log('All notifications cleared');
            }
        } catch (error) {
            console.error('[NotificationClient] Failed to clear notifications:', error);
        }
    }

    /**
     * Event handling system
     */
    on(event, handler) {
        if (!this.eventHandlers.has(event)) {
            this.eventHandlers.set(event, new Set());
        }
        this.eventHandlers.get(event).add(handler);
    }

    off(event, handler) {
        if (this.eventHandlers.has(event)) {
            this.eventHandlers.get(event).delete(handler);
        }
    }

    emit(event, data) {
        if (this.eventHandlers.has(event)) {
            this.eventHandlers.get(event).forEach(handler => {
                try {
                    handler(data);
                } catch (error) {
                    console.error(`[NotificationClient] Event handler error for ${event}:`, error);
                }
            });
        }
    }

    /**
     * Utility methods
     */
    log(message, data = null) {
        if (this.options.debug) {
            console.log(`[NotificationClient] ${message}`, data);
        }
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    updateFaviconBadge(count) {
        // Implementation for favicon badge updates
        const link = document.querySelector("link[rel~='icon']");
        if (link && count > 0) {
            // Create canvas for badge
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = 32;
            canvas.height = 32;
            
            // Draw red circle with count
            ctx.fillStyle = '#ff0000';
            ctx.beginPath();
            ctx.arc(24, 8, 8, 0, 2 * Math.PI);
            ctx.fill();
            
            ctx.fillStyle = '#ffffff';
            ctx.font = '12px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(count > 99 ? '99+' : count.toString(), 24, 12);
            
            link.href = canvas.toDataURL();
        }
    }

    updatePageTitle(count) {
        const originalTitle = document.title.replace(/^\(\d+\) /, '');
        document.title = count > 0 ? `(${count}) ${originalTitle}` : originalTitle;
    }

    playNotificationSound() {
        if (this.options.soundUrl) {
            const audio = new Audio(this.options.soundUrl);
            audio.play().catch(() => {
                // Ignore audio play errors (user interaction required)
            });
        }
    }

    renderNotificationActions(actions) {
        return actions.map(action => 
            `<button data-action="${action.action}" class="notification-action ${action.primary ? 'primary' : ''}">${action.title}</button>`
        ).join('');
    }

    removeToast(toast) {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }

    async sendSubscriptionToServer(subscription) {
        await fetch('/api/push-subscriptions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                subscription: subscription.toJSON(),
                user_id: this.options.userId,
            }),
        });
    }

    handleServiceWorkerMessage(data) {
        this.log('Service worker message:', data);
        this.emit('serviceWorker.message', data);
    }

    startHeartbeat() {
        setInterval(() => {
            if (this.pusher && this.pusher.connection.state === 'connected') {
                this.pusher.send_event('client-heartbeat', { timestamp: Date.now() });
            }
        }, 30000); // 30 seconds
    }

    /**
     * Cleanup and disconnect
     */
    disconnect() {
        if (this.pusher) {
            this.pusher.disconnect();
        }
        
        this.channels.clear();
        this.eventHandlers.clear();
        this.log('Notification client disconnected');
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationClient;
} else {
    window.NotificationClient = NotificationClient;
}