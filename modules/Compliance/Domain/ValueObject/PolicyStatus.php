<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\ValueObject;

enum PolicyStatus: string
{
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case ACTIVE = 'active';
    case RETIRED = 'retired';
    case ARCHIVED = 'archived';

    public function canBeActivated(): bool
    {
        return match ($this) {
            self::DRAFT => true,
            self::PENDING_REVIEW => true,
            default => false
        };
    }

    public function canBeEdited(): bool
    {
        return match ($this) {
            self::DRAFT => true,
            self::PENDING_REVIEW => true,
            default => false
        };
    }

    public function isPublic(): bool
    {
        return $this === self::ACTIVE;
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_REVIEW => 'Pending Review',
            self::ACTIVE => 'Active',
            self::RETIRED => 'Retired',
            self::ARCHIVED => 'Archived',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DRAFT => 'Policy is being drafted and not yet published',
            self::PENDING_REVIEW => 'Policy is awaiting legal review and approval',
            self::ACTIVE => 'Policy is currently active and binding',
            self::RETIRED => 'Policy has been retired and replaced',
            self::ARCHIVED => 'Policy is archived for historical reference',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING_REVIEW => 'yellow',
            self::ACTIVE => 'green',
            self::RETIRED => 'blue',
            self::ARCHIVED => 'purple',
        };
    }
}
