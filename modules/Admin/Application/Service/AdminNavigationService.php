<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Service;

use Modules\Admin\Domain\Port\AdminNavigationPort;

/**
 * Application service for managing admin navigation items.
 * Handles business logic for navigation registration and permissions.
 */
class AdminNavigationService implements AdminNavigationPort
{
    /**
     * Register system monitoring navigation items for super admins.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSystemMonitoringItems(): array
    {
        if (! $this->canAccessSystemMonitoring()) {
            return [];
        }

        return [
            [
                'label' => 'Pulse',
                'url' => '/pulse',
                'icon' => 'heroicon-o-chart-bar',
                'group' => 'System Monitoring',
                'shouldOpenInNewTab' => true,
                'visible' => true,
                'sort' => 100,
            ],
            [
                'label' => 'Horizon',
                'url' => '/horizon',
                'icon' => 'heroicon-o-queue-list',
                'group' => 'System Monitoring',
                'shouldOpenInNewTab' => true,
                'visible' => true,
                'sort' => 101,
            ],
        ];
    }

    /**
     * Check if current user has permission to view system monitoring tools.
     * Only super_admin role can access Pulse and Horizon.
     */
    public function canAccessSystemMonitoring(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Check if user has super_admin role using Laravel's hasRole method
        return method_exists($user, 'hasRole') && $user->hasRole('super_admin');
    }
}
