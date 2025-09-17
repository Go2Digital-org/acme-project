import Alpine from 'alpinejs';
import gsap from 'gsap';
import { searchAutocomplete } from './components/search-autocomplete';
import { keyboardShortcuts } from './components/keyboard-shortcuts';

// Make GSAP available globally before Alpine starts
window.gsap = gsap;

// Make Alpine available globally
window.Alpine = Alpine;

// Register search autocomplete component
Alpine.data('searchAutocomplete', searchAutocomplete);

// Register keyboard shortcuts component
Alpine.data('keyboardShortcuts', keyboardShortcuts);

// Alpine.js components for simple interactions
Alpine.data('campaignCard', () => ({
    showDetails: false,
    isBookmarked: false,
    
    toggleDetails() {
        this.showDetails = !this.showDetails;
    },
    
    async toggleBookmark() {
        this.isBookmarked = !this.isBookmarked;
        // TODO: Make API call to save bookmark state
        try {
            await window.axios.post(`/api/campaigns/${this.campaignId}/bookmark`, {
                bookmarked: this.isBookmarked
            });
        } catch (error) {
            // Revert on error
            this.isBookmarked = !this.isBookmarked;
            console.error('Failed to update bookmark:', error);
        }
    }
}));

Alpine.data('shareModal', () => ({
    isOpen: false,
    shareUrl: '',
    copied: false,
    
    open(url) {
        this.shareUrl = url;
        this.isOpen = true;
    },
    
    close() {
        this.isOpen = false;
        this.copied = false;
    },
    
    async copyUrl() {
        try {
            await navigator.clipboard.writeText(this.shareUrl);
            this.copied = true;
            setTimeout(() => {
                this.copied = false;
            }, 2000);
        } catch (error) {
            console.error('Failed to copy URL:', error);
        }
    },
    
    async share() {
        if (navigator.share) {
            try {
                await navigator.share({
                    title: 'Support this campaign',
                    url: this.shareUrl
                });
            } catch (error) {
                // User cancelled or error occurred
                console.log('Share cancelled or failed');
            }
        } else {
            this.copyUrl();
        }
    }
}));

Alpine.data('mobileMenu', () => ({
    isOpen: false,
    
    toggle() {
        this.isOpen = !this.isOpen;
    },
    
    close() {
        this.isOpen = false;
    }
}));

// Start Alpine.js
Alpine.start();