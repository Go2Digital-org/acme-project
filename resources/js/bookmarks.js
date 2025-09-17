/**
 * Bookmark functionality for campaigns
 * Handles favoriting, sharing, and local storage for offline tracking
 */

// Configuration
const CONFIG = {
    apiBaseUrl: '/api',
    storageKey: 'acme_bookmarks',
    toastDuration: 3000,
    animationDuration: 300
};

// State management
const BookmarkState = {
    bookmarks: new Set(),
    initialized: false,

    init() {
        if (this.initialized) return;
        this.loadFromStorage();
        this.initialized = true;
    },

    loadFromStorage() {
        try {
            const stored = localStorage.getItem(CONFIG.storageKey);
            if (stored) {
                this.bookmarks = new Set(JSON.parse(stored));
            }
        } catch (error) {
            console.error('Error loading bookmarks from storage:', error);
            this.bookmarks = new Set();
        }
    },

    saveToStorage() {
        try {
            localStorage.setItem(CONFIG.storageKey, JSON.stringify([...this.bookmarks]));
        } catch (error) {
            console.error('Error saving bookmarks to storage:', error);
        }
    },

    add(campaignId) {
        this.bookmarks.add(String(campaignId));
        this.saveToStorage();
    },

    remove(campaignId) {
        this.bookmarks.delete(String(campaignId));
        this.saveToStorage();
    },

    has(campaignId) {
        return this.bookmarks.has(String(campaignId));
    }
};

// Utility functions
function getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]');
    return token ? token.getAttribute('content') : null;
}

export function showToast(message, type = 'success') {
    // Define icons for different toast types
    const icons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-triangle',
        'warning': 'fas fa-exclamation-circle',
        'info': 'fas fa-info-circle'
    };

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `bookmark-toast ${type} enter`;
    
    // Create icon element
    const icon = document.createElement('i');
    icon.className = `toast-icon ${icons[type] || icons.info}`;
    
    // Create message element
    const messageElement = document.createElement('span');
    messageElement.className = 'toast-message';
    messageElement.textContent = message;
    
    // Assemble toast
    toast.appendChild(icon);
    toast.appendChild(messageElement);

    document.body.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        toast.classList.remove('enter');
        toast.classList.add('show');
    });

    // Remove after duration
    setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.add('enter');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, CONFIG.animationDuration);
    }, CONFIG.toastDuration);
}

function updateBookmarkButton(campaignId, isBookmarked) {
    const buttons = document.querySelectorAll(`[data-campaign-id="${campaignId}"]`);
    
    buttons.forEach(button => {
        const icon = button.querySelector('i');
        if (!icon) return;

        // Update icon classes
        if (isBookmarked) {
            icon.classList.remove('far', 'fa-bookmark');
            icon.classList.add('fas', 'fa-bookmark');
            button.classList.add('text-red-500');
            button.classList.remove('text-gray-600', 'dark:text-gray-400');
            button.setAttribute('title', 'Remove from favorites');
            button.setAttribute('aria-label', 'Remove from favorites');
        } else {
            icon.classList.remove('fas', 'fa-bookmark');
            icon.classList.add('far', 'fa-bookmark');
            button.classList.remove('text-red-500');
            button.classList.add('text-gray-600', 'dark:text-gray-400');
            button.setAttribute('title', 'Add to favorites');
            button.setAttribute('aria-label', 'Add to favorites');
        }

        // Add animation
        button.classList.add('bookmark-pulse');
        setTimeout(() => {
            button.classList.remove('bookmark-pulse');
        }, 300);
    });
}

function setButtonLoading(campaignId, isLoading) {
    const buttons = document.querySelectorAll(`[data-campaign-id="${campaignId}"]`);
    
    buttons.forEach(button => {
        if (isLoading) {
            button.disabled = true;
            button.classList.add('loading');
            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.add('fa-spin');
            }
        } else {
            button.disabled = false;
            button.classList.remove('loading');
            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-spin');
            }
        }
    });
}

async function makeApiRequest(url, options = {}) {
    const csrfToken = getCsrfToken();
    
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/ld+json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken })
        }
    };

    const response = await fetch(url, {
        ...defaultOptions,
        ...options,
        credentials: 'include', // Changed from 'same-origin' to 'include' for better cross-domain support
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    });

    if (response.status === 401) {
        console.warn('Authentication failed for API request:', url);
        showToast('Please log in to use this feature', 'error');
        
        // Check if we're already on login page to avoid redirect loop
        if (!window.location.pathname.includes('/login')) {
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
        }
        throw new Error('Unauthorized');
    }

    if (response.status === 419) {
        console.warn('CSRF token mismatch, retrying with fresh token');
        throw new Error('CSRF token mismatch');
    }

    if (!response.ok) {
        let errorData = {};
        try {
            const responseText = await response.text();
            if (responseText) {
                errorData = JSON.parse(responseText);
            }
        } catch (parseError) {
            console.warn('Could not parse error response:', parseError);
        }
        
        const errorMessage = errorData.message || errorData.detail || `HTTP error! status: ${response.status}`;
        console.error('API request failed:', { url, status: response.status, error: errorData });
        throw new Error(errorMessage);
    }

    return response.json();
}

