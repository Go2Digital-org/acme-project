<?php

declare(strict_types=1);

namespace Modules\Category\Domain\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Category\Domain\ValueObject\CategoryStatus;
use Modules\Category\Infrastructure\Laravel\Factory\CategoryFactory;
use Modules\Shared\Domain\Traits\HasTranslations;

/**
 * @property int $id
 * @property array<string, string> $name
 * @property array<string, string>|null $description
 * @property string $slug
 * @property CategoryStatus $status
 * @property string|null $color
 * @property string|null $icon
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, Campaign> $campaigns
 * @property int|null $campaigns_count
 *
 * @method static Builder|Category active()
 * @method static Builder|Category ordered()
 * @method static Builder|Category whereSlug(string $slug)
 * @method static Builder|Category whereStatus(CategoryStatus $status)
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static CategoryFactory factory($count = null, $state = [])
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use HasTranslations;
    use Searchable;
    use SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
        'slug',
        'status',
        'color',
        'icon',
        'sort_order',
    ];

    /** @var list<string> */
    protected array $translatable = [
        'name',
        'description',
    ];

    protected $attributes = [
        'status' => 'active',
        'sort_order' => 0,
    ];

    // Domain methods
    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getParentId(): ?int
    {
        return null; // This model doesn't have parent_id, but needed by ReadModel
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getSortOrder(): int
    {
        return $this->sort_order;
    }

    public function getStatus(): CategoryStatus
    {
        return $this->status;
    }

    public function getCampaignCount(): int
    {
        return $this->campaigns_count ?? $this->campaigns()->count();
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updated_at;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function getName(?string $locale = null): string
    {
        return $this->getTranslation('name', $locale) ?? 'Unnamed Category';
    }

    public function getDescription(?string $locale = null): ?string
    {
        return $this->getTranslation('description', $locale);
    }

    /**
     * @param  array<string, string>  $name
     */
    public function updateName(array $name): void
    {
        $this->name = $name;
    }

    /**
     * @param  array<string, string>|null  $description
     */
    public function updateDescription(?array $description): void
    {
        $this->description = $description;
    }

    public function setParentId(?int $parentId): void
    {
        // This model doesn't have parent support, but method needed by command handler
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sort_order = $sortOrder;
    }

    public function setStatus(CategoryStatus $status): void
    {
        $this->status = $status;
    }

    public function updateIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function updateSortOrder(int $sortOrder): void
    {
        $this->sort_order = $sortOrder;
    }

    public function activate(): void
    {
        $this->status = CategoryStatus::ACTIVE;
    }

    public function deactivate(): void
    {
        $this->status = CategoryStatus::INACTIVE;
    }

    public function hasActiveCampaigns(): bool
    {
        return $this->campaigns()->whereIn('status', ['active', 'draft'])->exists();
    }

    // Relationships
    /**
     * @return HasMany<Campaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'category_id');
    }

    // Query scopes
    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CategoryStatus::ACTIVE);
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name->en');
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        // Only load campaigns if not already loaded
        if (! $this->relationLoaded('campaigns')) {
            $this->load(['campaigns']);
        }

        // Get translations without fallback for specific locales
        $nameTranslations = $this->getDirectAttribute('name') ?? [];
        $descriptionTranslations = $this->getDirectAttribute('description') ?? [];

        return [
            'id' => $this->id,
            'name' => $this->getName(),
            'name_en' => $nameTranslations['en'] ?? $this->getName(),
            'name_fr' => $nameTranslations['fr'] ?? null,
            'name_de' => $nameTranslations['de'] ?? null,
            'description' => $this->getDescription(),
            'description_en' => $descriptionTranslations['en'] ?? null,
            'description_fr' => $descriptionTranslations['fr'] ?? null,
            'description_de' => $descriptionTranslations['de'] ?? null,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'color' => $this->color,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'is_active' => $this->isActive(),
            'campaigns_count' => $this->campaigns->count(),
            'has_active_campaigns' => $this->hasActiveCampaigns(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status->isActive();
    }

    /**
     * Modify the query used to retrieve models when making all searchable.
     */
    protected function makeAllSearchableUsing(mixed $query): mixed
    {
        return $query->with(['campaigns']);
    }

    // Factory
    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'status' => CategoryStatus::class,
            'sort_order' => 'integer',
        ];
    }
}
