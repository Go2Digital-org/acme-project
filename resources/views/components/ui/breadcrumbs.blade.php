@if($showBreadcrumbs && !empty($breadcrumbs))
<nav
    class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
    aria-label="{{ __('navigation.breadcrumb') }}"
    x-data
    x-init="
        if (typeof motion !== 'undefined') {
            // Animation is optional - breadcrumbs should be visible without it
            motion.inView($el, (element) => {
                motion.animate(
                    $el.querySelectorAll('ol > li'),
                    { opacity: [0.8, 1], x: [-10, 0] },
                    { duration: 0.5, ease: motion.easeOut, delay: motion.stagger(0.1) }
                )
            })
        }
    "
>
    <div class="max-w-7xl mx-auto px-6 py-3">
        <ol class="{{ $listClass }}" role="list">
            @foreach($breadcrumbs as $index => $breadcrumb)
                <li class="flex items-center" role="listitem" {!! $breadcrumb['aria_attributes'] !!}>
                    @if(!$breadcrumb['is_first'])
                        <svg class="flex-shrink-0 h-4 w-4 text-gray-400 mx-2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    @endif
                    
                    @if($breadcrumb['is_active'])
                        <span class="text-gray-900 dark:text-white font-medium" aria-current="page">
                            {{ $breadcrumb['title'] }}
                        </span>
                    @else
                        <a 
                            href="{{ $breadcrumb['url'] }}" 
                            class="{{ $linkClass }}"
                        >
                            {{ $breadcrumb['title'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    @if(!empty($structuredDataJson))
        <script type="application/ld+json">
            {!! $structuredDataJson !!}
        </script>
    @endif
</nav>
@endif