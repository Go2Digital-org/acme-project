<template>
  <div class="campaign-wizard">
    <!-- Progress Indicator -->
    <div class="wizard-progress mb-8">
      <div class="flex justify-between items-center">
        <div 
          v-for="(step, index) in steps" 
          :key="step.id"
          class="flex-1 text-center"
        >
          <div class="relative">
            <!-- Progress Line -->
            <div 
              v-if="index < steps.length - 1"
              class="absolute top-5 w-full h-0.5 bg-gray-300"
              :class="{ 'bg-green-500': index < currentStep }"
              style="left: 50%;"
            ></div>
            
            <!-- Step Circle -->
            <div 
              class="relative z-10 w-10 h-10 mx-auto rounded-full flex items-center justify-center text-sm font-semibold"
              :class="getStepClasses(index)"
            >
              <svg v-if="index < currentStep" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <span v-else>{{ index + 1 }}</span>
            </div>
            
            <!-- Step Label -->
            <div class="mt-2 text-xs font-medium" :class="index <= currentStep ? 'text-gray-900' : 'text-gray-500'">
              {{ step.label }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Step Content -->
    <form @submit.prevent="handleSubmit" class="wizard-content">
      <transition name="slide-fade" mode="out-in">
        <component 
          :is="currentStepComponent" 
          v-model="formData"
          :errors="validationErrors"
          @next="nextStep"
          @previous="previousStep"
        />
      </transition>

      <!-- Navigation Buttons -->
      <div class="wizard-navigation mt-8 flex justify-between">
        <button
          v-if="currentStep > 0"
          type="button"
          @click="previousStep"
          class="btn btn-outline"
        >
          Previous
        </button>
        <div v-else></div>

        <button
          v-if="currentStep < steps.length - 1"
          type="button"
          @click="nextStep"
          class="btn btn-primary"
          :disabled="!canProceed"
        >
          Next
        </button>
        
        <button
          v-else
          type="submit"
          class="btn btn-primary"
          :disabled="isSubmitting"
        >
          <span v-if="isSubmitting">Creating...</span>
          <span v-else>Create Campaign</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, defineAsyncComponent } from 'vue';
import { useCampaignStore } from '../../Stores/campaignStore';
// import { useNotification } from '@/composables/useNotification';

// Import step components
const BasicInfoStep = defineAsyncComponent(() => import('./Steps/BasicInfoStep.vue'));
const GoalSettingsStep = defineAsyncComponent(() => import('./Steps/GoalSettingsStep.vue'));
const MediaUploadStep = defineAsyncComponent(() => import('./Steps/MediaUploadStep.vue'));
const ReviewStep = defineAsyncComponent(() => import('./Steps/ReviewStep.vue'));

// Props
const props = defineProps({
  organizations: {
    type: Array,
    default: () => []
  },
  categories: {
    type: Array,
    default: () => []
  },
  user: {
    type: Object,
    required: true
  },
  csrfToken: {
    type: String,
    required: true
  }
});

// Emit events
const emit = defineEmits(['complete', 'cancel']);

// Store and composables
const campaignStore = useCampaignStore();
// const { showSuccess, showError } = useNotification();

// Wizard state
const currentStep = ref(0);
const isSubmitting = ref(false);
const validationErrors = ref({});

// Form data
const formData = ref({
  // Basic Info
  title: '',
  description: '',
  long_description: '',
  category_id: null,
  organization_id: null,
  tags: [],
  
  // Goal Settings
  goal_amount: 1000,
  currency: localStorage.getItem('currency') || 'EUR',
  start_date: '',
  end_date: '',
  allow_overfunding: false,
  
  // Media
  featured_image: null,
  gallery_images: [],
  video_url: '',
  
  // Settings
  is_featured: false,
  is_anonymous: false,
  status: 'draft'
});

// Steps configuration
const steps = [
  {
    id: 'basic-info',
    label: 'Basic Information',
    component: BasicInfoStep,
    validation: ['title', 'description', 'category_id', 'organization_id']
  },
  {
    id: 'goal-settings',
    label: 'Goal & Timeline',
    component: GoalSettingsStep,
    validation: ['goal_amount', 'start_date', 'end_date']
  },
  {
    id: 'media-upload',
    label: 'Media',
    component: MediaUploadStep,
    validation: []
  },
  {
    id: 'review',
    label: 'Review',
    component: ReviewStep,
    validation: []
  }
];

