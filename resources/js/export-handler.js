/**
 * Export Handler - Manages export requests, progress polling, and notifications
 */
class ExportHandler {
    constructor() {
        this.activeExports = new Map();
        this.completedExports = new Set(); // Track exports that have already shown completion notification
        this.pollInterval = 2000; // 2 seconds
        this.maxRetries = 3;
        this.initializeNotifications();
        this.bindEvents();
    }

    /**
     * Initialize browser notifications
     */
    initializeNotifications() {
        // Request notification permission if not already granted
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    /**
     * Bind global events
     */
    bindEvents() {
        // Listen for export progress events
        document.addEventListener('show-export-progress', (event) => {
            this.showProgressModal(event.detail.exportId, event.detail.exportType);
        });

        // Listen for export start events
        document.addEventListener('start-export', (event) => {
            this.startExport(event.detail.exportType, event.detail.filters || {});
        });

        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
    }

    /**
     * Start a new export
     */
    async startExport(exportType, filters = {}) {
        try {
            const response = await fetch('/api/exports', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    type: exportType,
                    filters: filters,
                    format: filters.format || 'csv'
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || errorData.message || `HTTP ${response.status}`);
            }

            const exportData = await response.json();
            
            // Show progress modal immediately
            this.showProgressModal(exportData.id, exportType);
            
            // Start polling for this export
            this.startPolling(exportData.id);
            
            this.showNotification('Export started successfully', 'info');
            
            // Refresh exports table when export starts successfully
            document.dispatchEvent(new CustomEvent('refresh-exports-table'));
            
            return exportData;

        } catch (error) {
            console.error('Error starting export:', error);
            this.showNotification(error.message, 'error');
            throw error;
        }
    }

    /**
     * Show progress modal
     */
    showProgressModal(exportId, exportType) {
        // Dispatch Alpine.js event to show modal
        window.dispatchEvent(new CustomEvent('alpine:init-export-modal', {
            detail: { exportId, exportType, show: true }
        }));
    }

    /**
     * Start polling for export progress
     */
    startPolling(exportId) {
        if (this.activeExports.has(exportId)) {
            return; // Already polling
        }

        // Clear any previous completion notification for this export (in case of retry)
        this.completedExports.delete(exportId);

        const pollData = {
            id: exportId,
            retryCount: 0,
            interval: null
        };

        pollData.interval = setInterval(async () => {
            try {
                const progress = await this.fetchProgress(exportId);
                
                // Dispatch progress update event
                document.dispatchEvent(new CustomEvent('export-progress-update', {
                    detail: { exportId, progress }
                }));

                // Stop polling if export is complete
                if (['completed', 'failed', 'cancelled'].includes(progress.status)) {
                    this.stopPolling(exportId);
                    this.handleExportComplete(exportId, progress);
                }

                // Reset retry count on successful request
                pollData.retryCount = 0;

            } catch (error) {
                console.error(`Error polling export ${exportId}:`, error);
                
                pollData.retryCount++;
                
                if (pollData.retryCount >= this.maxRetries) {
                    this.stopPolling(exportId);
                    this.handlePollingError(exportId, error);
                }
            }
        }, this.pollInterval);

        this.activeExports.set(exportId, pollData);
    }

    /**
     * Stop polling for specific export
     */
    stopPolling(exportId) {
        const pollData = this.activeExports.get(exportId);
        if (pollData && pollData.interval) {
            clearInterval(pollData.interval);
            this.activeExports.delete(exportId);
        }
    }

