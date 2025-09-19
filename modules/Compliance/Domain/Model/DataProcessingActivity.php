<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Compliance\Domain\ValueObject\DataProcessingPurpose;

/**
 * GDPR Data Processing Activity Model
 *
 * @property int $id
 * @property int $data_subject_id
 * @property DataProcessingPurpose $purpose
 * @property string $activity_type
 * @property string $description
 * @property array<int, string> $personal_data_categories
 * @property string $legal_basis
 * @property string|null $controller
 * @property string|null $processor
 * @property array<int, string>|null $recipients
 * @property string|null $retention_period
 * @property array<string, mixed>|null $security_measures
 * @property Carbon $processed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property DataSubject $dataSubject
 */
class DataProcessingActivity extends Model
{
    protected $table = 'compliance_data_processing_activities';

    protected $fillable = [
        'data_subject_id',
        'purpose',
        'activity_type',
        'description',
        'personal_data_categories',
        'legal_basis',
        'controller',
        'processor',
        'recipients',
        'retention_period',
        'security_measures',
        'processed_at',
    ];

    /**
     * @return BelongsTo<DataSubject, $this>
     */
    public function dataSubject(): BelongsTo
    {
        return $this->belongsTo(DataSubject::class);
    }

    public function isRetentionPeriodExpired(): bool
    {
        if (! $this->retention_period) {
            return false;
        }

        $retentionDate = $this->processed_at->modify($this->retention_period);

        return $retentionDate->isPast();
    }

    public function shouldBeDeleted(): bool
    {
        return $this->isRetentionPeriodExpired() &&
               ! $this->purpose->hasLegitimateInterest();
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    public function getPersonalDataCategoriesAttribute($value): array
    {
        return json_decode((string) $value, true) ?? [];
    }

    /**
     * @param  array<int, string>  $value
     */
    public function setPersonalDataCategoriesAttribute($value): void
    {
        $this->attributes['personal_data_categories'] = json_encode($value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => DataProcessingPurpose::class,
            'personal_data_categories' => 'array',
            'recipients' => 'array',
            'security_measures' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
