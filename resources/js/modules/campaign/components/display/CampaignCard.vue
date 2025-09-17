<template>
  <div 
    class="campaign-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden cursor-pointer"
    @click="$emit('click', campaign)"
  >
    <!-- Campaign Image -->
    <div class="relative h-48 bg-gray-200 overflow-hidden">
      <img
        v-if="campaign.imageUrl"
        :src="campaign.imageUrl"
        :alt="campaign.title"
        class="w-full h-full object-cover"
        @error="onImageError"
      />
      <div v-else class="flex items-center justify-center h-full bg-gradient-to-br from-blue-500 to-blue-600">
        <svg class="h-16 w-16 text-white opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
        </svg>
      </div>
      
      <!-- Status Badge -->
      <div class="absolute top-3 right-3">
        <span 
          :class="[
            'px-2 py-1 text-xs font-semibold rounded-full',
            statusClasses[campaign.status]
          ]"
        >
          {{ campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1) }}
        </span>
      </div>
      
      <!-- Progress Overlay -->
      <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-4">
        <div class="flex items-center justify-between text-white text-sm">
          <span>{{ Math.round(campaign.progress) }}% {{ $t('campaigns.funded') }}</span>
          <span>{{ $tc('time.daysLeft', campaign.daysRemaining, { count: campaign.daysRemaining }) }}</span>
        </div>
      </div>
    </div>

    <!-- Campaign Content -->
    <div class="p-6">
      <!-- Title -->
      <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
        {{ campaign.title }}
      </h3>
      
      <!-- Description -->
      <p class="text-gray-600 text-sm mb-4 line-clamp-3">
        {{ campaign.description }}
      </p>
      
      <!-- Fancy Progress Bar -->
      <div class="mb-4">
        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
          <span class="font-medium">{{ $t('campaigns.progress') }}</span>
          <span 
            class="font-bold px-2 py-0.5 rounded-full text-xs"
            :class="progressBadgeClass"
          >
            {{ Math.round(campaign.progress) }}%
          </span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden relative">
          <!-- Milestone markers -->
          <div v-for="milestone in [25, 50, 75]" :key="milestone"
               class="absolute top-0 bottom-0 w-px bg-white/30 dark:bg-black/30"
               :style="{ left: milestone + '%' }"
               v-show="campaign.progress < milestone">
          </div>
          
          <div 
            class="h-3 rounded-full transition-all duration-500 ease-out relative overflow-hidden"
            :class="progressColorClass"
            :style="{ width: `${Math.min(campaign.progress, 100)}%` }"
          >
            <!-- Animated stripes -->
            <div class="absolute inset-0 opacity-20">
              <div class="h-full w-full progress-stripes"></div>
            </div>
            
            <!-- Shimmer effect -->
            <div class="absolute inset-0 progress-shimmer"></div>
            
            <!-- Glow at edge -->
            <div v-if="campaign.progress > 0 && campaign.progress < 100"
                 class="absolute right-0 top-0 bottom-0 w-4 progress-glow">
            </div>
          </div>
          
          <!-- Pulse on completion -->
          <div v-if="campaign.progress >= 100"
               class="absolute inset-0 rounded-full bg-green-400 animate-ping-slow opacity-20">
          </div>
        </div>
      </div>
      
      <!-- Amount Information with enhanced styling -->
      <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
        <div>
          <div class="font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-green-600">
            {{ formatAmount(campaign.currentAmount) }}
          </div>
          <div class="text-gray-500 dark:text-gray-400">{{ $t('campaigns.raised') }}</div>
        </div>
        <div>
          <div class="font-semibold text-gray-900 dark:text-gray-100">
            {{ formatAmount(campaign.targetAmount) }}
          </div>
          <div class="text-gray-500 dark:text-gray-400">{{ $t('campaigns.goal') }}</div>
        </div>
      </div>
      
      <!-- Meta Information -->
      <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
        <span>{{ $tc('campaigns.donorCount', campaign.donationsCount, { count: campaign.donationsCount }) }}</span>
        <span>{{ formatDate(campaign.createdAt) }}</span>
      </div>
      
      <!-- Category Tag -->
      <div class="mb-4">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
          {{ campaign.category }}
        </span>
      </div>
      
      <!-- Action Buttons -->
      <div class="flex gap-2">
        <button
          @click.stop="$emit('donate', campaign)"
          class="flex-1 bg-blue-600 text-white text-sm font-medium py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
        >
          {{ $t('campaigns.donateNow') }}
        </button>
        <button
          @click.stop="$emit('share', campaign)"
          class="p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-md transition-colors"
          :title="$t('common.share')"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Campaign } from '@/types';

