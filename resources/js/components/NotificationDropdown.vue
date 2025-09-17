<template>
  <div class="relative" v-click-outside="closeDropdown">
    <!-- Notification Bell Icon -->
    <button
      @click="toggleDropdown"
      type="button"
      class="relative flex-shrink-0 p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
      :class="{ 'text-indigo-600': hasUnreadNotifications }"
      :aria-label="`Notifications ${unreadCount > 0 ? `(${unreadCount} unread)` : ''}`"
    >
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
        />
      </svg>
      
      <!-- Unread Count Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full min-w-5 h-5"
        :class="{ 'animate-pulse': hasNewNotification }"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
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
        class="absolute right-0 z-50 mt-2 w-96 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
      >
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
          <div class="flex items-center space-x-2">
            <!-- Mark All Read Button -->
            <button
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              :disabled="markingAllAsRead"
              class="text-sm text-indigo-600 hover:text-indigo-500 disabled:opacity-50 transition-colors duration-200"
            >
              <span v-if="markingAllAsRead">Marking...</span>
              <span v-else>Mark all read</span>
            </button>
            
            <!-- Settings Button -->
            <button
              @click="openPreferences"
              class="p-1 text-gray-400 hover:text-gray-500 transition-colors duration-200"
              aria-label="Notification preferences"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"
                />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          <div v-if="loading" class="flex items-center justify-center p-8">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-500">Loading notifications...</span>
          </div>

          <div v-else-if="recentNotifications.length === 0" class="p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a8.967 8.967 0 008.354-5.646z"
              />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
            <p class="mt-1 text-sm text-gray-500">You're all caught up!</p>
          </div>

          <div v-else class="divide-y divide-gray-100">
            <NotificationItem
              v-for="notification in recentNotifications"
              :key="notification.id"
              :notification="notification"
              @click="handleNotificationClick"
              @mark-read="handleMarkAsRead"
              @remove="handleRemoveNotification"
            />
          </div>
        </div>

        <!-- Footer -->
        <div v-if="recentNotifications.length > 0" class="border-t border-gray-200 p-4">
          <router-link
            to="/notifications"
            class="block w-full text-center text-sm text-indigo-600 hover:text-indigo-500 transition-colors duration-200"
            @click="closeDropdown"
          >
            View all notifications
          </router-link>
        </div>
      </div>
    </Transition>

    <!-- Notification Preferences Modal -->
    <NotificationPreferences
      v-if="showPreferences"
      @close="showPreferences = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notification'
import { useNotificationWebSocket } from '@/composables/useNotificationWebSocket'
import NotificationItem from './NotificationItem.vue'
import NotificationPreferences from './NotificationPreferences.vue'

const router = useRouter()
const notificationStore = useNotificationStore()
const { connect, disconnect, isConnected } = useNotificationWebSocket()

// Reactive state
const isOpen = ref(false)
const showPreferences = ref(false)
const markingAllAsRead = ref(false)
const hasNewNotification = ref(false)

// Computed properties
const { notifications, loading, unreadCount, recentNotifications } = notificationStore
const hasUnreadNotifications = computed(() => unreadCount > 0)

// Methods
const toggleDropdown = async () => {
  isOpen.value = !isOpen.value
  
  if (isOpen.value && !notifications.length) {
    await notificationStore.fetchNotifications()
  }
}

const closeDropdown = () => {
  isOpen.value = false
}

const markAllAsRead = async () => {
  if (markingAllAsRead.value) return
  
  markingAllAsRead.value = true
  try {
    await notificationStore.markAllAsRead()
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error)
  } finally {
    markingAllAsRead.value = false
  }
}

const openPreferences = () => {
  showPreferences.value = true
  closeDropdown()
}

const handleNotificationClick = (notification: any) => {
  // Mark as read if unread
  if (!notification.read) {
    notificationStore.markAsRead(notification.id)
  }
  
  // Navigate to action URL if provided
  if (notification.actionUrl) {
    router.push(notification.actionUrl)
  }
  
  closeDropdown()
}

const handleMarkAsRead = (notificationId: string) => {
  notificationStore.markAsRead(notificationId)
}

const handleRemoveNotification = (notificationId: string) => {
  notificationStore.removeNotification(notificationId)
}

// Close dropdown when clicking outside
const vClickOutside = {
  beforeMount: (el: HTMLElement, binding: any) => {
    el.addEventListener('click', (e) => e.stopPropagation())
    document.addEventListener('click', binding.value)
  },
  beforeUnmount: (el: HTMLElement, binding: any) => {
    document.removeEventListener('click', binding.value)
  }
}

// Animation for new notifications
const showNewNotificationAnimation = () => {
  hasNewNotification.value = true
  setTimeout(() => {
    hasNewNotification.value = false
  }, 2000)
}

// Lifecycle
onMounted(async () => {
  // Initialize notifications
  await notificationStore.fetchNotifications()
  
  // Connect to WebSocket for real-time updates
  connect()
  
  // Listen for new notifications
  notificationStore.$onAction(({ name }) => {
    if (name === 'addNotification') {
      showNewNotificationAnimation()
    }
  })
})

onBeforeUnmount(() => {
  disconnect()
})

// Keyboard shortcuts
const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && isOpen.value) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeyDown)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeyDown)
})
</script>

<style scoped>
.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.notification-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>