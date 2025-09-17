import { formatDistanceToNow, format, isToday, isYesterday, parseISO } from 'date-fns';
import type { Notification, NotificationAction } from '@/types';

/**
 * Notification Utility Functions
 * 
 * A comprehensive set of utility functions for formatting, processing, and managing
 * notifications in the ACME Corp CSR Platform.
 */

// Date and Time Formatting
export const formatNotificationTimestamp = (timestamp: string): string => {
  try {
    const date = parseISO(timestamp);
    
    if (isToday(date)) {
      return format(date, 'HH:mm');
    } else if (isYesterday(date)) {
      return `Yesterday ${format(date, 'HH:mm')}`;
    } else {
      return format(date, 'MMM d, yyyy');
    }
  } catch (error) {
    console.error('Invalid timestamp:', timestamp);
    return 'Just now';
  }
};

export const formatRelativeTime = (timestamp: string): string => {
  try {
    const date = parseISO(timestamp);
    return formatDistanceToNow(date, { addSuffix: true });
  } catch (error) {
    console.error('Invalid timestamp:', timestamp);
    return 'Just now';
  }
};

export const formatDetailedTimestamp = (timestamp: string): string => {
  try {
    const date = parseISO(timestamp);
    return format(date, 'PPpp'); // e.g., "Apr 29, 2023 at 11:59:59 AM"
  } catch (error) {
    console.error('Invalid timestamp:', timestamp);
    return 'Unknown time';
  }
};

// Icon and Visual Helpers
export const getNotificationIcon = (notification: Notification): string => {
  const iconMap: Record<string, string> = {
    // By category
    donation: 'heroicon-o-banknotes',
    campaign: 'heroicon-o-megaphone',
    milestone: 'heroicon-o-trophy',
    security: 'heroicon-o-shield-exclamation',
    system: 'heroicon-o-cog-6-tooth',
    payment: 'heroicon-o-credit-card',
    approval: 'heroicon-o-clock',
    organization: 'heroicon-o-building-office-2',
    maintenance: 'heroicon-o-wrench-screwdriver',
    
    // By type (fallback)
    success: 'heroicon-o-check-circle',
    error: 'heroicon-o-x-circle',
    warning: 'heroicon-o-exclamation-triangle',
    info: 'heroicon-o-information-circle'
  };

  // Check by specific notification icon first
  if (notification.icon) {
    return notification.icon;
  }

  // Then by category
  if (iconMap[notification.category]) {
    return iconMap[notification.category];
  }

  // Finally by type
  return iconMap[notification.type] || 'heroicon-o-bell';
};

export const getNotificationColor = (notification: Notification): {
  bg: string;
  text: string;
  border: string;
  icon: string;
} => {
  const colorMap: Record<string, { bg: string; text: string; border: string; icon: string }> = {
    // By priority first
    urgent: {
      bg: 'bg-red-50',
      text: 'text-red-900',
      border: 'border-red-200',
      icon: 'text-red-600'
    },
    high: {
      bg: 'bg-orange-50',
      text: 'text-orange-900',
      border: 'border-orange-200',
      icon: 'text-orange-600'
    },
    medium: {
      bg: 'bg-blue-50',
      text: 'text-blue-900',
      border: 'border-blue-200',
      icon: 'text-blue-600'
    },
    low: {
      bg: 'bg-gray-50',
      text: 'text-gray-900',
      border: 'border-gray-200',
      icon: 'text-gray-600'
    },

    // By category
    donation: {
      bg: 'bg-green-50',
      text: 'text-green-900',
      border: 'border-green-200',
      icon: 'text-green-600'
    },
    campaign: {
      bg: 'bg-blue-50',
      text: 'text-blue-900',
      border: 'border-blue-200',
      icon: 'text-blue-600'
    },
    security: {
      bg: 'bg-red-50',
      text: 'text-red-900',
      border: 'border-red-200',
      icon: 'text-red-600'
    },
    milestone: {
      bg: 'bg-yellow-50',
      text: 'text-yellow-900',
      border: 'border-yellow-200',
      icon: 'text-yellow-600'
    },

    // By type (fallback)
    success: {
      bg: 'bg-green-50',
      text: 'text-green-900',
      border: 'border-green-200',
      icon: 'text-green-600'
    },
    error: {
      bg: 'bg-red-50',
      text: 'text-red-900',
      border: 'border-red-200',
      icon: 'text-red-600'
    },
    warning: {
      bg: 'bg-yellow-50',
      text: 'text-yellow-900',
      border: 'border-yellow-200',
      icon: 'text-yellow-600'
    },
    info: {
      bg: 'bg-blue-50',
      text: 'text-blue-900',
      border: 'border-blue-200',
      icon: 'text-blue-600'
    }
  };

  // Check by priority first (most important)
  if (colorMap[notification.priority]) {
    return colorMap[notification.priority];
  }

  // Then by category
  if (colorMap[notification.category]) {
    return colorMap[notification.category];
  }

  // Finally by type
  return colorMap[notification.type] || colorMap.info;
};

