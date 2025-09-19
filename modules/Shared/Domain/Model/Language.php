<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Shared\Infrastructure\Laravel\Factory\LanguageFactory;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $native_name
 * @property string|null $flag
 * @property bool $is_active
 * @property bool $is_default
 * @property int|null $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|Language where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static Builder<static>|Language newModelQuery()
 * @method static Builder<static>|Language newQuery()
 * @method static Builder<static>|Language query()
 * @method static Builder<static>|Language whereCode($value)
 * @method static Builder<static>|Language whereCreatedAt($value)
 * @method static Builder<static>|Language whereFlag($value)
 * @method static Builder<static>|Language whereId($value)
 * @method static Builder<static>|Language whereIsActive($value)
 * @method static Builder<static>|Language whereIsDefault($value)
 * @method static Builder<static>|Language whereName($value)
 * @method static Builder<static>|Language whereNativeName($value)
 * @method static Builder<static>|Language whereSortOrder($value)
 * @method static Builder<static>|Language whereUpdatedAt($value)
 *
 * @mixin Model
 */
class Language extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag',
        'is_active',
        'is_default',
        'sort_order',
    ];

    /**
     * Get the active languages ordered by sort order.
     */
    public static function active(): mixed
    {
        return static::where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Get the default language.
     */
    public static function default(): mixed
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure only one default language
        static::saving(function ($language): void {
            if ($language->is_default) {
                static::where('id', '!=', $language->id)->update(['is_default' => false]);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'code' => 'string',
            'name' => 'string',
            'native_name' => 'string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return LanguageFactory
     */
    protected static function newFactory(): Factory
    {
        return LanguageFactory::new();
    }
}
