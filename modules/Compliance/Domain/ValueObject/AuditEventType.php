<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\ValueObject;

enum AuditEventType: string
{
    case DATA_ACCESS = 'data_access';
    case DATA_MODIFICATION = 'data_modification';
    case DATA_DELETION = 'data_deletion';
    case DATA_EXPORT = 'data_export';
    case USER_LOGIN = 'user_login';
    case USER_LOGOUT = 'user_logout';
    case PERMISSION_CHANGE = 'permission_change';
    case SECURITY_INCIDENT = 'security_incident';
    case DATA_BREACH = 'data_breach';
    case UNAUTHORIZED_ACCESS = 'unauthorized_access';
    case FAILED_LOGIN = 'failed_login';
    case CONSENT_GIVEN = 'consent_given';
    case CONSENT_WITHDRAWN = 'consent_withdrawn';
    case DATA_RETENTION_POLICY_APPLIED = 'data_retention_policy_applied';
    case GDPR_REQUEST_SUBMITTED = 'gdpr_request_submitted';
    case GDPR_REQUEST_FULFILLED = 'gdpr_request_fulfilled';
    case PCI_TRANSACTION = 'pci_transaction';
    case COMPLIANCE_VIOLATION = 'compliance_violation';

    public function isCritical(): bool
    {
        return match ($this) {
            self::DATA_BREACH => true,
            self::SECURITY_INCIDENT => true,
            self::UNAUTHORIZED_ACCESS => true,
            self::COMPLIANCE_VIOLATION => true,
            default => false
        };
    }

    public function isGdprRelated(): bool
    {
        return match ($this) {
            self::CONSENT_GIVEN => true,
            self::CONSENT_WITHDRAWN => true,
            self::GDPR_REQUEST_SUBMITTED => true,
            self::GDPR_REQUEST_FULFILLED => true,
            self::DATA_EXPORT => true,
            self::DATA_DELETION => true,
            default => false
        };
    }

    public function isPciRelated(): bool
    {
        return match ($this) {
            self::PCI_TRANSACTION => true,
            default => false
        };
    }

    public function requiresImmediateNotification(): bool
    {
        return match ($this) {
            self::DATA_BREACH => true,
            self::SECURITY_INCIDENT => true,
            self::UNAUTHORIZED_ACCESS => true,
            default => false
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DATA_ACCESS => 'Access to personal or sensitive data',
            self::DATA_MODIFICATION => 'Modification of personal or sensitive data',
            self::DATA_DELETION => 'Deletion of personal or sensitive data',
            self::DATA_EXPORT => 'Export of personal or sensitive data',
            self::USER_LOGIN => 'User authentication and login',
            self::USER_LOGOUT => 'User logout and session termination',
            self::PERMISSION_CHANGE => 'Change in user permissions or access rights',
            self::SECURITY_INCIDENT => 'Security incident or breach attempt',
            self::DATA_BREACH => 'Confirmed data breach',
            self::UNAUTHORIZED_ACCESS => 'Unauthorized access attempt',
            self::FAILED_LOGIN => 'Failed login attempt',
            self::CONSENT_GIVEN => 'GDPR consent granted by data subject',
            self::CONSENT_WITHDRAWN => 'GDPR consent withdrawn by data subject',
            self::DATA_RETENTION_POLICY_APPLIED => 'Data retention policy enforcement',
            self::GDPR_REQUEST_SUBMITTED => 'GDPR data subject request submitted',
            self::GDPR_REQUEST_FULFILLED => 'GDPR data subject request completed',
            self::PCI_TRANSACTION => 'PCI DSS related transaction',
            self::COMPLIANCE_VIOLATION => 'Regulatory compliance violation',
        };
    }

    public function retentionPeriod(): string
    {
        return match ($this) {
            self::DATA_BREACH => '10 years',
            self::SECURITY_INCIDENT => '7 years',
            self::GDPR_REQUEST_SUBMITTED => '6 years',
            self::GDPR_REQUEST_FULFILLED => '6 years',
            self::PCI_TRANSACTION => '1 year',
            default => '3 years'
        };
    }

    public function riskLevel(): string
    {
        return match ($this) {
            self::DATA_BREACH => 'critical',
            self::SECURITY_INCIDENT => 'high',
            self::UNAUTHORIZED_ACCESS => 'high',
            self::COMPLIANCE_VIOLATION => 'medium',
            self::DATA_DELETION => 'medium',
            self::FAILED_LOGIN => 'low',
            default => 'low'
        };
    }
}
