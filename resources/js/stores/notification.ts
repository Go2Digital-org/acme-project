import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '@/api/client';
import type { NotificationState, Notification, NotificationFilters, ApiResponse, PaginatedResponse } from '@/types';

export const useNotificationStore = defineStore('notification', () => {
  // State
  const notifications = ref<Notification[]>([]);
  const loading = ref(false);
  const connected = ref(false);
  const lastSeen = ref<string | null>(null);
  const filters = ref<NotificationFilters>({
    limit: 50,
    offset: 0
  });

  // Getters
  const unreadCount = computed(() => 
    notifications.value.filter(n => !n.read).length
  );

  const unreadNotifications = computed(() =>
    notifications.value.filter(n => !n.read)
  );

  const recentNotifications = computed(() =>
    notifications.value
      .sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime())
      .slice(0, 10)
  );

  const priorityNotifications = computed(() =>
    notifications.value.filter(n => n.priority === 'high' || n.priority === 'urgent')
  );

  const notificationsByCategory = computed(() => {
    const grouped: Record<string, Notification[]> = {};
    notifications.value.forEach(notification => {
      if (!grouped[notification.category]) {
        grouped[notification.category] = [];
      }
      grouped[notification.category].push(notification);
    });
    return grouped;
  });

  // Actions
  const fetchNotifications = async (customFilters?: NotificationFilters): Promise<Notification[]> => {
    loading.value = true;

    try {
      const searchParams = { ...filters.value, ...customFilters };
      const response = await api.get<Notification[]>('/notifications', searchParams);
      
      // If offset is 0, replace notifications; otherwise append for pagination
      if ((customFilters?.offset || 0) === 0) {
        notifications.value = response;
      } else {
        notifications.value = [...notifications.value, ...response];
      }
      
      return response;
    } catch (err: any) {
      console.error('Failed to fetch notifications:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchNotificationsPaginated = async (customFilters?: NotificationFilters): Promise<PaginatedResponse<Notification>> => {
    loading.value = true;

    try {
      const searchParams = { ...filters.value, ...customFilters };
      const response = await api.get<PaginatedResponse<Notification>>('/notifications/paginated', searchParams);
      
      // If it's the first page, replace notifications; otherwise append
      if ((customFilters?.offset || 0) === 0) {
        notifications.value = response.data;
      } else {
        notifications.value = [...notifications.value, ...response.data];
      }
      
      return response;
    } catch (err: any) {
      console.error('Failed to fetch paginated notifications:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const markAsRead = async (notificationId: string): Promise<void> => {
    try {
      await api.patch(`/notifications/${notificationId}/read`);
      
      const notification = notifications.value.find(n => n.id === notificationId);
      if (notification) {
        notification.read = true;
      }
    } catch (err: any) {
      console.error('Failed to mark notification as read:', err);
      throw err;
    }
  };

  const markAllAsRead = async (): Promise<void> => {
    try {
      await api.patch('/notifications/mark-all-read');
      
      notifications.value.forEach(notification => {
        notification.read = true;
      });
      
      updateLastSeen();
    } catch (err: any) {
      console.error('Failed to mark all notifications as read:', err);
      throw err;
    }
  };

  const addNotification = (notification: Notification): void => {
    notifications.value.unshift(notification);
  };

  const removeNotification = async (notificationId: string): Promise<void> => {
    try {
      await api.delete(`/notifications/${notificationId}`);
      
      const index = notifications.value.findIndex(n => n.id === notificationId);
      if (index !== -1) {
        notifications.value.splice(index, 1);
      }
    } catch (err: any) {
      console.error('Failed to remove notification:', err);
      throw err;
    }
  };

  const showToast = (
    type: Notification['type'], 
    title: string, 
    message: string, 
    duration: number = 5000
  ): void => {
    // Toast notifications are handled by the NotificationToast component
    // This method is kept for backward compatibility but delegates to the toast system
    console.log('Toast notification:', { type, title, message, duration });
  };

  const clearAll = (): void => {
    notifications.value = [];
  };

  const updateFilters = (newFilters: Partial<NotificationFilters>): void => {
    filters.value = { ...filters.value, ...newFilters };
  };

  const clearFilters = (): void => {
    filters.value = {
      limit: 50,
      offset: 0
    };
  };

  const updateLastSeen = (): void => {
    lastSeen.value = new Date().toISOString();
  };

  const setConnectionStatus = (status: boolean): void => {
    connected.value = status;
  };

  const handleRealtimeNotification = (notification: Notification): void => {
    addNotification(notification);
    
    // Show browser notification if permission is granted
    if ('Notification' in window && Notification.permission === 'granted') {
      const browserNotification = new Notification(notification.title, {
        body: notification.message,
        icon: '/favicon.ico',
        tag: notification.id,
        requireInteraction: notification.priority === 'urgent',
      });
      
      // Auto-close after 5 seconds unless it's urgent
      if (notification.priority !== 'urgent') {
        setTimeout(() => {
          browserNotification.close();
        }, 5000);
      }
      
      // Handle notification click
      browserNotification.onclick = () => {
        window.focus();
        if (notification.actionUrl) {
          window.location.href = notification.actionUrl;
        }
        browserNotification.close();
      };
    }
  };

  const bulkMarkAsRead = async (notificationIds: string[]): Promise<void> => {
    try {
      await api.patch('/notifications/bulk-mark-read', { ids: notificationIds });
      
      notificationIds.forEach(id => {
        const notification = notifications.value.find(n => n.id === id);
        if (notification) {
          notification.read = true;
        }
      });
    } catch (err: any) {
      console.error('Failed to bulk mark notifications as read:', err);
      throw err;
    }
  };

  const bulkRemove = async (notificationIds: string[]): Promise<void> => {
    try {
      await api.delete('/notifications/bulk-remove', { data: { ids: notificationIds } });
      
      notificationIds.forEach(id => {
        const index = notifications.value.findIndex(n => n.id === id);
        if (index !== -1) {
          notifications.value.splice(index, 1);
        }
      });
    } catch (err: any) {
      console.error('Failed to bulk remove notifications:', err);
      throw err;
    }
  };

  const getNotificationById = (id: string): Notification | undefined => {
    return notifications.value.find(n => n.id === id);
  };

  const searchNotifications = async (query: string): Promise<Notification[]> => {
    try {
      const response = await api.get<Notification[]>('/notifications/search', { q: query });
      return response;
    } catch (err: any) {
      console.error('Failed to search notifications:', err);
      throw err;
    }
  };

  return {
    // State
    notifications,
    loading,
    connected,
    lastSeen,
    filters,

    // Getters
    unreadCount,
    unreadNotifications,
    recentNotifications,
    priorityNotifications,
    notificationsByCategory,

    // Actions
    fetchNotifications,
    fetchNotificationsPaginated,
    markAsRead,
    markAllAsRead,
    addNotification,
    removeNotification,
    showToast,
    clearAll,
    updateFilters,
    clearFilters,
    updateLastSeen,
    setConnectionStatus,
    handleRealtimeNotification,
    bulkMarkAsRead,
    bulkRemove,
    getNotificationById,
    searchNotifications
  };
});