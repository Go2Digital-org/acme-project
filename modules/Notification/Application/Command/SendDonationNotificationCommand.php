<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class SendDonationNotificationCommand implements CommandInterface
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        public int $donationId,
        public int|string $userId,
        public int $campaignId,
        public float $amount,
        public string $currency,
        public string $campaignTitle,
        public string $status = self::STATUS_COMPLETED,
        public ?string $donorName = null,
        public ?string $failureReason = null,
        public ?string $refundReason = null,
    ) {}
}
