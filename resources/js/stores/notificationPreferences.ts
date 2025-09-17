import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '@/api/client';
import type { NotificationPreferences, NotificationPreferencesState } from '@/types';

export const useNotificationPreferencesStore = defineStore('notificationPreferences', () => {
  // State
  const preferences = ref<NotificationPreferences | null>(null);
  const loading = ref(false);
  const saving = ref(false);

  // Getters
  const hasPreferences = computed(() => preferences.value !== null);
  
  const isEmailEnabled = computed(() => preferences.value?.emailNotifications || false);
  const isBrowserEnabled = computed(() => preferences.value?.browserNotifications || false);
  const isSmsEnabled = computed(() => preferences.value?.smsNotifications || false);
  
  const quietHoursEnabled = computed(() => preferences.value?.quietHours?.enabled || false);
  const currentFrequency = computed(() => preferences.value?.frequency || 'immediate');
  
  const categoryPreferences = computed(() => preferences.value?.categories || {});

  // Helper to check if notifications are enabled for a specific category and channel
  const isNotificationEnabled = computed(() => (category: string, channel: 'email' | 'browser' | 'sms'): boolean => {
    if (!preferences.value) return false;
    
    // Check global setting first
    const globalEnabled = preferences.value[`${channel}Notifications` as keyof NotificationPreferences];
    if (!globalEnabled) return false;
    
    // Check category-specific setting
    const categorySettings = preferences.value.categories[category as keyof typeof preferences.value.categories];
    return categorySettings?.[channel] || false;
  });

  // Check if currently in quiet hours
  const isInQuietHours = computed(() => {
    if (!preferences.value?.quietHours?.enabled) return false;
    
    const now = new Date();
    const currentTime = now.getHours() * 60 + now.getMinutes(); // minutes since midnight
    
    const startTime = parseTimeString(preferences.value.quietHours.startTime);
    const endTime = parseTimeString(preferences.value.quietHours.endTime);
    
    if (startTime === null || endTime === null) return false;
    
    // Handle overnight quiet hours (e.g., 22:00 to 06:00)
    if (startTime > endTime) {
      return currentTime >= startTime || currentTime <= endTime;
    }
    
    return currentTime >= startTime && currentTime <= endTime;
  });

  // Actions
  const fetchPreferences = async (): Promise<NotificationPreferences | null> => {
    loading.value = true;

    try {
      const response = await api.get<NotificationPreferences>('/notification-preferences');
      preferences.value = response;
      return response;
    } catch (err: any) {
      console.error('Failed to fetch notification preferences:', err);
      
      // If preferences don't exist, create default ones
      if (err.response?.status === 404) {
        await createDefaultPreferences();
        return preferences.value;
      }
      
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const savePreferences = async (): Promise<NotificationPreferences> => {
    if (!preferences.value) {
      throw new Error('No preferences to save');
    }

    saving.value = true;

    try {
      const response = await api.put<NotificationPreferences>('/notification-preferences', preferences.value);
      preferences.value = response;
      return response;
    } catch (err: any) {
      console.error('Failed to save notification preferences:', err);
      throw err;
    } finally {
      saving.value = false;
    }
  };

  const createDefaultPreferences = async (): Promise<NotificationPreferences> => {
    const defaultPreferences: Omit<NotificationPreferences, 'id' | 'userId'> = {
      emailNotifications: true,
      browserNotifications: true,
      smsNotifications: false,
      categories: {
        donation: {
          email: true,
          browser: true,
          sms: false
        },
        campaign: {
          email: true,
          browser: true,
          sms: false
        },
        milestone: {
          email: true,
          browser: true,
          sms: false
        },
        system: {
          email: true,
          browser: false,
          sms: false
        },
        security: {
          email: true,
          browser: true,
          sms: true
        }
      },
      quietHours: {
        enabled: false,
        startTime: '22:00',
        endTime: '08:00'
      },
      frequency: 'immediate'
    };

    try {
      const response = await api.post<NotificationPreferences>('/notification-preferences', defaultPreferences);
      preferences.value = response;
      return response;
    } catch (err: any) {
      console.error('Failed to create default preferences:', err);
      throw err;
    }
  };

  const updatePreference = <K extends keyof NotificationPreferences>(
    key: K, 
    value: NotificationPreferences[K]
  ): void => {
    if (preferences.value) {
      preferences.value[key] = value;
    }
  };

  const updateCategoryPreference = (
    category: keyof NotificationPreferences['categories'],
    channel: 'email' | 'browser' | 'sms',
    value: boolean
  ): void => {
    if (preferences.value && preferences.value.categories[category]) {
      preferences.value.categories[category][channel] = value;
    }
  };

  const toggleGlobalNotifications = async (enabled: boolean): Promise<void> => {
    if (!preferences.value) return;
    
    preferences.value.emailNotifications = enabled;
    preferences.value.browserNotifications = enabled;
    preferences.value.smsNotifications = enabled;
    
    // Also update all category preferences
    Object.keys(preferences.value.categories).forEach(category => {
      const categoryKey = category as keyof NotificationPreferences['categories'];
      if (preferences.value?.categories[categoryKey]) {
        preferences.value.categories[categoryKey].email = enabled;
        preferences.value.categories[categoryKey].browser = enabled;
        preferences.value.categories[categoryKey].sms = enabled;
      }
    });

    await savePreferences();
  };

  const toggleChannelForAllCategories = async (
    channel: 'email' | 'browser' | 'sms', 
    enabled: boolean
  ): Promise<void> => {
    if (!preferences.value) return;
    
    Object.keys(preferences.value.categories).forEach(category => {
      const categoryKey = category as keyof NotificationPreferences['categories'];
      if (preferences.value?.categories[categoryKey]) {
        preferences.value.categories[categoryKey][channel] = enabled;
      }
    });

    await savePreferences();
  };

  const resetToDefaults = async (): Promise<void> => {
    await createDefaultPreferences();
  };

  const validateQuietHours = (startTime: string, endTime: string): boolean => {
    const start = parseTimeString(startTime);
    const end = parseTimeString(endTime);
    
    if (start === null || end === null) return false;
    
    // Allow overnight quiet hours
    return true;
  };

  const shouldSuppressNotification = (
    category: string,
    priority: 'low' | 'medium' | 'high' | 'urgent'
  ): boolean => {
    // Never suppress urgent notifications
    if (priority === 'urgent') return false;
    
    // Check if in quiet hours
    if (isInQuietHours.value) {
      // Only suppress low and medium priority notifications during quiet hours
      return priority === 'low' || priority === 'medium';
    }
    
    return false;
  };

  const getDeliveryDelay = (): number => {
    if (!preferences.value) return 0;
    
    const delays: Record<NotificationPreferences['frequency'], number> = {
      immediate: 0,
      hourly: 60 * 60 * 1000, // 1 hour in milliseconds
      daily: 24 * 60 * 60 * 1000, // 24 hours
      weekly: 7 * 24 * 60 * 60 * 1000 // 7 days
    };
    
    return delays[preferences.value.frequency] || 0;
  };

  // Utility functions
  const parseTimeString = (timeString: string): number | null => {
    const match = timeString.match(/^(\d{1,2}):(\d{2})$/);
    if (!match) return null;
    
    const hours = parseInt(match[1], 10);
    const minutes = parseInt(match[2], 10);
    
    if (hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
      return null;
    }
    
    return hours * 60 + minutes; // minutes since midnight
  };

  const formatTimeString = (minutes: number): string => {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
  };

  // Initialize preferences on store creation
  const initializePreferences = async (): Promise<void> => {
    try {
      await fetchPreferences();
    } catch (error) {
      console.error('Failed to initialize notification preferences:', error);
    }
  };

  return {
    // State
    preferences,
    loading,
    saving,

    // Getters
    hasPreferences,
    isEmailEnabled,
    isBrowserEnabled,
    isSmsEnabled,
    quietHoursEnabled,
    currentFrequency,
    categoryPreferences,
    isNotificationEnabled,
    isInQuietHours,

    // Actions
    fetchPreferences,
    savePreferences,
    createDefaultPreferences,
    updatePreference,
    updateCategoryPreference,
    toggleGlobalNotifications,
    toggleChannelForAllCategories,
    resetToDefaults,
    validateQuietHours,
    shouldSuppressNotification,
    getDeliveryDelay,
    initializePreferences,

    // Utilities
    parseTimeString,
    formatTimeString
  };
});