<?php

declare(strict_types=1);

namespace Modules\Bookmark\Domain\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Infrastructure\Laravel\Factory\BookmarkFactory;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @property int $id
 * @property int $user_id
 * @property int $campaign_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Campaign $campaign
 * @property User $user
 *
 * @method static Builder|Bookmark where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static Builder<static>|Bookmark newModelQuery()
 * @method static Builder<static>|Bookmark newQuery()
 * @method static Builder<static>|Bookmark query()
 * @method static Builder<static>|Bookmark whereCampaignId($value)
 * @method static Builder<static>|Bookmark whereCreatedAt($value)
 * @method static Builder<static>|Bookmark whereId($value)
 * @method static Builder<static>|Bookmark whereUpdatedAt($value)
 * @method static Builder<static>|Bookmark whereUserId($value)
 *
 * @mixin Model
 */
class Bookmark extends Model
{
    /** @use HasFactory<BookmarkFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BookmarkFactory
    {
        return BookmarkFactory::new();
    }

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'campaign_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Check if bookmark belongs to specific user.
     */
    public function belongsToUser(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    /**
     * Check if bookmark is for specific campaign.
     */
    public function isForCampaign(int $campaignId): bool
    {
        return $this->campaign_id === $campaignId;
    }

    /**
     * Check if bookmark matches user and campaign.
     */
    public function matches(int $userId, int $campaignId): bool
    {
        return $this->belongsToUser($userId) && $this->isForCampaign($campaignId);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'campaign_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
