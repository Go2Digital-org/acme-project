<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Organization\Domain\Model\Organization;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final class ListOrganizationsController
{
    /**
     * List all organizations for dropdown selections.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'min:1', 'max:255'],
            'category' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = min((int) $request->query('per_page', 50), 100);
        $search = $request->query('search');
        $request->query('category');

        $query = Organization::query();

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('name->en', 'like', "%{$search}%")
                    ->orWhere('name->nl', 'like', "%{$search}%")
                    ->orWhere('name->fr', 'like', "%{$search}%")
                    ->orWhere('description->en', 'like', "%{$search}%")
                    ->orWhere('description->nl', 'like', "%{$search}%")
                    ->orWhere('description->fr', 'like', "%{$search}%");
            });
        }

        // Apply category filter (removed - category no longer exists)
        // Organizations are now filtered by status instead

        // Only show active and verified organizations
        $query->where('is_active', true)
            ->where('is_verified', true);

        $organizations = $query->orderBy('name->en')
            ->paginate($perPage);

        // Transform to simple format for dropdowns
        /** @var Collection<int, array{id: int, name: string, description: string|null, status: string, website: string|null, logo: string|null}> $organizationsData */
        $organizationsData = $organizations->getCollection()->map(fn (Organization $org): array => [
            'id' => $org->id,
            'name' => $org->getName(),
            'description' => $org->getDescription(),
            'status' => $org->status,
            'website' => $org->website,
            'logo' => $org->logo,
        ]);

        // Create a new paginator with the transformed data
        /** @var array<int, array{id: int, name: string, description: string|null, status: string, website: string|null, logo: string|null}> $transformedItems */
        $transformedItems = $organizationsData->toArray();

        $transformedPaginator = new LengthAwarePaginator(
            items: $transformedItems,
            total: $organizations->total(),
            perPage: $organizations->perPage(),
            currentPage: $organizations->currentPage(),
            options: [
                'path' => $organizations->path(),
                'pageName' => $organizations->getPageName(),
            ],
        );

        $transformedPaginator->appends($organizations->getUrlRange(1, $organizations->lastPage()));

        return ApiResponse::paginated(
            paginator: $transformedPaginator,
            message: 'Organizations retrieved successfully.',
        );
    }
}
