<?php

declare(strict_types=1);

namespace Modules\Dashboard\Domain\ValueObject;

use Illuminate\Support\Carbon;
use Modules\Shared\Domain\ValueObject\Money;

final readonly class ActivityFeedItem
{
    public function __construct(
        public string $id,
        public ActivityType $type,
        public string $description,
        public Carbon $occurredAt,
        public ?Money $amount,
        public ?string $relatedEntityType,
        public ?int $relatedEntityId,
        public ?string $relatedEntityTitle,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}

    public function getIconClass(): string
    {
        return match ($this->type) {
            ActivityType::DONATION => 'fas fa-heart text-secondary',
            ActivityType::CAMPAIGN_CREATED => 'fas fa-plus text-primary',
            ActivityType::CAMPAIGN_BOOKMARKED => 'fas fa-star text-accent',
            ActivityType::CAMPAIGN_SHARED => 'fas fa-share text-info',
            ActivityType::MILESTONE_REACHED => 'fas fa-trophy text-warning',
            default => 'fas fa-circle text-gray-400',
        };
    }

    public function getColorClass(): string
    {
        return match ($this->type) {
            ActivityType::DONATION => 'secondary',
            ActivityType::CAMPAIGN_CREATED => 'primary',
            ActivityType::CAMPAIGN_BOOKMARKED => 'accent',
            ActivityType::CAMPAIGN_SHARED => 'info',
            ActivityType::MILESTONE_REACHED => 'warning',
            default => 'gray',
        };
    }

    public function getFormattedTime(): string
    {
        return $this->occurredAt->diffForHumans();
    }

    public function getRelatedUrl(): ?string
    {
        if ($this->relatedEntityType === null || $this->relatedEntityId === null) {
            return null;
        }

        return match ($this->relatedEntityType) {
            'campaign' => route('campaigns.show', $this->relatedEntityId),
            'donation' => route('donations.show', $this->relatedEntityId),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'description' => $this->description,
            'occurredAt' => $this->occurredAt->toIso8601String(),
            'formattedTime' => $this->getFormattedTime(),
            'amount' => $this->amount?->amount,
            'formattedAmount' => $this->amount?->format(),
            'relatedEntityType' => $this->relatedEntityType,
            'relatedEntityId' => $this->relatedEntityId,
            'relatedEntityTitle' => $this->relatedEntityTitle,
            'relatedUrl' => $this->getRelatedUrl(),
            'iconClass' => $this->getIconClass(),
            'colorClass' => $this->getColorClass(),
            'metadata' => $this->metadata,
        ];
    }
}
