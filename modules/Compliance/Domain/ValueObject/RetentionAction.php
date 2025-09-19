<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\ValueObject;

enum RetentionAction: string
{
    case DELETE = 'delete';
    case ANONYMIZE = 'anonymize';
    case ARCHIVE = 'archive';
    case RETAIN = 'retain';
    case REVIEW = 'review';

    public function isDestructive(): bool
    {
        return match ($this) {
            self::DELETE => true,
            self::ANONYMIZE => true,
            default => false
        };
    }

    public function requiresApproval(): bool
    {
        return match ($this) {
            self::DELETE => true,
            self::ANONYMIZE => true,
            default => false
        };
    }

    public function isReversible(): bool
    {
        return match ($this) {
            self::DELETE => false,
            self::ANONYMIZE => false,
            self::ARCHIVE => true,
            self::RETAIN => true,
            self::REVIEW => true,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DELETE => 'Permanently delete the data',
            self::ANONYMIZE => 'Remove personally identifiable information',
            self::ARCHIVE => 'Move data to long-term storage',
            self::RETAIN => 'Keep data in current system',
            self::REVIEW => 'Manually review retention decision',
        };
    }

    public function riskLevel(): string
    {
        return match ($this) {
            self::DELETE => 'high',
            self::ANONYMIZE => 'medium',
            self::ARCHIVE => 'low',
            self::RETAIN => 'low',
            self::REVIEW => 'medium',
        };
    }
}
