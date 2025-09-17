<nav
    class="sticky top-0 z-50 flex flex-col items-center justify-center px-3 pt-px"
    aria-label="Main Navigation"
>
    <div
        :class="{
            'ring-gray-200/80 backdrop-blur-2xl dark:ring-gray-700/70 bg-white/50 dark:bg-black/50 translate-y-3': scrolled || showMobileMenu,
            'ring-transparent dark:bg-transparent': ! scrolled && ! showMobileMenu,
        }"
        class="mx-auto flex w-full max-w-5xl items-center justify-between gap-5 rounded-2xl px-5 py-4 ring-1 transition duration-200 ease-out xl:max-w-7xl 2xl:max-w-[90rem]"
    >
        {{-- Left side --}}
        <div class="flex items-center gap-3">
            {{-- Logo --}}
            <a
                href="/"
                aria-label="ACME Corp CSR Homepage"
                class="flex items-center gap-2"
            >
                <div class="h-8 w-8 rounded-lg bg-primary flex items-center justify-center text-white font-bold text-lg">
                    A
                </div>
                <span class="text-lg font-bold text-corporate dark:text-white">ACME CSR</span>
                <span class="sr-only">ACME Corp CSR Platform</span>
            </a>
        </div>

        {{-- Right side --}}
        <div class="flex items-center gap-3.5">
            {{-- Search --}}
            <div class="hidden lg:block">
                <button
                    @click="showSearchModal = true"
                    class="flex items-center gap-2 px-3 py-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                    aria-label="Search campaigns"
                >
                    <i class="fas fa-search"></i>
                    <span>{{ __('navigation.search_campaigns') }}</span>
                    <kbd class="text-xs bg-white dark:bg-gray-700 px-1.5 py-0.5 rounded">⌘K</kbd>
                </button>
            </div>

            {{-- Mobile menu toggle --}}
            <button
                @click="showMobileMenu = !showMobileMenu"
                class="flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors lg:hidden"
                aria-label="Toggle mobile menu"
                :aria-expanded="showMobileMenu.toString()"
            >
                <i class="fas fa-bars" x-show="!showMobileMenu"></i>
                <i class="fas fa-times" x-show="showMobileMenu"></i>
            </button>

            {{-- Desktop menu --}}
            <div
                class="hidden items-center gap-3.5 text-sm lg:flex"
                aria-label="Primary navigation"
                x-data="{ 
                    activeDropdown: null
                }"
                @dropdown-opened="
                    if ($event.detail && $event.detail !== activeDropdown) {
                        $dispatch('close-all-dropdowns', $event.detail);
                        activeDropdown = $event.detail;
                    }
                "
            >
                {{-- Campaigns Link --}}
                <a
                    href="{{ route('campaigns.index') }}"
                    @class([
                        'px-3 py-1.5 font-medium rounded-lg transition-colors',
                        'bg-primary text-white' => request()->routeIs('campaigns.*'),
                        'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800' => !request()->routeIs('campaigns.*'),
                    ])
                >
                    <i class="fas fa-heart mr-1"></i>
                    Campaigns
                </a>

                {{-- My Donations and Help moved to user dropdown --}}

                {{-- Notifications --}}
                <div 
                    x-data="notificationDropdown()" 
                    x-init="fetchNotifications()"
                    @click.away="showNotifications = false"
                    @close-all-dropdowns.window="if($event.detail !== 'notifications') showNotifications = false"
                    class="relative"
                >
                    <button
                        @click="
                            if (!showNotifications) {
                                $dispatch('dropdown-opened', 'notifications');
                                showNotifications = true;
                            } else {
                                showNotifications = false;
                            }
                        "
                        class="relative flex items-center justify-center w-10 h-10 rounded-lg text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        aria-label="Notifications"
                        :aria-expanded="showNotifications.toString()"
                    >
                        <i class="fas fa-bell"></i>
                        <span x-show="unreadCount > 0" x-text="unreadCount > 9 ? '9+' : unreadCount" class="absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-medium"></span>
                    </button>
                    
                    {{-- Notifications dropdown --}}
                    <div
                        x-show="showNotifications"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-gray-700 max-h-96 flex flex-col"
                    >
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex-shrink-0">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('notifications.notifications') }}</h3>
                                <button 
                                    x-show="unreadCount > 0"
                                    @click="clearAllNotifications()"
                                    class="text-xs text-primary hover:text-primary-dark"
                                >
                                    {{ __('notifications.mark_all_read') }}
                                </button>
                            </div>
                        </div>
                        
                        <div class="py-2 flex-1 overflow-y-auto">
                            {{-- Loading state --}}
                            <div x-show="loading" class="px-4 py-3 text-center">
                                <i class="fas fa-spinner fa-spin text-gray-400"></i>
                                <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">{{ __('common.loading') }}...</span>
                            </div>
                            
                            {{-- No notifications --}}
                            <div x-show="!loading && notifications.length === 0" class="px-4 py-6 text-center">
                                <i class="fas fa-bell-slash text-gray-300 dark:text-gray-600 text-2xl mb-2"></i>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('notifications.no_notifications') }}</p>
                            </div>
                            
                            {{-- Notifications list --}}
                            <template x-for="notification in notifications" :key="notification.id">
                                <div 
                                    class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                    @click="markAsRead(notification.id, notification.url)"
                                >
                                    <div class="flex items-start gap-3">
                                        <div class="w-2 h-2 rounded-full mt-2 flex-shrink-0" :class="notification.icon_color"></div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="notification.title"></p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="notification.message"></p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1" x-text="notification.time_ago"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <div x-show="notifications.length > 0" class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 flex-shrink-0">
                            <a href="{{ route('notifications.index') }}" class="text-sm text-primary hover:text-primary-dark">{{ __('notifications.view_all_notifications') }}</a>
                        </div>
                    </div>
                </div>

                {{-- Currency and Language Selectors --}}
                <div class="flex items-center gap-2">
                    <x-currency-dropdown-item />
                    <x-language-dropdown-item />
                </div>

                {{-- User Dropdown --}}
                <div x-data="{ open: false }" 
                @click.away="open = false"
                @close-all-dropdowns.window="if($event.detail !== 'user') open = false"
                class="relative">
                    @auth
                        <button
                            @click="
                                if (!open) {
                                    $dispatch('dropdown-opened', 'user');
                                    open = true;
                                } else {
                                    open = false;
                                }
                            "
                            class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
                            aria-expanded="false"
                            :aria-expanded="open.toString()"
                        >
                            <x-user-avatar :user="auth()->user()" size="sm" :clickable="false" :realtime="true" />
                            <span class="hidden sm:block">{{ auth()->user()->name }}</span>
                            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-gray-700 divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <div class="px-4 py-3">
                                <p class="text-sm text-gray-900 dark:text-white font-medium">{{ auth()->user()->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            
                            {{-- Quick Links --}}
                            <div class="py-1">
                                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-tachometer-alt w-4 mr-2"></i>
                                    Dashboard
                                </a>
                                <a href="{{ route('campaigns.my-campaigns') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-bullhorn w-4 mr-2"></i>
                                    My Campaigns
                                </a>
                                <a href="{{ route('donations.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-hand-holding-heart w-4 mr-2"></i>
                                    My Donations
                                </a>
                                <a href="{{ route('profile.show') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-user w-4 mr-2"></i>
                                    Account Settings
                                </a>
                            </div>
                            
                            @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
                                <div class="py-1">
                                    <a href="/admin" class="flex items-center px-4 py-2 text-sm text-primary hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-shield-alt w-4 mr-2"></i>
                                        Admin Panel
                                    </a>
                                </div>
                            @endif
                            
                            <div class="py-1">
                                <form method="POST" action="{{ route('logout') }}" id="logout-form-nav">
                                    @csrf
                                    <button type="submit" class="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-sign-out-alt w-4 mr-2"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary hover:bg-primary-dark transition-colors rounded-lg"
                        >
                            <i class="fas fa-sign-in-alt"></i>
                            Login
                        </a>
                    @endauth
                </div>

                {{-- Theme toggle --}}
                <x-theme-toggle />
            </div>
        </div>
    </div>
    
    {{-- Mobile menu --}}
    <div
        x-show="showMobileMenu"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="lg:hidden fixed inset-0 top-20 bg-white dark:bg-gray-900 z-40 p-4"
    >
        {{-- User Profile Section for Mobile --}}
        @auth
        <div class="flex items-center gap-4 p-4 mb-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <x-user-avatar :user="auth()->user()" size="md" :clickable="true" :realtime="true" />
            <div class="flex-1">
                <p class="font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</p>
            </div>
        </div>
        @endauth
        
        <nav class="flex flex-col space-y-4">
            <a
                href="{{ route('dashboard') }}"
                @class([
                    'flex items-center gap-3 p-3 rounded-lg transition-colors',
                    'bg-primary text-white' => request()->routeIs('dashboard'),
                    'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' => !request()->routeIs('dashboard'),
                ])
                @click="showMobileMenu = false"
            >
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            
            <a
                href="{{ route('campaigns.index') }}"
                @class([
                    'flex items-center gap-3 p-3 rounded-lg transition-colors',
                    'bg-primary text-white' => request()->routeIs('campaigns.index'),
                    'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' => !request()->routeIs('campaigns.index'),
                ])
                @click="showMobileMenu = false"
            >
                <i class="fas fa-heart"></i>
                Browse Campaigns
            </a>
            
            <a
                href="{{ route('campaigns.my-campaigns') }}"
                @class([
                    'flex items-center gap-3 p-3 rounded-lg transition-colors',
                    'bg-primary text-white' => request()->routeIs('campaigns.my-campaigns'),
                    'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' => !request()->routeIs('campaigns.my-campaigns'),
                ])
                @click="showMobileMenu = false"
            >
                <i class="fas fa-bullhorn"></i>
                My Campaigns
            </a>
            
            <a
                href="{{ route('donations.index') }}"
                @class([
                    'flex items-center gap-3 p-3 rounded-lg transition-colors',
                    'bg-primary text-white' => request()->routeIs('donations.*'),
                    'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' => !request()->routeIs('donations.*'),
                ])
                @click="showMobileMenu = false"
            >
                <i class="fas fa-hand-holding-heart"></i>
                My Donations
            </a>
            
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <button
                    @click="showSearchModal = true; showMobileMenu = false"
                    class="flex items-center gap-3 w-full p-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                >
                    <i class="fas fa-search"></i>
                    Search campaigns
                </button>
            </div>
            
            @auth
                @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <a
                            href="/admin"
                            class="flex items-center gap-3 p-3 text-primary hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                            @click="showMobileMenu = false"
                        >
                            <i class="fas fa-shield-alt"></i>
                            Admin Panel
                        </a>
                    </div>
                @endif
            @endauth
            
            {{-- Preferences for Mobile --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 flex items-center gap-2">
                <x-currency-dropdown-item />
                <x-language-dropdown-item />
            </div>
        </nav>
    </div>
</nav>

{{-- Search Modal --}}
<div
    x-show="showSearchModal"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-start justify-center pt-20 bg-black bg-opacity-50"
    @click="showSearchModal = false"
    @keydown.escape.window="showSearchModal = false"
>
    <div
        x-show="showSearchModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="w-full max-w-lg mx-4 bg-white dark:bg-gray-800 rounded-lg shadow-xl"
        @click.stop
        x-data="searchAutocomplete({
            searchEndpoint: '/api/search/suggestions',
            formAction: '{{ route('campaigns.index') }}',
            placeholder: '{{ __('navigation.search_campaigns_placeholder') }}',
            minChars: 2,
            debounceMs: 150
        })"
    >
        <form method="GET" action="{{ route('campaigns.index') }}" class="p-4">
            <div class="relative">
                <input
                    type="text"
                    name="search"
                    x-model="query"
                    @input="handleInput"
                    @keydown="handleKeydown"
                    @focus="openDropdown"
                    :placeholder="placeholder"
                    class="w-full pl-10 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary"
                    autofocus
                    autocomplete="off"
                >
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                
                {{-- Loading indicator --}}
                <div x-show="isLoading" class="absolute right-12 top-1/2 transform -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
                
                <button
                    type="submit"
                    class="absolute right-2 top-1/2 transform -translate-y-1/2 px-3 py-1 bg-primary text-white rounded text-sm hover:bg-primary-dark transition-colors"
                >
                    {{ __('navigation.search') }}
                </button>
                
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
        </form>
        
        <div class="border-t border-gray-200 dark:border-gray-600 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('navigation.quick_filters') }}:</p>
            <div class="flex flex-wrap gap-2 mt-2">
                <a href="{{ route('campaigns.index', ['filter' => 'ending-soon']) }}" 
                   class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                   @click="showSearchModal = false">
                    {{ __('navigation.ending_soon') }}
                </a>
                <a href="{{ route('campaigns.index', ['filter' => 'popular']) }}" 
                   class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                   @click="showSearchModal = false">
                    {{ __('navigation.popular') }}
                </a>
                <a href="{{ route('campaigns.index', ['filter' => 'recent']) }}" 
                   class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                   @click="showSearchModal = false">
                    {{ __('navigation.recent') }}
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function notificationDropdown() {
    return {
        showNotifications: false,
        notifications: [],
        unreadCount: 0,
        loading: false,

        async fetchNotifications() {
            if (!document.querySelector('meta[name="user-authenticated"]')) {
                return; // User not authenticated
            }

            this.loading = true;
            try {
                const response = await fetch('{{ route("notifications.index") }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.notifications = data.notifications || [];
                    this.unreadCount = data.unread_count || 0;
                }
            } catch (error) {
                console.error('Error fetching notifications:', error);
            } finally {
                this.loading = false;
            }
        },

        async markAsRead(notificationId, url) {
            try {
                const response = await fetch(`{{ url('') }}/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (response.ok) {
                    // Remove notification from list
                    this.notifications = this.notifications.filter(n => n.id !== notificationId);
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    
                    // Navigate to URL if provided
                    if (url) {
                        window.location.href = url;
                    }
                    
                    this.showNotifications = false;
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        },

        async clearAllNotifications() {
            try {
                const response = await fetch('{{ route("notifications.clear") }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (response.ok) {
                    this.notifications = [];
                    this.unreadCount = 0;
                    this.showNotifications = false;
                }
            } catch (error) {
                console.error('Error clearing notifications:', error);
            }
        }
    }
}

// Refresh notifications every 30 seconds if user is authenticated
if (document.querySelector('meta[name="user-authenticated"]')) {
    setInterval(() => {
        const notificationComponent = Alpine.store ? 
            document.querySelector('[x-data*="notificationDropdown"]')?.__x?.$data :
            null;
        
        if (notificationComponent && typeof notificationComponent.fetchNotifications === 'function') {
            notificationComponent.fetchNotifications();
        }
    }, 30000);
}
</script>