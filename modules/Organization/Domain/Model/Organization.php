<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\Searchable;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Organization\Domain\Exception\OrganizationException;
use Modules\Organization\Infrastructure\Laravel\Factory\OrganizationFactory;
use Modules\Shared\Domain\Contract\OrganizationInterface;
use Modules\Shared\Domain\Traits\HasTranslations;
use Modules\User\Infrastructure\Laravel\Models\User;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\DatabaseConfig;

/**
 * @property int $id
 * @property array<string, mixed> $name
 * @property string $uuid
 * @property string|null $website
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property string $status
 * @property string|null $slug
 * @property bool $is_active
 * @property bool $is_verified
 * @property string|null $registration_number
 * @property string|null $tax_id
 * @property string|null $category
 * @property string|null $type
 * @property Carbon|null $verification_date
 * @property Carbon|null $founded_date
 * @property array<string, mixed>|null $description
 * @property array<string, mixed>|null $mission
 * @property string|null $logo
 * @property string|null $logo_url
 * @property array<string, mixed>|null $name_translations
 * @property array<string, mixed>|null $description_translations
 * @property array<string, mixed>|null $mission_translations
 * @property array<string, mixed>|null $social_links
 * @property array<string, mixed>|null $settings
 * @property string|null $subdomain
 * @property string|null $database
 * @property string|null $provisioning_status
 * @property string|null $provisioning_error
 * @property Carbon|null $provisioned_at
 * @property array<string, mixed>|null $tenant_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, Campaign> $campaigns
 * @property int|null $campaigns_count
 * @property Collection<int, User> $employees
 * @property int|null $employees_count
 *
 * @method static Builder|Organization where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static OrganizationFactory factory($count = null, $state = [])
 * @method static Builder<static>|Organization newModelQuery()
 * @method static Builder<static>|Organization newQuery()
 * @method static Builder<static>|Organization query()
 * @method static Builder<static>|Organization whereAddress($value)
 * @method static Builder<static>|Organization whereCategory($value)
 * @method static Builder<static>|Organization whereCity($value)
 * @method static Builder<static>|Organization whereCountry($value)
 * @method static Builder<static>|Organization whereCreatedAt($value)
 * @method static Builder<static>|Organization whereDescription($value)
 * @method static Builder<static>|Organization whereEmail($value)
 * @method static Builder<static>|Organization whereId($value)
 * @method static Builder<static>|Organization whereIsActive($value)
 * @method static Builder<static>|Organization whereIsVerified($value)
 * @method static Builder<static>|Organization whereLogoUrl($value)
 * @method static Builder<static>|Organization whereMission($value)
 * @method static Builder<static>|Organization whereName($value)
 * @method static Builder<static>|Organization wherePhone($value)
 * @method static Builder<static>|Organization wherePostalCode($value)
 * @method static Builder<static>|Organization whereRegistrationNumber($value)
 * @method static Builder<static>|Organization whereTaxId($value)
 * @method static Builder<static>|Organization whereType($value)
 * @method static Builder<static>|Organization whereUpdatedAt($value)
 * @method static Builder<static>|Organization whereVerificationDate($value)
 * @method static Builder<static>|Organization whereWebsite($value)
 *
 * @mixin Model
 */
class Organization extends Model implements Auditable, OrganizationInterface, TenantWithDatabase
{
    use AuditableTrait;
    use HasDatabase;
    use HasDomains;

    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use HasTranslations;
    use Searchable;
    use SoftDeletes;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'int';

    protected $fillable = [
        // 'uuid', // Not in database schema
        'name',
        'slug',
        'description',
        'mission',
        // 'logo', // Not in database schema
        'logo_url',
        'name_translations',
        'description_translations',
        'mission_translations',
        'website',
        'email',
        'phone',
        'address',
        'city',
        // 'state', // Not in current schema
        'postal_code',
        'country',
        'status',
        'registration_number',
        'tax_id',
        'category',
        'type',
        'is_active',
        'is_verified',
        'verification_date',
        'founded_date',
        // 'social_links', // Not in current schema
        // 'settings', // Not in current schema
        // Tenant-specific fields
        'subdomain',
        'database',
        'provisioning_status',
        'provisioning_error',
        'provisioned_at',
        'tenant_data',
    ];

    /**
     * Translatable fields.
     *
     * @var list<string>
     */
    protected $translatable = [
        'name',
        'description',
        'mission',
    ];

    // Getter methods for translatable fields
    public function getName(): string
    {
        return $this->getTranslation('name') ?? 'Unnamed Organization';
    }

    public function getDescription(): ?string
    {
        return $this->getTranslation('description');
    }

