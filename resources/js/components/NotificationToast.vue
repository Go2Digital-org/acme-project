<template>
  <Teleport to="body">
    <!-- Toast Container -->
    <div 
      class="fixed inset-0 flex items-end px-4 py-6 pointer-events-none sm:items-start sm:p-6 z-50"
      aria-live="assertive"
    >
      <div class="flex flex-col items-center space-y-4 w-full sm:items-end">
        <!-- Individual Toast Notifications -->
        <TransitionGroup
          name="toast"
          tag="div"
          class="flex flex-col space-y-2"
        >
          <div
            v-for="toast in visibleToasts"
            :key="toast.id"
            class="max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden"
          >
            <div class="p-4">
              <div class="flex items-start">
                <!-- Icon -->
                <div class="flex-shrink-0">
                  <component
                    :is="getIcon(toast.type)"
                    :class="getIconClass(toast.type)"
                    class="h-6 w-6"
                  />
                </div>
                
                <!-- Content -->
                <div class="ml-3 w-0 flex-1 pt-0.5">
                  <p class="text-sm font-medium text-gray-900">
                    {{ toast.title }}
                  </p>
                  <p class="mt-1 text-sm text-gray-500">
                    {{ toast.message }}
                  </p>
                  
                  <!-- Actions -->
                  <div v-if="toast.actions && toast.actions.length > 0" class="mt-3 flex space-x-2">
                    <button
                      v-for="action in toast.actions"
                      :key="action.label"
                      @click="handleActionClick(toast, action)"
                      :class="getActionButtonClass(action.style)"
                      class="text-sm font-medium rounded-md px-3 py-2 transition-colors duration-200"
                    >
                      {{ action.label }}
                    </button>
                  </div>
                </div>
                
                <!-- Close Button -->
                <div class="ml-4 flex-shrink-0 flex">
                  <button
                    @click="removeToast(toast.id)"
                    class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                  >
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
              </div>
              
              <!-- Progress Bar for Auto-dismiss -->
              <div 
                v-if="!toast.persistent && toast.duration"
                class="mt-3 w-full bg-gray-200 rounded-full h-1"
              >
                <div
                  :class="getProgressBarClass(toast.type)"
                  class="h-1 rounded-full transition-all ease-linear"
                  :style="{ 
                    width: getProgressWidth(toast.id) + '%',
                    animationDuration: toast.duration + 'ms'
                  }"
                ></div>
              </div>
            </div>
          </div>
        </TransitionGroup>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useNotificationStore } from '@/stores/notification'
import type { ToastNotification, NotificationAction } from '@/types'

// Store
const notificationStore = useNotificationStore()

// State
const toasts = ref<Map<string, ToastNotification>>(new Map())
const timers = ref<Map<string, NodeJS.Timeout>>(new Map())
const progressTimers = ref<Map<string, NodeJS.Timeout>>(new Map())
const progressValues = ref<Map<string, number>>(new Map())

// Computed
const visibleToasts = computed(() => Array.from(toasts.value.values()))

// Methods
const addToast = (toast: ToastNotification) => {
  // Set default duration if not specified
  if (!toast.duration && !toast.persistent) {
    toast.duration = getDefaultDuration(toast.type)
  }

  toasts.value.set(toast.id, toast)
  
  // Set up auto-dismiss if not persistent
  if (!toast.persistent && toast.duration) {
    // Progress bar animation
    progressValues.value.set(toast.id, 100)
    const progressTimer = setInterval(() => {
      const current = progressValues.value.get(toast.id) || 0
      if (current > 0) {
        progressValues.value.set(toast.id, current - 1)
      }
    }, toast.duration / 100)
    progressTimers.value.set(toast.id, progressTimer)

    // Auto-dismiss timer
    const timer = setTimeout(() => {
      removeToast(toast.id)
    }, toast.duration)
    timers.value.set(toast.id, timer)
  }

  // Limit number of visible toasts
  if (toasts.value.size > 5) {
    const oldestToastId = Array.from(toasts.value.keys())[0]
    removeToast(oldestToastId)
  }
}

const removeToast = (toastId: string) => {
  // Clear timers
  const timer = timers.value.get(toastId)
  if (timer) {
    clearTimeout(timer)
    timers.value.delete(toastId)
  }

  const progressTimer = progressTimers.value.get(toastId)
  if (progressTimer) {
    clearInterval(progressTimer)
    progressTimers.value.delete(toastId)
  }

  // Remove toast and progress value
  toasts.value.delete(toastId)
  progressValues.value.delete(toastId)
}

const removeAllToasts = () => {
  // Clear all timers
  timers.value.forEach(timer => clearTimeout(timer))
  progressTimers.value.forEach(timer => clearInterval(timer))
  
  // Clear all maps
  toasts.value.clear()
  timers.value.clear()
  progressTimers.value.clear()
  progressValues.value.clear()
}

