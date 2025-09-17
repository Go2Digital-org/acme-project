<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Service;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Shared\Application\Port\BreadcrumbManagerInterface;

/**
 * Application service for breadcrumb management.
 * This service acts as a wrapper around the infrastructure adapter to provide
 * domain-specific breadcrumb operations following hexagonal architecture principles.
 */
final readonly class BreadcrumbManager implements BreadcrumbManagerInterface
{
    public function __construct(
        private BreadcrumbManagerInterface $adapter,
    ) {}

    /**
     * Generate breadcrumbs for a specific route name with parameters.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function generate(string $name, mixed ...$params): Collection
    {
        return $this->adapter->generate($name, ...$params);
    }

    /**
     * Check if a breadcrumb route exists.
     */
    public function exists(string $name): bool
    {
        return $this->adapter->exists($name);
    }

    /**
     * Render breadcrumbs using a specific view template.
     */
    public function render(?string $view = null): string
    {
        return $this->adapter->render($view);
    }

    /**
     * Convert breadcrumbs to array format.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->adapter->toArray();
    }

    /**
     * Get structured data for breadcrumbs (JSON-LD format for SEO).
     *
     * @return array<string, mixed>
     */
    public function getStructuredData(): array
    {
        return $this->adapter->getStructuredData();
    }

    /**
     * Check if breadcrumbs are currently available.
     */
    public function hasBreadcrumbs(): bool
    {
        return $this->adapter->hasBreadcrumbs();
    }

    /**
     * Get the current active breadcrumb item.
     *
     * @return array<string, mixed>|null
     */
    public function getCurrentBreadcrumb(): ?array
    {
        return $this->adapter->getCurrentBreadcrumb();
    }

    /**
     * Clear all current breadcrumbs.
     */
    public function clear(): self
    {
        $this->adapter->clear();

        return $this;
    }

    /**
     * Generate breadcrumbs from current request context.
     * This method provides a convenient way to generate breadcrumbs
     * based on the current HTTP request without needing to specify route names.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function generateFromCurrentRequest(): Collection
    {
        // This will delegate to the infrastructure adapter which should handle request-based generation
        if (method_exists($this->adapter, 'generateFromCurrentRequest')) {
            return $this->adapter->generateFromCurrentRequest();
        }

        // Fallback: try to generate from current route if available
        $currentRoute = app('request')->route();

        if ($currentRoute && $currentRoute->getName()) {
            return $this->generate($currentRoute->getName(), ...$currentRoute->parameters());
        }

        return collect();
    }

    /**
     * Generate breadcrumbs with additional business logic validation.
     * This method adds domain-specific validation before generating breadcrumbs.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function generateWithValidation(string $name, mixed ...$params): Collection
    {
        // Validate route name format
        if (in_array(trim($name), ['', '0'], true)) {
            throw new InvalidArgumentException('Breadcrumb route name cannot be empty');
        }

        // Check if route exists before generating
        if (! $this->exists($name)) {
            // Log warning or handle missing route gracefully
            logger()->warning("Breadcrumb route '{$name}' does not exist", [
                'route' => $name,
                'params' => $params,
            ]);

            return collect();
        }

        return $this->generate($name, ...$params);
    }

    /**
     * Get breadcrumbs formatted for API responses.
     */
    /** @return array<array-key, mixed> */
    public function toApiFormat(): array
    {
        $breadcrumbs = $this->toArray();

        return [
            'breadcrumbs' => array_map(static fn (array $breadcrumb): array => [
                'title' => $breadcrumb['title'] ?? $breadcrumb['name'] ?? '',
                'url' => $breadcrumb['url'] ?? null,
                'active' => $breadcrumb['active'] ?? $breadcrumb['is_active'] ?? false,
                'home' => $breadcrumb['home'] ?? $breadcrumb['is_home'] ?? false,
            ], $breadcrumbs),
            'structured_data' => $this->getStructuredData(),
            'has_breadcrumbs' => $this->hasBreadcrumbs(),
        ];
    }

    /**
     * Get breadcrumbs formatted for view rendering with enhanced metadata.
     */
    /** @return array<array-key, mixed> */
    public function toViewData(): array
    {
        return [
            'breadcrumbs' => $this->toArray(),
            'structured_data' => $this->getStructuredData(),
            'show_breadcrumbs' => $this->hasBreadcrumbs() && count($this->toArray()) > 0,
            'current_breadcrumb' => $this->getCurrentBreadcrumb(),
            'breadcrumb_count' => count($this->toArray()),
        ];
    }
}
