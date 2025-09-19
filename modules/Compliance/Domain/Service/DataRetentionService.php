<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\Service;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Compliance\Domain\Model\ComplianceAuditLog;
use Modules\Compliance\Domain\Model\DataRetentionPolicy;
use Modules\Compliance\Domain\ValueObject\AuditEventType;
use Modules\Compliance\Domain\ValueObject\DataProcessingPurpose;
use Modules\Compliance\Domain\ValueObject\RetentionAction;
use Modules\Donation\Domain\Model\Donation;

class DataRetentionService
{
    public function __construct(
        private readonly ComplianceAuditService $auditService
    ) {}

    public function createRetentionPolicy(
        string $policyName,
        string $dataCategory,
        DataProcessingPurpose $purpose,
        string $retentionPeriod,
        RetentionAction $action,
        string $legalBasis,
        ?int $createdBy = null
    ): DataRetentionPolicy {
        $policy = DataRetentionPolicy::create([
            'policy_name' => $policyName,
            'data_category' => $dataCategory,
            'purpose' => $purpose,
            'retention_period' => $retentionPeriod,
            'retention_action' => $action,
            'legal_basis' => $legalBasis,
            'is_active' => false, // Requires activation
            'created_by' => $createdBy,
        ]);

        $this->auditService->logEvent(
            AuditEventType::DATA_RETENTION_POLICY_APPLIED,
            $policy,
            null,
            [
                'action' => 'policy_created',
                'policy_name' => $policyName,
                'data_category' => $dataCategory,
                'retention_period' => $retentionPeriod,
            ]
        );

        return $policy;
    }

