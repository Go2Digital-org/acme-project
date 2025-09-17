<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class SendCampaignNotificationCommand implements CommandInterface
{
    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_APPROVED = 'approved';

    public const EVENT_REJECTED = 'rejected';

    public const EVENT_PUBLISHED = 'published';

    public const EVENT_COMPLETED = 'completed';

    public function __construct(
        public int $campaignId,
        public int $userId,
        public string $eventType,
        public string $campaignTitle,
        public ?int $creatorId = null,
        public ?string $reason = null,
        public ?float $goalAmount = null,
        public ?string $currency = null,
    ) {}
}
