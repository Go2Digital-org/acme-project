<?php

declare(strict_types=1);

namespace Modules\Audit\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Entity history read model optimized for tracking changes to specific entities.
 */
class EntityHistoryReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $entityId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($entityId, $data, $version);
        $this->setCacheTtl(2400); // 40 minutes for entity history
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'entity_history',
            'auditable:' . $this->getAuditableType() . ':' . $this->id,
            'entity_type:' . $this->getAuditableType(),
        ]);
    }

    // Entity Information
    public function getEntityId(): int
    {
        return (int) $this->id;
    }

    public function getAuditableType(): string
    {
        return $this->get('auditable_type', '');
    }

    public function getAuditableTypeName(): string
    {
        return class_basename($this->getAuditableType());
    }

    public function getEntityName(): ?string
    {
        return $this->get('entity_name');
    }

    public function getEntityTitle(): ?string
    {
        return $this->get('entity_title');
    }

    public function getEntityDescription(): ?string
    {
        return $this->get('entity_description');
    }

    // Change History Statistics
    public function getTotalChanges(): int
    {
        return (int) $this->get('total_changes', 0);
    }

    public function getChangesInPeriod(): int
    {
        return (int) $this->get('changes_in_period', 0);
    }

    public function getFirstChange(): ?string
    {
        return $this->get('first_change');
    }

    public function getLastChange(): ?string
    {
        return $this->get('last_change');
    }

    public function getFormattedFirstChange(): string
    {
        $firstChange = $this->getFirstChange();

        if (! $firstChange) {
            return '';
        }

        $timestamp = strtotime($firstChange);

        return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : '';
    }

    public function getFormattedLastChange(): string
    {
        $lastChange = $this->getLastChange();

        if (! $lastChange) {
            return '';
        }

        $timestamp = strtotime($lastChange);

        return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : '';
    }

    // Change Breakdown by Event Type
    /**
     * @return array<string, int>
     */
    public function getEventBreakdown(): array
    {
        return $this->get('event_breakdown', []);
    }

    public function getCreatedCount(): int
    {
        return (int) ($this->getEventBreakdown()['created'] ?? 0);
    }

    public function getUpdatedCount(): int
    {
        return (int) ($this->getEventBreakdown()['updated'] ?? 0);
    }

    public function getDeletedCount(): int
    {
        return (int) ($this->getEventBreakdown()['deleted'] ?? 0);
    }

    public function getRestoredCount(): int
    {
        return (int) ($this->getEventBreakdown()['restored'] ?? 0);
    }

    // User Activity on Entity
    /**
     * @return array<int, int>
     */
    public function getUserBreakdown(): array
    {
        return $this->get('user_breakdown', []);
    }

    public function getUniqueUserCount(): int
    {
        return count($this->getUserBreakdown());
    }

    public function getMostActiveUserId(): ?int
    {
        $userBreakdown = $this->getUserBreakdown();

        if ($userBreakdown === []) {
            return null;
        }

        $maxValue = max($userBreakdown);
        $keys = array_keys($userBreakdown, $maxValue);

        return $keys[0];
    }

    public function getMostActiveUserName(): ?string
    {
        $mostActiveUserId = $this->getMostActiveUserId();

        if (! $mostActiveUserId) {
            return null;
        }

        return $this->get('most_active_user_name');
    }

    // Field Change Analysis
    /**
     * @return array<string, int>
     */
    public function getFieldChangeBreakdown(): array
    {
        return $this->get('field_change_breakdown', []);
    }

    public function getMostChangedField(): ?string
    {
        $fieldBreakdown = $this->getFieldChangeBreakdown();

        if ($fieldBreakdown === []) {
            return null;
        }

        $maxValue = max($fieldBreakdown);
        $keys = array_keys($fieldBreakdown, $maxValue);

        return $keys[0];
    }

    public function getTotalFieldChanges(): int
    {
        return array_sum($this->getFieldChangeBreakdown());
    }

    // Recent Changes
    /**
     * @return array<string, mixed>
     */
    public function getRecentChanges(): array
    {
        return $this->get('recent_changes', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMajorChanges(): array
    {
        return $this->get('major_changes', []);
    }

    // Lifecycle Information
    public function getCreationDate(): ?string
    {
        return $this->get('creation_date');
    }

    public function getLastModificationDate(): ?string
    {
        return $this->getLastChange();
    }

    public function getDeletionDate(): ?string
    {
        return $this->get('deletion_date');
    }

    public function isDeleted(): bool
    {
        return $this->getDeletionDate() !== null;
    }

    public function isRestored(): bool
    {
        return $this->getRestoredCount() > 0 && ! $this->isDeleted();
    }

    public function getEntityAge(): int
    {
        $creationDate = $this->getCreationDate();

        if (! $creationDate) {
            return 0;
        }

        return (int) ((time() - strtotime($creationDate)) / 86400); // Days
    }

    public function getDaysSinceLastChange(): int
    {
        $lastChange = $this->getLastChange();

        if (! $lastChange) {
            return 0;
        }

        return (int) ((time() - strtotime($lastChange)) / 86400);
    }

    // Change Pattern Analysis
    public function getChangeFrequency(): float
    {
        $totalChanges = $this->getTotalChanges();
        $entityAge = $this->getEntityAge();

        if ($totalChanges <= 0 || $entityAge <= 0) {
            return 0.0;
        }

        return round($totalChanges / $entityAge, 2); // Changes per day
    }

    public function getRecentActivity(): string
    {
        $daysSinceLastChange = $this->getDaysSinceLastChange();

        if ($daysSinceLastChange <= 1) {
            return 'very_active';
        }

        if ($daysSinceLastChange <= 7) {
            return 'active';
        }

        if ($daysSinceLastChange <= 30) {
            return 'moderate';
        }

        if ($daysSinceLastChange <= 90) {
            return 'inactive';
        }

        return 'dormant';
    }

    public function getStabilityScore(): float
    {
        $totalChanges = $this->getTotalChanges();
        $entityAge = $this->getEntityAge();

        if ($entityAge <= 0) {
            return 0.0;
        }

        // More changes relative to age = less stable
        $changeRatio = $totalChanges / max($entityAge, 1);

        // Convert to stability score (inverse relationship)
        $stabilityScore = max(0, 100 - ($changeRatio * 10));

        return round(min($stabilityScore, 100), 1);
    }

    // Risk Analysis for Entity
    /**
     * @return array<int, string>
     */
    public function getRiskFactors(): array
    {
        $risks = [];

        // High frequency changes
        if ($this->getChangeFrequency() > 2) {
            $risks[] = 'high_change_frequency';
        }

        // Recent deletions
        if ($this->getDeletedCount() > 0) {
            $risks[] = 'has_deletions';
        }

        // Multiple users making changes
        if ($this->getUniqueUserCount() > 5) {
            $risks[] = 'multiple_editors';
        }

        // Sensitive field changes
        $sensitiveFields = ['password', 'email', 'permissions', 'status', 'roles'];
        $changedFields = array_keys($this->getFieldChangeBreakdown());
        if (array_intersect($sensitiveFields, $changedFields)) {
            $risks[] = 'sensitive_field_changes';
        }

        return $risks;
    }

    public function getRiskLevel(): string
    {
        $riskFactors = $this->getRiskFactors();
        $riskCount = count($riskFactors);

        if ($riskCount >= 3) {
            return 'high';
        }

        if ($riskCount >= 2) {
            return 'medium';
        }

        if ($riskCount >= 1) {
            return 'low';
        }

        return 'minimal';
    }

    // Timeline Analysis
    /**
     * @return array<string, mixed>
     */
    public function getTimelineBreakdown(): array
    {
        return $this->get('timeline_breakdown', []);
    }

    /**
     * @return array<string, int>
     */
    public function getMonthlyChanges(): array
    {
        return $this->get('monthly_changes', []);
    }

    public function getPeakChangeMonth(): ?string
    {
        $monthlyChanges = $this->getMonthlyChanges();

        if ($monthlyChanges === []) {
            return null;
        }

        $maxValue = max($monthlyChanges);
        $keys = array_keys($monthlyChanges, $maxValue);

        return $keys[0];
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toSummaryArray(): array
    {
        return [
            'entity_id' => $this->getEntityId(),
            'auditable_type' => $this->getAuditableType(),
            'auditable_type_name' => $this->getAuditableTypeName(),
            'entity_name' => $this->getEntityName(),
            'entity_title' => $this->getEntityTitle(),
            'total_changes' => $this->getTotalChanges(),
            'changes_in_period' => $this->getChangesInPeriod(),
            'first_change' => $this->getFormattedFirstChange(),
            'last_change' => $this->getFormattedLastChange(),
            'unique_user_count' => $this->getUniqueUserCount(),
            'most_active_user_name' => $this->getMostActiveUserName(),
            'most_changed_field' => $this->getMostChangedField(),
            'change_frequency' => $this->getChangeFrequency(),
            'recent_activity' => $this->getRecentActivity(),
            'stability_score' => $this->getStabilityScore(),
            'risk_level' => $this->getRiskLevel(),
            'entity_age' => $this->getEntityAge(),
            'days_since_last_change' => $this->getDaysSinceLastChange(),
            'is_deleted' => $this->isDeleted(),
            'is_restored' => $this->isRestored(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetailedArray(): array
    {
        return array_merge($this->toSummaryArray(), [
            'event_breakdown' => $this->getEventBreakdown(),
            'user_breakdown' => $this->getUserBreakdown(),
            'field_change_breakdown' => $this->getFieldChangeBreakdown(),
            'recent_changes' => $this->getRecentChanges(),
            'major_changes' => $this->getMajorChanges(),
            'timeline_breakdown' => $this->getTimelineBreakdown(),
            'monthly_changes' => $this->getMonthlyChanges(),
            'risk_factors' => $this->getRiskFactors(),
            'creation_date' => $this->getCreationDate(),
            'deletion_date' => $this->getDeletionDate(),
            'peak_change_month' => $this->getPeakChangeMonth(),
        ]);
    }
}
