<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\Port;

/**
 * Port interface for admin navigation management.
 * Defines the contract for registering custom navigation items in the admin panel.
 */
interface AdminNavigationPort
{
    /**
     * Register system monitoring navigation items.
     *
     * @return array<int, array{
     *     label: string,
     *     url: string,
     *     icon: string,
     *     group: string,
     *     shouldOpenInNewTab: bool,
     *     visible: bool,
     *     sort?: int
     * }>
     */
    public function getSystemMonitoringItems(): array;

    /**
     * Check if user has permission to view system monitoring tools.
     */
    public function canAccessSystemMonitoring(): bool;
}
