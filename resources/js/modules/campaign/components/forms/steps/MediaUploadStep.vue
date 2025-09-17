<template>
  <div class="media-upload-step">
    <h3 class="text-lg font-semibold mb-4">Media & Images</h3>
    
    <div class="space-y-6">
      <!-- Featured Image Upload -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Featured Image
        </label>
        <div 
          class="upload-area"
          :class="{ 'drag-over': isDragging }"
          @drop="handleDrop"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
        >
          <div v-if="!previewUrl" class="upload-placeholder">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
              <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="mt-4">
              <label for="file-upload" class="cursor-pointer">
                <span class="text-primary hover:text-primary-dark">Upload a file</span>
                <input 
                  id="file-upload" 
                  type="file" 
                  class="sr-only" 
                  accept="image/*"
                  @change="handleFileSelect"
                >
              </label>
              <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
            </div>
            <p class="text-xs text-gray-500 mt-2">PNG, JPG, GIF up to 10MB</p>
          </div>
          
          <div v-else class="preview-container">
            <img :src="previewUrl" alt="Preview" class="preview-image">
            <button 
              type="button"
              @click="removeImage"
              class="remove-btn"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        <p v-if="errors.featured_image" class="mt-1 text-sm text-red-600">
          {{ errors.featured_image }}
        </p>
      </div>
      
      <!-- Additional Images -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Additional Images (Optional)
        </label>
        <p class="text-sm text-gray-500 mb-2">
          Add more images to showcase your campaign
        </p>
        <button 
          type="button"
          class="btn btn-outline"
          @click="$refs.additionalFiles.click()"
        >
          <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Images
        </button>
        <input 
          ref="additionalFiles"
          type="file" 
          class="hidden" 
          accept="image/*"
          multiple
          @change="handleAdditionalFiles"
        >
        
        <!-- Additional Images Preview -->
        <div v-if="additionalImages.length > 0" class="mt-4 grid grid-cols-3 gap-4">
          <div 
            v-for="(image, index) in additionalImages" 
            :key="index"
            class="relative group"
          >
            <img 
              :src="image.url" 
              alt="Additional image"
              class="w-full h-24 object-cover rounded"
            >
            <button 
              type="button"
              @click="removeAdditionalImage(index)"
              class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useCampaignStore } from '../../../Stores/campaignStore';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({})
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['update:modelValue']);

const store = useCampaignStore();
const isDragging = ref(false);
const previewUrl = ref(null);
const additionalImages = ref([]);
const selectedFile = ref(null);

// Initialize from props
if (props.modelValue.featured_image) {
  previewUrl.value = props.modelValue.featured_image;
}

const handleDrop = (e) => {
  e.preventDefault();
  isDragging.value = false;
  
  const files = e.dataTransfer.files;
  if (files.length > 0) {
    processFile(files[0]);
  }
};

const handleFileSelect = (e) => {
  const files = e.target.files;
  if (files.length > 0) {
    processFile(files[0]);
  }
};

const processFile = (file) => {
  if (!file.type.startsWith('image/')) {
    alert('Please select an image file');
    return;
  }
  
  if (file.size > 10 * 1024 * 1024) {
    alert('File size must be less than 10MB');
    return;
  }
  
  selectedFile.value = file;
  const reader = new FileReader();
  reader.onload = (e) => {
    previewUrl.value = e.target.result;
    updateValue();
  };
  reader.readAsDataURL(file);
};

const removeImage = () => {
  previewUrl.value = null;
  selectedFile.value = null;
  updateValue();
};

const handleAdditionalFiles = (e) => {
  const files = Array.from(e.target.files);
  
  files.forEach(file => {
    if (!file.type.startsWith('image/')) return;
    if (file.size > 10 * 1024 * 1024) return;
    
    const reader = new FileReader();
    reader.onload = (e) => {
      additionalImages.value.push({
        file: file,
        url: e.target.result
      });
    };
    reader.readAsDataURL(file);
  });
};

const removeAdditionalImage = (index) => {
  additionalImages.value.splice(index, 1);
};

const updateValue = () => {
  emit('update:modelValue', {
    ...props.modelValue,
    featured_image: selectedFile.value,
    featured_image_preview: previewUrl.value,
    additional_images: additionalImages.value.map(img => img.file)
  });
};

// Watch for external changes
watch(() => props.modelValue.featured_image, (newValue) => {
  if (newValue && typeof newValue === 'string') {
    previewUrl.value = newValue;
  }
});
</script>

<style scoped>
.upload-area {
  border: 2px dashed #d1d5db;
  border-radius: 0.5rem;
  padding: 2rem;
  text-align: center;
  transition: border-color 0.2s;
}

.upload-area.drag-over {
  border-color: #3b82f6;
  background-color: #eff6ff;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.preview-container {
  position: relative;
}

.preview-image {
  max-width: 100%;
  max-height: 300px;
  margin: 0 auto;
  display: block;
  border-radius: 0.5rem;
}

.remove-btn {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  background-color: #ef4444;
  color: white;
  border-radius: 9999px;
  padding: 0.25rem;
  transition: background-color 0.2s;
}

.remove-btn:hover {
  background-color: #dc2626;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: background-color 0.2s, color 0.2s;
  display: inline-flex;
  align-items: center;
}

.btn-outline {
  border: 1px solid #d1d5db;
  color: #374151;
  background-color: white;
}

.btn-outline:hover {
  background-color: #f9fafb;
}
</style>