// Computed properties
const currentStepComponent = computed(() => steps[currentStep.value].component);

const canProceed = computed(() => {
  const currentStepValidation = steps[currentStep.value].validation;
  return currentStepValidation.every(field => {
    const value = formData.value[field];
    return value !== null && value !== '' && value !== undefined;
  });
});

const getStepClasses = (index) => {
  if (index < currentStep.value) {
    return 'bg-green-500 text-white';
  } else if (index === currentStep.value) {
    return 'bg-blue-500 text-white';
  } else {
    return 'bg-gray-300 text-gray-500';
  }
};

// Methods
const validateCurrentStep = () => {
  validationErrors.value = {};
  const currentStepValidation = steps[currentStep.value].validation;
  
  let isValid = true;
  currentStepValidation.forEach(field => {
    const value = formData.value[field];
    if (!value || (Array.isArray(value) && value.length === 0)) {
      validationErrors.value[field] = `${field.replace('_', ' ')} is required`;
      isValid = false;
    }
  });
  
  // Additional validation rules
  if (currentStep.value === 1) {
    // Goal settings validation
    if (formData.value.goal_amount < 100) {
      validationErrors.value.goal_amount = 'Goal amount must be at least €100';
      isValid = false;
    }
    
    const startDate = new Date(formData.value.start_date);
    const endDate = new Date(formData.value.end_date);
    
    if (startDate >= endDate) {
      validationErrors.value.end_date = 'End date must be after start date';
      isValid = false;
    }
    
    const maxDuration = 365; // days
    const duration = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
    if (duration > maxDuration) {
      validationErrors.value.end_date = `Campaign duration cannot exceed ${maxDuration} days`;
      isValid = false;
    }
  }
  
  return isValid;
};

const nextStep = () => {
  if (validateCurrentStep() && currentStep.value < steps.length - 1) {
    currentStep.value++;
  }
};

const previousStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--;
    validationErrors.value = {};
  }
};

const handleSubmit = async () => {
  if (!validateCurrentStep()) {
    return;
  }
  
  isSubmitting.value = true;
  
  try {
    // Prepare form data for submission
    const submitData = new FormData();
    
    // Add all text fields
    Object.entries(formData.value).forEach(([key, value]) => {
      if (value !== null && value !== undefined) {
        if (Array.isArray(value)) {
          value.forEach(item => submitData.append(`${key}[]`, item));
        } else if (value instanceof File) {
          submitData.append(key, value);
        } else {
          submitData.append(key, value);
        }
      }
    });
    
    // Add CSRF token
    submitData.append('_token', props.csrfToken);
    
    // Submit to API
    const response = await campaignStore.createCampaign(submitData);
    
    // showSuccess('Campaign created successfully!');
    console.log('Campaign created successfully!');
    
    // Emit complete event with created campaign
    emit('complete', response.data);
    
    // Redirect to campaign page
    setTimeout(() => {
      window.location.href = `/campaigns/${response.data.id}`;
    }, 1500);
    
  } catch (error) {
    console.error('Failed to create campaign:', error);
    
    if (error.response?.data?.errors) {
      validationErrors.value = error.response.data.errors;
      // showError('Please fix the validation errors');
      console.error('Please fix the validation errors');
      
      // Find the first step with errors and navigate to it
      for (let i = 0; i < steps.length; i++) {
        const stepFields = steps[i].validation;
        if (stepFields.some(field => validationErrors.value[field])) {
          currentStep.value = i;
          break;
        }
      }
    } else {
      // showError('Failed to create campaign. Please try again.');
      console.error('Failed to create campaign. Please try again.');
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-fade-enter-from {
  transform: translateX(20px);
  opacity: 0;
}

.slide-fade-leave-to {
  transform: translateX(-20px);
  opacity: 0;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: background-color 0.2s, color 0.2s;
}

.btn-primary {
  background-color: #2563eb;
  color: white;
}

.btn-primary:hover {
  background-color: #1d4ed8;
}

.btn-primary:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.btn-outline {
  border: 1px solid #d1d5db;
  color: #374151;
}

.btn-outline:hover {
  background-color: #f9fafb;
}
</style>