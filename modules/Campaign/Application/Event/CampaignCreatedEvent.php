<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;
use Modules\Shared\Domain\Event\CampaignEventInterface;

class CampaignCreatedEvent extends AbstractDomainEvent implements CampaignEventInterface
{
    /**
     * @param  array<string, string>|null  $titleTranslations
     * @param  array<string, string>|null  $descriptionTranslations
     */
    public function __construct(
        public readonly int $campaignId,
        public readonly int $userId,
        public readonly int $organizationId,
        public readonly string $title,
        public readonly float $goalAmount,
        public readonly ?array $titleTranslations = null,
        public readonly ?array $descriptionTranslations = null,
        public readonly ?string $locale = 'en',
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'campaign.created';
    }

    public function getCampaignId(): int
    {
        return $this->campaignId;
    }

    public function getEntityType(): string
    {
        return 'campaign';
    }

    public function getEntityId(): int
    {
        return $this->campaignId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'campaignId' => $this->campaignId,
            'userId' => $this->userId,
            'organizationId' => $this->organizationId,
            'title' => $this->title,
            'goalAmount' => $this->goalAmount,
            'titleTranslations' => $this->titleTranslations,
            'descriptionTranslations' => $this->descriptionTranslations,
            'locale' => $this->locale,
        ];
    }
}