const { t, tc } = useI18n();

// Props
defineProps<{
  campaign: Campaign;
}>();

// Emits
defineEmits<{
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

const progressColorClass = computed(() => {
  const progress = props.campaign.progress;
  if (progress >= 100) return 'progress-gradient-success';
  if (progress >= 75) return 'progress-gradient-vibrant';
  if (progress >= 50) return 'progress-gradient-progress';
  if (progress >= 25) return 'progress-gradient-active';
  return 'progress-gradient-starting';
});

const progressBadgeClass = computed(() => {
  const progress = props.campaign.progress;
  if (progress >= 100) return 'bg-gradient-to-r from-green-400 to-emerald-500 text-white';
  if (progress >= 75) return 'bg-gradient-to-r from-blue-400 to-green-400 text-white';
  if (progress >= 50) return 'bg-gradient-to-r from-blue-400 to-blue-500 text-white';
  if (progress >= 25) return 'bg-gradient-to-r from-indigo-400 to-blue-400 text-white';
  return 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
});

// Import currency composable
import { useCurrency } from '@/modules/Shared/Infrastructure/Vue/Composables/useCurrency';

// Use currency composable
const { currentCurrency, formatCurrency } = useCurrency();

// Methods
const formatAmount = (amount: number): string => {
  const { locale } = useI18n();
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: currentCurrency.value || 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount);
};

const formatDate = (dateString: string): string => {
  const { locale } = useI18n();
  const date = new Date(dateString);
  return new Intl.DateTimeFormat(locale, {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  }).format(date);
};

const onImageError = (event: Event): void => {
  // Hide broken image, fallback to placeholder
  (event.target as HTMLImageElement).style.display = 'none';
};
</script>

<style scoped>
/* Fancy progress bar styles */

/* Line clamp utilities */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Hover effects */
.campaign-card:hover {
  transform: translateY(-2px);
}

.campaign-card:hover img {
  transform: scale(1.05);
}

.campaign-card:hover .progress-shimmer {
  animation-play-state: running;
}

/* Smooth transitions */
.campaign-card,
.campaign-card img {
  transition: all 0.3s ease;
}

/* Focus states for accessibility */
.campaign-card:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Progress bar animations - Red to Green gradient */
.progress-gradient-success {
  background: linear-gradient(90deg, #10b981 0%, #34d399 50%, #10b981 100%);
  background-size: 200% 100%;
  animation: gradient-shift 3s ease infinite;
}

.progress-gradient-vibrant {
  background: linear-gradient(90deg, #84cc16 0%, #10b981 50%, #84cc16 100%);
  background-size: 200% 100%;
  animation: gradient-shift 3s ease infinite;
}

.progress-gradient-progress {
  background: linear-gradient(90deg, #facc15 0%, #84cc16 50%, #facc15 100%);
  background-size: 200% 100%;
  animation: gradient-shift 4s ease infinite;
}

.progress-gradient-active {
  background: linear-gradient(90deg, #fb923c 0%, #facc15 50%, #fb923c 100%);
  background-size: 200% 100%;
  animation: gradient-shift 4s ease infinite;
}

.progress-gradient-starting {
  background: linear-gradient(90deg, #ef4444 0%, #fb923c 50%, #ef4444 100%);
  background-size: 200% 100%;
  animation: gradient-shift 5s ease infinite;
}

@keyframes gradient-shift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.progress-stripes {
  background-image: repeating-linear-gradient(
    45deg,
    transparent,
    transparent 10px,
    rgba(255, 255, 255, 0.1) 10px,
    rgba(255, 255, 255, 0.1) 20px
  );
  animation: progress-stripes-animation 1s linear infinite;
}

@keyframes progress-stripes-animation {
  0% { background-position: 0 0; }
  100% { background-position: 40px 0; }
}

.progress-shimmer {
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.3) 50%,
    transparent 100%
  );
  animation: shimmer 2s ease-in-out infinite;
  animation-play-state: paused;
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.progress-glow {
  background: radial-gradient(
    circle at center,
    rgba(255, 255, 255, 0.5) 0%,
    transparent 70%
  );
  animation: glow-pulse 2s ease-in-out infinite;
}

@keyframes glow-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

.animate-ping-slow {
  animation: ping-slow 2s cubic-bezier(0, 0, 0.2, 1) infinite;
}

@keyframes ping-slow {
  75%, 100% {
    transform: scale(2);
    opacity: 0;
  }
}
</style>