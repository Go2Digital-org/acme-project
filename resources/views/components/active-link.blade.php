@props([
    'href' => '#',
    'route' => null,
    'routePattern' => null,
    'activeClass' => 'text-primary',
    'inactiveClass' => 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white',
    'exact' => false,
    'isActive' => null
])

<a 
    href="{{ $href }}" 
    {{ $attributes->merge(['class' => ($isActive ?? ($route ? request()->routeIs($route) : ($routePattern ? request()->routeIs($routePattern) : false)) ? $activeClass : $inactiveClass) . ' transition-colors duration-200']) }}
    @if($isActive ?? ($route ? request()->routeIs($route) : ($routePattern ? request()->routeIs($routePattern) : false))) aria-current="page" @endif
>
    {{ $slot }}
</a>