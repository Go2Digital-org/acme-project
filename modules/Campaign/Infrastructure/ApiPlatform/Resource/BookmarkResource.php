<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Illuminate\Http\Response;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Campaign\Infrastructure\ApiPlatform\Handler\Processor\RemoveBookmarkProcessor;
use Modules\Campaign\Infrastructure\ApiPlatform\Handler\Processor\ToggleBookmarkProcessor;
use Modules\Campaign\Infrastructure\ApiPlatform\Handler\Provider\BookmarkStatusProvider;
use Modules\Campaign\Infrastructure\ApiPlatform\Handler\Provider\UserBookmarksProvider;

#[ApiResource(
    shortName: 'Bookmark',
    operations: [
        // Toggle bookmark for a campaign (POST /campaigns/{id}/bookmark)
        new Post(
            uriTemplate: '/campaigns/{id}/bookmark',
            requirements: ['id' => '\d+'],
            status: Response::HTTP_OK,
            processor: ToggleBookmarkProcessor::class,
        ),

        // Remove bookmark from a campaign (DELETE /campaigns/{id}/bookmark)
        new Delete(
            uriTemplate: '/campaigns/{id}/bookmark',
            requirements: ['id' => '\d+'],
            status: Response::HTTP_OK,
            read: false,
            deserialize: false,
            processor: RemoveBookmarkProcessor::class,
        ),

        // Get bookmark status for a campaign (GET /campaigns/{id}/bookmark/status)
        new Get(
            uriTemplate: '/campaigns/{id}/bookmark/status',
            requirements: ['id' => '\d+'],
            provider: BookmarkStatusProvider::class,
        ),

        // Get user's bookmarked campaigns (GET /campaigns/bookmarked)
        new GetCollection(
            uriTemplate: '/campaigns/bookmarked',
            paginationEnabled: true,
            paginationItemsPerPage: 20,
            paginationMaximumItemsPerPage: 100,
            paginationClientItemsPerPage: true,
            provider: UserBookmarksProvider::class,
        ),

        // Alias routes for /favorite endpoints

        // Toggle favorite for a campaign (POST /campaigns/{id}/favorite)
        new Post(
            uriTemplate: '/campaigns/{id}/favorite',
            requirements: ['id' => '\d+'],
            status: Response::HTTP_OK,
            processor: ToggleBookmarkProcessor::class,
        ),

        // Remove favorite from a campaign (DELETE /campaigns/{id}/favorite)
        new Delete(
            uriTemplate: '/campaigns/{id}/favorite',
            requirements: ['id' => '\d+'],
            status: Response::HTTP_OK,
            read: false,
            deserialize: false,
            processor: RemoveBookmarkProcessor::class,
        ),

        // Get favorite status for a campaign (GET /campaigns/{id}/favorite/status)
        new Get(
            uriTemplate: '/campaigns/{id}/favorite/status',
            requirements: ['id' => '\d+'],
            provider: BookmarkStatusProvider::class,
        ),

        // Get user's favorite campaigns (GET /campaigns/favorites)
        new GetCollection(
            uriTemplate: '/campaigns/favorites',
            paginationEnabled: true,
            paginationItemsPerPage: 20,
            paginationMaximumItemsPerPage: 100,
            paginationClientItemsPerPage: true,
            provider: UserBookmarksProvider::class,
        ),
    ],
    middleware: ['auth:sanctum', 'api.locale'],
)]
class BookmarkResource
{
    public function __construct(
        public ?int $id = null,
        public ?int $campaign_id = null,
        public ?string $campaign_title = null,
        public ?string $campaign_slug = null,
        public ?string $campaign_description = null,
        public ?float $campaign_goal_amount = null,
        public ?float $campaign_current_amount = null,
        public ?float $progress_percentage = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public ?string $status = null,
        public ?string $category = null,
        public ?string $featured_image = null,
        public ?int $days_remaining = null,
        /** @var array<string, mixed>|null */
        public ?array $organization = null,
        /** @var array<string, mixed>|null */
        public ?array $creator = null,
        public ?bool $bookmarked = null,
        public ?int $bookmark_count = null,
        public ?string $message = null,
        public ?string $created_at = null,
    ) {}

    /**
     * Create a bookmark status response.
     */
    public static function bookmarkStatus(
        int $campaignId,
        bool $isBookmarked,
        int $bookmarkCount,
    ): self {
        return new self(
            campaign_id: $campaignId,
            bookmarked: $isBookmarked,
            bookmark_count: $bookmarkCount,
        );
    }

    /**
     * Create a bookmark action response.
     */
    public static function bookmarkAction(
        int $campaignId,
        bool $wasBookmarked,
        int $bookmarkCount,
        string $message,
    ): self {
        return new self(
            campaign_id: $campaignId,
            bookmarked: $wasBookmarked,
            bookmark_count: $bookmarkCount,
            message: $message,
        );
    }

    /**
     * Create a bookmark response from campaign data.
     */
    /**
     * @param  array<string, mixed>  $campaignData
     */
    public static function fromCampaignData(array $campaignData): self
    {
        // Handle translatable fields (extract English or first available value)
        $title = is_array($campaignData['title'])
            ? ($campaignData['title']['en'] ?? array_values($campaignData['title'])[0] ?? null)
            : $campaignData['title'];

        $description = is_array($campaignData['description'])
            ? ($campaignData['description']['en'] ?? array_values($campaignData['description'])[0] ?? null)
            : $campaignData['description'];

        // Handle status value object
        $status = $campaignData['status'];
        if ($status instanceof CampaignStatus) {
            $status = $status->value;
        }

        return new self(
            id: $campaignData['id'], // Use campaign ID as the resource ID for API Platform
            campaign_id: $campaignData['id'],
            campaign_title: $title,
            campaign_slug: $campaignData['slug'] ?? null,
            campaign_description: $description,
            campaign_goal_amount: $campaignData['goal_amount'],
            campaign_current_amount: $campaignData['current_amount'],
            progress_percentage: $campaignData['progress_percentage'],
            start_date: $campaignData['start_date'],
            end_date: $campaignData['end_date'],
            status: $status,
            category: $campaignData['category'] ?? null,
            featured_image: $campaignData['featured_image'] ?? null,
            days_remaining: $campaignData['days_remaining'],
            organization: $campaignData['organization'] ?? null,
            creator: $campaignData['creator'] ?? null,
            created_at: $campaignData['created_at'] ?? null,
        );
    }
}
