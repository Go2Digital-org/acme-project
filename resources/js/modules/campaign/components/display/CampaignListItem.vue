<template>
  <div 
    class="campaign-list-item bg-white rounded-lg border border-gray-200 hover:border-gray-300 hover:shadow-md transition-all duration-200 cursor-pointer"
    @click="$emit('click', campaign)"
  >
    <div class="p-6">
      <div class="flex items-start space-x-4">
        <!-- Campaign Image -->
        <div class="flex-shrink-0 w-24 h-24 bg-gray-200 rounded-lg overflow-hidden">
          <img
            v-if="campaign.imageUrl"
            :src="campaign.imageUrl"
            :alt="campaign.title"
            class="w-full h-full object-cover"
            @error="onImageError"
          />
          <div v-else class="flex items-center justify-center h-full bg-gradient-to-br from-blue-500 to-blue-600">
            <svg class="h-8 w-8 text-white opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </div>
        </div>

        <!-- Campaign Content -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0 pr-4">
              <!-- Title and Status -->
              <div class="flex items-center gap-3 mb-2">
                <h3 class="text-lg font-semibold text-gray-900 truncate">
                  {{ campaign.title }}
                </h3>
                <span 
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full flex-shrink-0',
                    statusClasses[campaign.status]
                  ]"
                >
                  {{ campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1) }}
                </span>
              </div>
              
              <!-- Description -->
              <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                {{ campaign.description }}
              </p>
              
              <!-- Meta Information -->
              <div class="flex items-center gap-4 text-xs text-gray-500 mb-3">
                <span class="flex items-center">
                  <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                  {{ campaign.category }}
                </span>
                <span class="flex items-center">
                  <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                  </svg>
                  {{ campaign.donationsCount }} donor{{ campaign.donationsCount !== 1 ? 's' : '' }}
                </span>
                <span class="flex items-center">
                  <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 6h.01M6 20h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                  </svg>
                  {{ formatDate(campaign.createdAt) }}
                </span>
                <span v-if="campaign.daysRemaining > 0" class="flex items-center">
                  <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  {{ campaign.daysRemaining }}d left
                </span>
              </div>
            </div>

            <!-- Amount and Progress -->
            <div class="flex-shrink-0 text-right">
              <div class="mb-2">
                <div class="text-lg font-semibold text-gray-900">
                  {{ formatAmount(campaign.currentAmount) }}
                </div>
                <div class="text-sm text-gray-500">
                  of {{ formatAmount(campaign.targetAmount) }}
                </div>
              </div>
              
              <!-- Progress Percentage -->
              <div class="text-sm font-medium text-blue-600">
                {{ Math.round(campaign.progress) }}% funded
              </div>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mt-4 mb-4">
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${Math.min(campaign.progress, 100)}%` }"
              ></div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-between items-center">
            <div class="flex gap-2">
              <button
                @click.stop="$emit('donate', campaign)"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
              >
                Donate Now
              </button>
              <button
                @click.stop="viewDetails"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
              >
                View Details
              </button>
            </div>
            
            <!-- Share Button -->
            <button
              @click.stop="$emit('share', campaign)"
              class="inline-flex items-center p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-md transition-colors"
              title="Share campaign"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Campaign } from '@/types';

// Props
defineProps<{
  campaign: Campaign;
}>();

// Emits
const emit = defineEmits<{
  'click': [campaign: Campaign];
  'donate': [campaign: Campaign];
  'share': [campaign: Campaign];
}>();

// Computed
const statusClasses = computed(() => ({
  'active': 'bg-green-100 text-green-800',
  'completed': 'bg-blue-100 text-blue-800',
  'draft': 'bg-gray-100 text-gray-800',
  'cancelled': 'bg-red-100 text-red-800'
}));

// Import currency composable
import { useCurrency } from '@/modules/Shared/Infrastructure/Vue/Composables/useCurrency';

// Use currency composable
const { currentCurrency } = useCurrency();

// Methods
const formatAmount = (amount: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currentCurrency.value || 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount);
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  }).format(date);
};

const onImageError = (event: Event): void => {
  // Hide broken image, fallback to placeholder
  (event.target as HTMLImageElement).style.display = 'none';
};

const viewDetails = (): void => {
  // This would navigate to the campaign detail page
  // For now, just emit the click event
  emit('click', campaign);
};
</script>

<style scoped>
/* Line clamp utility */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Hover effects */
.campaign-list-item:hover {
  transform: translateY(-1px);
}

/* Smooth transitions */
.campaign-list-item {
  transition: all 0.2s ease;
}

/* Focus states for accessibility */
.campaign-list-item:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .campaign-list-item .flex {
    flex-direction: column;
    space-x: 0;
    space-y: 1rem;
  }
  
  .campaign-list-item .flex-shrink-0 {
    flex-shrink: 1;
    width: 100%;
    text-align: left;
  }
}
</style>