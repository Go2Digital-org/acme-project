{{-- Impact Statistics Card Component --}}
@props(['value', 'label'])

<div class="flex flex-col gap-y-3 border-l border-gray-900/10 dark:border-white/10 pl-6">
    <dt class="text-sm leading-6 text-gray-600 dark:text-gray-400">{{ $label }}</dt>
    <dd class="order-first text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
        {{ $value }}
    </dd>
</div>