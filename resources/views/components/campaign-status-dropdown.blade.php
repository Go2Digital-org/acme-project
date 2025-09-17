@props([
    'campaign',
    'size' => 'sm', // sm, md, lg
    'user' => null, // Pass the authenticated user
])

@php
    $sizeClasses = match($size) {
        'md' => 'px-3 py-2 text-sm',
        'lg' => 'px-4 py-3 text-base',
        default => 'px-2 py-1.5 text-xs',
    };

    $currentUser = $user ?? auth()->user();
    $isSuperAdmin = $currentUser && $currentUser->hasRole('super_admin');
    $isOwner = $currentUser && $campaign->user_id === $currentUser->id;

    $statusConfig = [
        'draft' => [
            'color' => 'gray',
            'icon' => 'fas fa-edit',
            'label' => 'Draft',
            'description' => 'Not visible to public',
        ],
        'pending_approval' => [
            'color' => 'orange',
            'icon' => 'fas fa-clock',
            'label' => 'Pending Approval',
            'description' => 'Awaiting admin approval',
        ],
        'rejected' => [
            'color' => 'red',
            'icon' => 'fas fa-ban',
            'label' => 'Rejected',
            'description' => 'Needs revisions',
        ],
        'active' => [
            'color' => 'green',
            'icon' => 'fas fa-play',
            'label' => 'Active',
            'description' => 'Accepting donations',
        ],
        'paused' => [
            'color' => 'yellow',
            'icon' => 'fas fa-pause',
            'label' => 'Paused',
            'description' => 'Temporarily stopped',
        ],
        'completed' => [
            'color' => 'blue',
            'icon' => 'fas fa-check',
            'label' => 'Completed',
            'description' => 'Goal achieved or ended',
        ],
        'cancelled' => [
            'color' => 'red',
            'icon' => 'fas fa-times',
            'label' => 'Cancelled',
            'description' => 'Campaign cancelled',
        ],
        'expired' => [
            'color' => 'gray',
            'icon' => 'fas fa-hourglass-end',
            'label' => 'Expired',
            'description' => 'Past end date',
        ],
    ];

    $currentStatus = $campaign->status->value;
    $currentConfig = $statusConfig[$currentStatus] ?? $statusConfig['active'];
    
    // Only show dropdown if user can manage the campaign
    $canManage = $isSuperAdmin || $isOwner;
@endphp

