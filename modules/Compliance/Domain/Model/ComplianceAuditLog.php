<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Compliance\Domain\ValueObject\AuditEventType;
use Modules\Compliance\Domain\ValueObject\ComplianceStatus;

/**
 * Compliance Audit Log Model
 *
 * @property int $id
 * @property AuditEventType $event_type
 * @property string $auditable_type
 * @property int $auditable_id
 * @property int|null $user_id
 * @property string|null $user_type
 * @property ComplianceStatus $compliance_status
 * @property array<string, mixed> $event_data
 * @property array<string, mixed>|null $risk_assessment
 * @property string|null $compliance_officer
 * @property string|null $remediation_action
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $session_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $remediated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ComplianceAuditLog extends Model
{
    protected $table = 'compliance_audit_logs';

    protected $fillable = [
        'event_type',
        'auditable_type',
        'auditable_id',
        'user_id',
        'user_type',
        'compliance_status',
        'event_data',
        'risk_assessment',
        'compliance_officer',
        'remediation_action',
        'ip_address',
        'user_agent',
        'session_id',
        'metadata',
        'remediated_at',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }

    public function isCompliant(): bool
    {
        return $this->compliance_status === ComplianceStatus::COMPLIANT ||
               $this->compliance_status === ComplianceStatus::REMEDIATED;
    }

    public function requiresRemediation(): bool
    {
        return $this->compliance_status === ComplianceStatus::NON_COMPLIANT &&
               $this->remediated_at === null;
    }

    public function isHighRisk(): bool
    {
        if (! $this->risk_assessment) {
            return false;
        }

        return ($this->risk_assessment['level'] ?? 'low') === 'high';
    }

    public function markRemediated(string $action): void
    {
        $this->remediation_action = $action;
        $this->remediated_at = now();
        $this->compliance_status = ComplianceStatus::REMEDIATED;
        $this->save();
    }

    public function updateComplianceStatus(ComplianceStatus $status): void
    {
        $this->compliance_status = $status;
        $this->save();
    }

    /**
     * @param  array<string, mixed>  $assessment
     */
    public function addRiskAssessment(array $assessment): void
    {
        $this->risk_assessment = $assessment;
        $this->save();
    }

    public function getTimeSinceCreation(): int
    {
        return (int) $this->created_at->diffInMinutes(now());
    }

    public function isOverdue(): bool
    {
        if ($this->compliance_status === ComplianceStatus::COMPLIANT) {
            return false;
        }

        $maxRemediationTime = match ($this->event_type) {
            AuditEventType::SECURITY_INCIDENT => 60, // 1 hour
            AuditEventType::DATA_BREACH => 30,       // 30 minutes
            AuditEventType::UNAUTHORIZED_ACCESS => 120, // 2 hours
            default => 1440 // 24 hours
        };

        return $this->getTimeSinceCreation() > $maxRemediationTime;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => AuditEventType::class,
            'compliance_status' => ComplianceStatus::class,
            'event_data' => 'array',
            'risk_assessment' => 'array',
            'metadata' => 'array',
            'remediated_at' => 'datetime',
        ];
    }
}
