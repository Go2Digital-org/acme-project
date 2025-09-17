<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Modules\Bookmark\Domain\Model\Bookmark;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Specification\EligibleForDonationSpecification;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Campaign\Infrastructure\Laravel\Factory\CampaignFactory;
use Modules\Category\Domain\Model\Category;
use Modules\Donation\Domain\Model\Donation;
use Modules\Organization\Domain\Model\Organization;
use Modules\Shared\Domain\Contract\CampaignInterface;
use Modules\Shared\Domain\Contract\UserInterface;
use Modules\Shared\Domain\Traits\HasTranslations;
use Modules\Shared\Domain\ValueObject\Money;
use Modules\User\Infrastructure\Laravel\Models\User;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property string $uuid
 * @property string $title
 * @property string|null $slug
 * @property string $description
 * @property numeric $goal_amount
 * @property numeric $target_amount
 * @property numeric $current_amount
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $deadline
 * @property Carbon|null $completed_at
 * @property CampaignStatus $status
 * @property string|null $category
 * @property int|null $category_id
 * @property string $visibility
 * @property string|null $featured_image
 * @property bool $has_corporate_matching
 * @property bool $is_featured
 * @property int $priority
 * @property int $shares_count
 * @property int $views_count
 * @property numeric|null $corporate_matching_rate
 * @property numeric|null $max_corporate_matching
 * @property int $organization_id
 * @property int $user_id
 * @property bool $send_confirmation_copies_to_organizer
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $submitted_for_approval_at
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property int|null $rejected_by
 * @property Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, Bookmark> $bookmarks
 * @property int|null $bookmarks_count
 * @property Category|null $categoryModel
 * @property UserInterface $creator
 * @property Collection<int, Donation> $donations
 * @property int|null $donations_count
 * @property Organization $organization
 *
 * @method static Builder|Campaign where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static CampaignFactory factory($count = null, $state = [])
 * @method static Builder<static>|Campaign newModelQuery()
 * @method static Builder<static>|Campaign newQuery()
 * @method static Builder<static>|Campaign query()
 * @method static Builder<static>|Campaign whereCategory($value)
 * @method static Builder<static>|Campaign whereCompletedAt($value)
 * @method static Builder<static>|Campaign whereCorporateMatchingRate($value)
 * @method static Builder<static>|Campaign whereCreatedAt($value)
 * @method static Builder<static>|Campaign whereCurrentAmount($value)
 * @method static Builder<static>|Campaign whereDescription($value)
 * @method static Builder<static>|Campaign whereDescriptionTranslations($value)
 * @method static Builder<static>|Campaign whereEndDate($value)
 * @method static Builder<static>|Campaign whereFeaturedImage($value)
 * @method static Builder<static>|Campaign whereGoalAmount($value)
 * @method static Builder<static>|Campaign whereHasCorporateMatching($value)
 * @method static Builder<static>|Campaign whereId($value)
 * @method static Builder<static>|Campaign whereMaxCorporateMatching($value)
 * @method static Builder<static>|Campaign whereOrganizationId($value)
 * @method static Builder<static>|Campaign whereSlug($value)
 * @method static Builder<static>|Campaign whereStartDate($value)
 * @method static Builder<static>|Campaign whereStatus($value)
 * @method static Builder<static>|Campaign whereTitle($value)
 * @method static Builder<static>|Campaign whereTitleTranslations($value)
 * @method static Builder<static>|Campaign whereUpdatedAt($value)
 * @method static Builder<static>|Campaign whereVisibility($value)
 *
 * @mixin Model
 */
