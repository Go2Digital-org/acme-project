@props(['routeName' => null, 'routeParams' => []])

<x-ui.breadcrumbs 
    :route-name="$routeName" 
    :route-params="$routeParams" 
    container-class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700" 
    list-class="flex items-center space-x-2 text-sm"
    link-class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors duration-200"
/>