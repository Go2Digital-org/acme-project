<x-layout title="Donation Cancelled">
    <section class="py-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center">
                {{-- Cancel Icon --}}
                <div class="w-24 h-24 bg-yellow-100 dark:bg-yellow-900/20 rounded-full flex items-center justify-center mx-auto mb-8">
                    <i class="fas fa-times text-4xl text-yellow-500"></i>
                </div>

                {{-- Cancel Message --}}
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ __('donations.cancelled_title') }}
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
                    {{ __('donations.cancelled_message') }}
                </p>

                {{-- Donation Info Card --}}
                <x-card class="text-left mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Left Column --}}
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                {{ __('donations.attempted_donation') }}
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('donations.amount') }}:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ format_currency($donation->amount) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('donations.status') }}:</span>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        {{ __('donations.cancelled') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Right Column --}}
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                {{ __('donations.campaign_info') }}
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400 block mb-1">{{ __('campaigns.campaign') }}:</span>
                                    <a href="{{ route('campaigns.show', $campaign->uuid ?? $campaign->id) }}" 
                                       class="text-primary hover:text-primary-dark font-semibold transition-colors">
                                        {{ $campaign->getTitle() }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>

                {{-- Try Again Message --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-8">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-500 flex-shrink-0 mt-1"></i>
                        <div class="text-left">
                            <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                                {{ __('donations.no_worries') }}
                            </h4>
                            <p class="text-blue-700 dark:text-blue-300">
                                {{ __('donations.cancelled_explanation') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <x-button href="{{ route('campaigns.donate', $campaign->uuid ?? $campaign->id) }}">
                        <i class="fas fa-heart mr-2"></i>
                        {{ __('donations.try_again') }}
                    </x-button>
                    <x-button href="{{ route('campaigns.show', $campaign->uuid ?? $campaign->id) }}" variant="outline">
                        <i class="fas fa-arrow-left mr-2"></i>
                        {{ __('donations.back_to_campaign') }}
                    </x-button>
                    <x-button href="{{ route('campaigns.index') }}" variant="outline">
                        <i class="fas fa-search mr-2"></i>
                        {{ __('campaigns.browse_campaigns') }}
                    </x-button>
                </div>
            </div>
        </div>
    </section>
</x-layout>