<template>
  <div class="search-results">
    <!-- Search and Filter Header -->
    <div v-if="showFilters" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <div class="flex flex-col lg:flex-row lg:items-center gap-4">
        <!-- Search Input -->
        <div class="flex-1">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search campaigns..."
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              @input="debouncedSearch"
            />
          </div>
        </div>

        <!-- Category Filter -->
        <div class="flex-shrink-0">
          <select
            v-model="selectedCategory"
            @change="applyFilters"
            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md"
          >
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category" :value="category">
              {{ category }}
            </option>
          </select>
        </div>

        <!-- Status Filter -->
        <div class="flex-shrink-0">
          <select
            v-model="selectedStatus"
            @change="applyFilters"
            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md"
          >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="draft">Draft</option>
          </select>
        </div>

        <!-- Sort Options -->
        <div class="flex-shrink-0">
          <select
            v-model="sortBy"
            @change="applyFilters"
            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md"
          >
            <option value="created_at:desc">Newest First</option>
            <option value="created_at:asc">Oldest First</option>
            <option value="progress:desc">Most Progress</option>
            <option value="progress:asc">Least Progress</option>
            <option value="target_amount:desc">Highest Goal</option>
            <option value="target_amount:asc">Lowest Goal</option>
            <option value="title:asc">A-Z</option>
            <option value="title:desc">Z-A</option>
          </select>
        </div>
      </div>

      <!-- Active Filters Display -->
      <div v-if="hasActiveFilters" class="flex flex-wrap gap-2 mt-4">
        <span class="text-sm text-gray-600">Active filters:</span>
        <span
          v-if="searchQuery"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
        >
          Search: {{ searchQuery }}
          <button @click="clearSearch" class="ml-1.5 -mr-1 h-4 w-4 rounded-full inline-flex items-center justify-center text-blue-400 hover:bg-blue-200 hover:text-blue-500 focus:outline-none">
            <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
              <path stroke-linecap="round" stroke-width="1.5" d="m1 1 6 6m0-6-6 6" />
            </svg>
          </button>
        </span>
        <span
          v-if="selectedCategory"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
        >
          Category: {{ selectedCategory }}
          <button @click="clearCategory" class="ml-1.5 -mr-1 h-4 w-4 rounded-full inline-flex items-center justify-center text-green-400 hover:bg-green-200 hover:text-green-500 focus:outline-none">
            <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
              <path stroke-linecap="round" stroke-width="1.5" d="m1 1 6 6m0-6-6 6" />
            </svg>
          </button>
        </span>
        <span
          v-if="selectedStatus"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
        >
          Status: {{ selectedStatus }}
          <button @click="clearStatus" class="ml-1.5 -mr-1 h-4 w-4 rounded-full inline-flex items-center justify-center text-purple-400 hover:bg-purple-200 hover:text-purple-500 focus:outline-none">
            <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
              <path stroke-linecap="round" stroke-width="1.5" d="m1 1 6 6m0-6-6 6" />
            </svg>
          </button>
        </span>
        <button 
          @click="clearAllFilters"
          class="text-xs text-gray-500 hover:text-gray-700 underline"
        >
          Clear all
        </button>
      </div>
    </div>

    <!-- Results Header -->
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">
          {{ isSearching ? 'Search Results' : 'Campaigns' }}
        </h2>
        <p class="text-sm text-gray-600">
          {{ loading ? 'Searching...' : `${results.total} campaign${results.total !== 1 ? 's' : ''} found` }}
        </p>
      </div>
      
      <!-- View Toggle -->
      <div class="flex rounded-lg border border-gray-200">
        <button
          @click="viewMode = 'grid'"
          :class="[
            'px-3 py-1.5 text-sm font-medium rounded-l-lg',
            viewMode === 'grid' 
              ? 'bg-blue-50 text-blue-700 border-blue-200' 
              : 'bg-white text-gray-700 hover:bg-gray-50'
          ]"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
          </svg>
        </button>
        <button
          @click="viewMode = 'list'"
          :class="[
            'px-3 py-1.5 text-sm font-medium rounded-r-lg',
            viewMode === 'list' 
              ? 'bg-blue-50 text-blue-700 border-blue-200' 
              : 'bg-white text-gray-700 hover:bg-gray-50'
          ]"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="flex items-center space-x-2 text-gray-500">
        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Loading campaigns...</span>
      </div>
    </div>

    <!-- No Results -->
    <div v-else-if="results.campaigns.length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No campaigns found</h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ isSearching ? 'Try adjusting your search or filters' : 'No campaigns match your criteria' }}
      </p>
      <div class="mt-6">
        <button 
          @click="clearAllFilters"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          Clear filters
        </button>
      </div>
    </div>

    <!-- Results Grid/List -->
    <div v-else>
      <!-- Grid View -->
      <div 
        v-if="viewMode === 'grid'"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <CampaignCard
          v-for="campaign in results.campaigns"
          :key="`grid-${campaign.id}`"
          :campaign="campaign"
          @click="$emit('campaign-selected', campaign)"
        />
      </div>

      <!-- List View -->
      <div 
        v-else
        class="space-y-4"
      >
        <CampaignListItem
          v-for="campaign in results.campaigns"
          :key="`list-${campaign.id}`"
          :campaign="campaign"
          @click="$emit('campaign-selected', campaign)"
        />
      </div>

      <!-- Load More Button -->
      <div v-if="results.hasMore" class="flex justify-center mt-8">
        <button
          @click="loadMore"
          :disabled="loadingMore"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="loadingMore" class="animate-spin -ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ loadingMore ? 'Loading...' : 'Load More' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
