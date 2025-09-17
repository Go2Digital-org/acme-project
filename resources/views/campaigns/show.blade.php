@php
    $isDeleted = isset($campaign->deleted_at) && $campaign->deleted_at !== null;
@endphp

<x-layout :title="$campaign->getTitle()">
    {{-- Hero Section --}}
    <section class="relative {{ $isDeleted ? 'opacity-90' : '' }}">
        @if($campaign->featured_image)
            <div class="h-48 sm:h-56 lg:h-64 bg-gray-100 dark:bg-gray-800 overflow-hidden">
                <img 
                    src="{{ $campaign->featured_image_url ?? $campaign->featured_image }}" 
                    alt="{{ $campaign->getTitle() }}"
                    class="h-full w-full object-cover"
                    onerror="this.onerror=null; this.src='{{ asset('images/placeholder.png') }}';"
                />
                <div class="absolute inset-0 bg-black/50"></div>
            </div>
        @else
            <div class="h-48 sm:h-56 lg:h-64 bg-gradient-to-br from-primary/90 to-secondary/90 dark:from-primary-dark/90 dark:to-secondary-dark/90 relative overflow-hidden">
                {{-- Pattern overlay --}}
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.4"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E'); background-size: 60px 60px;"></div>
                </div>
                {{-- Centered icon --}}
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-white/20">
                        <i class="fas fa-hand-holding-heart text-6xl sm:text-8xl"></i>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Floating Content --}}
        <div class="relative mt-8 z-10">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 pb-8">
                {{-- Deleted Campaign Banner --}}
                @if($isDeleted)
                    <div class="max-w-4xl mb-6 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-trash text-xl"></i>
                            <div>
                                <h3 class="font-semibold text-lg">This Campaign Has Been Deleted</h3>
                                <p class="text-red-100 text-sm">
                                    Deleted {{ $campaign->deleted_at->format('M j, Y \a\t g:i A') }} 
                                    ({{ $campaign->deleted_at->diffForHumans() }})
                                </p>
                            </div>
                        </div>
                        @if($campaign->user_id === auth()->id())
                            <button 
                                onclick="confirmRestore({{ $campaign->id }}, '{{ addslashes($campaign->getTitle()) }}')"
                                class="bg-white text-red-600 px-4 py-2 rounded-lg font-medium hover:bg-red-50 transition-colors">
                                <i class="fas fa-undo mr-1"></i>
                                Restore
                            </button>
                        @endif
                    </div>
                @endif

                <div class="max-w-4xl">
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        @if($isDeleted)
                            <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300">
                                <i class="fas fa-trash mr-2"></i>
                                Deleted
                            </span>
                        @else
                            @php
                                // Determine actual display status based on dates
                                [$displayStatus, $displayLabel, $displayIcon] = match(true) {
                                    $campaign->status->value === 'active' && $campaign->start_date->isFuture() => ['pending', 'Not Started', 'hourglass-start'],
                                    $campaign->status->value === 'active' && $campaign->end_date->isPast() => ['expired', 'Ended', 'calendar-times'],
                                    $campaign->status->value === 'active' => ['active', 'Active', 'play'],
                                    $campaign->status->value === 'completed' => ['completed', 'Completed', 'check'],
                                    $campaign->status->value === 'cancelled' => ['cancelled', 'Cancelled', 'ban'],
                                    $campaign->status->value === 'paused' => ['paused', 'Paused', 'pause'],
                                    default => [$campaign->status->value, $campaign->status->getLabel(), 'circle']
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold status-{{ $displayStatus }}">
                                <i class="fas fa-{{ $displayIcon }} mr-2"></i>
                                {{ $displayLabel }}
                            </span>
                        @endif
                        @php
                            $categoryName = null;
                            if ($campaign->categoryModel) {
                                $categoryName = $campaign->categoryModel->getName();
                            }
                            if (!$categoryName && $campaign->category) {
                                $categoryName = ucfirst($campaign->category);
                            }
                        @endphp
                        @if($categoryName)
                            <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium bg-white/20 backdrop-blur-sm text-white">
                                <i class="fas fa-tag mr-2"></i>
                                {{ $categoryName }}
                            </span>
                        @endif
                        @if($daysLeft >= 0)
                            <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium {{ $daysLeft <= 7 ? 'bg-orange-500' : 'bg-white/20 backdrop-blur-sm' }} text-white">
                                <i class="fas fa-clock mr-2"></i>
                                {{ $daysLeft }} days left
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium bg-red-500 text-white">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Campaign Ended
                            </span>
                        @endif
                    </div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4">
                        {{ $campaign->getTitle() }}
                    </h1>
                    <p class="text-xl text-white/90 mb-6 max-w-3xl">
                        Created by <strong>{{ $campaign->creator?->getName() ?? 'Unknown' }}</strong> • {{ $campaign->created_at->format('M j, Y') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Content --}}
    <section class="py-6">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Main Content --}}
                <div class="lg:col-span-2 space-y-8">
                    {{-- Description --}}
                    <x-card>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">About This Campaign</h2>
                        <div class="prose prose-lg max-w-none dark:prose-invert">
                            {!! nl2br(e($campaign->getDescription())) !!}
                        </div>
                    </x-card>

                    {{-- Updates Section --}}
                    @if(isset($campaign->updates) && $campaign->updates->count() > 0)
                        <x-card>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Campaign Updates</h2>
                            <div class="space-y-6">
                                @foreach($campaign->updates->take(3) as $update)
                                    <div class="border-l-4 border-primary pl-6 pb-6 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                                        <div class="flex items-center justify-between mb-3">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ $update->title }}
                                            </h3>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $update->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        <div class="text-gray-700 dark:text-gray-300">
                                            {!! nl2br(e($update->content)) !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </x-card>
                    @endif

                    {{-- Recent Donations --}}
                    @if($recentDonations && $recentDonations->count() > 0)
                        <x-card>
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Recent Donations</h2>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $campaign->donations_count }} total donations
                                </span>
                            </div>
                            
                            <div class="space-y-4">
                                @foreach($recentDonations as $donation)
                                    <div class="flex items-center justify-between py-4 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 bg-secondary/10 rounded-full flex items-center justify-center">
                                                <i class="fas fa-heart text-secondary"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-900 dark:text-white">
                                                    {{ $donation->is_anonymous ? 'Anonymous' : $donation->donor->name }}
                                                </h4>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $donation->created_at->diffForHumans() }}
                                                    @if($donation->message)
                                                        • "{{ Str::limit($donation->message, 50) }}"
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                @formatCurrency($donation->amount)
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            @if($campaign->donations_count > ($recentDonations ? $recentDonations->count() : 0))
                                <div class="mt-6 text-center">
                                    <x-button href="#" variant="outline" size="sm">
                                        View All Donations
                                    </x-button>
                                </div>
                            @endif
                        </x-card>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">
                    {{-- Donation Progress Card with Fancy Progress Bar --}}
                    <x-card>
                        @php
                            $urgencyLevel = match(true) {
                                !$campaign->status->value === 'active' => 'inactive',
                                $daysLeft === 0 => 'critical',
                                $daysLeft <= 3 => 'very-high',
                                $daysLeft <= 7 => 'high',
                                $daysLeft <= 14 => 'medium',
                                default => 'normal'
                            };
                            
                            $showCelebration = $progressPercentage >= 100 || 
                                              ($progressPercentage >= 75 && $campaign->donations_count > 100);
                        @endphp
                        
                        {{-- Fancy Progress Bar --}}
                        <x-fancy-progress-bar
                            :current="$campaign->current_amount"
                            :goal="$campaign->goal_amount"
                            :percentage="$progressPercentage"
                            :showStats="true"
                            :showMilestones="true"
                            :animated="true"
                            size="large"
                            :donorCount="$campaign->donations_count"
                            :daysRemaining="$daysLeft >= 0 ? $daysLeft : null"
                            :urgencyLevel="$urgencyLevel"
                            :showCelebration="$showCelebration"
                            label="Campaign Progress"
                            class="mb-6"
                        />

                        {{-- Action Buttons --}}
                        <div class="space-y-3">
                            @if($isDeleted)
                                <x-button disabled fullWidth size="lg" class="opacity-60 cursor-not-allowed">
                                    <i class="fas fa-trash mr-2"></i>
                                    Campaign Deleted
                                </x-button>
                            @elseif($campaign->canAcceptDonation())
                                <x-button href="{{ route('campaigns.donate', $campaign->uuid ?? $campaign->id) }}" size="lg" fullWidth>
                                    <i class="fas fa-heart mr-2"></i>
                                    Donate Now
                                </x-button>
                            @else
                                @php
                                    $statusInfo = match($campaign->status->value) {
                                        'draft' => ['icon' => 'fa-edit', 'text' => 'Draft - Not Published'],
                                        'pending_approval' => ['icon' => 'fa-hourglass-half', 'text' => 'Pending Approval'],
                                        'rejected' => ['icon' => 'fa-times-circle', 'text' => 'Rejected - Needs Revision'],
                                        'paused' => ['icon' => 'fa-pause-circle', 'text' => 'Campaign Paused'],
                                        'completed' => ['icon' => 'fa-check-circle', 'text' => 'Campaign Completed'],
                                        'cancelled' => ['icon' => 'fa-ban', 'text' => 'Campaign Cancelled'],
                                        'expired' => ['icon' => 'fa-calendar-times', 'text' => 'Campaign Expired'],
                                        default => ['icon' => 'fa-clock', 'text' => 'Campaign Ended']
                                    };
                                @endphp
                                <x-button disabled fullWidth size="lg" class="opacity-60 cursor-not-allowed">
                                    <i class="fas {{ $statusInfo['icon'] }} mr-2"></i>
                                    {{ $statusInfo['text'] }}
                                </x-button>
                            @endif
                            
                            <x-button 
                                variant="outline" 
                                size="lg" 
                                fullWidth
                                @click="$dispatch('share-modal-open', { id: 'share-modal-show' })"
                            >
                                <i class="fas fa-share-alt mr-2"></i>
                                {{ __('common.share_campaign') }}
                            </x-button>

                            {{-- Campaign Management Actions (only for campaign creators) --}}
                            @auth
                                @if($campaign->user_id === auth()->id())
                                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                            <i class="fas fa-user-circle mr-2 text-primary"></i>
                                            Campaign Management
                                        </p>
                                        
                                        @if($isDeleted)
                                            {{-- Deleted Campaign Controls --}}
                                            <div class="space-y-3">
                                                <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-700">
                                                    <div class="flex items-center mb-2">
                                                        <i class="fas fa-trash text-red-500 mr-2"></i>
                                                        <span class="text-sm font-medium text-red-800 dark:text-red-300">Campaign Deleted</span>
                                                    </div>
                                                    <p class="text-xs text-red-600 dark:text-red-400 mb-3">
                                                        Deleted {{ $campaign->deleted_at->diffForHumans() }}
                                                        @if($campaign->donations_count > 0)
                                                            <br>{{ $campaign->donations_count }} donations preserved
                                                        @endif
                                                    </p>
                                                    <button 
                                                        onclick="confirmRestore({{ $campaign->id }}, '{{ addslashes($campaign->getTitle()) }}')"
                                                        class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                                    >
                                                        <i class="fas fa-undo mr-2"></i>
                                                        Restore Campaign
                                                    </button>
                                                </div>

                                                <x-button 
                                                    variant="ghost" 
                                                    size="md" 
                                                    fullWidth
                                                    href="{{ route('campaigns.my-campaigns') }}"
                                                >
                                                    <i class="fas fa-list mr-2"></i>
                                                    My Campaigns
                                                </x-button>
                                            </div>
                                        @else
                                            {{-- Active Campaign Controls --}}
                                            <div class="space-y-2">
                                                {{-- Status Dropdown --}}
                                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg mb-3">
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</span>
                                                    <x-campaign-status-dropdown :campaign="$campaign" :user="auth()->user()" size="sm" />
                                                </div>

                                                <x-button 
                                                    variant="outline" 
                                                    size="md" 
                                                    fullWidth
                                                    href="{{ route('campaigns.edit', $campaign->uuid ?? $campaign->id) }}"
                                                >
                                                    <i class="fas fa-edit mr-2"></i>
                                                    Edit Campaign
                                                </x-button>

                                                <x-button 
                                                    variant="ghost" 
                                                    size="md" 
                                                    fullWidth
                                                    href="{{ route('campaigns.my-campaigns') }}"
                                                >
                                                    <i class="fas fa-list mr-2"></i>
                                                    My Campaigns
                                                </x-button>

                                                <button 
                                                    onclick="confirmDelete({{ $campaign->id }}, '{{ addslashes($campaign->getTitle()) }}')"
                                                    class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                                >
                                                    <i class="fas fa-trash mr-2"></i>
                                                    Delete Campaign
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endauth
                        </div>
                    </x-card>

                    {{-- Campaign Creator Card --}}
                    <x-card>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Campaign Creator</h3>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-bold">
                                {{ $campaign->creator ? substr($campaign->creator->getName(), 0, 1) : '?' }}
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">
                                    {{ $campaign->creator?->getName() ?? 'Unknown' }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $campaign->creator?->job_title ?? 'ACME Employee' }}
                                </p>
                            </div>
                        </div>
                        
                        @if($campaign->creator_note)
                            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <p class="text-sm text-gray-700 dark:text-gray-300 italic">
                                    "{{ $campaign->creator_note }}"
                                </p>
                            </div>
                        @endif
                    </x-card>

                    {{-- Campaign Details --}}
                    <x-card>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Campaign Details</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Created</span>
                                <span class="text-gray-900 dark:text-white">{{ $campaign->created_at->format('M j, Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">End Date</span>
                                <span class="text-gray-900 dark:text-white">{{ $campaign->end_date->format('M j, Y') }}</span>
                            </div>
                            @if($campaign->category)
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Category</span>
                                    <span class="text-gray-900 dark:text-white">{{ ucfirst($campaign->category) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Campaign ID</span>
                                <span class="text-gray-900 dark:text-white font-mono text-sm">#{{ $campaign->id }}</span>
                            </div>
                        </div>
                    </x-card>

                    {{-- Related Campaigns --}}
                    @if($relatedCampaigns && $relatedCampaigns->count() > 0)
                        <x-card>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Related Campaigns</h3>
                            <div class="space-y-4">
                                @foreach($relatedCampaigns as $related)
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                                            @if($related->featured_image)
                                                <img src="{{ $related->featured_image_url }}" alt="{{ $related->title }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <i class="fas fa-hand-holding-heart text-gray-400"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-gray-900 dark:text-white line-clamp-1">
                                                <a href="{{ route('campaigns.show', $related->uuid ?? $related->id) }}" class="hover:text-primary transition-colors">
                                                    {{ $related->title }}
                                                </a>
                                            </h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                @formatCurrency($related->current_amount) raised
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </x-card>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Share Modal --}}
    <x-share-modal :campaign="$campaign" :id="'share-modal-show'" />

    {{-- SweetAlert Scripts for Delete and Restore --}}
    @auth
        @if($campaign->user_id === auth()->id())
            @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                function confirmDelete(campaignId, campaignTitle) {
                    Swal.fire({
                        title: 'Delete Campaign',
                        html: `Are you sure you want to delete "<strong>${campaignTitle}</strong>"?<br><br>
                               <span style="color: #ef4444; font-size: 14px;">
                               This action cannot be undone and the campaign will be permanently removed.
                               All donations associated with this campaign will be preserved.
                               </span>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Yes, Delete Campaign',
                        cancelButtonText: 'Cancel',
                        customClass: {
                            popup: 'rounded-2xl',
                            title: 'text-xl font-semibold',
                            confirmButton: 'rounded-lg px-6 py-3 font-medium',
                            cancelButton: 'rounded-lg px-6 py-3 font-medium'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteCampaign(campaignId);
                        }
                    });
                }

                function confirmRestore(campaignId, campaignTitle) {
                    Swal.fire({
                        title: 'Restore Campaign',
                        html: `Are you sure you want to restore "<strong>${campaignTitle}</strong>"?<br><br>
                               <span style="color: #059669; font-size: 14px;">
                               This campaign will be restored to its previous state and will be visible again.
                               </span>`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#059669',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Yes, Restore Campaign',
                        cancelButtonText: 'Cancel',
                        customClass: {
                            popup: 'rounded-2xl',
                            title: 'text-xl font-semibold',
                            confirmButton: 'rounded-lg px-6 py-3 font-medium',
                            cancelButton: 'rounded-lg px-6 py-3 font-medium'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            restoreCampaign(campaignId);
                        }
                    });
                }

                function deleteCampaign(campaignId) {
                    // Show loading indicator
                    Swal.fire({
                        title: 'Deleting Campaign...',
                        text: 'Please wait while we delete your campaign.',
                        icon: 'info',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Create a form and submit it
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/campaigns/${campaignId}`;
                    
                    // Add CSRF token
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);
                    
                    // Add method override for DELETE
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    form.appendChild(methodInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                }

                function restoreCampaign(campaignId) {
                    // Show loading indicator
                    Swal.fire({
                        title: 'Restoring Campaign...',
                        text: 'Please wait while we restore your campaign.',
                        icon: 'info',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Create a form and submit it
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/campaigns/${campaignId}/restore`;
                    
                    // Add CSRF token
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);
                    
                    // Add method override for PATCH
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'PATCH';
                    form.appendChild(methodInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            </script>
            @endpush
        @endif
    @endauth
</x-layout>