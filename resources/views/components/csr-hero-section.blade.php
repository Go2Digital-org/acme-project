{{-- CSR Hero Section Component --}}
<section class="relative overflow-hidden bg-white dark:bg-gray-900 py-20 sm:py-32" aria-labelledby="hero-title">
    {{-- Background decoration --}}
    <div class="absolute inset-0 -z-10">
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-72 h-72 bg-secondary/5 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-accent/5 rounded-full blur-2xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
        <div class="text-center">
            {{-- Hero Title --}}
            <h1 
                id="hero-title"
                class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-6xl lg:text-7xl"
            >
                <span class="block text-gray-900 dark:text-white">{{ __('homepage.hero_title') }}</span>
            </h1>
            
            {{-- Subtitle --}}
            <div 
                class="hero-subtitle mt-6 text-lg sm:text-xl lg:text-2xl text-gray-600 dark:text-gray-300"
            >
                <span class="block">{{ __('homepage.hero_subtitle') }}</span>
                <span class="block font-semibold text-primary mt-2">{{ __('homepage.hero_csr') }}</span>
            </div>

            {{-- Description --}}
            <p 
                class="hero-description mt-6 max-w-3xl mx-auto text-lg leading-8 text-gray-600 dark:text-gray-400"
            >
                {{ __('homepage.hero_description') }}
            </p>

            {{-- CTA Buttons --}}
            <div 
                class="mt-10 flex items-center justify-center gap-6"
            >
                <x-button 
                    href="{{ route('campaigns.index') }}" 
                    variant="primary" 
                    size="lg"
                    icon="fas fa-heart"
                    class="hero-cta-button"
                >
                    {{ __('homepage.explore_campaigns') }}
                </x-button>

                <x-button 
                    href="#impact" 
                    variant="outline" 
                    size="lg"
                    icon="fas fa-info-circle"
                    class="hero-cta-button"
                >
                    {{ __('homepage.learn_more') }}
                </x-button>
            </div>
        </div>
    </div>
</section>