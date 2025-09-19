<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\ValueObject;

enum ComplianceStatus: string
{
    case COMPLIANT = 'compliant';
    case NON_COMPLIANT = 'non_compliant';
    case PENDING_REVIEW = 'pending_review';
    case REMEDIATED = 'remediated';
    case UNDER_INVESTIGATION = 'under_investigation';
    case EXEMPTED = 'exempted';

    public function isActionRequired(): bool
    {
        return match ($this) {
            self::NON_COMPLIANT => true,
            self::PENDING_REVIEW => true,
            self::UNDER_INVESTIGATION => true,
            default => false
        };
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::COMPLIANT => true,
            self::REMEDIATED => true,
            self::EXEMPTED => true,
            default => false
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::COMPLIANT => 'green',
            self::NON_COMPLIANT => 'red',
            self::PENDING_REVIEW => 'yellow',
            self::REMEDIATED => 'blue',
            self::UNDER_INVESTIGATION => 'orange',
            self::EXEMPTED => 'gray',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::COMPLIANT => 'Compliant',
            self::NON_COMPLIANT => 'Non-Compliant',
            self::PENDING_REVIEW => 'Pending Review',
            self::REMEDIATED => 'Remediated',
            self::UNDER_INVESTIGATION => 'Under Investigation',
            self::EXEMPTED => 'Exempted',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::COMPLIANT => 'Meets all regulatory requirements',
            self::NON_COMPLIANT => 'Violates regulatory requirements',
            self::PENDING_REVIEW => 'Awaiting compliance review',
            self::REMEDIATED => 'Issues resolved and remediated',
            self::UNDER_INVESTIGATION => 'Under investigation for compliance',
            self::EXEMPTED => 'Exempt from compliance requirements',
        };
    }
}
