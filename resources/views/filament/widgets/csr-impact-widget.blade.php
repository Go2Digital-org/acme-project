<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('widgets.csr_impact.title') }}
        </x-slot>

        <x-slot name="description">
            {{ __('widgets.csr_impact.description') }}
        </x-slot>

        <div class="space-y-6">
            {{-- Impact Metrics Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($impactMetrics as $key => $metric)
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ $metric['label'] }}
                                </p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    @if($metric['format'] ?? null === 'currency')
                                        ${{ number_format($metric['value'], 0) }}
                                    @else
                                        {{ is_numeric($metric['value']) ? number_format($metric['value']) : $metric['value'] }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                @php
                                    $iconColor = match($metric['color']) {
                                        'success' => 'text-green-500',
                                        'primary' => 'text-blue-500',
                                        'info' => 'text-cyan-500',
                                        'warning' => 'text-yellow-500',
                                        'danger' => 'text-red-500',
                                        default => 'text-gray-500'
                                    };
                                @endphp
                                <x-heroicon-o-heart class="w-8 h-8 {{ $iconColor }}" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Main Content Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Top Causes --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <x-heroicon-o-heart class="w-5 h-5 mr-2 text-red-500" />
                        {{ __('widgets.csr_impact.sections.top_causes') }}
                    </h3>
                    
                    <div class="space-y-3">
                        @forelse($topCauses as $cause)
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $cause['category'] }}
                                </span>
                                <span class="text-sm font-bold text-green-600 dark:text-green-400">
                                    {{ $cause['formatted_amount'] }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                @php
                                    $maxAmount = $topCauses[0]['amount'] ?? 1;
                                    $percentage = ($cause['amount'] / $maxAmount) * 100;
                                @endphp
                                <div class="bg-primary dark:bg-primary-dark h-2 rounded-full transition-all duration-300" 
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                        @empty
                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                                {{ __('widgets.csr_impact.status.no_data') }}
                            </p>
                        @endforelse
                    </div>
                </div>

                {{-- Recent Milestones --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <x-heroicon-o-star class="w-5 h-5 mr-2 text-yellow-500" />
                        {{ __('widgets.csr_impact.sections.recent_milestones') }}
                    </h3>
                    
                    <div class="space-y-4">
                        @forelse($recentMilestones as $milestone)
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    @php
                                        $iconColor = match($milestone['color']) {
                                            'success' => 'text-green-500',
                                            'primary' => 'text-blue-500',
                                            'info' => 'text-cyan-500',
                                            'warning' => 'text-yellow-500',
                                            'danger' => 'text-red-500',
                                            default => 'text-gray-500'
                                        };
                                    @endphp
                                    <x-heroicon-o-trophy class="w-5 h-5 {{ $iconColor }}" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $milestone['title'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $milestone['description'] }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        {{ $milestone['date']->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                                {{ __('widgets.csr_impact.status.no_milestones') }}
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Upcoming Goals --}}
            @if(count($upcomingGoals) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <x-heroicon-o-flag class="w-5 h-5 mr-2 text-blue-500" />
                        {{ __('widgets.csr_impact.sections.upcoming_goals') }}
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($upcomingGoals as $goal)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $goal['title'] }}
                                    </h4>
                                    @php
                                        $iconColor = match($goal['color']) {
                                            'success' => 'text-green-500',
                                            'primary' => 'text-blue-500',
                                            'info' => 'text-cyan-500',
                                            'warning' => 'text-yellow-500',
                                            'danger' => 'text-red-500',
                                            default => 'text-gray-500'
                                        };
                                    @endphp
                                    <x-heroicon-o-flag class="w-4 h-4 {{ $iconColor }}" />
                                </div>
                                
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                                    {{ $goal['description'] }}
                                </p>
                                
                                @if(isset($goal['progress']))
                                    <div class="mb-2">
                                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                                            <span>{{ __('widgets.csr_impact.sections.progress') }}</span>
                                            <span>{{ number_format($goal['progress'], 1) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-primary dark:bg-primary-dark h-2 rounded-full transition-all duration-300" 
                                                 style="width: {{ min(100, $goal['progress']) }}%"></div>
                                        </div>
                                    </div>
                                @endif
                                
                                @if(isset($goal['days_left']))
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('widgets.csr_impact.time.days_remaining', ['count' => $goal['days_left']]) }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>