<template>
  <div class="step-review">
    <h2 class="text-2xl font-bold mb-6">Review Your Campaign</h2>
    
    <div class="space-y-8">
      <!-- Basic Information Summary -->
      <div class="review-section">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Basic Information
        </h3>
        <div class="review-content">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-500">Campaign Title</label>
              <p class="text-gray-900">{{ localData.title || 'Not specified' }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Category</label>
              <p class="text-gray-900">{{ getCategoryName(localData.category_id) }}</p>
            </div>
            <div class="md:col-span-2">
              <label class="text-sm font-medium text-gray-500">Description</label>
              <p class="text-gray-900">{{ localData.description || 'Not specified' }}</p>
            </div>
            <div class="md:col-span-2" v-if="localData.long_description">
              <label class="text-sm font-medium text-gray-500">Detailed Description</label>
              <p class="text-gray-900 text-sm">{{ localData.long_description }}</p>
            </div>
            <div v-if="localData.tags && localData.tags.length > 0" class="md:col-span-2">
              <label class="text-sm font-medium text-gray-500">Tags</label>
              <div class="flex flex-wrap gap-2 mt-1">
                <span
                  v-for="tag in localData.tags"
                  :key="tag"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800"
                >
                  {{ tag }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Goal & Timeline Summary -->
      <div class="review-section">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
          </svg>
          Goal & Timeline
        </h3>
        <div class="review-content">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-500">Goal Amount</label>
              <p class="text-2xl font-bold text-green-600">{{ formattedGoalAmount }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Currency</label>
              <p class="text-gray-900">{{ localData.currency || 'EUR' }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Start Date</label>
              <p class="text-gray-900">{{ formatDate(localData.start_date) }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">End Date</label>
              <p class="text-gray-900">{{ formatDate(localData.end_date) }}</p>
            </div>
            <div class="md:col-span-2">
              <label class="text-sm font-medium text-gray-500">Campaign Duration</label>
              <p class="text-gray-900">{{ campaignDuration }} days</p>
            </div>
            <div class="md:col-span-2">
              <label class="text-sm font-medium text-gray-500">Overfunding</label>
              <p class="text-gray-900">
                <span v-if="localData.allow_overfunding" class="text-green-600">✓ Allowed</span>
                <span v-else class="text-gray-500">✗ Not allowed</span>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Media Summary -->
      <div class="review-section">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          Media
        </h3>
        <div class="review-content">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-500">Featured Image</label>
              <div class="mt-2">
                <div v-if="localData.featured_image_preview" class="featured-image-preview">
                  <img 
                    :src="localData.featured_image_preview" 
                    alt="Featured image" 
                    class="w-full h-32 object-cover rounded border"
                  />
                </div>
                <p v-else class="text-gray-400 text-sm">No featured image selected</p>
              </div>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Additional Images</label>
              <div class="mt-2">
                <p v-if="additionalImageCount > 0" class="text-gray-900">
                  {{ additionalImageCount }} additional image{{ additionalImageCount !== 1 ? 's' : '' }}
                </p>
                <p v-else class="text-gray-400 text-sm">No additional images</p>
              </div>
            </div>
            <div v-if="localData.video_url" class="md:col-span-2">
              <label class="text-sm font-medium text-gray-500">Video URL</label>
              <p class="text-gray-900 break-all">{{ localData.video_url }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Campaign Settings Summary -->
      <div class="review-section">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          Settings
        </h3>
        <div class="review-content">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-500">Status</label>
              <p class="text-gray-900 capitalize">{{ localData.status || 'Draft' }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Featured Campaign</label>
              <p class="text-gray-900">
                <span v-if="localData.is_featured" class="text-green-600">✓ Yes</span>
                <span v-else class="text-gray-500">✗ No</span>
              </p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Anonymous Campaign</label>
              <p class="text-gray-900">
                <span v-if="localData.is_anonymous" class="text-blue-600">✓ Yes</span>
                <span v-else class="text-gray-500">✗ No</span>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Validation Summary -->
      <div v-if="hasValidationErrors" class="validation-summary">
        <h3 class="text-lg font-semibold mb-4 text-red-600 flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
          Please Fix These Issues
        </h3>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
          <ul class="space-y-1 text-sm text-red-700">
            <li v-for="(error, field) in errors" :key="field">
              • {{ error }}
            </li>
          </ul>
        </div>
      </div>

      <!-- Success Message -->
      <div v-else class="success-summary">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
          <div class="flex items-center">
            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-green-800 font-medium">
              Your campaign is ready to be created!
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, inject } from 'vue';

// Props
const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

// Emit
const emit = defineEmits(['update:modelValue', 'next', 'previous']);

// Inject parent data if available
const organizations = inject('organizations', []);
const categories = inject('categories', []);

// Local state
const localData = ref({ ...props.modelValue });

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  localData.value = { ...newValue };
}, { deep: true });

// Computed properties
const formattedGoalAmount = computed(() => {
  if (localData.value.goal_amount) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: localData.value.currency || 'EUR'
    }).format(localData.value.goal_amount);
  }
  return '€0';
});

const campaignDuration = computed(() => {
  if (localData.value.start_date && localData.value.end_date) {
    const startDate = new Date(localData.value.start_date);
    const endDate = new Date(localData.value.end_date);
    const timeDiff = endDate.getTime() - startDate.getTime();
    const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
    return dayDiff > 0 ? dayDiff : 0;
  }
  return 0;
});

const additionalImageCount = computed(() => {
  if (localData.value.additional_images && Array.isArray(localData.value.additional_images)) {
    return localData.value.additional_images.length;
  }
  return 0;
});

const hasValidationErrors = computed(() => {
  return Object.keys(props.errors).length > 0;
});

// Methods
const updateData = () => {
  emit('update:modelValue', localData.value);
};

const formatDate = (dateString) => {
  if (!dateString) return 'Not specified';
  
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const getCategoryName = (categoryId) => {
  if (!categoryId) return 'Not specified';
  
  const category = categories.find(cat => cat.id === categoryId);
  return category ? category.name : 'Unknown Category';
};

const getOrganizationName = (organizationId) => {
  if (!organizationId) return 'Not specified';
  
  const organization = organizations.find(org => org.id === organizationId);
  return organization ? organization.name : 'Unknown Organization';
};
</script>

<style scoped>
.review-section {
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1.5rem;
  background-color: #f9fafb;
}

.review-content {
  background-color: white;
  border-radius: 0.375rem;
  padding: 1rem;
}

.review-content label {
  display: block;
  margin-bottom: 0.25rem;
}

.review-content p {
  margin-bottom: 0;
}

.featured-image-preview img {
  border: 2px solid #e5e7eb;
}

.validation-summary,
.success-summary {
  margin-top: 1rem;
}
</style>