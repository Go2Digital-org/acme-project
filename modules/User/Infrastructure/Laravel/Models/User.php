<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Models;

use Exception;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Scout\Searchable;
use Modules\Bookmark\Domain\Model\Bookmark;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Organization\Domain\Model\Organization;
use Modules\Shared\Domain\Contract\UserInterface;
use Modules\User\Infrastructure\Laravel\Factory\UserFactory;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Eloquent Model.
 *
 * Laravel Eloquent model for persistence layer.
 * This is the infrastructure implementation used by repositories.
 *
 * @property int $id
 * @property string $name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $status
 * @property string $role
 * @property string|null $profile_photo_path
 * @property string $password
 * @property int|null $organization_id
 * @property string|null $google_id
 * @property string|null $two_factor_secret
 * @property array<array-key, mixed>|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $account_locked
 * @property string|null $account_locked_at
 * @property string|null $lock_reason
 * @property int $failed_login_attempts
 * @property string|null $last_failed_login
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property int $mfa_enabled
 * @property string|null $mfa_secret
 * @property string|null $mfa_backup_codes
 * @property string|null $mfa_enabled_at
 * @property string|null $mfa_last_used
 * @property string|null $password_history
 * @property string|null $password_changed_at
 * @property string|null $password_expires_at
 * @property int $password_change_required
 * @property int $max_concurrent_sessions
 * @property string|null $trusted_devices
 * @property int $data_processing_restricted
 * @property string|null $data_restriction_reason
 * @property string|null $data_restricted_at
 * @property int $personal_data_anonymized
 * @property string|null $anonymized_at
 * @property string|null $consent_records
 * @property string|null $last_consent_at
 * @property array<string, mixed>|null $notification_preferences
 * @property array<string, mixed>|null $preferences
 * @property string|null $privacy_settings
 * @property string $preferred_language
 * @property string $timezone
 * @property string|null $job_title
 * @property string|null $department
 * @property string|null $user_id
 * @property string|null $manager_email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $hire_date
 * @property int $security_score
 * @property int $risk_level
 * @property string|null $last_security_assessment
 * @property int $requires_background_check
 * @property string|null $background_check_completed_at
 * @property string|null $compliance_certifications
 * @property string|null $deleted_at
 * @property bool $wants_donation_confirmations
 * @property string|null $locale
 * @property Collection<int, Bookmark> $bookmarks
 * @property int|null $bookmarks_count
 * @property Collection<int, Campaign> $campaigns
 * @property int|null $campaigns_count
 * @property Collection<int, Donation> $donations
 * @property int|null $donations_count
 * @property string $profile_photo_url
 * @property DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property int|null $notifications_count
 * @property Organization|null $organization
 * @property Collection<int, Permission> $permissions
 * @property int|null $permissions_count
 * @property Collection<int, Role> $roles
 * @property int|null $roles_count
 * @property Collection<int, PersonalAccessToken> $tokens
 * @property int|null $tokens_count
 *
 * @method static Builder|User where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User permission($permissions, $without = false)
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static Builder<static>|User whereAccountLocked($value)
 * @method static Builder<static>|User whereAccountLockedAt($value)
 * @method static Builder<static>|User whereAddress($value)
 * @method static Builder<static>|User whereAnonymizedAt($value)
 * @method static Builder<static>|User whereBackgroundCheckCompletedAt($value)
 * @method static Builder<static>|User whereComplianceCertifications($value)
 * @method static Builder<static>|User whereConsentRecords($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereDataProcessingRestricted($value)
 * @method static Builder<static>|User whereDataRestrictedAt($value)
 * @method static Builder<static>|User whereDataRestrictionReason($value)
 * @method static Builder<static>|User whereDeletedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereFailedLoginAttempts($value)
 * @method static Builder<static>|User whereFirstName($value)
 * @method static Builder<static>|User whereGoogleId($value)
 * @method static Builder<static>|User whereHireDate($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereJobTitle($value)
 * @method static Builder<static>|User whereLastConsentAt($value)
 * @method static Builder<static>|User whereLastFailedLogin($value)
 * @method static Builder<static>|User whereLastLoginAt($value)
 * @method static Builder<static>|User whereLastLoginIp($value)
 * @method static Builder<static>|User whereLastName($value)
 * @method static Builder<static>|User whereLastSecurityAssessment($value)
 * @method static Builder<static>|User whereLockReason($value)
 * @method static Builder<static>|User whereManagerEmail($value)
 * @method static Builder<static>|User whereMaxConcurrentSessions($value)
 * @method static Builder<static>|User whereMfaBackupCodes($value)
 * @method static Builder<static>|User whereMfaEnabled($value)
 * @method static Builder<static>|User whereMfaEnabledAt($value)
 * @method static Builder<static>|User whereMfaLastUsed($value)
 * @method static Builder<static>|User whereMfaSecret($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User whereNotificationPreferences($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User wherePasswordChangeRequired($value)
 * @method static Builder<static>|User wherePasswordChangedAt($value)
 * @method static Builder<static>|User wherePasswordExpiresAt($value)
 * @method static Builder<static>|User wherePasswordHistory($value)
 * @method static Builder<static>|User wherePersonalDataAnonymized($value)
 * @method static Builder<static>|User wherePhone($value)
 * @method static Builder<static>|User wherePreferredLanguage($value)
 * @method static Builder<static>|User wherePrivacySettings($value)
 * @method static Builder<static>|User whereProfilePhotoPath($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereRequiresBackgroundCheck($value)
 * @method static Builder<static>|User whereRiskLevel($value)
 * @method static Builder<static>|User whereRole($value)
 * @method static Builder<static>|User whereSecurityScore($value)
 * @method static Builder<static>|User whereStatus($value)
 * @method static Builder<static>|User whereTimezone($value)
 * @method static Builder<static>|User whereTrustedDevices($value)
 * @method static Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static Builder<static>|User whereTwoFactorSecret($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @method static Builder<static>|User withoutPermission($permissions)
 * @method static Builder<static>|User withoutRole($roles, $guard = null)
 *
 * @mixin Model
 */
class User extends Authenticatable implements Auditable, FilamentUser, UserInterface
{
    use AuditableTrait;
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use Notifiable;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'email_verified_at',
        'status',
        'role',
        'department',
        'user_id',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'remember_token',
        'account_locked',
        'account_locked_at',
        'lock_reason',
        'failed_login_attempts',
        'last_failed_login',
        'last_login_at',
        'last_login_ip',
        'mfa_enabled',
        'mfa_secret',
        'mfa_backup_codes',
        'mfa_enabled_at',
        'mfa_last_used',
        'password_history',
        'password_changed_at',
        'password_expires_at',
        'password_change_required',
        'max_concurrent_sessions',
        'trusted_devices',
        'data_processing_restricted',
        'data_restriction_reason',
        'data_restricted_at',
        'personal_data_anonymized',
        'anonymized_at',
        'consent_records',
        'last_consent_at',
        'notification_preferences',
        'preferences',
        'privacy_settings',
        'preferred_language',
        'timezone',
        'job_title',
        'manager_email',
        'phone',
        'address',
        'hire_date',
        'security_score',
        'risk_level',
        'last_security_assessment',
        'requires_background_check',
        'background_check_completed_at',
        'compliance_certifications',
        'profile_photo_path',
        'organization_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        // Default attributes will be added as migrations are created
    ];

    /**
     * Get the URL to the user's profile photo.
     *
     * @return Attribute<string, never>
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->profile_photo_path
            ? Storage::disk('public')->url($this->profile_photo_path)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF');
    }

    /**
     * Delete the user's profile photo.
     */
    public function deleteProfilePhoto(): void
    {
        if ($this->profile_photo_path && Storage::disk('public')->exists($this->profile_photo_path)) {
            Storage::disk('public')->delete($this->profile_photo_path);
        }

        $this->forceFill([
            'profile_photo_path' => null,
        ])->save();
    }

    /**
     * Get recovery codes for two-factor authentication.
     */
    /** @return array<int, string> */
    public function recoveryCodes(): array
    {
        $codes = $this->two_factor_recovery_codes ?? [];

        // Ensure we return a properly typed string array
        return array_values(array_filter($codes, 'is_string'));
    }

    /**
     * Replace recovery codes for two-factor authentication.
     */
    public function replaceRecoveryCodes(): void
    {
        /** @var array<int, string> $recoveryCodes */
        $recoveryCodes = collect(range(1, 8))->map(fn (): string => str_replace('-', '', (string) Str::uuid()))->toArray();

        $this->forceFill([
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();
    }

    /**
     * Relationship with donations.
     *
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'donor_id');
    }

    /**
     * Relationship with campaigns.
     *
     * @return HasMany<Campaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'creator_id');
    }

    /**
     * Relationship with bookmarks.
     *
     * @return HasMany<Bookmark, $this>
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * Relationship with organization.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user's ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the user's full name.
     */
    public function getFullName(): string
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name . ' ' . $this->last_name;
        }

        return $this->name;
    }

    /**
     * Get the user's email as string.
     */
    public function getEmailString(): string
    {
        return $this->email;
    }

    /**
     * Get the user's email.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the user's role.
     */
    public function getRole(): ?string
    {
        if (! empty($this->role)) {
            return $this->role;
        }

        $firstRole = $this->getRoleNames()->first();

        return is_string($firstRole) ? $firstRole : null;
    }

    /**
     * Get the user's profile photo path.
     */
    public function getProfilePhotoPath(): ?string
    {
        return $this->profile_photo_path;
    }

    /**
     * Get the email verified at timestamp.
     */
    public function getEmailVerifiedAt(): ?Carbon
    {
        return $this->email_verified_at;
    }

    /**
     * Get the created at timestamp.
     */
    public function getCreatedAt(): ?Carbon
    {
        return $this->created_at;
    }

    /**
     * Get the updated at timestamp.
     */
    public function getUpdatedAt(): ?Carbon
    {
        return $this->updated_at;
    }

    /**
     * Check if the user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get a preference value.
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        $preferences = $this->preferences ?? [];

        return $preferences[$key] ?? $default;
    }

    /**
     * Set a preference value.
     */
    public function setPreference(string $key, mixed $value): void
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        $this->preferences = $preferences;
        $this->save();
    }

    /**
     * Update multiple preferences at once.
     *
     * @param  array<string, mixed>  $preferences
     */
    public function updatePreferences(array $preferences): void
    {
        $this->preferences = $preferences;
        $this->save();
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        // Only load organization if not already loaded and if model exists in DB
        if (! $this->relationLoaded('organization') && $this->exists) {
            $this->load(['organization']);
        }

        // Get organization name with fallback
        $organizationName = null;
        if ($this->organization) {
            try {
                $organizationName = $this->organization->getName();
            } catch (Exception) {
                // If getName() fails, try direct access to name attribute
                $name = $this->organization->name;
                if (is_array($name)) {
                    $organizationName = $name['en'] ?? array_values($name)[0] ?? null;
                } elseif (is_string($name)) {
                    $organizationName = $name;
                }
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->getFullName(),
            'email' => $this->email,
            'status' => $this->status,
            'role' => $this->getRole(),
            'job_title' => $this->job_title,
            'department' => $this->department,
            'manager_email' => $this->manager_email,
            'phone' => $this->phone,
            'address' => $this->address,
            'user_id' => $this->user_id,
            'hire_date' => $this->hire_date,
            'preferred_language' => $this->preferred_language,
            'timezone' => $this->timezone,
            'organization_id' => $this->organization_id,
            'organization_name' => $organizationName,
            'is_active' => $this->isActive(),
            'account_locked' => $this->isAccountLocked(),
            'email_verified' => $this->email_verified_at !== null,
            'mfa_enabled' => (bool) $this->mfa_enabled,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'active' && ! $this->isPersonalDataAnonymized();
    }

    /**
     * Modify the query used to retrieve models when making all searchable.
     */
    protected function makeAllSearchableUsing(mixed $query): mixed
    {
        return $query->with(['organization']);
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Only allow super_admin role to access the admin panel
        return $this->hasRole('super_admin');
    }

    /**
     * Get the name to display in Filament.
     */
    public function getFilamentName(): string
    {
        // Return just the name without "Welcome" prefix
        return $this->name;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'preferences' => 'array',
            'two_factor_recovery_codes' => 'array',
        ];
    }

    /**
     * Get the user's organization ID.
     */
    public function getOrganizationId(): ?int
    {
        return $this->organization_id;
    }

    /**
     * Check if the user account is locked.
     */
    public function isAccountLocked(): bool
    {
        return (bool) $this->account_locked;
    }

    /**
     * Check if the user's data processing is restricted.
     */
    public function isDataProcessingRestricted(): bool
    {
        return (bool) $this->data_processing_restricted;
    }

    /**
     * Check if the user's personal data is anonymized.
     */
    public function isPersonalDataAnonymized(): bool
    {
        return (bool) $this->personal_data_anonymized;
    }

    /**
     * Get the user's organization.
     */
    public function getOrganization(): mixed
    {
        return $this->organization;
    }

    /**
     * Get the user's display name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->can($permission);
    }

    /**
     * Get user settings/preferences.
     *
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->preferences ?? [];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
