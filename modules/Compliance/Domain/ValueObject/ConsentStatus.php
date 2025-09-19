<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\ValueObject;

enum ConsentStatus: string
{
    case PENDING = 'pending';
    case GIVEN = 'given';
    case WITHDRAWN = 'withdrawn';
    case EXPIRED = 'expired';
    case DENIED = 'denied';

    public function isValid(): bool
    {
        return $this === self::GIVEN;
    }

    public function canProcess(): bool
    {
        return $this === self::GIVEN;
    }

    public function requiresAction(): bool
    {
        return match ($this) {
            self::PENDING => true,
            self::EXPIRED => true,
            default => false
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Consent',
            self::GIVEN => 'Consent Given',
            self::WITHDRAWN => 'Consent Withdrawn',
            self::EXPIRED => 'Consent Expired',
            self::DENIED => 'Consent Denied',
        };
    }
}
