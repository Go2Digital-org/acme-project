<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Event;

final readonly class CampaignPublishedEvent
{
    public function __construct(
        public int $publishedBy,
        public int $campaignId,
        public int $organizationId,
        public string $title,
        public float $targetAmount,
        public ?string $startDate,
        public ?string $endDate
    ) {}

    public function getCampaignId(): int
    {
        return $this->campaignId;
    }

    public function getCampaignTitle(): string
    {
        return $this->title;
    }

    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }
}
