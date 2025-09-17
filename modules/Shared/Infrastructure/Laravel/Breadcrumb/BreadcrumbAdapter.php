<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Breadcrumb;

use Diglactic\Breadcrumbs\Breadcrumbs;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Modules\Shared\Application\Port\BreadcrumbManagerInterface;

/**
 * Infrastructure adapter that wraps the diglactic/laravel-breadcrumbs package.
 * This adapter implements the hexagonal architecture pattern by providing
 * a concrete implementation of the BreadcrumbManagerInterface port.
 */
final class BreadcrumbAdapter implements BreadcrumbManagerInterface
{
    /**
     * Generate breadcrumbs for a specific route name with parameters.
     *
     * @return Collection<int, array<string, mixed>>
     */
    /** @phpstan-return Collection<int, array<string, mixed>> */
    public function generate(string $name, mixed ...$params): Collection
    {
        try {
            $breadcrumbs = Breadcrumbs::generate($name, ...$params);

            /** @var Collection<int, array<string, mixed>> $collection */
            $collection = collect($breadcrumbs)->map(fn ($breadcrumb): array => [
                'title' => $breadcrumb->title,
                'url' => $breadcrumb->url,
                'active' => ! $breadcrumb->url, // Active items typically don't have URLs
                'is_active' => ! $breadcrumb->url,
                'is_home' => $this->isHomeBreadcrumb($breadcrumb->title),
                'name' => $breadcrumb->title, // For backward compatibility
            ])->values();

            return $collection;
        } catch (Exception $e) {
            // Log error and return empty collection for graceful degradation
            logger()->warning('Failed to generate breadcrumbs', [
                'route' => $name,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Check if a breadcrumb route exists.
     */
    public function exists(string $name): bool
    {
        return Breadcrumbs::exists($name);
    }

    /**
     * Render breadcrumbs using a specific view template.
     */
    public function render(?string $view = null): string
    {
        try {
            if ($view) {
                return Breadcrumbs::view($view)->render();
            }

            return Breadcrumbs::render()->render();
        } catch (Exception $e) {
            logger()->warning('Failed to render breadcrumbs', [
                'view' => $view,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Convert breadcrumbs to array format.
     */
    public function toArray(): array
    {
        try {
            // Try to get breadcrumbs from current route
            $currentRoute = Request::route();

            // If no route found, abort with 404
            if (! $currentRoute) {
                abort(404);
            }

            $routeName = $currentRoute->getName();

            // Check if route has a name
            if (! $routeName) {
                return [];
            }

            // Don't show breadcrumbs on home/welcome pages
            if (in_array($routeName, ['home', 'welcome'], true)) {
                return [];
            }

            $breadcrumbs = $this->generate($routeName, ...$currentRoute->parameters());

            return $breadcrumbs->toArray();
        } catch (Exception) {
            return [];
        }
    }

    /**
     * Get structured data for breadcrumbs (JSON-LD format for SEO).
     */
    public function getStructuredData(): array
    {
        $breadcrumbs = $this->toArray();

        if ($breadcrumbs === []) {
            return [];
        }

        $items = [];

        foreach ($breadcrumbs as $index => $breadcrumb) {
            // Only include breadcrumbs with URLs in structured data
            if (! empty($breadcrumb['url'])) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $breadcrumb['title'] ?? $breadcrumb['name'] ?? '',
                    'item' => $breadcrumb['url'],
                ];
            }
        }

        if ($items === []) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Check if breadcrumbs are currently available.
     */
    public function hasBreadcrumbs(): bool
    {
        return count($this->toArray()) > 0;
    }

    /**
     * Get the current active breadcrumb item.
     */
    public function getCurrentBreadcrumb(): ?array
    {
        $breadcrumbs = $this->toArray();

        if ($breadcrumbs === []) {
            return null;
        }

        // Find the last breadcrumb or the one marked as active
        $current = null;

        foreach ($breadcrumbs as $breadcrumb) {
            if ($breadcrumb['active'] || $breadcrumb['is_active']) {
                $current = $breadcrumb;
                break;
            }
        }

        // If no active breadcrumb found, return the last one
        return $current ?? end($breadcrumbs);
    }

    /**
     * Clear all current breadcrumbs.
     */
    public function clear(): self
    {
        // The diglactic package doesn't have a clear method as breadcrumbs are generated per request
        // This method exists for interface compatibility
        return $this;
    }

    /**
     * Generate breadcrumbs from current request context.
     * This method provides Laravel-specific request handling.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function generateFromCurrentRequest(): Collection
    {
        $currentRoute = Request::route();

        // If no route found, return empty collection
        if (! $currentRoute) {
            return collect();
        }

        $routeName = $currentRoute->getName();

        // Check if route has a name
        if (! $routeName) {
            return collect();
        }

        // Don't show breadcrumbs on home/welcome pages
        if (in_array($routeName, ['home', 'welcome'], true)) {
            return collect();
        }

        return $this->generate($routeName, ...$currentRoute->parameters());
    }

    /**
     * Get breadcrumbs with additional Laravel-specific metadata.
     */
    /** @return array<array-key, mixed> */
    public function getLaravelBreadcrumbs(): array
    {
        $breadcrumbs = $this->toArray();
        $currentRoute = Request::route();

        return [
            'breadcrumbs' => $breadcrumbs,
            'structured_data' => $this->getStructuredData(),
            'show_breadcrumbs' => count($breadcrumbs) > 1,
            'current_breadcrumb' => $this->getCurrentBreadcrumb(),
            'route_name' => $currentRoute->getName(),
            'route_parameters' => $currentRoute->parameters(),
        ];
    }

    /**
     * Get breadcrumbs formatted for JSON API responses.
     */
    /** @return array<array-key, mixed> */
    public function toJsonApi(): array
    {
        $breadcrumbs = $this->toArray();

        return [
            'data' => array_map(static fn (array $breadcrumb, int $index): array => [
                'type' => 'breadcrumb',
                'id' => (string) $index,
                'attributes' => [
                    'title' => $breadcrumb['title'] ?? $breadcrumb['name'] ?? '',
                    'url' => $breadcrumb['url'] ?? null,
                    'active' => $breadcrumb['active'] ?? $breadcrumb['is_active'] ?? false,
                    'position' => $index + 1,
                ],
            ], $breadcrumbs, array_keys($breadcrumbs)),
            'meta' => [
                'structured_data' => $this->getStructuredData(),
                'total_count' => count($breadcrumbs),
                'has_breadcrumbs' => $this->hasBreadcrumbs(),
            ],
        ];
    }

    /**
     * Check if a breadcrumb represents the home page.
     */
    private function isHomeBreadcrumb(string $title): bool
    {
        $homeTitles = [
            __('navigation.home'),
            'Home',
            'Accueil', // French
            'Thuis',   // Dutch
            'Start',
            'Dashboard',
        ];

        return in_array($title, $homeTitles, true);
    }
}
