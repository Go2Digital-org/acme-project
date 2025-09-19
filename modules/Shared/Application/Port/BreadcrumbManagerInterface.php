<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Port;

use Illuminate\Support\Collection;

/**
 * Interface for breadcrumb management following hexagonal architecture principles.
 * This port defines the contract for breadcrumb operations without depending on any specific implementation.
 */
interface BreadcrumbManagerInterface
{
    /**
     * Generate breadcrumbs for a specific route name with parameters.
     *
     * @param  string  $name  Route name for breadcrumb generation
     * @param  mixed  ...$params  Parameters to pass to the breadcrumb generation
     * @return Collection<int, array<string, mixed>> Collection of breadcrumb items
     */
    public function generate(string $name, mixed ...$params): Collection;

    /**
     * Check if a breadcrumb route exists.
     *
     * @param  string  $name  Route name to check
     * @return bool True if the breadcrumb route exists
     */
    public function exists(string $name): bool;

    /**
     * Render breadcrumbs using a specific view template.
     *
     * @param  string|null  $view  Optional view template name
     * @return string Rendered breadcrumb HTML
     */
    public function render(?string $view = null): string;

    /**
     * Convert breadcrumbs to array format.
     *
     * @return array<int, array<string, mixed>> Array of breadcrumb items
     */
    public function toArray(): array;

    /**
     * Get structured data for breadcrumbs (JSON-LD format for SEO).
     *
     * @return array<string, mixed> Structured data array
     */
    public function getStructuredData(): array;

    /**
     * Check if breadcrumbs are currently available.
     *
     * @return bool True if breadcrumbs exist
     */
    public function hasBreadcrumbs(): bool;

    /**
     * Get the current active breadcrumb item.
     *
     * @return array<string, mixed>|null Current breadcrumb item or null
     */
    public function getCurrentBreadcrumb(): ?array;

    /**
     * Clear all current breadcrumbs.
     */
    public function clear(): self;
}
