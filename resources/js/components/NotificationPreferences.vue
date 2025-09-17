<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div 
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
        @click="$emit('close')"
      ></div>

      <!-- Modal panel -->
      <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
        <div class="absolute top-0 right-0 pt-4 pr-4">
          <button
            @click="$emit('close')"
            type="button"
            class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <span class="sr-only">Close</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="sm:flex sm:items-start">
          <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
            <!-- Header -->
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6" id="modal-title">
              Notification Preferences
            </h3>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-8">
              <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="text-gray-600">Loading preferences...</span>
            </div>

            <!-- Form -->
            <form v-else @submit.prevent="savePreferences" class="space-y-6">
              <!-- General Settings -->
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-4">General Settings</h4>
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <label for="email-notifications" class="text-sm font-medium text-gray-700">
                        Email Notifications
                      </label>
                      <p class="text-sm text-gray-500">Receive notifications via email</p>
                    </div>
                    <button
                      type="button"
                      @click="toggleSetting('emailNotifications')"
                      :class="[
                        preferences.emailNotifications ? 'bg-indigo-600' : 'bg-gray-200',
                        'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                      ]"
                      role="switch"
                      :aria-checked="preferences.emailNotifications"
                    >
                      <span
                        :class="[
                          preferences.emailNotifications ? 'translate-x-5' : 'translate-x-0',
                          'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200'
                        ]"
                      ></span>
                    </button>
                  </div>

                  <div class="flex items-center justify-between">
                    <div>
                      <label for="browser-notifications" class="text-sm font-medium text-gray-700">
                        Browser Notifications
                      </label>
                      <p class="text-sm text-gray-500">Show desktop notifications in your browser</p>
                    </div>
                    <button
                      type="button"
                      @click="toggleSetting('browserNotifications')"
                      :class="[
                        preferences.browserNotifications ? 'bg-indigo-600' : 'bg-gray-200',
                        'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                      ]"
                      role="switch"
                      :aria-checked="preferences.browserNotifications"
                    >
                      <span
                        :class="[
                          preferences.browserNotifications ? 'translate-x-5' : 'translate-x-0',
                          'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200'
                        ]"
                      ></span>
                    </button>
                  </div>

                  <div class="flex items-center justify-between">
                    <div>
                      <label for="sms-notifications" class="text-sm font-medium text-gray-700">
                        SMS Notifications
                      </label>
                      <p class="text-sm text-gray-500">Receive critical notifications via SMS</p>
                    </div>
                    <button
                      type="button"
                      @click="toggleSetting('smsNotifications')"
                      :class="[
                        preferences.smsNotifications ? 'bg-indigo-600' : 'bg-gray-200',
                        'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                      ]"
                      role="switch"
                      :aria-checked="preferences.smsNotifications"
                    >
                      <span
                        :class="[
                          preferences.smsNotifications ? 'translate-x-5' : 'translate-x-0',
                          'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200'
                        ]"
                      ></span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Category Settings -->
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-4">Category Settings</h4>
                <div class="space-y-4">
                  <div v-for="(category, key) in preferences.categories" :key="key">
                    <h5 class="text-sm font-medium text-gray-800 capitalize mb-2">
                      {{ formatCategoryName(key) }}
                    </h5>
                    <div class="grid grid-cols-3 gap-4 pl-4">
                      <div class="flex items-center">
                        <input
                          :id="`${key}-email`"
                          type="checkbox"
                          v-model="category.email"
                          class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label :for="`${key}-email`" class="ml-2 text-sm text-gray-600">Email</label>
                      </div>
                      <div class="flex items-center">
                        <input
                          :id="`${key}-browser`"
                          type="checkbox"
                          v-model="category.browser"
                          class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label :for="`${key}-browser`" class="ml-2 text-sm text-gray-600">Browser</label>
                      </div>
                      <div class="flex items-center">
                        <input
                          :id="`${key}-sms`"
                          type="checkbox"
                          v-model="category.sms"
                          class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label :for="`${key}-sms`" class="ml-2 text-sm text-gray-600">SMS</label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Quiet Hours -->
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-4">Quiet Hours</h4>
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <label class="text-sm font-medium text-gray-700">Enable Quiet Hours</label>
                      <p class="text-sm text-gray-500">Suppress non-urgent notifications during specified hours</p>
                    </div>
                    <button
                      type="button"
                      @click="toggleQuietHours"
                      :class="[
                        preferences.quietHours.enabled ? 'bg-indigo-600' : 'bg-gray-200',
                        'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                      ]"
                      role="switch"
                      :aria-checked="preferences.quietHours.enabled"
                    >
                      <span
                        :class="[
                          preferences.quietHours.enabled ? 'translate-x-5' : 'translate-x-0',
                          'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200'
                        ]"
                      ></span>
                    </button>
                  </div>

                  <div v-if="preferences.quietHours.enabled" class="grid grid-cols-2 gap-4 pl-4">
                    <div>
                      <label for="quiet-start" class="block text-sm font-medium text-gray-700">Start Time</label>
                      <input
                        id="quiet-start"
                        type="time"
                        v-model="preferences.quietHours.startTime"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      />
                    </div>
                    <div>
                      <label for="quiet-end" class="block text-sm font-medium text-gray-700">End Time</label>
                      <input
                        id="quiet-end"
                        type="time"
                        v-model="preferences.quietHours.endTime"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Frequency Setting -->
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-4">Notification Frequency</h4>
                <select
                  v-model="preferences.frequency"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
                  <option value="immediate">Immediate - Get notifications as they happen</option>
                  <option value="hourly">Hourly - Receive hourly digests</option>
                  <option value="daily">Daily - Receive daily summary</option>
                  <option value="weekly">Weekly - Receive weekly summary</option>
                </select>
              </div>

              <!-- Action Buttons -->
              <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <button
                  type="button"
                  @click="$emit('close')"
                  class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="saving"
                  class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-colors duration-200"
                >
                  <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ saving ? 'Saving...' : 'Save Preferences' }}
                </button>
              </div>
            </form>

            <!-- Permission Notice -->
            <div v-if="showBrowserPermissionNotice" class="mt-4 p-4 bg-yellow-50 border-l-4 border-yellow-400">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm text-yellow-700">
                    Browser notifications require permission. 
                    <button 
                      @click="requestBrowserPermission"
                      class="font-medium underline hover:text-yellow-600"
                    >
                      Click here to enable
                    </button>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useNotificationPreferencesStore } from '@/stores/notificationPreferences'