const pauseAutoDistamiss = (toastId: string) => {
  const timer = timers.value.get(toastId)
  const progressTimer = progressTimers.value.get(toastId)
  
  if (timer) {
    clearTimeout(timer)
  }
  
  if (progressTimer) {
    clearInterval(progressTimer)
  }
}

const resumeAutoDismiss = (toastId: string) => {
  const toast = toasts.value.get(toastId)
  if (!toast || toast.persistent || !toast.duration) return

  const remainingProgress = progressValues.value.get(toastId) || 0
  const remainingTime = (toast.duration * remainingProgress) / 100

  if (remainingTime > 0) {
    // Resume progress bar animation
    const progressTimer = setInterval(() => {
      const current = progressValues.value.get(toastId) || 0
      if (current > 0) {
        progressValues.value.set(toastId, current - 1)
      }
    }, remainingTime / remainingProgress)
    progressTimers.value.set(toastId, progressTimer)

    // Resume auto-dismiss timer
    const timer = setTimeout(() => {
      removeToast(toastId)
    }, remainingTime)
    timers.value.set(toastId, timer)
  }
}

const handleActionClick = async (toast: ToastNotification, action: NotificationAction) => {
  try {
    await action.action()
  } catch (error) {
    console.error('Error executing toast action:', error)
  }
  
  // Remove toast after action (unless it's persistent)
  if (!toast.persistent) {
    removeToast(toast.id)
  }
}

const getIcon = (type: string) => {
  const iconMap: Record<string, string> = {
    success: 'CheckCircleIcon',
    error: 'XCircleIcon',
    warning: 'ExclamationTriangleIcon',
    info: 'InformationCircleIcon',
    donation: 'BanknotesIcon',
    campaign: 'MegaphoneIcon',
    milestone: 'TrophyIcon'
  }
  return iconMap[type] || 'BellIcon'
}

const getIconClass = (type: string): string => {
  const classMap: Record<string, string> = {
    success: 'text-green-500',
    error: 'text-red-500',
    warning: 'text-yellow-500',
    info: 'text-blue-500',
    donation: 'text-green-500',
    campaign: 'text-blue-500',
    milestone: 'text-yellow-500'
  }
  return classMap[type] || 'text-gray-500'
}

const getProgressBarClass = (type: string): string => {
  const classMap: Record<string, string> = {
    success: 'bg-green-500',
    error: 'bg-red-500',
    warning: 'bg-yellow-500',
    info: 'bg-blue-500',
    donation: 'bg-green-500',
    campaign: 'bg-blue-500',
    milestone: 'bg-yellow-500'
  }
  return classMap[type] || 'bg-gray-500'
}

const getActionButtonClass = (style: string = 'primary'): string => {
  const classMap: Record<string, string> = {
    primary: 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
    secondary: 'bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-500',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500'
  }
  return classMap[style] || classMap.primary
}

const getDefaultDuration = (type: string): number => {
  const durationMap: Record<string, number> = {
    success: 4000,
    error: 6000,
    warning: 5000,
    info: 4000,
    donation: 6000,
    campaign: 5000,
    milestone: 6000
  }
  return durationMap[type] || 4000
}

const getProgressWidth = (toastId: string): number => {
  return progressValues.value.get(toastId) || 0
}

// Toast management functions for external use
const showToast = (
  type: ToastNotification['type'],
  title: string,
  message: string,
  options: Partial<ToastNotification> = {}
) => {
  const toast: ToastNotification = {
    id: `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
    type,
    title,
    message,
    duration: options.duration,
    persistent: options.persistent || false,
    actions: options.actions || []
  }

  addToast(toast)
  return toast.id
}

const showSuccess = (title: string, message: string, options?: Partial<ToastNotification>) =>
  showToast('success', title, message, options)

const showError = (title: string, message: string, options?: Partial<ToastNotification>) =>
  showToast('error', title, message, options)

const showWarning = (title: string, message: string, options?: Partial<ToastNotification>) =>
  showToast('warning', title, message, options)

const showInfo = (title: string, message: string, options?: Partial<ToastNotification>) =>
  showToast('info', title, message, options)

// Listen for toast requests from the notification store
onMounted(() => {
  // Listen for new toast notifications
  notificationStore.$onAction(({ name, args }) => {
    if (name === 'showToast') {
      const [type, title, message, duration] = args
      showToast(type, title, message, { duration })
    }
  })
})

// Cleanup on unmount
onBeforeUnmount(() => {
  removeAllToasts()
})

// Expose methods for programmatic use
defineExpose({
  showToast,
  showSuccess,
  showError,
  showWarning,
  showInfo,
  removeToast,
  removeAllToasts
})
</script>

<style scoped>
/* Toast Transition Animations */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.toast-move {
  transition: transform 0.3s ease;
}

/* Progress bar animation */
@keyframes progress {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

.progress-animate {
  animation: progress linear;
}
</style>