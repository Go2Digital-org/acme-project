<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Modules\Compliance\Domain\ValueObject\DataProcessingPurpose;
use Modules\Compliance\Domain\ValueObject\RetentionAction;

/**
 * Data Retention Policy Model
 *
 * @property int $id
 * @property string $policy_name
 * @property string $data_category
 * @property DataProcessingPurpose $purpose
 * @property string $retention_period
 * @property RetentionAction $retention_action
 * @property string $legal_basis
 * @property array<string, mixed> $deletion_criteria
 * @property array<string, mixed> $anonymization_rules
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property Carbon|null $effective_from
 * @property Carbon|null $effective_until
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DataRetentionPolicy extends Model
{
    protected $table = 'compliance_data_retention_policies';

    protected $fillable = [
        'policy_name',
        'data_category',
        'purpose',
        'retention_period',
        'retention_action',
        'legal_basis',
        'deletion_criteria',
        'anonymization_rules',
        'is_active',
        'created_by',
        'approved_by',
        'effective_from',
        'effective_until',
    ];

    public function isEffective(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->effective_from && $now->isBefore($this->effective_from)) {
            return false;
        }

        return ! ($this->effective_until && $now->isAfter($this->effective_until));
    }

    public function calculateRetentionDate(Carbon $dataCreatedAt): Carbon
    {
        return $dataCreatedAt->clone()->modify($this->retention_period);
    }

    public function shouldRetainData(Carbon $dataCreatedAt): bool
    {
        $retentionDate = $this->calculateRetentionDate($dataCreatedAt);

        return now()->isBefore($retentionDate);
    }

    public function shouldDeleteData(Carbon $dataCreatedAt): bool
    {
        if (! $this->isEffective()) {
            return false;
        }

        return ! $this->shouldRetainData($dataCreatedAt) &&
               $this->retention_action === RetentionAction::DELETE;
    }

    public function shouldAnonymizeData(Carbon $dataCreatedAt): bool
    {
        if (! $this->isEffective()) {
            return false;
        }

        return ! $this->shouldRetainData($dataCreatedAt) &&
               $this->retention_action === RetentionAction::ANONYMIZE;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAnonymizationRules(): array
    {
        return $this->anonymization_rules ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDeletionCriteria(): array
    {
        return $this->deletion_criteria ?? [];
    }

    public function activate(): void
    {
        $this->is_active = true;
        $this->effective_from = now();
        $this->save();
    }

    public function deactivate(): void
    {
        $this->is_active = false;
        $this->effective_until = now();
        $this->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => DataProcessingPurpose::class,
            'retention_action' => RetentionAction::class,
            'deletion_criteria' => 'array',
            'anonymization_rules' => 'array',
            'is_active' => 'boolean',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
        ];
    }
}