import type { NotificationPreferences } from '@/types'

// Emits
const emit = defineEmits<{
  (e: 'close'): void
}>()

// Store
const preferencesStore = useNotificationPreferencesStore()
const { preferences, loading, saving } = preferencesStore

// State
const showBrowserPermissionNotice = ref(false)

// Computed
const isBrowserNotificationSupported = computed(() => 'Notification' in window)

// Methods
const toggleSetting = (setting: keyof NotificationPreferences) => {
  if (preferences.value) {
    preferences.value[setting] = !preferences.value[setting] as any
  }
}

const toggleQuietHours = () => {
  if (preferences.value) {
    preferences.value.quietHours.enabled = !preferences.value.quietHours.enabled
  }
}

const formatCategoryName = (key: string): string => {
  const categoryNames: Record<string, string> = {
    donation: 'Donations',
    campaign: 'Campaigns', 
    milestone: 'Milestones',
    system: 'System',
    security: 'Security'
  }
  return categoryNames[key] || key.charAt(0).toUpperCase() + key.slice(1)
}

const requestBrowserPermission = async () => {
  if (!isBrowserNotificationSupported.value) return
  
  try {
    const permission = await Notification.requestPermission()
    if (permission === 'granted') {
      showBrowserPermissionNotice.value = false
      if (preferences.value) {
        preferences.value.browserNotifications = true
      }
    }
  } catch (error) {
    console.error('Error requesting notification permission:', error)
  }
}

const savePreferences = async () => {
  try {
    await preferencesStore.savePreferences()
    emit('close')
  } catch (error) {
    console.error('Error saving preferences:', error)
  }
}

// Check browser notification permission
const checkBrowserPermission = () => {
  if (!isBrowserNotificationSupported.value) {
    showBrowserPermissionNotice.value = false
    return
  }
  
  showBrowserPermissionNotice.value = 
    preferences.value?.browserNotifications && 
    Notification.permission !== 'granted'
}

// Lifecycle
onMounted(async () => {
  await preferencesStore.fetchPreferences()
  checkBrowserPermission()
})
</script>