<?php

declare(strict_types=1);

namespace Modules\Compliance\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Privacy Policy Acceptance Model
 *
 * @property int $id
 * @property int $privacy_policy_id
 * @property string $user_type
 * @property int $user_id
 * @property string $acceptance_method
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $acceptance_data
 * @property Carbon $accepted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property PrivacyPolicy $privacyPolicy
 */
class PolicyAcceptance extends Model
{
    protected $table = 'compliance_policy_acceptances';

    protected $fillable = [
        'privacy_policy_id',
        'user_type',
        'user_id',
        'acceptance_method',
        'ip_address',
        'user_agent',
        'acceptance_data',
        'accepted_at',
    ];

    /**
     * @return BelongsTo<PrivacyPolicy, $this>
     */
    public function privacyPolicy(): BelongsTo
    {
        return $this->belongsTo(PrivacyPolicy::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function isValid(): bool
    {
        return $this->privacyPolicy->isActive();
    }

    public function isRecent(): bool
    {
        return $this->accepted_at->isAfter(now()->subYear());
    }

    public function requiresReacceptance(): bool
    {
        if (! $this->isRecent()) {
            return true;
        }

        return ! $this->isValid();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acceptance_data' => 'array',
            'accepted_at' => 'datetime',
        ];
    }
}
