<x-layout title="Donation Successful">
    <section class="py-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center">
                {{-- Success Icon --}}
                <div class="w-24 h-24 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mx-auto mb-8">
                    <i class="fas fa-check text-4xl text-green-500"></i>
                </div>

                {{-- Success Message --}}
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ __('donations.success_title') }}
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
                    {{ __('donations.success_message') }}
                </p>

                {{-- Donation Details Card --}}
                <x-card class="text-left mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Left Column --}}
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                {{ __('donations.donation_details') }}
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('donations.amount') }}:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ format_currency($donation->amount) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('donations.date') }}:</span>
                                    <span class="text-gray-900 dark:text-white">
                                        {{ $donation->created_at->format('M j, Y \a\t g:i A') }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('donations.donation_id') }}:</span>
                                    <span class="text-gray-900 dark:text-white font-mono text-sm">
                                        #{{ $donation->id }}
                                    </span>
                                </div>
                                @if($donation->message)
                                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                        <span class="text-gray-600 dark:text-gray-400 block mb-2">{{ __('donations.message') }}:</span>
                                        <span class="text-gray-900 dark:text-white italic">
                                            "{{ $donation->message }}"
                                        </span>
                                    </div>
                                @endif
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
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400 block mb-1">{{ __('campaigns.creator') }}:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $campaign->creator->name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400 block mb-1">{{ __('campaigns.progress') }}:</span>
                                    <span class="text-gray-900 dark:text-white">
                                        {{ format_currency($campaign->current_amount) }} of {{ format_currency($campaign->goal_amount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>

                {{-- Thank You Message --}}
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 mb-8">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-heart text-green-500 flex-shrink-0 mt-1"></i>
                        <div class="text-left">
                            <h4 class="font-semibold text-green-900 dark:text-green-100 mb-2">
                                {{ __('donations.thank_you_title') }}
                            </h4>
                            <p class="text-green-700 dark:text-green-300">
                                {{ __('donations.thank_you_message') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Share Campaign Section --}}
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-8">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 text-center">
                        <i class="fas fa-share-alt mr-2 text-blue-500"></i>
                        {{ __('campaigns.share_campaign') }}
                    </h4>
                    <p class="text-gray-600 dark:text-gray-400 text-center mb-4">
                        {{ __('campaigns.share_message') }}
                    </p>
                    <div class="flex flex-wrap justify-center gap-3">
                        {{-- Facebook Share --}}
                        <button onclick="shareOnFacebook()" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fab fa-facebook-f mr-2"></i>
                            Facebook
                        </button>
                        {{-- Twitter Share --}}
                        <button onclick="shareOnTwitter()" 
                                class="inline-flex items-center px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fab fa-twitter mr-2"></i>
                            Twitter
                        </button>
                        {{-- LinkedIn Share --}}
                        <button onclick="shareOnLinkedIn()" 
                                class="inline-flex items-center px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fab fa-linkedin-in mr-2"></i>
                            LinkedIn
                        </button>
                        {{-- Copy Link --}}
                        <button onclick="copyLink()" 
                                class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-copy mr-2"></i>
                            {{ __('campaigns.copy_link') }}
                        </button>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <x-button href="{{ route('campaigns.show', $campaign->uuid ?? $campaign->id) }}" variant="outline">
                        <i class="fas fa-arrow-left mr-2"></i>
                        {{ __('donations.back_to_campaign') }}
                    </x-button>
                    <x-button href="{{ route('donations.index') }}">
                        <i class="fas fa-list mr-2"></i>
                        {{ __('donations.view_my_donations') }}
                    </x-button>
                    <x-button href="{{ route('campaigns.index') }}" variant="outline">
                        <i class="fas fa-search mr-2"></i>
                        {{ __('campaigns.browse_campaigns') }}
                    </x-button>
                </div>

                {{-- Receipt Info --}}
                <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <div class="flex items-center justify-center gap-2 text-blue-800 dark:text-blue-200">
                        <i class="fas fa-envelope"></i>
                        <span class="text-sm">
                            {{ __('donations.receipt_sent') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        const campaignUrl = '{{ route('campaigns.show', $campaign->uuid ?? $campaign->id) }}';
        const campaignTitle = '{{ addslashes($campaign->getTitle()) }}';
        const campaignDescription = '{{ addslashes(Str::limit($campaign->getDescription(), 100)) }}';
        
        function shareOnFacebook() {
            const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(campaignUrl)}&quote=${encodeURIComponent(`Support "${campaignTitle}" - ${campaignDescription}`)}`;
            window.open(url, 'facebook-share', 'width=580,height=400');
        }
        
        function shareOnTwitter() {
            const text = `Support "${campaignTitle}" - ${campaignDescription}`;
            const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(campaignUrl)}`;
            window.open(url, 'twitter-share', 'width=580,height=400');
        }
        
        function shareOnLinkedIn() {
            const url = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(campaignUrl)}`;
            window.open(url, 'linkedin-share', 'width=580,height=400');
        }
        
        async function copyLink() {
            try {
                await navigator.clipboard.writeText(campaignUrl);
                
                // Show feedback
                const button = event.target.closest('button');
                const originalContent = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check mr-2"></i>{{ __('campaigns.link_copied') }}';
                button.classList.remove('bg-gray-600', 'hover:bg-gray-700');
                button.classList.add('bg-green-600', 'hover:bg-green-700');
                
                setTimeout(() => {
                    button.innerHTML = originalContent;
                    button.classList.remove('bg-green-600', 'hover:bg-green-700');
                    button.classList.add('bg-gray-600', 'hover:bg-gray-700');
                }, 2000);
            } catch (err) {
                console.error('Failed to copy link:', err);
                alert('{{ __('campaigns.copy_failed') }}');
            }
        }
    </script>
</x-layout>