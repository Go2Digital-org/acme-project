<x-layout title="My Donations">
    <section class="py-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">My Donations</h1>
                        <p class="text-xl text-gray-600 dark:text-gray-300">
                            Track your contributions and see the impact you've made
                        </p>
                    </div>
                    {{-- Export Actions --}}
                    <div class="flex items-center gap-3">
                        <x-button
                            href="{{ route('exports.manage') }}"
                            variant="outline">
                            <i class="fas fa-history mr-2"></i>
                            Manage Exports
                        </x-button>
                    </div>
                </div>
            </div>

            {{-- Stats Overview --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                <x-card class="text-center">
                    <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-donate text-2xl text-primary"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                        {{ format_currency($totalDonated) }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">Total Donated</p>
                </x-card>

                <x-card class="text-center">
                    <div class="w-12 h-12 bg-secondary/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-hand-holding-heart text-2xl text-secondary"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                        {{ $campaignsSupported }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">Campaigns Supported</p>
                </x-card>

                <x-card class="text-center">
                    <div class="w-12 h-12 bg-accent/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-calendar text-2xl text-accent"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                        {{ $thisYearDonations }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">This Year</p>
                </x-card>

                <x-card class="text-center">
                    <div class="w-12 h-12 bg-corporate/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-2xl text-corporate"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                        {{ format_currency($averageDonation) }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">Average Donation</p>
                </x-card>
            </div>

            {{-- Filters --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
                <form method="GET" action="{{ route('donations.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Year</label>
                        <select name="year" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">All Years</option>
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Campaign</label>
                        <select name="campaign" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">All Campaigns</option>
                            @foreach($supportedCampaigns as $campaign)
                                <option value="{{ $campaign->id }}" {{ request('campaign') == $campaign->id ? 'selected' : '' }}>
                                    {{ is_object($campaign) && method_exists($campaign, 'getTitle') ? $campaign->getTitle() : $campaign->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                        <select name="sort" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest First</option>
                            <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                            <option value="amount_desc" {{ request('sort') === 'amount_desc' ? 'selected' : '' }}>Highest Amount</option>
                            <option value="amount_asc" {{ request('sort') === 'amount_asc' ? 'selected' : '' }}>Lowest Amount</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <x-button type="submit" class="flex-1">
                            <i class="fas fa-filter mr-2"></i>
                            Apply Filters
                        </x-button>
                        @if(request()->query())
                            <x-button href="{{ route('donations.index') }}" variant="outline">
                                <i class="fas fa-times"></i>
                            </x-button>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Donations Table --}}
            @if($donations->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Campaign
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Payment Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Campaign Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Progress
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($donations as $donation)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        {{-- Campaign --}}
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-700">
                                                    @if($donation->campaign && $donation->campaign->featured_image)
                                                        <img 
                                                            src="{{ $donation->campaign->featured_image_url ?: asset('images/placeholder.png') }}" 
                                                            alt="{{ $donation->campaign->getTitle() }}"
                                                            class="h-full w-full object-cover"
                                                            loading="lazy"
                                                            onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                        />
                                                        <div class="h-full w-full flex items-center justify-center" style="display: none;">
                                                            <i class="fas fa-hand-holding-heart text-gray-400"></i>
                                                        </div>
                                                    @else
                                                        <div class="h-full w-full flex items-center justify-center">
                                                            <i class="fas fa-hand-holding-heart text-gray-400"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    @if($donation->campaign)
                                                        <a 
                                                            href="{{ route('campaigns.show', $donation->campaign) }}" 
                                                            class="font-medium text-gray-900 dark:text-white hover:text-primary transition-colors"
                                                        >
                                                            {{ $donation->campaign->getTitle() }}
                                                        </a>
                                                        @if($donation->campaign->category)
                                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                                {{ ucfirst($donation->campaign->category) }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="font-medium text-gray-500 dark:text-gray-400">
                                                            Campaign Not Available
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Amount --}}
                                        <td class="px-6 py-4">
                                            <div class="text-lg font-semibold text-primary">
                                                {{ format_currency($donation->amount) }}
                                            </div>
                                        </td>

                                        {{-- Date --}}
                                        <td class="px-6 py-4">
                                            <div class="text-sm">
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ $donation->created_at->format('M j, Y') }}
                                                </div>
                                                <div class="text-gray-500 dark:text-gray-400">
                                                    {{ $donation->created_at->format('g:i A') }}
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Payment Status --}}
                                        <td class="px-6 py-4">
                                            @if($donation->status)
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium 
                                                    {{ $donation->status->value === 'completed' || $donation->status->value === 'successful' ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200' : '' }}
                                                    {{ $donation->status->value === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200' : '' }}
                                                    {{ $donation->status->value === 'processing' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200' : '' }}
                                                    {{ $donation->status->value === 'failed' ? 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200' : '' }}
                                                    {{ $donation->status->value === 'cancelled' || $donation->status->value === 'canceled' ? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' : '' }}
                                                ">
                                                    <i class="fas fa-{{ $donation->status->value === 'completed' || $donation->status->value === 'successful' ? 'check-circle' : ($donation->status->value === 'pending' ? 'clock' : ($donation->status->value === 'processing' ? 'spinner fa-spin' : ($donation->status->value === 'failed' ? 'times-circle' : 'ban'))) }} mr-1 text-xs"></i>
                                                    {{ $donation->status->getLabel() }}
                                                </span>
                                            @else
                                                <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                            @endif
                                        </td>

                                        {{-- Campaign Status --}}
                                        <td class="px-6 py-4">
                                            @if($donation->campaign && $donation->campaign->status)
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium 
                                                    {{ $donation->campaign->status->value === 'active' ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200' : '' }}
                                                    {{ $donation->campaign->status->value === 'completed' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200' : '' }}
                                                    {{ $donation->campaign->status->value === 'paused' ? 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200' : '' }}
                                                ">
                                                    {{ $donation->campaign->status->getLabel() }}
                                                </span>
                                            @else
                                                <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                            @endif
                                        </td>

                                        {{-- Progress --}}
                                        <td class="px-6 py-4">
                                            @if($donation->campaign)
                                                <div class="w-32">
                                                    <div class="flex items-center gap-2">
                                                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                            <div 
                                                                class="bg-primary h-2 rounded-full transition-all duration-300"
                                                                style="width: {{ min(100, ($donation->campaign->current_amount / $donation->campaign->goal_amount) * 100) }}%"
                                                            ></div>
                                                        </div>
                                                        <span class="text-xs text-gray-600 dark:text-gray-400">
                                                            {{ number_format(min(100, ($donation->campaign->current_amount / $donation->campaign->goal_amount) * 100), 0) }}%
                                                        </span>
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        {{ format_currency($donation->campaign->current_amount) }} / {{ format_currency($donation->campaign->goal_amount) }}
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                            @endif
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if($donation->campaign)
                                                    <a 
                                                        href="{{ route('campaigns.show', $donation->campaign) }}" 
                                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-md transition-colors"
                                                    >
                                                        <i class="fas fa-eye mr-1"></i>
                                                        Campaign
                                                    </a>
                                                @endif
                                                <a 
                                                    href="{{ route('donations.show', $donation) }}" 
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-md transition-colors"
                                                >
                                                    <i class="fas fa-receipt mr-1"></i>
                                                    Details
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Pagination --}}
                @if($donations->hasPages())
                    <div class="mt-8 flex justify-center">
                        {{ $donations->links() }}
                    </div>
                @endif
            @else
                {{-- No Donations --}}
                <x-card class="text-center py-16">
                    <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-heart text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                        @if(request()->query())
                            No Donations Found
                        @else
                            No Donations Yet
                        @endif
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
                        @if(request()->query())
                            No donations match your current filters. Try adjusting your search criteria.
                        @else
                            You haven't made any donations yet. Explore campaigns and start making a difference!
                        @endif
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        @if(request()->query())
                            <x-button href="{{ route('donations.index') }}" variant="outline">
                                <i class="fas fa-times mr-2"></i>
                                Clear Filters
                            </x-button>
                        @endif
                        <x-button href="{{ route('campaigns.index') }}">
                            <i class="fas fa-search mr-2"></i>
                            Browse Campaigns
                        </x-button>
                    </div>
                </x-card>
            @endif

            
            {{-- Include Export Progress Modal --}}
            {{-- <x-export-progress-modal export-type="donations" /> --}}
        </div>
    </section>
</x-layout>