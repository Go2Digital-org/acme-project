@props([
    'exportType' => 'donations',
    'filters' => [],
    'size' => 'default', // 'small', 'default', 'large'
    'variant' => 'primary', // 'primary', 'secondary', 'outline', 'ghost'
    'disabled' => false,
    'showIcon' => true,
    'showProgress' => false,
    'dropdownOptions' => null // For advanced export options
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';
    
    // Size classes
    $sizeClasses = match($size) {
        'small' => 'px-3 py-1.5 text-sm',
        'large' => 'px-6 py-3 text-base',
        default => 'px-4 py-2 text-sm'
    };
    
    // Variant classes
    $variantClasses = match($variant) {
        'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500',
        'outline' => 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-blue-500',
        'ghost' => 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 focus:ring-blue-500',
        default => 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500'
    };
    
    $disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';
    $borderRadius = 'rounded-lg';
    
    $finalClasses = implode(' ', array_filter([
        $baseClasses,
        $sizeClasses,
        $variantClasses,
        $borderRadius,
        $disabledClasses
    ]));
    
    // Export type configurations
    $exportConfigs = [
        'donations' => [
            'label' => __('components.export.donations'),
            'icon' => 'fas fa-heart',
            'description' => __('components.export.donations_description')
        ],
        'reports' => [
            'label' => __('components.export.reports'),
            'icon' => 'fas fa-chart-bar',
            'description' => __('components.export.reports_description')
        ],
        'users' => [
            'label' => __('components.export.users'),
            'icon' => 'fas fa-users',
            'description' => __('components.export.users_description')
        ]
    ];
    
    $config = $exportConfigs[$exportType] ?? $exportConfigs['donations'];
@endphp

<div 
    x-data="exportButton(@js([
        'exportType' => $exportType,
        'filters' => $filters,
        'showProgress' => $showProgress,
        'dropdownOptions' => $dropdownOptions
    ]))"
    class="relative"
>
    @if($dropdownOptions)
        <!-- Dropdown Export Button -->
        <div class="relative" x-data="{ open: false }">
            <button 
                type="button"
                @click="open = !open"
                :disabled="loading"
                {{ $attributes->merge(['class' => $finalClasses . ' pr-8']) }}
            >
                <div class="flex items-center gap-2">
                    @if($showProgress && $slot->isEmpty())
                        <!-- Progress mode -->
                        <div x-show="loading" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ __('components.export.exporting') }}</span>
                            <span class="text-xs opacity-75" x-text="progress + '%'"></span>
                        </div>
                        <div x-show="!loading" class="flex items-center gap-2">
                            @if($showIcon)
                                <i class="{{ $config['icon'] }} text-sm"></i>
                            @endif
                            <span>{{ $config['label'] }}</span>
                        </div>
                    @elseif($slot->isEmpty())
                        <!-- Default content -->
                        @if($showIcon)
                            <i class="{{ $config['icon'] }} text-sm"></i>
                        @endif
                        <span>{{ $config['label'] }}</span>
                    @else
                        <!-- Custom content -->
                        {{ $slot }}
                    @endif
                </div>
                
                <!-- Dropdown arrow -->
                <i class="fas fa-chevron-down absolute right-2 top-1/2 transform -translate-y-1/2 text-xs transition-transform" 
                   :class="{ 'rotate-180': open }"></i>
            </button>
            
            <!-- Dropdown Menu -->
            <div 
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                @click.outside="open = false"
                class="absolute right-0 z-50 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1"
            >
                @foreach($dropdownOptions as $option)
                    <button 
                        type="button"
                        @click="startExportWithOptions('{{ $option['value'] }}', '{{ $option['format'] ?? 'csv' }}'); open = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3"
                    >
                        <i class="{{ $option['icon'] ?? 'fas fa-file-export' }} text-blue-600 dark:text-blue-400"></i>
                        <div>
                            <div class="font-medium">{{ $option['label'] }}</div>
                            @if(!empty($option['description']))
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $option['description'] }}</div>
                            @endif
                        </div>
                    </button>
                @endforeach
                
                <hr class="my-1 border-gray-200 dark:border-gray-700">
                
                <!-- Advanced options -->
                <button 
                    type="button"
                    @click="showAdvancedOptions(); open = false"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3"
                >
                    <i class="fas fa-cog text-gray-600 dark:text-gray-400"></i>
                    <span>{{ __('components.export.advanced_options') }}</span>
                </button>
            </div>
        </div>
    @else
        <!-- Simple Export Button -->
        <button 
            type="button"
            @click="startExport"
            :disabled="loading"
            {{ $attributes->merge(['class' => $finalClasses]) }}
        >
            @if($showProgress)
                <!-- Progress mode -->
                <div x-show="loading" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ __('components.export.exporting') }}</span>
                    <span class="text-xs opacity-75" x-text="progress + '%'"></span>
                </div>
                <div x-show="!loading" class="flex items-center gap-2">
                    @if($showIcon)
                        <i class="{{ $config['icon'] }} text-sm"></i>
                    @endif
                    <span>{{ $slot->isEmpty() ? $config['label'] : $slot }}</span>
                </div>
            @elseif($slot->isEmpty())
                <!-- Default content -->
                @if($showIcon)
                    <i class="{{ $config['icon'] }} text-sm"></i>
                @endif
                <span>{{ $config['label'] }}</span>
            @else
                <!-- Custom content -->
                {{ $slot }}
            @endif
        </button>
    @endif
    
    <!-- Tooltip -->
    @if(!$slot->isEmpty() || !empty($config['description']))
        <div 
            x-data="{ show: false }"
            @mouseenter="show = true"
            @mouseleave="show = false"
            class="absolute inset-0 pointer-events-none"
        >
            <div 
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 text-sm bg-gray-900 dark:bg-gray-700 text-white rounded-lg shadow-lg z-50 whitespace-nowrap"
            >
                {{ $config['description'] }}
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
            </div>
        </div>
    @endif
