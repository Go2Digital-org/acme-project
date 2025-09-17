<template>
  <div class="notification-center relative">
    <!-- Notification Bell Button -->
    <button
      @click="toggleDropdown"
      class="relative p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-full transition-colors"
      :class="{ 'text-blue-600': isOpen }"
    >
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 17v5l-5-5h5z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
      </svg>
      
      <!-- Unread Count Badge -->
      <span 
        v-if="notificationStore.unreadCount > 0"
        class="absolute -top-1 -right-1 h-5 w-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-pulse"
      >
        {{ notificationStore.unreadCount > 99 ? '99+' : notificationStore.unreadCount }}
      </span>
    </button>

    <!-- Dropdown Panel -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div 
        v-if="isOpen"
        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50"
        @click.stop
      >
        <!-- Header -->
        <div class="p-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
            <div class="flex items-center gap-2">
              <!-- Mark All Read Button -->
              <button
                v-if="notificationStore.unreadCount > 0"
                @click="markAllAsRead"
                class="text-sm text-blue-600 hover:text-blue-700 focus:outline-none focus:underline"
              >
                Mark all read
              </button>
              <!-- Close Button -->
              <button
                @click="closeDropdown"
                class="text-gray-400 hover:text-gray-600 focus:outline-none"
              >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="notificationStore.loading" class="p-8 text-center">
          <svg class="animate-spin h-6 w-6 text-blue-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-sm text-gray-500">Loading notifications...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="displayedNotifications.length === 0" class="p-8 text-center">
          <svg class="h-12 w-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17v5l-5-5h5z" />
          </svg>
          <p class="text-gray-500 text-sm">
            {{ showUnreadOnly ? 'No unread notifications' : 'No notifications yet' }}
          </p>
          <button
            v-if="showUnreadOnly"
            @click="showUnreadOnly = false"
            class="mt-2 text-blue-600 text-sm hover:text-blue-700 focus:outline-none focus:underline"
          >
            Show all notifications
          </button>
        </div>

        <!-- Notifications List -->
        <div v-else class="max-h-96 overflow-y-auto">
          <div class="divide-y divide-gray-100">
            <div
              v-for="notification in displayedNotifications"
              :key="notification.id"
              :class="[
                'p-4 hover:bg-gray-50 cursor-pointer transition-colors',
                notification.read ? 'opacity-75' : 'bg-blue-50/30'
              ]"
              @click="handleNotificationClick(notification)"
            >
              <!-- Notification Content -->
              <div class="flex items-start space-x-3">
                <!-- Icon -->
                <div 
                  :class="[
                    'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
                    iconClasses[notification.type]
                  ]"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <!-- Success -->
                    <path v-if="notification.type === 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    <!-- Error -->
                    <path v-else-if="notification.type === 'error'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    <!-- Warning -->
                    <path v-else-if="notification.type === 'warning'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.072 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    <!-- Info -->
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900 truncate">
                      {{ notification.title }}
                    </p>
                    <div class="flex items-center gap-1">
                      <!-- Unread Indicator -->
                      <div 
                        v-if="!notification.read"
                        class="w-2 h-2 bg-blue-600 rounded-full flex-shrink-0"
                      ></div>
                      <!-- Time -->
                      <p class="text-xs text-gray-500 flex-shrink-0">
                        {{ formatTime(notification.timestamp) }}
                      </p>
                    </div>
                  </div>
                  
                  <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                    {{ notification.message }}
                  </p>
                  
                  <!-- Action Button -->
                  <button
                    v-if="notification.actionUrl && notification.actionText"
                    @click.stop="handleAction(notification)"
                    class="mt-2 text-xs text-blue-600 hover:text-blue-700 focus:outline-none focus:underline"
                  >
                    {{ notification.actionText }}
                  </button>
                </div>

                <!-- Dismiss Button -->
                <button
                  @click.stop="dismissNotification(notification.id)"
                  class="flex-shrink-0 text-gray-300 hover:text-gray-500 focus:outline-none focus:text-gray-500"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="displayedNotifications.length > 0" class="p-3 border-t border-gray-200 bg-gray-50">
          <div class="flex items-center justify-between text-sm">
            <!-- Show Unread Toggle -->
            <label class="flex items-center">
              <input
                v-model="showUnreadOnly"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-gray-600">Show unread only</span>
            </label>
            
            <!-- Clear All Button -->
            <button
              @click="clearAllNotifications"
              class="text-red-600 hover:text-red-700 focus:outline-none focus:underline"
            >
              Clear all
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Toast Notifications -->
    <Teleport to="body">
      <div class="fixed top-4 right-4 z-50 space-y-2">
        <TransitionGroup name="toast">
          <div
            v-for="toast in toastNotifications"
            :key="toast.id"
            :class="[
              'max-w-sm bg-white rounded-lg shadow-lg border-l-4 p-4',
              toastBorderClasses[toast.type]
            ]"
          >
            <div class="flex items-start">
              <div 
                :class="[
                  'flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center mr-3',
                  iconClasses[toast.type]
                ]"
              >
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <!-- Icons same as above -->
                  <path v-if="toast.type === 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  <path v-else-if="toast.type === 'error'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  <path v-else-if="toast.type === 'warning'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.072 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">
                  {{ toast.title }}
                </p>
                <p class="text-sm text-gray-600 mt-1">
                  {{ toast.message }}
                </p>
              </div>
              
              <button
                @click="dismissToast(toast.id)"
                class="ml-3 text-gray-400 hover:text-gray-600 focus:outline-none"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </TransitionGroup>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useNotificationStore } from '@/stores/notification';
