<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Log;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Infrastructure\Laravel\Traits\HasTenantAwareCache;
use Modules\User\Domain\Repository\UserRepositoryInterface;

class HomepageStatsService
{
    use HasTenantAwareCache;

    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly DonationRepositoryInterface $donationRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get all homepage statistics from application cache
     *
     * @return array<string, mixed>
     */
    public function getHomepageStats(): array
    {
        try {
            // Try to get from Redis first with tenant-aware cache key
            $redisKey = $this->getTenantAwareCacheKey('page:home');
            Log::debug('HomepageStatsService: Looking for cache key', ['redis_key' => $redisKey]);

            $cached = Redis::get($redisKey);

            if ($cached) {
                $data = json_decode($cached, true);
                if (is_array($data)) {
                    Log::debug('HomepageStatsService: Cache hit, returning cached data', ['data_keys' => array_keys($data)]);

                    return $data;
                }
            }

            Log::warning('HomepageStatsService: Cache miss, falling back to database/empty stats', ['redis_key' => $redisKey]);

            // Fallback to database if Redis doesn't have the data
            $cached = DB::table('application_cache')
                ->where('cache_key', 'page_home')
                ->where('cache_status', 'ready')
                ->first();

            if (! $cached || ! property_exists($cached, 'stats_data') || ! $cached->stats_data) {
                return $this->getEmptyHomepageStats();
            }

            return json_decode((string) $cached->stats_data, true);
        } catch (Exception) {
            return $this->getEmptyHomepageStats();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getEmptyHomepageStats(): array
    {
        return [
            'impact' => [
                'total_raised' => '€0',
                'total_raised_raw' => 0,
                'active_campaigns' => 0,
                'participating_employees' => '0',
                'participating_employees_raw' => 0,
                'countries_reached' => 0,
            ],
            'featured_campaigns' => [],
            'employee_stats' => [
                'total_employees' => '0+',
                'total_employees_raw' => 0,
                'total_raised_all_time' => '€0',
                'total_raised_all_time_raw' => 0,
                'total_campaigns' => '0',
                'total_campaigns_raw' => 0,
            ],
        ];
    }

    /**
     * Get impact statistics for the main counter section
     *
     * @return array<string, mixed>
     */
    public function getImpactStats(): array
    {
        // Use optimized SQL aggregation instead of loading all campaigns
        $activeCampaignCount = $this->campaignRepository->getActiveCampaignsCount();
        $totalRaised = $this->campaignRepository->getActiveRaisedAmount() * 100; // Convert to cents

        // Get participating employees count
        $participatingEmployees = $this->getParticipatingEmployeesCount();

        // Get countries reached (from organization data)
        $countriesReached = $this->getCountriesReachedCount();

        return [
            'total_raised' => $this->formatCurrency((int) $totalRaised),
            'total_raised_raw' => $totalRaised,
            'active_campaigns' => $activeCampaignCount,
            'participating_employees' => number_format($participatingEmployees),
            'participating_employees_raw' => $participatingEmployees,
            'countries_reached' => $countriesReached,
        ];
    }

    /**
     * Get featured campaigns for homepage display
     *
     * @return list<array<string, mixed>>
     */
    public function getFeaturedCampaigns(): array
    {
        $campaigns = $this->campaignRepository->getFeaturedCampaigns(3);

        if ($campaigns === []) {
            // Fallback to active campaigns sorted by donation progress
            $campaigns = $this->campaignRepository->findByStatus(CampaignStatus::ACTIVE, 3);
        }

        // Array of different default images for variety
        $defaultImages = [
            'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // Children education/charity
            'https://images.unsplash.com/photo-1559027615-cd4628902d4a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // Environmental/nature
            'https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // Community/volunteers
        ];

        $featuredData = [];
        $index = 0;
        foreach ($campaigns as $campaign) {
            // Campaign model uses properties directly
            $goalAmount = (float) $campaign->goal_amount * 100; // Convert to cents
            $raisedAmount = (float) $campaign->current_amount * 100; // Convert to cents
            $progress = $goalAmount > 0 ? round(($raisedAmount / $goalAmount) * 100) : 0;

            // Use campaign's image if available, otherwise use a different default image for each campaign
            $image = $campaign->featured_image ?? $defaultImages[$index % count($defaultImages)];

            $featuredData[] = [
                'id' => $campaign->id,
                'title' => $campaign->getTitle(),
                'description' => $campaign->getDescription(),
                'category' => $campaign->category ?? 'General',
                'raised' => $this->formatCurrency((int) $raisedAmount),
                'raised_raw' => $raisedAmount,
                'goal' => $this->formatCurrency((int) $goalAmount),
                'goal_raw' => $goalAmount,
                'progress' => min($progress, 100),
                'image' => $image,
                'slug' => $campaign->slug,
            ];
            $index++;
        }

        return $featuredData;
    }

    /**
     * Get employee statistics for the empowering section
     *
     * @return array<string, mixed>
     */
    public function getEmployeeStats(): array
    {
        $totalEmployees = $this->userRepository->getTotalEmployeeCount();

        // Use optimized SQL aggregation instead of loading all campaigns
        $totalCampaignCount = $this->campaignRepository->getTotalCampaignsCount();
        $totalRaisedAllTime = $this->campaignRepository->getTotalRaisedAmount() * 100; // Convert to cents

        return [
            'total_employees' => $this->formatLargeNumber($totalEmployees),
            'total_employees_raw' => $totalEmployees,
            'total_raised_all_time' => $this->formatCurrency((int) $totalRaisedAllTime),
            'total_raised_all_time_raw' => $totalRaisedAllTime,
            'total_campaigns' => number_format($totalCampaignCount),
            'total_campaigns_raw' => $totalCampaignCount,
        ];
    }

    /**
     * Get count of employees who have made at least one donation
     */
    private function getParticipatingEmployeesCount(): int
    {
        return $this->donationRepository->getUniqueDonatorsCount();
    }

    /**
     * Get count of countries reached based on organization data
     */
    private function getCountriesReachedCount(): int
    {
        try {
            // Use raw SQL to get distinct countries count - much faster than loading all orgs
            $count = DB::table('organizations')
                ->whereNotNull('country')
                ->distinct('country')
                ->count('country');

            return $count ?: 42; // Fallback to previous static value if no data
        } catch (Exception) {
            // Fallback if query fails
            return 42;
        }
    }

    /**
     * Format currency for display
     */
    private function formatCurrency(int $cents): string
    {
        $amount = $cents / 100;

        if ($amount >= 1000000) {
            return '€' . number_format($amount / 1000000, 1) . 'M';
        }

        if ($amount >= 1000) {
            return '€' . number_format($amount / 1000, 0) . 'K';
        }

        return '€' . number_format($amount, 0);
    }

    /**
     * Format large numbers for display
     */
    private function formatLargeNumber(int $number): string
    {
        if ($number >= 1000) {
            return number_format($number / 1000, 0) . ',000+';
        }

        return number_format($number) . '+';
    }

    /**
     * Clear homepage statistics cache
     */
    public function clearCache(): void
    {
        try {
            // Clear Redis cache with tenant-aware key
            $redisKey = $this->getTenantAwareCacheKey('page:home');
            Redis::del($redisKey);

            Log::info('HomepageStatsService: Cleared cache', ['redis_key' => $redisKey]);

            // Also clear database cache status for backwards compatibility
            DB::table('application_cache')
                ->where('cache_key', 'page_home')
                ->update(['cache_status' => 'pending']);
        } catch (Exception $e) {
            // If cache clearing fails, log but don't throw
            Log::warning('Failed to clear homepage stats cache: ' . $e->getMessage());
        }
    }

    /**
     * Get tenant-aware cache key following the same pattern as RedisCacheRepository
     */
    private function getTenantAwareCacheKey(string $baseKey): string
    {
        return self::formatCacheKey($baseKey);
    }
}
