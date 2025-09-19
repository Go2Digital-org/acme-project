<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Application\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Category\Domain\Repository\CategoryRepositoryInterface;
use Modules\Currency\Domain\Repository\CurrencyRepositoryInterface;
use Modules\Shared\Application\Service\HomepageStatsService;
use Modules\Shared\Domain\Repository\PageRepositoryInterface;

final readonly class PageStatsCalculator
{
    public function __construct(
        private HomepageStatsService $homepageStatsService,
        private CurrencyRepositoryInterface $currencyRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private PageRepositoryInterface $pageRepository
    ) {}

    /**
     * Calculate statistics for all system page/cache types
     *
     * @return array<string, mixed>
     */
    public function calculateAllPageStats(): array
    {
        $stats = [];

        try {
            // Page: home
            $stats['page:home'] = $this->calculateHomepageStats();

            // System: active_currencies
            $stats['system:active_currencies'] = $this->calculateActiveCurrenciesStats();

            // System: footer_pages
            $stats['system:footer_pages'] = $this->calculateFooterPagesStats();

            // System: campaigns_list
            $stats['system:campaigns_list'] = $this->calculateCampaignsListStats();

        } catch (Exception $e) {
            Log::error('Error calculating page stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $stats;
    }

    /**
     * Calculate statistics for a specific page/system cache
     *
     * @return array<string, mixed>
     */
    public function calculatePageStats(string $pageKey): array
    {
        // Handle campaign pagination pages
        if (str_starts_with($pageKey, 'campaigns:page:')) {
            $page = (int) str_replace('campaigns:page:', '', $pageKey);

            return $this->calculateCampaignPageStats($page);
        }

        return match ($pageKey) {
            'page:home' => $this->calculateHomepageStats(),
            'system:active_currencies' => $this->calculateActiveCurrenciesStats(),
            'system:footer_pages' => $this->calculateFooterPagesStats(),
            'system:campaigns_list' => $this->calculateCampaignsListStats(),
            default => throw new Exception("Unknown page key: {$pageKey}"),
        };
    }

    /**
     * Calculate homepage statistics using the dedicated service
     *
     * @return array<string, mixed>
     */
    private function calculateHomepageStats(): array
    {
        try {
            // Use the dedicated HomepageStatsService for comprehensive homepage data
            $impactStats = $this->homepageStatsService->getImpactStats();
            $featuredCampaigns = $this->homepageStatsService->getFeaturedCampaigns();
            $employeeStats = $this->homepageStatsService->getEmployeeStats();

            return [
                'impact' => $impactStats,
                'featured_campaigns' => $featuredCampaigns,
                'employee_stats' => $employeeStats,
                'cache_meta' => [
                    'generated_at' => now()->toISOString(),
                    'expires_at' => now()->addHour()->toISOString(),
                    'cache_key' => 'page:home',
                    'version' => '1.0',
                ],
            ];
        } catch (Exception $e) {
            Log::warning('Error calculating homepage stats', ['error' => $e->getMessage()]);

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
                'cache_meta' => [
                    'generated_at' => now()->toISOString(),
                    'expires_at' => now()->addHour()->toISOString(),
                    'cache_key' => 'page:home',
                    'version' => '1.0',
                    'error' => true,
                ],
            ];
        }
    }

    /**
     * Calculate active currencies statistics
     *
     * @return array<string, mixed>
     */
    private function calculateActiveCurrenciesStats(): array
    {
        try {
            // Get active currencies from the repository
            $currencies = $this->currencyRepository->getActive();

            $currencyData = [];
            $defaultCurrency = null;

            foreach ($currencies as $currency) {
                $currencyInfo = [
                    'code' => $currency->code ?? 'EUR',
                    'symbol' => $currency->symbol ?? '€',
                    'name' => $currency->name ?? 'Euro',
                    'rate' => $currency->exchange_rate ?? 1.0,
                    'is_default' => $currency->is_default ?? false,
                ];

                $currencyData[] = $currencyInfo;

                if ($currencyInfo['is_default']) {
                    $defaultCurrency = $currencyInfo;
                }
            }

            return [
                'currencies' => $currencyData,
                'total_currencies' => count($currencyData),
                'default_currency' => $defaultCurrency ?? ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
                'cache_meta' => [
                    'generated_at' => now()->toISOString(),
                    'expires_at' => now()->addHours(6)->toISOString(), // Currencies change less frequently
                    'cache_key' => 'system:active_currencies',
                    'version' => '1.0',
                ],
            ];
        } catch (Exception $e) {
            Log::warning('Error calculating active currencies stats', ['error' => $e->getMessage()]);

            return [
                'currencies' => [
                    ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro', 'rate' => 1.0, 'is_default' => true],
                    ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar', 'rate' => 1.1, 'is_default' => false],
                    ['code' => 'GBP', 'symbol' => '£', 'name' => 'British Pound', 'rate' => 0.85, 'is_default' => false],
                ],
                'total_currencies' => 3,
                'default_currency' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
                'cache_meta' => [
                    'generated_at' => now()->toISOString(),
                    'expires_at' => now()->addHours(6)->toISOString(),
                    'cache_key' => 'system:active_currencies',
                    'version' => '1.0',
                    'fallback' => true,
                ],
            ];
        }
    }

    /**
     * Calculate footer pages statistics
     *
     * @return array<string, mixed>
     */
    private function calculateFooterPagesStats(): array
    {
        try {
            // Get footer pages from the repository (using published pages as fallback)
            $footerPages = $this->pageRepository->getPublishedPages();

            $pagesData = [];

            foreach ($footerPages as $page) {
                $pagesData[] = [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'url' => "/pages/{$page->slug}",
                    'is_published' => $page->isPublished(),
                    'order' => $page->order ?? 0,
                    'category' => 'general',
                ];
            }

            // Sort by order
            usort($pagesData, static fn (array $a, array $b): int => $a['order'] <=> $b['order']);

            return [
                'pages' => $pagesData,
                'total_pages' => count($pagesData),
                'categories' => array_unique(array_column($pagesData, 'category')),
                'cache_meta' => [
                    'generated_at' => now()->toISOString(),
                    'expires_at' => now()->addHours(12)->toISOString(), // Pages change infrequently
                    'cache_key' => 'system:footer_pages',
                    'version' => '1.0',
                ],
            ];
        } catch (Exception $e) {
            Log::warning('Error calculating footer pages stats', ['error' => $e->getMessage()]);

            return [
                'pages' => [
                    ['id' => 1, 'title' => 'About Us', 'slug' => 'about-us', 'url' => '/pages/about-us', 'is_published' => true, 'order' => 1, 'category' => 'company'],
                    ['id' => 2, 'title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'url' => '/pages/privacy-policy', 'is_published' => true, 'order' => 2, 'category' => 'legal'],
                    ['id' => 3, 'title' => 'Terms of Service', 'slug' => 'terms-of-service', 'url' => '/pages/terms-of-service', 'is_published' => true, 'order' => 3, 'category' => 'legal'],
                    ['id' => 4, 'title' => 'Contact Us', 'slug' => 'contact-us', 'url' => '/pages/contact-us', 'is_published' => true, 'order' => 4, 'category' => 'support'],
                ],
                'total_pages' => 4,
                'categories' => ['company', 'legal', 'support'],
                'cache_meta' => [
                    'generated_at' => now()->toISOString(),
                    'expires_at' => now()->addHours(12)->toISOString(),
                    'cache_key' => 'system:footer_pages',
                    'version' => '1.0',
                    'fallback' => true,
                ],
            ];
        }
    }

    /**
     * Calculate campaigns list statistics
     *
     * @return array<string, mixed>
     */
    private function calculateCampaignsListStats(): array
    {
        try {
            // Get campaign categories and basic stats for the campaigns list page
            $categories = $this->categoryRepository->findActive();

            $categoriesData = [];
            foreach ($categories as $category) {
                $categoriesData[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description ?? '',
                    'campaign_count' => $category->campaigns_count ?? 0,
                    'is_featured' => $category->is_featured ?? false,
                ];
            }

            // Get some basic campaign statistics for filtering
            $campaignStats = DB::table('campaigns')
                ->selectRaw('
                    COUNT(*) as total_campaigns,
                    COUNT(CASE WHEN status = "active" THEN 1 END) as active_campaigns,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_campaigns,
                    COUNT(CASE WHEN status = "draft" THEN 1 END) as draft_campaigns
                ')
                ->first();

            return [
                'categories' => $categoriesData,
                'total_categories' => count($categoriesData),
                'campaign_stats' => [
                    'total' => $campaignStats->total_campaigns ?? 0,
                    'active' => $campaignStats->active_campaigns ?? 0,
                    'completed' => $campaignStats->completed_campaigns ?? 0,
                    'draft' => $campaignStats->draft_campaigns ?? 0,
                ],
                'filters' => [
                    'status' => ['active', 'completed', 'draft'],
                    'categories' => array_column($categoriesData, 'slug'),
                    'sort_options' => ['newest', 'oldest', 'goal_asc', 'goal_desc', 'progress'],
                ],
                'cache_meta' => [
                    'generated_at' => now()->toISOString(),
                    'expires_at' => now()->addMinutes(30)->toISOString(), // Campaign list changes more frequently
                    'cache_key' => 'system:campaigns_list',
                    'version' => '1.0',
                ],
            ];
        } catch (Exception $e) {
            Log::warning('Error calculating campaigns list stats', ['error' => $e->getMessage()]);

            return [
                'categories' => [
                    ['id' => 1, 'name' => 'Environment', 'slug' => 'environment', 'description' => 'Environmental causes', 'campaign_count' => 15, 'is_featured' => true],
                    ['id' => 2, 'name' => 'Education', 'slug' => 'education', 'description' => 'Educational initiatives', 'campaign_count' => 12, 'is_featured' => true],
                    ['id' => 3, 'name' => 'Health', 'slug' => 'health', 'description' => 'Health and wellness', 'campaign_count' => 18, 'is_featured' => false],
                    ['id' => 4, 'name' => 'Community', 'slug' => 'community', 'description' => 'Community projects', 'campaign_count' => 9, 'is_featured' => false],
                ],
                'total_categories' => 4,
                'campaign_stats' => [
                    'total' => 54,
                    'active' => 32,
                    'completed' => 18,
                    'draft' => 4,
                ],
                'filters' => [
                    'status' => ['active', 'completed', 'draft'],
                    'categories' => ['environment', 'education', 'health', 'community'],
                    'sort_options' => ['newest', 'oldest', 'goal_asc', 'goal_desc', 'progress'],
                ],
                'cache_meta' => [
                    'generated_at' => now()->toISOString(),
                    'expires_at' => now()->addMinutes(30)->toISOString(),
                    'cache_key' => 'system:campaigns_list',
                    'version' => '1.0',
                    'fallback' => true,
                ],
            ];
        }
    }

    /**
     * Calculate and cache campaign page data
     *
     * @return array<string, mixed>
     */
    private function calculateCampaignPageStats(int $page): array
    {
        Log::info('Starting campaign page stats calculation', [
            'page' => $page,
            'cache_key' => "campaigns:page:{$page}",
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
        ]);

        try {
            // Get campaign repository instance with detailed logging
            Log::debug('Resolving campaign repository', [
                'interface' => CampaignRepositoryInterface::class,
            ]);

            $campaignRepo = app(CampaignRepositoryInterface::class);

            if (! $campaignRepo) {
                throw new Exception('Campaign repository could not be resolved');
            }

            Log::debug('Campaign repository resolved successfully', [
                'repository_class' => $campaignRepo::class,
            ]);

            // Fetch campaigns for the specific page with common filters
            Log::debug('Fetching campaigns from repository', [
                'page' => $page,
                'per_page' => 12,
                'sort_by' => 'is_featured',
                'sort_order' => 'desc',
            ]);

            $campaigns = $campaignRepo->paginate(
                page: $page,
                perPage: 12,
                filters: [],
                sortBy: 'is_featured',
                sortOrder: 'desc'
            );

            if (! $campaigns) {
                throw new Exception('Campaign repository returned null/empty result');
            }

            Log::debug('Campaigns fetched successfully', [
                'total_campaigns' => method_exists($campaigns, 'total') ? $campaigns->total() : 'unknown',
                'current_page' => method_exists($campaigns, 'currentPage') ? $campaigns->currentPage() : 'unknown',
                'per_page' => method_exists($campaigns, 'perPage') ? $campaigns->perPage() : 'unknown',
                'items_count' => method_exists($campaigns, 'items') && is_countable($campaigns->items()) ? count($campaigns->items()) : 'unknown',
            ]);

            // Convert to array for caching
            $result = [
                'campaigns' => [
                    'data' => $campaigns->items(),
                    'total' => $campaigns->total(),
                    'per_page' => $campaigns->perPage(),
                    'current_page' => $campaigns->currentPage(),
                    'last_page' => $campaigns->lastPage(),
                ],
                'cache_meta' => [
                    'generated_at' => now()->toISOString(),
                    'expires_at' => now()->addMinutes(5)->toISOString(),
                    'cache_key' => "campaigns:page:{$page}",
                    'version' => '1.0',
                ],
            ];

            Log::info('Campaign page stats calculation completed successfully', [
                'page' => $page,
                'total_campaigns' => $campaigns->total(),
                'data_size' => strlen(json_encode($result) ?: '') . ' bytes',
                'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error("Error calculating campaign page {$page} stats", [
                'page' => $page,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
            ]);

            // Re-throw the exception so cache warming can handle it properly
            throw $e;
        }
    }
}
