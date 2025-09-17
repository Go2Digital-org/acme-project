<x-layout title="Browse Campaigns">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div x-data="campaignBrowser()">
            {{-- Header Section --}}
            <div class="mb-4 sm:mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div>
                    <h1 class="text-xl sm:text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-1 sm:mb-2">
                        Browse Campaigns
                    </h1>
                    <p class="text-sm sm:text-lg text-gray-600 dark:text-gray-400">
                        Discover meaningful causes and make a difference through your donations
                    </p>
                </div>
                
                <div class="hidden sm:flex flex-col sm:flex-row gap-4">
                    <x-button 
                        variant="primary" 
                        href="{{ route('campaigns.create') }}"
                        icon="fas fa-plus"
                    >
                        Start Campaign
                    </x-button>
                    
                    <x-button 
                        variant="outline" 
                        href="{{ route('campaigns.my-campaigns') }}"
                        icon="fas fa-cog"
                    >
                        Manage Campaigns
                    </x-button>
                </div>
            </div>
        </div>

        {{-- Search and Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg sm:rounded-2xl shadow-sm p-3 sm:p-6 mb-4 sm:mb-8">
            <form method="GET" action="{{ route('campaigns.index') }}" id="search-form">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2 sm:gap-4">
                {{-- Search Input with Autocomplete --}}
                <div class="sm:col-span-2 lg:col-span-4" 
                     x-data="searchAutocomplete({
                         searchEndpoint: '/api/search/suggestions',
                         formAction: '{{ route('campaigns.index') }}',
                         placeholder: 'Search campaigns, organizations, or causes...',
                         minChars: 2,
                         debounceMs: 150,
                         entityType: 'campaign',
                         initialQuery: '{{ request('search', '') }}'
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
                            placeholder="Search campaigns, organizations, or causes..."
                            class="w-full pl-10 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary"
                            autocomplete="off"
                        >
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        
                        {{-- Search loading indicator --}}
                        <div x-show="isLoading || searchLoading" class="absolute right-3 top-1/2 transform -translate-y-1/2">
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
                                            <span class="flex-1" x-html="suggestion.highlightedText || suggestion"></span>
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
                            
                            {{-- Popular searches --}}
                            <template x-if="query.length === 0 && popularSearches.length > 0">
                                <div>
                                    <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 font-medium border-t border-gray-200 dark:border-gray-600">
                                        Trending Searches
                                    </div>
                                    <template x-for="(search, index) in popularSearches" :key="'popular-' + index">
                                        <button
                                            type="button"
                                            @click="selectSuggestion(search)"
                                            @mouseenter="selectedIndex = recentSearches.length + index"
                                            :class="{
                                                'bg-gray-100 dark:bg-gray-700': isSelected(recentSearches.length + index),
                                                'hover:bg-gray-50 dark:hover:bg-gray-700': !isSelected(recentSearches.length + index)
                                            }"
                                            class="w-full px-4 py-3 text-left flex items-center gap-3 transition-colors"
                                        >
                                            <i class="fas fa-fire text-orange-400 text-sm"></i>
                                            <span class="flex-1" x-text="search"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Category Filter --}}
                <div class="sm:col-span-1 lg:col-span-3">
                    <select 
                        name="category_id"
                        value="{{ request('category_id', '') }}"
                        @change="$el.form.submit()"
                        class="w-full py-3 px-4 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary"
                    >
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                                {{ $category->name }}
                                @if($category->campaigns_count > 0)
                                    ({{ $category->campaigns_count }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div class="sm:col-span-1 lg:col-span-2">
                    <select 
                        name="status"
                        value="{{ request('status', '') }}"
                        @change="$el.form.submit()"
                        class="w-full py-3 px-4 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary"
                    >
                        <option value="">All Campaigns</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="ending-soon" @selected(request('status') === 'ending-soon')>Ending Soon</option>
                        <option value="newly-launched" @selected(request('status') === 'newly-launched')>New Campaigns</option>
                        <option value="nearly-funded" @selected(request('status') === 'nearly-funded')>Nearly Funded</option>
                    </select>
                </div>

                {{-- Sort Options --}}
                <div class="lg:col-span-2">
                    <select 
                        name="sort_by"
                        @change="$el.form.submit()"
                        class="w-full py-3 px-4 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary"
                    >
                        <option value="featured" @selected(request('sort_by', 'featured') === 'featured')>Featured First</option>
                        <option value="newest" @selected(request('sort_by', 'featured') === 'newest')>Newest First</option>
                        <option value="ending-soon" @selected(request('sort_by', 'featured') === 'ending-soon')>Ending Soon</option>
                        <option value="most-funded" @selected(request('sort_by', 'featured') === 'most-funded')>Most Funded</option>
                        <option value="least-funded" @selected(request('sort_by', 'featured') === 'least-funded')>Least Funded</option>
                        <option value="alphabetical" @selected(request('sort_by', 'featured') === 'alphabetical')>A-Z</option>
                    </select>
                </div>

            </div>
            </form>

            {{-- Active Filters Display --}}
            @if(request('search') || request('category_id') || request('status') || request('filter'))
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Active filters:</span>
                
                @if(request('search'))
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
                        <i class="fas fa-search text-xs"></i>
                        <span>{{ request('search') }}</span>
                        <a href="{{ route('campaigns.index', array_merge(request()->except('search'), ['category_id' => request('category_id'), 'status' => request('status')])) }}" class="hover:text-blue-900">
                            <i class="fas fa-times text-xs"></i>
                        </a>
                    </span>
                @endif
                
                @if(request('category_id'))
                    @php
                        $selectedCategory = $categories->firstWhere('id', request('category_id'));
                    @endphp
                    @if($selectedCategory)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-primary/10 text-primary rounded-full text-sm">
                            <span>{{ $selectedCategory->name }}</span>
                            <a href="{{ route('campaigns.index', array_merge(request()->except('category_id'), ['search' => request('search'), 'status' => request('status')])) }}" class="hover:text-primary-dark">
                                <i class="fas fa-times text-xs"></i>
                            </a>
                        </span>
                    @endif
                @endif
                
                @if(request('status'))
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-secondary/10 text-secondary rounded-full text-sm">
                        <span>{{ ucfirst(request('status')) }}</span>
                        <a href="{{ route('campaigns.index', array_merge(request()->except('status'), ['search' => request('search'), 'category' => request('category')])) }}" class="hover:text-green-700">
                            <i class="fas fa-times text-xs"></i>
                        </a>
                    </span>
                @endif
                
                @if(request('filter'))
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 rounded-full text-sm">
                        <i class="fas fa-{{ request('filter') === 'favorites' ? 'star' : 'filter' }} text-xs"></i>
                        <span>{{ ucfirst(str_replace('-', ' ', request('filter'))) }}</span>
                        <a href="{{ route('campaigns.index', request()->except('filter')) }}" class="hover:text-amber-900 dark:hover:text-amber-300">
                            <i class="fas fa-times text-xs"></i>
                        </a>
                    </span>
                @endif

                <a href="{{ route('campaigns.index') }}" 
                   class="text-sm text-gray-500 hover:text-gray-700 underline"
                >
                    Clear all
                </a>
            </div>
            @endif
        </div>

        {{-- Results Summary --}}
        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2 mb-4 sm:mb-6">
            <div>
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                    Showing <span class="font-medium">{{ $campaigns->firstItem() ?? 0 }}</span> 
                    to <span class="font-medium">{{ $campaigns->lastItem() ?? 0 }}</span> 
                    of <span class="font-medium">{{ $campaigns->total() }}</span> results
                </p>
            </div>

            {{-- Quick Filter Tags --}}
            <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                <a
                    href="{{ route('campaigns.index', array_merge(request()->all(), ['filter' => 'active-only'])) }}"
                    class="inline-flex items-center flex-shrink-0 px-3 py-1.5 text-xs sm:text-sm {{ request('filter') === 'active-only' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-green-50 dark:bg-green-900/20' }} text-green-700 dark:text-green-400 rounded-full hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors"
                >
                    <i class="fas fa-check-circle mr-1 text-xs"></i>
                    <span>Active</span>
                </a>
                <a
                    href="{{ route('campaigns.index', array_merge(request()->all(), ['filter' => 'popular'])) }}"
                    class="inline-flex items-center flex-shrink-0 px-3 py-1.5 text-xs sm:text-sm {{ request('filter') === 'popular' ? 'bg-gray-200 dark:bg-gray-600' : 'bg-gray-100 dark:bg-gray-700' }} text-gray-600 dark:text-gray-400 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    <i class="fas fa-fire mr-1 text-xs"></i>
                    <span>Popular</span>
                </a>
                <a
                    href="{{ route('campaigns.index', array_merge(request()->all(), ['filter' => 'ending-soon'])) }}"
                    class="inline-flex items-center flex-shrink-0 px-3 py-1.5 text-xs sm:text-sm {{ request('filter') === 'ending-soon' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-red-50 dark:bg-red-900/20' }} text-red-600 rounded-full hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                >
                    <i class="fas fa-clock mr-1 text-xs"></i>
                    <span>Ending Soon</span>
                </a>
                @auth
                <a
                    href="{{ route('campaigns.index', array_merge(request()->all(), ['filter' => 'favorites'])) }}"
                    class="inline-flex items-center flex-shrink-0 px-3 py-1.5 text-xs sm:text-sm {{ request('filter') === 'favorites' ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-yellow-50 dark:bg-yellow-900/20' }} text-yellow-700 dark:text-yellow-400 rounded-full hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-colors"
                >
                    <i class="fas fa-star mr-1 text-xs"></i>
                    <span>Favorites</span>
                </a>
                @endauth
            </div>
        </div>

        {{-- Campaign Grid/List --}}
        <div class="mb-4 sm:mb-8">
            {{-- Loading State --}}
            <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <template x-for="i in 6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm animate-pulse">
                        <div class="h-48 bg-gray-200 dark:bg-gray-700 rounded-xl mb-4"></div>
                        <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded mb-2"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-2/3 mb-4"></div>
                        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded mb-2"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                    </div>
                </template>
            </div>

            {{-- Campaign Grid --}}
            <div 
                x-show="!loading"
                class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3 gap-3 sm:gap-4 lg:gap-5 xl:gap-6"
            >
                @foreach($campaigns->items() as $campaign)
                    <x-campaign-card 
                        :campaign="$campaign" 
                        :href="route('campaigns.show', $campaign->uuid ?? $campaign->id)"
                    />
                @endforeach
            </div>


            {{-- Empty State --}}
            @if($campaigns->isEmpty())
            <div x-show="!loading" class="text-center py-16">
                <div class="w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-search text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">No campaigns found</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Try adjusting your search terms or filters to find what you're looking for.
                </p>
                <x-button variant="outline" onclick="window.location.href='{{ route('campaigns.index') }}'">
                    Clear all filters
                </x-button>
            </div>
            @endif
        </div>

        {{-- Pagination --}}
        @if($campaigns->hasPages())
        <div class="flex items-center justify-center">
            {{ $campaigns->appends(request()->query())->links() }}
        </div>
        @endif
        </div>
    </div>

    <script>
        function campaignBrowser() {
            return {
                // State
                campaigns: @json($campaigns->items()),
                totalPages: {{ $campaigns->lastPage() }},
                currentPage: {{ $campaigns->currentPage() }},
                filteredCampaigns: [],
                searchQuery: '{{ request('search', '') }}',
                selectedCategoryId: '{{ request('category_id', '') }}',
                selectedStatus: '{{ request('status', '') }}',
                currentSort: '{{ $currentSort ?? 'featured' }}',
                loading: false,
                searchLoading: false,
                
                // Computed properties
                get totalCampaigns() {
                    return this.campaigns.length;
                },

                // Methods
                init() {
                    this.filteredCampaigns = [...this.campaigns];
                },

                debouncedSearch: debounce(function() {
                    this.searchLoading = true;
                    setTimeout(() => {
                        this.filterCampaigns();
                        this.searchLoading = false;
                    }, 300);
                }, 300),

                filterCampaigns() {
                    let filtered = [...this.campaigns];

                    // Search filter
                    if (this.searchQuery) {
                        const query = this.searchQuery.toLowerCase();
                        filtered = filtered.filter(campaign => 
                            campaign.title.toLowerCase().includes(query) ||
                            campaign.description.toLowerCase().includes(query) ||
                            campaign.organization_name.toLowerCase().includes(query)
                        );
                    }

                    // Category filter
                    if (this.selectedCategory) {
                        filtered = filtered.filter(campaign => 
                            campaign.category === this.selectedCategory
                        );
                    }

                    // Status filter
                    if (this.selectedStatus) {
                        switch (this.selectedStatus) {
                            case 'ending-soon':
                                filtered = filtered.filter(campaign => {
                                    const daysLeft = Math.ceil((new Date(campaign.end_date) - new Date()) / (1000 * 60 * 60 * 24));
                                    return daysLeft <= 7 && daysLeft > 0;
                                });
                                break;
                            case 'newly-launched':
                                // Simulate newly launched campaigns
                                break;
                            case 'nearly-funded':
                                filtered = filtered.filter(campaign => 
                                    (campaign.current_amount / campaign.goal_amount) >= 0.8
                                );
                                break;
                            default:
                                filtered = filtered.filter(campaign => 
                                    campaign.status === this.selectedStatus
                                );
                        }
                    }

                    this.filteredCampaigns = filtered;
                },

                hasActiveFilters() {
                    return this.selectedCategory || this.selectedStatus || this.searchQuery;
                },

                clearAllFilters() {
                    // Clear the search input in the autocomplete component
                    const searchForm = document.getElementById('search-form');
                    if (searchForm) {
                        // Clear all form inputs
                        const searchInput = searchForm.querySelector('input[name="search"]');
                        const categorySelect = searchForm.querySelector('select[name="category"]');
                        const statusSelect = searchForm.querySelector('select[name="status"]');
                        
                        if (searchInput) searchInput.value = '';
                        if (categorySelect) categorySelect.value = '';
                        if (statusSelect) statusSelect.value = '';
                        
                        // Submit the form to clear all filters server-side
                        searchForm.submit();
                    }
                },

                quickFilter(type) {
                    // Quick filters are handled by the server-side filter parameter
                    // This method is kept for compatibility but no longer needed
                    return;
                },

                getCategoryName(category) {
                    const names = {
                        'education': 'Education',
                        'healthcare': 'Healthcare',
                        'environment': 'Environment',
                        'poverty': 'Poverty Alleviation',
                        'disaster': 'Disaster Relief',
                        'community': 'Community Development',
                        'arts': 'Arts & Culture',
                        'human-rights': 'Human Rights'
                    };
                    return names[category] || category;
                },

                getStatusName(status) {
                    const names = {
                        'active': 'Active',
                        'ending-soon': 'Ending Soon',
                        'newly-launched': 'New Campaigns',
                        'nearly-funded': 'Nearly Funded'
                    };
                    return names[status] || status;
                },

            };
        }

        // Utility function
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>
</x-layout>