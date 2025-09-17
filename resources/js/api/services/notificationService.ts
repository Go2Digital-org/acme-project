import { api } from '@/api/client';
import type { 
  Notification, 
  NotificationFilters, 
  NotificationPreferences,
  PaginatedResponse,
  ApiResponse 
} from '@/types';

/**
 * Notification API Service
 * 
 * Provides all notification-related API calls for the ACME Corp CSR Platform
 */
export class NotificationService {
  // Notification CRUD Operations
  
  /**
   * Fetch notifications with optional filters
   */
  static async getNotifications(filters?: NotificationFilters): Promise<Notification[]> {
    return api.get<Notification[]>('/notifications', filters);
  }

  /**
   * Fetch paginated notifications
   */
  static async getPaginatedNotifications(
    filters?: NotificationFilters
  ): Promise<PaginatedResponse<Notification>> {
    return api.get<PaginatedResponse<Notification>>('/notifications/paginated', filters);
  }

  /**
   * Get a specific notification by ID
   */
  static async getNotification(id: string): Promise<Notification> {
    return api.get<Notification>(`/notifications/${id}`);
  }

  /**
   * Search notifications by query string
   */
  static async searchNotifications(
    query: string, 
    filters?: Partial<NotificationFilters>
  ): Promise<Notification[]> {
    return api.get<Notification[]>('/notifications/search', { 
      q: query, 
      ...filters 
    });
  }

  /**
   * Get unread notification count
   */
  static async getUnreadCount(): Promise<number> {
    const response = await api.get<{ count: number }>('/notifications/unread-count');
    return response.count;
  }

  // Notification State Management

  /**
   * Mark a single notification as read
   */
  static async markAsRead(id: string): Promise<void> {
    await api.patch(`/notifications/${id}/read`);
  }

  /**
   * Mark a single notification as unread
   */
  static async markAsUnread(id: string): Promise<void> {
    await api.patch(`/notifications/${id}/unread`);
  }

  /**
   * Mark all notifications as read
   */
  static async markAllAsRead(): Promise<void> {
    await api.patch('/notifications/mark-all-read');
  }

  /**
   * Mark multiple notifications as read
   */
  static async bulkMarkAsRead(ids: string[]): Promise<void> {
    await api.patch('/notifications/bulk-mark-read', { ids });
  }

  /**
   * Mark multiple notifications as unread
   */
  static async bulkMarkAsUnread(ids: string[]): Promise<void> {
    await api.patch('/notifications/bulk-mark-unread', { ids });
  }

  // Notification Deletion

  /**
   * Delete a single notification
   */
  static async deleteNotification(id: string): Promise<void> {
    await api.delete(`/notifications/${id}`);
  }

  /**
   * Delete multiple notifications
   */
  static async bulkDelete(ids: string[]): Promise<void> {
    await api.delete('/notifications/bulk-delete', { data: { ids } });
  }

  /**
   * Clear all read notifications
   */
  static async clearReadNotifications(): Promise<void> {
    await api.delete('/notifications/clear-read');
  }

  /**
   * Clear all notifications
   */
  static async clearAllNotifications(): Promise<void> {
    await api.delete('/notifications/clear-all');
  }

  // Notification Categories and Statistics

  /**
   * Get notifications grouped by category
   */
  static async getNotificationsByCategory(): Promise<Record<string, Notification[]>> {
    return api.get<Record<string, Notification[]>>('/notifications/by-category');
  }

  /**
   * Get notification statistics
   */
  static async getNotificationStats(): Promise<{
    total: number;
    unread: number;
    byCategory: Record<string, number>;
    byType: Record<string, number>;
    byPriority: Record<string, number>;
  }> {
    return api.get('/notifications/stats');
  }

  /**
   * Get notifications for a specific time period
   */
  static async getNotificationsByDateRange(
    startDate: string,
    endDate: string,
    filters?: Partial<NotificationFilters>
  ): Promise<Notification[]> {
    return api.get<Notification[]>('/notifications/date-range', {
      start_date: startDate,
      end_date: endDate,
      ...filters
    });
  }

  // Notification Preferences

  /**
   * Get user's notification preferences
   */
  static async getPreferences(): Promise<NotificationPreferences> {
    return api.get<NotificationPreferences>('/notification-preferences');
  }

  /**
   * Update user's notification preferences
   */
  static async updatePreferences(preferences: Partial<NotificationPreferences>): Promise<NotificationPreferences> {
    return api.put<NotificationPreferences>('/notification-preferences', preferences);
  }

  /**
   * Create default notification preferences for user
   */
  static async createDefaultPreferences(): Promise<NotificationPreferences> {
    return api.post<NotificationPreferences>('/notification-preferences/default');
  }

  /**
   * Reset preferences to default values
   */
  static async resetPreferencesToDefault(): Promise<NotificationPreferences> {
    return api.post<NotificationPreferences>('/notification-preferences/reset');
  }

  // Real-time and WebSocket Management

  /**
   * Subscribe to real-time notifications for the current user
   */
  static async subscribeToNotifications(): Promise<{ channel: string; token?: string }> {
    return api.post<{ channel: string; token?: string }>('/notifications/subscribe');
  }

  /**
   * Unsubscribe from real-time notifications
   */
  static async unsubscribeFromNotifications(): Promise<void> {
    await api.post('/notifications/unsubscribe');
  }

  /**
   * Get WebSocket connection info
   */
  static async getWebSocketInfo(): Promise<{
    url: string;
    channels: string[];
    authToken?: string;
  }> {
    return api.get('/notifications/websocket-info');
  }

  // Admin and Testing Functions

