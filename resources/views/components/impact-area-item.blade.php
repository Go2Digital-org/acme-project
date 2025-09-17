{{-- Impact Area Item Component --}}
@props(['title', 'description', 'iconClass', 'iconBgColor' => 'bg-primary'])

<div class="relative flex items-start">
    <dt class="flex items-center">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $iconBgColor }}">
            <i class="{{ $iconClass }} text-white"></i>
        </div>
        <span class="ml-4 text-base font-semibold leading-7 text-gray-900 dark:text-white">
            {{ $title }}
        </span>
    </dt>
    <dd class="ml-14 text-base leading-7 text-gray-600 dark:text-gray-400">
        {{ $description }}
    </dd>
</div>