<template>
  <div class="campaign-progress">
    <!-- Progress Bar -->
    <div class="mb-4">
      <div class="flex justify-between items-center mb-2">
        <span class="text-sm font-medium text-gray-700">Progress</span>
        <span class="text-sm font-medium text-gray-900">
          {{ Math.round(currentProgress) }}%
        </span>
      </div>
      
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden relative fancy-progress-container">
        <!-- Milestone markers -->
        <div v-for="milestone in [25, 50, 75]" :key="milestone"
             class="absolute top-0 bottom-0 w-px bg-white/30 dark:bg-black/30 milestone-marker"
             :style="{ left: milestone + '%' }"
             v-show="currentProgress < milestone">
        </div>
        
        <div 
          class="h-4 rounded-full transition-all duration-1000 ease-out relative overflow-hidden fancy-progress-fill"
          :class="progressColorClass"
          :style="{ width: `${Math.min(currentProgress, 100)}%` }"
        >
          <!-- Animated stripes pattern -->
          <div class="absolute inset-0 opacity-20">
            <div class="h-full w-full progress-stripes"></div>
          </div>
          
          <!-- Shimmer effect -->
          <div v-if="showAnimation && isAnimating"
               class="absolute inset-0 progress-shimmer">
          </div>
          
          <!-- Wave animation for high progress -->
          <div v-if="currentProgress >= 75"
               class="absolute inset-0 progress-wave">
          </div>
          
          <!-- Glow effect at the edge -->
          <div v-if="currentProgress > 0 && currentProgress < 100"
               class="absolute right-0 top-0 bottom-0 w-8 progress-glow">
          </div>
        </div>
        
        <!-- Pulse ring on goal reached -->
        <div v-if="currentProgress >= 100"
             class="absolute inset-0 rounded-full">
          <div class="absolute inset-0 rounded-full bg-green-400 animate-ping-slow opacity-20"></div>
        </div>
      </div>
    </div>

    <!-- Amount Information -->
    <div class="grid grid-cols-2 gap-4 mb-4">
      <div class="text-center">
        <div class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-green-600">
          {{ formatAmount(currentAmount) }}
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Raised</div>
      </div>
      <div class="text-center">
        <div class="text-2xl font-bold text-gray-700 dark:text-gray-300">
          {{ formatAmount(targetAmount) }}
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Goal</div>
      </div>
    </div>

    <!-- Additional Stats -->
    <div v-if="campaign" class="grid grid-cols-2 gap-4 text-center text-sm text-gray-600">
      <div>
        <div class="font-semibold">{{ campaign.donationsCount }}</div>
        <div>Donations</div>
      </div>
      <div>
        <div class="font-semibold">
          {{ campaign.daysRemaining > 0 ? campaign.daysRemaining : 0 }}
        </div>
        <div>Days Left</div>
      </div>
    </div>

    <!-- Real-time Update Indicator -->
    <div 
      v-if="isUpdating" 
      class="flex items-center justify-center mt-4 text-sm text-blue-600"
    >
      <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      Updating progress...
    </div>

    <!-- Milestone Celebration -->
    <div 
      v-if="showMilestone"
      class="fixed inset-0 flex items-center justify-center z-50 pointer-events-none"
    >
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm mx-4 pointer-events-auto animate-celebration">
        <div class="text-center">
          <div class="text-4xl mb-2">🎉</div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Milestone Reached!</h3>
          <p class="text-gray-600">
            This campaign has reached {{ Math.round(currentProgress) }}% of its goal!
          </p>
          <button 
            @click="closeMilestone"
            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
          >
            Awesome!
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useCampaignStore } from '@/stores/campaign';
import type { CampaignProgressProps, Campaign } from '@/types';

// Props
const props = withDefaults(defineProps<CampaignProgressProps>(), {
  initialProgress: 0,
  showAnimation: true,
  updateInterval: 30000 // 30 seconds
});

// Stores
const campaignStore = useCampaignStore();

// Reactive data
const currentAmount = ref<number>(0);
const targetAmount = ref<number>(1);
const currentProgress = ref<number>(props.initialProgress);
const isAnimating = ref<boolean>(false);
const isUpdating = ref<boolean>(false);
const showMilestone = ref<boolean>(false);
const lastMilestone = ref<number>(0);
const updateTimer = ref<NodeJS.Timeout | null>(null);

// Computed
const campaign = computed((): Campaign | null => {
  return campaignStore.currentCampaign || 
         campaignStore.campaigns.find(c => c.id === props.campaignId) || 
         null;
});

const progressColorClass = computed(() => {
  if (currentProgress.value >= 100) return 'progress-gradient-success';
  if (currentProgress.value >= 75) return 'progress-gradient-vibrant';
  if (currentProgress.value >= 50) return 'progress-gradient-progress';
  if (currentProgress.value >= 25) return 'progress-gradient-active';
  return 'progress-gradient-starting';
});

