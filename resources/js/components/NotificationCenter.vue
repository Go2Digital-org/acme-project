<template>
  <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="sm:flex sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
          <p class="mt-2 text-sm text-gray-700">
            Stay updated with your campaigns, donations, and important alerts.
          </p>
        </div>
        
        <div class="mt-4 sm:mt-0 sm:flex-shrink-0 flex items-center space-x-3">
          <!-- Connection Status -->
          <div
            class="flex items-center text-xs"
            :class="isConnected ? 'text-green-600' : 'text-red-600'"
          >
            <div
              class="w-2 h-2 rounded-full mr-2"
              :class="isConnected ? 'bg-green-400' : 'bg-red-400'"
            ></div>
            {{ isConnected ? 'Connected' : 'Disconnected' }}
          </div>

          <!-- Actions -->
          <button
            v-if="unreadCount > 0"
            @click="markAllAsRead"
            :disabled="markingAllAsRead"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-colors duration-200"
          >
            <svg v-if="markingAllAsRead" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ markingAllAsRead ? 'Marking...' : 'Mark all read' }}
          </button>

          <button
            @click="openPreferences"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
          >
            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"
              />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Settings
          </button>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-6">
      <div class="bg-white shadow rounded-lg p-4">
        <div class="flex flex-wrap items-center gap-4">
          <!-- Category Filter -->
          <div class="min-w-0 flex-1 md:flex-none md:w-48">
            <label for="category-filter" class="sr-only">Filter by category</label>
            <select
              id="category-filter"
              v-model="selectedCategories"
              multiple
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="">All Categories</option>
              <option value="donation">Donations</option>
              <option value="campaign">Campaigns</option>
              <option value="milestone">Milestones</option>
              <option value="security">Security</option>
              <option value="system">System</option>
              <option value="payment">Payments</option>
              <option value="approval">Approvals</option>
              <option value="organization">Organizations</option>
            </select>
          </div>

          <!-- Type Filter -->
          <div class="min-w-0 flex-1 md:flex-none md:w-48">
            <label for="type-filter" class="sr-only">Filter by type</label>
            <select
              id="type-filter"
              v-model="selectedTypes"
              multiple
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="">All Types</option>
              <option value="success">Success</option>
              <option value="error">Error</option>
              <option value="warning">Warning</option>
              <option value="info">Info</option>
            </select>
          </div>

          <!-- Read Status Filter -->
          <div class="min-w-0 flex-1 md:flex-none md:w-32">
            <label for="read-filter" class="sr-only">Filter by read status</label>
            <select
              id="read-filter"
              v-model="selectedReadStatus"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="">All</option>
              <option value="unread">Unread</option>
              <option value="read">Read</option>
            </select>
          </div>

          <!-- Clear Filters -->
          <button
            v-if="hasActiveFilters"
            @click="clearFilters"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
          >
            Clear filters
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-5 5v-5zM4.006 10l5.5-6.624a1 1 0 011.488 0L16.494 10H4.006z" />
                </svg>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total</dt>
                <dd class="text-lg font-medium text-gray-900">{{ totalNotifications }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Unread</dt>
                <dd class="text-lg font-medium text-gray-900">{{ unreadCount }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Today</dt>
                <dd class="text-lg font-medium text-gray-900">{{ todayCount }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">High Priority</dt>
                <dd class="text-lg font-medium text-gray-900">{{ highPriorityCount }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notifications List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-gray-600">Loading notifications...</span>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredNotifications.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a8.967 8.967 0 008.354-5.646z"
          />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ hasActiveFilters ? 'No notifications match your current filters.' : 'You\'re all caught up!' }}
        </p>
        <div v-if="hasActiveFilters" class="mt-6">
          <button
            @click="clearFilters"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
          >
            Clear filters
          </button>
        </div>
      </div>

      <!-- Notifications -->
      <div v-else>
        <ul class="divide-y divide-gray-200">
          <li v-for="notification in paginatedNotifications" :key="notification.id">
            <NotificationItem
              :notification="notification"
              @click="handleNotificationClick"
              @mark-read="handleMarkAsRead"
              @remove="handleRemoveNotification"
            />
          </li>
        </ul>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              @click="previousPage"
              :disabled="currentPage === 1"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>

          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing
                <span class="font-medium">{{ (currentPage - 1) * itemsPerPage + 1 }}</span>
                to
                <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filteredNotifications.length) }}</span>
                of
                <span class="font-medium">{{ filteredNotifications.length }}</span>
                results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <button
                  @click="previousPage"
                  :disabled="currentPage === 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span class="sr-only">Previous</span>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>

                <button
                  v-for="page in visiblePages"
                  :key="page"
                  @click="goToPage(page)"
                  :class="[
                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                    page === currentPage
                      ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </button>

                <button
                  @click="nextPage"
                  :disabled="currentPage === totalPages"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span class="sr-only">Next</span>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notification Preferences Modal -->
    <NotificationPreferences
      v-if="showPreferences"
      @close="showPreferences = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notification'
