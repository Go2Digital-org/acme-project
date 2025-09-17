
<article
    {{ $attributes->merge([
        'class' => "group relative z-0 flex flex-col overflow-hidden rounded-lg sm:rounded-xl bg-white dark:bg-gray-800 shadow-sm hover:shadow-lg transition-all duration-300 " . ($cardClasses ?? 'p-3 sm:p-4 lg:p-5') . " " . ($href ? 'cursor-pointer sm:hover:scale-[1.02]' : ''),
        'aria-labelledby' => "campaign-title-{$campaign->id}"
    ]) }}
    @if($href)
        onclick="window.location.href='{{ $href }}'"
        role="button"
        tabindex="0"
        @keydown.enter="window.location.href='{{ $href }}'"
        @keydown.space.prevent="window.location.href='{{ $href }}'"
    @endif
>
    {{-- Status and Category badges --}}
    <div class="absolute top-2 right-2 sm:top-4 sm:right-4 z-10 flex items-center gap-1 sm:gap-2">
        {{-- Category badge --}}
        @php
            $categoryName = null;
            if ($campaign->categoryModel) {
                $categoryName = $campaign->categoryModel->getName();
            }
            if (!$categoryName && isset($campaign->category) && $campaign->category) {
                $categoryName = ucfirst($campaign->category);
            }
        @endphp
        @if($categoryName)
            <span class="px-1.5 py-0.5 sm:px-2 sm:py-1 text-[10px] sm:text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full">
                {{ $categoryName }}
            </span>
        @endif
        
        {{-- Status badge --}}
        @php
            // Determine actual display status based on dates
            [$displayStatus, $displayLabel] = match(true) {
                $campaign->status->value === 'active' && $campaign->start_date->isFuture() => ['pending', 'Not Started'],
                $campaign->status->value === 'active' && $campaign->end_date->isPast() => ['expired', 'Ended'],
                $campaign->status->value === 'active' => ['active', 'Active'],
                $campaign->status->value === 'completed' => ['completed', 'Completed'],
                $campaign->status->value === 'cancelled' => ['cancelled', 'Cancelled'],
                $campaign->status->value === 'paused' => ['paused', 'Paused'],
                default => [$campaign->status->value, $campaign->status->getLabel()]
            };
            
            $statusColor = match($displayStatus) {
                'active' => 'bg-green-500',
                'pending' => 'bg-blue-500',
                'completed' => 'bg-blue-500',
                'expired' => 'bg-gray-500',
                'cancelled' => 'bg-red-500',
                'paused' => 'bg-yellow-500',
                default => 'bg-gray-400'
            };
        @endphp
        <span class="px-1.5 py-0.5 sm:px-2 sm:py-1 text-[10px] sm:text-xs font-medium text-white rounded-full {{ $statusColor }}">
            {{ $displayLabel }}
        </span>
        
        {{-- Featured badge --}}
        @if($campaign->featured_image !== null)
            <span class="px-1.5 py-0.5 sm:px-2 sm:py-1 text-[10px] sm:text-xs font-medium bg-yellow-400 text-yellow-900 rounded-full">
                Featured
            </span>
        @endif
    </div>

    {{-- Main content container --}}
    <div class="flex-1 flex flex-col">
        {{-- Campaign image --}}
        <div class="aspect-w-16 aspect-h-9 mb-2 sm:mb-3 rounded-md sm:rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-700">
            <img
                src="{{ $campaign->featured_image ?: asset('images/placeholder.png') }}"
                alt="Campaign image for {{ $campaign->getTitle() }}"
                class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
                loading="lazy"
                onerror="this.onerror=null; this.src='{{ asset('images/placeholder.png') }}';"
            >
        </div>

        {{-- Header --}}
        <div class="flex items-start justify-between gap-2 sm:gap-3 mb-2 sm:mb-3">
            {{-- Title --}}
            <div class="flex-1">
                <h3
                    id="campaign-title-{{ $campaign->id }}"
                    class="text-sm sm:text-base lg:text-lg font-semibold leading-tight text-gray-900 dark:text-white line-clamp-1 sm:line-clamp-2 group-hover:text-primary dark:group-hover:text-primary transition-colors"
                >
                    {{ $campaign->getTitle() }}
                </h3>
                
                @if($campaign->organization)
                    <p class="text-[10px] sm:text-xs lg:text-sm text-gray-600 dark:text-gray-400 mt-0.5 sm:mt-1 truncate">
                        by {{ $campaign->organization->getName() }}
                    </p>
                @endif
            </div>

            {{-- Arrow icon for clickable cards --}}
            @if($href)
                <div class="flex-shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 group-hover:text-primary sm:group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            @endif
        </div>

        {{-- Description with fixed height --}}
        <div class="h-[40px] sm:h-[48px] lg:h-[52px] mb-2 sm:mb-3 overflow-hidden">
            @if($campaign->getDescription())
                <p class="text-gray-700 dark:text-gray-300 text-xs sm:text-sm leading-snug sm:leading-relaxed line-clamp-2">
                    {{ $campaign->getDescription() }}
                </p>
            @else
                <p class="text-gray-400 dark:text-gray-600 text-xs sm:text-sm italic">
                    No description available
                </p>
            @endif
        </div>

        {{-- Progress Section with consistent spacing --}}
        <div class="flex-1 flex flex-col justify-end">
            @php
                $daysRemaining = $campaign->getDaysRemaining();
                $urgencyLevel = match(true) {
                    !$campaign->isActive() => 'inactive',
                    $daysRemaining === 0 => 'critical',
                    $daysRemaining <= 3 => 'very-high',
                    $daysRemaining <= 7 => 'high',
                    $daysRemaining <= 14 => 'medium',
                    default => 'normal'
                };
                
                $showCelebration = $campaign->getProgressPercentage() >= 100 || 
                                  ($campaign->getProgressPercentage() >= 75 && $campaign->donations_count > 50);
            @endphp
            
            <x-fancy-progress-bar
                :current="$campaign->current_amount"
                :goal="$campaign->goal_amount"
                :percentage="$campaign->getProgressPercentage()"
                :showStats="true"
                :showMilestones="false"
                :animated="true"
                size="small"
                :donorCount="$campaign->donations_count ?? 0"
                :daysRemaining="$campaign->isActive() ? $daysRemaining : null"
                :urgencyLevel="$urgencyLevel"
                :showCelebration="$showCelebration"
                :showInlinePercentage="false"
                class="campaign-card-progress"
            />
        </div>
    </div>

    {{-- Actions --}}
    @if(($showActions ?? true) && $campaign->canAcceptDonation())
        <div class="flex gap-1 sm:gap-2 pt-2 sm:pt-3 border-t border-gray-100 dark:border-gray-700 mt-2">
            <button
                onclick="event.stopPropagation(); window.location.href='{{ route('campaigns.donate', $campaign->uuid ?? $campaign->id) }}'"
                class="flex-1 bg-primary text-white px-2 py-1.5 sm:px-3 sm:py-2 rounded-md sm:rounded-lg font-medium text-xs sm:text-sm hover:bg-primary-dark transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
            >
                <i class="fas fa-heart mr-1 sm:mr-2 text-xs sm:text-sm"></i>
                <span class="hidden sm:inline">Donate Now</span>
                <span class="sm:hidden">Donate</span>
            </button>
            
            <button
                x-data
                onclick="event.stopPropagation()"
                @click="$dispatch('share-modal-open', { id: 'share-modal-{{ $campaign->id }}' })"
                class="px-2 py-1.5 sm:px-3 sm:py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md sm:rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300"
                aria-label="Share campaign"
                title="Share campaign"
            >
                <i class="fas fa-share-alt text-xs sm:text-sm"></i>
            </button>
            
            <button
                onclick="event.stopPropagation(); toggleFavorite('{{ $campaign->id }}')"
                data-campaign-id="{{ $campaign->id }}"
                class="bookmark-button px-2 py-1.5 sm:px-3 sm:py-2 text-gray-600 dark:text-gray-400 hover:text-red-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md sm:rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300"
                aria-label="Add to favorites"
                title="Add to favorites"
            >
                <i class="far fa-bookmark text-xs sm:text-sm"></i>
            </button>
        </div>
    @elseif(!$campaign->isActive())
        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
            <div class="text-center py-2 text-gray-500 dark:text-gray-400 text-sm">
                <i class="fas fa-clock mr-2"></i>
                This campaign has ended
            </div>
        </div>
    @endif

    {{-- Hover effect decoration --}}
    <div class="absolute -left-10 -top-10 -z-10 h-3/4 w-40 rounded-full bg-primary/5 opacity-0 blur-3xl transition duration-300 group-hover:opacity-100"></div>
</article>

{{-- Share Modal for this campaign --}}
@if(($showActions ?? true) && $campaign->isActive())
    <x-share-modal 
        :campaign="$campaign" 
        :id="'share-modal-' . $campaign->id"
    />
@endif