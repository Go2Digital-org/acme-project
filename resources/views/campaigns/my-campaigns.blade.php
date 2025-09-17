<x-layout title="My Campaigns">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header Section --}}
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Campaigns</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Manage and track your fundraising campaigns</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <x-button
                        variant="outline"
                        href="{{ route('campaigns.index') }}"
                        icon="fas fa-search"
                        size="sm"
                    >
                        <span class="hidden sm:inline">Browse All</span>
                        <span class="sm:hidden">Browse</span>
                    </x-button>

                    <x-button
                        variant="primary"
                        href="{{ route('campaigns.create') }}"
                        icon="fas fa-plus"
                        size="sm"
                    >
                        <span class="hidden sm:inline">Create Campaign</span>
                        <span class="sm:hidden">Create</span>
                    </x-button>
                </div>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
            {{-- Total Campaigns --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">Total</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $stats->totalCampaigns }}</p>
                        <p class="text-xs sm:text-sm text-primary font-medium">
                            {{ $stats->getTotalPublishedCampaigns() }} published
                        </p>
                    </div>
                    <div class="p-2 sm:p-3 bg-primary/10 rounded-xl">
                        <i class="fas fa-bullhorn text-primary text-sm sm:text-xl"></i>
                    </div>
                </div>
            </div>

            {{-- Active Campaigns --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">Active</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $stats->activeCampaigns }}</p>
                        <p class="text-xs sm:text-sm text-green-600 font-medium">Running now</p>
                    </div>
                    <div class="p-2 sm:p-3 bg-green-100 dark:bg-green-900/20 rounded-xl">
                        <i class="fas fa-play text-green-600 text-sm sm:text-xl"></i>
                    </div>
                </div>
            </div>

            {{-- Amount Raised --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">Raised</p>
                        <p class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $stats->getFormattedTotalRaised() }}</p>
                        <p class="text-xs sm:text-sm text-secondary font-medium">{{ $stats->getFormattedSuccessRate() }} success</p>
                    </div>
                    <div class="p-2 sm:p-3 bg-secondary/10 rounded-xl">
                        <i class="fas fa-hand-holding-heart text-secondary text-sm sm:text-xl"></i>
                    </div>
                </div>
            </div>

            {{-- Total Donations --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">Donations</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $stats->totalDonations }}</p>
                        <p class="text-xs sm:text-sm text-accent font-medium">From colleagues</p>
                    </div>
                    <div class="p-2 sm:p-3 bg-accent/10 rounded-xl">
                        <i class="fas fa-users text-accent text-sm sm:text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Attention Needed Section --}}
        @if(count($needingAttention) > 0)
            <div class="mb-8">
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl sm:rounded-2xl p-4 sm:p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-yellow-100 dark:bg-yellow-900/40 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Campaigns Need Attention</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ count($needingAttention) }} campaigns require your review</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach($needingAttention as $item)
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white line-clamp-1">{{ $item['campaign']->getTitle() }}</h4>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        @foreach($item['reasons'] as $reason)
                                            <span class="inline-flex items-center px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-200 rounded-full">
                                                {{ $reason }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <x-button
                                        variant="ghost"
                                        size="sm"
                                        href="{{ route('campaigns.show', $item['campaign']->id) }}"
                                    >
                                        View
                                    </x-button>
                                    <x-button
                                        variant="outline"
                                        size="sm"
                                        href="{{ route('campaigns.edit', $item['campaign']->id) }}"
                                    >
                                        Edit
                                    </x-button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Filters and Search Section --}}
        <div class="mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                <form method="GET" action="{{ route('campaigns.my-campaigns') }}" class="space-y-4">
                    {{-- Search Bar with Autocomplete --}}
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1"
                             x-data="searchAutocomplete({
                                 searchEndpoint: '/api/search/suggestions',
                                 formAction: '{{ route('campaigns.my-campaigns') }}',
                                 placeholder: 'Search your campaigns...',
                                 minChars: 2,
                                 debounceMs: 150,
                                 entityType: 'my-campaign',
                                 initialQuery: '{{ request('search', '') }}',
                                 employeeOnly: true
                             })">
                            <div class="relative">
                                <input
                                    type="text"
                                    name="search"
                                    x-model="query"
                                    @input="handleInput()"
                                    @keydown.enter="$el.form.submit()"
                                    @keydown="handleKeydown"
                                    @focus="openDropdown"
                                    placeholder="Search your campaigns..."
                                    class="w-full pl-10 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary"
                                    autocomplete="off"
                                >
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>

                                {{-- Search loading indicator --}}
                                <div x-show="isLoading" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                    <svg class="animate-spin h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </div>

                                {{-- Autocomplete dropdown --}}
                                <div
                                    x-show="showDropdown && (suggestions.length > 0 || recentSearches.length > 0 || (query.length === 0 && popularSearches.length > 0))"
                                    x-ref="dropdown"
                                    class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-96 overflow-y-auto z-50"
                                    @click.stop
                                >
                                    {{-- Suggestions --}}
                                    <template x-if="query.length >= minChars && suggestions.length > 0">
                                        <div>
                                            <template x-for="(suggestion, index) in suggestions" :key="index">
                                                <button
                                                    type="button"
                                                    @click="selectSuggestion(suggestion)"
                                                    @mouseenter="selectedIndex = index"
                                                    :class="{
                                                        'bg-gray-100 dark:bg-gray-700': isSelected(index),
                                                        'hover:bg-gray-50 dark:hover:bg-gray-700': !isSelected(index)
                                                    }"
                                                    class="w-full px-4 py-3 text-left flex items-center gap-3 transition-colors"
                                                >
                                                    <i :class="getSuggestionIcon(suggestion)" class="text-gray-400 text-sm"></i>
                                                    <span class="flex-1" x-html="suggestion.highlighted || suggestion.text || suggestion"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Recent searches --}}
                                    <template x-if="query.length === 0 && recentSearches.length > 0">
                                        <div>
                                            <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 font-medium">
                                                Recent Searches
                                                <button
                                                    @click="clearRecentSearches"
                                                    class="float-right text-primary hover:text-primary-dark"
                                                >
                                                    Clear
                                                </button>
                                            </div>
                                            <template x-for="(search, index) in recentSearches" :key="'recent-' + index">
                                                <button
                                                    type="button"
                                                    @click="selectSuggestion(search)"
                                                    @mouseenter="selectedIndex = index"
                                                    :class="{
                                                        'bg-gray-100 dark:bg-gray-700': isSelected(index),
                                                        'hover:bg-gray-50 dark:hover:bg-gray-700': !isSelected(index)
                                                    }"
                                                    class="w-full px-4 py-3 text-left flex items-center gap-3 transition-colors"
                                                >
                                                    <i class="fas fa-clock text-gray-400 text-sm"></i>
                                                    <span class="flex-1" x-text="search"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <x-button type="submit" variant="primary" size="sm">
                                <i class="fas fa-search mr-2"></i>Search
                            </x-button>
                            @if(!empty($currentFilters))
                                <x-button
                                    variant="ghost"
                                    size="sm"
                                    href="{{ route('campaigns.my-campaigns') }}"
                                >
                                    Clear
                                </x-button>
                            @endif
                        </div>
                    </div>

                    {{-- Show Deleted Campaigns Toggle --}}
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center space-x-3">
                            @php
                                $isEnabled = isset($currentFilters['show_deleted']) && $currentFilters['show_deleted'];
                            @endphp

                            <button type="button"
                                    class="{{ $isEnabled ? 'bg-red-600' : 'bg-gray-200' }} relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
                                    role="switch"
                                    aria-checked="{{ $isEnabled ? 'true' : 'false' }}"
                                    onclick="document.getElementById('show_deleted').click()">
                                <span class="sr-only">Show deleted campaigns</span>
                                <span class="{{ $isEnabled ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                    <span class="{{ $isEnabled ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in' }} absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                                            <path d="m4 8 2-2m0 0 2-2M6 6 4 4m2 2 2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <span class="{{ $isEnabled ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out' }} absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true">
                                        <svg class="h-3 w-3 text-red-600" fill="currentColor" viewBox="0 0 12 12">
                                            <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                                        </svg>
                                    </span>
                                </span>
                            </button>

                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                <i class="fas fa-trash mr-2 text-red-500"></i>
                                Show Deleted Campaigns
                            </span>

                            <input
                                type="checkbox"
                                id="show_deleted"
                                name="show_deleted"
                                value="1"
                                {{ $isEnabled ? 'checked' : '' }}
                                class="sr-only"
                                onchange="this.form.submit()"
                            />
                        </div>

                        @if(isset($currentFilters['show_deleted']) && $currentFilters['show_deleted'])
                            <div class="text-xs text-red-600 dark:text-red-400 flex items-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                Showing deleted campaigns
                            </div>
                        @endif
                    </div>

                    {{-- Quick Filter Tags --}}
                    <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                        <a href="{{ route('campaigns.my-campaigns', array_merge(request()->all(), ['filter' => 'active'])) }}"
                           class="inline-flex items-center flex-shrink-0 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-full transition-colors {{ ($currentFilters['filter'] ?? '') === 'active' ? 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                            <i class="fas fa-play mr-1 text-xs"></i>
                            Active
                        </a>

                        <a href="{{ route('campaigns.my-campaigns', array_merge(request()->all(), ['filter' => 'draft'])) }}"
                           class="inline-flex items-center flex-shrink-0 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-full transition-colors {{ ($currentFilters['filter'] ?? '') === 'draft' ? 'bg-gray-100 dark:bg-gray-900/20 text-gray-800 dark:text-gray-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                            <i class="fas fa-edit mr-1 text-xs"></i>
                            Drafts
                        </a>

                        <a href="{{ route('campaigns.my-campaigns', array_merge(request()->all(), ['filter' => 'completed'])) }}"
                           class="inline-flex items-center flex-shrink-0 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-full transition-colors {{ ($currentFilters['filter'] ?? '') === 'completed' ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                            <i class="fas fa-check mr-1 text-xs"></i>
                            Completed
                        </a>

                        <a href="{{ route('campaigns.my-campaigns', array_merge(request()->all(), ['filter' => 'ending-soon'])) }}"
                           class="inline-flex items-center flex-shrink-0 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-full transition-colors {{ ($currentFilters['filter'] ?? '') === 'ending-soon' ? 'bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                            <i class="fas fa-clock mr-1 text-xs"></i>
                            Ending Soon
                        </a>

                        <a href="{{ route('campaigns.my-campaigns', array_merge(request()->all(), ['filter' => 'popular'])) }}"
                           class="inline-flex items-center flex-shrink-0 px-3 py-1.5 text-xs sm:text-sm font-medium rounded-full transition-colors {{ ($currentFilters['filter'] ?? '') === 'popular' ? 'bg-purple-100 dark:bg-purple-900/20 text-purple-800 dark:text-purple-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                            <i class="fas fa-fire mr-1 text-xs"></i>
                            Popular
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Campaigns Grid --}}
        @if($campaigns->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                @foreach($campaigns as $campaign)
                    @php
                        $isDeleted = isset($campaign->deleted_at) && $campaign->deleted_at !== null;
                    @endphp
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-200 flex flex-col h-full {{ $isDeleted ? 'opacity-75 ring-2 ring-red-200 dark:ring-red-800' : '' }}">
                        {{-- Deleted Campaign Banner (Non-blocking) --}}
                        @if($isDeleted)
                            <div class="absolute top-2 left-2 right-2 bg-red-500 text-white px-3 py-1 rounded-lg text-xs font-medium shadow-lg z-10 flex items-center justify-between">
                                <span>
                                    <i class="fas fa-trash mr-1"></i>
                                    DELETED
                                </span>
                                <span class="text-red-200 text-[10px]">
                                    {{ $campaign->deleted_at->diffForHumans() }}
                                </span>
                            </div>
                        @endif

                        {{-- Campaign Image --}}
                        <div class="relative h-48 rounded-t-2xl overflow-hidden bg-gradient-to-br from-primary/20 to-secondary/20 {{ $isDeleted ? 'grayscale' : '' }}">
                            @if($campaign->image_url)
                                <img src="{{ $campaign->image_url }}" alt="{{ $campaign->getTitle() }}" class="w-full h-full object-cover {{ $isDeleted ? 'grayscale' : '' }}">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-heart text-6xl text-primary/30 {{ $isDeleted ? 'text-gray-400' : '' }}"></i>
                                </div>
                            @endif

                            {{-- Status Badge --}}
                            <div class="absolute top-3 left-3">
                                @if($isDeleted)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300">
                                        <i class="fas fa-trash mr-1"></i>
                                        Deleted
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $campaign->status->value === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : '' }}
                                        {{ $campaign->status->value === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                        {{ $campaign->status->value === 'completed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300' : '' }}
                                        {{ $campaign->status->value === 'paused' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' : '' }}
                                        {{ $campaign->status->value === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' : '' }}">
                                        {{ $campaign->status->getLabel() }}
                                    </span>
                                @endif
                            </div>

                            {{-- Quick Actions --}}
                            <div class="absolute top-3 right-3 flex gap-2">
                                @if($isDeleted)
                                    <button
                                        onclick="confirmRestore({{ $campaign->id }}, '{{ addslashes($campaign->getTitle()) }}')"
                                        class="p-2 bg-green-500 text-white rounded-lg shadow-sm hover:bg-green-600 transition-colors group"
                                        title="Restore Campaign">
                                        <i class="fas fa-undo text-sm"></i>
                                    </button>
                                @else
                                    <a href="{{ route('campaigns.edit', $campaign->uuid ?? $campaign->id) }}"
                                       class="p-2 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-lg shadow-sm hover:bg-white dark:hover:bg-gray-800 transition-colors group"
                                       title="Edit Campaign">
                                        <i class="fas fa-edit text-sm text-gray-600 dark:text-gray-300 group-hover:text-primary"></i>
                                    </a>

                                    <button
                                        onclick="confirmDelete({{ $campaign->id }}, '{{ addslashes($campaign->getTitle()) }}')"
                                        class="p-2 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-lg shadow-sm hover:bg-white dark:hover:bg-gray-800 transition-colors group"
                                        title="Delete Campaign">
                                        <i class="fas fa-trash text-sm text-gray-600 dark:text-gray-300 group-hover:text-red-500"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Campaign Content --}}
                        <div class="flex-1 p-6 flex flex-col">
                            {{-- Title (Always Clickable for Authors) --}}
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-2 line-clamp-2 min-h-[3.5rem] {{ $isDeleted ? 'line-through text-gray-600 dark:text-gray-300' : '' }}">
                                <a href="{{ route('campaigns.show', $campaign->uuid ?? $campaign->id) }}" class="hover:text-primary transition-colors {{ $isDeleted ? 'hover:text-gray-800 dark:hover:text-gray-200' : '' }}">
                                    {{ $campaign->getTitle() }}
                                </a>
                            </h3>

                            {{-- Description --}}
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-3 flex-1">
                                {{ Str::limit($campaign->getDescription(), 120) }}
                            </p>

                            {{-- Progress Bar --}}
                            <div class="mb-4">
                                @php
                                    $progressPercentage = $campaign->goal_amount > 0 ? min(100, ($campaign->current_amount / $campaign->goal_amount) * 100) : 0;
                                @endphp
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        @formatCurrency($campaign->current_amount)
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ number_format($progressPercentage, 0) }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-primary to-secondary h-2 rounded-full transition-all duration-500"
                                         style="width: {{ $progressPercentage }}%"></div>
                                </div>
                                <div class="flex justify-between items-center mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span>Goal: @formatCurrency($campaign->goal_amount)</span>
                                    <span>{{ $campaign->donations_count }} {{ Str::plural('donation', $campaign->donations_count) }}</span>
                                </div>
                            </div>

                            {{-- Footer with Days Left and Status --}}
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    @if($isDeleted)
                                        <div class="space-y-1">
                                            <div class="text-red-500 dark:text-red-400 flex items-center">
                                                <i class="fas fa-trash mr-1"></i>
                                                <span class="font-medium">Deleted {{ $campaign->deleted_at->format('M j, Y') }}</span>
                                            </div>
                                            <div class="text-xs text-gray-400 dark:text-gray-500 flex items-center space-x-3">
                                                <span>
                                                    <i class="fas fa-clock mr-1"></i>
                                                    {{ $campaign->deleted_at->diffForHumans() }}
                                                </span>
                                                @if($campaign->donations_count > 0)
                                                    <span>
                                                        <i class="fas fa-heart mr-1"></i>
                                                        {{ $campaign->donations_count }} donations preserved
                                                    </span>
                                                @endif
                                            </div>
                                            @if(isset($campaign->status))
                                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Was: <span class="capitalize">{{ $campaign->status->value }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($campaign->status->value === 'active' && $campaign->end_date && $campaign->end_date->isFuture())
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ $campaign->end_date->diffForHumans() }}
                                    @elseif($campaign->status->value === 'active' && $campaign->end_date && $campaign->end_date->isPast())
                                        <span class="text-red-500">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Expired
                                        </span>
                                    @else
                                        <i class="fas fa-calendar mr-1"></i>
                                        {{ $campaign->created_at->format('M j, Y') }}
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    @if(!$isDeleted)
                                        <x-campaign-status-dropdown :campaign="$campaign" :user="auth()->user()" size="sm" />
                                    @endif

                                    @if($isDeleted)
                                        <button
                                            onclick="confirmRestore({{ $campaign->id }}, '{{ addslashes($campaign->getTitle()) }}')"
                                            class="text-xs text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 font-medium flex items-center transition-colors">
                                            <i class="fas fa-undo mr-1"></i>
                                            Restore
                                        </button>
                                    @else
                                        <a href="{{ route('campaigns.show', $campaign->uuid ?? $campaign->id) }}"
                                           class="text-xs text-primary hover:text-primary-dark font-medium">
                                            View Details
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="flex justify-center">
                {{ $campaigns->appends(request()->query())->links() }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-bullhorn text-gray-400 text-3xl"></i>
                </div>

                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    @if(!empty($currentFilters))
                        No campaigns found
                    @else
                        No campaigns yet
                    @endif
                </h3>

                <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                    @if(!empty($currentFilters))
                        Try adjusting your filters or search terms to find what you're looking for.
                    @else
                        Start making an impact by creating your first fundraising campaign for a cause you believe in.
                    @endif
                </p>

                @if(empty($currentFilters))
                    <x-button
                        variant="primary"
                        href="{{ route('campaigns.create') }}"
                        icon="fas fa-plus"
                    >
                        Create Your First Campaign
                    </x-button>
                @else
                    <x-button
                        variant="outline"
                        href="{{ route('campaigns.my-campaigns') }}"
                    >
                        Clear Filters
                    </x-button>
                @endif
            </div>
        @endif
    </div>


    @push('scripts')
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
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                focusCancel: true,
                customClass: {
                    popup: 'dark:bg-gray-800',
                    title: 'dark:text-white',
                    htmlContainer: 'dark:text-gray-300',
                    confirmButton: 'hover:bg-red-700',
                    cancelButton: 'hover:bg-gray-600'
                },
                buttonsStyling: true
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
                confirmButtonText: 'Yes, restore it!',
                cancelButtonText: 'Cancel',
                focusCancel: false,
                customClass: {
                    popup: 'dark:bg-gray-800',
                    title: 'dark:text-white',
                    htmlContainer: 'dark:text-gray-300',
                    confirmButton: 'hover:bg-green-700',
                    cancelButton: 'hover:bg-gray-600'
                },
                buttonsStyling: true
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
                customClass: {
                    popup: 'dark:bg-gray-800',
                    title: 'dark:text-white',
                    text: 'dark:text-gray-300'
                },
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
                customClass: {
                    popup: 'dark:bg-gray-800',
                    title: 'dark:text-white',
                    text: 'dark:text-gray-300'
                },
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
</x-layout>