<template>
  <div
    class="group flex p-4 hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
    :class="{
      'bg-blue-50 border-l-4 border-l-blue-500': !notification.read,
      'opacity-75': notification.read
    }"
    @click="$emit('click', notification)"
  >
    <!-- Icon -->
    <div class="flex-shrink-0">
      <div
        class="flex items-center justify-center w-10 h-10 rounded-full"
        :class="iconContainerClass"
      >
        <component
          :is="iconComponent"
          class="w-5 h-5"
          :class="iconClass"
        />
      </div>
    </div>

    <!-- Content -->
    <div class="ml-3 flex-1 min-w-0">
      <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
          <!-- Title -->
          <p class="text-sm font-medium text-gray-900 truncate">
            {{ notification.title }}
          </p>

          <!-- Message -->
          <p class="mt-1 text-sm text-gray-600 line-clamp-2">
            {{ notification.message }}
          </p>

          <!-- Metadata -->
          <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
            <span class="flex items-center">
              <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              {{ formatRelativeTime(notification.timestamp) }}
            </span>

            <span
              v-if="notification.priority !== 'low'"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
              :class="priorityBadgeClass"
            >
              {{ notification.priority }}
            </span>

            <span
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
              :class="categoryBadgeClass"
            >
              {{ formatCategory(notification.category) }}
            </span>
          </div>

          <!-- Action Button -->
          <div v-if="notification.actionUrl && notification.actionText" class="mt-3">
            <button
              class="text-sm text-indigo-600 hover:text-indigo-500 font-medium transition-colors duration-150"
              @click.stop="$emit('click', notification)"
            >
              {{ notification.actionText }} →
            </button>
          </div>
        </div>

        <!-- Actions Menu -->
        <div class="ml-2 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
          <div class="relative" v-click-outside="closeActionsMenu">
            <button
              @click.stop="toggleActionsMenu"
              class="flex items-center justify-center w-6 h-6 text-gray-400 hover:text-gray-600 transition-colors duration-150"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
              </svg>
            </button>

            <!-- Actions Menu Dropdown -->
            <Transition
              enter-active-class="transition ease-out duration-100"
              enter-from-class="transform opacity-0 scale-95"
              enter-to-class="transform opacity-100 scale-100"
              leave-active-class="transition ease-in duration-75"
              leave-from-class="transform opacity-100 scale-100"
              leave-to-class="transform opacity-0 scale-95"
            >
              <div
                v-if="showActionsMenu"
                class="absolute right-0 z-10 mt-1 w-32 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
              >
                <div class="py-1">
                  <button
                    v-if="!notification.read"
                    @click.stop="handleMarkAsRead"
                    class="flex w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150"
                  >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7" />
                    </svg>
                    Mark read
                  </button>
                  
                  <button
                    @click.stop="handleRemove"
                    class="flex w-full px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-150"
                  >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      />
                    </svg>
                    Remove
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { formatDistanceToNow } from 'date-fns'
import type { Notification } from '@/types'

// Props
interface Props {
  notification: Notification
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  (e: 'click', notification: Notification): void
  (e: 'markRead', notificationId: string): void
  (e: 'remove', notificationId: string): void
}>()

// State
const showActionsMenu = ref(false)

// Computed properties
const iconComponent = computed(() => {
  const iconMap: Record<string, string> = {
    donation: 'BanknotesIcon',
    campaign: 'MegaphoneIcon',
    milestone: 'TrophyIcon',
    security: 'ShieldExclamationIcon',
    system: 'CogIcon',
    payment: 'CreditCardIcon',
    approval: 'ClockIcon',
    organization: 'BuildingOfficeIcon',
    maintenance: 'WrenchScrewdriverIcon',
    success: 'CheckCircleIcon',
    error: 'XCircleIcon',
    warning: 'ExclamationTriangleIcon',
    info: 'InformationCircleIcon'
  }

  return iconMap[props.notification.category] || iconMap[props.notification.type] || 'BellIcon'
})

const iconContainerClass = computed(() => {
  const classMap: Record<string, string> = {
    donation: 'bg-green-100',
    campaign: 'bg-blue-100',
    milestone: 'bg-yellow-100',
    security: 'bg-red-100',
    system: 'bg-gray-100',
    payment: 'bg-purple-100',
    approval: 'bg-orange-100',
    organization: 'bg-indigo-100',
    maintenance: 'bg-gray-100',
    success: 'bg-green-100',
    error: 'bg-red-100',
    warning: 'bg-yellow-100',
    info: 'bg-blue-100'
  }

  return classMap[props.notification.category] || classMap[props.notification.type] || 'bg-gray-100'
})

const iconClass = computed(() => {
  const classMap: Record<string, string> = {
    donation: 'text-green-600',
    campaign: 'text-blue-600',
    milestone: 'text-yellow-600',
    security: 'text-red-600',
    system: 'text-gray-600',
    payment: 'text-purple-600',
    approval: 'text-orange-600',
    organization: 'text-indigo-600',
    maintenance: 'text-gray-600',
    success: 'text-green-600',
    error: 'text-red-600',
    warning: 'text-yellow-600',
    info: 'text-blue-600'
  }

  return classMap[props.notification.category] || classMap[props.notification.type] || 'text-gray-600'
})

const priorityBadgeClass = computed(() => {
  const classMap: Record<string, string> = {
    low: 'bg-gray-100 text-gray-800',
    medium: 'bg-blue-100 text-blue-800',
    high: 'bg-orange-100 text-orange-800',
    urgent: 'bg-red-100 text-red-800'
  }

  return classMap[props.notification.priority] || 'bg-gray-100 text-gray-800'
})

const categoryBadgeClass = computed(() => {
  const classMap: Record<string, string> = {
    donation: 'bg-green-100 text-green-800',
    campaign: 'bg-blue-100 text-blue-800',
    security: 'bg-red-100 text-red-800',
    system: 'bg-gray-100 text-gray-800',
    payment: 'bg-purple-100 text-purple-800',
    approval: 'bg-orange-100 text-orange-800',
    organization: 'bg-indigo-100 text-indigo-800',
    maintenance: 'bg-gray-100 text-gray-800'
  }

  return classMap[props.notification.category] || 'bg-gray-100 text-gray-800'
})

// Methods
const formatRelativeTime = (timestamp: string): string => {
  try {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
  } catch {
    return 'Just now'
  }
}

const formatCategory = (category: string): string => {
  const categoryMap: Record<string, string> = {
    donation: 'Donation',
    campaign: 'Campaign',
    milestone: 'Milestone',
    security: 'Security',
    system: 'System',
    payment: 'Payment',
    approval: 'Approval',
    organization: 'Organization',
    maintenance: 'Maintenance'
  }

  return categoryMap[category] || category.charAt(0).toUpperCase() + category.slice(1)
}

const toggleActionsMenu = () => {
  showActionsMenu.value = !showActionsMenu.value
}

const closeActionsMenu = () => {
  showActionsMenu.value = false
}

const handleMarkAsRead = () => {
  emit('markRead', props.notification.id)
  closeActionsMenu()
}

const handleRemove = () => {
  emit('remove', props.notification.id)
  closeActionsMenu()
}

// Click outside directive
const vClickOutside = {
  beforeMount: (el: HTMLElement, binding: any) => {
    el.addEventListener('click', (e) => e.stopPropagation())
    document.addEventListener('click', binding.value)
  },
  beforeUnmount: (el: HTMLElement, binding: any) => {
    document.removeEventListener('click', binding.value)
  }
}
</script>

<style scoped>
.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
</style>