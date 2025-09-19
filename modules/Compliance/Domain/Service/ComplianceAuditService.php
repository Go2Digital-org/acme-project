<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\Service;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Compliance\Domain\Model\ComplianceAuditLog;
use Modules\Compliance\Domain\ValueObject\AuditEventType;
use Modules\Compliance\Domain\ValueObject\ComplianceStatus;

class ComplianceAuditService
{
    /**
     * @param  array<string, mixed>  $eventData
     */
    public function logEvent(
        AuditEventType $eventType,
        Model $auditable,
        ?Model $user = null,
        array $eventData = [],
        ?ComplianceStatus $complianceStatus = null
    ): ComplianceAuditLog {
        $log = ComplianceAuditLog::create([
            'event_type' => $eventType,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'user_id' => $user?->getKey(),
            'user_type' => $user instanceof Model ? $user::class : null,
            'compliance_status' => $complianceStatus ?? $this->determineComplianceStatus($eventType),
            'event_data' => $eventData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'metadata' => $this->gatherMetadata($auditable),
        ]);

        if ($eventType->isCritical()) {
            $this->handleCriticalEvent($log);
        }

        if ($eventType->requiresImmediateNotification()) {
            $this->sendNotification();
        }

        return $log;
    }

    /**
     * @param  array<string>  $accessedFields
     */
    public function logDataAccess(Model $auditable, ?Model $user = null, array $accessedFields = []): ComplianceAuditLog
    {
        return $this->logEvent(
            AuditEventType::DATA_ACCESS,
            $auditable,
            $user,
            [
                'accessed_fields' => $accessedFields,
                'access_method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['function'] ?? 'unknown',
                'timestamp' => now()->toISOString(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public function logDataModification(Model $auditable, array $changes, ?Model $user = null): ComplianceAuditLog
    {
        return $this->logEvent(
            AuditEventType::DATA_MODIFICATION,
            $auditable,
            $user,
            [
                'changes' => $changes,
                'modification_type' => $this->determineModificationType($changes),
                'timestamp' => now()->toISOString(),
            ]
        );
    }

    public function logDataDeletion(Model $auditable, ?Model $user = null, string $reason = ''): ComplianceAuditLog
    {
        return $this->logEvent(
            AuditEventType::DATA_DELETION,
            $auditable,
            $user,
            [
                'deletion_reason' => $reason,
                'model_data' => $auditable->toArray(),
                'timestamp' => now()->toISOString(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logSecurityIncident(
        string $incidentType,
        string $description,
        array $context = [],
        ?Model $user = null
    ): ComplianceAuditLog {
        // Create a dummy model for incident logging
        $incident = new class extends Model
        {
            protected $table = 'security_incidents';

            /** @var int */
            public $id = 1;

            public function getKey()
            {
                return 1;
            }
        };

        return $this->logEvent(
            AuditEventType::SECURITY_INCIDENT,
            $incident,
            $user,
            [
                'incident_type' => $incidentType,
                'description' => $description,
                'context' => $context,
                'severity' => $this->assessIncidentSeverity($incidentType),
                'timestamp' => now()->toISOString(),
            ],
            ComplianceStatus::NON_COMPLIANT
        );
    }

    /**
     * @param  array<string, mixed>  $requestData
     */
    public function logGdprRequest(
        string $requestType,
        Model $dataSubject,
        ?Model $user = null,
        array $requestData = []
    ): ComplianceAuditLog {
        $eventType = match ($requestType) {
            'data_export' => AuditEventType::GDPR_REQUEST_SUBMITTED,
            'data_deletion' => AuditEventType::GDPR_REQUEST_SUBMITTED,
            'consent_withdrawal' => AuditEventType::CONSENT_WITHDRAWN,
            default => AuditEventType::GDPR_REQUEST_SUBMITTED,
        };

        return $this->logEvent(
            $eventType,
            $dataSubject,
            $user,
            [
                'request_type' => $requestType,
                'request_data' => $requestData,
                'legal_basis' => 'GDPR Article 15-22',
                'timestamp' => now()->toISOString(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    public function generateAuditTrail(Model $auditable, array $dateRange = []): array
    {
        $startDate = $dateRange['start'] ?? now()->subMonth();
        $endDate = $dateRange['end'] ?? now();

        $logs = ComplianceAuditLog::where('auditable_type', $auditable::class)
            ->where('auditable_id', $auditable->getKey())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'auditable' => [
                'type' => $auditable::class,
                'id' => $auditable->getKey(),
            ],
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_events' => $logs->count(),
                'by_event_type' => $logs->groupBy('event_type')->map->count(),
                'by_compliance_status' => $logs->groupBy('compliance_status')->map->count(),
                'critical_events' => $logs->filter(fn ($log) => $log->event_type->isCritical())->count(),
                'pending_remediation' => $logs->filter(fn ($log) => $log->requiresRemediation())->count(),
            ],
            'events' => $logs->map(fn (ComplianceAuditLog $log): array => [
                'id' => $log->id,
                'event_type' => $log->event_type->value,
                'compliance_status' => $log->compliance_status->value,
                'event_data' => $log->event_data,
                'user_id' => $log->user_id,
                'created_at' => $log->created_at->toISOString(),
                'is_critical' => $log->event_type->isCritical(),
                'requires_remediation' => $log->requiresRemediation(),
            ])->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    public function getComplianceReport(array $dateRange = []): array
    {
        $startDate = $dateRange['start'] ?? now()->subMonth();
        $endDate = $dateRange['end'] ?? now();

        $logs = ComplianceAuditLog::whereBetween('created_at', [$startDate, $endDate])->get();

        $complianceRate = $this->calculateComplianceRate($logs);
        $riskAssessment = $this->assessOverallRisk($logs);

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'metrics' => [
                'total_events' => $logs->count(),
                'compliance_rate' => $complianceRate,
                'critical_incidents' => $logs->filter(fn ($log) => $log->event_type->isCritical())->count(),
                'remediation_rate' => $this->calculateRemediationRate($logs),
            ],
            'risk_assessment' => $riskAssessment,
            'recommendations' => $this->generateRecommendations($riskAssessment),
            'regulatory_summary' => [
                'gdpr_events' => $logs->filter(fn ($log) => $log->event_type->isGdprRelated())->count(),
                'pci_events' => $logs->filter(fn ($log) => $log->event_type->isPciRelated())->count(),
                'security_incidents' => $logs->where('event_type', AuditEventType::SECURITY_INCIDENT)->count(),
            ],
        ];
    }

    public function markRemediated(int $logId, string $action, ?string $officer = null): bool
    {
        $log = ComplianceAuditLog::find($logId);

        if (! $log || ! $log->requiresRemediation()) {
            return false;
        }

        $log->markRemediated($action);

        if ($officer) {
            $log->compliance_officer = $officer;
            $log->save();
        }

        return true;
    }

    private function determineComplianceStatus(AuditEventType $eventType): ComplianceStatus
    {
        return match (true) {
            $eventType->isCritical() => ComplianceStatus::NON_COMPLIANT,
            $eventType === AuditEventType::CONSENT_GIVEN => ComplianceStatus::COMPLIANT,
            $eventType === AuditEventType::GDPR_REQUEST_FULFILLED => ComplianceStatus::COMPLIANT,
            $eventType === AuditEventType::GDPR_REQUEST_SUBMITTED => ComplianceStatus::COMPLIANT,
            $eventType === AuditEventType::DATA_ACCESS => ComplianceStatus::COMPLIANT,
            $eventType === AuditEventType::DATA_MODIFICATION => ComplianceStatus::COMPLIANT,
            $eventType === AuditEventType::CONSENT_WITHDRAWN => ComplianceStatus::COMPLIANT,
            default => ComplianceStatus::COMPLIANT  // Default to compliant for standard operations
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherMetadata(Model $auditable): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'server_time' => now()->toISOString(),
            'model_table' => $auditable->getTable() ?? 'unknown',
            'event_context' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ];
    }

    private function handleCriticalEvent(ComplianceAuditLog $log): void
    {
        // Immediately assess risk and trigger alerts
        $riskAssessment = [
            'level' => 'high',
            'immediate_action_required' => true,
            'estimated_impact' => $this->estimateImpact($log),
            'recommended_actions' => $this->getCriticalEventActions($log),
        ];

        $log->addRiskAssessment($riskAssessment);
    }

    private function sendNotification(): void
    {
        // Implementation would send notifications to compliance team
        // This could integrate with email, Slack, SMS, etc.
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function determineModificationType(array $changes): string
    {
        $sensitiveFields = ['email', 'password', 'phone', 'address', 'payment_info'];
        $modifiedSensitive = array_intersect(array_keys($changes), $sensitiveFields);

        return $modifiedSensitive === [] ? 'non_sensitive' : 'sensitive';
    }

    private function assessIncidentSeverity(string $incidentType): string
    {
        return match ($incidentType) {
            'data_breach' => 'critical',
            'unauthorized_access' => 'high',
            'failed_login_attempts' => 'medium',
            default => 'low'
        };
    }

    /**
     * @param  Collection<int, ComplianceAuditLog>  $logs
     */
    private function calculateComplianceRate($logs): float
    {
        $total = $logs->count();
        if ($total === 0) {
            return 100.0;
        }

        $compliant = $logs->filter(fn (ComplianceAuditLog $log): bool => $log->isCompliant())->count();

        return round(($compliant / $total) * 100, 2);
    }

    /**
     * @param  Collection<int, ComplianceAuditLog>  $logs
     */
    private function calculateRemediationRate($logs): float
    {
        $needingRemediation = $logs->filter(fn (ComplianceAuditLog $log): bool => $log->compliance_status === ComplianceStatus::NON_COMPLIANT
        );

        if ($needingRemediation->count() === 0) {
            return 100.0;
        }

        $remediated = $needingRemediation->filter(fn (ComplianceAuditLog $log): bool => $log->remediated_at !== null
        );

        return round(($remediated->count() / $needingRemediation->count()) * 100, 2);
    }

    /**
     * @param  Collection<int, ComplianceAuditLog>  $logs
     * @return array<string, mixed>
     */
    private function assessOverallRisk($logs): array
    {
        $criticalEvents = $logs->filter(fn ($log) => $log->event_type->isCritical())->count();
        $pendingRemediation = $logs->filter(fn ($log): bool => $log->requiresRemediation())->count();
        $complianceRate = $this->calculateComplianceRate($logs);

        $riskLevel = match (true) {
            $criticalEvents > 0 || $complianceRate < 80 => 'high',
            $pendingRemediation > 5 || $complianceRate < 95 => 'medium',
            default => 'low'
        };

        return [
            'level' => $riskLevel,
            'factors' => [
                'critical_events' => $criticalEvents,
                'pending_remediation' => $pendingRemediation,
                'compliance_rate' => $complianceRate,
            ],
            'score' => $this->calculateRiskScore($criticalEvents, $pendingRemediation, $complianceRate),
        ];
    }

    private function calculateRiskScore(int $criticalEvents, int $pendingRemediation, float $complianceRate): int
    {
        $score = 100;
        $score -= ($criticalEvents * 20);
        $score -= ($pendingRemediation * 5);
        $score -= ((100 - $complianceRate) * 2);

        return (int) max(0, min(100, $score));
    }

    /**
     * @param  array<string, mixed>  $riskAssessment
     * @return list<string>
     */
    private function generateRecommendations(array $riskAssessment): array
    {
        $recommendations = [];

        if ($riskAssessment['level'] === 'high') {
            $recommendations[] = 'Immediate action required: Address critical compliance issues';
        }

        if ($riskAssessment['factors']['critical_events'] > 0) {
            $recommendations[] = 'Investigate and remediate all critical security incidents';
        }

        if ($riskAssessment['factors']['compliance_rate'] < 95) {
            $recommendations[] = 'Improve compliance procedures and staff training';
        }

        if ($riskAssessment['factors']['pending_remediation'] > 3) {
            $recommendations[] = 'Accelerate remediation efforts for non-compliant events';
        }

        return $recommendations;
    }

    private function estimateImpact(ComplianceAuditLog $log): string
    {
        return match ($log->event_type) {
            AuditEventType::DATA_BREACH => 'severe',
            AuditEventType::SECURITY_INCIDENT => 'high',
            AuditEventType::UNAUTHORIZED_ACCESS => 'medium',
            default => 'low'
        };
    }

    /**
     * @return list<string>
     */
    private function getCriticalEventActions(ComplianceAuditLog $log): array
    {
        return match ($log->event_type) {
            AuditEventType::DATA_BREACH => [
                'Isolate affected systems',
                'Notify regulatory authorities within 72 hours',
                'Conduct forensic investigation',
                'Prepare breach notification for data subjects',
            ],
            AuditEventType::SECURITY_INCIDENT => [
                'Secure the compromised system',
                'Change all relevant passwords',
                'Review access logs',
                'Update security measures',
            ],
            default => [
                'Review the incident',
                'Take appropriate corrective action',
                'Document lessons learned',
            ]
        };
    }
}