import type { NotificationCenterProps, Notification } from '@/types';

// Props
const props = withDefaults(defineProps<NotificationCenterProps>(), {
  maxNotifications: 50,
  showUnreadOnly: false
});

// Stores
const notificationStore = useNotificationStore();

// Reactive data
const isOpen = ref<boolean>(false);
const showUnreadOnly = ref<boolean>(props.showUnreadOnly);

// Computed
const displayedNotifications = computed(() => {
  const notifications = showUnreadOnly.value 
    ? notificationStore.unreadNotifications
    : notificationStore.recentNotifications;
    
  return notifications.slice(0, props.maxNotifications);
});

const toastNotifications = computed(() => 
  notificationStore.notifications.filter(n => n.id.startsWith('toast-'))
);

const iconClasses = computed(() => ({
  'success': 'bg-green-100 text-green-600',
  'error': 'bg-red-100 text-red-600',
  'warning': 'bg-yellow-100 text-yellow-600',
  'info': 'bg-blue-100 text-blue-600'
}));

const toastBorderClasses = computed(() => ({
  'success': 'border-green-400',
  'error': 'border-red-400',
  'warning': 'border-yellow-400',
  'info': 'border-blue-400'
}));

// Methods
const toggleDropdown = (): void => {
  isOpen.value = !isOpen.value;
  if (isOpen.value && notificationStore.notifications.length === 0) {
    loadNotifications();
  }
};

const closeDropdown = (): void => {
  isOpen.value = false;
};

const loadNotifications = async (): Promise<void> => {
  try {
    await notificationStore.fetchNotifications();
  } catch (error) {
    console.error('Failed to load notifications:', error);
  }
};

const markAllAsRead = async (): Promise<void> => {
  try {
    await notificationStore.markAllAsRead();
  } catch (error) {
    console.error('Failed to mark all as read:', error);
  }
};

const handleNotificationClick = async (notification: Notification): Promise<void> => {
  if (!notification.read) {
    try {
      await notificationStore.markAsRead(notification.id);
    } catch (error) {
      console.error('Failed to mark as read:', error);
    }
  }

  if (notification.actionUrl) {
    window.location.href = notification.actionUrl;
  }
};

const handleAction = (notification: Notification): void => {
  if (notification.actionUrl) {
    window.location.href = notification.actionUrl;
  }
};

const dismissNotification = async (notificationId: string): Promise<void> => {
  try {
    await notificationStore.removeNotification(notificationId);
  } catch (error) {
    console.error('Failed to dismiss notification:', error);
  }
};

const dismissToast = (toastId: string): void => {
  const index = notificationStore.notifications.findIndex(n => n.id === toastId);
  if (index !== -1) {
    notificationStore.notifications.splice(index, 1);
  }
};

const clearAllNotifications = (): void => {
  notificationStore.clearAll();
  closeDropdown();
};

const formatTime = (timestamp: string): string => {
  const now = new Date();
  const time = new Date(timestamp);
  const diffInMinutes = Math.floor((now.getTime() - time.getTime()) / (1000 * 60));

  if (diffInMinutes < 1) return 'Just now';
  if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
  if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
  if (diffInMinutes < 43200) return `${Math.floor(diffInMinutes / 1440)}d ago`;
  
  return time.toLocaleDateString();
};

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent): void => {
  const target = event.target as Element;
  if (!target.closest('.notification-center')) {
    closeDropdown();
  }
};

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  notificationStore.initializeRealTime();
  loadNotifications();
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
/* Line clamp utility */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Toast transitions */
.toast-enter-active {
  transition: all 0.3s ease;
}

.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100px);
}

/* Pulse animation */
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}

/* Scrollbar styling */
.max-h-96::-webkit-scrollbar {
  width: 4px;
}

.max-h-96::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.max-h-96::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.max-h-96::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>