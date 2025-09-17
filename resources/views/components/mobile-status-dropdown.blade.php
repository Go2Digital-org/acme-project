{{-- Mobile-Friendly Status Dropdown Component --}}
@props([
    'currentStatus' => 'active',
    'campaignId' => null,
    'availableStatuses' => ['active', 'paused', 'completed', 'draft'],
    'disabled' => false,
    'size' => 'default', // sm, default, lg
    'showIcon' => true,
    'showDescription' => false,
    'name' => 'status',
    'onChangeCallback' => null
])

@php
    $statusConfig = [
        'active' => [
            'label' => 'Active',
            'icon' => 'fa-play-circle',
            'color' => 'text-green-600 dark:text-green-400',
            'bgColor' => 'bg-green-100 dark:bg-green-900/30',
            'description' => 'Campaign is live and accepting donations'
        ],
        'paused' => [
            'label' => 'Paused',
            'icon' => 'fa-pause-circle',
            'color' => 'text-yellow-600 dark:text-yellow-400',
            'bgColor' => 'bg-yellow-100 dark:bg-yellow-900/30',
            'description' => 'Campaign is temporarily paused'
        ],
        'completed' => [
            'label' => 'Completed',
            'icon' => 'fa-check-circle',
            'color' => 'text-blue-600 dark:text-blue-400',
            'bgColor' => 'bg-blue-100 dark:bg-blue-900/30',
            'description' => 'Campaign has reached its goal or ended'
        ],
        'draft' => [
            'label' => 'Draft',
            'icon' => 'fa-edit',
            'color' => 'text-gray-600 dark:text-gray-400',
            'bgColor' => 'bg-gray-100 dark:bg-gray-700',
            'description' => 'Campaign is not yet published'
        ],
        'cancelled' => [
            'label' => 'Cancelled',
            'icon' => 'fa-times-circle',
            'color' => 'text-red-600 dark:text-red-400',
            'bgColor' => 'bg-red-100 dark:bg-red-900/30',
            'description' => 'Campaign has been cancelled'
        ]
    ];
    
    $sizeClasses = match($size) {
        'sm' => 'text-xs px-2 py-1',
        'lg' => 'text-base px-4 py-3',
        default => 'text-sm px-3 py-2'
    };
@endphp

<div 
    class="relative"
    x-data="statusDropdown({
        currentStatus: '{{ $currentStatus }}',
        campaignId: {{ $campaignId ?: 'null' }},
        availableStatuses: @js($availableStatuses),
        onChangeCallback: {{ $onChangeCallback ?: 'null' }},
        disabled: {{ $disabled ? 'true' : 'false' }}
    })"
>
    {{-- Dropdown Button --}}
    <button
        type="button"
        @click="toggleDropdown()"
        @click.away="closeDropdown()"
        :disabled="disabled || updating"
        class="w-full flex items-center justify-between gap-2 {{ $sizeClasses }} border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-left focus:ring-2 focus:ring-primary focus:border-primary transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        :class="getCurrentStatusClasses()"
        :aria-label="`Current status: ${getCurrentStatusLabel()}. Click to change status.`"
        aria-haspopup="listbox"
        :aria-expanded="isOpen"
    >
        <div class="flex items-center gap-2 flex-1 min-w-0">
            @if($showIcon)
            <i 
                class="fas flex-shrink-0" 
                :class="getCurrentStatusIcon()" 
                aria-hidden="true"
            ></i>
            @endif
            
            <div class="flex-1 min-w-0">
                <span class="font-medium" x-text="getCurrentStatusLabel()"></span>
                @if($showDescription)
                <div class="text-xs opacity-75 truncate" x-text="getCurrentStatusDescription()"></div>
                @endif
            </div>
        </div>
        
        {{-- Loading/Arrow Icon --}}
        <div class="flex-shrink-0">
            <i 
                x-show="!updating" 
                class="fas fa-chevron-down transition-transform text-xs"
                :class="{ 'rotate-180': isOpen }"
                aria-hidden="true"
            ></i>
            <i 
                x-show="updating" 
                class="fas fa-spinner fa-spin text-xs"
                aria-hidden="true"
            ></i>
        </div>
    </button>
    
    {{-- Hidden Input for Form Submission --}}
    <input 
        type="hidden" 
        :name="'{{ $name }}'" 
        :value="selectedStatus"
        x-model="selectedStatus"
    >

    {{-- Dropdown Menu --}}
    <div
        x-show="isOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50 max-h-60 overflow-y-auto"
        role="listbox"
        :aria-label="'Available status options'"
    >
        <template x-for="status in availableStatuses" :key="status">
            <button
                type="button"
                @click="selectStatus(status)"
                class="w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                :class="{
                    'bg-gray-50 dark:bg-gray-700': status === selectedStatus,
                    'cursor-not-allowed opacity-50': status === selectedStatus
                }"
                :disabled="status === selectedStatus"
                role="option"
                :aria-selected="status === selectedStatus"
            >
                {{-- Status Icon --}}
                <i 
                    class="fas flex-shrink-0 text-sm"
                    :class="[getStatusIcon(status), getStatusColor(status)]"
                    aria-hidden="true"
                ></i>
                
                {{-- Status Info --}}
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-gray-900 dark:text-white" x-text="getStatusLabel(status)"></div>
                    @if($showDescription)
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5" x-text="getStatusDescription(status)"></div>
                    @endif
                </div>
                
                {{-- Selected Indicator --}}
                <i 
                    x-show="status === selectedStatus"
                    class="fas fa-check text-primary text-xs"
                    aria-hidden="true"
                ></i>
            </button>
        </template>
    </div>
