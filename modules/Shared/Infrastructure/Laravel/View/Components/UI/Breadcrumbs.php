<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\View\Components\UI;

use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Shared\Application\Service\BreadcrumbManager;

/**
 * Breadcrumbs view component following hexagonal architecture principles.
 * This component acts as a presentation layer adapter that renders breadcrumb data
 * obtained from the domain service through the application layer.
 */
final class Breadcrumbs extends Component
{
    /** @var array<string, mixed> */
    public array $breadcrumbs;

    /** @var array<string, mixed> */
    public array $structuredData;

    public bool $showBreadcrumbs;

    /** @var array<string, mixed>|null */
    public ?array $currentBreadcrumb = null;

    /**
     * @param  array<string, mixed>  $routeParams
     */
    public function __construct(
        private readonly BreadcrumbManager $breadcrumbManager,
        ?string $routeName = null,
        array $routeParams = [],
        public string $containerClass = 'breadcrumbs-container',
        public string $listClass = 'breadcrumbs-list flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400',
        public string $itemClass = 'breadcrumb-item flex items-center',
        public string $activeItemClass = 'breadcrumb-item breadcrumb-item--active text-gray-900 dark:text-gray-100 font-medium',
        public string $linkClass = 'breadcrumb-link text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors',
        public string $separatorClass = 'breadcrumb-separator text-gray-300 dark:text-gray-600 mx-2',
        public string $separator = '/',
    ) {
        // Generate breadcrumbs
        $this->generateBreadcrumbs($routeName, $routeParams);
    }

