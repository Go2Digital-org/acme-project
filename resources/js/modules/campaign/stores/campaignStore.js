import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useCampaignStore = defineStore('campaign', () => {
    // State
    const campaigns = ref([]);
    const currentCampaign = ref(null);
    const loading = ref(false);
    const error = ref(null);
    
    // Form state for wizard
    const formData = ref({
        title: '',
        description: '',
        category_id: null,
        goal_amount: 0,
        start_date: '',
        end_date: '',
        featured_image: null,
        status: 'draft'
    });
    
    // Getters
    const activeCampaigns = computed(() => 
        campaigns.value.filter(c => c.status === 'active')
    );
    
    const totalRaised = computed(() => 
        campaigns.value.reduce((sum, c) => sum + (c.current_amount || 0), 0)
    );
    
    // Actions
    async function fetchCampaigns() {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/campaigns');
            const data = await response.json();
            campaigns.value = data.data || [];
        } catch (err) {
            error.value = err.message;
            console.error('Error fetching campaigns:', err);
        } finally {
            loading.value = false;
        }
    }
    
    async function fetchCampaign(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/campaigns/${id}`);
            const data = await response.json();
            currentCampaign.value = data.data;
            return data.data;
        } catch (err) {
            error.value = err.message;
            console.error('Error fetching campaign:', err);
        } finally {
            loading.value = false;
        }
    }
    
    async function createCampaign(campaignData) {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/campaigns', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(campaignData)
            });
            
            if (!response.ok) {
                throw new Error('Failed to create campaign');
            }
            
            const data = await response.json();
            campaigns.value.push(data.data);
            return data.data;
        } catch (err) {
            error.value = err.message;
            console.error('Error creating campaign:', err);
            throw err;
        } finally {
            loading.value = false;
        }
    }
    
    async function updateCampaign(id, updates) {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/campaigns/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(updates)
            });
            
            if (!response.ok) {
                throw new Error('Failed to update campaign');
            }
            
            const data = await response.json();
            const index = campaigns.value.findIndex(c => c.id === id);
            if (index !== -1) {
                campaigns.value[index] = data.data;
            }
            if (currentCampaign.value?.id === id) {
                currentCampaign.value = data.data;
            }
            return data.data;
        } catch (err) {
            error.value = err.message;
            console.error('Error updating campaign:', err);
            throw err;
        } finally {
            loading.value = false;
        }
    }
    
    async function deleteCampaign(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/campaigns/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to delete campaign');
            }
            
            campaigns.value = campaigns.value.filter(c => c.id !== id);
            if (currentCampaign.value?.id === id) {
                currentCampaign.value = null;
            }
        } catch (err) {
            error.value = err.message;
            console.error('Error deleting campaign:', err);
            throw err;
        } finally {
            loading.value = false;
        }
    }
    
    function resetFormData() {
        formData.value = {
            title: '',
            description: '',
            category_id: null,
            goal_amount: 0,
            start_date: '',
            end_date: '',
            featured_image: null,
            status: 'draft'
        };
    }
    
    function updateFormData(updates) {
        formData.value = { ...formData.value, ...updates };
    }
    
    return {
        // State
        campaigns,
        currentCampaign,
        loading,
        error,
        formData,
        
        // Getters
        activeCampaigns,
        totalRaised,
        
        // Actions
        fetchCampaigns,
        fetchCampaign,
        createCampaign,
        updateCampaign,
        deleteCampaign,
        resetFormData,
        updateFormData
    };
});