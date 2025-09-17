{{-- SEO Preview Component --}}
<div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
    <h3 class="text-lg font-semibold text-blue-600 hover:underline cursor-pointer line-clamp-1">
        {{ $title }}
    </h3>
    <p class="text-sm text-green-600 mt-1 line-clamp-1">
        {{ $url }}
    </p>
    <p class="text-sm text-gray-600 mt-2 leading-relaxed line-clamp-2">
        {{ $description }}
    </p>
    @if (strlen($description) > 160)
        <p class="text-xs text-red-500 mt-1">
            {{ __('components.seo_preview.description_too_long', ['count' => strlen($description), 'max' => 160]) }}
        </p>
    @endif
</div>