import { useNotificationWebSocket } from '@/composables/useNotificationWebSocket'
import { isToday, isYesterday, parseISO } from 'date-fns'
import NotificationItem from './NotificationItem.vue'
import NotificationPreferences from './NotificationPreferences.vue'
import type { Notification } from '@/types'

const router = useRouter()
const notificationStore = useNotificationStore()
const { connect, disconnect, isConnected } = useNotificationWebSocket()

// State
const showPreferences = ref(false)
const markingAllAsRead = ref(false)
const selectedCategories = ref<string[]>([])
const selectedTypes = ref<string[]>([])
const selectedReadStatus = ref<string>('')
const currentPage = ref(1)
const itemsPerPage = 20

// Computed properties
const { notifications, loading, unreadCount } = notificationStore

const totalNotifications = computed(() => notifications.length)

const todayCount = computed(() => {
  return notifications.filter(notification => {
    try {
      return isToday(parseISO(notification.timestamp))
    } catch {
      return false
    }
  }).length
})

const highPriorityCount = computed(() => {
  return notifications.filter(notification => 
    notification.priority === 'high' || notification.priority === 'urgent'
  ).length
})

const filteredNotifications = computed(() => {
  let filtered = [...notifications]

  // Filter by categories
  if (selectedCategories.value.length > 0) {
    filtered = filtered.filter(notification =>
      selectedCategories.value.includes(notification.category)
    )
  }

  // Filter by types
  if (selectedTypes.value.length > 0) {
    filtered = filtered.filter(notification =>
      selectedTypes.value.includes(notification.type)
    )
  }

  // Filter by read status
  if (selectedReadStatus.value) {
    const isUnread = selectedReadStatus.value === 'unread'
    filtered = filtered.filter(notification => notification.read !== isUnread)
  }

  return filtered.sort((a, b) => 
    new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
  )
})

const totalPages = computed(() => 
  Math.ceil(filteredNotifications.value.length / itemsPerPage)
)

const paginatedNotifications = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredNotifications.value.slice(start, end)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const pages: number[] = []

  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) {
        pages.push(i)
      }
      pages.push(-1, total) // -1 represents ellipsis
    } else if (current >= total - 3) {
      pages.push(1, -1)
      for (let i = total - 4; i <= total; i++) {
        pages.push(i)
      }
    } else {
      pages.push(1, -1)
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i)
      }
      pages.push(-1, total)
    }
  }

  return pages.filter(page => page !== -1) // Remove ellipsis for simplicity
})

const hasActiveFilters = computed(() => 
  selectedCategories.value.length > 0 || 
  selectedTypes.value.length > 0 || 
  selectedReadStatus.value !== ''
)

// Methods
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
}

const handleNotificationClick = (notification: Notification) => {
  // Mark as read if unread
  if (!notification.read) {
    notificationStore.markAsRead(notification.id)
  }
  
  // Navigate to action URL if provided
  if (notification.actionUrl) {
    router.push(notification.actionUrl)
  }
}

const handleMarkAsRead = (notificationId: string) => {
  notificationStore.markAsRead(notificationId)
}

const handleRemoveNotification = (notificationId: string) => {
  notificationStore.removeNotification(notificationId)
}

const clearFilters = () => {
  selectedCategories.value = []
  selectedTypes.value = []
  selectedReadStatus.value = ''
  currentPage.value = 1
}

const goToPage = (page: number) => {
  currentPage.value = page
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

// Watch for filter changes and reset pagination
watch([selectedCategories, selectedTypes, selectedReadStatus], () => {
  currentPage.value = 1
}, { deep: true })

// Lifecycle
onMounted(async () => {
  // Fetch all notifications
  await notificationStore.fetchNotifications()
  
  // Connect to WebSocket for real-time updates
  connect()
})

onBeforeUnmount(() => {
  disconnect()
})
</script>