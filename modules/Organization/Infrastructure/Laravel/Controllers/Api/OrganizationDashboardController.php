<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Controllers\Api;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organization\Infrastructure\Laravel\Http\Resources\OrganizationDashboardResource;
use Modules\Organization\Infrastructure\Laravel\Repository\OrganizationDashboardRepository;
use Modules\Shared\Application\ReadModel\ReadModelInterface;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class OrganizationDashboardController
{
    public function __construct(
        private OrganizationDashboardRepository $dashboardRepository,
    ) {}

    /**
     * Get organization dashboard data with optimized field selection and caching.
     */
    public function __invoke(Request $request, int $organizationId): JsonResponse
    {
        // Validate request parameters for field selection
        $validated = $request->validate([
            'fields' => 'array',
            'fields.*' => 'string|in:organization,employees,campaigns,financial,donations,performance,engagement,top_performers,recent_activity',
            'include' => 'string|in:trends',
            'refresh' => 'boolean',
        ]);

        // Allow cache refresh for admins
        if ($validated['refresh'] ?? false) {
            $this->dashboardRepository->invalidateOrganizationCache($organizationId);
        }

        // Get dashboard read model with optimized caching
        $dashboard = $this->dashboardRepository->find($organizationId);

        if (! $dashboard instanceof ReadModelInterface) {
            return ApiResponse::notFound('Organization dashboard not found');
        }

        // Transform using optimized resource
        $dashboardResource = new OrganizationDashboardResource($dashboard);

        // Calculate cache headers based on data freshness
        $lastModified = now();
        $etag = md5($dashboard->getVersion() . serialize($validated));

        $headers = [
            'Cache-Control' => 'private, max-age=300, s-maxage=300', // 5 minutes for dashboard
            'Vary' => 'Accept, Accept-Encoding, Authorization, X-Organization-Id',
            'Last-Modified' => $lastModified->format('D, d M Y H:i:s T'),
            'ETag' => '"' . $etag . '"',
            'X-Organization-ID' => (string) $organizationId,
            'X-Data-Source' => 'read_model',
            'X-Cache-Status' => 'hit',
        ];

        // Headers are already complete

        // Check for client-side caching
        if ($request->header('If-None-Match') === '"' . $etag . '"') {
            return response()->json([], 304, $headers);
        }

        if ($request->header('If-Modified-Since')) {
            $ifModifiedSince = strtotime($request->header('If-Modified-Since'));
            $lastModifiedTime = $lastModified->timestamp;

            if ($ifModifiedSince >= $lastModifiedTime) {
                return response()->json([], 304, $headers);
            }
        }

        return ApiResponse::success(
            data: $dashboardResource->toArray($request),
            message: 'Organization dashboard retrieved successfully',
            headers: $headers
        );
    }

    /**
     * Get cache statistics for organization dashboard (admin endpoint).
     */
    public function cacheStats(Request $request, int $organizationId): JsonResponse
    {
        $stats = $this->dashboardRepository->getCacheStatistics($organizationId);

        return ApiResponse::success(
            data: $stats,
            message: 'Cache statistics retrieved successfully',
            headers: ['Cache-Control' => 'no-cache, no-store, must-revalidate']
        );
    }

    /**
     * Warm cache for organization dashboard (admin endpoint).
     */
    public function warmCache(Request $request, int $organizationId): JsonResponse
    {
        try {
            $this->dashboardRepository->warmCacheForOrganizations([$organizationId]);

            return ApiResponse::success(
                data: ['organization_id' => $organizationId, 'cache_warmed' => true],
                message: 'Cache warmed successfully'
            );
        } catch (Exception $e) {
            return ApiResponse::error('Failed to warm cache: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Invalidate cache for organization dashboard (admin endpoint).
     */
    public function invalidateCache(Request $request, int $organizationId): JsonResponse
    {
        try {
            $this->dashboardRepository->invalidateOrganizationCache($organizationId);

            return ApiResponse::success(
                data: ['organization_id' => $organizationId, 'cache_invalidated' => true],
                message: 'Cache invalidated successfully'
            );
        } catch (Exception $e) {
            return ApiResponse::error('Failed to invalidate cache: ' . $e->getMessage(), null, 500);
        }
    }
}
