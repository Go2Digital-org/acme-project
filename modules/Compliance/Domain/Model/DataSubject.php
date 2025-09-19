<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Compliance\Domain\ValueObject\ConsentStatus;
use Modules\Compliance\Domain\ValueObject\DataProcessingPurpose;

/**
 * GDPR Data Subject Model
 *
 * @property int $id
 * @property string $subject_type
 * @property int $subject_id
 * @property string $email
 * @property ConsentStatus $consent_status
 * @property array<int, string> $consented_purposes
 * @property Carbon|null $consent_given_at
 * @property Carbon|null $consent_withdrawn_at
 * @property Carbon|null $data_export_requested_at
 * @property Carbon|null $data_export_completed_at
 * @property Carbon|null $deletion_requested_at
 * @property Carbon|null $deletion_completed_at
 * @property string|null $legal_basis
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DataSubject extends Model
{
    protected $table = 'compliance_data_subjects';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'email',
        'consent_status',
        'consented_purposes',
        'consent_given_at',
        'consent_withdrawn_at',
        'data_export_requested_at',
        'data_export_completed_at',
        'deletion_requested_at',
        'deletion_completed_at',
        'legal_basis',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<ConsentRecord, $this>
     */
    public function consentRecords(): HasMany
    {
        return $this->hasMany(ConsentRecord::class);
    }

    /**
     * @return HasMany<DataProcessingActivity, $this>
     */
    public function processingActivities(): HasMany
    {
        return $this->hasMany(DataProcessingActivity::class);
    }

    public function hasValidConsent(): bool
    {
        return $this->consent_status === ConsentStatus::GIVEN &&
               $this->consent_given_at !== null &&
               $this->consent_withdrawn_at === null;
    }

    public function hasConsentFor(DataProcessingPurpose $purpose): bool
    {
        return $this->hasValidConsent() &&
               in_array($purpose->value, $this->consented_purposes ?? [], true);
    }

    public function requestDataExport(): void
    {
        $this->data_export_requested_at = now();
        $this->save();
    }

    public function completeDataExport(): void
    {
        $this->data_export_completed_at = now();
        $this->save();
    }

    public function requestDeletion(): void
    {
        $this->deletion_requested_at = now();
        $this->save();
    }

    public function completeDeletion(): void
    {
        $this->deletion_completed_at = now();
        $this->save();
    }

    public function withdrawConsent(): void
    {
        $this->consent_status = ConsentStatus::WITHDRAWN;
        $this->consent_withdrawn_at = now();
        $this->consented_purposes = [];
        $this->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consent_status' => ConsentStatus::class,
            'consented_purposes' => 'array',
            'consent_given_at' => 'datetime',
            'consent_withdrawn_at' => 'datetime',
            'data_export_requested_at' => 'datetime',
            'data_export_completed_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'deletion_completed_at' => 'datetime',
        ];
    }
}
