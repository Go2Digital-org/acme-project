<x-layout title="{{ __('donations.receipt.title') }} #{{ $donation->id }}">
    <div class="px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-4xl mx-auto">
            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-receipt text-2xl text-green-500"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ __('donations.receipt.title') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ __('donations.receipt.receipt_number', ['id' => $donation->id]) }}</p>
            </div>

            {{-- Main Receipt Card --}}
            <x-card class="mb-8">
                {{-- Status Badge --}}
                <div class="flex items-center justify-between mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('donations.receipt.donation_details') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('donations.receipt.transaction_info') }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold {{ $donation->status->getTailwindBadgeClasses() }}">
                        <div class="w-2 h-2 rounded-full mr-2 {{ $donation->status->getTailwindDotClasses() }}"></div>
                        {{ $donation->status->getLabel() }}
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {{-- Left Column - Transaction Details --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('donations.receipt.transaction_information') }}</h3>
                        <div class="space-y-4">
                            {{-- Amount --}}
                            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('donations.amount') }}</span>
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                    @if($donation->currency === 'EUR')
                                        {{ format_currency($donation->amount) }}
                                    @else
                                        {{ $donation->currency }} {{ number_format($donation->amount, 2) }}
                                    @endif
                                </span>
                            </div>

                            {{-- Date and Time --}}
                            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('donations.receipt.date_time') }}</span>
                                <div class="text-right">
                                    <div class="text-gray-900 dark:text-white font-medium">
                                        {{ $donation->donated_at ? $donation->donated_at->format('M j, Y') : $donation->created_at->format('M j, Y') }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $donation->donated_at ? $donation->donated_at->format('g:i A') : $donation->created_at->format('g:i A') }}
                                    </div>
                                </div>
                            </div>

                            {{-- Payment Method --}}
                            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('donations.payment_method') }}</span>
                                <div class="flex items-center gap-2">
                                    <i class="{{ $donation->payment_method->getIcon() }} text-gray-600 dark:text-gray-400"></i>
                                    <span class="text-gray-900 dark:text-white font-medium">
                                        {{ $donation->payment_method->getLabel() }}
                                    </span>
                                </div>
                            </div>

                            {{-- Transaction ID --}}
                            @if($donation->transaction_id)
                                <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('donations.transaction_id') }}</span>
                                    <span class="text-gray-900 dark:text-white font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                        {{ $donation->transaction_id }}
                                    </span>
                                </div>
                            @endif

                            {{-- Donation ID --}}
                            <div class="flex justify-between items-center py-3">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('donations.donation_id') }}</span>
                                <span class="text-gray-900 dark:text-white font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                    #{{ $donation->id }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column - Campaign & Donor Info --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('donations.receipt.campaign_information') }}</h3>
                        <div class="space-y-4">
                            {{-- Campaign Details --}}
                            @if($donation->campaign)
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-hand-holding-heart text-primary"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">
                                                <a href="{{ route('campaigns.show', $donation->campaign) }}" 
                                                   class="hover:text-primary transition-colors">
                                                    {{ $donation->campaign->getTitle() }}
                                                </a>
                                            </h4>
                                            @if($donation->campaign->employee)
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ __('donations.receipt.created_by', ['name' => $donation->campaign->employee->name]) }}
                                                </p>
                                            @endif
                                            @if($donation->campaign->current_amount && $donation->campaign->goal_amount)
                                                <div class="mt-2">
                                                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                                                        <span>{{ __('donations.receipt.progress') }}</span>
                                                        <span>{{ round(($donation->campaign->current_amount / $donation->campaign->goal_amount) * 100) }}%</span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-600">
                                                        <div class="bg-primary h-1.5 rounded-full transition-all duration-300" 
                                                             style="width: {{ min(100, ($donation->campaign->current_amount / $donation->campaign->goal_amount) * 100) }}%"></div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 bg-gray-300/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-question text-gray-400"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-500 dark:text-gray-400 mb-1">
                                                {{ __('Campaign information not available') }}
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Donor Information --}}
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ __('donations.receipt.donor_information') }}</h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">{{ __('donations.receipt.name') }}</span>
                                        <span class="text-gray-900 dark:text-white font-medium">
                                            @if($donation->anonymous)
                                                <i class="fas fa-user-secret mr-1"></i>{{ __('donations.receipt.anonymous_donor') }}
                                            @else
                                                {{ $donation->user->name }}
                                            @endif
                                        </span>
                                    </div>
                                    @if(!$donation->anonymous && $donation->user->email)
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">{{ __('donations.receipt.email') }}</span>
                                            <span class="text-gray-900 dark:text-white">{{ $donation->user->email }}</span>
                                        </div>
                                    @endif
                                    @if($donation->recurring)
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">{{ __('donations.receipt.type') }}</span>
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                                <i class="fas fa-sync-alt mr-1"></i>
                                                {{ __('donations.receipt.recurring_frequency', ['frequency' => $donation->recurring_frequency ?? __('donations.monthly')]) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Notes/Message --}}
                            @if($donation->notes)
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ __('donations.message') }}</h4>
                                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                        <p class="text-blue-800 dark:text-blue-200 italic text-sm">
                                            "{{ $donation->notes }}"
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </x-card>

            {{-- Receipt Section --}}
            @if($donation->isSuccessful())
                <x-card class="mb-8">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('donations.receipt.tax_receipt_title') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                    TAX-{{ date('Y') }}-{{ str_pad($donation->id, 6, '0', STR_PAD_LEFT) }}
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('donations.receipt.tax_receipt_number') }}</p>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                    @if($donation->currency === 'EUR')
                                        {{ format_currency($donation->amount) }}
                                    @else
                                        {{ $donation->currency }} {{ number_format($donation->amount, 2) }}
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('donations.receipt.tax_deductible_amount') }}</p>
                            </div>
                        </div>
                    </div>
                </x-card>
            @endif
            {{-- Footer Notice --}}
            <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ __('donations.receipt.footer_notice') }}
                    @if($donation->isSuccessful())
                        {{ __('donations.receipt.tax_notice') }}
                    @endif
                </p>
            </div>
        </div>
    </div>
</x-layout>