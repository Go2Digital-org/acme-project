<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Shared\Infrastructure\Laravel\Factory\SocialMediaFactory;

/**
 * @property int $id
 * @property string $platform
 * @property string $url
 * @property string|null $icon
 * @property bool $is_active
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $platform_name
 * @property string $platform_icon
 *
 * @method static Builder|SocialMedia where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static Builder<static>|SocialMedia newModelQuery()
 * @method static Builder<static>|SocialMedia newQuery()
 * @method static Builder<static>|SocialMedia query()
 * @method static Builder<static>|SocialMedia active()
 * @method static Builder<static>|SocialMedia ordered()
 * @method static Builder<static>|SocialMedia byPlatform(string $platform)
 * @method static Builder<static>|SocialMedia whereCreatedAt($value)
 * @method static Builder<static>|SocialMedia whereIcon($value)
 * @method static Builder<static>|SocialMedia whereId($value)
 * @method static Builder<static>|SocialMedia whereIsActive($value)
 * @method static Builder<static>|SocialMedia whereOrder($value)
 * @method static Builder<static>|SocialMedia wherePlatform($value)
 * @method static Builder<static>|SocialMedia whereUpdatedAt($value)
 * @method static Builder<static>|SocialMedia whereUrl($value)
 *
 * @mixin Model
 */
class SocialMedia extends Model
{
    /** @use HasFactory<SocialMediaFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var array<string, string> */
    public const PLATFORMS = [
        'facebook' => 'Facebook',
        'twitter' => 'Twitter/X',
        'linkedin' => 'LinkedIn',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
    ];

    /** @var array<string, string> */
    public const PLATFORM_ICONS = [
        'facebook' => 'heroicon-o-facebook',
        'twitter' => 'heroicon-o-twitter',
        'linkedin' => 'heroicon-o-linkedin',
        'instagram' => 'heroicon-o-instagram',
        'youtube' => 'heroicon-o-youtube',
    ];

    protected $table = 'social_media';

    /** @var list<string> */
    protected $fillable = [
        'platform',
        'name',
        'url',
        'icon',
        'color',
        'description',
        'is_active',
        'order',
    ];

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeByPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function platformName(): Attribute
    {
        return Attribute::make(get: fn (): string => self::PLATFORMS[$this->platform] ?? $this->platform);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function platformIcon(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->icon !== null && $this->icon !== '' ? $this->icon : (self::PLATFORM_ICONS[$this->platform] ?? 'heroicon-o-link'));
    }

    public function isValidPlatform(): bool
    {
        return array_key_exists($this->platform, self::PLATFORMS);
    }

    public function isValidUrl(): bool
    {
        return filter_var($this->url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SocialMediaFactory
    {
        return SocialMediaFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (SocialMedia $model): void {
            // Only validate URL if it's provided and not empty
            if (! empty($model->url) && ! $model->isValidUrl()) {
                throw new InvalidArgumentException("Invalid URL: {$model->url}");
            }

            if ($model->order === null) {
                /** @var int|null $maxOrder */
                $maxOrder = DB::table('social_media')->max('order');
                $model->order = ($maxOrder ?? 0) + 1;
            }
        });
    }

    /**
     * @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }
}
