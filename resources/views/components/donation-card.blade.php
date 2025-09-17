<article
    class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-all duration-300 {{ $cardClasses() }}"
    aria-labelledby="donation-title-{{ $donation->id }}"
>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-4">
        {{-- Campaign info --}}
        <div class="flex-1">
            <h3
                id="donation-title-{{ $donation->id }}"
                class="font-semibold text-gray-900 dark:text-white line-clamp-2"
            >
                <a 
                    href="{{ route('campaigns.show', $donation->campaign) }}" 
                    class="hover:text-primary transition-colors"
                >
                    {{ $donation->campaign->getTitle() }}
                </a>
            </h3>
            
            @if($hasOrganizationName())
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $donation->campaign->organization_name }}
                </p>
            @endif
        </div>

        {{-- Status badge --}}
        <div class="flex-shrink-0">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full {{ $statusColor() }}">
                <i class="{{ $statusIcon() }} text-xs"></i>
                {{ ucfirst($donation->status) }}
            </span>
        </div>
    </div>

    {{-- Donation amount --}}
    <div class="mb-4">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ $formattedAmount() }}
        </div>
        
        @if($hasRecurringType())
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ ucfirst($donation->recurring_type) }} donation
            </div>
        @endif
    </div>

    {{-- Donation details --}}
    <div class="space-y-2 mb-4 text-sm">
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Date:</span>
            <span class="text-gray-900 dark:text-white font-medium">
                {{ $donationDate()->format('M j, Y') }}
            </span>
        </div>
        
        @if($hasPaymentMethod())
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Payment:</span>
                <span class="text-gray-900 dark:text-white font-medium flex items-center gap-1">
                    <i class="{{ $paymentMethodIcon() }} text-xs"></i>
                    {{ $donation->payment_method }}
                </span>
            </div>
        @endif
        
        @if($hasTransactionId())
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Transaction ID:</span>
                <span class="text-gray-900 dark:text-white font-mono text-xs">
                    {{ $truncatedTransactionId() }}
                </span>
            </div>
        @endif
    </div>

    {{-- Personal message/note --}}
    @if($hasMessage())
        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <p class="text-sm text-gray-700 dark:text-gray-300 italic">
                "{{ $donation->message }}"
            </p>
        </div>
    @endif

    {{-- Impact information --}}
    @if($hasImpactDescription())
        <div class="mb-4 p-3 bg-primary/5 rounded-lg border border-primary/10">
            <div class="flex items-start gap-2">
                <i class="fas fa-heart text-primary text-sm mt-0.5"></i>
                <div>
                    <h4 class="text-sm font-medium text-primary mb-1">Your Impact</h4>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        {{ $donation->impact_description }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
        @if($canDownloadReceipt())
            <button
                onclick="downloadReceipt('{{ $donation->id }}')"
                class="flex-1 bg-primary text-white px-4 py-2 rounded-lg font-medium text-sm hover:bg-primary-dark transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
            >
                <i class="fas fa-download mr-2"></i>
                Download Receipt
            </button>
        @endif
        
        <button
            onclick="viewCampaign('{{ $donation->campaign->id }}')"
            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300 text-sm"
        >
            <i class="fas fa-external-link-alt mr-2"></i>
            View Campaign
        </button>
        
        @if($canShareImpact())
            <button
                onclick="shareImpact('{{ $donation->id }}')"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300"
                aria-label="Share your impact"
                title="Share your impact"
            >
                <i class="fas fa-share-alt"></i>
            </button>
        @endif
    </div>

    {{-- Tax information --}}
    @if($isTaxDeductible())
        <div class="mt-4 p-3 bg-secondary/5 rounded-lg border border-secondary/10">
            <div class="flex items-center gap-2 text-secondary">
                <i class="fas fa-receipt text-sm"></i>
                <span class="text-sm font-medium">Tax Deductible</span>
            </div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                This donation may be tax deductible. Consult your tax advisor for details.
            </p>
        </div>
    @endif

    {{-- Recurring donation info --}}
    @if($hasRecurringType())
        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
                    <i class="fas fa-sync-alt text-sm"></i>
                    <span class="text-sm font-medium">Recurring Donation</span>
                </div>
                <button class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200">
                    Manage
                </button>
            </div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                Next payment: {{ $nextPaymentDate() }}
            </p>
        </div>
    @endif

    {{-- Hover effect decoration --}}
    <div class="absolute -right-10 -bottom-10 -z-10 h-20 w-20 rounded-full bg-primary/5 opacity-0 blur-2xl transition duration-300 group-hover:opacity-100"></div>
</article>

<script>
    function downloadReceipt(donationId) {
        window.open('/donations/' + donationId + '/receipt', '_blank');
    }
    
    function viewCampaign(campaignId) {
        window.location.href = '/campaigns/' + campaignId;
    }
    
    function shareImpact(donationId) {
        if (navigator.share) {
            navigator.share({
                title: 'I made a difference!',
                text: 'I just donated to a great cause through ACME Corp CSR platform.',
                url: window.location.origin + '/donations/' + donationId + '/impact'
            });
        } else {
            // Fallback to copy to clipboard
            navigator.clipboard.writeText('I just made a donation through ACME Corp CSR platform!');
            alert('Impact message copied to clipboard!');
        }
    }
</script>