</div>

{{-- Status Change Confirmation Modal (for critical changes) --}}
<x-mobile-confirmation-modal 
    :id="'status-change-modal-' . ($campaignId ?: 'default')"
    title="Confirm Status Change"
>
    <div>
        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg mb-4">
            <i class="fas fa-info-circle text-blue-500 text-lg"></i>
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Change status from <span x-text="getCurrentStatusLabel()" class="font-semibold"></span> 
                    to <span x-text="pendingStatus ? getStatusLabel(pendingStatus) : ''" class="font-semibold"></span>?
                </p>
            </div>
        </div>
        
        {{-- Warning for critical status changes --}}
        <div x-show="isCriticalChange()" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 mb-4">
            <div class="flex items-start gap-2">
                <i class="fas fa-exclamation-triangle text-yellow-500 text-sm mt-0.5"></i>
                <div>
                    <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-400 mb-1">Important</h4>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300" x-text="getCriticalChangeWarning()"></p>
                </div>
            </div>
        </div>

        <div class="flex gap-2 justify-end">
            <button
                type="button"
                @click="cancelStatusChange()"
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
            >
                Cancel
            </button>
            <button
                type="button"
                @click="confirmStatusChange()"
                :disabled="updating"
                class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary disabled:opacity-50"
            >
                <span x-show="!updating">Confirm Change</span>
                <span x-show="updating">Updating...</span>
            </button>
        </div>
    </div>
</x-mobile-confirmation-modal>