    /**
     * Fetch progress for specific export
     */
    async fetchProgress(exportId) {
        const response = await fetch(`/api/exports/${exportId}/progress`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return await response.json();
    }

    /**
     * Handle export completion
     */
    handleExportComplete(exportId, progress) {
        // Check if we've already shown a notification for this export
        if (this.completedExports.has(exportId)) {
            return; // Already handled this completion
        }
        
        switch (progress.status) {
            case 'completed':
                this.completedExports.add(exportId); // Mark as notified
                this.showNotification('Export completed successfully!', 'success');
                this.showBrowserNotification('Export Complete', 'Your export is ready for download');
                break;
                
            case 'failed':
                this.completedExports.add(exportId); // Mark as notified
                this.showNotification(
                    `Export failed: ${progress.error_message || 'Unknown error'}`,
                    'error'
                );
                this.showBrowserNotification('Export Failed', 'There was an error processing your export');
                break;
                
            case 'cancelled':
                this.completedExports.add(exportId); // Mark as notified
                this.showNotification('Export was cancelled', 'info');
                break;
        }

        // Refresh exports table if it exists
        document.dispatchEvent(new CustomEvent('refresh-exports-table'));
    }

    /**
     * Handle polling errors
     */
    handlePollingError(exportId, error) {
        console.error(`Polling failed for export ${exportId}:`, error);
        this.showNotification(
            'Lost connection to export progress. Please refresh the page to check status.',
            'warning'
        );
    }

    /**
     * Cancel export
     */
    async cancelExport(exportId) {
        try {
            const response = await fetch(`/api/exports/${exportId}/cancel`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || errorData.message || `HTTP ${response.status}`);
            }

            this.stopPolling(exportId);
            this.showNotification('Export cancelled successfully', 'info');
            
            return true;

        } catch (error) {
            console.error('Error cancelling export:', error);
            this.showNotification(`Failed to cancel export: ${error.message}`, 'error');
            return false;
        }
    }

    /**
     * Download export
     */
    async downloadExport(exportId, filename) {
        try {
            const response = await fetch(`/api/exports/${exportId}/download`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || errorData.message || `HTTP ${response.status}`);
            }

            // Create blob and download
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename || `export-${exportId}.csv`;
            
            // Trigger download
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Clean up blob URL
            window.URL.revokeObjectURL(url);
            
            this.showNotification('Download started', 'success');
            
            return true;

        } catch (error) {
            console.error('Error downloading export:', error);
            this.showNotification(`Failed to download export: ${error.message}`, 'error');
            return false;
        }
    }

    /**
     * Show toast notification
     */
    showNotification(message, type = 'info') {
        if (window.toast) {
            // Use the global toast helper
            switch(type) {
                case 'success':
                    window.toast.success(message);
                    break;
                case 'error':
                    window.toast.error(message);
                    break;
                case 'warning':
                    window.toast.warning(message);
                    break;
                default:
                    window.toast.info(message);
            }
        } else if (window.Swal) {
            // Fallback to direct Swal usage
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: type === 'error' ? 5000 : 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });

            const icons = {
                success: 'success',
                error: 'error',
                warning: 'warning',
                info: 'info'
            };

            Toast.fire({
                icon: icons[type] || 'info',
                title: message
            });
        } else {
            // Fallback to console if nothing is available
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }

    /**
     * Show browser notification
     */
    showBrowserNotification(title, body) {
        if ('Notification' in window && Notification.permission === 'granted') {
            const notification = new Notification(title, {
                body: body,
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                tag: 'export-notification',
                requireInteraction: false
            });

            // Auto-close after 5 seconds
            setTimeout(() => {
                notification.close();
            }, 5000);

            // Handle click to focus window
            notification.onclick = function() {
                window.focus();
                notification.close();
            };
        }
    }

    /**
     * Get all active exports
     */
    getActiveExports() {
        return Array.from(this.activeExports.keys());
    }

    /**
     * Stop all polling and cleanup
     */
    cleanup() {
        this.activeExports.forEach((pollData, exportId) => {
            this.stopPolling(exportId);
        });
        this.activeExports.clear();
        this.completedExports.clear(); // Clear completed exports tracking
    }

    /**
     * Utility method to format file size
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    /**
     * Utility method to format duration
     */
    formatDuration(seconds) {
        if (seconds < 60) {
            return `${Math.round(seconds)}s`;
        } else if (seconds < 3600) {
            return `${Math.round(seconds / 60)}m`;
        } else {
            return `${Math.round(seconds / 3600)}h`;
        }
    }

    /**
     * Estimate completion time
     */
    estimateCompletion(startTime, progress) {
        if (progress <= 0) return null;
        
        const elapsed = (Date.now() - new Date(startTime)) / 1000;
        const timePerPercent = elapsed / progress;
        const remainingTime = (100 - progress) * timePerPercent;
        
        return this.formatDuration(remainingTime);
    }
}

// Initialize export handler immediately and also on DOM ready
if (typeof window !== 'undefined') {
    window.exportHandler = new ExportHandler();
}

// Also reinitialize on DOMContentLoaded to ensure it's available
document.addEventListener('DOMContentLoaded', () => {
    if (!window.exportHandler) {
        window.exportHandler = new ExportHandler();
        console.log('ExportHandler initialized on DOMContentLoaded:', window.exportHandler);
    }
});

// Export for use in modules
export default ExportHandler;