// Text Formatting and Processing
export const truncateText = (text: string, maxLength: number = 100): string => {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength).trim() + '...';
};

export const stripHtml = (html: string): string => {
  const div = document.createElement('div');
  div.innerHTML = html;
  return div.textContent || div.innerText || '';
};

export const formatCategoryName = (category: string): string => {
  const categoryNames: Record<string, string> = {
    donation: 'Donation',
    campaign: 'Campaign',
    milestone: 'Milestone',
    security: 'Security',
    system: 'System',
    payment: 'Payment',
    approval: 'Approval',
    organization: 'Organization',
    maintenance: 'Maintenance'
  };

  return categoryNames[category] || category.charAt(0).toUpperCase() + category.slice(1);
};

export const formatPriorityName = (priority: string): string => {
  const priorityNames: Record<string, string> = {
    low: 'Low Priority',
    medium: 'Medium Priority',
    high: 'High Priority',
    urgent: 'Urgent'
  };

  return priorityNames[priority] || priority;
};

export const formatTypeName = (type: string): string => {
  const typeNames: Record<string, string> = {
    success: 'Success',
    error: 'Error',
    warning: 'Warning',
    info: 'Information',
    donation: 'Donation',
    campaign: 'Campaign',
    milestone: 'Milestone'
  };

  return typeNames[type] || type.charAt(0).toUpperCase() + type.slice(1);
};

// Notification Grouping and Sorting
export const groupNotificationsByDate = (notifications: Notification[]): Record<string, Notification[]> => {
  const groups: Record<string, Notification[]> = {};
  
  notifications.forEach(notification => {
    try {
      const date = parseISO(notification.timestamp);
      let groupKey: string;
      
      if (isToday(date)) {
        groupKey = 'Today';
      } else if (isYesterday(date)) {
        groupKey = 'Yesterday';
      } else {
        groupKey = format(date, 'MMMM d, yyyy');
      }
      
      if (!groups[groupKey]) {
        groups[groupKey] = [];
      }
      
      groups[groupKey].push(notification);
    } catch (error) {
      // Invalid timestamp, put in "Unknown" group
      if (!groups['Unknown']) {
        groups['Unknown'] = [];
      }
      groups['Unknown'].push(notification);
    }
  });
  
  // Sort notifications within each group
  Object.keys(groups).forEach(key => {
    groups[key].sort((a, b) => 
      new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
    );
  });
  
  return groups;
};

export const groupNotificationsByCategory = (notifications: Notification[]): Record<string, Notification[]> => {
  return notifications.reduce((groups, notification) => {
    const category = formatCategoryName(notification.category);
    if (!groups[category]) {
      groups[category] = [];
    }
    groups[category].push(notification);
    return groups;
  }, {} as Record<string, Notification[]>);
};

export const sortNotifications = (
  notifications: Notification[],
  sortBy: 'timestamp' | 'priority' | 'category' | 'read' = 'timestamp',
  sortOrder: 'asc' | 'desc' = 'desc'
): Notification[] => {
  const sorted = [...notifications];
  
  sorted.sort((a, b) => {
    let comparison = 0;
    
    switch (sortBy) {
      case 'timestamp':
        comparison = new Date(a.timestamp).getTime() - new Date(b.timestamp).getTime();
        break;
      case 'priority':
        const priorityOrder = { urgent: 4, high: 3, medium: 2, low: 1 };
        comparison = (priorityOrder[a.priority] || 1) - (priorityOrder[b.priority] || 1);
        break;
      case 'category':
        comparison = a.category.localeCompare(b.category);
        break;
      case 'read':
        comparison = (a.read ? 1 : 0) - (b.read ? 1 : 0);
        break;
    }
    
    return sortOrder === 'asc' ? comparison : -comparison;
  });
  
  return sorted;
};

// Notification Filtering
export const filterNotifications = (
  notifications: Notification[],
  filters: {
    read?: boolean;
    category?: string[];
    type?: string[];
    priority?: string[];
    search?: string;
    dateFrom?: string;
    dateTo?: string;
  }
): Notification[] => {
  return notifications.filter(notification => {
    // Read status filter
    if (filters.read !== undefined && notification.read !== filters.read) {
      return false;
    }
    
    // Category filter
    if (filters.category && filters.category.length > 0 && !filters.category.includes(notification.category)) {
      return false;
    }
    
    // Type filter
    if (filters.type && filters.type.length > 0 && !filters.type.includes(notification.type)) {
      return false;
    }
    
    // Priority filter
    if (filters.priority && filters.priority.length > 0 && !filters.priority.includes(notification.priority)) {
      return false;
    }
    
    // Search filter
    if (filters.search) {
      const searchTerm = filters.search.toLowerCase();
      const searchableText = `${notification.title} ${notification.message}`.toLowerCase();
      if (!searchableText.includes(searchTerm)) {
        return false;
      }
    }
    
    // Date range filter
    if (filters.dateFrom || filters.dateTo) {
      try {
        const notificationDate = parseISO(notification.timestamp);
        
        if (filters.dateFrom) {
          const fromDate = parseISO(filters.dateFrom);
          if (notificationDate < fromDate) {
            return false;
          }
        }
        
        if (filters.dateTo) {
          const toDate = parseISO(filters.dateTo);
          if (notificationDate > toDate) {
            return false;
          }
        }
      } catch (error) {
        // Invalid date, exclude from results
        return false;
      }
    }
    
    return true;
  });
};

