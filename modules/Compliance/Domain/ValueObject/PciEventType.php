<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\ValueObject;

enum PciEventType: string
{
    case PAYMENT_PROCESSING = 'payment_processing';
    case CARD_DATA_ACCESS = 'card_data_access';
    case CARD_DATA_STORAGE = 'card_data_storage';
    case CARD_DATA_TRANSMISSION = 'card_data_transmission';
    case CARD_DATA_DELETION = 'card_data_deletion';
    case VULNERABILITY_SCAN = 'vulnerability_scan';
    case PENETRATION_TEST = 'penetration_test';
    case SECURITY_INCIDENT = 'security_incident';
    case COMPLIANCE_AUDIT = 'compliance_audit';
    case ACCESS_CONTROL_CHANGE = 'access_control_change';

    public function isHighRisk(): bool
    {
        return match ($this) {
            self::CARD_DATA_ACCESS => true,
            self::CARD_DATA_STORAGE => true,
            self::SECURITY_INCIDENT => true,
            default => false
        };
    }

    public function requiresLogging(): bool
    {
        return true; // All PCI events must be logged
    }

    public function requiresRealTimeMonitoring(): bool
    {
        return match ($this) {
            self::SECURITY_INCIDENT => true,
            self::CARD_DATA_ACCESS => true,
            self::ACCESS_CONTROL_CHANGE => true,
            default => false
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PAYMENT_PROCESSING => 'Payment card processing activity',
            self::CARD_DATA_ACCESS => 'Access to cardholder data',
            self::CARD_DATA_STORAGE => 'Storage of cardholder data',
            self::CARD_DATA_TRANSMISSION => 'Transmission of cardholder data',
            self::CARD_DATA_DELETION => 'Deletion of cardholder data',
            self::VULNERABILITY_SCAN => 'Vulnerability scanning activity',
            self::PENETRATION_TEST => 'Penetration testing activity',
            self::SECURITY_INCIDENT => 'Security incident or breach',
            self::COMPLIANCE_AUDIT => 'PCI compliance audit',
            self::ACCESS_CONTROL_CHANGE => 'Access control modification',
        };
    }

    public function retentionPeriod(): string
    {
        return match ($this) {
            self::SECURITY_INCIDENT => '7 years',
            self::COMPLIANCE_AUDIT => '3 years',
            default => '1 year'
        };
    }
}
