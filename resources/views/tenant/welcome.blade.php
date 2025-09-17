<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tenant->getName() }} - CSR Platform</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased">
    <div class="relative min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        @if($tenant->logo_url)
                            <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->getName() }}" class="h-10 w-auto mr-3">
                        @endif
                        <span class="text-xl font-semibold text-gray-800">{{ $tenant->getName() }}</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('tenant.campaigns.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('navigation.campaigns') }}</a>
                        <a href="{{ route('tenant.about') }}" class="text-gray-600 hover:text-gray-900">{{ __('welcome.tenant.about_us') }}</a>
                        <a href="{{ route('tenant.contact') }}" class="text-gray-600 hover:text-gray-900">{{ __('welcome.tenant.contact') }}</a>
                        @guest('tenant')
                            <a href="{{ route('tenant.login') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">{{ __('navigation.login') }}</a>
                        @else
                            <a href="{{ route('tenant.dashboard') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">{{ __('navigation.dashboard') }}</a>
                        @endguest
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative px-6 lg:px-8">
            <div class="mx-auto max-w-7xl py-24 sm:py-32">
                <div class="text-center">
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">
                        {{ __('welcome.tenant.welcome_to') }} {{ $tenant->getName() }}
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-gray-600">
                        @if($tenant->mission)
                            {{ $tenant->getMission() }}
                        @else
                            {{ __('welcome.tenant.default_mission') }}
                        @endif
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <a href="{{ route('tenant.campaigns.index') }}" class="rounded-md bg-indigo-600 px-6 py-3 text-lg font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            {{ __('welcome.tenant.view_campaigns') }}
                        </a>
                        <a href="{{ route('tenant.about') }}" class="text-lg font-semibold leading-6 text-gray-900">
                            {{ __('welcome.tenant.learn_more') }} <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="py-24 bg-white">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ __('welcome.tenant.our_impact') }}</h2>
                    <p class="mt-4 text-lg text-gray-600">
                        @if($tenant->description)
                            {{ $tenant->getDescription() }}
                        @else
                            {{ __('welcome.tenant.default_description') }}
                        @endif
                    </p>
                </div>

                <!-- Stats -->
                <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-indigo-600">{{ $tenant->campaigns()->count() }}</div>
                        <div class="mt-2 text-lg text-gray-600">{{ __('welcome.tenant.active_campaigns_count') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-indigo-600">{{ $tenant->campaigns()->sum('donors_count') }}</div>
                        <div class="mt-2 text-lg text-gray-600">{{ __('welcome.tenant.total_donors') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-indigo-600">€{{ number_format($tenant->campaigns()->sum('raised_amount'), 0) }}</div>
                        <div class="mt-2 text-lg text-gray-600">{{ __('welcome.tenant.raised_so_far') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-indigo-600">{{ $tenant->employees()->count() }}</div>
                        <div class="mt-2 text-lg text-gray-600">{{ __('welcome.tenant.team_members') }}</div>
                    </div>
                </div>

                <!-- Recent Campaigns -->
                @php
                    $recentCampaigns = $tenant->campaigns()
                        ->where('status', 'active')
                        ->orderBy('created_at', 'desc')
                        ->limit(3)
                        ->get();
                @endphp

                @if($recentCampaigns->count() > 0)
                    <div class="mt-20">
                        <h3 class="text-2xl font-bold text-center text-gray-900 mb-8">{{ __('welcome.tenant.recent_campaigns') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            @foreach($recentCampaigns as $campaign)
                                <div class="bg-gray-50 rounded-lg p-6 hover:shadow-lg transition-shadow">
                                    @if($campaign->featured_image)
                                        <img src="{{ $campaign->featured_image }}" alt="{{ $campaign->getTitle() }}" class="w-full h-48 object-cover rounded-lg mb-4">
                                    @endif
                                    <h4 class="text-xl font-semibold text-gray-900 mb-2">{{ $campaign->getTitle() }}</h4>
                                    <p class="text-gray-600 mb-4">{{ Str::limit($campaign->getDescription(), 100) }}</p>
                                    <div class="mb-4">
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>{{ __('welcome.tenant.raised_label') }}: €{{ number_format($campaign->raised_amount, 0) }}</span>
                                            <span>{{ __('welcome.tenant.goal_label') }}: €{{ number_format($campaign->goal_amount, 0) }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min(100, ($campaign->raised_amount / $campaign->goal_amount) * 100) }}%"></div>
                                        </div>
                                    </div>
                                    <a href="{{ route('tenant.campaigns.show', $campaign->slug) }}" class="text-indigo-600 font-semibold hover:text-indigo-700">
                                        {{ __('welcome.tenant.view_campaign') }} →
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-indigo-600 py-16">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        {{ __('welcome.tenant.ready_to_make_difference') }}
                    </h2>
                    <p class="mt-4 text-lg text-indigo-100">
                        {{ __('welcome.tenant.join_mission') }}
                    </p>
                    <div class="mt-8 flex justify-center gap-x-6">
                        @guest('tenant')
                            <a href="{{ route('tenant.register') }}" class="rounded-md bg-white px-6 py-3 text-lg font-semibold text-indigo-600 shadow-sm hover:bg-gray-100">
                                {{ __('welcome.tenant.join_our_team') }}
                            </a>
                        @endguest
                        <a href="{{ route('tenant.campaigns.index') }}" class="rounded-md border-2 border-white px-6 py-3 text-lg font-semibold text-white hover:bg-white hover:text-indigo-600 transition-colors">
                            {{ __('welcome.tenant.support_campaign') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-12">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ $tenant->getName() }}</h3>
                        <p class="text-gray-400">
                            @if($tenant->address)
                                {{ $tenant->address }}<br>
                            @endif
                            @if($tenant->city || $tenant->postal_code)
                                {{ $tenant->city }} {{ $tenant->postal_code }}<br>
                            @endif
                            @if($tenant->country)
                                {{ $tenant->country }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ __('welcome.tenant.quick_links') }}</h3>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="{{ route('tenant.campaigns.index') }}" class="hover:text-white">{{ __('navigation.campaigns') }}</a></li>
                            <li><a href="{{ route('tenant.about') }}" class="hover:text-white">{{ __('welcome.tenant.about_us') }}</a></li>
                            <li><a href="{{ route('tenant.contact') }}" class="hover:text-white">{{ __('welcome.tenant.contact') }}</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ __('welcome.tenant.legal') }}</h3>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="{{ route('tenant.privacy') }}" class="hover:text-white">{{ __('welcome.tenant.privacy_policy') }}</a></li>
                            <li><a href="{{ route('tenant.terms') }}" class="hover:text-white">{{ __('welcome.tenant.terms_of_service') }}</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ __('welcome.tenant.contact') }}</h3>
                        <ul class="space-y-2 text-gray-400">
                            @if($tenant->email)
                                <li>{{ __('welcome.tenant.email_label') }}: <a href="mailto:{{ $tenant->email }}" class="hover:text-white">{{ $tenant->email }}</a></li>
                            @endif
                            @if($tenant->phone)
                                <li>{{ __('welcome.tenant.phone_label') }}: <a href="tel:{{ $tenant->phone }}" class="hover:text-white">{{ $tenant->phone }}</a></li>
                            @endif
                            @if($tenant->website)
                                <li>{{ __('welcome.tenant.web_label') }}: <a href="{{ $tenant->website }}" target="_blank" class="hover:text-white">{{ parse_url($tenant->website, PHP_URL_HOST) }}</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="mt-8 pt-8 border-t border-gray-800 text-center text-gray-400">
                    <p>&copy; {{ date('Y') }} {{ $tenant->getName() }}. {{ __('welcome.tenant.all_rights_reserved') }}.</p>
                    <p class="mt-2 text-sm">{{ __('welcome.tenant.powered_by') }}</p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>