</div>

@once
@push('scripts')
<script>
if (!window.exportButton) {
    window.exportButton = function(config) {
    return {
        exportType: config.exportType,
        filters: config.filters || {},
        loading: false,
        progress: 0,
        
        async startExport() {
            console.log('Export button clicked', this.exportType, this.loading);
            if (this.loading) return;
            
            this.loading = true;
            this.progress = 0;
            
            try {
                // Always use event dispatch since exportHandler listens for this event
                console.log('Dispatching start-export event for type:', this.exportType);
                document.dispatchEvent(new CustomEvent('start-export', {
                    detail: {
                        exportType: this.exportType,
                        filters: this.filters
                    }
                }));
                
                // Listen for progress updates if progress is enabled
                @if($showProgress)
                    this.listenForProgress();
                @endif
                
                // Reset loading state after a timeout if no handler responds
                setTimeout(() => {
                    if (this.loading) {
                        console.log('No response from export handler, resetting loading state');
                        this.loading = false;
                    }
                }, 5000);
                
            } catch (error) {
                console.error('Error starting export:', error);
                this.loading = false;
                if (window.toast) {
                    window.toast.error('{{ __('components.export.export_failed') }}');
                }
            }
        },
        
        async startExportWithOptions(option, format = 'csv') {
            if (this.loading) return;
            
            this.loading = true;
            this.progress = 0;
            
            try {
                const enhancedFilters = {
                    ...this.filters,
                    export_option: option,
                    format: format
                };
                
                document.dispatchEvent(new CustomEvent('start-export', {
                    detail: {
                        exportType: this.exportType,
                        filters: enhancedFilters
                    }
                }));
                
                @if($showProgress)
                    this.listenForProgress();
                @endif
                
            } catch (error) {
                console.error('Error starting export with options:', error);
                this.loading = false;
            }
        },
        
        showAdvancedOptions() {
            // Show advanced export options modal
            if (window.Swal) {
                Swal.fire({
                    title: '{{ __('components.export.advanced_export_options') }}',
                    html: this.getAdvancedOptionsHtml(),
                    showCancelButton: true,
                    confirmButtonText: '{{ __('components.export.start_export') }}',
                    preConfirm: () => {
                        return this.getAdvancedFormData();
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.startExportWithAdvancedOptions(result.value);
                    }
                });
            }
        },
        
        getAdvancedOptionsHtml() {
            return `
                <div class="text-left space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.export.export_format') }}</label>
                        <select id="export-format" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="csv">{{ __('components.export.csv') }}</option>
                            <option value="xlsx">{{ __('components.export.xlsx') }}</option>
                            <option value="json">{{ __('components.export.json') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.export.date_range') }}</label>
                        <select id="date-range" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">{{ __('components.export.all_time') }}</option>
                            <option value="today">{{ __('components.today') }}</option>
                            <option value="week">{{ __('components.export.this_week') }}</option>
                            <option value="month">{{ __('components.export.this_month') }}</option>
                            <option value="quarter">{{ __('components.export.this_quarter') }}</option>
                            <option value="year">{{ __('components.export.this_year') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="include-archived" class="mr-2">
                            <span class="text-sm text-gray-700">{{ __('components.export.include_archived') }}</span>
                        </label>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="include-metadata" class="mr-2" checked>
                            <span class="text-sm text-gray-700">{{ __('components.export.include_metadata') }}</span>
                        </label>
                    </div>
                </div>
            `;
        },
        
        getAdvancedFormData() {
            return {
                format: document.getElementById('export-format').value,
                dateRange: document.getElementById('date-range').value,
                includeArchived: document.getElementById('include-archived').checked,
                includeMetadata: document.getElementById('include-metadata').checked
            };
        },
        
        async startExportWithAdvancedOptions(options) {
            if (this.loading) return;
            
            this.loading = true;
            this.progress = 0;
            
            try {
                const enhancedFilters = {
                    ...this.filters,
                    ...options
                };
                
                document.dispatchEvent(new CustomEvent('start-export', {
                    detail: {
                        exportType: this.exportType,
                        filters: enhancedFilters
                    }
                }));
                
                @if($showProgress)
                    this.listenForProgress();
                @endif
                
            } catch (error) {
                console.error('Error starting export with advanced options:', error);
                this.loading = false;
            }
        },
        
        @if($showProgress)
        listenForProgress() {
            const progressHandler = (event) => {
                if (event.detail.exportType === this.exportType) {
                    this.progress = event.detail.progress.progress || 0;
                    
                    if (['completed', 'failed', 'cancelled'].includes(event.detail.progress.status)) {
                        this.loading = false;
                        document.removeEventListener('export-progress-update', progressHandler);
                    }
                }
            };
            
            document.addEventListener('export-progress-update', progressHandler);
            
            // Auto-stop loading after 30 seconds as fallback
            setTimeout(() => {
                this.loading = false;
                document.removeEventListener('export-progress-update', progressHandler);
            }, 30000);
        }
        @endif
    };
    }
}
</script>
@endpush
@endonce