    public function activatePolicy(int $policyId, ?int $approvedBy = null): bool
    {
        $policy = DataRetentionPolicy::find($policyId);

        if (! $policy) {
            return false;
        }

        $policy->approved_by = $approvedBy;
        $policy->activate();

        $this->auditService->logEvent(
            AuditEventType::DATA_RETENTION_POLICY_APPLIED,
            $policy,
            null,
            [
                'action' => 'policy_activated',
                'approved_by' => $approvedBy,
            ]
        );

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function applyRetentionPolicies(): array
    {
        $activePolicies = DataRetentionPolicy::where('is_active', true)->get();
        $results = [];

        foreach ($activePolicies as $policy) {
            $result = $this->applyPolicy($policy);
            $results[$policy->id] = $result;
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    public function applyPolicy(DataRetentionPolicy $policy): array
    {
        if (! $policy->isEffective()) {
            return [
                'status' => 'skipped',
                'reason' => 'Policy not effective',
                'processed_count' => 0,
            ];
        }

        $eligibleData = $this->findEligibleData();
        $processedCount = 0;
        $errors = [];

        foreach ($eligibleData as $data) {
            try {
                $this->processDataItem($data, $policy);
                $processedCount++;
            } catch (Exception $e) {
                $errors[] = [
                    'data_id' => $data->getKey(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->auditService->logEvent(
            AuditEventType::DATA_RETENTION_POLICY_APPLIED,
            $policy,
            null,
            [
                'action' => 'policy_applied',
                'processed_count' => $processedCount,
                'eligible_count' => $eligibleData->count(),
                'errors' => $errors,
            ]
        );

        return [
            'status' => 'completed',
            'processed_count' => $processedCount,
            'eligible_count' => $eligibleData->count(),
            'errors' => $errors,
        ];
    }

    public function scheduleDataDeletion(Model $data, DataRetentionPolicy $policy, ?string $reason = null): bool
    {
        if (! $policy->shouldDeleteData($data->getAttribute('created_at'))) {
            return false;
        }

        // Mark for deletion (could use a job queue)
        $this->auditService->logEvent(
            AuditEventType::DATA_DELETION,
            $data,
            null,
            [
                'scheduled_for_deletion' => true,
                'policy_id' => $policy->id,
                'reason' => $reason ?? 'Retention policy expired',
                'retention_period' => $policy->retention_period,
            ]
        );

        return true;
    }

    /**
     * @param  array<string, mixed>  $anonymizationRules
     */
    public function anonymizeData(Model $data, array $anonymizationRules): bool
    {
        foreach ($anonymizationRules as $field => $rule) {
            $value = $data->getAttribute($field);
            if ($value === null) {
                continue;
            }

            $anonymizedValue = $this->applyAnonymizationRule($value, $rule);
            $data->setAttribute($field, $anonymizedValue);
        }

        $data->save();

        $this->auditService->logEvent(
            AuditEventType::DATA_MODIFICATION,
            $data,
            null,
            [
                'action' => 'anonymization',
                'anonymized_fields' => array_keys($anonymizationRules),
            ]
        );

        return true;
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    public function getRetentionReport(array $dateRange = []): array
    {
        $startDate = $dateRange['start'] ?? now()->subMonth();
        $endDate = $dateRange['end'] ?? now();

        $policies = DataRetentionPolicy::where('is_active', true)->get();
        $auditLogs = ComplianceAuditLog::where('event_type', AuditEventType::DATA_RETENTION_POLICY_APPLIED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'active_policies' => $policies->count(),
                'policy_applications' => $auditLogs->count(),
                'data_processed' => $auditLogs->sum(fn ($log) => $log->event_data['processed_count'] ?? 0),
                'errors' => $auditLogs->sum(fn ($log): int => count($log->event_data['errors'] ?? [])),
            ],
            'policies' => $policies->map(fn (DataRetentionPolicy $policy): array => [
                'id' => $policy->id,
                'name' => $policy->policy_name,
                'data_category' => $policy->data_category,
                'retention_period' => $policy->retention_period,
                'action' => $policy->retention_action->value,
                'is_effective' => $policy->isEffective(),
                'next_application' => $this->calculateNextApplication(),
            ])->toArray(),
            'recommendations' => $this->generateRetentionRecommendations($policies, $auditLogs),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assessDataForRetention(Model $data): array
    {
        $applicablePolicies = $this->findApplicablePolicies($data);
        $assessment = [];

        foreach ($applicablePolicies as $policy) {
            $retentionDate = $policy->calculateRetentionDate($data->getAttribute('created_at'));
            $daysUntilAction = (int) now()->diffInDays($retentionDate, false);

            $assessment[] = [
                'policy' => [
                    'id' => $policy->id,
                    'name' => $policy->policy_name,
                    'action' => $policy->retention_action->value,
                ],
                'retention_date' => $retentionDate->toDateString(),
                'days_until_action' => $daysUntilAction,
                'action_required' => $daysUntilAction <= 0,
                'recommended_action' => $this->getRecommendedAction($policy, $daysUntilAction),
            ];
        }

        return [
            'data_id' => $data->getKey(),
            'data_type' => $data::class,
            'assessments' => $assessment,
            'total_policies' => count($assessment),
        ];
    }

    /**
     * @return Collection<int, Model>
     */
    private function findEligibleData(): Collection
    {
        // This is a simplified implementation
        // In practice, you'd query specific models based on the data category
        return collect();
    }

    private function processDataItem(Model $data, DataRetentionPolicy $policy): void
    {
        if ($policy->shouldDeleteData($data->getAttribute('created_at'))) {
            if ($policy->retention_action === RetentionAction::DELETE) {
                $this->scheduleDataDeletion($data, $policy);
            } elseif ($policy->retention_action === RetentionAction::ANONYMIZE) {
                $this->anonymizeData($data, $policy->getAnonymizationRules());
            }
        }
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function applyAnonymizationRule(mixed $value, array $rule): string
    {
        return match ($rule['type']) {
            'hash' => hash('sha256', (string) $value),
            'mask' => str_repeat('*', strlen((string) $value)),
            'pseudonymize' => 'USER_' . hash('crc32', (string) $value),
            'generalize' => $this->generalizeValue($value, $rule),
            default => 'ANONYMIZED'
        };
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function generalizeValue(mixed $value, array $rule): string
    {
        // Implement generalization logic based on rule parameters
        if (is_numeric($value)) {
            $range = $rule['range'] ?? 10;
            $bucket = floor($value / $range) * $range;

            return "{$bucket}-" . ($bucket + $range - 1);
        }

        return 'GENERALIZED';
    }

    /**
     * @return Collection<int, DataRetentionPolicy>
     */
    private function findApplicablePolicies(Model $data): Collection
    {
        // Find policies that apply to this type of data
        $dataCategory = $this->determineDataCategory($data);

        return DataRetentionPolicy::where('is_active', true)
            ->where('data_category', $dataCategory)
            ->get();
    }

    private function determineDataCategory(Model $data): string
    {
        // Determine data category based on model type
        return match ($data::class) {
            'App\\Models\\User' => 'user_data',
            Donation::class => 'donation_data',
            Campaign::class => 'campaign_data',
            default => 'general_data'
        };
    }

    private function calculateNextApplication(): Carbon
    {
        // Calculate when this policy should next be applied
        // This could be based on a schedule (daily, weekly, monthly)
        return now()->addDay();
        // Simplified - run daily
    }

    /**
     * @param  Collection<int, DataRetentionPolicy>  $policies
     * @param  Collection<int, ComplianceAuditLog>  $auditLogs
     * @return list<string>
     */
    private function generateRetentionRecommendations(Collection $policies, Collection $auditLogs): array
    {
        $recommendations = [];

        // Check for policies with high error rates
        $policiesWithErrors = $auditLogs->filter(fn ($log): bool => ! empty($log->event_data['errors'] ?? []))
            ->groupBy(fn ($log) => $log->auditable_id);

        if ($policiesWithErrors->isNotEmpty()) {
            $recommendations[] = 'Review policies with processing errors and update deletion criteria';
        }

        // Check for inactive policies
        $inactivePolicies = DataRetentionPolicy::where('is_active', false)->count();
        if ($inactivePolicies > 0) {
            $recommendations[] = "Activate {$inactivePolicies} pending retention policies";
        }

        // Check for missing policies for common data types
        $commonDataTypes = ['user_data', 'donation_data', 'campaign_data', 'audit_data'];
        $coveredTypes = $policies->pluck('data_category')->unique();
        $missingTypes = array_diff($commonDataTypes, $coveredTypes->toArray());

        if ($missingTypes !== []) {
            $recommendations[] = 'Create retention policies for: ' . implode(', ', $missingTypes);
        }

        return $recommendations;
    }

    private function getRecommendedAction(DataRetentionPolicy $policy, int $daysUntilAction): string
    {
        if ($daysUntilAction <= 0) {
            return match ($policy->retention_action) {
                RetentionAction::DELETE => 'Schedule for deletion',
                RetentionAction::ANONYMIZE => 'Apply anonymization',
                RetentionAction::ARCHIVE => 'Move to archive',
                RetentionAction::REVIEW => 'Manual review required',
                default => 'No action'
            };
        }

        if ($daysUntilAction <= 30) {
            return 'Review upcoming retention action';
        }

        return 'Monitor retention schedule';
    }
}
