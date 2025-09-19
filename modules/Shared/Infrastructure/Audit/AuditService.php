<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Audit;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Shared\Domain\Audit\CampaignAuditableInterface;
use Modules\Shared\Domain\Audit\DonationAuditableInterface;
use Modules\Shared\Domain\Audit\EmployeeAuditableInterface;
use Modules\Shared\Domain\Audit\OrganizationAuditableInterface;
use Modules\Shared\Infrastructure\Audit\Models\AuditLog;
use Modules\User\Infrastructure\Laravel\Models\User;
use RuntimeException;

class AuditService
{
    /**
     * Log an admin action.
     */
    /**
     * @param  array<string, mixed>  $old_values
     * @param  array<string, mixed>  $new_values
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $action,
        string $entity_type,
        ?int $entity_id = null,
        array $old_values = [],
        array $new_values = [],
        array $metadata = [],
        ?User $user = null,
    ): AuditLog {
        $user ??= Auth::user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user->name ?? 'System',
            'user_email' => $user->email ?? 'system@acme-corp.com',
            'user_role' => $user->role ?? 'system',
            'action' => $action,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'old_values' => $old_values,
            'new_values' => $new_values,
            'metadata' => array_merge($metadata, [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'timestamp' => now()->toISOString(),
            ]),
            'performed_at' => now(),
        ]);
    }

    /**
     * Log campaign actions.
     */
    /**
     * @param  array<string, mixed>  $old_values
     * @param  array<string, mixed>  $new_values
     */
    public function logCampaignAction(string $action, CampaignAuditableInterface $campaign, array $old_values = [], array $new_values = []): AuditLog
    {
        return $this->log(
            action: "campaign.{$action}",
            entity_type: $campaign->getAuditableType(),
            entity_id: $campaign->getAuditableId(),
            old_values: $old_values,
            new_values: $new_values,
            metadata: array_merge([
                'campaign_title' => $campaign->getCampaignTitle(),
                'campaign_status' => $campaign->getCampaignStatus(),
                'organization_id' => $campaign->getCampaignOrganizationId(),
            ], $campaign->getAuditableData()),
        );
    }

    /**
     * Log donation actions.
     */
    /**
     * @param  array<string, mixed>  $old_values
     * @param  array<string, mixed>  $new_values
     */
    public function logDonationAction(string $action, DonationAuditableInterface $donation, array $old_values = [], array $new_values = []): AuditLog
    {
        return $this->log(
            action: "donation.{$action}",
            entity_type: $donation->getAuditableType(),
            entity_id: $donation->getAuditableId(),
            old_values: $old_values,
            new_values: $new_values,
            metadata: array_merge([
                'donation_amount' => $donation->getDonationAmount(),
                'donation_status' => $donation->getDonationStatus(),
                'campaign_id' => $donation->getDonationCampaignId(),
                'user_id' => $donation->getDonationEmployeeId(),
                'is_anonymous' => $donation->isDonationAnonymous(),
            ], $donation->getAuditableData()),
        );
    }

    /**
     * Log organization actions.
     */
    /**
     * @param  array<string, mixed>  $old_values
     * @param  array<string, mixed>  $new_values
     */
    public function logOrganizationAction(string $action, OrganizationAuditableInterface $organization, array $old_values = [], array $new_values = []): AuditLog
    {
        return $this->log(
            action: "organization.{$action}",
            entity_type: $organization->getAuditableType(),
            entity_id: $organization->getAuditableId(),
            old_values: $old_values,
            new_values: $new_values,
            metadata: array_merge([
                'organization_name' => $organization->getOrganizationName(),
                'organization_category' => $organization->getOrganizationCategory(),
                'is_verified' => $organization->isOrganizationVerified(),
                'is_active' => $organization->isOrganizationActive(),
            ], $organization->getAuditableData()),
        );
    }

    /**
     * Log employee actions.
     */
    /**
     * @param  array<string, mixed>  $old_values
     * @param  array<string, mixed>  $new_values
     */
    public function logEmployeeAction(string $action, EmployeeAuditableInterface $employee, array $old_values = [], array $new_values = []): AuditLog
    {
        return $this->log(
            action: "employee.{$action}",
            entity_type: $employee->getAuditableType(),
            entity_id: $employee->getAuditableId(),
            old_values: $old_values,
            new_values: $new_values,
            metadata: array_merge([
                'employee_name' => $employee->getEmployeeName(),
                'employee_email' => $employee->getEmployeeEmail(),
                'employee_role' => $employee->getEmployeeRole(),
            ], $employee->getAuditableData()),
        );
    }

    /**
     * Log system actions.
     */
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logSystemAction(string $action, array $metadata = []): AuditLog
    {
        return $this->log(
            action: "system.{$action}",
            entity_type: 'system',
            metadata: $metadata,
        );
    }

    /**
     * Log bulk actions.
     */
    /**
     * @param  array<string, mixed>  $entity_ids
     * @param  array<string, mixed>  $metadata
     */
    public function logBulkAction(string $action, string $entity_type, array $entity_ids, array $metadata = []): AuditLog
    {
        return $this->log(
            action: "bulk.{$action}",
            entity_type: $entity_type,
            metadata: array_merge($metadata, [
                'entity_ids' => $entity_ids,
                'entity_count' => count($entity_ids),
            ]),
        );
    }

    /**
     * Log security events.
     */
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logSecurityEvent(string $event, array $metadata = []): AuditLog
    {
        return $this->log(
            action: "security.{$event}",
            entity_type: 'security',
            metadata: array_merge($metadata, [
                'severity' => $this->getSecurityEventSeverity($event),
                'requires_review' => $this->securityEventRequiresReview($event),
            ]),
        );
    }

