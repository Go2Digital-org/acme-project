{{-- ACME Corp CSR Platform Homepage --}}
<x-layout title="{{ __('homepage.hero_title', ['employees' => '20,000']) }}">
    
    {{-- Hero Section --}}
    @include('components.csr-hero-section')

    {{-- Impact Statistics Section --}}
    <section id="impact" class="py-16 sm:py-24 bg-white dark:bg-gray-900" aria-labelledby="impact-title">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 id="impact-title" class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                    {{ __('homepage.our_impact') }}
                </h2>
                <p class="mt-4 text-lg leading-8 text-gray-600 dark:text-gray-400">
                    {{ __('homepage.making_difference') }}
                </p>
            </div>

            {{-- Stats Grid --}}
            <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-4">
                    <x-impact-stats-card value="{{ $impact['total_raised'] ?? '€0' }}" :label="__('homepage.total_raised')" />
                    <x-impact-stats-card value="{{ $impact['active_campaigns'] ?? 0 }}" :label="__('homepage.active_campaigns')" />
                    <x-impact-stats-card value="{{ $impact['participating_employees'] ?? 0 }}" :label="__('homepage.employees_participating')" />
                    <x-impact-stats-card value="{{ $impact['countries_reached'] ?? 0 }}" :label="__('homepage.countries_reached')" />
                </dl>
            </div>
        </div>
    </section>

    {{-- Featured Campaigns Section - Only show if we have at least 3 campaigns --}}
    @if(count($featuredCampaigns) >= 3)
    <section class="py-16 sm:py-24 bg-gray-50 dark:bg-gray-800" aria-labelledby="campaigns-title">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 id="campaigns-title" class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                    {{ __('homepage.featured_campaigns') }}
                </h2>
                <p class="mt-4 text-lg leading-8 text-gray-600 dark:text-gray-400">
                    {{ __('campaigns.subtitle') ?? 'Discover causes that matter and make your contribution count.' }}
                </p>
            </div>

            {{-- Featured Campaigns --}}
            <div class="mx-auto mt-16 grid max-w-2xl auto-rows-fr grid-cols-1 gap-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                @foreach($featuredCampaigns as $campaign)
                    <x-campaign-feature-card 
                        :title="$campaign['title']"
                        :description="$campaign['description']"
                        :image="$campaign['image']"
                        :category="$campaign['category']"
                        category-icon="fas fa-flag"
                        category-color="primary"
                        :raised="$campaign['raised']"
                        :goal="$campaign['goal']"
                        :percentage="$campaign['progress']"
                        progress-color="bg-primary"
                        :badge-text="$campaign['progress'] . '% ' . __('campaigns.funded')"
                        badge-color="bg-primary"
                        :href="route('campaigns.show', $campaign['slug'] ?? $campaign['id'])"
                    />
                @endforeach
            </div>

            {{-- View All Campaigns Button --}}
            <div class="mt-16 flex justify-center">
                <x-button 
                    href="{{ route('campaigns.index') }}" 
                    variant="primary" 
                    size="lg"
                >
                    {{ __('homepage.view_all_campaigns') }}
                </x-button>
            </div>
        </div>
    </section>
    @endif

    {{-- Impact Areas Section --}}
    <section class="py-16 sm:py-24 bg-white dark:bg-gray-900" aria-labelledby="impact-areas-title">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 id="impact-areas-title" class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                    {{ __('homepage.impact_areas') }}
                </h2>
                <p class="mt-4 text-lg leading-8 text-gray-600 dark:text-gray-400">
                    {{ __('campaigns.areas_description') ?? 'We support causes across multiple domains to create comprehensive positive change.' }}
                </p>
            </div>

            <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
                <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-10 lg:max-w-none lg:grid-cols-2 lg:gap-y-16">
                    <x-impact-area-item 
                        :title="__('homepage.education')"
                        :description="__('campaigns.education_desc') ?? 'Supporting educational programs and infrastructure development worldwide.'"
                        icon-class="fas fa-graduation-cap"
                        icon-bg-color="bg-primary"
                    />

                    <x-impact-area-item 
                        :title="__('homepage.healthcare')"
                        :description="__('campaigns.healthcare_desc') ?? 'Providing medical support and healthcare infrastructure to underserved communities.'"
                        icon-class="fas fa-heartbeat"
                        icon-bg-color="bg-red-600"
                    />

                    <x-impact-area-item 
                        :title="__('homepage.environment')"
                        :description="__('campaigns.environment_desc') ?? 'Environmental conservation and sustainability initiatives for our planet.'"
                        icon-class="fas fa-leaf"
                        icon-bg-color="bg-green-600"
                    />

                    <x-impact-area-item 
                        :title="__('homepage.community')"
                        :description="__('campaigns.community_desc') ?? 'Building stronger communities through social development programs.'"
                        icon-class="fas fa-users"
                        icon-bg-color="bg-secondary"
                    />
                </dl>
            </div>
        </div>
    </section>

    {{-- Call to Action Section --}}
    @auth
        <x-cta-section 
            :title="__('homepage.join_mission')"
            :description="__('homepage.cta_description')"
            :primary-button-text="__('homepage.start_donating')"
            :primary-button-href="route('campaigns.index')"
            primary-button-icon="fas fa-hand-holding-heart"
            :secondary-button-text="__('homepage.create_campaign')"
            :secondary-button-href="route('campaigns.create')"
            secondary-button-icon="fas fa-plus-circle"
        />
    @else
        <x-cta-section 
            :title="__('homepage.join_mission')"
            :description="__('homepage.cta_description')"
            :primary-button-text="__('homepage.start_donating')"
            :primary-button-href="route('campaigns.index')"
            primary-button-icon="fas fa-hand-holding-heart"
            :secondary-button-text="__('auth.login') ?? 'Login'"
            :secondary-button-href="route('login')"
            secondary-button-icon="fas fa-sign-in-alt"
        />
    @endauth

    {{-- Empowering Message Section --}}
    <section class="relative py-20 bg-primary dark:bg-primary-dark overflow-hidden">
        
        <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-4xl text-center">
                {{-- Icon --}}
                <div class="mx-auto mb-8 flex h-20 w-20 items-center justify-center rounded-full bg-white/10 backdrop-blur-sm">
                    <svg class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5c0-2.485-4.03-4.5-9-4.5s-9 2.015-9 4.5m18 0V17c0 2.485-4.03 4.5-9 4.5s-9-2.015-9-4.5V8.5m18 0c0 2.485-4.03 4.5-9 4.5s-9-2.015-9-4.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v20m0-8c-4.97 0-9-2.015-9-4.5V8.5c0-2.485 4.03-4.5 9-4.5s9 2.015 9 4.5V9.5c0 2.485-4.03 4.5-9 4.5z" />
                    </svg>
                </div>
                
                {{-- Main Heading --}}
                <h2 class="mb-6 text-4xl font-bold tracking-tight text-white sm:text-5xl">
                    {{ __('homepage.empowering_employees') }}
                </h2>
                
                {{-- Subheading --}}
                <p class="mx-auto max-w-3xl text-xl leading-relaxed text-white/90">
                    {{ __('homepage.meaningful_impact') }}
                </p>
                
                {{-- Stats --}}
                <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-3">
                    <div class="rounded-xl bg-white/10 backdrop-blur-sm p-6">
                        <div class="text-3xl font-bold text-white">{{ $employeeStats['total_employees'] ?? '20,000+' }}</div>
                        <div class="mt-2 text-sm text-white/80">{{ __('welcome.employees_making_difference') }}</div>
                    </div>
                    <div class="rounded-xl bg-white/10 backdrop-blur-sm p-6">
                        <div class="text-3xl font-bold text-white">{{ $employeeStats['total_raised_all_time'] ?? '€5M+' }}</div>
                        <div class="mt-2 text-sm text-white/80">{{ __('welcome.raised_good_causes') }}</div>
                    </div>
                    <div class="rounded-xl bg-white/10 backdrop-blur-sm p-6">
                        <div class="text-3xl font-bold text-white">{{ $employeeStats['total_campaigns'] ?? '500+' }}</div>
                        <div class="mt-2 text-sm text-white/80">{{ __('welcome.active_campaigns') }}</div>
                    </div>
                </div>
                
                {{-- Call to Action --}}
                <div class="mt-12 flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('campaigns.index') }}" class="inline-flex items-center justify-center gap-4 rounded-lg bg-white px-6 py-3 text-base font-semibold text-primary shadow-sm hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white transition-all duration-200">
                        {{ __('homepage.explore_campaigns') }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <a href="{{ route('campaigns.create') }}" class="inline-flex items-center justify-center rounded-lg border-2 border-white/30 bg-white/10 backdrop-blur-sm px-6 py-3 text-base font-semibold text-white hover:bg-white/20 hover:border-white/50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white transition-all duration-200">
                        {{ __('homepage.start_campaign') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

</x-layout>