{{-- Alpine.js Component --}}
<script>
    function statusDropdown(config) {
        return {
            // Configuration
            currentStatus: config.currentStatus,
            campaignId: config.campaignId,
            availableStatuses: config.availableStatuses,
            onChangeCallback: config.onChangeCallback,
            disabled: config.disabled,
            
            // State
            selectedStatus: config.currentStatus,
            isOpen: false,
            updating: false,
            pendingStatus: null,
            
            // Status configuration
            statusConfig: {
                'active': {
                    label: 'Active',
                    icon: 'fa-play-circle',
                    color: 'text-green-600 dark:text-green-400',
                    bgColor: 'bg-green-100 dark:bg-green-900/30',
                    description: 'Campaign is live and accepting donations'
                },
                'paused': {
                    label: 'Paused',
                    icon: 'fa-pause-circle',
                    color: 'text-yellow-600 dark:text-yellow-400',
                    bgColor: 'bg-yellow-100 dark:bg-yellow-900/30',
                    description: 'Campaign is temporarily paused'
                },
                'completed': {
                    label: 'Completed',
                    icon: 'fa-check-circle',
                    color: 'text-blue-600 dark:text-blue-400',
                    bgColor: 'bg-blue-100 dark:bg-blue-900/30',
                    description: 'Campaign has reached its goal or ended'
                },
                'draft': {
                    label: 'Draft',
                    icon: 'fa-edit',
                    color: 'text-gray-600 dark:text-gray-400',
                    bgColor: 'bg-gray-100 dark:bg-gray-700',
                    description: 'Campaign is not yet published'
                },
                'cancelled': {
                    label: 'Cancelled',
                    icon: 'fa-times-circle',
                    color: 'text-red-600 dark:text-red-400',
                    bgColor: 'bg-red-100 dark:bg-red-900/30',
                    description: 'Campaign has been cancelled'
                }
            },

            // Dropdown methods
            toggleDropdown() {
                if (this.disabled || this.updating) return;
                this.isOpen = !this.isOpen;
            },

            closeDropdown() {
                this.isOpen = false;
            },

            // Status selection
            selectStatus(status) {
                if (status === this.selectedStatus || this.updating) return;
                
                this.closeDropdown();
                
                // Check if this is a critical change that needs confirmation
                if (this.isCriticalStatusChange(this.selectedStatus, status)) {
                    this.pendingStatus = status;
                    this.$dispatch('modal-open', { 
                        id: `status-change-modal-${this.campaignId || 'default'}`
                    });
                } else {
                    this.updateStatus(status);
                }
            },

            // Status update
            async updateStatus(newStatus) {
                if (this.updating) return;
                
                this.updating = true;
                const oldStatus = this.selectedStatus;
                
                try {
                    // Optimistic update
                    this.selectedStatus = newStatus;
                    
                    // If there's a campaign ID, make API call
                    if (this.campaignId) {
                        const response = await fetch(`/campaigns/${this.campaignId}/status`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ status: newStatus })
                        });
                        
                        if (!response.ok) {
                            // Revert on failure
                            this.selectedStatus = oldStatus;
                            throw new Error('Failed to update campaign status');
                        }
                    }
                    
                    // Call custom callback if provided
                    if (this.onChangeCallback && typeof window[this.onChangeCallback] === 'function') {
                        await window[this.onChangeCallback](newStatus, oldStatus);
                    }
                    
                    this.showNotification(`Status changed to ${this.getStatusLabel(newStatus)}`, 'success');
                    
                } catch (error) {
                    console.error('Status update error:', error);
                    this.selectedStatus = oldStatus; // Revert
                    this.showNotification('Failed to update status', 'error');
                } finally {
                    this.updating = false;
                    this.pendingStatus = null;
                }
            },

            // Confirmation methods
            confirmStatusChange() {
                if (this.pendingStatus) {
                    this.updateStatus(this.pendingStatus);
                    this.$dispatch('modal-close', { 
                        id: `status-change-modal-${this.campaignId || 'default'}`
                    });
                }
            },

            cancelStatusChange() {
                this.pendingStatus = null;
                this.$dispatch('modal-close', { 
                    id: `status-change-modal-${this.campaignId || 'default'}`
                });
            },

            // Helper methods
            getCurrentStatusClasses() {
                return this.statusConfig[this.selectedStatus]?.bgColor || 'bg-gray-100 dark:bg-gray-700';
            },

            getCurrentStatusIcon() {
                return this.statusConfig[this.selectedStatus]?.icon || 'fa-circle';
            },

            getCurrentStatusLabel() {
                return this.statusConfig[this.selectedStatus]?.label || this.selectedStatus;
            },

            getCurrentStatusDescription() {
                return this.statusConfig[this.selectedStatus]?.description || '';
            },

            getStatusIcon(status) {
                return this.statusConfig[status]?.icon || 'fa-circle';
            },

            getStatusColor(status) {
                return this.statusConfig[status]?.color || 'text-gray-600';
            },

            getStatusLabel(status) {
                return this.statusConfig[status]?.label || status;
            },

            getStatusDescription(status) {
                return this.statusConfig[status]?.description || '';
            },

            // Critical change detection
            isCriticalStatusChange(from, to) {
                const criticalChanges = [
                    ['active', 'cancelled'],
                    ['active', 'completed'],
                    ['completed', 'active'],
                    ['completed', 'draft']
                ];
                
                return criticalChanges.some(([f, t]) => f === from && t === to);
            },

            isCriticalChange() {
                return this.pendingStatus && 
                       this.isCriticalStatusChange(this.selectedStatus, this.pendingStatus);
            },

            getCriticalChangeWarning() {
                if (!this.pendingStatus) return '';
                
                const warnings = {
                    'cancelled': 'This will permanently cancel the campaign and stop all donations.',
                    'completed': 'This will mark the campaign as completed and stop accepting new donations.',
                    'active': 'This will make the campaign live and visible to all users.',
                    'draft': 'This will hide the campaign from public view.'
                };
                
                return warnings[this.pendingStatus] || 'This action may have significant effects on your campaign.';
            },

            // Notification system
            showNotification(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm max-w-sm ${
                    type === 'success' ? 'bg-green-600' : 
                    type === 'error' ? 'bg-red-600' : 
                    'bg-blue-600'
                }`;
                
                toast.innerHTML = `
                    <div class="flex items-center gap-2">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
                        <span>${message}</span>
                    </div>
                `;
                
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 3000);
            },

            // Keyboard navigation
            init() {
                // Add keyboard support for accessibility
                this.$el.addEventListener('keydown', (e) => {
                    if (this.disabled || this.updating) return;
                    
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.toggleDropdown();
                    } else if (e.key === 'Escape') {
                        this.closeDropdown();
                    } else if (this.isOpen && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
                        e.preventDefault();
                        // Navigate through options
                        const currentIndex = this.availableStatuses.indexOf(this.selectedStatus);
                        let newIndex;
                        
                        if (e.key === 'ArrowUp') {
                            newIndex = currentIndex > 0 ? currentIndex - 1 : this.availableStatuses.length - 1;
                        } else {
                            newIndex = currentIndex < this.availableStatuses.length - 1 ? currentIndex + 1 : 0;
                        }
                        
                        this.selectStatus(this.availableStatuses[newIndex]);
                    }
                });
            }
        };
    }
</script>