  /**
   * Create a test notification (admin only)
   */
  static async createTestNotification(data: {
    type: Notification['type'];
    category: Notification['category'];
    title: string;
    message: string;
    priority?: Notification['priority'];
    userId?: number;
  }): Promise<Notification> {
    return api.post<Notification>('/admin/notifications/test', data);
  }

  /**
   * Send notification to specific user (admin only)
   */
  static async sendNotificationToUser(
    userId: number,
    notification: {
      type: Notification['type'];
      category: Notification['category'];
      title: string;
      message: string;
      priority?: Notification['priority'];
      actionUrl?: string;
      actionText?: string;
      data?: Record<string, any>;
    }
  ): Promise<Notification> {
    return api.post<Notification>('/admin/notifications/send', {
      user_id: userId,
      ...notification
    });
  }

  /**
   * Broadcast notification to multiple users (admin only)
   */
  static async broadcastNotification(
    userIds: number[],
    notification: {
      type: Notification['type'];
      category: Notification['category'];
      title: string;
      message: string;
      priority?: Notification['priority'];
      actionUrl?: string;
      actionText?: string;
      data?: Record<string, any>;
    }
  ): Promise<{ sent: number; failed: number }> {
    return api.post<{ sent: number; failed: number }>('/admin/notifications/broadcast', {
      user_ids: userIds,
      ...notification
    });
  }

  /**
   * Get notification delivery logs (admin only)
   */
  static async getDeliveryLogs(
    notificationId?: string,
    userId?: number,
    limit?: number
  ): Promise<{
    id: string;
    notification_id: string;
    user_id: number;
    channel: 'email' | 'browser' | 'sms';
    status: 'pending' | 'sent' | 'delivered' | 'failed';
    sent_at?: string;
    delivered_at?: string;
    error_message?: string;
  }[]> {
    return api.get('/admin/notifications/delivery-logs', {
      notification_id: notificationId,
      user_id: userId,
      limit
    });
  }

  // Utility Functions

  /**
   * Test notification delivery channels
   */
  static async testDeliveryChannels(): Promise<{
    email: boolean;
    browser: boolean;
    sms: boolean;
    websocket: boolean;
  }> {
    return api.get('/notifications/test-channels');
  }

  /**
   * Get available notification templates
   */
  static async getNotificationTemplates(): Promise<{
    id: string;
    name: string;
    category: string;
    type: string;
    template: string;
    variables: string[];
  }[]> {
    return api.get('/notifications/templates');
  }

  /**
   * Validate notification data before sending
   */
  static async validateNotification(data: {
    type: Notification['type'];
    category: Notification['category'];
    title: string;
    message: string;
    userId?: number;
  }): Promise<{ valid: boolean; errors: string[] }> {
    return api.post('/notifications/validate', data);
  }

  /**
   * Get notification system health
   */
  static async getSystemHealth(): Promise<{
    status: 'healthy' | 'degraded' | 'down';
    websocket: boolean;
    database: boolean;
    email: boolean;
    sms: boolean;
    queues: boolean;
    lastCheck: string;
  }> {
    return api.get('/notifications/health');
  }

  // Export/Import Functions

  /**
   * Export user's notifications to CSV/JSON
   */
  static async exportNotifications(
    format: 'csv' | 'json' = 'json',
    filters?: NotificationFilters
  ): Promise<Blob> {
    const response = await fetch('/api/notifications/export?' + new URLSearchParams({
      format,
      ...filters as any
    }).toString(), {
      headers: {
        'Authorization': `Bearer ${api.get}`, // This would need to be properly implemented
        'Accept': format === 'csv' ? 'text/csv' : 'application/json'
      }
    });

    if (!response.ok) {
      throw new Error('Export failed');
    }

    return response.blob();
  }

  /**
   * Get notification analytics data
   */
  static async getAnalytics(
    timeframe: 'day' | 'week' | 'month' | 'year' = 'week'
  ): Promise<{
    totalSent: number;
    totalRead: number;
    readRate: number;
    byCategory: Record<string, number>;
    byType: Record<string, number>;
    byChannel: Record<string, number>;
    timeline: Array<{
      date: string;
      sent: number;
      read: number;
    }>;
  }> {
    return api.get(`/notifications/analytics?timeframe=${timeframe}`);
  }
}

// Export convenience functions for direct use
export const notificationApi = {
  // Basic operations
  get: NotificationService.getNotifications,
  getPaginated: NotificationService.getPaginatedNotifications,
  getById: NotificationService.getNotification,
  search: NotificationService.searchNotifications,
  getUnreadCount: NotificationService.getUnreadCount,

  // State management
  markRead: NotificationService.markAsRead,
  markUnread: NotificationService.markAsUnread,
  markAllRead: NotificationService.markAllAsRead,
  bulkMarkRead: NotificationService.bulkMarkAsRead,
  
  // Deletion
  delete: NotificationService.deleteNotification,
  bulkDelete: NotificationService.bulkDelete,
  clearRead: NotificationService.clearReadNotifications,
  clearAll: NotificationService.clearAllNotifications,

  // Preferences
  getPreferences: NotificationService.getPreferences,
  updatePreferences: NotificationService.updatePreferences,
  resetPreferences: NotificationService.resetPreferencesToDefault,

  // Real-time
  subscribe: NotificationService.subscribeToNotifications,
  unsubscribe: NotificationService.unsubscribeFromNotifications,
  getWebSocketInfo: NotificationService.getWebSocketInfo,

  // Analytics
  getStats: NotificationService.getNotificationStats,
  getAnalytics: NotificationService.getAnalytics,
  getHealth: NotificationService.getSystemHealth
};

export default NotificationService;