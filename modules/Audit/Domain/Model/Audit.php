<?php

declare(strict_types=1);

namespace Modules\Audit\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Models\Audit as BaseAudit;

/**
 * @property int $id
 * @property string|null $user_type
 * @property int|null $user_id
 * @property string $event
 * @property string $auditable_type
 * @property int $auditable_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $tags
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $formatted_created_at
 * @property string $formatted_event
 * @property array<string, mixed> $diff
 * @property int $change_count
 */
class Audit extends BaseAudit
{
    protected $table = 'audits';

    /** @var array<int, string> */
    protected $guarded = [];

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedCreatedAt(): Attribute
    {
        return Attribute::make(get: fn () => $this->created_at->format('Y-m-d H:i:s'));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedEvent(): Attribute
    {
        return Attribute::make(get: fn (): string => ucfirst($this->event));
    }

    /**
     * @return Attribute<array<string, array<string, mixed>>, never>
     */
    protected function diff(): Attribute
    {
        return Attribute::make(get: function (): array {
            $diff = [];
            $oldValues = $this->old_values ?? [];
            $newValues = $this->new_values ?? [];
            $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
            foreach ($allKeys as $key) {
                $diff[$key] = [
                    'old' => $oldValues[$key] ?? null,
                    'new' => $newValues[$key] ?? null,
                    'changed' => ($oldValues[$key] ?? null) !== ($newValues[$key] ?? null),
                ];
            }

            return $diff;
        });
    }

    /**
     * @return Attribute<int, never>
     */
    protected function changeCount(): Attribute
    {
        return Attribute::make(get: fn (): int => count($this->diff));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function forModel($query, string $modelType, int $modelId)
    {
        return $query->where('auditable_type', $modelType)
            ->where('auditable_id', $modelId);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function byUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function byEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function inDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function recent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'json',
            'new_values' => 'json',
            'auditable_id' => 'integer',
        ];
    }
}
