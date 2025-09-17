import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { 
  CampaignState, 
  Campaign, 
  SearchFilters, 
  SearchResults, 
  ApiResponse, 
  PaginatedResponse 
} from '@/types';

export const useCampaignStore = defineStore('campaign', () => {
  // State
  const campaigns = ref<Campaign[]>([]);
  const currentCampaign = ref<Campaign | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Getters
  const activeCampaigns = computed(() => 
    campaigns.value.filter(c => c.status === 'active')
  );

  const completedCampaigns = computed(() =>
    campaigns.value.filter(c => c.status === 'completed')
  );

  const totalRaised = computed(() =>
    campaigns.value.reduce((sum, campaign) => sum + campaign.currentAmount, 0)
  );

  // Actions
  const fetchCampaigns = async (filters?: SearchFilters): Promise<SearchResults> => {
    loading.value = true;
    error.value = null;

    try {
      const response = await window.axios.get<PaginatedResponse<Campaign>>('/api/campaigns', {
        params: filters
      });

      campaigns.value = response.data.data;

      return {
        campaigns: response.data.data,
        total: response.data.meta.total,
        page: response.data.meta.current_page,
        perPage: response.data.meta.per_page,
        hasMore: response.data.meta.current_page < response.data.meta.last_page
      };
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch campaigns';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchCampaign = async (id: number): Promise<Campaign> => {
    loading.value = true;
    error.value = null;

    try {
      const response = await window.axios.get<ApiResponse<Campaign>>(`/api/campaigns/${id}`);
      currentCampaign.value = response.data.data;
      
      // Update in campaigns array if it exists
      const index = campaigns.value.findIndex(c => c.id === id);
      if (index !== -1) {
        campaigns.value[index] = response.data.data;
      }

      return response.data.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch campaign';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const updateCampaignProgress = (campaignId: number, progress: number, currentAmount: number) => {
    // Update in campaigns array
    const campaignIndex = campaigns.value.findIndex(c => c.id === campaignId);
    if (campaignIndex !== -1) {
      campaigns.value[campaignIndex] = {
        ...campaigns.value[campaignIndex],
        progress,
        currentAmount,
        donationsCount: campaigns.value[campaignIndex].donationsCount + 1
      };
    }

    // Update current campaign if it matches
    if (currentCampaign.value && currentCampaign.value.id === campaignId) {
      currentCampaign.value = {
        ...currentCampaign.value,
        progress,
        currentAmount,
        donationsCount: currentCampaign.value.donationsCount + 1
      };
    }
  };

  const searchCampaigns = async (filters: SearchFilters): Promise<SearchResults> => {
    loading.value = true;
    error.value = null;

    try {
      const response = await window.axios.get<PaginatedResponse<Campaign>>('/api/campaigns/search', {
        params: filters
      });

      return {
        campaigns: response.data.data,
        total: response.data.meta.total,
        page: response.data.meta.current_page,
        perPage: response.data.meta.per_page,
        hasMore: response.data.meta.current_page < response.data.meta.last_page
      };
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Search failed';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const bookmarkCampaign = async (campaignId: number): Promise<void> => {
    try {
      await window.axios.post(`/api/campaigns/${campaignId}/bookmark`);
      
      // Update local state - this could be enhanced to track bookmark status
      const campaign = campaigns.value.find(c => c.id === campaignId);
      if (campaign) {
        // Add bookmark property to Campaign type if needed
        // campaign.isBookmarked = !campaign.isBookmarked;
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to bookmark campaign';
      throw err;
    }
  };

  const clearError = () => {
    error.value = null;
  };

  const reset = () => {
    campaigns.value = [];
    currentCampaign.value = null;
    loading.value = false;
    error.value = null;
  };

  return {
    // State
    campaigns,
    currentCampaign,
    loading,
    error,

    // Getters
    activeCampaigns,
    completedCampaigns,
    totalRaised,

    // Actions
    fetchCampaigns,
    fetchCampaign,
    updateCampaignProgress,
    searchCampaigns,
    bookmarkCampaign,
    clearError,
    reset
  };
});