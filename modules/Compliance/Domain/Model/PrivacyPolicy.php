<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Compliance\Domain\ValueObject\PolicyStatus;

/**
 * Privacy Policy Model
 *
 * @property int $id
 * @property string $version
 * @property string $title
 * @property string $content
 * @property PolicyStatus $status
 * @property array<int, string> $data_categories
 * @property array<int, string> $processing_purposes
 * @property array<int, string> $legal_bases
 * @property array<int, string> $third_parties
 * @property array<string, string> $retention_periods
 * @property array<int, string> $user_rights
 * @property string|null $contact_information
 * @property Carbon|null $effective_from
 * @property Carbon|null $effective_until
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PrivacyPolicy extends Model
{
    protected $table = 'compliance_privacy_policies';

    protected $fillable = [
        'version',
        'title',
        'content',
        'status',
        'data_categories',
        'processing_purposes',
        'legal_bases',
        'third_parties',
        'retention_periods',
        'user_rights',
        'contact_information',
        'effective_from',
        'effective_until',
        'created_by',
        'approved_by',
    ];

    /**
     * @return HasMany<PolicyAcceptance, $this>
     */
    public function acceptances(): HasMany
    {
        return $this->hasMany(PolicyAcceptance::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== PolicyStatus::ACTIVE) {
            return false;
        }

        $now = now();

        if ($this->effective_from && $now->isBefore($this->effective_from)) {
            return false;
        }

        return ! ($this->effective_until && $now->isAfter($this->effective_until));
    }

    public function activate(?int $approvedBy = null): void
    {
        $this->status = PolicyStatus::ACTIVE;
        $this->effective_from = now();
        $this->approved_by = $approvedBy;
        $this->save();
    }

    public function retire(): void
    {
        $this->status = PolicyStatus::RETIRED;
        $this->effective_until = now();
        $this->save();
    }

    /**
     * @return array<int, string>
     */
    public function getDataCategories(): array
    {
        return $this->data_categories ?? [];
    }

    /**
     * @return array<int, string>
     */
    public function getProcessingPurposes(): array
    {
        return $this->processing_purposes ?? [];
    }

    /**
     * @return array<int, string>
     */
    public function getLegalBases(): array
    {
        return $this->legal_bases ?? [];
    }

    /**
     * @return array<int, string>
     */
    public function getThirdParties(): array
    {
        return $this->third_parties ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRetentionPeriods(): array
    {
        return $this->retention_periods ?? [];
    }

    /**
     * @return array<int, string>
     */
    public function getUserRights(): array
    {
        return $this->user_rights ?? [];
    }

    public function getAcceptanceRate(): float
    {
        $totalUsers = $this->estimateActiveUsers();
        $acceptances = $this->acceptances()->count();

        if ($totalUsers === 0) {
            return 0.0;
        }

        return round(($acceptances / $totalUsers) * 100, 2);
    }

    public function requiresUpdate(): bool
    {
        // Check if policy is older than 12 months
        return $this->effective_from && $this->effective_from->addYear()->isPast();
    }

    public function getLastUpdateDate(): ?Carbon
    {
        return $this->effective_from;
    }

    /**
     * @return array<string, mixed>
     */
    public function generateSummary(): array
    {
        return [
            'version' => $this->version,
            'status' => $this->status->value,
            'effective_period' => [
                'from' => $this->effective_from?->toDateString(),
                'until' => $this->effective_until?->toDateString(),
            ],
            'coverage' => [
                'data_categories' => count($this->getDataCategories()),
                'processing_purposes' => count($this->getProcessingPurposes()),
                'third_parties' => count($this->getThirdParties()),
                'user_rights' => count($this->getUserRights()),
            ],
            'compliance_indicators' => [
                'has_contact_info' => ! empty($this->contact_information),
                'has_retention_periods' => $this->getRetentionPeriods() !== [],
                'has_legal_bases' => $this->getLegalBases() !== [],
                'requires_update' => $this->requiresUpdate(),
            ],
        ];
    }

    private function estimateActiveUsers(): int
    {
        // This would typically query the User model
        // Returning a placeholder for now
        return 1000;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PolicyStatus::class,
            'data_categories' => 'array',
            'processing_purposes' => 'array',
            'legal_bases' => 'array',
            'third_parties' => 'array',
            'retention_periods' => 'array',
            'user_rights' => 'array',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
        ];
    }
}
