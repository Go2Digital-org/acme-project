<template>
  <div class="step-goal-settings">
    <h2 class="text-2xl font-bold mb-6">Goal & Timeline Settings</h2>
    
    <div class="space-y-6">
      <!-- Goal Amount and Currency -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Goal Amount -->
        <div class="form-group">
          <label for="goal_amount" class="block text-sm font-medium text-gray-700 mb-1">
            Goal Amount <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">
              {{ currencySymbol }}
            </span>
            <input
              id="goal_amount"
              v-model.number="localData.goal_amount"
              type="number"
              min="100"
              step="50"
              class="form-input w-full pl-8"
              :class="{ 'border-red-500': errors.goal_amount }"
              placeholder="1000"
              @input="updateData"
            />
          </div>
          <p v-if="errors.goal_amount" class="mt-1 text-sm text-red-600">{{ errors.goal_amount }}</p>
          <p class="mt-1 text-sm text-gray-500">Minimum goal amount is €100</p>
        </div>

        <!-- Currency -->
        <div class="form-group">
          <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">
            Currency
          </label>
          <select
            id="currency"
            v-model="localData.currency"
            class="form-select w-full"
            @change="updateData"
          >
            <option value="EUR">EUR (€)</option>
            <option value="USD">USD ($)</option>
            <option value="GBP">GBP (£)</option>
          </select>
        </div>
      </div>

      <!-- Start and End Dates -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Start Date -->
        <div class="form-group">
          <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">
            Start Date <span class="text-red-500">*</span>
          </label>
          <input
            id="start_date"
            v-model="localData.start_date"
            type="date"
            class="form-input w-full"
            :class="{ 'border-red-500': errors.start_date }"
            :min="minStartDate"
            @input="updateData"
          />
          <p v-if="errors.start_date" class="mt-1 text-sm text-red-600">{{ errors.start_date }}</p>
        </div>

        <!-- End Date -->
        <div class="form-group">
          <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">
            End Date <span class="text-red-500">*</span>
          </label>
          <input
            id="end_date"
            v-model="localData.end_date"
            type="date"
            class="form-input w-full"
            :class="{ 'border-red-500': errors.end_date }"
            :min="minEndDate"
            @input="updateData"
          />
          <p v-if="errors.end_date" class="mt-1 text-sm text-red-600">{{ errors.end_date }}</p>
          <p class="mt-1 text-sm text-gray-500">Campaign duration: {{ campaignDuration }} days</p>
        </div>
      </div>

      <!-- Overfunding Option -->
      <div class="form-group">
        <div class="flex items-start space-x-3">
          <input
            id="allow_overfunding"
            v-model="localData.allow_overfunding"
            type="checkbox"
            class="mt-1"
            @change="updateData"
          />
          <div>
            <label for="allow_overfunding" class="text-sm font-medium text-gray-700">
              Allow Overfunding
            </label>
            <p class="text-sm text-gray-500">
              Allow donations to continue after reaching the goal amount
            </p>
          </div>
        </div>
      </div>

      <!-- Goal Progress Visualization -->
      <div v-if="localData.goal_amount >= 100" class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Goal Visualization
        </label>
        <div class="bg-gray-100 rounded-lg p-4">
          <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium">Target: {{ formattedGoalAmount }}</span>
            <span class="text-sm text-gray-500">0% reached</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-green-500 h-2 rounded-full" style="width: 0%"></div>
          </div>
          <p class="text-xs text-gray-500 mt-2">
            This shows how your goal will appear to supporters
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';

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

// Local state
const localData = ref({ ...props.modelValue });

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  localData.value = { ...newValue };
}, { deep: true });

// Computed properties
const currencySymbol = computed(() => {
  const symbols = {
    EUR: '€',
    USD: '$',
    GBP: '£'
  };
  return symbols[localData.value.currency] || '€';
});

const minStartDate = computed(() => {
  const today = new Date();
  return today.toISOString().split('T')[0];
});

const minEndDate = computed(() => {
  if (localData.value.start_date) {
    const startDate = new Date(localData.value.start_date);
    startDate.setDate(startDate.getDate() + 1);
    return startDate.toISOString().split('T')[0];
  }
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  return tomorrow.toISOString().split('T')[0];
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

const formattedGoalAmount = computed(() => {
  if (localData.value.goal_amount) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: localData.value.currency || 'EUR'
    }).format(localData.value.goal_amount);
  }
  return '€0';
});

// Methods
const updateData = () => {
  emit('update:modelValue', localData.value);
};

// Validation
const isValid = computed(() => {
  return localData.value.goal_amount >= 100 && 
         localData.value.start_date && 
         localData.value.end_date &&
         new Date(localData.value.end_date) > new Date(localData.value.start_date);
});
</script>

<style scoped>
.form-input {
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
}

.form-input:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
  border-color: transparent;
}

.form-select {
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  background-color: white;
}

.form-select:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
  border-color: transparent;
}
</style>