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
use Laravel\Scout\Searchable;
use Modules\Shared\Domain\Traits\HasTranslations;
use Modules\Shared\Infrastructure\Laravel\Factory\PageFactory;

/**
 * @property int $id
 * @property string $slug
 * @property string $status
 * @property int $order
 * @property array<string, string>|null $title
 * @property array<string, string>|null $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $url
 *
 * @method static Builder|Page where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static Builder<static>|Page newModelQuery()
 * @method static Builder<static>|Page newQuery()
 * @method static Builder<static>|Page query()
 * @method static Builder<static>|Page published()
 * @method static Builder<static>|Page draft()
 * @method static Builder<static>|Page ordered()
 * @method static Builder<static>|Page whereContent($value)
 * @method static Builder<static>|Page whereCreatedAt($value)
 * @method static Builder<static>|Page whereId($value)
 * @method static Builder<static>|Page whereOrder($value)
 * @method static Builder<static>|Page whereSlug($value)
 * @method static Builder<static>|Page whereStatus($value)
 * @method static Builder<static>|Page whereTitle($value)
 * @method static Builder<static>|Page whereUpdatedAt($value)
 *
 * @phpstan-use HasFactory<PageFactory>
 *
 * @mixin Model
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    use HasTranslations;
    use Searchable;
    use SoftDeletes;

    /**
     * The attributes that are translatable.
     *
     * @var list<string>
     */
    protected array $translatable = [
        'title',
        'content',
    ];

    /** @var list<string> */
    protected $fillable = [
        'slug',
        'status',
        'order',
        'title',
        'content',
    ];

    /**
     * Scope for published pages only.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for draft pages only.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to order by display order.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * Check if the page is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if the page is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Get the page URL.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::make(get: fn (): string => route('page', $this->slug));
    }

    /**
     * Mark the page as published.
     */
    public function publish(): void
    {
        $this->update(['status' => 'published']);
    }

    /**
     * Mark the page as draft.
     */
    public function makeDraft(): void
    {
        $this->update(['status' => 'draft']);
    }

    /**
     * Get the indexable data array for the model.
     */
    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        // Get translations arrays directly to avoid fallback
        $titleTranslations = is_array($this->title) ? $this->title : [];
        $contentTranslations = is_array($this->content) ? $this->content : [];

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'status' => $this->status,
            'order' => $this->order,
            'title' => $this->getTranslation('title') ?? 'Untitled Page',
            'title_en' => $titleTranslations['en'] ?? null,
            'title_fr' => $titleTranslations['fr'] ?? null,
            'title_de' => $titleTranslations['de'] ?? null,
            'content' => $this->getTranslation('content'),
            'content_en' => $contentTranslations['en'] ?? null,
            'content_fr' => $contentTranslations['fr'] ?? null,
            'content_de' => $contentTranslations['de'] ?? null,
            'content_plain' => strip_tags($this->getTranslation('content') ?? ''),
            'content_plain_en' => strip_tags($contentTranslations['en'] ?? ''),
            'content_plain_fr' => strip_tags($contentTranslations['fr'] ?? ''),
            'content_plain_de' => strip_tags($contentTranslations['de'] ?? ''),
            'is_published' => $this->isPublished(),
            'is_draft' => $this->isDraft(),
            'url' => $this->url,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->isPublished();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'content' => 'array',
            'order' => 'integer',
        ];
    }
}
