@props([
    'current' => 0,
    'goal' => 100,
    'percentage' => null,
    'showStats' => true,
    'showMilestones' => true,
    'animated' => true,
    'animateOnLoad' => false, // New prop to control initial animation
    'animateOnVisible' => true, // New prop to animate when visible in viewport
    'size' => 'default', // 'small', 'default', 'large', 'xlarge'
    'label' => null,
    'donorCount' => null,
    'daysRemaining' => null,
    'urgencyLevel' => 'normal',
    'showCelebration' => false,
    'showInlinePercentage' => true,
    'id' => 'progress-' . uniqid(),
])

@php
    $calculatedPercentage = $percentage ?? ($goal > 0 ? min(100, ($current / $goal) * 100) : 0);
    $formattedPercentage = round($calculatedPercentage);
    
    // Determine color scheme based on progress
    $colorScheme = match(true) {
        $calculatedPercentage >= 100 => 'success',
        $calculatedPercentage >= 75 => 'vibrant',
        $calculatedPercentage >= 50 => 'progress',
        $calculatedPercentage >= 25 => 'active',
        default => 'starting'
    };
    
    // Determine size classes with better height options
    $heightClass = match($size) {
        'small' => 'h-3',
        'large' => 'h-8',
        'xlarge' => 'h-10',
        default => 'h-6'
    };
    
    $textSizeClass = match($size) {
        'small' => 'text-xs',
        'large' => 'text-base',
        'xlarge' => 'text-lg',
        default => 'text-sm'
    };
    
    $percentageTextClass = match($size) {
        'small' => 'text-[10px]',
        'large' => 'text-sm',
        'xlarge' => 'text-base',
        default => 'text-xs'
    };
    
    // Animation intensity based on urgency
    $animationClass = match($urgencyLevel) {
        'critical' => 'animate-pulse-fast',
        'very-high' => 'animate-pulse',
        'high' => 'animate-glow',
        default => ''
    };
    
    // Calculate milestones
    $milestones = [25, 50, 75, 100];
    $currentMilestone = null;
    foreach ($milestones as $milestone) {
        if ($calculatedPercentage >= $milestone) {
            $currentMilestone = $milestone;
        }
    }
@endphp

<div 
    {{ $attributes->merge(['class' => 'fancy-progress-container']) }}
    x-data="fancyProgressBar(@js([
        'percentage' => $calculatedPercentage,
        'animated' => $animated,
        'animateOnLoad' => $animateOnLoad,
        'animateOnVisible' => $animateOnVisible,
        'showCelebration' => $showCelebration,
        'currentMilestone' => $currentMilestone,
    ]))"
    x-init="init()"
