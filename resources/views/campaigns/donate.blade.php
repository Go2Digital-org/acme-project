<x-layout :title="'Donate to ' . $campaign->getTitle()">
    <section class="py-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-6xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    {{-- Campaign Summary --}}
                    <div>
                        {{-- Campaign Header --}}
                        <div class="mb-8">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium status-{{ $campaign->status->value }}">
                                    <i class="fas fa-{{ $campaign->status->value === 'active' ? 'play' : ($campaign->status->value === 'completed' ? 'check' : 'pause') }} mr-2"></i>
                                    {{ $campaign->status->getLabel() }}
                                </span>
                                @if($campaign->category)
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        <i class="fas fa-tag mr-2"></i>
                                        {{ ucfirst($campaign->category) }}
                                    </span>
                                @endif
                            </div>
                            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                                {{ $campaign->getTitle() }}
                            </h1>
                            <p class="text-lg text-gray-600 dark:text-gray-300 mb-6">
                                Created by <strong>{{ $campaign->creator?->getName() ?? 'Unknown' }}</strong>
                            </p>
                        </div>

                        {{-- Campaign Image --}}
                        @if($campaign->featured_image)
                            <div class="aspect-video overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800 mb-8">
                                <img 
                                    src="{{ $campaign->featured_image_url ?: asset('images/placeholder.png') }}" 
                                    alt="{{ $campaign->getTitle() }}"
                                    class="h-full w-full object-cover"
                                    onerror="this.onerror=null; this.src='{{ asset('images/placeholder.png') }}';"
                                />
                            </div>
                        @endif

                        {{-- Fancy Progress Section --}}
                        <x-card class="mb-8">
                            @php
                                $urgencyLevel = match(true) {
                                    !$campaign->status->value === 'active' => 'inactive',
                                    $daysLeft === 0 => 'critical',
                                    $daysLeft <= 3 => 'very-high',
                                    $daysLeft <= 7 => 'high',
                                    $daysLeft <= 14 => 'medium',
                                    default => 'normal'
                                };
                                
                                $showCelebration = $progressPercentage >= 100 || 
                                                  ($progressPercentage >= 75 && $campaign->donations_count > 50);
                                
                                $remainingAmount = max(0, $campaign->goal_amount - $campaign->current_amount);
                            @endphp
                            
                            {{-- Fancy Progress Bar --}}
                            <x-fancy-progress-bar
                                :current="$campaign->current_amount"
                                :goal="$campaign->goal_amount"
                                :percentage="$progressPercentage"
                                :showStats="true"
                                :showMilestones="true"
                                :animated="true"
                                size="default"
                                :donorCount="$campaign->donations_count"
                                :daysRemaining="$daysLeft >= 0 ? $daysLeft : null"
                                :urgencyLevel="$urgencyLevel"
                                :showCelebration="$showCelebration"
                                class="mb-6"
                            />
                            
                            {{-- Additional Stats --}}
                            <div class="grid grid-cols-3 gap-4 text-center pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ $campaign->donations_count }}
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Total Donors</p>
                                </div>
                                <div>
                                    <div class="text-lg font-bold {{ $daysLeft <= 7 ? 'text-orange-500' : 'text-gray-900 dark:text-white' }}">
                                        {{ abs($daysLeft) }}
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        Days {{ $daysLeft >= 0 ? 'Remaining' : 'Ended' }}
                                    </p>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ format_currency($remainingAmount) }}
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Still Needed</p>
                                </div>
                            </div>
                        </x-card>

                        {{-- Campaign Description --}}
                        <x-card>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">About This Campaign</h2>
                            <div class="prose prose-gray max-w-none dark:prose-invert">
                                {!! nl2br(e(Str::limit($campaign->getDescription(), 500))) !!}
                                @if(strlen($campaign->getDescription()) > 500)
                                    <div class="mt-4">
                                        <x-button href="{{ route('campaigns.show', $campaign->uuid ?? $campaign->id) }}" variant="ghost" size="sm">
                                            Read More
                                        </x-button>
                                    </div>
                                @endif
                            </div>
                        </x-card>
                    </div>

                    {{-- Donation Form --}}
                    <div>
                        <x-card>
                            <div class="mb-6">
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Make a Donation</h2>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Your contribution will help make a difference
                                </p>
                            </div>

                            @if($daysLeft >= 0 && $campaign->status->value === 'active')
                                <form action="{{ route('campaigns.donations.store', $campaign->id) }}" method="POST" class="space-y-6" x-data="donationForm()">
                                    @csrf
                                    
                                    {{-- Donation Amount --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                            Donation Amount <span class="text-red-500">*</span>
                                        </label>
                                        
                                        {{-- Quick Amount Buttons --}}
                                        <div class="grid grid-cols-3 gap-2 mb-4">
                                            @foreach([25, 50, 100, 250, 500, 1000] as $amount)
                                                <button 
                                                    type="button"
                                                    @click="setAmount({{ $amount }})"
                                                    :class="selectedAmount === {{ $amount }} ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'"
                                                    class="px-4 py-3 rounded-lg font-medium transition-colors"
                                                >
                                                    {{ currency_symbol() }}{{ $amount }}
                                                </button>
                                            @endforeach
                                        </div>
                                        
                                        {{-- Custom Amount Input --}}
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 dark:text-gray-400">€</span>
                                            </div>
                                            <input
                                                type="number"
                                                name="amount"
                                                id="amount"
                                                min="1"
                                                step="1"
                                                x-model="customAmount"
                                                @input="selectedAmount = null"
                                                placeholder="Enter custom amount"
                                                class="block w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
                                                required
                                            >
                                        </div>
                                        @error('amount')
                                            <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Donation Message --}}
                                    <x-textarea
                                        name="message"
                                        label="Message (Optional)"
                                        placeholder="Leave a message of support..."
                                        rows="3"
                                        :error="$errors->first('message')"
                                        hint="Your message will be visible to others unless you choose to donate anonymously"
                                    />

                                    {{-- Donation Options --}}
                                    <div class="space-y-4">
                                        <div class="flex items-center gap-3">
                                            <input 
                                                type="checkbox" 
                                                id="is_anonymous" 
                                                name="is_anonymous" 
                                                class="rounded border-gray-300 text-primary focus:ring-primary"
                                                {{ old('is_anonymous') ? 'checked' : '' }}
                                            >
                                            <label for="is_anonymous" class="text-gray-700 dark:text-gray-300">
                                                Make this donation anonymous
                                            </label>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <input type="hidden" name="subscribe_updates" value="0">
                                            <input 
                                                type="checkbox" 
                                                id="subscribe_updates" 
                                                name="subscribe_updates" 
                                                value="1"
                                                class="rounded border-gray-300 text-primary focus:ring-primary"
                                                {{ old('subscribe_updates', true) ? 'checked' : '' }}
                                            >
                                            <label for="subscribe_updates" class="text-gray-700 dark:text-gray-300">
                                                Receive campaign updates via email
                                            </label>
                                        </div>
                                    </div>

                                    {{-- Secure Payment Badge --}}
                                    <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                        <div class="flex items-start gap-3">
                                            <i class="fas fa-shield-alt text-green-500 mt-1"></i>
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-900 dark:text-white mb-1">
                                                    Secure Payment Processing
                                                </p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    Your payment will be processed securely through our trusted payment partners. Multiple payment methods are available at checkout including cards, PayPal, iDEAL, and more.
                                                </p>
                                                <div class="flex items-center gap-4 mt-3">
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        <i class="fas fa-lock text-green-500 mr-1"></i>
                                                        SSL Encrypted
                                                    </span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                                        PCI Compliant
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Tax Information --}}
                                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                        <div class="flex items-start gap-3">
                                            <i class="fas fa-receipt text-green-500 flex-shrink-0 mt-1"></i>
                                            <div>
                                                <p class="font-medium text-green-900 dark:text-green-100 mb-1">Tax Deductible</p>
                                                <p class="text-sm text-green-700 dark:text-green-300">
                                                    Your donation may be tax-deductible. You'll receive a receipt for your records.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Submit Button --}}
                                    <div class="pt-4">
                                        <x-button 
                                            type="submit" 
                                            size="lg" 
                                            fullWidth
                                            x-bind:disabled="!customAmount || customAmount < 1"
                                        >
                                            <i class="fas fa-heart mr-2"></i>
                                            <span x-text="'Donate €' + (customAmount || 0)">Donate</span>
                                        </x-button>
                                        
                                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-3">
                                            By clicking "Donate" you agree to our <a href="/terms" class="text-primary hover:underline">Terms of Service</a>
                                            and will be redirected for secure payment processing
                                        </p>
                                    </div>
                                </form>
                            @else
                                {{-- Campaign Ended --}}
                                <div class="text-center py-8">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-clock text-2xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                        Campaign Has Ended
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                                        This campaign ended on {{ $campaign->end_date->format('M j, Y') }}
                                    </p>
                                    <x-button href="{{ route('campaigns.index') }}" variant="outline">
                                        <i class="fas fa-search mr-2"></i>
                                        Browse Other Campaigns
                                    </x-button>
                                </div>
                            @endif
                        </x-card>

                        {{-- Recent Donations --}}
                        @if($recentDonations->count() > 0)
                            <x-card class="mt-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Donations</h3>
                                <div class="space-y-3">
                                    @foreach($recentDonations->take(5) as $donation)
                                        <div class="flex items-center justify-between py-2">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-secondary/10 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-heart text-secondary text-sm"></i>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-white text-sm">
                                                        {{ $donation->is_anonymous ? 'Anonymous' : $donation->donor->name }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $donation->created_at->diffForHumans() }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ format_currency($donation->amount) }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </x-card>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function donationForm() {
            return {
                selectedAmount: 25,
                customAmount: '25',
                
                setAmount(amount) {
                    this.selectedAmount = amount;
                    this.customAmount = amount.toString();
                }
            }
        }
    </script>
</x-layout>