// import { useCampaignStore } from '@/stores/campaign';
// import type { SearchResultsProps, SearchFilters, SearchResults, Campaign } from '@/types';

// Placeholder components - these would be separate components
// import CampaignCard from './CampaignCard.vue';
// import CampaignCard from '../../../Campaign/Infrastructure/Vue/Components/Display/CampaignCard.vue';
// import CampaignListItem from './CampaignListItem.vue';

// Props
const props = defineProps({
  initialFilters: {
    type: Object,
    default: () => ({})
  },
  showFilters: {
    type: Boolean,
    default: true
  },
  pageSize: {
    type: Number,
    default: 12
  }
});

// Stores
// const campaignStore = useCampaignStore();

// Reactive data
const searchQuery = ref(props.initialFilters.query || '');
const selectedCategory = ref(props.initialFilters.category || '');
const selectedStatus = ref(props.initialFilters.status || '');
const sortBy = ref('created_at:desc');
const viewMode = ref('grid');
const loading = ref(false);
const loadingMore = ref(false);
const results = ref({
  campaigns: [],
  total: 0,
  page: 1,
  perPage: props.pageSize,
  hasMore: false
});

const searchTimeout = ref(null);

// Sample categories - these would come from API
const categories = ref([
  'Health & Medical',
  'Education',
  'Environment',
  'Animals',
  'Community',
  'Emergency',
  'Sports',
  'Arts & Culture'
]);

// Computed
const isSearching = computed(() => {
  return !!(searchQuery.value || selectedCategory.value || selectedStatus.value);
});

const hasActiveFilters = computed(() => {
  return !!(searchQuery.value || selectedCategory.value || selectedStatus.value);
});

const currentFilters = computed((): SearchFilters => {
  const [sortField, sortOrder] = sortBy.value.split(':');
  
  return {
    query: searchQuery.value || undefined,
    category: selectedCategory.value || undefined,
    status: selectedStatus.value as Campaign['status'] || undefined,
    sortBy: sortField as any,
    sortOrder: sortOrder as 'asc' | 'desc'
  };
});

// Methods
const performSearch = async (resetResults: boolean = true): Promise<void> => {
  loading.value = resetResults;
  if (!resetResults) loadingMore.value = true;

  try {
    const searchResults = await campaignStore.searchCampaigns({
      ...currentFilters.value,
      page: resetResults ? 1 : results.value.page + 1,
      perPage: props.pageSize
    });

    if (resetResults) {
      results.value = searchResults;
    } else {
      // Append results for pagination
      results.value.campaigns.push(...searchResults.campaigns);
      results.value.page = searchResults.page;
      results.value.hasMore = searchResults.hasMore;
    }
  } catch (error) {
    console.error('Search failed:', error);
  } finally {
    loading.value = false;
    loadingMore.value = false;
  }
};

const debouncedSearch = (): void => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
  
  searchTimeout.value = setTimeout(() => {
    performSearch(true);
  }, 300);
};

const applyFilters = (): void => {
  performSearch(true);
};

const loadMore = (): void => {
  if (!results.value.hasMore || loadingMore.value) return;
  performSearch(false);
};

const clearSearch = (): void => {
  searchQuery.value = '';
  performSearch(true);
};

const clearCategory = (): void => {
  selectedCategory.value = '';
  performSearch(true);
};

const clearStatus = (): void => {
  selectedStatus.value = '';
  performSearch(true);
};

const clearAllFilters = (): void => {
  searchQuery.value = '';
  selectedCategory.value = '';
  selectedStatus.value = '';
  sortBy.value = 'created_at:desc';
  performSearch(true);
};

// Emits
const emit = defineEmits<{
  'campaign-selected': [campaign: Campaign];
  'filters-changed': [filters: SearchFilters];
}>();

// Watchers
watch(currentFilters, (newFilters) => {
  emit('filters-changed', newFilters);
}, { deep: true });

// Lifecycle
onMounted(() => {
  performSearch(true);
});
</script>

<style scoped>
/* Loading animation */
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
.search-results * {
  transition: all 0.2s ease;
}

/* Focus states for accessibility */
input:focus,
select:focus,
button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Responsive grid adjustments */
@media (max-width: 640px) {
  .grid-cols-1 {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 768px) {
  .md\:grid-cols-2 {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .lg\:grid-cols-3 {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>