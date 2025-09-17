<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Enum;

enum DonationStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
}
