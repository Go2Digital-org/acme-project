<button
    @click="darkMode = !darkMode"
    class="flex items-center justify-between w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
>
    <span class="flex items-center">
        <span>{{ __('Theme') }}</span>
    </span>
    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="darkMode ? 'Dark' : 'Light'"></span>
</button>