<x-layout title="Dashboard">
    <div
        class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="dashboardData()"
        x-init="initDashboard()"
    >
        {{-- Hero Section --}}
        <div class="mb-8">
            <div class="bg-primary dark:bg-primary-dark rounded-2xl p-8 text-white relative overflow-hidden">
                {{-- Background decoration --}}
                <div class="absolute inset-0">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-10 -left-10 w-60 h-60 bg-white/5 rounded-full blur-3xl"></div>
                </div>
                
                <div class="relative z-10">
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                        <div class="flex-1">
                            <h1 class="text-3xl lg:text-4xl font-bold mb-2">
                                {{ __('homepage.welcome_back', ['name' => auth()->user()->name]) }}
                            </h1>
                            <p class="text-primary-light text-lg opacity-90">
                                {{ __('homepage.hero_subtitle') }}
                            </p>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-4">
                            <x-button 
                                variant="secondary" 
                                href="{{ route('campaigns.index', ['filter' => 'ending-soon']) }}"
                                icon="fas fa-heart"
                                class="bg-white text-primary hover:bg-gray-100"
                            >
                                {{ __('homepage.browse_campaigns') }}
                            </x-button>
                            
                            <x-button 
                                variant="primary" 
                                href="{{ route('campaigns.create') }}"
                                icon="fas fa-plus"
                                class="bg-secondary text-white hover:bg-secondary-dark border-secondary"
                            >
                                {{ __('homepage.start_campaign') }}
                            </x-button>
                            
                            <x-button 
                                variant="outline" 
                                href="{{ route('campaigns.my-campaigns') }}"
                                icon="fas fa-cog"
                                class="border-white text-white hover:bg-white hover:text-primary"
                            >
                                {{ __('dashboard.manage_campaigns') }}
                            </x-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cache Status Progress Bar --}}
        <div x-show="cacheStatus.isWarming" class="mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Loading dashboard data...</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div
                        class="bg-blue-500 h-2 rounded-full transition-all duration-300 ease-out"
                        :style="{ width: cacheStatus.warmingProgress + '%' }"
                    ></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <span>Preparing your personalized dashboard</span>
                    <span x-text="cacheStatus.warmingProgress + '%'"></span>
                </div>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Total Donations --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                <div x-show="!dashboardData.statistics && cacheStatus.isLoading" class="animate-pulse">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 mb-3"></div>
                            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-32 mb-2"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                        </div>
                        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
                    </div>
                </div>
                <div
                    x-show="dashboardData.statistics || !cacheStatus.isLoading"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    class="flex items-center justify-between"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('homepage.total_donated') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="dashboardData.statistics?.totalDonated || '@formatCurrency(0)'">
                            @if(isset($statistics)){{ $statistics->totalDonated->format() }}@else @formatCurrency(0) @endif
                        </p>
                        <p class="text-sm text-secondary font-medium" x-text="dashboardData.statistics?.monthlyGrowthText || '{{ __('homepage.this_month_increase', ['percent' => '0']) }}'">
                            {{ __('homepage.this_month_increase', ['percent' => isset($statistics) ? number_format($statistics->monthlyGrowthPercentage, 0) : '0']) }}
                        </p>
                    </div>
                    <div class="p-3 bg-primary/10 rounded-xl">
                        <i class="fas fa-hand-holding-heart text-primary text-xl"></i>
                    </div>
                </div>
            </div>

            {{-- Campaigns Supported --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                <div x-show="!dashboardData.statistics && cacheStatus.isLoading" class="animate-pulse">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-28 mb-3"></div>
                            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-16 mb-2"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                        </div>
                        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
                    </div>
                </div>
                <div
                    x-show="dashboardData.statistics || !cacheStatus.isLoading"
                    x-transition:enter="transition ease-out duration-300 delay-75"
                    x-transition:enter-start="opacity-0 transform translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    class="flex items-center justify-between"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('homepage.campaigns_supported') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="dashboardData.statistics?.campaignsSupported || '0'">
                            {{ isset($statistics) ? $statistics->campaignsSupported : 0 }}
                        </p>
                        <p class="text-sm text-accent font-medium" x-text="dashboardData.statistics?.newThisMonthText || '{{ __('homepage.new_this_month', ['count' => '0']) }}'">
                            {{ __('homepage.new_this_month', ['count' => isset($statistics) && $statistics->campaignsSupported > 0 ? min(3, $statistics->campaignsSupported) : '0']) }}
                        </p>
                    </div>
                    <div class="p-3 bg-secondary/10 rounded-xl">
                        <i class="fas fa-heart text-secondary text-xl"></i>
                    </div>
                </div>
            </div>

            {{-- Impact Score --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                <div x-show="!dashboardData.statistics && cacheStatus.isLoading" class="animate-pulse">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20 mb-3"></div>
                            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-12 mb-2"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-28"></div>
                        </div>
                        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
                    </div>
                </div>
                <div
                    x-show="dashboardData.statistics || !cacheStatus.isLoading"
                    x-transition:enter="transition ease-out duration-300 delay-150"
                    x-transition:enter-start="opacity-0 transform translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    class="flex items-center justify-between"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('homepage.impact_score') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="dashboardData.statistics?.impactScore || '0.0'">
                            {{ isset($statistics) ? $statistics->getFormattedImpactScore() : '0.0' }}
                        </p>
                        <p class="text-sm text-purple-600 font-medium" x-text="dashboardData.statistics?.topPerformerText || '{{ __('homepage.top_percent_donors', ['percent' => '50']) }}'">
                            {{ __('homepage.top_percent_donors', ['percent' => isset($statistics) && $statistics->isTopPerformer() ? '10' : '50']) }}
                        </p>
                    </div>
                    <div class="p-3 bg-accent/10 rounded-xl">
                        <i class="fas fa-star text-accent text-xl"></i>
                    </div>
                </div>
            </div>

            {{-- Monthly Goal Progress --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                <div x-show="!dashboardData.statistics && cacheStatus.isLoading" class="animate-pulse">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 mb-3"></div>
                            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-20 mb-2"></div>
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                        </div>
                        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-xl ml-4"></div>
                    </div>
                </div>
                <div
                    x-show="dashboardData.statistics || !cacheStatus.isLoading"
                    x-transition:enter="transition ease-out duration-300 delay-200"
                    x-transition:enter-start="opacity-0 transform translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    class="flex items-center justify-between"
                >
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('dashboard.monthly_goal') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            <span x-text="dashboardData.statistics?.monthlyProgress || '0'">
                                @php
                                    $monthlyGoal = 1000; // Default monthly goal
                                    $monthlyProgress = isset($statistics) && $statistics->monthlyIncrease ? min(100, ($statistics->monthlyIncrease->getAmount() / $monthlyGoal) * 100) : 0;
                                @endphp
                                {{ number_format($monthlyProgress, 0) }}
                            </span>%
                            <span class="text-sm font-normal text-gray-500">{{ __('dashboard.achieved') }}</span>
                        </p>
                        <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div
                                class="bg-purple-600 h-2 rounded-full transition-all duration-500 ease-out"
                                :style="{ width: (dashboardData.statistics?.monthlyProgress || {{ $monthlyProgress }}) + '%' }"
                            ></div>
                        </div>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-xl ml-4">
                        <i class="fas fa-bullseye text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            {{-- Left Column - Featured & Recent --}}
            <div class="xl:col-span-2 space-y-8">
                {{-- Bookmarked or Featured Campaigns --}}
                <section>
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            @if($hasBookmarks ?? false)
                                <i class="fas fa-bookmark text-yellow-500 mr-2"></i>
                                {{ __('dashboard.your_bookmarked_campaigns') }}
                            @else
                                {{ __('dashboard.featured_campaigns') }}
                            @endif
                        </h2>
                        <x-button variant="ghost" href="{{ route('campaigns.index') }}" size="sm">
                            {{ __('dashboard.view_all') }}
                            <i class="fas fa-arrow-right ml-2"></i>
                        </x-button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Loading Skeletons --}}
                        <template x-if="!dashboardData.featuredCampaigns && cacheStatus.isLoading">
                            <div class="col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="animate-pulse" x-data x-init="$nextTick(() => setTimeout(() => {}, Math.random() * 200))">
                                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
                                        <div class="h-48 bg-gray-200 dark:bg-gray-700 rounded-xl mb-4"></div>
                                        <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
                                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full mb-2"></div>
                                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-2/3 mb-4"></div>
                                        <div class="flex justify-between items-center">
                                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                                            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="animate-pulse" style="animation-delay: 100ms;">
                                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
                                        <div class="h-48 bg-gray-200 dark:bg-gray-700 rounded-xl mb-4"></div>
                                        <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-2/3 mb-2"></div>
                                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full mb-2"></div>
                                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-4"></div>
                                        <div class="flex justify-between items-center">
                                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                                            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Actual Campaign Cards --}}
                        <template x-if="dashboardData.featuredCampaigns || !cacheStatus.isLoading">
                            <div class="col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <template x-for="(campaign, index) in dashboardData.featuredCampaigns" :key="campaign.id">
                                    <div
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0 transform translate-y-4"
                                        x-transition:enter-end="opacity-100 transform translate-y-0"
                                        :style="{ 'transition-delay': (index * 150) + 'ms' }"
                                        x-html="campaign.html"
                                    ></div>
                                </template>
                            </div>
                        </template>

                        {{-- Fallback Content --}}
                        @forelse($featuredCampaigns ?? [] as $campaign)
                            <div
                                x-show="!dashboardData.featuredCampaigns"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 transform translate-y-4"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                            >
                                <x-campaign-card
                                    :campaign="$campaign"
                                    :href="route('campaigns.show', $campaign->uuid ?? $campaign->id)"
                                />
                            </div>
                        @empty
                            <div
                                x-show="(!dashboardData.featuredCampaigns || dashboardData.featuredCampaigns.length === 0)"
                                class="col-span-2 text-center py-8 text-gray-500 dark:text-gray-400"
                            >
                                <i class="fas fa-bullhorn text-4xl mb-4"></i>
                                <p>{{ __('dashboard.no_featured_campaigns_msg') }}</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                {{-- Recent Activity --}}
                <section>
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('dashboard.recent_activity') }}</h2>
                        <x-button variant="ghost" href="{{ route('donations.index') }}" size="sm">
                            {{ __('dashboard.view_history') }}
                            <i class="fas fa-arrow-right ml-2"></i>
                        </x-button>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                        <div class="p-6 space-y-4">
                            {{-- Loading Skeleton for Activity Feed --}}
                            <template x-if="!dashboardData.recentActivities && cacheStatus.isLoading">
                                <div class="space-y-4">
                                    <div class="animate-pulse flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                        <div class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded-full flex-shrink-0"></div>
                                        <div class="flex-1">
                                            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-3/4 mb-2"></div>
                                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/2"></div>
                                        </div>
                                        <div class="h-5 bg-gray-200 dark:bg-gray-600 rounded w-16"></div>
                                    </div>
                                    <div class="animate-pulse flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl" style="animation-delay: 100ms;">
                                        <div class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded-full flex-shrink-0"></div>
                                        <div class="flex-1">
                                            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-2/3 mb-2"></div>
                                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                                        </div>
                                        <div class="h-5 bg-gray-200 dark:bg-gray-600 rounded w-20"></div>
                                    </div>
                                    <div class="animate-pulse flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl" style="animation-delay: 200ms;">
                                        <div class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded-full flex-shrink-0"></div>
                                        <div class="flex-1">
                                            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-4/5 mb-2"></div>
                                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-2/5"></div>
                                        </div>
                                        <div class="h-5 bg-gray-200 dark:bg-gray-600 rounded w-12"></div>
                                    </div>
                                </div>
                            </template>

                            {{-- Actual Activity Feed --}}
                            <template x-if="dashboardData.recentActivities || !cacheStatus.isLoading">
                                <div class="space-y-4">
                                    <template x-for="(activity, index) in dashboardData.recentActivities" :key="activity.id">
                                        <div
                                            x-transition:enter="transition ease-out duration-300"
                                            x-transition:enter-start="opacity-0 transform translate-y-4"
                                            x-transition:enter-end="opacity-100 transform translate-y-0"
                                            :style="{ 'transition-delay': (index * 100) + 'ms' }"
                                            x-html="activity.html"
                                            class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
                                        ></div>
                                    </template>
                                </div>
                            </template>

                            {{-- Fallback Content --}}
                            @forelse($recentActivities ?? [] as $activity)
                                <div
                                    x-show="!dashboardData.recentActivities"
                                    class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
                                >
                                    <div class="w-10 h-10 bg-{{ $activity->getColorClass() }}/10 rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="{{ $activity->getIconClass() }}"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {!! $activity->description !!}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $activity->getFormattedTime() }}</p>
                                    </div>
                                    @if($activity->amount)
                                        <div class="text-secondary font-bold">{{ $activity->amount->format() }}</div>
                                    @endif
                                </div>
                            @empty
                                <div
                                    x-show="(!dashboardData.recentActivities || dashboardData.recentActivities.length === 0)"
                                    class="text-center py-8 text-gray-500 dark:text-gray-400"
                                >
                                    <i class="fas fa-clock text-4xl mb-4"></i>
                                    <p>{{ __('dashboard.no_recent_activity_msg') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>

            {{-- Right Column - Sidebar --}}
            <div class="space-y-8">
                {{-- Impact Summary --}}
                <section>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('dashboard.your_impact') }}</h3>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
                        {{-- Loading Skeleton for Impact Summary --}}
                        <div x-show="!dashboardData.impact && cacheStatus.isLoading" class="animate-pulse space-y-4">
                            <div class="text-center">
                                <div class="w-20 h-20 bg-gray-200 dark:bg-gray-700 rounded-full mx-auto mb-3"></div>
                                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-16 mx-auto mb-2"></div>
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 mx-auto"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="text-center">
                                    <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-8 mx-auto mb-2"></div>
                                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-16 mx-auto"></div>
                                </div>
                                <div class="text-center">
                                    <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-8 mx-auto mb-2"></div>
                                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-20 mx-auto"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Actual Impact Summary --}}
                        <div
                            x-show="dashboardData.impact || !cacheStatus.isLoading"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 transform translate-y-4"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            class="space-y-4"
                        >
                            <div class="text-center">
                                <div class="w-20 h-20 bg-primary dark:bg-primary-dark rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-globe text-white text-2xl"></i>
                                </div>
                                <p class="font-bold text-2xl text-gray-900 dark:text-white" x-text="dashboardData.impact?.peopleHelped || '0'">
                                    {{ isset($impact) ? $impact->peopleHelped : 0 }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.people_helped') }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="text-center">
                                    <p class="font-bold text-lg text-gray-900 dark:text-white" x-text="dashboardData.impact?.countriesReached || '0'">
                                        {{ isset($impact) ? $impact->countriesReached : 0 }}
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('dashboard.countries_reached') }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="font-bold text-lg text-gray-900 dark:text-white" x-text="dashboardData.impact?.organizationsSupported || '0'">
                                        {{ isset($impact) ? $impact->organizationsSupported : 0 }}
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('dashboard.organizations_supported') }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Fallback Content --}}
                        <div x-show="!dashboardData.impact" class="space-y-4">
                            <div class="text-center">
                                <div class="w-20 h-20 bg-primary dark:bg-primary-dark rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-globe text-white text-2xl"></i>
                                </div>
                                <p class="font-bold text-2xl text-gray-900 dark:text-white">{{ isset($impact) ? $impact->peopleHelped : 0 }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.people_helped') }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="text-center">
                                    <p class="font-bold text-lg text-gray-900 dark:text-white">{{ isset($impact) ? $impact->countriesReached : 0 }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('dashboard.countries_reached') }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="font-bold text-lg text-gray-900 dark:text-white">{{ isset($impact) ? $impact->organizationsSupported : 0 }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('dashboard.organizations_supported') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Top Donators Leaderboard --}}
                <section>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('dashboard.top_donators') }}</h3>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm">
                        {{-- Loading Skeleton for Leaderboard --}}
                        <div x-show="!dashboardData.leaderboard && cacheStatus.isLoading" class="animate-pulse space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-full flex-shrink-0"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                                </div>
                                <div class="w-4 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-full flex-shrink-0"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-2/3 mb-2"></div>
                                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-full flex-shrink-0"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-4/5 mb-2"></div>
                                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-2/5"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Actual Leaderboard --}}
                        <div
                            x-show="dashboardData.leaderboard || !cacheStatus.isLoading"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 transform translate-y-4"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            class="space-y-3"
                        >
                            <template x-for="(entry, index) in dashboardData.leaderboard" :key="entry.id">
                                <div
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform translate-x-4"
                                    x-transition:enter-end="opacity-100 transform translate-x-0"
                                    :style="{ 'transition-delay': (index * 100) + 'ms' }"
                                    x-html="entry.html"
                                    :class="entry.isCurrentUser ? 'p-2 bg-primary/5 rounded-lg' : ''"
                                    class="flex items-center gap-3"
                                ></div>
                            </template>
                        </div>

                        {{-- Fallback Content --}}
                        <div x-show="!dashboardData.leaderboard" class="space-y-3">
                            @forelse($leaderboard ?? [] as $entry)
                                <div class="flex items-center gap-3 {{ $entry->isCurrentUser ? 'p-2 bg-primary/5 rounded-lg' : '' }}">
                                    <div class="w-8 h-8 bg-{{ $entry->rank === 1 ? 'yellow-100 dark:bg-yellow-900/20' : 'gray-100 dark:bg-gray-700' }} rounded-full flex items-center justify-center">
                                        <span class="{{ $entry->rank === 1 ? 'text-yellow-600' : 'text-gray-600 dark:text-gray-400' }} font-bold text-sm">{{ $entry->rank }}</span>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium {{ $entry->isCurrentUser ? 'text-primary' : 'text-gray-900 dark:text-white' }}">
                                            {{ $entry->name }}{{ $entry->isCurrentUser ? ' (You)' : '' }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $entry->totalDonations->format() }}</p>
                                    </div>
                                    @if($entry->rank === 1)
                                        <i class="fas fa-crown text-yellow-500"></i>
                                    @elseif($entry->isCurrentUser)
                                        <i class="fas fa-user text-primary text-sm"></i>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                                    <p class="text-sm">{{ __('dashboard.no_donation_data') }}</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Empty State --}}
                        <div
                            x-show="dashboardData.leaderboard && dashboardData.leaderboard.length === 0"
                            class="text-center py-4 text-gray-500 dark:text-gray-400"
                        >
                            <p class="text-sm">{{ __('dashboard.no_donation_data') }}</p>
                        </div>
                    </div>
                </section>

                {{-- Quick Actions --}}
                <section>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('dashboard.quick_actions') }}</h3>
                    <div class="space-y-3">
                        <x-button 
                            variant="outline" 
                            fullWidth
                            href="{{ route('campaigns.create') }}"
                            icon="fas fa-plus"
                        >
                            {{ __('dashboard.create_campaign') }}
                        </x-button>

                        <x-button 
                            variant="ghost" 
                            fullWidth
                            href="{{ route('campaigns.index', ['filter' => 'ending-soon']) }}"
                            icon="fas fa-clock"
                        >
                            {{ __('dashboard.ending_soon') }}
                        </x-button>

                        <x-button 
                            variant="ghost" 
                            fullWidth
                            href="{{ route('donations.index') }}"
                            icon="fas fa-receipt"
                        >
                            {{ __('dashboard.download_receipts') }}
                        </x-button>
                    </div>
                </section>
            </div>
        </div>
    </div>

    {{-- Dashboard JavaScript --}}
    <script>
        function dashboardData() {
            return {
                // Cache status tracking
                cacheStatus: {
                    status: @json($cacheStatus ?? 'miss'),
                    isLoading: false,
                    isWarming: false,
                    warmingProgress: @json($warmingProgress ?? 0),
                    lastChecked: null
                },

                // Dashboard data containers
                dashboardData: {
                    statistics: null,
                    featuredCampaigns: null,
                    recentActivities: null,
                    impact: null,
                    leaderboard: null
                },

                // Polling configuration
                polling: {
                    interval: null,
                    retryCount: 0,
                    maxRetries: 30 // Stop after 30 retries (1 minute)
                },

                // Initialize dashboard
                initDashboard() {
                    // Set initial cache status
                    this.updateCacheStatus(@json($cacheStatus ?? 'miss'), @json($warmingProgress ?? 0));

                    // Load initial data if available
                    this.loadInitialData();

                    // Start polling if cache is not ready
                    if (this.shouldStartPolling()) {
                        this.startPolling();
                    }
                },

                // Update cache status
                updateCacheStatus(status, progress = 0) {
                    this.cacheStatus.status = status;
                    this.cacheStatus.isLoading = ['miss', 'warming'].includes(status);
                    this.cacheStatus.isWarming = status === 'warming';
                    this.cacheStatus.warmingProgress = Math.min(100, Math.max(0, progress));
                    this.cacheStatus.lastChecked = new Date().toISOString();
                },

                // Check if polling should start
                shouldStartPolling() {
                    return ['miss', 'warming'].includes(this.cacheStatus.status);
                },

                // Load initial data from server-side rendering
                loadInitialData() {
                    // Load statistics if available
                    @if(isset($statistics))
                        this.dashboardData.statistics = {
                            totalDonated: '{{ $statistics->totalDonated->format() }}',
                            monthlyGrowthText: '{{ __('homepage.this_month_increase', ['percent' => number_format($statistics->monthlyGrowthPercentage, 0)]) }}',
                            campaignsSupported: {{ $statistics->campaignsSupported }},
                            newThisMonthText: '{{ __('homepage.new_this_month', ['count' => isset($statistics) && $statistics->campaignsSupported > 0 ? min(3, $statistics->campaignsSupported) : '0']) }}',
                            impactScore: '{{ $statistics->getFormattedImpactScore() }}',
                            topPerformerText: '{{ __('homepage.top_percent_donors', ['percent' => $statistics->isTopPerformer() ? '10' : '50']) }}',
                            monthlyProgress: {{ number_format(isset($statistics) && $statistics->monthlyIncrease ? min(100, ($statistics->monthlyIncrease->getAmount() / 1000) * 100) : 0, 0) }}
                        };
                    @endif

                    // Load impact data if available
                    @if(isset($impact))
                        this.dashboardData.impact = {
                            peopleHelped: {{ $impact->peopleHelped }},
                            countriesReached: {{ $impact->countriesReached }},
                            organizationsSupported: {{ $impact->organizationsSupported }}
                        };
                    @endif

                    // If cache is ready, mark as loaded
                    if (this.cacheStatus.status === 'hit') {
                        this.cacheStatus.isLoading = false;
                    }
                },

                // Start polling for cache status
                startPolling() {
                    this.polling.interval = setInterval(() => {
                        this.checkCacheStatus();
                    }, 2000); // Poll every 2 seconds
                },

                // Stop polling
                stopPolling() {
                    if (this.polling.interval) {
                        clearInterval(this.polling.interval);
                        this.polling.interval = null;
                    }
                },

                // Check cache status via AJAX
                async checkCacheStatus() {
                    try {
                        const response = await fetch('/api/dashboard/cache-status', {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }

                        const data = await response.json();

                        // Update cache status with progress percentage from progress object
                        const progressPercentage = data.progress?.percentage || 0;
                        this.updateCacheStatus(data.status, progressPercentage);

                        // If cache is ready, load dashboard data
                        if (data.status === 'hit') {
                            await this.loadDashboardData();
                            this.stopPolling();
                            this.polling.retryCount = 0;
                        } else {
                            // Increment retry count
                            this.polling.retryCount++;

                            // Stop polling after max retries
                            if (this.polling.retryCount >= this.polling.maxRetries) {
                                this.stopPolling();
                                this.handlePollingTimeout();
                            }
                        }
                    } catch (error) {
                        console.error('Cache status check failed:', error);
                        this.polling.retryCount++;

                        if (this.polling.retryCount >= this.polling.maxRetries) {
                            this.stopPolling();
                            this.handlePollingTimeout();
                        }
                    }
                },

                // Load dashboard data when cache is ready
                async loadDashboardData() {
                    try {
                        const response = await fetch('/api/dashboard/data', {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }

                        const data = await response.json();

                        // Progressive data loading with delays for smooth UX
                        await this.loadDataProgressively(data);

                    } catch (error) {
                        console.error('Dashboard data loading failed:', error);
                        this.handleDataLoadError();
                    }
                },

                // Load data progressively to avoid jarring jumps
                async loadDataProgressively(data) {
                    // Load statistics first
                    if (data.statistics) {
                        await this.delay(150);
                        this.dashboardData.statistics = data.statistics;
                    }

                    // Load featured campaigns
                    if (data.featuredCampaigns) {
                        await this.delay(200);
                        this.dashboardData.featuredCampaigns = data.featuredCampaigns;
                    }

                    // Load recent activities
                    if (data.recentActivities) {
                        await this.delay(150);
                        this.dashboardData.recentActivities = data.recentActivities;
                    }

                    // Load impact data
                    if (data.impact) {
                        await this.delay(100);
                        this.dashboardData.impact = data.impact;
                    }

                    // Load leaderboard last
                    if (data.leaderboard) {
                        await this.delay(100);
                        this.dashboardData.leaderboard = data.leaderboard;
                    }

                    // Mark loading as complete
                    this.cacheStatus.isLoading = false;
                },

                // Utility function for delays
                delay(ms) {
                    return new Promise(resolve => setTimeout(resolve, ms));
                },

                // Handle polling timeout
                handlePollingTimeout() {
                    console.warn('Dashboard polling timed out. Loading fallback data.');
                    this.cacheStatus.isLoading = false;
                    this.cacheStatus.isWarming = false;

                    // Show error notification (if notification system is available)
                    if (window.showNotification) {
                        window.showNotification('Dashboard data is taking longer than expected to load. Some features may be limited.', 'warning');
                    }
                },

                // Handle data loading error
                handleDataLoadError() {
                    this.cacheStatus.isLoading = false;

                    // Show error notification
                    if (window.showNotification) {
                        window.showNotification('Failed to load dashboard data. Please refresh the page.', 'error');
                    }
                },

                // Cleanup when component is destroyed
                destroy() {
                    this.stopPolling();
                }
            }
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            // Stop any active polling intervals
            if (window.dashboardComponent && window.dashboardComponent.destroy) {
                window.dashboardComponent.destroy();
            }
        });
    </script>
</x-layout>