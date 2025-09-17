{{-- Campaign Management Actions Component --}}
@props([
    'campaign',
    'position' => 'inline', // inline, sticky, floating
    'showEditButton' => true,
    'showDeleteButton' => true,
    'showStatusToggle' => true,
    'showAnalytics' => false,
    'compact' => false
])

@php
    $isOwner = auth()->check() && auth()->user()->id === $campaign->creator_id;
    $canEdit = $isOwner || auth()->user()?->hasRole('admin');
    
    $containerClasses = match($position) {
        'sticky' => 'sticky bottom-4 left-4 right-4 z-40',
        'floating' => 'fixed bottom-4 right-4 z-40',
        default => ''
    };
@endphp

@if($canEdit)
<div 
    class="{{ $containerClasses }}" 
    x-data="campaignActions('{{ $campaign->uuid ?? $campaign->id }}')"
>
    {{-- Mobile-First Action Panel --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-3 sm:p-4">
        {{-- Header for non-inline positions --}}
        @if($position !== 'inline')
        <div class="flex items-center justify-between mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Campaign Actions</h3>
            <button
                @click="collapsed = !collapsed"
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 sm:hidden"
                :aria-label="collapsed ? 'Expand actions' : 'Collapse actions'"
            >
                <i class="fas" :class="collapsed ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>
        @endif

        {{-- Actions Grid --}}
        <div 
            class="space-y-2 sm:space-y-0 sm:grid {{ $compact ? 'sm:grid-cols-2 sm:gap-2' : 'sm:grid-cols-4 sm:gap-3' }}"
            x-show="!collapsed || '{{ $position }}' === 'inline'"
            x-collapse
        >
            {{-- Edit Campaign --}}
            @if($showEditButton)
            <a
                href="{{ route('campaigns.edit', $campaign->uuid ?? $campaign->id) }}"
                class="flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            >
                <i class="fas fa-edit text-xs"></i>
                <span class="{{ $compact ? 'hidden sm:inline' : '' }}">Edit</span>
            </a>
            @endif

            {{-- Status Toggle --}}
            @if($showStatusToggle && in_array($campaign->status, ['active', 'paused']))
            <button
                @click="toggleStatus()"
                :disabled="loading"
                class="flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                :class="getStatusToggleClasses()"
            >
                <i class="fas text-xs" :class="getStatusIcon()"></i>
                <span class="{{ $compact ? 'hidden sm:inline' : '' }}" x-text="getStatusText()"></span>
                <i x-show="loading" class="fas fa-spinner fa-spin text-xs ml-1"></i>
            </button>
            @endif

            {{-- Duplicate Campaign --}}
            <button
                @click="duplicateCampaign()"
                :disabled="loading"
                class="flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-blue-700 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
            >
                <i class="fas fa-copy text-xs"></i>
                <span class="{{ $compact ? 'hidden sm:inline' : '' }}">Duplicate</span>
                <i x-show="loading" class="fas fa-spinner fa-spin text-xs ml-1"></i>
            </button>

            {{-- Analytics (if enabled) --}}
            @if($showAnalytics)
            <a
                href="{{ route('campaigns.analytics', $campaign->uuid ?? $campaign->id) }}"
                class="flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-purple-700 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/30 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors"
            >
                <i class="fas fa-chart-line text-xs"></i>
                <span class="{{ $compact ? 'hidden sm:inline' : '' }}">Analytics</span>
            </a>
            @endif

            {{-- Delete Campaign --}}
            @if($showDeleteButton && $isOwner)
            <button
                @click="confirmDelete()"
                class="flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-red-700 dark:text-red-400 bg-red-100 dark:bg-red-900/30 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
            >
                <i class="fas fa-trash text-xs"></i>
                <span class="{{ $compact ? 'hidden sm:inline' : '' }}">Delete</span>
            </button>
            @endif
        </div>

        {{-- Quick Status Indicator --}}
        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full" :class="getStatusIndicatorColor()"></div>
                <span>Status: </span>
                <span class="font-medium capitalize" x-text="currentStatus">{{ $campaign->status }}</span>
            </div>
            
            @if($campaign->updated_at)
            <span>Updated {{ $campaign->updated_at->diffForHumans() }}</span>
            @endif
        </div>

        {{-- Mobile Collapse Trigger --}}
        @if($position !== 'inline')
        <button
            @click="collapsed = !collapsed"
            class="w-full mt-2 py-1 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 sm:hidden"
            :aria-label="collapsed ? 'Show actions' : 'Hide actions'"
        >
            <i class="fas" :class="collapsed ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </button>
        @endif
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<x-mobile-confirmation-modal 
    id="delete-campaign-modal-{{ $campaign->uuid ?? $campaign->id }}"
    title="Delete Campaign"
    :danger="true"
>
    <div>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Are you sure you want to delete "<strong>{{ $campaign->getTitle() }}</strong>"? This action cannot be undone.
        </p>
        
        @if($campaign->current_amount > 0 || ($campaign->donations_count ?? 0) > 0)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 mb-4">
            <div class="flex items-start gap-2">
                <i class="fas fa-exclamation-triangle text-red-500 text-sm mt-0.5"></i>
                <div>
                    <h4 class="text-sm font-medium text-red-800 dark:text-red-400 mb-1">This campaign has active donations!</h4>
                    <ul class="text-xs text-red-700 dark:text-red-300 space-y-1">
                        @if($campaign->current_amount > 0)
                        <li>• ${{ number_format($campaign->current_amount) }} has been raised</li>
                        @endif
                        @if(($campaign->donations_count ?? 0) > 0)
                        <li>• {{ $campaign->donations_count }} donations will be preserved</li>
                        @endif
                        <li>• Campaign history and analytics will be lost</li>
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <div class="flex gap-2 justify-end">
            <button
                type="button"
                @click="$dispatch('modal-close', { id: 'delete-campaign-modal-{{ $campaign->uuid ?? $campaign->id }}' })"
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
            >
                Cancel
            </button>
            <button
                type="button"
                @click="deleteCampaign(); $dispatch('modal-close', { id: 'delete-campaign-modal-{{ $campaign->uuid ?? $campaign->id }}' })"
                :disabled="loading"
                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50"
            >
                <i class="fas fa-trash mr-1"></i>
                <span x-show="!loading">Delete Campaign</span>
                <span x-show="loading">Deleting...</span>
            </button>
        </div>
    </div>
</x-mobile-confirmation-modal>

{{-- Alpine.js Component Script --}}
<script>
    function campaignActions(campaignId) {
        return {
            // State
            currentStatus: @json($campaign->status),
            loading: false,
            collapsed: window.innerWidth < 640, // Start collapsed on mobile
            
            // Status management
            async toggleStatus() {
                if (this.loading) return;
                
                this.loading = true;
                const newStatus = this.currentStatus === 'active' ? 'paused' : 'active';
                
                try {
                    const response = await fetch(`/campaigns/${campaignId}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ status: newStatus })
                    });
                    
                    if (response.ok) {
                        this.currentStatus = newStatus;
                        this.showNotification(`Campaign ${newStatus === 'active' ? 'activated' : 'paused'} successfully`, 'success');
                        
                        // Update page elements if needed
                        this.updatePageStatus(newStatus);
                    } else {
                        throw new Error('Failed to update status');
                    }
                    
                } catch (error) {
                    console.error('Status toggle error:', error);
                    this.showNotification('Failed to update campaign status', 'error');
                } finally {
                    this.loading = false;
                }
            },

            // Duplicate campaign
            async duplicateCampaign() {
                if (this.loading) return;
                
                this.loading = true;
                
                try {
                    const response = await fetch(`/campaigns/${campaignId}/duplicate`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (response.ok) {
                        const result = await response.json();
                        this.showNotification('Campaign duplicated successfully', 'success');
                        
                        // Redirect to edit the new campaign
                        setTimeout(() => {
                            window.location.href = `/campaigns/${result.campaign.id}/edit`;
                        }, 1000);
                    } else {
                        throw new Error('Failed to duplicate campaign');
                    }
                    
                } catch (error) {
                    console.error('Duplicate error:', error);
                    this.showNotification('Failed to duplicate campaign', 'error');
                } finally {
                    this.loading = false;
                }
            },

            // Confirm delete
            confirmDelete() {
                this.$dispatch('modal-open', { 
                    id: 'delete-campaign-modal-{{ $campaign->uuid ?? $campaign->id }}'
                });
            },

            // Delete campaign
            async deleteCampaign() {
                if (this.loading) return;
                
                this.loading = true;
                
                try {
                    const response = await fetch(`/campaigns/${campaignId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (response.ok) {
                        this.showNotification('Campaign deleted successfully', 'success');
                        
                        // Redirect to campaigns list
                        setTimeout(() => {
                            window.location.href = '/campaigns/my-campaigns';
                        }, 1000);
                    } else {
                        throw new Error('Failed to delete campaign');
                    }
                    
                } catch (error) {
                    console.error('Delete error:', error);
                    this.showNotification('Failed to delete campaign', 'error');
                } finally {
                    this.loading = false;
                }
            },

            // UI Helper Functions
            getStatusToggleClasses() {
                if (this.currentStatus === 'active') {
                    return 'text-yellow-700 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/30 hover:bg-yellow-200 dark:hover:bg-yellow-900/50';
                } else {
                    return 'text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/30 hover:bg-green-200 dark:hover:bg-green-900/50';
                }
            },

            getStatusIcon() {
                return this.currentStatus === 'active' ? 'fa-pause' : 'fa-play';
            },

            getStatusText() {
                return this.currentStatus === 'active' ? 'Pause' : 'Resume';
            },

            getStatusIndicatorColor() {
                const colors = {
                    'active': 'bg-green-500',
                    'paused': 'bg-yellow-500',
                    'completed': 'bg-blue-500',
                    'draft': 'bg-gray-500',
                    'cancelled': 'bg-red-500'
                };
                return colors[this.currentStatus] || 'bg-gray-500';
            },

            // Update page elements when status changes
            updatePageStatus(newStatus) {
                // Update any status badges on the page
                const statusBadges = document.querySelectorAll('.campaign-status-badge');
                statusBadges.forEach(badge => {
                    badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    badge.className = badge.className.replace(/status-\w+/, `status-${newStatus}`);
                });
                
                // Update any status-dependent elements
                const statusDependentElements = document.querySelectorAll('[data-status-dependent]');
                statusDependentElements.forEach(element => {
                    if (newStatus === 'active') {
                        element.style.display = '';
                    } else {
                        element.style.display = 'none';
                    }
                });
            },

            // Notification system
            showNotification(message, type = 'info') {
                // Create toast notification
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm max-w-sm ${
                    type === 'success' ? 'bg-green-600' : 
                    type === 'error' ? 'bg-red-600' : 
                    type === 'warning' ? 'bg-yellow-600' : 'bg-blue-600'
                }`;
                
                toast.innerHTML = `
                    <div class="flex items-center gap-2">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                        <span>${message}</span>
                    </div>
                `;
                
                document.body.appendChild(toast);
                
                // Auto-remove after 4 seconds
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 4000);
                
                // Add click to dismiss
                toast.addEventListener('click', () => toast.remove());
            },

            // Responsive handling
            init() {
                // Handle responsive behavior
                const handleResize = () => {
                    if (window.innerWidth >= 640) {
                        this.collapsed = false;
                    }
                };
                
                window.addEventListener('resize', handleResize);
                
                // Cleanup on destroy
                this.$cleanup = () => {
                    window.removeEventListener('resize', handleResize);
                };
            }
        };
    }
</script>
@endif