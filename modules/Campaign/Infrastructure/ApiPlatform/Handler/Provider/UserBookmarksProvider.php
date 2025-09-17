<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Illuminate\Http\Request;
use Modules\Campaign\Application\Service\BookmarkService;
use Modules\Campaign\Infrastructure\ApiPlatform\Resource\BookmarkResource;
use Modules\Shared\Infrastructure\ApiPlatform\State\Paginator;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProviderInterface<BookmarkResource>
 */
final readonly class UserBookmarksProvider implements ProviderInterface
{
    public function __construct(
        private BookmarkService $bookmarkService,
        private Request $request,
        private Pagination $pagination,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     * @return Paginator<BookmarkResource>|array<int, BookmarkResource>
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): Paginator|array {
        $user = $this->request->user();

        if (! $user) {
            throw new UnauthorizedHttpException('', 'Authentication required');
        }

        $bookmarkedCampaigns = $this->bookmarkService->getUserBookmarksWithDetails($user->id);

        // Convert campaigns to BookmarkResource format
        $bookmarkResources = $bookmarkedCampaigns->map(fn ($campaign): BookmarkResource => BookmarkResource::fromCampaignData([
            'id' => $campaign->id,
            'title' => $campaign->title,
            'slug' => $campaign->slug ?? null,
            'description' => $campaign->description,
            'goal_amount' => $campaign->goal_amount,
            'current_amount' => $campaign->current_amount,
            'progress_percentage' => $campaign->getProgressPercentage(),
            'start_date' => $campaign->start_date?->toDateTimeString(),
            'end_date' => $campaign->end_date?->toDateTimeString(),
            'status' => $campaign->status,
            'category' => $campaign->category ?? null,
            'featured_image' => $campaign->featured_image ?? null,
            'days_remaining' => $campaign->getDaysRemaining(),
            'organization' => $campaign->organization !== null ? [
                'id' => $campaign->organization->id,
                'name' => $campaign->organization->getName(),
            ] : null,
            'creator' => $campaign->creator !== null ? [
                'id' => $campaign->creator->getId(),
                'name' => $campaign->creator->getName(),
            ] : null,
            'created_at' => $campaign->created_at?->toDateTimeString(),
        ]));

        // Handle pagination if requested
        if ($this->pagination->isEnabled($operation, $context)) {
            $page = $this->pagination->getPage($context);
            $limit = $this->pagination->getLimit($operation, $context);
            $offset = ($page - 1) * $limit;

            $paginatedResources = $bookmarkResources->slice($offset, $limit);
            $totalItems = $bookmarkResources->count();
            $lastPage = (int) ceil($totalItems / $limit);

            /** @var ArrayIterator<int, BookmarkResource> $iterator */
            $iterator = new ArrayIterator($paginatedResources->toArray());

            return new Paginator(
                $iterator,
                $page,
                $limit,
                $lastPage,
                $totalItems,
            );
        }

        return $bookmarkResources->toArray();
    }
}
