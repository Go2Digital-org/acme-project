<x-layout :title="$page->getTranslation('title')">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                {{ $page->getTranslation('title') }}
            </h1>
        </div>
        
        <!-- Page Content -->
        <div class="prose prose-lg max-w-none dark:prose-invert prose-gray dark:prose-slate">
            {!! $page->getTranslation('content') !!}
        </div>
        
        <!-- Page Meta -->
        @if($page->updated_at)
            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('common.last_updated') }}: 
                    <time datetime="{{ $page->updated_at->toISOString() }}">
                        {{ $page->updated_at->format('F j, Y') }}
                    </time>
                </p>
            </div>
        @endif
    </div>
</x-layout>