>
    {{-- Progress Stats Container with fixed height --}}
    <div class="h-[36px] sm:h-[44px] mb-2 sm:mb-3 flex items-center">
        @if($showStats)
            {{-- Mobile: Single line layout --}}
            <div class="w-full flex items-center justify-between sm:hidden">
                <div class="flex items-baseline gap-1">
                    <span class="text-xs font-bold text-gray-900 dark:text-white truncate max-w-[100px]">
                        {{ format_currency($current) }}
                    </span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400">
                        /{{ format_currency($goal) }}
                    </span>
                    <span 
                        class="text-[10px] font-bold px-1 py-0.5 rounded {{ $animationClass }}"
                        :class="{
                            'text-green-600': percentage >= 75,
                            'text-blue-600': percentage >= 50 && percentage < 75,
                            'text-gray-600': percentage < 50
                        }"
                    >
                        <span x-text="Math.round(animatedPercentage) + '%'"></span>
                    </span>
                </div>
                
                <div class="flex items-center gap-2 text-[10px] text-gray-600 dark:text-gray-400">
                    @if($donorCount !== null)
                        <span>{{ $donorCount }} donors</span>
                    @endif
                    @if($daysRemaining !== null)
                        <span class="@if($daysRemaining <= 7) text-orange-500 font-medium @endif">
                            {{ $daysRemaining }}d left
                        </span>
                    @endif
                </div>
            </div>
            
            {{-- Desktop: Full layout --}}
            <div class="hidden sm:flex sm:w-full sm:items-center sm:justify-between">
                <div class="flex items-baseline gap-3">
                    <span class="{{ $textSizeClass }} font-bold text-gray-900 dark:text-white">
                        {{ format_currency($current) }}
                    </span>
                    <span class="{{ $textSizeClass }} text-gray-500 dark:text-gray-400">
                        of {{ format_currency($goal) }}
                    </span>
                    
                    {{-- Percentage Badge --}}
                    <span 
                        class="fancy-percentage-badge {{ $textSizeClass }} font-bold px-2 py-0.5 rounded-full {{ $animationClass }}"
                        :class="{
                            'bg-gradient-to-r from-green-400 to-emerald-500 text-white': percentage >= 100,
                            'bg-gradient-to-r from-blue-400 to-green-400 text-white': percentage >= 75 && percentage < 100,
                            'bg-gradient-to-r from-blue-400 to-blue-500 text-white': percentage >= 50 && percentage < 75,
                            'bg-gradient-to-r from-indigo-400 to-blue-400 text-white': percentage >= 25 && percentage < 50,
                            'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300': percentage < 25
                        }"
                    >
                        <span x-text="Math.round(animatedPercentage) + '%'"></span>
                    </span>
                </div>
                
                {{-- Additional Stats --}}
                <div class="flex items-center gap-4 text-xs lg:text-sm text-gray-600 dark:text-gray-400">
                    @if($donorCount !== null)
                        <span class="flex items-center gap-1">
                            <i class="fas fa-users text-xs"></i>
                            <span>{{ $donorCount }} donors</span>
                        </span>
                    @endif
                    
                    @if($daysRemaining !== null)
                        <span class="flex items-center gap-1 @if($daysRemaining <= 7) text-orange-500 font-medium @endif">
                            <i class="fas fa-clock text-xs"></i>
                            <span>{{ $daysRemaining }} days left</span>
                        </span>
                    @endif
                </div>
            </div>
        @else
            {{-- Empty placeholder to maintain height --}}
            <div class="w-full">&nbsp;</div>
        @endif
    </div>
    
    {{-- Progress Bar Container --}}
    <div class="relative fancy-progress-wrapper">
        {{-- Background Track --}}
        <div class="{{ $heightClass }} bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden relative flex items-center">
            
            {{-- Milestone Markers --}}
            @if($showMilestones && $size !== 'small')
                @foreach($milestones as $milestone)
                    <div 
                        class="absolute top-0 bottom-0 w-px bg-white/30 dark:bg-black/30 milestone-marker"
                        style="left: {{ $milestone }}%"
                        x-show="percentage < {{ $milestone }}"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                    ></div>
                @endforeach
            @endif
            
            {{-- Progress Fill --}}
            <div 
                class="{{ $heightClass }} rounded-full transition-all duration-1000 ease-out relative overflow-hidden fancy-progress-fill flex items-center justify-end"
                :style="{ width: animatedPercentage + '%' }"
                :class="{
                    'progress-gradient-success': percentage >= 100,
                    'progress-gradient-vibrant': percentage >= 75 && percentage < 100,
                    'progress-gradient-progress': percentage >= 50 && percentage < 75,
                    'progress-gradient-active': percentage >= 25 && percentage < 50,
                    'progress-gradient-starting': percentage < 25
                }"
            >
                {{-- Animated Stripes Pattern --}}
                <div class="absolute inset-0 opacity-20">
                    <div class="h-full w-full progress-stripes"></div>
                </div>
                
                {{-- Shimmer Effect --}}
                <div 
                    class="absolute inset-0 progress-shimmer"
                    x-show="animated && percentage > 0"
                ></div>
                
                {{-- Wave Animation --}}
                <div 
                    class="absolute inset-0 progress-wave"
                    x-show="percentage >= 75"
                ></div>
                
                {{-- Glow Effect --}}
                <div 
                    class="absolute right-0 top-0 bottom-0 w-8 progress-glow"
                    x-show="percentage > 0 && percentage < 100"
                ></div>
                
                {{-- Inline Percentage Text --}}
                @if($showInlinePercentage)
                    <div 
                        class="absolute inset-y-0 right-2 flex items-center {{ $percentageTextClass }} font-bold text-white drop-shadow-md"
                        x-show="animatedPercentage > 10"
                    >
                        <span x-text="Math.round(animatedPercentage) + '%'"></span>
                    </div>
                @endif
            </div>
            
            {{-- Percentage text for empty/low progress --}}
            @if($showInlinePercentage)
                <div 
                    class="absolute inset-y-0 left-2 flex items-center {{ $percentageTextClass }} font-semibold text-gray-600 dark:text-gray-400"
                    x-show="animatedPercentage <= 10"
                >
                    <span x-text="Math.round(animatedPercentage) + '%'"></span>
                </div>
            @endif
            
            {{-- Pulse Ring on Goal Reached --}}
            <div 
                class="absolute inset-0 rounded-full"
                x-show="percentage >= 100"
                x-transition:enter="transition ease-out duration-1000"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
            >
                <div class="absolute inset-0 rounded-full bg-green-400 animate-ping-slow opacity-20"></div>
            </div>
        </div>
    </div>
    
    {{-- Milestone Celebration --}}
    <div 
        class="fixed inset-0 pointer-events-none z-50"
        x-show="showMilestoneCelebration"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-500"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
            <div class="text-6xl animate-bounce-slow">
                <span x-text="getMilestoneEmoji()"></span>
            </div>
        </div>
        
    </div>
    
    {{-- Label --}}
    @if($label)
        <div class="mt-2 {{ $textSizeClass }} text-gray-600 dark:text-gray-400">
            {{ $label }}
        </div>
    @endif
</div>

<script>
function fancyProgressBar(config) {
    return {
        percentage: config.percentage || 0,
        animatedPercentage: 0,
        animated: config.animated !== false,
        showCelebration: config.showCelebration || false,
        currentMilestone: config.currentMilestone,
        showMilestoneCelebration: false,
        lastMilestone: null,
        
        init() {
            if (this.animated) {
                // Animate the percentage counter
                this.animatePercentage();
                
                // Check for milestone celebration
                if (this.currentMilestone && this.showCelebration) {
                    this.celebrateMilestone();
                }
            } else {
                this.animatedPercentage = this.percentage;
            }
        },
        
        animatePercentage() {
            const duration = 2000; // 2 seconds
            const steps = 60;
            const increment = this.percentage / steps;
            let current = 0;
            
            const interval = setInterval(() => {
                current += increment;
                this.animatedPercentage = Math.min(current, this.percentage);
                
                if (current >= this.percentage) {
                    clearInterval(interval);
                    this.animatedPercentage = this.percentage;
                }
            }, duration / steps);
        },
        
        celebrateMilestone() {
            if (this.currentMilestone && this.currentMilestone !== this.lastMilestone) {
                setTimeout(() => {
                    this.showMilestoneCelebration = true;
                    this.lastMilestone = this.currentMilestone;
                    
                    setTimeout(() => {
                        this.showMilestoneCelebration = false;
                    }, 2000);
                }, 1500);
            }
        },
        
        getMilestoneEmoji() {
            if (this.percentage >= 100) return '';
            if (this.percentage >= 75) return '';
            if (this.percentage >= 50) return '⭐';
            if (this.percentage >= 25) return '';
            return '';
        }
    }
}
</script>