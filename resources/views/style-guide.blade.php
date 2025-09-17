{{-- ACME Corp CSR Platform Style Guide --}}
<x-layout title="{{ __('style_guide.title') }}">
    <div class="min-h-screen py-12 bg-gray-50 dark:bg-[#050714]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
                    {{ __('style_guide.page_title') }}
                </h1>
                <p class="mt-4 text-xl text-gray-600 dark:text-gray-300">
                    {{ __('style_guide.page_subtitle') }}
                </p>
            </div>

            {{-- Typography Section --}}
            <section class="mb-16 p-8 bg-white dark:bg-[#0f1629] rounded-xl border border-gray-200 dark:border-gray-800/50 relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">{{ __('style_guide.typography.title') }}</h2>

                    <div class="space-y-6">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('style_guide.typography.heading_1.label') }}</p>
                            <h1 class="text-5xl font-bold text-gray-900 dark:text-white">{{ __('style_guide.typography.heading_1.example') }}</h1>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('style_guide.typography.heading_2.label') }}</p>
                            <h2 class="text-4xl font-bold text-gray-900 dark:text-white">{{ __('style_guide.typography.heading_2.example') }}</h2>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('style_guide.typography.heading_3.label') }}</p>
                            <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('style_guide.typography.heading_3.example') }}</h3>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('style_guide.typography.heading_4.label') }}</p>
                            <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('style_guide.typography.heading_4.example') }}</h4>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('style_guide.typography.heading_5.label') }}</p>
                            <h5 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('style_guide.typography.heading_5.example') }}</h5>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('style_guide.typography.body_text.label') }}</p>
                            <p class="text-base text-gray-600 dark:text-gray-300">{{ __('style_guide.typography.body_text.example') }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('style_guide.typography.small_text.label') }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('style_guide.typography.small_text.example') }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('style_guide.typography.extra_small_text.label') }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-300">{{ __('style_guide.typography.extra_small_text.example') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Color Palette Section --}}
            <section class="mb-16 p-8 bg-gray-100 dark:bg-[#1e2738] rounded-xl border border-gray-200 dark:border-gray-800/50 relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">{{ __('style_guide.colors.title') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {{-- Primary Colors --}}
                        <div class="bg-white dark:bg-[#0f1629] border border-gray-200 dark:border-gray-600/20 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('style_guide.colors.primary.title') }}</h3>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-16 h-16 bg-primary rounded-lg shadow-md"></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('style_guide.colors.primary.primary_blue.name') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('style_guide.colors.primary.primary_blue.description') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-16 h-16 bg-secondary rounded-lg shadow-md"></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('style_guide.colors.primary.secondary.name') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('style_guide.colors.primary.secondary.description') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Status Colors --}}
                        <div class="bg-white dark:bg-[#0f1629] border border-gray-200 dark:border-gray-600/20 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('style_guide.colors.status.title') }}</h3>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-16 h-16 bg-emerald-500 rounded-lg shadow-md"></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('style_guide.colors.status.success.name') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('style_guide.colors.status.success.description') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-16 h-16 bg-amber-500 rounded-lg shadow-md"></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('style_guide.colors.status.warning.name') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('style_guide.colors.status.warning.description') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-16 h-16 bg-red-500 rounded-lg shadow-md"></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('style_guide.colors.status.urgent.name') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('style_guide.colors.status.urgent.description') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Background Colors --}}
                        <div class="bg-white dark:bg-[#0f1629] border border-gray-200 dark:border-gray-600/20 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('style_guide.colors.background.title') }}</h3>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-16 h-16 bg-gray-50 dark:bg-[#050714] rounded-lg shadow-md border border-gray-300 dark:border-gray-700"></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('style_guide.colors.background.page_background.name') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('style_guide.colors.background.page_background.description') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-16 h-16 bg-white dark:bg-[#0f1629] rounded-lg shadow-md border border-gray-300 dark:border-gray-700"></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('style_guide.colors.background.card_background.name') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('style_guide.colors.background.card_background.description') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-[#1e2738] rounded-lg shadow-md border border-gray-300 dark:border-gray-700"></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('style_guide.colors.background.section_background.name') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('style_guide.colors.background.section_background.description') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Button Components Section --}}
            <section class="mb-16 p-8 bg-white dark:bg-[#0f1629] rounded-xl border border-gray-200 dark:border-gray-800/50 relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">{{ __('style_guide.buttons.title') }}</h2>

                    <div class="space-y-8">
                        {{-- Primary Buttons --}}
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('style_guide.buttons.primary.title') }}</h3>
                            <div class="flex flex-wrap gap-4">
                                <x-button variant="primary" size="sm">{{ __('style_guide.buttons.primary.small_primary') }}</x-button>
                                <x-button variant="primary" size="md">{{ __('style_guide.buttons.primary.medium_primary') }}</x-button>
                                <x-button variant="primary" size="lg" icon="fas fa-heart">{{ __('style_guide.buttons.primary.large_with_icon') }}</x-button>
                            </div>
                        </div>

                        {{-- Secondary Buttons --}}
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('style_guide.buttons.secondary.title') }}</h3>
                            <div class="flex flex-wrap gap-4">
                                <x-button variant="secondary" size="md">{{ __('style_guide.buttons.secondary.secondary') }}</x-button>
                                <x-button variant="outline" size="md">{{ __('style_guide.buttons.secondary.outline') }}</x-button>
                                <x-button variant="ghost" size="md">{{ __('style_guide.buttons.secondary.ghost') }}</x-button>
                            </div>
                        </div>

                        {{-- Status Buttons --}}
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('style_guide.buttons.status.title') }}</h3>
                            <div class="flex flex-wrap gap-4">
                                <x-button variant="success" size="md" icon="fas fa-check">{{ __('style_guide.buttons.status.donate_now') }}</x-button>
                                <x-button variant="warning" size="md" icon="fas fa-exclamation-triangle">{{ __('style_guide.buttons.status.urgent') }}</x-button>
                                <x-button variant="danger" size="md" icon="fas fa-times">{{ __('style_guide.buttons.status.cancel') }}</x-button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Campaign Card Components --}}
            <section class="mb-16 p-8 bg-gray-100 dark:bg-[#1e2738] rounded-xl border border-gray-200 dark:border-gray-800/50 relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">{{ __('style_guide.campaigns.title') }}</h2>

                    <div class="space-y-8">
                        {{-- Sample Campaign Card --}}
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('style_guide.campaigns.featured_card.title') }}</h3>
                            <div class="max-w-sm">
                                <div class="relative isolate flex flex-col justify-end overflow-hidden rounded-2xl bg-gray-900 px-8 pb-8 pt-80 sm:pt-48">
                                    <img src="https://images.unsplash.com/photo-1497486751825-1233686d5d80?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" alt="Education Initiative" class="absolute inset-0 -z-10 h-full w-full object-cover">
                                    <div class="absolute inset-0 -z-10 bg-gray-900/80"></div>
                                    <div class="absolute inset-0 -z-10 rounded-2xl ring-1 ring-inset ring-gray-900/10"></div>

                                    <div class="flex flex-wrap items-center gap-y-1 overflow-hidden text-sm leading-6 text-gray-300">
                                        <div class="flex items-center gap-x-2">
                                            <i class="fas fa-graduation-cap text-secondary"></i>
                                            {{ __('style_guide.campaigns.featured_card.category') }}
                                        </div>
                                        <div class="ml-auto">
                                            <span class="inline-flex items-center rounded-md bg-primary px-2 py-1 text-xs font-medium text-white">
                                                {{ __('style_guide.campaigns.featured_card.funding_status') }}
                                            </span>
                                        </div>
                                    </div>
                                    <h3 class="mt-3 text-lg font-semibold leading-6 text-white">
                                        {{ __('style_guide.campaigns.featured_card.name') }}
                                    </h3>
                                    <p class="mt-2 text-sm leading-6 text-gray-300">
                                        {{ __('style_guide.campaigns.featured_card.description') }}
                                    </p>
                                    <div class="mt-4">
                                        <div class="flex justify-between text-sm text-gray-300 mb-2">
                                            <span>{{ __('style_guide.campaigns.featured_card.raised') }}</span>
                                            <span>{{ __('style_guide.campaigns.featured_card.goal') }}</span>
                                        </div>
                                        <div class="w-full bg-gray-700 rounded-full h-2">
                                            <div class="bg-secondary h-2 rounded-full" style="width: 72%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Impact Statistics Card --}}
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('style_guide.campaigns.impact_stats.title') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="flex flex-col gap-y-3 border-l border-gray-900/10 dark:border-white/10 pl-6 bg-white dark:bg-[#0f1629] p-4 rounded-lg">
                                    <dt class="text-sm leading-6 text-gray-600 dark:text-gray-400">{{ __('style_guide.campaigns.impact_stats.total_raised.label') }}</dt>
                                    <dd class="order-first text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                                        {{ __('style_guide.campaigns.impact_stats.total_raised.value') }}
                                    </dd>
                                </div>

                                <div class="flex flex-col gap-y-3 border-l border-gray-900/10 dark:border-white/10 pl-6 bg-white dark:bg-[#0f1629] p-4 rounded-lg">
                                    <dt class="text-sm leading-6 text-gray-600 dark:text-gray-400">{{ __('style_guide.campaigns.impact_stats.active_campaigns.label') }}</dt>
                                    <dd class="order-first text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                                        {{ __('style_guide.campaigns.impact_stats.active_campaigns.value') }}
                                    </dd>
                                </div>

                                <div class="flex flex-col gap-y-3 border-l border-gray-900/10 dark:border-white/10 pl-6 bg-white dark:bg-[#0f1629] p-4 rounded-lg">
                                    <dt class="text-sm leading-6 text-gray-600 dark:text-gray-400">{{ __('style_guide.campaigns.impact_stats.employees_participating.label') }}</dt>
                                    <dd class="order-first text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                                        {{ __('style_guide.campaigns.impact_stats.employees_participating.value') }}
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Form Components Section --}}
            <section class="mb-16 p-8 bg-white dark:bg-[#0f1629] rounded-xl border border-gray-200 dark:border-gray-800/50 relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">{{ __('style_guide.forms.title') }}</h2>

                    <form class="space-y-6 max-w-md">
                        {{-- Text Input --}}
                        <div>
                            <label for="sample-text" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                                {{ __('style_guide.forms.campaign_name.label') }}
                            </label>
                            <input
                                type="text"
                                id="sample-text"
                                name="sample-text"
                                placeholder="{{ __('style_guide.forms.campaign_name.placeholder') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-800 dark:text-white sm:text-sm"
                            />
                        </div>

                        {{-- Select --}}
                        <div>
                            <label for="sample-select" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                                {{ __('style_guide.forms.campaign_category.label') }}
                            </label>
                            <select
                                id="sample-select"
                                name="sample-select"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-800 dark:text-white sm:text-sm"
                            >
                                <option>{{ __('style_guide.forms.campaign_category.placeholder') }}</option>
                                <option>{{ __('style_guide.forms.campaign_category.options.education') }}</option>
                                <option>{{ __('style_guide.forms.campaign_category.options.healthcare') }}</option>
                                <option>{{ __('style_guide.forms.campaign_category.options.environment') }}</option>
                                <option>{{ __('style_guide.forms.campaign_category.options.community') }}</option>
                            </select>
                        </div>

                        {{-- Textarea --}}
                        <div>
                            <label for="sample-textarea" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                                {{ __('style_guide.forms.campaign_description.label') }}
                            </label>
                            <textarea
                                id="sample-textarea"
                                name="sample-textarea"
                                rows="4"
                                placeholder="{{ __('style_guide.forms.campaign_description.placeholder') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-800 dark:text-white sm:text-sm"
                            ></textarea>
                        </div>

                        {{-- Checkbox --}}
                        <div class="flex items-center">
                            <input
                                id="sample-checkbox"
                                name="sample-checkbox"
                                type="checkbox"
                                class="h-4 w-4 text-primary focus:ring-primary border-gray-300 dark:border-gray-600 rounded"
                            />
                            <label for="sample-checkbox" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                {{ __('style_guide.forms.terms_agreement') }}
                            </label>
                        </div>
                    </form>
                </div>
            </section>

            {{-- Icons Section --}}
            <section class="mb-16 p-8 bg-gray-100 dark:bg-[#1e2738] rounded-xl border border-gray-200 dark:border-gray-800/50 relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">{{ __('style_guide.icons.title') }}</h2>

                    <div class="grid grid-cols-4 md:grid-cols-8 gap-6">
                        <div class="text-center">
                            <i class="fas fa-heart text-2xl text-gray-700 dark:text-gray-300 mb-2"></i>
                            <p class="text-xs">{{ __('style_guide.icons.donate') }}</p>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-graduation-cap text-2xl text-gray-700 dark:text-gray-300 mb-2"></i>
                            <p class="text-xs">{{ __('style_guide.icons.education') }}</p>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-heartbeat text-2xl text-gray-700 dark:text-gray-300 mb-2"></i>
                            <p class="text-xs">{{ __('style_guide.icons.healthcare') }}</p>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-leaf text-2xl text-gray-700 dark:text-gray-300 mb-2"></i>
                            <p class="text-xs">{{ __('style_guide.icons.environment') }}</p>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-users text-2xl text-gray-700 dark:text-gray-300 mb-2"></i>
                            <p class="text-xs">{{ __('style_guide.icons.community') }}</p>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-hand-holding-heart text-2xl text-gray-700 dark:text-gray-300 mb-2"></i>
                            <p class="text-xs">{{ __('style_guide.icons.support') }}</p>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-chart-line text-2xl text-gray-700 dark:text-gray-300 mb-2"></i>
                            <p class="text-xs">{{ __('style_guide.icons.impact') }}</p>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-globe text-2xl text-gray-700 dark:text-gray-300 mb-2"></i>
                            <p class="text-xs">{{ __('style_guide.icons.global') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Design Principles --}}
            <section class="mb-16 p-8 bg-white dark:bg-[#0f1629] rounded-xl border border-gray-200 dark:border-gray-800/50 relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">{{ __('style_guide.principles.title') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('style_guide.principles.clean_professional.title') }}</h3>
                            <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                                @foreach(__('style_guide.principles.clean_professional.items') as $item)
                                    <li>• {{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('style_guide.principles.accessible_inclusive.title') }}</h3>
                            <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                                @foreach(__('style_guide.principles.accessible_inclusive.items') as $item)
                                    <li>• {{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('style_guide.principles.corporate_standards.title') }}</h3>
                            <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                                @foreach(__('style_guide.principles.corporate_standards.items') as $item)
                                    <li>• {{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('style_guide.principles.user_centered.title') }}</h3>
                            <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                                @foreach(__('style_guide.principles.user_centered.items') as $item)
                                    <li>• {{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layout>