    /**
     * Get audit trail for an entity.
     *
     * @return Collection<int, AuditLog>
     */
    public function getAuditTrail(string $entity_type, int $entity_id): Collection
    {
        return AuditLog::where('entity_type', $entity_type)
            ->where('entity_id', $entity_id)
            ->orderBy('performed_at', 'desc')
            ->get();
    }

    /**
     * Get recent audit logs.
     *
     * @return Collection<int, AuditLog>
     */
    public function getRecentLogs(int $limit = 50): Collection
    {
        return AuditLog::orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs by user.
     *
     * @return Collection<int, AuditLog>
     */
    public function getLogsByUser(int $user_id, int $limit = 100): Collection
    {
        return AuditLog::where('user_id', $user_id)
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get suspicious activities.
     *
     * @return Collection<int, AuditLog>
     */
    public function getSuspiciousActivities(int $hours = 24): Collection
    {
        $startTime = now()->subHours($hours);

        // Define suspicious patterns
        $suspiciousActions = [
            'employee.role_changed',
            'organization.verified',
            'organization.unverified',
            'bulk.delete',
            'donation.refund',
            'campaign.delete',
            'security.failed_login_multiple',
            'security.permission_denied',
        ];

        return AuditLog::where('performed_at', '>=', $startTime)
            ->whereIn('action', $suspiciousActions)
            ->orWhere('metadata->severity', 'high')
            ->orderBy('performed_at', 'desc')
            ->get();
    }

    /**
     * Generate compliance report.
     */
    /**
     * @return array<string, mixed>
     */
    public function generateComplianceReport(Carbon $from, Carbon $to): array
    {
        $logs = AuditLog::whereBetween('performed_at', [$from, $to])
            ->get();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'total_actions' => $logs->count(),
                'unique_users' => $logs->unique('user_id')->count(),
                'entities_affected' => $logs->whereNotNull('entity_id')->unique(fn (AuditLog $log): string => $log->entity_type . '-' . $log->entity_id)->count(),
            ],
            'actions_by_type' => $logs->groupBy('action')
                ->map(fn ($group) => $group->count())
                ->sortDesc()
                ->toArray(),
            'users_by_activity' => $logs->groupBy('user_name')
                ->map(fn ($group) => $group->count())
                ->sortDesc()
                ->take(10)
                ->toArray(),
            'high_risk_actions' => $logs->where('metadata.severity', 'high')
                ->values()
                ->toArray(),
            'failed_actions' => $logs->where('metadata.success', false)
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Clean old audit logs.
     */
    public function cleanOldLogs(int $keepDays = 365): int
    {
        $cutoffDate = now()->subDays($keepDays);

        return AuditLog::where('performed_at', '<', $cutoffDate)->delete();
    }

    /**
     * Export audit logs.
     */
    public function exportLogs(Carbon $from, Carbon $to, string $format = 'csv'): string
    {
        $logs = AuditLog::whereBetween('performed_at', [$from, $to])
            ->orderBy('performed_at', 'desc')
            ->get();

        $filename = "audit_logs_{$from->format('Y-m-d')}_to_{$to->format('Y-m-d')}.{$format}";
        $path = storage_path("app/exports/{$filename}");

        if ($format === 'csv') {
            $this->exportToCsv($logs, $path);
        } else {
            $this->exportToJson($logs, $path);
        }

        return $path;
    }

    private function getSecurityEventSeverity(string $event): string
    {
        return match ($event) {
            'failed_login_multiple', 'unauthorized_access', 'permission_escalation' => 'high',
            'failed_login', 'permission_denied', 'suspicious_activity' => 'medium',
            'login_success', 'logout', 'password_changed' => 'low',
            default => 'medium',
        };
    }

    private function securityEventRequiresReview(string $event): bool
    {
        return in_array($event, [
            'failed_login_multiple',
            'unauthorized_access',
            'permission_escalation',
            'data_export_large',
            'bulk_delete_large',
        ], true);
    }

    /**
     * @param  Collection<int, AuditLog>  $logs
     */
    private function exportToCsv(Collection $logs, string $path): void
    {
        $file = fopen($path, 'w');

        if ($file === false) {
            throw new RuntimeException('Cannot create export file');
        }

        // CSV headers
        fputcsv($file, [
            'ID',
            'Timestamp',
            'User',
            'Email',
            'Role',
            'Action',
            'Entity Type',
            'Entity ID',
            'IP Address',
            'User Agent',
            'URL',
            'Method',
            'Success',
            'Old Values',
            'New Values',
            'Metadata',
        ]);

        // CSV data
        foreach ($logs as $log) {
            fputcsv($file, [
                $log->id,
                $log->performed_at->toISOString(),
                $log->user_name,
                $log->user_email,
                $log->user_role,
                $log->action,
                $log->entity_type,
                $log->entity_id,
                $log->metadata['ip_address'] ?? '',
                $log->metadata['user_agent'] ?? '',
                $log->metadata['url'] ?? '',
                $log->metadata['method'] ?? '',
                $log->metadata['success'] ?? 'true',
                json_encode($log->old_values),
                json_encode($log->new_values),
                json_encode($log->metadata),
            ]);
        }

        fclose($file);
    }

    /**
     * @param  Collection<int, AuditLog>  $logs
     */
    private function exportToJson(Collection $logs, string $path): void
    {
        file_put_contents($path, $logs->toJson(JSON_PRETTY_PRINT));
    }
}
