<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Http\Response;
use JsonSerializable;
use Modules\Campaign\Application\Request\CreateCampaignRequest;
use Modules\Campaign\Application\Request\UpdateCampaignRequest;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Infrastructure\ApiPlatform\Handler\Processor\CreateCampaignProcessor;
use Modules\Campaign\Infrastructure\ApiPlatform\Handler\Processor\DeleteCampaignProcessor;
use Modules\Campaign\Infrastructure\ApiPlatform\Handler\Processor\PatchCampaignProcessor;
use Modules\Campaign\Infrastructure\ApiPlatform\Handler\Processor\UpdateCampaignProcessor;
use Modules\Campaign\Infrastructure\ApiPlatform\Handler\Provider\CampaignCollectionProvider;
use Modules\Campaign\Infrastructure\ApiPlatform\Handler\Provider\CampaignItemProvider;
use Modules\Shared\Infrastructure\ApiPlatform\Filter\MultiSearchFilter;

#[ApiResource(
    shortName: 'Campaign',
    formats: ['jsonld' => ['application/ld+json'], 'json' => ['application/json']],
    types: ['https://schema.org/Event'],
    operations: [
        new GetCollection(
            uriTemplate: '/campaigns',
            paginationEnabled: true,
            paginationItemsPerPage: 20,
            paginationMaximumItemsPerPage: 100,
            paginationClientItemsPerPage: true,
            provider: CampaignCollectionProvider::class,
            parameters: [
                'id' => new QueryParameter(key: 'id', filter: EqualsFilter::class),
                'title' => new QueryParameter(key: 'title', filter: PartialSearchFilter::class),
                'status' => new QueryParameter(key: 'status', filter: EqualsFilter::class),
                'organization_id' => new QueryParameter(key: 'organization_id', filter: EqualsFilter::class),
                'user_id' => new QueryParameter(key: 'user_id', filter: EqualsFilter::class),
                'search' => new QueryParameter(
                    key: 'search',
                    filter: MultiSearchFilter::class,
                    extraProperties: ['fields' => ['title', 'description']],
                ),
                'sort[:property]' => new QueryParameter(key: 'sort[:property]', filter: OrderFilter::class),
                'start_date' => new QueryParameter(key: 'start_date', filter: DateFilter::class),
                'end_date' => new QueryParameter(key: 'end_date', filter: DateFilter::class),
                'created_at' => new QueryParameter(key: 'created_at', filter: DateFilter::class),
                'updated_at' => new QueryParameter(key: 'updated_at', filter: DateFilter::class),
                'locale' => new QueryParameter(key: 'locale', filter: EqualsFilter::class),
            ],
        ),
        new Get(
            uriTemplate: '/campaigns/{id}',
            provider: CampaignItemProvider::class,
        ),
        new Post(
            uriTemplate: '/campaigns',
            status: Response::HTTP_CREATED,
            processor: CreateCampaignProcessor::class,
            rules: CreateCampaignRequest::class,
        ),
        new Put(
            uriTemplate: '/campaigns/{id}',
            status: Response::HTTP_OK,
            processor: UpdateCampaignProcessor::class,
            rules: UpdateCampaignRequest::class,
        ),
        new Patch(
            uriTemplate: '/campaigns/{id}',
            status: Response::HTTP_OK,
            processor: PatchCampaignProcessor::class,
            rules: UpdateCampaignRequest::class,
        ),
        new Delete(
            uriTemplate: '/campaigns/{id}',
            status: Response::HTTP_NO_CONTENT,
            processor: DeleteCampaignProcessor::class,
        ),
    ],
    middleware: ['auth:sanctum', 'api.locale'],
)]
class CampaignResource implements JsonSerializable
{
    public function __construct(
        public ?int $id = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?float $goalAmount = null,
        public ?float $goal_amount = null, // Add snake_case property for API compatibility
        public ?float $currentAmount = null,
        public ?string $startDate = null,
        public ?string $start_date = null, // Add snake_case property for API compatibility
        public ?string $endDate = null,
        public ?string $end_date = null, // Add snake_case property for API compatibility
        public ?string $status = null,
        public ?int $organizationId = null,
        public ?int $organization_id = null, // Add snake_case property for API compatibility
        public ?string $organizationName = null,
        public ?int $userId = null,
        public ?string $employeeName = null,
        public ?float $progressPercentage = null,
        public ?int $daysRemaining = null,
        public ?float $remainingAmount = null,
        public ?bool $hasReachedGoal = null,
        public ?bool $isActive = null,
        public ?bool $canAcceptDonation = null,
        public ?string $completedAt = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}

    // Add snake_case getters for API compatibility
    public function getGoal_amount(): ?float
    {
        return $this->goalAmount;
    }

    public function getStart_date(): ?string
    {
        return $this->startDate;
    }

    public function getEnd_date(): ?string
    {
        return $this->endDate;
    }

    public function getOrganization_id(): ?int
    {
        return $this->organizationId;
    }

    public static function fromModel(Campaign $campaign): self
    {
        $goalAmount = (float) $campaign->goal_amount;
        $startDate = $campaign->start_date?->toDateTimeString();
        $endDate = $campaign->end_date?->toDateTimeString();
        $organizationId = $campaign->organization_id;

        return new self(
            id: $campaign->id,
            title: $campaign->getTitle(), // Use the model method for proper translation handling
            description: $campaign->getDescription(), // Use the model method for proper translation handling
            goalAmount: $goalAmount,
            goal_amount: $goalAmount, // Set snake_case property
            currentAmount: (float) $campaign->current_amount,
            startDate: $startDate,
            start_date: $startDate, // Set snake_case property
            endDate: $endDate,
            end_date: $endDate, // Set snake_case property
            status: $campaign->status->value,
            organizationId: $organizationId,
            organization_id: $organizationId, // Set snake_case property
            organizationName: $campaign->organization ? $campaign->organization->getName() : null,
            userId: $campaign->user_id,
            employeeName: $campaign->employee->name ?? null,
            progressPercentage: $campaign->getProgressPercentage(),
            daysRemaining: $campaign->getDaysRemaining(),
            remainingAmount: $campaign->getRemainingAmount(),
            hasReachedGoal: $campaign->hasReachedGoal(),
            isActive: $campaign->isActive(),
            canAcceptDonation: $campaign->canAcceptDonation(),
            completedAt: $campaign->completed_at?->toDateTimeString(),
            createdAt: $campaign->created_at?->toDateTimeString(),
            updatedAt: $campaign->updated_at?->toDateTimeString(),
        );
    }

    /**
     * Custom JSON serialization to provide both camelCase and snake_case properties
     * for API compatibility
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'goalAmount' => $this->goalAmount,
            'goal_amount' => $this->goal_amount, // snake_case alias
            'currentAmount' => $this->currentAmount,
            'startDate' => $this->startDate,
            'start_date' => $this->start_date, // snake_case alias
            'endDate' => $this->endDate,
            'end_date' => $this->end_date, // snake_case alias
            'status' => $this->status,
            'organizationId' => $this->organizationId,
            'organization_id' => $this->organization_id, // snake_case alias
            'organizationName' => $this->organizationName,
            'userId' => $this->userId,
            'employeeName' => $this->employeeName,
            'progressPercentage' => $this->progressPercentage,
            'daysRemaining' => $this->daysRemaining,
            'remainingAmount' => $this->remainingAmount,
            'hasReachedGoal' => $this->hasReachedGoal,
            'isActive' => $this->isActive,
            'canAcceptDonation' => $this->canAcceptDonation,
            'completedAt' => $this->completedAt,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
