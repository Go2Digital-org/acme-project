<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\ViewComposer;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;
use Modules\Shared\Domain\Repository\SocialMediaRepositoryInterface;
use Modules\Shared\Infrastructure\Laravel\Traits\HasTenantAwareCache;

final readonly class FooterViewComposer
{
    use HasTenantAwareCache;

    public function __construct(
        private SocialMediaRepositoryInterface $socialMediaRepository,
    ) {}

    public function compose(View $view): void
    {
        // Get active social media links
        $socialMediaLinks = $this->socialMediaRepository->getActiveSocialMedia();

        // Get footer pages (legal, help, etc.)
        $footerPages = $this->getFooterPages();

        // Get current year for copyright
        $currentYear = date('Y');

        // Get company information
        $companyInfo = $this->getCompanyInfo();

        // Determine current page for active link highlighting
        $currentPage = request()->route()?->parameter('slug') ?? request()->path();

        $view->with([
            'socialMediaLinks' => $socialMediaLinks,
            'footerPages' => $footerPages,
            'currentYear' => $currentYear,
            'companyInfo' => $companyInfo,
            'currentPage' => $currentPage,
        ]);
    }

    /** @return array<array-key, mixed> */
    private function getFooterPages(): array
    {
        try {
            // Try to get from Redis first with tenant-aware cache key, but only if Redis is available
            $redisKey = $this->getTenantAwareCacheKey('system:footer_pages');
            $cached = null;

            // Check if Redis is configured and available
            if (config('database.redis.default.host') && app()->environment() !== 'testing') {
                try {
                    $cached = Redis::get($redisKey);
                } catch (Exception) {
                    // Redis connection failed, continue to database fallback
                    $cached = null;
                }
            }

            if ($cached) {
                $data = json_decode($cached, true);
                if (is_array($data)) {
                    return $data;
                }
            }

            // Fallback to database cache table
            $cached = DB::table('application_cache')
                ->where('cache_key', 'footer_pages')
                ->where('cache_status', 'ready')
                ->first();

            if ($cached && property_exists($cached, 'stats_data') && $cached->stats_data) {
                return json_decode((string) $cached->stats_data, true);
            }

            return $this->getEmptyFooterPages();
        } catch (Exception) {
            return $this->getEmptyFooterPages();
        }
    }

    /**
     * @return array<string, array<mixed>>
     */
    private function getEmptyFooterPages(): array
    {
        return [
            'support' => [],
            'corporate' => [],
            'legal' => [],
        ];
    }

    /** @return array<array-key, mixed> */
    private function getCompanyInfo(): array
    {
        return [
            'name' => config('app.name', 'ACME Corp'),
            'subtitle' => __('footer.company_subtitle'),
            'description' => __('footer.company_description'),
            'logo' => asset('images/acme-logo.svg'),
            'copyright' => __('footer.copyright'),
        ];
    }

    /**
     * Get tenant-aware cache key following the same pattern as RedisCacheRepository
     */
    private function getTenantAwareCacheKey(string $baseKey): string
    {
        return self::formatCacheKey($baseKey);
    }
}
