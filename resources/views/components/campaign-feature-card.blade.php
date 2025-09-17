{{-- Featured Campaign Card Component --}}
@props([
    'title',
    'description', 
    'image',
    'category',
    'categoryIcon',
    'categoryColor' => 'primary',
    'raised',
    'goal',
    'percentage',
    'progressColor' => 'bg-secondary',
    'badgeText' => null,
    'badgeColor' => 'bg-primary',
    'href' => null
])

<a href="{{ $href }}" class="block group h-full">
<div class="relative isolate flex flex-col justify-end overflow-hidden rounded-2xl bg-gray-900 px-8 pb-8 pt-80 sm:pt-48 lg:pt-80 transition-transform duration-300 group-hover:scale-[1.02] h-full">
    <img src="{{ $image }}" 
         alt="{{ $title }}" 
         class="absolute inset-0 -z-10 h-full w-full object-cover"
         onerror="this.src='https://images.unsplash.com/photo-1497486751825-1233686d5d80?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'; this.onerror=null;">
    <div class="absolute inset-0 -z-10 bg-gray-900/80"></div>
    <div class="absolute inset-0 -z-10 rounded-2xl ring-1 ring-inset ring-gray-900/10"></div>

    <div class="flex flex-wrap items-center gap-y-1 overflow-hidden text-sm leading-6 text-gray-300">
        <div class="flex items-center gap-x-2">
            <i class="{{ $categoryIcon }} text-{{ $categoryColor }}"></i>
            {{ $category }}
        </div>
        @if($badgeText)
        <div class="ml-auto">
            <span class="inline-flex items-center rounded-md {{ $badgeColor }} px-2 py-1 text-xs font-medium text-white">
                {{ $badgeText }}
            </span>
        </div>
        @endif
    </div>
    <h3 class="mt-3 text-lg font-semibold leading-6 text-white">
        {{ $title }}
    </h3>
    <p class="mt-2 text-sm leading-6 text-gray-300">
        {{ $description }}
    </p>
    <div class="mt-4">
        @php
            // Extract numeric values from currency strings
            $raisedAmount = (float) preg_replace('/[^\d.,]/', '', str_replace(',', '', $raised));
            $goalAmount = (float) preg_replace('/[^\d.,]/', '', str_replace(',', '', $goal));
            
            $urgencyLevel = match(true) {
                $percentage >= 95 => 'very-high',
                $percentage >= 75 => 'high',
                $percentage >= 50 => 'medium',
                default => 'normal'
            };
            
            $showCelebration = $percentage >= 95;
        @endphp
        
        <x-fancy-progress-bar
            :current="$raisedAmount"
            :goal="$goalAmount"
            :percentage="$percentage"
            :showStats="false"
            :showMilestones="true"
            :animated="true"
            size="large"
            :urgencyLevel="$urgencyLevel"
            :showCelebration="$showCelebration"
            :showInlinePercentage="true"
            class="featured-campaign-progress"
        />
        
        <div class="flex justify-between text-sm text-gray-300 mt-2">
            <span class="font-medium">{{ $raised }} {{ __('campaigns.raised') ?? 'raised' }}</span>
            <span>{{ $goal }} {{ __('campaigns.goal') ?? 'goal' }}</span>
        </div>
    </div>
</div>
</a>