@if(!$canManage)
    {{-- Show read-only status badge if user cannot manage --}}
    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
        {{ $currentConfig['color'] === 'gray' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
        {{ $currentConfig['color'] === 'orange' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300' : '' }}
        {{ $currentConfig['color'] === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : '' }}
        {{ $currentConfig['color'] === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' : '' }}
        {{ $currentConfig['color'] === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300' : '' }}
        {{ $currentConfig['color'] === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' : '' }}">
        <i class="{{ $currentConfig['icon'] }} mr-1"></i>
        {{ $currentConfig['label'] }}
    </span>
@else
    {{-- Interactive dropdown for users who can manage --}}

<div 
    x-data="campaignStatusDropdown('{{ $campaign->uuid ?? $campaign->id }}', '{{ $currentStatus }}', {{ $isSuperAdmin ? 'true' : 'false' }})" 
    x-init="init()"
    class="relative inline-block text-left"
>
    <!-- Current Status Button -->
    <button
        @click="toggle()"
        :disabled="loading"
        class="inline-flex items-center justify-between {{ $sizeClasses }} font-medium rounded-lg border-2 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1 min-h-[44px] sm:min-h-[auto]"
        :class="{
            'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700': status === 'draft' || status === 'expired',
            'border-orange-300 dark:border-orange-600 bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 hover:bg-orange-100 dark:hover:bg-orange-900/30': status === 'pending_approval',
            'border-green-300 dark:border-green-600 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30': status === 'active',
            'border-yellow-300 dark:border-yellow-600 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 hover:bg-yellow-100 dark:hover:bg-yellow-900/30': status === 'paused',
            'border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/30': status === 'completed',
            'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/30': status === 'cancelled' || status === 'rejected',
            'opacity-50 cursor-not-allowed': loading
        }"
        type="button"
    >
        <span class="flex items-center gap-2">
            <i :class="getStatusIcon(status)" class="text-xs sm:text-sm"></i>
            <span x-text="getStatusLabel(status)" class="hidden sm:inline"></span>
            <span x-text="getStatusLabel(status).substring(0, 1)" class="sm:hidden"></span>
        </span>
        <svg class="ml-1 sm:ml-2 w-3 h-3 sm:w-4 sm:h-4 transition-transform duration-200" 
             :class="{ 'rotate-180': open }" 
             fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="close()"
        class="absolute right-0 z-50 mt-2 w-72 sm:w-80 origin-top-right bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg"
        style="display: none;"
    >
        <div class="p-2">
            <div class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                Change Campaign Status
            </div>
            
            <template x-for="(config, statusKey) in statusOptions" :key="statusKey">
                <button
                    @click="changeStatus(statusKey)"
                    :disabled="!canChangeToStatus(statusKey) || loading"
                    class="w-full text-left px-3 py-2 sm:py-3 rounded-lg mt-2 transition-colors duration-200 min-h-[44px] flex items-center"
                    :class="{
                        'bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600': canChangeToStatus(statusKey) && statusKey !== status,
                        'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 ring-2 ring-blue-200 dark:ring-blue-800': statusKey === status,
                        'opacity-50 cursor-not-allowed text-gray-400 dark:text-gray-600': !canChangeToStatus(statusKey),
                    }"
                >
                    <div class="flex items-center gap-3 flex-1">
                        <div class="flex-shrink-0">
                            <i :class="config.icon" 
                               class="text-sm sm:text-base"
                               :class="{
                                   'text-gray-500 dark:text-gray-400': statusKey === 'draft' || statusKey === 'expired',
                                   'text-orange-500': statusKey === 'pending_approval',
                                   'text-red-500': statusKey === 'rejected' || statusKey === 'cancelled',
                                   'text-green-500': statusKey === 'active',
                                   'text-yellow-500': statusKey === 'paused',
                                   'text-blue-500': statusKey === 'completed'
                               }">
                            </i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm" x-text="config.label"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="config.description"></div>
                        </div>
                        <div x-show="statusKey === status" class="flex-shrink-0">
                            <i class="fas fa-check text-blue-500 text-sm"></i>
                        </div>
                    </div>
                </button>
            </template>
        </div>
    </div>

    <!-- Success Toast -->
    <div 
        x-show="showSuccess"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-4 right-4 z-50 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg"
        style="display: none;"
    >
        <i class="fas fa-check mr-2"></i>
        Status updated successfully!
    </div>

    <!-- Error Toast -->
    <div 
        x-show="showError"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-4 right-4 z-50 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg"
        style="display: none;"
    >
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <span x-text="errorMessage"></span>
    </div>
</div>

@endif

<script>
function campaignStatusDropdown(campaignId, currentStatus, isSuperAdmin = false) {
    return {
        campaignId: campaignId,
        status: currentStatus,
        open: false,
        loading: false,
        showSuccess: false,
        showError: false,
        errorMessage: '',
        isSuperAdmin: isSuperAdmin,
        
        statusOptions: {
            'draft': {
                icon: 'fas fa-edit',
                label: 'Draft',
                description: 'Not visible to public'
            },
            'pending_approval': {
                icon: 'fas fa-clock',
                label: 'Pending Approval',
                description: 'Awaiting admin approval'
            },
            'rejected': {
                icon: 'fas fa-ban',
                label: 'Rejected',
                description: 'Needs revisions'
            },
            'active': {
                icon: 'fas fa-play',
                label: 'Active',
                description: 'Accepting donations'
            },
            'paused': {
                icon: 'fas fa-pause',
                label: 'Paused',
                description: 'Temporarily stopped'
            },
            'completed': {
                icon: 'fas fa-check',
                label: 'Completed',
                description: 'Goal achieved or ended'
            },
            'cancelled': {
                icon: 'fas fa-times',
                label: 'Cancelled',
                description: 'Campaign cancelled'
            },
            'expired': {
                icon: 'fas fa-hourglass-end',
                label: 'Expired',
                description: 'Past end date'
            }
        },

        init() {
            // Auto-hide success/error messages
            this.$watch('showSuccess', (value) => {
                if (value) {
                    setTimeout(() => {
                        this.showSuccess = false;
                    }, 3000);
                }
            });

            this.$watch('showError', (value) => {
                if (value) {
                    setTimeout(() => {
                        this.showError = false;
                    }, 5000);
                }
            });
        },

        toggle() {
            this.open = !this.open;
        },

        close() {
            this.open = false;
        },

        getStatusIcon(status) {
            return this.statusOptions[status]?.icon || 'fas fa-circle';
        },

        getStatusLabel(status) {
            return this.statusOptions[status]?.label || 'Unknown';
        },

        canChangeToStatus(newStatus) {
            if (newStatus === this.status) return true;
            
            // All valid transitions from CampaignStatus enum
            const allTransitions = {
                'draft': ['pending_approval', 'cancelled'],
                'pending_approval': ['active', 'rejected'],
                'rejected': ['draft', 'cancelled'],
                'active': ['paused', 'completed', 'cancelled', 'expired'],
                'paused': ['active', 'cancelled', 'expired'],
                'completed': [],
                'cancelled': [],
                'expired': []
            };

            // Check if transition is valid
            const isValidTransition = allTransitions[this.status]?.includes(newStatus) || false;
            
            if (!isValidTransition) {
                return false;
            }

            // Super admins can make all valid transitions
            if (this.isSuperAdmin) {
                return true;
            }

            // Regular users (campaign owners) have limited transitions
            const ownerAllowedTransitions = {
                'draft': ['pending_approval', 'cancelled'],
                'rejected': ['draft', 'cancelled'],
                'pending_approval': [],
                'active': [],
                'paused': [],
                'completed': [],
                'cancelled': [],
                'expired': []
            };

            return ownerAllowedTransitions[this.status]?.includes(newStatus) || false;
        },

        async changeStatus(newStatus) {
            if (!this.canChangeToStatus(newStatus) || this.loading) {
                return;
            }

            if (newStatus === this.status) {
                this.close();
                return;
            }

            this.loading = true;
            this.errorMessage = '';

            try {
                const response = await fetch(`/api/campaigns/${this.campaignId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        status: newStatus
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    this.status = newStatus;
                    this.showSuccess = true;
                    this.close();
                } else {
                    this.errorMessage = data.message || 'Failed to update status';
                    this.showError = true;
                }
            } catch (error) {
                console.error('Status update error:', error);
                this.errorMessage = 'Network error occurred';
                this.showError = true;
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>