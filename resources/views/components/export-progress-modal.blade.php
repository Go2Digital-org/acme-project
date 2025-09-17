@props([
    'show' => false,
    'exportId' => null,
    'exportType' => 'donations',
    'title' => 'Exporting Data'
])

<div 
    x-data="exportProgressModal(@js(['exportId' => $exportId, 'exportType' => $exportType]))"
    x-show="showModal || {{ $show ? 'true' : 'false' }}"
    x-init="init(); if ({{ $show ? 'true' : 'false' }}) { showModal = true; startExport(); }"
    class="fixed inset-0 z-50 overflow-y-auto"
    x-cloak
>
    <!-- Backdrop -->
    <div 
        x-show="showModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
        @click="cancelExport"
    ></div>

    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div 
            x-show="showModal"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="relative w-full max-w-lg transform rounded-xl bg-white dark:bg-gray-800 shadow-2xl"
        >
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div 
                        class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                        :class="{
                            'bg-blue-100 dark:bg-blue-900/50': status === 'processing',
                            'bg-green-100 dark:bg-green-900/50': status === 'completed',
                            'bg-red-100 dark:bg-red-900/50': status === 'failed',
                            'bg-yellow-100 dark:bg-yellow-900/50': status === 'cancelled'
                        }"
                    >
                        <i 
                            class="text-sm"
                            :class="{
                                'fas fa-spinner fa-spin text-blue-600 dark:text-blue-400': status === 'processing',
                                'fas fa-check text-green-600 dark:text-green-400': status === 'completed',
                                'fas fa-times text-red-600 dark:text-red-400': status === 'failed',
                                'fas fa-pause text-yellow-600 dark:text-yellow-400': status === 'cancelled'
                            }"
                        ></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $title }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="statusMessage"></p>
                    </div>
                </div>
                
                <button 
                    @click="cancelExport"
                    x-show="status === 'processing'"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Progress Section -->
            <div class="p-6 space-y-4">
                <!-- Progress Bar -->
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Progress</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="progress + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div 
                            class="h-full rounded-full transition-all duration-500 ease-out"
                            :class="{
                                'bg-gradient-to-r from-blue-500 to-blue-600': status === 'processing',
                                'bg-gradient-to-r from-green-500 to-green-600': status === 'completed',
                                'bg-gradient-to-r from-red-500 to-red-600': status === 'failed',
                                'bg-gradient-to-r from-yellow-500 to-yellow-600': status === 'cancelled'
                            }"
                            :style="{ width: progress + '%' }"
                        >
                            <!-- Animated stripes for processing -->
                            <div 
                                x-show="status === 'processing'"
                                class="h-full w-full bg-gradient-to-r from-transparent via-white/20 to-transparent animate-pulse"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Status Details -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 block">Records Processed</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="processedRecords"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 block">Total Records</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="totalRecords"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 block">Started At</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="startedAt"></span>
                        </div>
                        <div x-show="estimatedCompletion">
                            <span class="text-gray-500 dark:text-gray-400 block">ETA</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="estimatedCompletion"></span>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div 
                    x-show="status === 'failed' && errorMessage"
                    class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4"
                >
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-red-500 text-sm mt-0.5"></i>
                        <div>
                            <h4 class="font-medium text-red-800 dark:text-red-200">Export Failed</h4>
                            <p class="text-sm text-red-700 dark:text-red-300 mt-1" x-text="errorMessage"></p>
                        </div>
                    </div>
                </div>

                <!-- Success Message -->
                <div 
                    x-show="status === 'completed'"
                    class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4"
                >
                    <div class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 text-sm mt-0.5"></i>
                        <div>
                            <h4 class="font-medium text-green-800 dark:text-green-200">Export Completed</h4>
                            <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                                Your export is ready for download
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700">
                <button 
                    @click="cancelExport"
                    x-show="status === 'processing'"
                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    Cancel Export
                </button>
                
                <button 
                    @click="closeModal"
                    x-show="status !== 'processing'"
                    class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg transition-colors"
                >
                    Close
                </button>
                
                <button 
                    @click="downloadExport"
                    x-show="status === 'completed'"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center gap-2"
                >
                    <i class="fas fa-download text-xs"></i>
                    Download
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function exportProgressModal(config) {
    return {
        showModal: false,
        exportId: config.exportId,
        exportType: config.exportType,
        status: 'processing',
        progress: 0,
        processedRecords: 0,
        totalRecords: 0,
        statusMessage: 'Preparing export...',
        errorMessage: '',
        downloadUrl: '',
        startedAt: '',
        estimatedCompletion: '',
        pollInterval: null,

        init() {
            // Listen for show-export-progress event
            document.addEventListener('show-export-progress', (event) => {
                this.exportId = event.detail.exportId;
                this.exportType = event.detail.exportType;
                this.startExport();
            });
            
            // Also listen for the Alpine.js specific event
            window.addEventListener('alpine:init-export-modal', (event) => {
                this.exportId = event.detail.exportId;
                this.exportType = event.detail.exportType;
                this.showModal = event.detail.show;
                if (event.detail.show) {
                    this.startExport();
                }
            });
        },
        
        startExport() {
            this.showModal = true;
            this.status = 'processing';
            this.progress = 0;
            this.startedAt = new Date().toLocaleTimeString();
            
            if (this.exportId) {
                this.pollProgress();
            }
        },

        pollProgress() {
            this.pollInterval = setInterval(async () => {
                try {
                    const response = await fetch(`/api/exports/${this.exportId}/progress`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const data = await response.json();
                    this.updateProgress(data);

                    // Stop polling if export is complete
                    if (['completed', 'failed', 'cancelled'].includes(data.status)) {
                        this.clearPolling();
                        
                        // Don't show notification here - export-handler.js already handles this
                        // to avoid duplicate notifications
                    }

                } catch (error) {
                    console.error('Error polling export progress:', error);
                    this.clearPolling();
                    this.status = 'failed';
                    this.errorMessage = 'Failed to check export progress. Please refresh and try again.';
                }
            }, 2000); // Poll every 2 seconds
        },

        updateProgress(data) {
            this.status = data.status;
            this.progress = Math.round(data.progress || 0);
            // Handle both camelCase (from API) and snake_case field names
            this.processedRecords = data.processedRecords || data.processed_records || 0;
            this.totalRecords = data.totalRecords || data.total_records || 0;
            this.statusMessage = data.status_message || this.getDefaultStatusMessage();
            this.errorMessage = data.errorMessage || data.error_message || '';
            this.downloadUrl = data.downloadUrl || data.download_url || '';

            // Calculate ETA if we have enough data
            if (this.progress > 5 && this.status === 'processing') {
                const timeElapsed = (new Date() - new Date(this.startedAt)) / 1000;
                const timePerPercent = timeElapsed / this.progress;
                const remainingTime = (100 - this.progress) * timePerPercent;
                
                if (remainingTime < 60) {
                    this.estimatedCompletion = `${Math.round(remainingTime)}s`;
                } else {
                    this.estimatedCompletion = `${Math.round(remainingTime / 60)}m`;
                }
            }
        },

        getDefaultStatusMessage() {
            switch (this.status) {
                case 'processing':
                    return 'Exporting records...';
                case 'completed':
                    return 'Export completed successfully';
                case 'failed':
                    return 'Export failed';
                case 'cancelled':
                    return 'Export was cancelled';
                default:
                    return 'Processing...';
            }
        },

        async cancelExport() {
            if (this.status === 'processing' && this.exportId) {
                try {
                    const response = await fetch(`/api/exports/${this.exportId}/cancel`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (response.ok) {
                        this.status = 'cancelled';
                        this.statusMessage = 'Export cancelled';
                        // Don't show notification here - export-handler.js already handles this
                    }
                } catch (error) {
                    console.error('Error cancelling export:', error);
                }
            }
            
            this.clearPolling();
            this.closeModal();
        },

        async downloadExport() {
            if (!this.exportId) {
                console.error('No export ID available for download');
                return;
            }
            
            try {
                const response = await fetch(`/exports/download/${this.exportId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `export-${this.exportId}.csv`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
                
                this.showNotification('Download started', 'success');
                this.closeModal();
                
            } catch (error) {
                console.error('Error downloading export:', error);
                this.showNotification('Failed to download export', 'error');
            }
        },

        closeModal() {
            this.clearPolling();
            this.showModal = false;
        },

        clearPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },

        showNotification(message, type = 'success') {
            // Check if browser supports notifications
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('Export Status', {
                    body: message,
                    icon: '/favicon.ico'
                });
            }

            // Also show toast notification if available
            if (window.Swal) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });

                Toast.fire({
                    icon: type,
                    title: message
                });
            }
        }
    };
}
</script>