    public function getMission(): ?string
    {
        return $this->getTranslation('mission');
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getIsVerified(): bool
    {
        return (bool) $this->is_verified;
    }

    public function getVerificationDate(): ?Carbon
    {
        return $this->verification_date;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logo_url;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registration_number;
    }

    public function getTaxId(): ?string
    {
        return $this->tax_id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getFoundedDate(): ?Carbon
    {
        return $this->founded_date;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIsActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updated_at;
    }

    // Business logic methods
    public function canCreateCampaigns(): bool
    {
        return $this->is_active && $this->is_verified;
    }

    /**
     * Check if the organization/tenant is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Check if provisioning has failed.
     */
    public function hasFailed(): bool
    {
        return $this->provisioning_status === 'failed';
    }

    /**
     * Get admin data from tenant_data.
     *
     * @return array<string, mixed>|null
     */
    public function getAdminData(): ?array
    {
        $data = $this->tenant_data ?? [];

        return $data['admin'] ?? null;
    }

    /**
     * Set admin data in tenant_data.
     *
     * @param  array<string, mixed>  $adminData
     */
    public function setAdminData(array $adminData): void
    {
        $data = $this->tenant_data ?? [];
        $data['admin'] = $adminData;
        $this->tenant_data = $data;
        $this->save();
    }

    public function activate(): void
    {
        if ($this->is_active) {
            throw OrganizationException::alreadyActive($this);
        }

        $this->is_active = true;
    }

    public function deactivate(): void
    {
        if (! $this->is_active) {
            throw OrganizationException::alreadyInactive($this);
        }

        $this->is_active = false;
    }

    public function getStatusColor(): string
    {
        return match ($this->getStatus()) {
            'active' => 'success',
            'unverified' => 'warning',
            'inactive' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->getStatus()) {
            'active' => 'Active',
            'unverified' => 'Pending Verification',
            'inactive' => 'Inactive',
            default => 'Unknown',
        };
    }

    /**
     * Get the organization's computed status based on is_active and is_verified.
     */
    public function getStatus(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if (! $this->is_verified) {
            return 'unverified';
        }

        return 'active';
    }

    /**
     * Check if the organization is eligible for verification.
     */
    public function isEligibleForVerification(): bool
    {
        // Organization must be active
        if (! $this->is_active) {
            return false;
        }

        // Must have required basic information
        $name = $this->getName();
        if ($name === '' || $name === '0' || trim($name) === '' || $name === 'Unnamed Organization') {
            return false;
        }

        // Must have registration number
        if (empty($this->registration_number) || trim($this->registration_number) === '') {
            return false;
        }

        // Must have tax ID
        if (empty($this->tax_id) || trim($this->tax_id) === '') {
            return false;
        }

        // Must have valid email
        if (empty($this->email) || trim($this->email) === '') {
            return false;
        }

        // Must have category
        return ! empty($this->category) && trim($this->category) !== '';
    }

    /**
     * Verify the organization.
     *
     * @throws OrganizationException
     */
    public function verify(): void
    {
        if ($this->is_verified) {
            throw OrganizationException::alreadyVerified($this);
        }

        $this->is_verified = true;
        $this->verification_date = now();
    }

    /**
     * Unverify the organization.
     *
     * @throws OrganizationException
     */
    public function unverify(): void
    {
        if (! $this->is_verified) {
            throw OrganizationException::notVerified($this);
        }

        $this->is_verified = false;
        $this->verification_date = null;
    }

    /**
     * Start provisioning process for tenant.
     */
    public function startProvisioning(): void
    {
        $this->update([
            'provisioning_status' => 'provisioning',
            'provisioning_error' => null,
        ]);
    }

    /**
     * Mark tenant as successfully provisioned.
     */
    public function markAsProvisioned(): void
    {
        $this->update([
            'provisioning_status' => 'active',
            'provisioned_at' => now(),
            'provisioning_error' => null,
        ]);
    }

    /**
     * Mark tenant provisioning as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'provisioning_status' => 'failed',
            'provisioning_error' => $error,
        ]);
    }

    // Relationships
    /**
     * @return HasMany<Campaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the tenant's domains.
     *
     * @return HasMany<Domain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'tenant_id');
    }

    /**
     * Get the tenant's key for use in database naming.
     * Returns the ID as string for queue job compatibility.
     */
    public function getTenantKey(): string
    {
        // Return the ID as string for tenancy package compatibility
        // The queue tenancy bootstrapper uses this to identify tenants
        return (string) $this->id;
    }

    /**
     * Get the name of the tenant key column.
     * We use 'id' as the key name to match the primary key.
     */
    public function getTenantKeyName(): string
    {
        // Return 'id' to match the actual primary key column
        // The tenancy package will use this to find tenants
        return 'id';
    }

    /**
     * Get the subdomain-based key for database naming.
     * This is used for generating database names and internal prefixes.
     */
    protected function getSubdomainKey(): string
    {
        if ($this->subdomain) {
            // Replace hyphens with underscores for database compatibility
            return str_replace('-', '_', $this->subdomain);
        }

        // Fallback to ID if no subdomain
        return (string) $this->id;
    }

    /**
     * Get internal tenant data.
     */
    public function getInternal(string $key): mixed
    {
        $data = $this->tenant_data ?? [];

        return $data[$key] ?? null;
    }

    /**
     * Set internal tenant data.
     */
    public function setInternal(string $key, mixed $value): static
    {
        $data = $this->tenant_data ?? [];
        $data[$key] = $value;
        $this->tenant_data = $data;

        return $this;
    }

    /**
     * Run a callback with tenant initialization.
     */
    public function run(callable $callback): mixed
    {
        $result = null;
        tenancy()->runForMultiple([$this], function () use ($callback, &$result): void { // @phpstan-ignore-line
            $result = $callback();
        });

        return $result;
    }

    /**
     * Get the database attribute value.
     * This accessor ensures Laravel knows 'database' is an attribute, not a relationship.
     *
     * @return Attribute<string|null, string|null>
     */
    public function databaseName(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->attributes['database'] ?? null, set: fn (?string $value): array => ['database' => $value]);
    }

    // The database() method from HasDatabase trait returns DatabaseConfig
    // This is different from the database attribute which stores the database name

    /**
     * Get the tenant's database name as string.
     */
    public function getDatabaseName(): string
    {
        if ($this->attributes['database'] ?? null) {
            return $this->attributes['database'];
        }

        $prefix = config('tenancy.database.prefix', 'tenant_');
        $suffix = config('tenancy.database.suffix', '');

        return $prefix . $this->getSubdomainKey() . $suffix;
    }

    /**
     * Override the tenancy package's database name method.
     * This is used by the tenancy package when switching contexts.
     */
    public function tenantDatabaseName(): ?string
    {
        return $this->getDatabaseName();
    }

    /**
     * Override the database configuration from HasDatabase trait.
     * This ensures the correct database name is used during tenant context switching.
     */
    public function database(): DatabaseConfig
    {
        return new DatabaseConfig($this);
    }

    /**
     * Get the internal prefix for tenant-aware functionality.
     */
    public function internalPrefix(): string
    {
        return 'tenant_' . $this->getSubdomainKey() . '_';
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        // Only load campaigns if the table exists (for test compatibility)
        if (Schema::hasTable('campaigns')) {
            $this->load(['campaigns']);
        }

        return [
            'id' => $this->id,
            'name' => $this->getName(),
            'name_en' => $this->getTranslation('name', 'en') ?? $this->getName(),
            'name_fr' => $this->getTranslation('name', 'fr'),
            'name_de' => $this->getTranslation('name', 'de'),
            'description' => $this->getDescription(),
            'description_en' => $this->getTranslation('description', 'en'),
            'description_fr' => $this->getTranslation('description', 'fr'),
            'description_de' => $this->getTranslation('description', 'de'),
            'mission' => $this->getMission(),
            'mission_en' => $this->getTranslation('mission', 'en'),
            'mission_fr' => $this->getTranslation('mission', 'fr'),
            'mission_de' => $this->getTranslation('mission', 'de'),
            'website' => $this->website,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'category' => $this->category,
            'type' => $this->type,
            'status' => $this->getStatus(),
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'verification_date' => $this->verification_date?->toIso8601String(),
            'campaigns_count' => Schema::hasTable('campaigns') ? $this->campaigns->count() : 0,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Computed fields for filtering
            'location' => trim($this->city . ', ' . $this->country, ', '),
            'full_address' => trim($this->address . ' ' . $this->city . ' ' . $this->postal_code . ' ' . $this->country),
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Modify the query used to retrieve models when making all searchable.
     */
    protected function makeAllSearchableUsing(mixed $query): mixed
    {
        // Only eager load campaigns if the table exists
        if (Schema::hasTable('campaigns')) {
            return $query->with(['campaigns']);
        }

        return $query;
    }

    /**
     * Scope a query to only include verified organizations.
     *
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to only include unverified organizations.
     *
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('is_verified', false);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'mission' => 'array',
            'name_translations' => 'array',
            'description_translations' => 'array',
            'mission_translations' => 'array',
            'social_links' => 'array',
            'settings' => 'array',
            'tenant_data' => 'array',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'verification_date' => 'datetime',
            'founded_date' => 'datetime',
            'provisioned_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
