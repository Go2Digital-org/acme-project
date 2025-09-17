<template>
  <div class="step-basic-info">
    <h2 class="text-2xl font-bold mb-6">Campaign Basic Information</h2>
    
    <div class="space-y-6">
      <!-- Title -->
      <div class="form-group">
        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
          Campaign Title <span class="text-red-500">*</span>
        </label>
        <input
          id="title"
          v-model="localData.title"
          type="text"
          class="form-input w-full"
          :class="{ 'border-red-500': errors.title }"
          placeholder="Enter a compelling campaign title"
          maxlength="100"
          @input="updateData"
        />
        <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
        <p class="mt-1 text-sm text-gray-500">{{ localData.title.length }}/100 characters</p>
      </div>

      <!-- Description -->
      <div class="form-group">
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
          Short Description <span class="text-red-500">*</span>
        </label>
        <textarea
          id="description"
          v-model="localData.description"
          rows="3"
          class="form-input w-full"
          :class="{ 'border-red-500': errors.description }"
          placeholder="Brief description that will appear in campaign listings"
          maxlength="500"
          @input="updateData"
        ></textarea>
        <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
        <p class="mt-1 text-sm text-gray-500">{{ localData.description.length }}/500 characters</p>
      </div>

      <!-- Long Description -->
      <div class="form-group">
        <label for="long_description" class="block text-sm font-medium text-gray-700 mb-1">
          Detailed Description
        </label>
        <textarea
          id="long_description"
          v-model="localData.long_description"
          rows="6"
          class="form-input w-full"
          placeholder="Provide detailed information about your campaign, its goals, and impact"
          @input="updateData"
        ></textarea>
        <p class="mt-1 text-sm text-gray-500">
          Use this space to tell your campaign's story in detail
        </p>
      </div>

      <!-- Category and Organization -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Category -->
        <div class="form-group">
          <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
            Category <span class="text-red-500">*</span>
          </label>
          <select
            id="category_id"
            v-model="localData.category_id"
            class="form-select w-full"
            :class="{ 'border-red-500': errors.category_id }"
            @change="updateData"
          >
            <option value="">Select a category</option>
            <option 
              v-for="category in categories" 
              :key="category.id"
              :value="category.id"
            >
              {{ category.name }}
            </option>
          </select>
          <p v-if="errors.category_id" class="mt-1 text-sm text-red-600">{{ errors.category_id }}</p>
        </div>

        <!-- Organization -->
        <div class="form-group">
          <label for="organization_id" class="block text-sm font-medium text-gray-700 mb-1">
            Organization <span class="text-red-500">*</span>
          </label>
          <select
            id="organization_id"
            v-model="localData.organization_id"
            class="form-select w-full"
            :class="{ 'border-red-500': errors.organization_id }"
            @change="updateData"
          >
            <option value="">Select an organization</option>
            <option 
              v-for="org in organizations" 
              :key="org.id"
              :value="org.id"
            >
              {{ org.name }}
            </option>
          </select>
          <p v-if="errors.organization_id" class="mt-1 text-sm text-red-600">{{ errors.organization_id }}</p>
        </div>
      </div>

      <!-- Tags -->
      <div class="form-group">
        <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">
          Tags
        </label>
        <div class="flex items-center space-x-2">
          <input
            id="tags"
            v-model="tagInput"
            type="text"
            class="form-input flex-1"
            placeholder="Add tags (press Enter to add)"
            @keydown.enter.prevent="addTag"
          />
          <button
            type="button"
            @click="addTag"
            class="btn btn-outline px-4 py-2"
          >
            Add Tag
          </button>
        </div>
        <div v-if="localData.tags.length > 0" class="mt-2 flex flex-wrap gap-2">
          <span
            v-for="(tag, index) in localData.tags"
            :key="index"
            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800"
          >
            {{ tag }}
            <button
              type="button"
              @click="removeTag(index)"
              class="ml-2 text-blue-600 hover:text-blue-800"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </span>
        </div>
        <p class="mt-1 text-sm text-gray-500">
          Tags help users discover your campaign
        </p>
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
const tagInput = ref('');

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  localData.value = { ...newValue };
}, { deep: true });

// Methods
const updateData = () => {
  emit('update:modelValue', localData.value);
};

const addTag = () => {
  if (tagInput.value.trim()) {
    if (!localData.value.tags) {
      localData.value.tags = [];
    }
    
    const tag = tagInput.value.trim().toLowerCase();
    if (!localData.value.tags.includes(tag)) {
      localData.value.tags.push(tag);
      updateData();
    }
    tagInput.value = '';
  }
};

const removeTag = (index) => {
  localData.value.tags.splice(index, 1);
  updateData();
};

// Validation
const isValid = computed(() => {
  return localData.value.title && 
         localData.value.description && 
         localData.value.category_id && 
         localData.value.organization_id;
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

.btn-outline {
  border: 1px solid #d1d5db;
  color: #374151;
  border-radius: 0.5rem;
  transition: colors;
}

.btn-outline:hover {
  background-color: #f9fafb;
}
</style>