<x-layout title="{{ __('donations.create_title') }}">
    <div class="px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold mb-6">{{ __('donations.create_title') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('donations.create_description') }}</p>
            
            {{-- Placeholder form --}}
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-center text-gray-500">{{ __('donations.form_coming_soon') }}</p>
            </div>
        </div>
    </div>
</x-layout>