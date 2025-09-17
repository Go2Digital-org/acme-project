<?php

declare(strict_types=1);

namespace Modules\Dashboard\Infrastructure\Laravel\Observer;

use Illuminate\Support\Facades\Cache;
use Modules\Donation\Domain\Model\Donation;

final class DashboardCacheObserver
{
    /**
     * Handle the Donation "created" event.
     */
    public function created(Donation $donation): void
    {
        $this->clearDashboardCaches($donation);
    }

    /**
     * Handle the Donation "updated" event.
     */
    public function updated(Donation $donation): void
    {
        $this->clearDashboardCaches($donation);
    }

    /**
     * Clear dashboard-related caches for a user
     */
    private function clearDashboardCaches(Donation $donation): void
    {
        // Clear user-specific caches
        if ($donation->user_id) {
            Cache::forget("user_ranking:{$donation->user_id}");
        }

        // Clear organization-wide caches
        // Note: We're assuming organization ID 1 for now
        Cache::forget('org_leaderboard:1:5');
        Cache::forget('org_leaderboard:1:10');

        // Don't clear active users count as it doesn't change with donations
    }
}
