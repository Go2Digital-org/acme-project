import { ref, onBeforeUnmount, computed } from 'vue';
import { useNotificationStore } from '@/stores/notification';
import { useNotificationPreferencesStore } from '@/stores/notificationPreferences';
import type { Notification } from '@/types';

interface EchoConfig {
  userChannels?: string[];
  adminChannels?: string[];
  reconnectAttempts?: number;
  reconnectDelay?: number;
}

declare global {
  interface Window {
    Echo: any;
    $filament?: {
      notify: (type: string, title: string, message: string) => void;
    };
  }
}

/**
 * Laravel Echo WebSocket Composable for Real-time Notifications
 * 
 * Handles Laravel Echo connections and real-time notification processing
 * for the ACME Corp CSR Platform notification system.
 */
export function useNotificationWebSocket(config: EchoConfig = {}) {
  // Configuration with defaults
  const {
    reconnectAttempts = 5,
    reconnectDelay = 5000,
    userChannels = [],
    adminChannels = ['admin-dashboard', 'admin-role-super_admin', 'admin-role-csr_admin', 'admin-role-finance_admin'],
  } = config;

  // Stores
  const notificationStore = useNotificationStore();
  const preferencesStore = useNotificationPreferencesStore();

  // State
  const echoConnection = ref<any>(null);
  const adminConnection = ref<any>(null);
  const isConnected = ref(false);
  const isConnecting = ref(false);
  const connectionAttempts = ref(0);
  const lastError = ref<string | null>(null);
  const subscribedChannels = ref<Set<string>>(new Set());

  // User info from meta tags
  const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
  const userRole = document.querySelector('meta[name="user-role"]')?.getAttribute('content');

  // Computed
  const connectionStatus = computed(() => {
    if (isConnecting.value) return 'connecting';
    if (isConnected.value) return 'connected';
    if (lastError.value) return 'error';
    return 'disconnected';
  });

  const canReconnect = computed(() => {
    return connectionAttempts.value < reconnectAttempts;
  });

  const isAdmin = computed(() => {
    return userRole && ['super_admin', 'csr_admin', 'finance_admin', 'hr_manager'].includes(userRole);
  });

  // Laravel Echo Connection Management
  const connect = async (): Promise<void> => {
    if (!window.Echo) {
      console.error('Laravel Echo not available');
      lastError.value = 'Laravel Echo not loaded';
      return;
    }

    if (isConnected.value) {
      console.warn('Already connected to Laravel Echo');
      return;
    }

    if (isConnecting.value) {
      console.warn('Connection already in progress');
      return;
    }

    isConnecting.value = true;
    lastError.value = null;

    try {
      // Connect to user-specific private channel
      if (userId) {
        await connectToUserChannel(userId);
      }

      // Connect to admin channels if user is admin
      if (isAdmin.value) {
        await connectToAdminChannels();
      }

      isConnected.value = true;
      isConnecting.value = false;
      connectionAttempts.value = 0;
      notificationStore.setConnectionStatus(true);

      console.log('Successfully connected to Laravel Echo channels');

    } catch (error) {
      console.error('Failed to connect to Laravel Echo:', error);
      isConnecting.value = false;
      lastError.value = error instanceof Error ? error.message : 'Connection failed';
      scheduleReconnect();
    }
  };

  const disconnect = (): void => {
    // Disconnect user channel
    if (echoConnection.value) {
      try {
        echoConnection.value.stopListening('.notification.created');
        echoConnection.value.stopListening('.notification.updated');
        echoConnection.value.stopListening('.notification.deleted');
      } catch (error) {
        console.warn('Error stopping user channel listeners:', error);
      }
      echoConnection.value = null;
    }

    // Disconnect admin channels
    if (adminConnection.value) {
      try {
        // Stop listening to all admin events
        adminConnection.value.stopListening('.donation.large');
        adminConnection.value.stopListening('.campaign.milestone');
        adminConnection.value.stopListening('.campaign.approval_needed');
        adminConnection.value.stopListening('.payment.failed');
        adminConnection.value.stopListening('.security.alert');
        adminConnection.value.stopListening('.system.maintenance');
        adminConnection.value.stopListening('.compliance.issues');
        adminConnection.value.stopListening('.system.test');
      } catch (error) {
        console.warn('Error stopping admin channel listeners:', error);
      }
      adminConnection.value = null;
    }

    isConnected.value = false;
    isConnecting.value = false;
    connectionAttempts.value = 0;
    subscribedChannels.value.clear();
    notificationStore.setConnectionStatus(false);

    console.log('Disconnected from Laravel Echo');
  };

  // User Channel Connection
  const connectToUserChannel = async (userId: string): Promise<void> => {
    try {
      echoConnection.value = window.Echo.private(`user.notifications.${userId}`)
        .listen('.notification.created', (data: any) => {
          console.log('Personal notification received:', data);
          handlePersonalNotification(data);
        })
        .listen('.notification.updated', (data: any) => {
          console.log('Notification update received:', data);
          handleNotificationUpdate(data);
        })
        .listen('.notification.deleted', (data: any) => {
          console.log('Notification deleted:', data);
          handleNotificationDeleted(data);
        });

      subscribedChannels.value.add(`user.notifications.${userId}`);
      console.log(`Connected to personal channel: user.notifications.${userId}`);

    } catch (error) {
      console.error('Failed to connect to user channel:', error);
      throw error;
    }
  };

  // Admin Channels Connection
  const connectToAdminChannels = async (): Promise<void> => {
    try {
      // Connect to main admin dashboard channel
      adminConnection.value = window.Echo.channel('admin-dashboard')
        .listen('.donation.large', (data: any) => {
          console.log('Large donation notification:', data);
          handleLargeDonationNotification(data);
        })
        .listen('.campaign.milestone', (data: any) => {
          console.log('Campaign milestone notification:', data);
          handleCampaignMilestoneNotification(data);
        })
        .listen('.campaign.approval_needed', (data: any) => {
          console.log('Campaign approval needed:', data);
          handleApprovalNeededNotification(data);
        })
        .listen('.payment.failed', (data: any) => {
          console.log('Payment failure notification:', data);
          handlePaymentFailedNotification(data);
        })
        .listen('.system.test', (data: any) => {
          console.log('System test notification:', data);
          handleTestNotification(data);
        });

      subscribedChannels.value.add('admin-dashboard');

      // Connect to role-specific channel
      if (userRole) {
        window.Echo.channel(`admin-role-${userRole}`)
          .listen('.security.alert', (data: any) => {
            console.log('Security alert received:', data);
            handleSecurityAlertNotification(data);
          })
          .listen('.compliance.issues', (data: any) => {
            console.log('Compliance issues received:', data);
            handleComplianceNotification(data);
          });

        subscribedChannels.value.add(`admin-role-${userRole}`);
      }

      // Connect to system maintenance channel
      window.Echo.channel('system-maintenance')
        .listen('.system.maintenance', (data: any) => {
          console.log('Maintenance notification:', data);
          handleMaintenanceNotification(data);
        });

      subscribedChannels.value.add('system-maintenance');

      console.log(`Connected to admin channels for role: ${userRole}`);

    } catch (error) {
      console.error('Failed to connect to admin channels:', error);
      throw error;
    }
  };

  // Notification Handlers
  const handlePersonalNotification = (data: any): void => {
    // Create notification object from broadcast data
    const notification: Notification = {
      id: data.id || `temp-${Date.now()}`,
      type: data.type,
      title: data.title,
      message: data.message,
      priority: data.priority || 'normal',
      category: data.category || 'general',
      read: false,
      timestamp: data.created_at || new Date().toISOString(),
      actionUrl: data.actions?.[0]?.url || null,
      data: data.metadata || {},
    };

    // Check user preferences before showing
    if (preferencesStore.shouldSuppressNotification(notification.category, notification.priority)) {
      console.log('Notification suppressed due to user preferences:', notification.id);
      return;
    }

    // Add to store
    notificationStore.handleRealtimeNotification(notification);
    
    // Show browser notification for high priority
    if (notification.priority === 'high' || notification.priority === 'urgent') {
      showBrowserNotification(notification);
    }

    // Play sound
    playNotificationSound(notification);
  };

  const handleLargeDonationNotification = (data: any): void => {
    showFilamentNotification(
      'success',
      'Large Donation Received!',
      `${data.donor_name || 'Anonymous donor'} donated ${data.formatted_amount} to ${data.campaign_title}`
    );

    // Update dashboard metrics if available
    updateDashboardMetric('total-raised', data.amount, 'add');
    updateDashboardMetric('recent-donations', 1, 'add');
  };

  const handleCampaignMilestoneNotification = (data: any): void => {
    const milestoneMessages: Record<string, string> = {
      '25': 'reached 25% of its goal!',
      '50': 'is halfway to its goal!',
      '75': 'is 75% funded!',
      '100': 'has reached its goal!',
      'goal_exceeded': 'has exceeded its goal!'
    };

    const message = milestoneMessages[data.milestone] || `reached a milestone: ${data.milestone}%`;

    showFilamentNotification(
      data.milestone === '100' || data.milestone === 'goal_exceeded' ? 'success' : 'info',
      'Campaign Milestone!',
      `${data.campaign_title} ${message}`
    );

    // Update campaign progress if on campaigns page
    updateCampaignProgress(data.campaign_id, data.progress_percentage);
  };

  const handleApprovalNeededNotification = (data: any): void => {
    showFilamentNotification(
      'warning',
      'Campaign Needs Approval',
      `${data.campaign_title} by ${data.manager_name} requires approval`
    );

    // Update approvals badge
    updateApprovalsBadge(1);
  };

  const handlePaymentFailedNotification = (data: any): void => {
    showFilamentNotification(
      'danger',
      'Payment Failed',
      `${data.formatted_amount} payment failed: ${data.failure_reason}`
    );
  };

  const handleSecurityAlertNotification = (data: any): void => {
    showFilamentNotification(
      'danger',
      'Security Alert',
      `${data.event}: ${data.details?.message || 'Suspicious activity detected'}`
    );

    // Flash security alert for high severity
    if (data.severity === 'high') {
      flashSecurityAlert();
    }
  };

  const handleComplianceNotification = (data: any): void => {
    showFilamentNotification(
      data.severity === 'high' ? 'danger' : 'warning',
      'Compliance Issues Detected',
      `${data.organization_name}: ${data.issues?.join(', ') || 'Review required'}`
    );
  };

  const handleMaintenanceNotification = (data: any): void => {
    showFilamentNotification(
      'warning',
      'System Maintenance Scheduled',
      `${data.type} maintenance scheduled for ${data.scheduled_for ? new Date(data.scheduled_for).toLocaleString() : 'soon'}`
    );
  };

  const handleTestNotification = (data: any): void => {
    showFilamentNotification(
      'info',
      'Test Notification',
      data.message || 'This is a test broadcast to verify WebSocket connectivity.'
    );
  };

  const handleNotificationUpdate = (data: any): void => {
    // Update existing notification in store
    const notification = notificationStore.getNotificationById(data.id);
    if (notification) {
      Object.assign(notification, data);
    }
  };

  const handleNotificationDeleted = (data: any): void => {
    // Remove notification from store
    notificationStore.removeNotification(data.id);
  };

  // Utility Functions
  const showFilamentNotification = (type: string, title: string, message: string): void => {
    if (window.$filament) {
      window.$filament.notify(type, title, message);
    } else {
      // Fallback to console log
      console.log(`[${type.toUpperCase()}] ${title}: ${message}`);
    }
  };

  const showBrowserNotification = (notification: Notification): void => {
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(notification.title, {
        body: notification.message,
        icon: '/favicon.ico',
        tag: 'notification-' + notification.id
      });
    }
  };

  const playNotificationSound = (notification: Notification): void => {
    if (!preferencesStore.isBrowserEnabled) return;
    
    try {
      const audio = new Audio(`/sounds/notification-${notification.type}.mp3`);
      audio.volume = 0.3;
      audio.play().catch(() => {
        // Ignore audio play errors (user hasn't interacted with page yet)
      });
    } catch (error) {
      // Sound file not available, ignore
    }
  };

  const updateDashboardMetric = (metric: string, value: number, operation: string = 'set'): void => {
    const metricElement = document.querySelector(`[data-metric="${metric}"]`);
    if (!metricElement) return;

    let currentValue = parseFloat(metricElement.textContent?.replace(/[^0-9.-]+/g, '') || '0');
    let newValue: number;

    switch (operation) {
      case 'add':
        newValue = currentValue + value;
        break;
      case 'subtract':
        newValue = currentValue - value;
        break;
      default:
        newValue = value;
    }

    metricElement.textContent = formatMetricValue(metric, newValue);
    metricElement.classList.add('metric-updated');
    setTimeout(() => metricElement.classList.remove('metric-updated'), 2000);
  };

  const formatMetricValue = (metric: string, value: number): string => {
    if (metric.includes('amount') || metric.includes('raised') || metric.includes('donation')) {
      return '$' + value.toLocaleString();
    } else if (metric.includes('percentage') || metric.includes('rate')) {
      return value.toFixed(1) + '%';
    } else {
      return Math.floor(value).toLocaleString();
    }
  };

  const updateCampaignProgress = (campaignId: string, progressPercentage: number): void => {
    const progressBars = document.querySelectorAll(`[data-campaign-id="${campaignId}"] .progress-bar`);
    progressBars.forEach(bar => {
      (bar as HTMLElement).style.width = `${progressPercentage}%`;
      bar.setAttribute('aria-valuenow', progressPercentage.toString());
    });
  };

  const updateApprovalsBadge = (increment: number): void => {
    const badge = document.querySelector('.approvals-badge');
    if (badge) {
      const currentCount = parseInt(badge.textContent || '0') || 0;
      badge.textContent = (currentCount + increment).toString();
      badge.classList.add('badge-updated');
      setTimeout(() => badge.classList.remove('badge-updated'), 2000);
    }
  };

  const flashSecurityAlert = (): void => {
    document.body.classList.add('security-alert-flash');
    setTimeout(() => {
      document.body.classList.remove('security-alert-flash');
    }, 3000);
  };

  // Reconnection Logic
  const scheduleReconnect = (): void => {
    if (!canReconnect.value) {
      console.error('Max reconnection attempts reached');
      return;
    }

    connectionAttempts.value++;
    const delay = reconnectDelay * Math.pow(1.5, connectionAttempts.value - 1);
    
    console.log(`Scheduling reconnection attempt ${connectionAttempts.value} in ${delay}ms`);
    
    setTimeout(() => {
      connect();
    }, delay);
  };

  // Manual Actions
  const subscribeToChannel = (channelName: string): void => {
    if (subscribedChannels.value.has(channelName)) {
      console.warn(`Already subscribed to channel: ${channelName}`);
      return;
    }

    try {
      window.Echo.channel(channelName);
      subscribedChannels.value.add(channelName);
      console.log(`Manually subscribed to channel: ${channelName}`);
    } catch (error) {
      console.error(`Failed to subscribe to channel ${channelName}:`, error);
    }
  };

  const unsubscribeFromChannel = (channelName: string): void => {
    if (!subscribedChannels.value.has(channelName)) {
      console.warn(`Not subscribed to channel: ${channelName}`);
      return;
    }

    try {
      window.Echo.leaveChannel(channelName);
      subscribedChannels.value.delete(channelName);
      console.log(`Unsubscribed from channel: ${channelName}`);
    } catch (error) {
      console.error(`Failed to unsubscribe from channel ${channelName}:`, error);
    }
  };

  const getConnectionInfo = () => ({
    isConnected: isConnected.value,
    isConnecting: isConnecting.value,
    connectionAttempts: connectionAttempts.value,
    lastError: lastError.value,
    subscribedChannels: Array.from(subscribedChannels.value),
    status: connectionStatus.value,
    userId,
    userRole,
    isAdmin: isAdmin.value
  });

  // Cleanup on unmount
  onBeforeUnmount(() => {
    disconnect();
  });

  return {
    // State
    isConnected,
    isConnecting,
    connectionStatus,
    lastError,
    connectionAttempts,
    subscribedChannels: computed(() => Array.from(subscribedChannels.value)),

    // Actions
    connect,
    disconnect,
    subscribeToChannel,
    unsubscribeFromChannel,
    getConnectionInfo
  };
}