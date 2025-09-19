<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\ValueObject;

enum DataProcessingPurpose: string
{
    case DONATION_PROCESSING = 'donation_processing';
    case USER_AUTHENTICATION = 'user_authentication';
    case MARKETING_COMMUNICATIONS = 'marketing_communications';
    case ANALYTICS_TRACKING = 'analytics_tracking';
    case FRAUD_PREVENTION = 'fraud_prevention';
    case LEGAL_COMPLIANCE = 'legal_compliance';
    case SERVICE_PROVISION = 'service_provision';
    case CUSTOMER_SUPPORT = 'customer_support';
    case AUDIT_TRAIL = 'audit_trail';

    public function requiresExplicitConsent(): bool
    {
        return match ($this) {
            self::MARKETING_COMMUNICATIONS => true,
            self::ANALYTICS_TRACKING => true,
            default => false
        };
    }

    public function hasLegitimateInterest(): bool
    {
        return match ($this) {
            self::FRAUD_PREVENTION => true,
            self::LEGAL_COMPLIANCE => true,
            self::SERVICE_PROVISION => true,
            self::AUDIT_TRAIL => true,
            default => false
        };
    }

    public function isEssential(): bool
    {
        return match ($this) {
            self::DONATION_PROCESSING => true,
            self::USER_AUTHENTICATION => true,
            self::LEGAL_COMPLIANCE => true,
            self::FRAUD_PREVENTION => true,
            default => false
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DONATION_PROCESSING => 'Processing donations and payments',
            self::USER_AUTHENTICATION => 'User authentication and account management',
            self::MARKETING_COMMUNICATIONS => 'Sending marketing emails and communications',
            self::ANALYTICS_TRACKING => 'Website analytics and usage tracking',
            self::FRAUD_PREVENTION => 'Fraud detection and prevention',
            self::LEGAL_COMPLIANCE => 'Legal and regulatory compliance',
            self::SERVICE_PROVISION => 'Providing core platform services',
            self::CUSTOMER_SUPPORT => 'Customer support and help desk',
            self::AUDIT_TRAIL => 'Audit logging and security monitoring',
        };
    }

    public function legalBasis(): string
    {
        return match ($this) {
            self::DONATION_PROCESSING => 'Contract performance',
            self::USER_AUTHENTICATION => 'Contract performance',
            self::MARKETING_COMMUNICATIONS => 'Consent',
            self::ANALYTICS_TRACKING => 'Consent',
            self::FRAUD_PREVENTION => 'Legitimate interest',
            self::LEGAL_COMPLIANCE => 'Legal obligation',
            self::SERVICE_PROVISION => 'Contract performance',
            self::CUSTOMER_SUPPORT => 'Contract performance',
            self::AUDIT_TRAIL => 'Legitimate interest',
        };
    }
}
