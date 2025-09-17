<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Laravel\Adapter;

use Filament\Navigation\NavigationItem;
use Modules\Admin\Domain\Port\AdminNavigationPort;

/**
 * Infrastructure adapter for Filament navigation.
 * Adapts domain navigation items to Filament-specific NavigationItem objects.
 */
final readonly class FilamentNavigationAdapter
{
    public function __construct(
        private AdminNavigationPort $navigationService
    ) {}

    /**
     * Convert domain navigation items to Filament NavigationItem objects.
     *
     * @return array<int, NavigationItem>
     */
    public function getNavigationItems(): array
    {
        // Create navigation items that will check permissions at render time
        return [
            NavigationItem::make('Pulse')
                ->url('/pulse', shouldOpenInNewTab: true)
                ->icon('heroicon-o-chart-bar')
                ->group('System Monitoring')
                ->sort(100)
                ->visible(fn (): bool => $this->navigationService->canAccessSystemMonitoring()),
            NavigationItem::make('Horizon')
                ->url('/horizon', shouldOpenInNewTab: true)
                ->icon('heroicon-o-queue-list')
                ->group('System Monitoring')
                ->sort(101)
                ->visible(fn (): bool => $this->navigationService->canAccessSystemMonitoring()),
        ];
    }
}
