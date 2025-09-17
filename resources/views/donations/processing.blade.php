<x-layout title="Processing Donation">
    <section class="py-12" x-data="donationStatus">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center">
                {{-- Processing Icon --}}
                <div class="w-24 h-24 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center mx-auto mb-8" 
                     x-show="!timedOut && (status === 'pending' || status === 'processing')">
                    <i class="fas fa-credit-card text-3xl text-blue-600 dark:text-blue-400 animate-pulse"></i>
                </div>
                
                {{-- Timeout Icon --}}
                <div class="w-24 h-24 bg-yellow-100 dark:bg-yellow-900/20 rounded-full flex items-center justify-center mx-auto mb-8" 
                     x-show="timedOut">
                    <i class="fas fa-hourglass-half text-3xl text-yellow-600 dark:text-yellow-400"></i>
                </div>

                {{-- Dynamic Processing Message --}}
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4"
                    x-text="timedOut ? 'Payment Still Processing' : 'Processing Your Donation'">
                    Processing Your Donation
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-4"
                    x-show="!timedOut"
                    x-text="elapsedTime < 30 ? 'Please wait while we confirm your payment...' : 
                           (elapsedTime < 60 ? 'This is taking a bit longer than usual...' : 
                           (elapsedTime < 90 ? 'Almost there, please wait...' : 
                           'Taking longer than expected. You can check back later.'))"
                    >
                    Please wait while we confirm your payment with our payment provider.
                </p>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-4" x-show="timedOut">
                    Your payment is still being processed in the background. You can safely leave this page and check back later.
                </p>
                
                {{-- Dynamic Timer and Progress Bar --}}
                <div class="mb-8" x-show="!timedOut">
                    <div class="flex items-center justify-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <i class="fas fa-clock text-blue-500"></i>
                        <span>Processing for <span class="font-semibold" x-text="elapsedTime"></span> seconds</span>
                        <span class="text-gray-400">/ 2 minutes max</span>
                    </div>
                    <div class="w-full max-w-xs mx-auto bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-1000 ease-linear" 
                             :style="`width: ${Math.min(100, (elapsedTime/120)*100)}%`"></div>
                    </div>
                </div>

                {{-- Status Card --}}
                <x-card class="text-left mb-8">
                    <div class="space-y-4">
                        {{-- Progress Steps --}}
                        <div class="space-y-3">
                            {{-- Step 1: Donation Created --}}
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-green-500 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white">Donation Created</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Your donation has been registered</p>
                                </div>
                            </div>

                            {{-- Step 2: Payment Processing --}}
                            <div class="flex items-center gap-3" data-step="processing">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                     :class="status === 'pending' ? 'bg-blue-100 dark:bg-blue-900/20' : 'bg-green-100 dark:bg-green-900/20'">
                                    <div x-show="status === 'pending'" class="w-4 h-4 border-2 border-t-blue-500 border-blue-200 dark:border-blue-800 dark:border-t-blue-400 rounded-full animate-spin"></div>
                                    <i x-show="status !== 'pending'" class="fas fa-check text-green-500 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium" :class="status === 'pending' ? 'text-gray-900 dark:text-white' : 'text-gray-900 dark:text-white'">Payment Processing</p>
                                    <p class="text-sm" :class="status === 'pending' ? 'text-gray-500 dark:text-gray-400' : 'text-gray-500 dark:text-gray-400'"
                                       x-text="status === 'pending' ? 'Confirming payment with provider' : 'Payment confirmed'"></p>
                                </div>
                            </div>

                            {{-- Step 3: Confirmation Pending --}}
                            <div class="flex items-center gap-3" data-step="confirmation">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                     :class="status === 'completed' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-gray-100 dark:bg-gray-800'">
                                    <i :class="status === 'completed' ? 'fas fa-check text-green-500 text-sm' : 'fas fa-circle text-gray-400 text-xs'"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium" :class="status === 'completed' ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">Confirmation</p>
                                    <p class="text-sm" :class="status === 'completed' ? 'text-gray-500 dark:text-gray-400' : 'text-gray-400 dark:text-gray-500'"
                                       x-text="status === 'completed' ? 'Payment successfully completed' : 'Waiting for final confirmation'"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Donation Details --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Donation Details</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Amount:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ format_currency($donation->amount) }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Campaign:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ Str::limit($campaign->getTitle(), 30) }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Reference:</span>
                                    <span class="font-mono text-gray-900 dark:text-white">
                                        #{{ $donation->id }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>

                {{-- Information Box --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-8">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-500 flex-shrink-0 mt-1"></i>
                        <div class="text-left">
                            <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                                What's happening?
                            </h4>
                            <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                <li>• Your payment is being securely processed</li>
                                <li>• This usually takes just a few moments</li>
                                <li>• You'll be redirected automatically when complete</li>
                                <li>• A confirmation email will be sent to your address</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Status Indicator --}}
                <div class="text-center text-sm text-gray-600 dark:text-gray-400 mb-8" x-show="!timedOut">
                    <p class="flex items-center justify-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <span>Checking payment status automatically...</span>
                    </p>
                </div>
                
                {{-- Timeout Information --}}
                <div class="text-center mb-8" x-show="timedOut">
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 max-w-md mx-auto">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-yellow-500 flex-shrink-0 mt-1"></i>
                            <div class="text-left">
                                <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-2">
                                    Payment processing is taking longer than expected
                                </h4>
                                <p class="text-sm text-yellow-700 dark:text-yellow-300 mb-3">
                                    Don't worry! Your payment is still being processed. You can:
                                </p>
                                <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                                    <li>• Check back in a few minutes</li>
                                    <li>• Check your email for confirmation</li>
                                    <li>• View your donation history</li>
                                </ul>
                                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-3">
                                    Reference ID: #{{ $donation->id }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <template x-if="timedOut">
                        <button @click="checkNow()" 
                                class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Check Again
                        </button>
                    </template>
                    <x-button href="{{ route('campaigns.show', $campaign->uuid ?? $campaign->id) }}" variant="outline">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <span x-text="timedOut ? 'I\'ll Check Back Later' : 'Return to Campaign'">Return to Campaign</span>
                    </x-button>
                    <x-button href="{{ route('donations.index') }}" variant="outline">
                        <i class="fas fa-list mr-2"></i>
                        View My Donations
                    </x-button>
                </div>
            </div>
        </div>
    </section>

    {{-- AJAX Status Polling Script --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('donationStatus', () => ({
                status: '{{ $donation->status->value }}',
                checkCount: 0,
                maxChecks: 40, // 2 minutes with 3-second intervals
                lastChecked: null,
                isChecking: false,
                elapsedTime: 0,
                timedOut: false,
                intervalTimer: null,
                
                init() {
                    // Start elapsed time counter
                    this.intervalTimer = setInterval(() => {
                        this.elapsedTime++;
                        if (this.elapsedTime >= 120 && !this.timedOut) {
                            this.handleTimeout();
                        }
                    }, 1000);
                    
                    this.checkStatus();
                },
                
                async checkStatus() {
                    if (this.isChecking) return;
                    
                    this.isChecking = true;
                    
                    try {
                        const response = await fetch('{{ route('donations.status', $donation) }}', {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin'
                        });
                        
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        
                        const data = await response.json();
                        this.lastChecked = new Date();
                        
                        // Update UI based on status
                        if (data.status !== this.status) {
                            this.status = data.status;
                            this.updateProgressSteps(data.status);
                        }
                        
                        // Redirect if payment is complete or failed
                        if (data.redirect_url) {
                            setTimeout(() => {
                                window.location.href = data.redirect_url;
                            }, 1000); // Small delay for UI update
                            return;
                        }
                        
                        // Continue polling if still pending and not timed out
                        this.checkCount++;
                        if (!this.timedOut && this.checkCount < this.maxChecks && (data.status === 'pending' || data.status === 'processing')) {
                            setTimeout(() => {
                                this.isChecking = false;
                                this.checkStatus();
                            }, 3000); // Check every 3 seconds
                        } else if (this.checkCount >= this.maxChecks && !this.timedOut) {
                            this.handleTimeout();
                        }
                    } catch (error) {
                        console.error('Error checking donation status:', error);
                        // Retry after a longer delay on error
                        setTimeout(() => {
                            this.isChecking = false;
                            this.checkStatus();
                        }, 5000);
                    }
                },
                
                updateProgressSteps(status) {
                    // Update the visual progress indicators based on status
                    const steps = {
                        created: document.querySelector('[data-step="created"]'),
                        processing: document.querySelector('[data-step="processing"]'),
                        confirmation: document.querySelector('[data-step="confirmation"]')
                    };
                    
                    if (status === 'processing' || status === 'completed') {
                        // Show processing as complete
                        if (steps.processing) {
                            steps.processing.innerHTML = `
                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-green-500 text-sm"></i>
                                </div>
                            `;
                        }
                    }
                    
                    if (status === 'completed') {
                        // Show confirmation as complete
                        if (steps.confirmation) {
                            steps.confirmation.innerHTML = `
                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-green-500 text-sm"></i>
                                </div>
                            `;
                        }
                    }
                },
                
                handleTimeout() {
                    this.timedOut = true;
                    // Stop auto-polling
                    if (this.intervalTimer) {
                        clearInterval(this.intervalTimer);
                    }
                },
                
                checkNow() {
                    // Manual check after timeout
                    this.timedOut = false;
                    this.checkCount = 0;
                    this.elapsedTime = 0;
                    
                    // Restart timer
                    this.intervalTimer = setInterval(() => {
                        this.elapsedTime++;
                        if (this.elapsedTime >= 120 && !this.timedOut) {
                            this.handleTimeout();
                        }
                    }, 1000);
                    
                    this.checkStatus();
                }
            }));
        });
    </script>
    
</x-layout>