const urgencyLevel = computed(() => {
  if (!campaign.value) return 'normal';
  const days = campaign.value.daysRemaining;
  if (days === 0) return 'critical';
  if (days <= 3) return 'very-high';
  if (days <= 7) return 'high';
  if (days <= 14) return 'medium';
  return 'normal';
});

// Methods
const formatAmount = (amount: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: campaign.value?.currency || 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount);
};

const updateProgress = async (): Promise<void> => {
  if (isUpdating.value) return;
  
  isUpdating.value = true;
  
  try {
    const updatedCampaign = await campaignStore.fetchCampaign(props.campaignId);
    
    const newProgress = updatedCampaign.progress;
    const newAmount = updatedCampaign.currentAmount;
    
    // Check for milestone achievements (every 25%)
    const milestones = [25, 50, 75, 100];
    const reachedMilestone = milestones.find(
      milestone => newProgress >= milestone && lastMilestone.value < milestone
    );
    
    if (reachedMilestone && props.showAnimation) {
      showMilestone.value = true;
      lastMilestone.value = reachedMilestone;
    }
    
    // Animate progress update
    if (props.showAnimation && newProgress !== currentProgress.value) {
      isAnimating.value = true;
      
      // Animate the progress bar
      const startProgress = currentProgress.value;
      const startAmount = currentAmount.value;
      const progressDiff = newProgress - startProgress;
      const amountDiff = newAmount - startAmount;
      const duration = 1000; // 1 second
      const steps = 60; // 60fps
      const stepTime = duration / steps;
      
      let step = 0;
      const animate = () => {
        if (step <= steps) {
          const easeProgress = easeOutCubic(step / steps);
          currentProgress.value = startProgress + (progressDiff * easeProgress);
          currentAmount.value = startAmount + (amountDiff * easeProgress);
          targetAmount.value = updatedCampaign.targetAmount;
          
          step++;
          setTimeout(animate, stepTime);
        } else {
          isAnimating.value = false;
          currentProgress.value = newProgress;
          currentAmount.value = newAmount;
        }
      };
      
      animate();
    } else {
      // Direct update without animation
      currentProgress.value = newProgress;
      currentAmount.value = newAmount;
      targetAmount.value = updatedCampaign.targetAmount;
    }
    
  } catch (error) {
    console.error('Failed to update campaign progress:', error);
  } finally {
    isUpdating.value = false;
  }
};

const easeOutCubic = (t: number): number => {
  return 1 - Math.pow(1 - t, 3);
};

const closeMilestone = (): void => {
  showMilestone.value = false;
};

const startPeriodicUpdates = (): void => {
  if (updateTimer.value) {
    clearInterval(updateTimer.value);
  }
  
  updateTimer.value = setInterval(() => {
    updateProgress();
  }, props.updateInterval);
};

const stopPeriodicUpdates = (): void => {
  if (updateTimer.value) {
    clearInterval(updateTimer.value);
    updateTimer.value = null;
  }
};

// Watchers
watch(() => props.campaignId, () => {
  updateProgress();
}, { immediate: true });

// Listen for real-time updates from the campaign store
watch(() => campaign.value, (newCampaign) => {
  if (newCampaign) {
    currentProgress.value = newCampaign.progress;
    currentAmount.value = newCampaign.currentAmount;
    targetAmount.value = newCampaign.targetAmount;
  }
});

// Lifecycle
onMounted(async () => {
  // Initial load
  await updateProgress();
  
  // Start periodic updates
  startPeriodicUpdates();
  
  // Listen for page visibility changes to pause/resume updates
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopPeriodicUpdates();
    } else {
      startPeriodicUpdates();
      updateProgress(); // Immediate update when page becomes visible
    }
  });
});

onUnmounted(() => {
  stopPeriodicUpdates();
});
</script>

<style scoped>
/* Fancy progress bar styles for Vue component */

/* Additional Vue-specific styles */
.fancy-progress-container {
  position: relative;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.fancy-progress-fill {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: relative;
}

/* Milestone celebrations */
.milestone-marker {
  transition: opacity 0.3s ease;
}

/* Responsive progress text */
@media (max-width: 640px) {
  .fancy-progress-container {
    height: 0.75rem;
  }
  
  .fancy-progress-fill {
    height: 0.75rem;
  }
}

/* Celebration animation */
@keyframes celebration {
  0% {
    transform: scale(0.8) translateY(20px);
    opacity: 0;
  }
  50% {
    transform: scale(1.05) translateY(-10px);
    opacity: 1;
  }
  100% {
    transform: scale(1) translateY(0);
    opacity: 1;
  }
}

.animate-celebration {
  animation: celebration 0.6s ease-out;
}

/* Pulse animation for updating indicator */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Smooth transitions */
.campaign-progress * {
  transition: all 0.3s ease;
}

/* Responsive design */
@media (max-width: 640px) {
  .grid-cols-2 {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }
  
  .text-2xl {
    font-size: 1.5rem;
  }
}
</style>