<x-layout title="{{ __('Manage Exports') }}">
<script>
window.exportFilters = function() {
    return {
        filters: {
            search: '',
            status: '',
            type: '',
            dateRange: ''
        },
        
        applyFilters() {
            // Dispatch event to update exports table
            document.dispatchEvent(new CustomEvent('filters-changed', {
                detail: this.filters
            }));
        },
        
        hasActiveFilters() {
            return Object.values(this.filters).some(value => value !== '');
        },
        
        getActiveFilters() {
            const active = [];
            const labels = {
                search: 'Search',
                status: 'Status', 
                type: 'Type',
                dateRange: 'Date Range'
            };
            
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value) {
                    active.push({
                        key: key,
                        label: labels[key],
                        value: value
                    });
                }
            });
            
            return active;
        },
        
        removeFilter(key) {
            this.filters[key] = '';
            this.applyFilters();
        },
        
        clearAllFilters() {
            Object.keys(this.filters).forEach(key => {
                this.filters[key] = '';
            });
            this.applyFilters();
        }
    };
};

window.exportsTable = function() {
    return {
        exports: [],
        loading: true,
        currentPage: 1,
        perPage: 15,
        totalRecords: 0,
        totalPages: 0,
        filters: {},
        availableTypes: [],
        
        async loadExports() {
            this.loading = true;
            
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: this.perPage,
                });
                
                // Add filters if they exist
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.type) params.append('resource_type', this.filters.type);
                if (this.filters.dateRange) params.append('date_range', this.filters.dateRange);
                
                const response = await fetch(`/exports?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                
                // The response has nested data structure from ApiResponse
                const exportData = data.data || {};
                this.exports = exportData.data || [];
                this.currentPage = exportData.meta?.current_page || 1;
                this.totalRecords = exportData.meta?.total || 0;
                this.totalPages = exportData.meta?.last_page || 1;
                
                // Extract unique resource types for filter dropdown
                this.updateAvailableTypes();
                
                // Start auto-refresh for processing exports
                this.startAutoRefresh();
                
            } catch (error) {
                console.error('Error loading exports:', error);
                this.showErrorMessage('Failed to load exports');
            } finally {
                this.loading = false;
            }
        },
        
        updateAvailableTypes() {
            // Get unique resource types from all exports
            const types = [...new Set(this.exports.map(e => e.resource_type).filter(Boolean))];
            this.availableTypes = types.sort();
            
            // Dispatch event to update filter dropdown
            document.dispatchEvent(new CustomEvent('types-updated', {
                detail: { types: this.availableTypes }
            }));
        },
        
        startAutoRefresh() {
            // Auto-refresh disabled - use manual refresh or event-based updates
            // Users can manually refresh by calling loadExports()
            return;
        },
        
        async downloadExport(exportItem) {
            try {
                const response = await fetch(`/exports/download/${exportItem.export_id}`, {
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
                link.download = exportItem.filename || `export-${exportItem.export_id}.csv`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
                
                this.showSuccessMessage('Download started');
                
            } catch (error) {
                console.error('Error downloading export:', error);
                this.showErrorMessage('Failed to download export');
            }
        },
        
        viewProgress(exportItem) {
            // Dispatch event to open progress modal
            document.dispatchEvent(new CustomEvent('show-export-progress', {
                detail: { exportId: exportItem.export_id, exportType: exportItem.resource_type }
            }));
        },
        
        async cancelExport(exportItem) {
            if (window.Swal) {
                const result = await Swal.fire({
                    title: 'Cancel Export?',
                    text: 'Are you sure you want to cancel this export? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, cancel it',
                    cancelButtonText: 'Keep export'
                });
                
                if (!result.isConfirmed) {
                    return;
                }
            } else if (!confirm('Are you sure you want to cancel this export?')) {
                return;
            }
            
            try {
                const response = await fetch(`/exports/cancel/${exportItem.export_id}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                // Update the specific export's status and progress bar without reloading
                const exportIndex = this.exports.findIndex(e => e.export_id === exportItem.export_id);
                if (exportIndex !== -1) {
                    this.exports[exportIndex].status = 'cancelled';
                    this.exports[exportIndex].current_percentage = 0;
                    this.exports[exportIndex].current_message = 'Export cancelled';
                }
                
                this.showSuccessMessage('Export cancelled successfully');
                
            } catch (error) {
                console.error('Error cancelling export:', error);
                this.showErrorMessage('Failed to cancel export');
            }
        },
        
        async retryExport(exportItem) {
            if (window.Swal) {
                const result = await Swal.fire({
                    title: 'Retry Export?',
                    text: 'Are you sure you want to retry this failed export?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, retry it',
                    cancelButtonText: 'Cancel'
                });
                
                if (!result.isConfirmed) {
                    return;
                }
            } else if (!confirm('Are you sure you want to retry this failed export?')) {
                return;
            }
            
            try {
                const response = await fetch(`/exports/retry/${exportItem.export_id}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                this.showSuccessMessage('Export retry initiated successfully');
                this.loadExports();
                
            } catch (error) {
                console.error('Error retrying export:', error);
                this.showErrorMessage('Failed to retry export');
            }
        },
        
        async deleteExport(exportItem) {
            if (window.Swal) {
                const result = await Swal.fire({
                    title: 'Delete Export?',
                    text: 'Are you sure you want to delete this export? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Keep export'
                });
                
                if (!result.isConfirmed) {
                    return;
                }
            } else if (!confirm('Are you sure you want to delete this export? This action cannot be undone.')) {
                return;
            }
            
            try {
                const response = await fetch(`/exports/${exportItem.export_id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                this.showSuccessMessage('Export deleted successfully');
                this.loadExports();
                
            } catch (error) {
                console.error('Error deleting export:', error);
                this.showErrorMessage('Failed to delete export');
            }
        },
        
        // Pagination methods
        goToPage(page) {
            this.currentPage = page;
            this.loadExports();
        },
        
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadExports();
            }
        },
        
        previousPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadExports();
            }
        },
        
        getVisiblePages() {
            const pages = [];
            const start = Math.max(1, this.currentPage - 2);
            const end = Math.min(this.totalPages, this.currentPage + 2);
            
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            
            return pages;
        },
        
        // Utility methods
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString();
        },
        
        formatTime(dateString) {
            return new Date(dateString).toLocaleTimeString();
        },
        
        formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },
        
        showSuccessMessage(message) {
            if (window.Swal) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
                
                Toast.fire({
                    icon: 'success',
                    title: message
                });
            } else {
                console.log('Success:', message);
            }
        },
        
        showErrorMessage(message) {
            if (window.Swal) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
                
                Toast.fire({
                    icon: 'error',
                    title: message
                });
            } else {
                console.error('Error:', message);
            }
        },
        
        showInfoMessage(message) {
            if (window.Swal) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
                
                Toast.fire({
                    icon: 'info',
                    title: message
                });
            } else {
                console.log('Info:', message);
            }
        },
        
        // Event listeners
        init() {
            // Listen for filter changes
            document.addEventListener('filters-changed', (event) => {
                this.filters = event.detail;
                this.currentPage = 1;
                this.loadExports();
            });
            
            // Listen for refresh requests (e.g., when a new export is started)
            document.addEventListener('refresh-exports-table', () => {
                console.log('Received refresh-exports-table event');
                console.log('Current this context:', this);
                // Use arrow function to preserve 'this' context
                this.loadExports();
            });
        }
    };
};
</script>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ __('Export Management') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        {{ __('Track and manage your data exports') }}
                    </p>
                </div>
                
                <!-- Export Actions -->
                <div class="flex items-center gap-3">
                    
                    <x-export-button 
                        export-type="donations"
                        class="bg-green-600 hover:bg-green-700"
                    >
                        <i class="fas fa-heart mr-2"></i>
                        {{ __('Export Donations') }}
                    </x-export-button>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div 
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6"
            x-data="window.exportFilters()"
        >
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Search') }}
                    </label>
                    <div class="relative">
                        <input 
                            type="text"
                            x-model="filters.search"
                            @input.debounce.300ms="applyFilters"
                            placeholder="{{ __('Search exports...') }}"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        >
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Status') }}
                    </label>
                    <select 
                        x-model="filters.status"
                        @change="applyFilters"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="processing">{{ __('Processing') }}</option>
                        <option value="completed">{{ __('Completed') }}</option>
                        <option value="failed">{{ __('Failed') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
                    </select>
                </div>

                <!-- Type Filter - Dynamic options from actual data -->
                <div x-data="{ resourceTypes: [] }" x-init="
                    // Listen for types update from exports table
                    document.addEventListener('types-updated', (event) => {
                        resourceTypes = event.detail.types || [];
                    });
                ">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Type') }}
                    </label>
                    <select 
                        x-model="filters.type"
                        @change="applyFilters"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        :disabled="resourceTypes.length === 0"
                    >
                        <option value="">{{ __('All Types') }}</option>
                        <template x-for="type in resourceTypes" :key="type">
                            <option :value="type" x-text="type.charAt(0).toUpperCase() + type.slice(1)"></option>
                        </template>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Date Range') }}
                    </label>
                    <select 
                        x-model="filters.dateRange"
                        @change="applyFilters"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">{{ __('All Time') }}</option>
                        <option value="today">{{ __('Today') }}</option>
                        <option value="week">{{ __('This Week') }}</option>
                        <option value="month">{{ __('This Month') }}</option>
                        <option value="quarter">{{ __('This Quarter') }}</option>
                    </select>
                </div>
            </div>

            <!-- Active Filters Display -->
            <div class="flex flex-wrap items-center gap-2 mt-4" x-show="hasActiveFilters()">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Active filters:') }}</span>
                
                <template x-for="filter in getActiveFilters()" :key="filter.key">
                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 rounded-full">
                        <span x-text="filter.label + ': ' + filter.value"></span>
                        <button @click="removeFilter(filter.key)" class="hover:text-blue-600 dark:hover:text-blue-300">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </span>
                </template>
                
                <button @click="clearAllFilters" class="text-xs text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">
                    {{ __('Clear all') }}
                </button>
            </div>
        </div>

        <!-- Exports Table -->
        <div 
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden"
            x-data="window.exportsTable()"
            x-init="init(); loadExports()"
        >
            <!-- Loading State -->
            <div x-show="loading" class="p-8 text-center">
                <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-2"></i>
                <p class="text-gray-600 dark:text-gray-400">{{ __('Loading exports...') }}</p>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && exports.length === 0" class="p-8 text-center">
                <i class="fas fa-file-export text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    {{ __('No exports found') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('Start by creating your first export using the buttons above.') }}
                </p>
            </div>

            <!-- Exports Table -->
            <div x-show="!loading && exports.length > 0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Type & Status') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Progress') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Records') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Created') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('File Size') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="exportItem in exports" :key="exportItem.export_id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <!-- Type & Status -->
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <i 
                                                    class="text-sm"
                                                    :class="(() => {
                                                        // Dynamic icon assignment based on resource type
                                                        const iconMap = {
                                                            'campaigns': 'fas fa-bullhorn text-blue-600',
                                                            'donations': 'fas fa-heart text-green-600',
                                                            'reports': 'fas fa-chart-bar text-purple-600',
                                                            'users': 'fas fa-users text-indigo-600',
                                                            'organizations': 'fas fa-building text-yellow-600',
                                                            // Default icon for unknown types
                                                            'default': 'fas fa-file-export text-gray-600'
                                                        };
                                                        return iconMap[exportItem.resource_type] || iconMap['default'];
                                                    })()"
                                                ></i>
                                                <span class="font-medium text-gray-900 dark:text-white capitalize" x-text="exportItem.resource_type"></span>
                                            </div>
                                            <span 
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full w-fit"
                                                :class="{
                                                    'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200': exportItem.status === 'pending',
                                                    'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200': exportItem.status === 'processing',
                                                    'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200': exportItem.status === 'completed',
                                                    'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200': exportItem.status === 'failed',
                                                    'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200': exportItem.status === 'cancelled'
                                                }"
                                                x-text="exportItem.status.charAt(0).toUpperCase() + exportItem.status.slice(1)"
                                            ></span>
                                        </div>
                                    </td>

                                    <!-- Progress -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div 
                                                    class="h-2 rounded-full transition-all duration-300"
                                                    :class="{
                                                        'bg-blue-600': exportItem.status === 'processing',
                                                        'bg-green-600': exportItem.status === 'completed',
                                                        'bg-red-600': exportItem.status === 'failed',
                                                        'bg-gray-400': exportItem.status === 'cancelled' || exportItem.status === 'pending'
                                                    }"
                                                    :style="{ width: (exportItem.progress_percentage || 0) + '%' }"
                                                ></div>
                                            </div>
                                            <span class="text-sm text-gray-600 dark:text-gray-400 min-w-[3rem]" x-text="(exportItem.progress_percentage || 0) + '%'"></span>
                                        </div>
                                    </td>

                                    <!-- Records -->
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                <span x-text="exportItem.processed_records || 0"></span>
                                                <span class="text-gray-400">/ </span>
                                                <span x-text="exportItem.total_records || 0"></span>
                                            </div>
                                            <div class="text-gray-500 dark:text-gray-400 text-xs">
                                                {{ __('records') }}
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Created -->
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <div class="font-medium text-gray-900 dark:text-white" x-text="formatDate(exportItem.created_at)"></div>
                                            <div class="text-gray-500 dark:text-gray-400 text-xs" x-text="formatTime(exportItem.created_at)"></div>
                                        </div>
                                    </td>

                                    <!-- File Size -->
                                    <td class="px-6 py-4">
                                        <span 
                                            class="text-sm text-gray-900 dark:text-white"
                                            x-text="exportItem.file_size ? formatFileSize(exportItem.file_size) : '-'"
                                        ></span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Download Button -->
                                            <button 
                                                @click="downloadExport(exportItem)"
                                                x-show="exportItem.status === 'completed'"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 hover:bg-green-100 dark:hover:bg-green-900/50 rounded-md transition-colors"
                                            >
                                                <i class="fas fa-download mr-1"></i>
                                                {{ __('Download') }}
                                            </button>

                                            <!-- View Progress Button -->
                                            <button 
                                                @click="viewProgress(exportItem)"
                                                x-show="exportItem.status === 'processing'"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-md transition-colors"
                                            >
                                                <i class="fas fa-eye mr-1"></i>
                                                {{ __('View Progress') }}
                                            </button>

                                            <!-- Cancel Button -->
                                            <button 
                                                @click="cancelExport(exportItem)"
                                                x-show="exportItem.status === 'processing' || exportItem.status === 'pending'"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-md transition-colors"
                                            >
                                                <i class="fas fa-times mr-1"></i>
                                                {{ __('Cancel') }}
                                            </button>

                                            <!-- Retry Button -->
                                            <button 
                                                @click="retryExport(exportItem)"
                                                x-show="exportItem.status === 'failed'"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/30 hover:bg-yellow-100 dark:hover:bg-yellow-900/50 rounded-md transition-colors"
                                            >
                                                <i class="fas fa-redo mr-1"></i>
                                                {{ __('Retry') }}
                                            </button>

                                            <!-- Delete Button -->
                                            <button 
                                                @click="deleteExport(exportItem)"
                                                x-show="['completed', 'failed', 'cancelled'].includes(exportItem.status)"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-red-600 dark:hover:text-red-400 rounded-md transition-colors"
                                            >
                                                <i class="fas fa-trash mr-1"></i>
                                                {{ __('Delete') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            {{ __('Showing') }} 
                            <span class="font-medium" x-text="((currentPage - 1) * perPage) + 1"></span> 
                            {{ __('to') }} 
                            <span class="font-medium" x-text="Math.min(currentPage * perPage, totalRecords)"></span> 
                            {{ __('of') }} 
                            <span class="font-medium" x-text="totalRecords"></span> 
                            {{ __('results') }}
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <button 
                                @click="previousPage"
                                :disabled="currentPage <= 1"
                                class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ __('Previous') }}
                            </button>
                            
                            <template x-for="page in getVisiblePages()" :key="page">
                                <button 
                                    @click="goToPage(page)"
                                    :class="page === currentPage ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                    class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md"
                                    x-text="page"
                                ></button>
                            </template>
                            
                            <button 
                                @click="nextPage"
                                :disabled="currentPage >= totalPages"
                                class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ __('Next') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Progress Modal -->
    <x-export-progress-modal />
</div>

@push('scripts')
<!-- JavaScript functions are defined inline above, no duplicate definitions needed -->
@endpush
</x-layout>