    /**
     * Determine if the component should be rendered.
     */
    public function shouldRender(): bool
    {
        return $this->showBreadcrumbs && $this->breadcrumbs !== [];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|string
    {
        if (! $this->shouldRender()) {
            return '';
        }

        return view('components.ui.breadcrumbs', [
            'breadcrumbs' => $this->breadcrumbs,
            'structuredData' => $this->structuredData,
            'showBreadcrumbs' => $this->showBreadcrumbs,
            'currentBreadcrumb' => $this->currentBreadcrumb,
            'containerClass' => $this->containerClass,
            'listClass' => $this->listClass,
            'itemClass' => $this->itemClass,
            'activeItemClass' => $this->activeItemClass,
            'linkClass' => $this->linkClass,
            'separatorClass' => $this->separatorClass,
            'separator' => $this->separator,
        ]);
    }

    /**
     * Get breadcrumbs formatted for JSON-LD structured data.
     */
    public function getStructuredDataJson(): string
    {
        if ($this->structuredData === []) {
            return '';
        }

        $encoded = json_encode($this->structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded !== false ? $encoded : '';
    }

    /**
     * Check if a breadcrumb item is active.
     */
    /**
     * Check if a breadcrumb item is active.
     */
    /**
     * @param  array<string, mixed>  $breadcrumb
     */
    public function isActive(array $breadcrumb): bool
    {
        return $breadcrumb['active'] ?? $breadcrumb['is_active'] ?? false;
    }

    /**
     * Check if a breadcrumb item is the home page.
     */
    /**
     * Check if a breadcrumb item is the home page.
     */
    /**
     * @param  array<string, mixed>  $breadcrumb
     */
    public function isHome(array $breadcrumb): bool
    {
        return $breadcrumb['home'] ?? $breadcrumb['is_home'] ?? false;
    }

    /**
     * Get the URL for a breadcrumb item.
     */
    /**
     * Get the URL for a breadcrumb item.
     */
    /**
     * @param  array<string, mixed>  $breadcrumb
     */
    public function getBreadcrumbUrl(array $breadcrumb): ?string
    {
        return $breadcrumb['url'] ?? null;
    }

    /**
     * Get the title for a breadcrumb item.
     */
    /**
     * Get the title for a breadcrumb item.
     */
    /**
     * @param  array<string, mixed>  $breadcrumb
     */
    public function getBreadcrumbTitle(array $breadcrumb): string
    {
        return $breadcrumb['title'] ?? $breadcrumb['name'] ?? '';
    }

    /**
     * Get CSS classes for a breadcrumb item.
     */
    /**
     * Get CSS classes for a breadcrumb item.
     */
    /**
     * @param  array<string, mixed>  $breadcrumb
     */
    public function getBreadcrumbItemClasses(array $breadcrumb): string
    {
        if ($this->isActive($breadcrumb)) {
            return $this->activeItemClass;
        }

        return $this->itemClass;
    }

    /**
     * Get accessibility attributes for a breadcrumb item.
     */
    /**
     * Get accessibility attributes for a breadcrumb item.
     */
    /**
     * @param  array<string, mixed>  $breadcrumb
     * @return array<string, mixed>
     */
    public function getBreadcrumbAriaAttributes(array $breadcrumb, int $position, int $total): array
    {
        $attributes = [];

        if ($this->isActive($breadcrumb)) {
            $attributes['aria-current'] = 'page';
        }

        if ($position === 0) {
            $attributes['aria-label'] = __('navigation.breadcrumb_home');
        }

        return $attributes;
    }

    /**
     * Get accessibility attributes as a string for a breadcrumb item.
     */
    /**
     * Get accessibility attributes as a string for a breadcrumb item.
     */
    /**
     * @param  array<string, mixed>  $breadcrumb
     */
    public function getBreadcrumbAriaAttributesString(array $breadcrumb, int $position, int $total): string
    {
        $attributes = $this->getBreadcrumbAriaAttributes($breadcrumb, $position, $total);
        $attributeStrings = [];

        foreach ($attributes as $key => $value) {
            $attributeStrings[] = $key . '="' . e($value) . '"';
        }

        return implode(' ', $attributeStrings);
    }

    /**
     * Create a breadcrumb component instance with custom styling.
     */
    /**
     * Create a breadcrumb component instance with custom styling.
     */
    /**
     * @param  array<string, mixed>  $classes
     */
    public static function withCustomStyling(
        BreadcrumbManager $breadcrumbManager,
        array $classes = [],
    ): self {
        return new self(
            breadcrumbManager: $breadcrumbManager,
            containerClass: $classes['container'] ?? 'breadcrumbs-container',
            listClass: $classes['list'] ?? 'breadcrumbs-list flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400',
            itemClass: $classes['item'] ?? 'breadcrumb-item flex items-center',
            activeItemClass: $classes['active'] ?? 'breadcrumb-item breadcrumb-item--active text-gray-900 dark:text-gray-100 font-medium',
            linkClass: $classes['link'] ?? 'breadcrumb-link text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors',
            separatorClass: $classes['separator'] ?? 'breadcrumb-separator text-gray-300 dark:text-gray-600 mx-2',
            separator: $classes['separatorSymbol'] ?? '/',
        );
    }

    /**
     * Create a breadcrumb component instance for specific route.
     */
    /**
     * Create a breadcrumb component instance for specific route.
     */
    /**
     * @param  array<string, mixed>  $routeParams
     */
    public static function forRoute(
        BreadcrumbManager $breadcrumbManager,
        string $routeName,
        array $routeParams = [],
    ): self {
        return new self(
            breadcrumbManager: $breadcrumbManager,
            routeName: $routeName,
            routeParams: $routeParams,
        );
    }

    /**
     * Get the data that should be available to the component template.
     */
    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        // Process breadcrumbs to include computed properties for the view
        $processedBreadcrumbs = array_map(fn ($breadcrumb, $index): array => [
            'title' => $breadcrumb['title'] ?? $breadcrumb['name'] ?? '',
            'url' => $breadcrumb['url'] ?? null,
            'is_active' => $breadcrumb['active'] ?? $breadcrumb['is_active'] ?? false,
            'is_first' => (int) $index === 0,
            'aria_attributes' => $this->computeAriaAttributes($breadcrumb, (int) $index),
        ], $this->breadcrumbs, array_keys($this->breadcrumbs));

        return [
            'breadcrumbs' => $processedBreadcrumbs,
            'structuredData' => $this->structuredData,
            'showBreadcrumbs' => $this->showBreadcrumbs,
            'currentBreadcrumb' => $this->currentBreadcrumb,
            'containerClass' => $this->containerClass,
            'listClass' => $this->listClass,
            'itemClass' => $this->itemClass,
            'activeItemClass' => $this->activeItemClass,
            'linkClass' => $this->linkClass,
            'separatorClass' => $this->separatorClass,
            'separator' => $this->separator,
            'structuredDataJson' => $this->structuredData === [] ? '' : (json_encode($this->structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: ''),
        ];
    }

    /**
     * Generate breadcrumbs data for rendering.
     */
    /**
     * Generate breadcrumbs data for rendering.
     */
    /**
     * @param  array<string, mixed>  $routeParams
     */
    private function generateBreadcrumbs(?string $routeName, array $routeParams): void
    {
        try {
            if ($routeName && $this->breadcrumbManager->exists($routeName)) {
                // Generate from specific route
                $breadcrumbCollection = $this->breadcrumbManager->generate($routeName, ...$routeParams);
                $breadcrumbData = $this->breadcrumbManager->toViewData();
            } else {
                // Generate from current request
                $breadcrumbCollection = $this->breadcrumbManager->generateFromCurrentRequest();
                $breadcrumbData = $this->breadcrumbManager->toViewData();
            }

            $this->breadcrumbs = $breadcrumbData['breadcrumbs'] ?? [];
            $this->structuredData = $breadcrumbData['structured_data'] ?? [];
            $this->showBreadcrumbs = $breadcrumbData['show_breadcrumbs'] ?? false;
            $this->currentBreadcrumb = $breadcrumbData['current_breadcrumb'] ?? null;
        } catch (Exception $e) {
            // Graceful degradation on errors
            logger()->warning('Failed to generate breadcrumbs in component', [
                'route' => $routeName,
                'params' => $routeParams,
                'error' => $e->getMessage(),
            ]);

            $this->breadcrumbs = [];
            $this->structuredData = [];
            $this->showBreadcrumbs = false;
            $this->currentBreadcrumb = null;
        }
    }

    /**
     * Compute ARIA attributes for a breadcrumb item.
     */
    /**
     * Compute ARIA attributes for a breadcrumb item.
     */
    /**
     * @param  array<string, mixed>  $breadcrumb
     */
    private function computeAriaAttributes(array $breadcrumb, int $index): string
    {
        $attributes = [];

        $isActive = $breadcrumb['active'] ?? $breadcrumb['is_active'] ?? false;

        if ($isActive) {
            $attributes[] = 'aria-current="page"';
        }

        if ($index === 0) {
            $attributes[] = 'aria-label="' . __('navigation.breadcrumb_home') . '"';
        }

        return implode(' ', $attributes);
    }
}
