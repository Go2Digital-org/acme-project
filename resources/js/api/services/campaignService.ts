import { api } from '../client';
import type { 
  Campaign, 
  SearchFilters, 
  SearchResults, 
  PaginatedResponse 
} from '@/types';

export class CampaignService {
  /**
   * Fetch campaigns with optional filters and pagination
   */
  static async getCampaigns(filters?: SearchFilters & { page?: number; perPage?: number }): Promise<SearchResults> {
    const response = await api.get<PaginatedResponse<Campaign>>('/campaigns', filters);
    
    return {
      campaigns: response.data,
      total: response.meta.total,
      page: response.meta.current_page,
      perPage: response.meta.per_page,
      hasMore: response.meta.current_page < response.meta.last_page
    };
  }

  /**
   * Get a single campaign by ID
   */
  static async getCampaign(id: number): Promise<Campaign> {
    return api.get<Campaign>(`/campaigns/${id}`);
  }

  /**
   * Create a new campaign
   */
  static async createCampaign(campaignData: Partial<Campaign>): Promise<Campaign> {
    return api.post<Campaign>('/campaigns', campaignData);
  }

  /**
   * Update an existing campaign
   */
  static async updateCampaign(id: number, campaignData: Partial<Campaign>): Promise<Campaign> {
    return api.patch<Campaign>(`/campaigns/${id}`, campaignData);
  }

  /**
   * Delete a campaign
   */
  static async deleteCampaign(id: number): Promise<void> {
    return api.delete(`/campaigns/${id}`);
  }

  /**
   * Get campaign statistics
   */
  static async getCampaignStats(id: number): Promise<{
    totalDonations: number;
    uniqueDonors: number;
    averageDonation: number;
    recentDonations: any[];
    dailyProgress: { date: string; amount: number }[];
  }> {
    return api.get(`/campaigns/${id}/stats`);
  }

  /**
   * Upload campaign image
   */
  static async uploadCampaignImage(
    campaignId: number, 
    file: File, 
    onProgress?: (progress: number) => void
  ): Promise<{ imageUrl: string }> {
    return api.upload(`/campaigns/${campaignId}/image`, file, onProgress);
  }

  /**
   * Get campaign categories
   */
  static async getCategories(): Promise<{ id: string; name: string; count: number }[]> {
    return api.get('/campaigns/categories');
  }

  /**
   * Search campaigns with advanced filters
   */
  static async searchCampaigns(query: string, filters?: SearchFilters): Promise<SearchResults> {
    return this.getCampaigns({ ...filters, query });
  }

  /**
   * Get featured campaigns
   */
  static async getFeaturedCampaigns(limit: number = 6): Promise<Campaign[]> {
    return api.get('/campaigns/featured', { limit });
  }

  /**
   * Get campaigns by organization
   */
  static async getCampaignsByOrganization(organizationId: number): Promise<Campaign[]> {
    return api.get(`/organizations/${organizationId}/campaigns`);
  }

  /**
   * Get trending campaigns
   */
  static async getTrendingCampaigns(limit: number = 10): Promise<Campaign[]> {
    return api.get('/campaigns/trending', { limit });
  }

  /**
   * Report a campaign
   */
  static async reportCampaign(campaignId: number, reason: string, description?: string): Promise<void> {
    return api.post(`/campaigns/${campaignId}/report`, {
      reason,
      description
    });
  }

  /**
   * Subscribe to campaign updates (real-time)
   */
  static subscribeToCampaignUpdates(campaignId: number, callback: (update: any) => void): () => void {
    // This would implement WebSocket or Server-Sent Events
    // For now, we'll use polling
    const interval = setInterval(async () => {
      try {
        const campaign = await this.getCampaign(campaignId);
        callback({ type: 'campaign_update', data: campaign });
      } catch (error) {
        console.warn('Failed to fetch campaign update:', error);
      }
    }, 30000); // Poll every 30 seconds

    // Return unsubscribe function
    return () => clearInterval(interval);
  }
}