class Campaign extends Model implements Auditable, CampaignInterface
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    use AuditableTrait;

    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    use HasTranslations;
    use Searchable;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'description',
        'goal_amount',
        'target_amount',
        'current_amount',
        'start_date',
        'end_date',
        'deadline',
        'status',
        'category',
        'category_id',
        'visibility',
        'featured_image',
        'has_corporate_matching',
        'is_featured',
        'priority',
        'shares_count',
        'views_count',
        'corporate_matching_rate',
        'max_corporate_matching',
        'submitted_for_approval_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'organization_id',
        'user_id',
        'metadata',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    /**
     * Translatable fields.
     *
     * @var list<string>
     */
    protected array $translatable = [
        'title',
        'description',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'title' => 'array',  // Cast to array for translations
        'description' => 'array',  // Cast to array for translations
        'goal_amount' => 'float',
        'target_amount' => 'float',
        'current_amount' => 'float',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
        'submitted_for_approval_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'status' => CampaignStatus::class,
        'has_corporate_matching' => 'boolean',
        'is_featured' => 'boolean',
        'priority' => 'integer',
        'shares_count' => 'integer',
        'views_count' => 'integer',
        'corporate_matching_rate' => 'float',
        'max_corporate_matching' => 'float',
        'metadata' => 'array',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        // Default attributes will be added as migrations are created
    ];

    /** @var list<string> */
    protected $appends = [
        'featured_image_url',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($campaign) {
            if (empty($campaign->uuid)) {
                $campaign->uuid = (string) Str::uuid();
            }
        });
    }

    // Business logic in domain - refactored to use specification pattern
    public function canAcceptDonation(): bool
    {
        $donationEligibilitySpec = new EligibleForDonationSpecification;

        return $donationEligibilitySpec->isSatisfiedBy($this);
    }

    public function addDonation(Money $amount): void
    {
        if (! $this->canAcceptDonation()) {
            throw CampaignException::cannotAcceptDonation($this);
        }

        $this->current_amount += $amount->amount;

        $goalAmount = (float) ($this->target_amount ?? $this->goal_amount ?? 0);
        if ($this->current_amount >= $goalAmount) {
            $this->markAsCompleted();
        }
    }

    public function markAsCompleted(): void
    {
        $this->status = CampaignStatus::COMPLETED;
        $this->completed_at = now();
    }

    public function getProgressPercentage(): float
    {
        // Use target_amount as primary, fallback to goal_amount for compatibility
        $goalAmount = (float) ($this->target_amount ?? $this->goal_amount ?? 0);

        if ($goalAmount === 0.0) {
            return 0.0;
        }

        $currentAmount = (float) $this->current_amount;

        return min(100.0, ($currentAmount / $goalAmount) * 100);
    }

    public function isActive(): bool
    {
        return $this->status->isActive() &&
               $this->end_date?->isFuture() === true &&
               $this->start_date?->isPast() === true;
    }

    public function getDaysRemaining(): int
    {
        if (! $this->status->isActive() || ! $this->end_date) {
            return 0;
        }

        // Calculate days remaining, allowing negative values for expired campaigns
        $daysRemaining = (int) now()->diffInDays($this->end_date, false);

        // If the campaign has ended, return 0 instead of negative
        return max(0, $daysRemaining);
    }

    public function getRemainingAmount(): float
    {
        $goalAmount = (float) ($this->target_amount ?? $this->goal_amount ?? 0);

        return max(0, $goalAmount - $this->current_amount);
    }

    public function hasReachedGoal(): bool
    {
        $goalAmount = (float) ($this->target_amount ?? $this->goal_amount ?? 0);

        return $this->current_amount >= $goalAmount;
    }

    public function validateDateRange(): void
    {
        if ($this->start_date && $this->end_date && $this->start_date->isAfter($this->end_date)) {
            throw CampaignException::invalidDateRange(
                $this->start_date->format('Y-m-d'),
                $this->end_date->format('Y-m-d'),
            );
        }
    }

    public function validateGoalAmount(): void
    {
        $goalAmount = (float) ($this->target_amount ?? $this->goal_amount ?? 0);
        if ($goalAmount <= 0) {
            throw CampaignException::invalidGoalAmount($goalAmount);
        }
    }

    // Approval-related helper methods
    public function isAwaitingApproval(): bool
    {
        return $this->status === CampaignStatus::PENDING_APPROVAL;
    }

    public function wasApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function wasRejected(): bool
    {
        return $this->rejected_at !== null;
    }

    public function getApprover(): ?UserInterface
    {
        return $this->approver;
    }

    public function getRejecter(): ?UserInterface
    {
        return $this->rejecter;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejection_reason;
    }

    // Relationships
    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Employee relationship (alias for creator for factory compatibility)
     *
     * @return BelongsTo<User, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the category of the campaign.
     *
     * @return BelongsTo<Category, $this>
     */
    public function categoryModel(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the featured image URL attribute.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (! $this->featured_image) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->featured_image, FILTER_VALIDATE_URL)) {
            return $this->featured_image;
        }

        // If it starts with /storage, it's a local storage path
        if (str_starts_with($this->featured_image, '/storage/')) {
            return asset($this->featured_image);
        }

        // Otherwise, assume it's a storage path that needs the storage prefix
        return asset('storage/' . $this->featured_image);
    }

    /**
     * Get the campaign image URL (alias for getFeaturedImageUrlAttribute).
     */
    public function getImageUrl(): ?string
    {
        return $this->getFeaturedImageUrlAttribute();
    }

    /**
     * Get the target amount (with fallback to goal_amount for compatibility).
     */
    public function getTargetAmount(): float
    {
        return (float) ($this->target_amount ?? $this->goal_amount ?? 0);
    }

    /**
     * Get the deadline (with fallback to end_date for compatibility).
     */
    public function getDeadline(): ?Carbon
    {
        return $this->deadline ?? $this->end_date;
    }

    /**
     * Retrieve the model for route binding, including soft-deleted campaigns for authors.
     * Supports ID-based routing without redirects.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Find by ID (numeric value)
        if (is_numeric($value)) {
            $campaign = static::where('id', $value)->first();
            if ($campaign) {
                return $campaign;
            }
        }

        // Find by UUID
        if (is_string($value) && strlen($value) === 36) {
            $campaign = static::where('uuid', $value)->first();
            if ($campaign) {
                return $campaign;
            }
        }

        // Find by slug
        if (is_string($value)) {
            $campaign = static::where('slug', $value)->first();
            if ($campaign) {
                return $campaign;
            }
        }

        // Check soft-deleted campaigns
        $campaign = $this->findSoftDeletedCampaign($value);
        if (! $campaign instanceof \Modules\Campaign\Domain\Model\Campaign) {
            return null;
        }

        // If soft-deleted campaign found, check if current user is the author
        if (! $campaign->trashed()) {
            return $campaign;
        }

        $user = auth()->user();

        // Only allow authors to view their deleted campaigns
        if (! $user || $campaign->user_id !== $user->id) {
            return null; // This will trigger a 404
        }

        return $campaign;
    }

    private function findSoftDeletedCampaign(string|int $value): ?Campaign
    {
        if (is_numeric($value)) {
            return static::withTrashed()->where('id', $value)->first();
        }

        // At this point, $value is definitely a string
        if (strlen($value) === 36) {
            return static::withTrashed()->where('uuid', $value)->first();
        }

        return static::withTrashed()->where('slug', $value)->first();
    }

    /**
     * Get all donations for this campaign.
     * Note: Uses string reference to maintain architectural boundaries.
     *
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Get all bookmarks for this campaign.
     *
     * @return HasMany<Bookmark, $this>
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    // Scout Search Configuration

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        // Check if relationships are already loaded to prevent N+1 queries
        // If not loaded, we'll use null fallbacks instead of triggering lazy loading
        $organizationName = $this->relationLoaded('organization') && $this->organization
            ? $this->organization->getName()
            : null;

        $creatorName = $this->relationLoaded('creator') && $this->creator
            ? $this->creator->getName()
            : null;

        $categoryName = $this->relationLoaded('categoryModel') && $this->categoryModel
            ? $this->categoryModel->getName()
            : null;

        $categorySlug = $this->relationLoaded('categoryModel') && $this->categoryModel
            ? $this->categoryModel->slug
            : null;

        // Calculate goal percentage for filtering
        $goalPercentage = 0;
        if ($this->goal_amount > 0) {
            $goalPercentage = round(($this->current_amount / $this->goal_amount) * 100, 2);
        }

        return [
            'id' => $this->id,
            'title' => $this->getTitle(),
            'description' => mb_substr(strip_tags((string) $this->getDescription()), 0, 1000),
            'goal_amount' => $this->getTargetAmount(),
            'target_amount' => $this->getTargetAmount(),
            'current_amount' => (float) $this->current_amount,
            'goal_percentage' => $goalPercentage,
            'status' => $this->status->value,
            'category' => $this->category, // Legacy string category
            'category_id' => $this->category_id,
            'category_name' => $categoryName,
            'category_slug' => $categorySlug,
            'visibility' => $this->visibility,
            // Use timestamps for numeric comparisons in Meilisearch
            'start_date' => $this->start_date?->timestamp,
            'end_date' => $this->end_date?->timestamp,
            'created_at' => $this->created_at?->timestamp,
            'updated_at' => $this->updated_at?->timestamp,
            'organization_id' => $this->organization_id,
            'organization_name' => $organizationName,
            'user_id' => $this->user_id,
            'creator_name' => $creatorName,
            'is_featured' => (bool) $this->is_featured,
            'is_active' => $this->isActive(),
            'donations_count' => $this->donations_count ?? 0,
            'progress_percentage' => $goalPercentage,
            'days_remaining' => $this->end_date ? max(0, now()->diffInDays($this->end_date, false)) : null,
            'has_reached_goal' => $goalPercentage >= 100,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return config('scout.prefix', 'acme_') . 'campaigns';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        // Only index active and completed campaigns for public search
        // Draft campaigns will be handled via database fallback in user searches
        return in_array($this->status, [CampaignStatus::ACTIVE, CampaignStatus::COMPLETED], true);
    }

    /**
     * Make the given model instance searchable with eager loading.
     */
    public function searchable(): mixed
    {
        // Skip loading relationships to avoid memory issues
        // The toSearchableArray method will handle missing relations gracefully

        // Call the parent trait method to actually make this model searchable
        return parent::searchable(); // @phpstan-ignore-line
    }

    /**
     * Get the Scout engine for custom bulk indexing.
     */
    public static function searchableUsing(): mixed
    {
        return parent::searchableUsing(); // @phpstan-ignore-line
    }

    /**
     * Chunk campaigns for indexing with eager loading.
     */
    public static function makeAllSearchableUsing(mixed $engine): void
    {
        static::with(['organization', 'creator', 'categoryModel'])
            ->whereIn('status', [CampaignStatus::ACTIVE->value, CampaignStatus::COMPLETED->value])
            ->chunk(500, function ($models) {
                $models->searchable();
            });
    }

    // CampaignInterface implementation
    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->getTranslation('title') ?? 'Untitled';
    }

    public function getDescription(): ?string
    {
        return $this->getTranslation('description');
    }

    public function getOrganizationId(): int
    {
        return $this->organization_id;
    }

    public function getUrl(): string
    {
        return route('campaigns.show', $this->id);
    }

    /**
     * Get the number of days the campaign has been active.
     */
    public function getDaysActive(): int
    {
        $now = now();

        if (! $this->start_date || $now->lt($this->start_date)) {
            return 0;
        }

        return (int) $this->start_date->diffInDays($now);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CampaignFactory
    {
        return CampaignFactory::new();
    }
}