// Action Helpers
export const createNotificationAction = (
  label: string,
  action: () => void | Promise<void>,
  style: 'primary' | 'secondary' | 'danger' = 'primary'
): NotificationAction => {
  return { label, action, style };
};

export const getDefaultActions = (notification: Notification): NotificationAction[] => {
  const actions: NotificationAction[] = [];
  
  // Mark as read action
  if (!notification.read) {
    actions.push({
      label: 'Mark as Read',
      action: async () => {
        // This would be handled by the component
        console.log('Mark as read:', notification.id);
      },
      style: 'secondary'
    });
  }
  
  // View action if actionUrl exists
  if (notification.actionUrl) {
    actions.push({
      label: notification.actionText || 'View Details',
      action: () => {
        window.location.href = notification.actionUrl!;
      },
      style: 'primary'
    });
  }
  
  // Dismiss action
  actions.push({
    label: 'Dismiss',
    action: async () => {
      console.log('Dismiss notification:', notification.id);
    },
    style: 'secondary'
  });
  
  return actions;
};

// Validation Helpers
export const validateNotification = (notification: Partial<Notification>): { valid: boolean; errors: string[] } => {
  const errors: string[] = [];
  
  if (!notification.title || notification.title.trim().length === 0) {
    errors.push('Title is required');
  }
  
  if (!notification.message || notification.message.trim().length === 0) {
    errors.push('Message is required');
  }
  
  if (notification.title && notification.title.length > 255) {
    errors.push('Title must be less than 255 characters');
  }
  
  if (notification.message && notification.message.length > 1000) {
    errors.push('Message must be less than 1000 characters');
  }
  
  const validTypes = ['success', 'error', 'warning', 'info', 'donation', 'campaign', 'milestone'];
  if (notification.type && !validTypes.includes(notification.type)) {
    errors.push('Invalid notification type');
  }
  
  const validCategories = ['system', 'donation', 'campaign', 'security', 'organization', 'payment', 'approval', 'maintenance'];
  if (notification.category && !validCategories.includes(notification.category)) {
    errors.push('Invalid notification category');
  }
  
  const validPriorities = ['low', 'medium', 'high', 'urgent'];
  if (notification.priority && !validPriorities.includes(notification.priority)) {
    errors.push('Invalid notification priority');
  }
  
  return {
    valid: errors.length === 0,
    errors
  };
};

// Statistical Helpers
export const getNotificationStats = (notifications: Notification[]) => {
  const stats = {
    total: notifications.length,
    unread: notifications.filter(n => !n.read).length,
    byCategory: {} as Record<string, number>,
    byType: {} as Record<string, number>,
    byPriority: {} as Record<string, number>,
    today: 0,
    thisWeek: 0
  };
  
  const today = new Date();
  const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
  
  notifications.forEach(notification => {
    // Count by category
    stats.byCategory[notification.category] = (stats.byCategory[notification.category] || 0) + 1;
    
    // Count by type
    stats.byType[notification.type] = (stats.byType[notification.type] || 0) + 1;
    
    // Count by priority
    stats.byPriority[notification.priority] = (stats.byPriority[notification.priority] || 0) + 1;
    
    // Count by date
    try {
      const notificationDate = parseISO(notification.timestamp);
      if (isToday(notificationDate)) {
        stats.today++;
      }
      if (notificationDate >= weekAgo) {
        stats.thisWeek++;
      }
    } catch (error) {
      // Invalid date, skip
    }
  });
  
  return stats;
};

// Export all utilities as a single object for convenience
export const notificationUtils = {
  // Formatting
  formatTimestamp: formatNotificationTimestamp,
  formatRelativeTime,
  formatDetailedTimestamp,
  truncateText,
  stripHtml,
  formatCategoryName,
  formatPriorityName,
  formatTypeName,

  // Visual helpers
  getIcon: getNotificationIcon,
  getColor: getNotificationColor,

  // Grouping and sorting
  groupByDate: groupNotificationsByDate,
  groupByCategory: groupNotificationsByCategory,
  sort: sortNotifications,

  // Filtering
  filter: filterNotifications,

  // Actions
  createAction: createNotificationAction,
  getDefaultActions,

  // Validation
  validate: validateNotification,

  // Statistics
  getStats: getNotificationStats
};

export default notificationUtils;