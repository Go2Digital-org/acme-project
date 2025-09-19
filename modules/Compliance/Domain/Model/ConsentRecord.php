<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Compliance\Domain\ValueObject\ConsentStatus;
use Modules\Compliance\Domain\ValueObject\DataProcessingPurpose;

/**
 * GDPR Consent Record Model
 *
 * @property int $id
 * @property int $data_subject_id
 * @property DataProcessingPurpose $purpose
 * @property ConsentStatus $status
 * @property string|null $consent_method
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $consent_data
 * @property string|null $withdrawal_reason
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property DataSubject $dataSubject
 */
class ConsentRecord extends Model
{
    protected $table = 'compliance_consent_records';

    protected $fillable = [
        'data_subject_id',
        'purpose',
        'status',
        'consent_method',
        'ip_address',
        'user_agent',
        'consent_data',
        'withdrawal_reason',
        'expires_at',
    ];

    /**
     * @return BelongsTo<DataSubject, $this>
     */
    public function dataSubject(): BelongsTo
    {
        return $this->belongsTo(DataSubject::class);
    }

    public function isValid(): bool
    {
        return $this->status === ConsentStatus::GIVEN &&
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function withdraw(?string $reason = null): void
    {
        $this->status = ConsentStatus::WITHDRAWN;
        $this->withdrawal_reason = $reason;
        $this->save();
    }

    public function markExpired(): void
    {
        $this->status = ConsentStatus::EXPIRED;
        $this->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => DataProcessingPurpose::class,
            'status' => ConsentStatus::class,
            'consent_data' => 'array',
            'expires_at' => 'datetime',
        ];
    }
}
