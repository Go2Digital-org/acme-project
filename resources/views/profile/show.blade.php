<x-layout title="{{ __('profile.profile_settings') }}">
    {{-- Hero Section --}}
    <section class="relative bg-gradient-to-br from-primary/10 via-transparent to-primary/5 py-16">
        <div class="absolute inset-0 bg-grid-slate-100 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.5))] dark:bg-grid-slate-700/25 dark:[mask-image:linear-gradient(0deg,rgba(255,255,255,0.1),rgba(255,255,255,0.5))]"></div>

        <div class="container relative">
            <div class="flex flex-col lg:flex-row items-center gap-8">
                {{-- User Avatar & Quick Stats --}}
                <div class="w-full lg:w-1/3">
                    <div class="text-center lg:text-left">
                        <div class="relative inline-block mb-6">
                            <div class="w-40 h-40 rounded-full bg-gradient-to-br from-primary to-purple-600 p-1">
                                <div class="w-full h-full rounded-full bg-white dark:bg-dark overflow-hidden">
                                    <x-user-avatar 
                                        :user="$user" 
                                        size="4xl" 
                                        :clickable="true" 
                                        :realtime="true"
                                        class="w-full h-full"
                                    />
                                </div>
                            </div>
                            <div class="absolute bottom-0 right-0 w-10 h-10 bg-green-500 rounded-full border-4 border-white dark:border-dark flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>

                        <h1 class="text-3xl font-bold text-dark dark:text-white mb-2">{{ $user->name }}</h1>
                        <p class="text-body-color mb-6">{{ $user->email }}</p>

                        <div class="flex flex-wrap gap-4 justify-center lg:justify-start">
                            <div class="px-4 py-2 dark:bg-gray-800 rounded-lg shadow-one">
                                <p class="text-xs text-body-color">{{ __('profile.member_since') }}</p>
                                <p class="font-semibold text-dark dark:text-white">{{ $user->created_at->format('M Y') }}</p>
                            </div>
                            <div class="px-4 py-2 dark:bg-gray-800 rounded-lg shadow-one">
                                <p class="text-xs text-body-color">{{ __('profile.account_type') }}</p>
                                <p class="font-semibold text-dark dark:text-white">{{ ucwords(str_replace('_', ' ', $profile['roles'][0] ?? 'Employee')) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Welcome Message --}}
                <div class="w-full lg:w-2/3">
                    <div class="dark:bg-gray-800 rounded-2xl p-8 shadow-one">
                        <h2 class="text-2xl font-bold text-dark dark:text-white mb-4">
                            {{ __('profile.welcome_back', ['name' => explode(' ', $user->name)[0]]) }}
                        </h2>
                        <p class="text-body-color mb-6">
                            {{ __('profile.manage_account_desc') }}
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <button onclick="document.getElementById('logout-form-profile').submit();" class="inline-flex items-center px-6 py-3 bg-gray-100 dark:bg-gray-700 text-body-color dark:text-body-color rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                {{ __('profile.sign_out') }}
                            </button>
                        </div>
                        <form id="logout-form-profile" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Profile Settings Tabs --}}
    <section class="py-8">
        <div class="container">
            <div class="max-w-6xl mx-auto">
                {{-- Tab Navigation --}}
                <script>
                    (function() {
                        // Track the current active tab globally - load from localStorage or default to 'general'
                        const savedTab = localStorage.getItem('profileActiveTab');
                        window.currentActiveTab = savedTab || 'general';
                        
                        window.switchTab = function(tabName, skipSave = false) {
                            // Update global active tab
                            window.currentActiveTab = tabName;
                            
                            // Save tab to localStorage (unless skipping for initial load)
                            if (!skipSave) {
                                localStorage.setItem('profileActiveTab', tabName);
                            }
                            
                            // Hide all tab panels
                            document.querySelectorAll('.tab-panel').forEach(panel => {
                                panel.classList.add('hidden');
                            });

                            // Remove active state from all tabs
                            document.querySelectorAll('.tab-button').forEach(button => {
                                button.classList.remove('border-primary', 'text-primary');
                                button.classList.add('border-transparent', 'text-body-color', 'hover:text-dark', 'hover:border-gray-300', 'dark:text-body-color', 'dark:hover:text-white');
                            });

                            // Show selected tab panel
                            const tabPanel = document.getElementById(tabName + '-content');
                            if (tabPanel) {
                                tabPanel.classList.remove('hidden');
                            }

                            // Add active state to selected tab
                            const activeTab = document.getElementById(tabName + '-tab');
                            if (activeTab) {
                                activeTab.classList.remove('border-transparent', 'text-body-color', 'hover:text-dark', 'hover:border-gray-300', 'dark:text-body-color', 'dark:hover:text-white');
                                activeTab.classList.add('border-primary', 'text-primary');
                            }

                            // Update all Alpine components with activeTab data
                            document.querySelectorAll('[x-data*="activeTab"]').forEach(el => {
                                if (el.__x && el.__x.$data.activeTab !== tabName) {
                                    el.__x.$data.activeTab = tabName;
                                }
                            });
                            
                            // Dispatch event for tab change
                            window.dispatchEvent(new CustomEvent('tabChanged', { detail: { tabName: tabName } }));
                        };
                        
                        // Approach 1: DOMContentLoaded
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', function() {
                                window.switchTab(window.currentActiveTab, true);
                            });
                        } else {
                            // DOM already loaded
                            setTimeout(() => {
                                window.switchTab(window.currentActiveTab, true);
                            }, 100);
                        }
                        
                        // Approach 2: Also try on window load as backup
                        window.addEventListener('load', function() {
                            // Don't call if already initialized
                            const visiblePanel = document.querySelector('.tab-panel:not(.hidden)');
                            if (!visiblePanel) {
                                window.switchTab(window.currentActiveTab, true);
                            }
                        });
                    })();
                </script>
                <div class="mb-8 relative z-50" x-data="{ 
                    mobileMenuOpen: false, 
                    activeTab: window.currentActiveTab || 'general',
                    init() {
                        // Set initial tab from localStorage
                        this.activeTab = window.currentActiveTab;
                        
                        // Watch for tab changes but don't duplicate saves
                        this.$watch('activeTab', value => {
                            if (window.currentActiveTab !== value) {
                                window.currentActiveTab = value;
                            }
                        });
                        
                        // Update when tab changes from other sources
                        window.addEventListener('tabChanged', (e) => {
                            if (this.activeTab !== e.detail.tabName) {
                                this.activeTab = e.detail.tabName;
                            }
                        });
                    }
                }">
                    {{-- Mobile Dropdown --}}
                    <div class="lg:hidden relative">
                        <button @click="mobileMenuOpen = !mobileMenuOpen"
                                class="w-full flex items-center justify-between p-4 bg-gradient-to-r from-primary/5 to-purple-600/5 dark:from-primary/10 dark:to-purple-600/10 backdrop-blur-sm border border-primary/20 dark:border-primary/30 rounded-xl shadow-one hover:shadow-xl transition-all duration-300">
                            <span class="flex items-center">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary to-purple-600 flex items-center justify-center mr-3">
                                    <svg x-show="activeTab === 'general'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <svg x-show="activeTab === 'security'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <svg x-show="activeTab === 'sessions'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <svg x-show="activeTab === 'danger'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <span x-text="activeTab === 'general' ? '{{ __('profile.profile') }}' : 
                                             activeTab === 'security' ? '{{ __('profile.security') }}' : 
                                             activeTab === 'sessions' ? '{{ __('profile.sessions') }}' : 
                                             activeTab === 'danger' ? '{{ __('profile.advanced') }}' : '{{ __('profile.profile') }}'" 
                                      class="font-semibold text-dark dark:text-white text-lg"></span>
                            </span>
                            <svg class="w-6 h-6 text-primary dark:text-primary/80 transition-transform duration-300" :class="{'rotate-180': mobileMenuOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="mobileMenuOpen"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 -translate-y-2"
                             x-transition:enter-end="transform opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="transform opacity-100 translate-y-0"
                             x-transition:leave-end="transform opacity-0 -translate-y-2"
                             @click.away="mobileMenuOpen = false"
                             class="absolute z-50 mt-3 w-full bg-white dark:bg-dark border border-gray-100 dark:border-gray-700 rounded-xl shadow-2xl overflow-hidden">
                            <div class="py-1 bg-transparent">
                                <button @click="window.switchTab('general'); mobileMenuOpen = false; activeTab = 'general'"
                                        :class="activeTab === 'general' ? 'bg-gradient-to-r from-primary/10 to-purple-600/10 dark:from-primary/20 dark:to-purple-600/20 border-l-4 border-primary' : 'bg-transparent hover:bg-gray-50 dark:hover:bg-gray-800'"
                                        class="w-full px-4 py-3.5 text-left flex items-center transition-all duration-200 group text-dark dark:text-white">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3 transition-colors"
                                         :class="activeTab === 'general' ? 'bg-gradient-to-br from-primary to-purple-600' : 'bg-gray-100 dark:bg-gray-700 group-hover:bg-primary/10 dark:group-hover:bg-primary/20'">
                                        <svg class="w-5 h-5 transition-colors" :class="activeTab === 'general' ? 'text-white' : 'text-body-color dark:text-body-color group-hover:text-primary'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <span class="flex-1 font-medium" :class="activeTab === 'general' ? 'text-primary dark:text-primary' : 'text-body-color dark:text-body-color'">{{ __('profile.profile') }}</span>
                                </button>

                                <button @click="window.switchTab('security'); mobileMenuOpen = false; activeTab = 'security'"
                                        :class="activeTab === 'security' ? 'bg-gradient-to-r from-primary/10 to-purple-600/10 dark:from-primary/20 dark:to-purple-600/20 border-l-4 border-primary' : 'bg-transparent hover:bg-gray-50 dark:hover:bg-gray-800'"
                                        class="w-full px-4 py-3.5 text-left flex items-center transition-all duration-200 group text-dark dark:text-white">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3 transition-colors"
                                         :class="activeTab === 'security' ? 'bg-gradient-to-br from-primary to-purple-600' : 'bg-gray-100 dark:bg-gray-700 group-hover:bg-primary/10 dark:group-hover:bg-primary/20'">
                                        <svg class="w-5 h-5 transition-colors" :class="activeTab === 'security' ? 'text-white' : 'text-body-color dark:text-body-color group-hover:text-primary'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </div>
                                    <span class="font-medium" :class="activeTab === 'security' ? 'text-primary dark:text-primary' : 'text-body-color dark:text-body-color'">{{ __('profile.security') }}</span>
                                </button>

                                <button @click="window.switchTab('sessions'); mobileMenuOpen = false; activeTab = 'sessions'"
                                        :class="activeTab === 'sessions' ? 'bg-gradient-to-r from-primary/10 to-purple-600/10 dark:from-primary/20 dark:to-purple-600/20 border-l-4 border-primary' : 'bg-transparent hover:bg-gray-50 dark:hover:bg-gray-800'"
                                        class="w-full px-4 py-3.5 text-left flex items-center transition-all duration-200 group text-dark dark:text-white">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3 transition-colors"
                                         :class="activeTab === 'sessions' ? 'bg-gradient-to-br from-primary to-purple-600' : 'bg-gray-100 dark:bg-gray-700 group-hover:bg-primary/10 dark:group-hover:bg-primary/20'">
                                        <svg class="w-5 h-5 transition-colors" :class="activeTab === 'sessions' ? 'text-white' : 'text-body-color dark:text-body-color group-hover:text-primary'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <span class="font-medium" :class="activeTab === 'sessions' ? 'text-primary dark:text-primary' : 'text-body-color dark:text-body-color'">{{ __('profile.sessions') }}</span>
                                </button>

                                <div class="my-1 px-4">
                                    <div class="border-t border-gray-200 dark:border-gray-700"></div>
                                </div>
                                <button @click="window.switchTab('danger'); mobileMenuOpen = false; activeTab = 'danger'"
                                        :class="activeTab === 'danger' ? 'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500' : 'bg-transparent hover:bg-red-50 dark:hover:bg-red-900/10'"
                                        class="w-full px-4 py-3.5 text-left flex items-center transition-all duration-200 group text-dark dark:text-white">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3 transition-colors"
                                         :class="activeTab === 'danger' ? 'bg-gradient-to-br from-red-500 to-red-600' : 'bg-red-100 dark:bg-red-900/30 group-hover:bg-red-200 dark:group-hover:bg-red-900/50'">
                                        <svg class="w-5 h-5 transition-colors" :class="activeTab === 'danger' ? 'text-white' : 'text-red-600 dark:text-red-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    </div>
                                    <span class="font-medium text-red-600 dark:text-red-400">{{ __('profile.advanced') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Desktop Tabs --}}
                    <div class="hidden lg:block border-b border-gray-200 dark:border-gray-700 relative z-10">
                        <nav class="-mb-px flex space-x-8 overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600" aria-label="Tabs">
                            <button
                                onclick="window.switchTab('general')"
                                id="general-tab"
                                class="tab-button border-primary text-primary border-transparent text-body-color hover:text-dark hover:border-gray-300 dark:text-body-color dark:hover:text-white whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors group relative z-10"
                                style="pointer-events: auto;"
                            >
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 group-hover:text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span>{{ __('profile.profile') }}</span>
                                </div>
                            </button>
                            <button
                                onclick="window.switchTab('security')"
                                id="security-tab"
                                class="tab-button border-transparent text-body-color hover:text-dark hover:border-gray-300 dark:text-body-color dark:hover:text-white whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors group relative z-10"
                                style="pointer-events: auto;"
                            >
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 group-hover:text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <span>{{ __('profile.security') }}</span>
                                </div>
                            </button>
                            <button
                                onclick="window.switchTab('sessions')"
                                id="sessions-tab"
                                class="tab-button border-transparent text-body-color hover:text-dark hover:border-gray-300 dark:text-body-color dark:hover:text-white whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors group relative z-10"
                                style="pointer-events: auto;"
                            >
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 group-hover:text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span>{{ __('profile.sessions') }}</span>
                                </div>
                            </button>
                            <button
                                onclick="window.switchTab('danger')"
                                id="danger-tab"
                                class="tab-button border-transparent text-body-color hover:text-dark hover:border-gray-300 dark:text-body-color dark:hover:text-white whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors group relative z-10"
                                style="pointer-events: auto;"
                            >
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-red-500 group-hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span>{{ __('profile.advanced') }}</span>
                                </div>
                            </button>
                        </nav>
                    </div>

                    {{-- Tab Content --}}
                    <div class="mt-8">
                        {{-- Profile Information Tab --}}
                        <div id="general-content" class="tab-panel">
                            {{-- Introduction Text --}}
                            <div class="mb-6 p-4 sm:p-6 bg-blue-50 dark:bg-blue-500/10 rounded-lg border dark:bg-dark">
                                <div class="flex items-start space-x-3">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm sm:text-base text-blue-800 dark:text-blue-200">
                                        <strong class="font-semibold">{{ __('profile.profile_management_desc') }}</strong>
                                    </p>
                                </div>
                            </div>
                            @include('profile.partials.update-profile-information')
                        </div>

                        {{-- Security Tab --}}
                        <div id="security-content" class="tab-panel hidden">
                            {{-- Introduction Text --}}
                            <div class="mb-6 p-4 sm:p-6 bg-blue-50 dark:bg-blue-500/10 rounded-lg border dark:bg-dark">
                                <div class="flex items-start space-x-3">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm sm:text-base text-blue-800 dark:text-blue-200">
                                        <strong class="font-semibold">{{ __('profile.security_management_desc') }}</strong>
                                    </p>
                                </div>
                            </div>
                            @include('profile.partials.update-password')
                            @include('profile.partials.two-factor-authentication')
                        </div>

                        {{-- Sessions Tab --}}
                        <div id="sessions-content" class="tab-panel hidden">
                            {{-- Introduction Text --}}
                            <div class="mb-6 p-4 sm:p-6 bg-blue-50 dark:bg-blue-500/10 rounded-lg border dark:bg-dark">
                                <div class="flex items-start space-x-3">
                                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm sm:text-base text-cyan-800 dark:text-cyan-200">
                                        <strong class="font-semibold">{{ __('profile.sessions_management_desc') }}</strong>
                                    </p>
                                </div>
                            </div>
                            @include('profile.partials.logout-other-browser-sessions')
                        </div>

                        {{-- Danger Zone Tab --}}
                        <div id="danger-content" class="tab-panel hidden">
                            {{-- Introduction Text --}}
                            <div class="mb-6 p-4 sm:p-6 bg-red-50 dark:bg-red-500/10 rounded-lg border dark:bg-dark">
                                <div class="flex items-start space-x-3">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm sm:text-base text-red-800 dark:text-red-200">
                                        <strong class="font-semibold">{{ __('profile.danger_zone_desc') }}</strong>
                                    </p>
                                </div>
                            </div>
                            @include('profile.partials.delete-user')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layout>