// Main functions
export async function toggleFavorite(campaignId, retryCount = 0) {
    if (!campaignId) {
        console.error('Campaign ID is required');
        return;
    }

    BookmarkState.init();
    
    const isCurrentlyBookmarked = BookmarkState.has(campaignId);
    setButtonLoading(campaignId, true);

    try {
        // Ensure CSRF cookie is set for Sanctum authentication
        const csrfResponse = await fetch('/sanctum/csrf-cookie', {
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!csrfResponse.ok) {
            console.warn('Failed to fetch CSRF cookie:', csrfResponse.status);
        }
        
        // Small delay to ensure cookie is set
        await new Promise(resolve => setTimeout(resolve, 100));
        
        if (isCurrentlyBookmarked) {
            // Remove bookmark
            await makeApiRequest(`${CONFIG.apiBaseUrl}/campaigns/${campaignId}/favorite`, {
                method: 'DELETE'
            });
            
            BookmarkState.remove(campaignId);
            updateBookmarkButton(campaignId, false);
            showToast('Removed from favorites');
        } else {
            // Add bookmark
            await makeApiRequest(`${CONFIG.apiBaseUrl}/campaigns/${campaignId}/favorite`, {
                method: 'POST',
                body: JSON.stringify({})
            });
            
            BookmarkState.add(campaignId);
            updateBookmarkButton(campaignId, true);
            showToast('Added to favorites');
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
        
        // Retry once on CSRF token mismatch
        if (error.message.includes('CSRF token mismatch') && retryCount < 1) {
            setButtonLoading(campaignId, false);
            return toggleFavorite(campaignId, retryCount + 1);
        }
        
        // Show specific error messages for different scenarios
        if (error.message.includes('Unauthorized')) {
            showToast('Please log in to use favorites', 'error');
        } else if (error.message.includes('CSRF')) {
            showToast('Security token expired, please refresh the page', 'error');
        } else {
            // Fallback to local storage only for other errors
            if (isCurrentlyBookmarked) {
                BookmarkState.remove(campaignId);
                updateBookmarkButton(campaignId, false);
                showToast('Removed from favorites (offline)', 'warning');
            } else {
                BookmarkState.add(campaignId);
                updateBookmarkButton(campaignId, true);
                showToast('Added to favorites (offline)', 'warning');
            }
        }
    } finally {
        setButtonLoading(campaignId, false);
    }
}

export async function shareCampaign(campaignId) {
    if (!campaignId) {
        console.error('Campaign ID is required');
        return;
    }

    const campaignUrl = `${window.location.origin}/campaigns/${campaignId}`;
    
    // Get campaign title from the page if possible
    const titleElement = document.querySelector(`#campaign-title-${campaignId}`);
    const campaignTitle = titleElement ? titleElement.textContent.trim() : 'Support this campaign';
    
    const shareData = {
        title: campaignTitle,
        text: `Help make a difference by supporting this charitable campaign: ${campaignTitle}`,
        url: campaignUrl
    };

    try {
        // Try native Web Share API first
        if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
            await navigator.share(shareData);
            showToast('Campaign shared successfully');
            return;
        }
    } catch (error) {
        if (error.name !== 'AbortError') {
            console.error('Error sharing via Web Share API:', error);
        }
        // Fall through to clipboard fallback
    }

    // Fallback to clipboard
    try {
        await navigator.clipboard.writeText(campaignUrl);
        showToast('Campaign link copied to clipboard!');
    } catch (error) {
        console.error('Error copying to clipboard:', error);
        
        // Final fallback - create temporary textarea
        try {
            const textArea = document.createElement('textarea');
            textArea.value = campaignUrl;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);
            
            if (successful) {
                showToast('Campaign link copied to clipboard!');
            } else {
                throw new Error('Copy command failed');
            }
        } catch (fallbackError) {
            console.error('All share methods failed:', fallbackError);
            showToast('Unable to share. Please copy the URL manually.', 'error');
        }
    }
}

// Initialize bookmark states on page load
export function initializeBookmarkStates() {
    BookmarkState.init();
    
    // Find all bookmark buttons and update their states
    const buttons = document.querySelectorAll('[data-campaign-id]');
    buttons.forEach(button => {
        const campaignId = button.getAttribute('data-campaign-id');
        if (campaignId && BookmarkState.has(campaignId)) {
            updateBookmarkButton(campaignId, true);
        }
    });
}

// Initialize module
function init() {
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeBookmarkStates);
    } else {
        initializeBookmarkStates();
    }

    // Export functions to window for backward compatibility
    window.toggleFavorite = toggleFavorite;
    window.shareCampaign = shareCampaign;
    window.showToast = showToast;
    window.initializeBookmarks = initializeBookmarkStates;

    // Export for debugging and testing
    if (window.location.hostname === 'localhost' || window.location.hostname.includes('dev') || window.location.hostname.includes('test')) {
        window.BookmarkState = BookmarkState;
        
        // Add toast testing function for development
        window.testToasts = function() {
            showToast('Success: Added to favorites!', 'success');
            setTimeout(() => showToast('Warning: Working in offline mode', 'warning'), 1000);
            setTimeout(() => showToast('Error: Authentication required', 'error'), 2000);
            setTimeout(() => showToast('Info: Feature temporarily disabled', 'info'), 3000);
        };
    }
}

// Initialize the module
init();

// Export for ES6 modules
export default {
    showToast,
    toggleFavorite,
    shareCampaign,
    initializeBookmarkStates,
    BookmarkState
};