/**
 * Avatar Synchronization Module
 * Handles real-time avatar updates across all components
 * PSR-12 compliant JavaScript following clean architecture principles
 */

class AvatarSyncManager {
    constructor() {
        this.currentUserId = null;
        this.init();
    }

    /**
     * Initialize the avatar sync manager
     */
    init() {
        // Get current user ID from meta tag if authenticated
        const userMeta = document.querySelector('meta[name="user-authenticated"]');
        if (userMeta) {
            this.currentUserId = this.getCurrentUserId();
            this.setupEventListeners();
        }
    }

    /**
     * Get current user ID from the page
     */
    getCurrentUserId() {
        // Try to get from data attribute or extract from avatar element
        const avatarElement = document.querySelector('[data-user-avatar]');
        if (avatarElement) {
            return avatarElement.dataset.userAvatar;
        }
        return null;
    }

    /**
     * Setup event listeners for avatar updates
     */
    setupEventListeners() {
        // Listen for successful avatar uploads
        window.addEventListener('current-user-avatar-updated', (event) => {
            this.handleAvatarUpdate(event.detail);
        });
    }

    /**
     * Handle avatar update event
     */
    handleAvatarUpdate(details) {
        const { photoUrl, userId } = details;
        
        // Update all avatar instances for the user
        this.updateAllAvatarInstances(userId || this.currentUserId, photoUrl);
        
        // Force update Alpine components
        this.forceUpdateAlpineAvatars(userId || this.currentUserId, photoUrl);
        
        // Notification is already shown by the user-avatar component, so we don't need to show it here
        // This prevents duplicate toast messages
        // this.showNotification('Avatar updated successfully', 'success');
    }
    
    /**
     * Force update all Alpine avatar components
     */
    forceUpdateAlpineAvatars(userId, photoUrl) {
        // Find all Alpine avatar components and update them
        document.querySelectorAll('[x-data*="userAvatarComponent"]').forEach(el => {
            if (el.__x && el.__x.$data) {
                // Check if this component is for the current user
                if (el.__x.$data.userId == userId) {
                    el.__x.$data.updateAvatar(photoUrl);
                }
            }
        });
    }

    /**
     * Update all avatar instances on the page
     */
    updateAllAvatarInstances(userId, photoUrl) {
        if (!userId) return;

        // Find all avatar elements for this user
        const avatarElements = document.querySelectorAll(`[data-user-avatar="${userId}"]`);
        
        avatarElements.forEach(element => {
            const img = element.querySelector('img');
            const initials = element.querySelector('span');
            
            if (photoUrl) {
                if (img) {
                    // Update existing image
                    img.src = photoUrl;
                    // Force browser to reload image
                    img.src = `${photoUrl}?t=${Date.now()}`;
                } else if (initials) {
                    // Replace initials with image
                    const newImg = document.createElement('img');
                    newImg.src = `${photoUrl}?t=${Date.now()}`;
                    newImg.alt = 'User Avatar';
                    newImg.className = 'w-full h-full object-cover';
                    initials.replaceWith(newImg);
                    
                    // Remove background color classes
                    element.classList.remove('bg-primary', 'text-white');
                }
            }
        });
    }

    /**
     * Show notification to user
     */
    showNotification(message, type = 'info') {
        // Check if global toast function exists
        if (typeof window.showToast === 'function') {
            window.showToast(type, message);
            return;
        }

        // Fallback: Create simple notification
        const notification = document.createElement('div');
        notification.className = `fixed top-20 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transition-all duration-300 ${
            type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' :
            type === 'error' ? 'bg-red-50 text-red-800 border border-red-200' :
            'bg-blue-50 text-blue-800 border border-blue-200'
        }`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    /**
     * Cleanup resources
     */
    destroy() {
        // Cleanup any resources if needed
    }
}

// Initialize avatar sync manager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.avatarSyncManager = new AvatarSyncManager();
    });
} else {
    window.avatarSyncManager = new AvatarSyncManager();
}

// Export for module usage
export default AvatarSyncManager;