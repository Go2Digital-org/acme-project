<?php

declare(strict_types=1);

namespace Modules\Team\Domain\Model;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Organization\Domain\Model\Organization;
use Modules\Team\Domain\Exception\TeamException;
use Modules\Team\Domain\ValueObject\Role;
use Modules\Team\Domain\ValueObject\TeamId;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Team Domain Model
 *
 * Represents a team within an organization that can manage campaigns collectively
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $slug
 * @property int $organization_id
 * @property int $owner_id
 * @property bool $is_active
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property Organization $organization
 * @property User $owner
 * @property Collection<int, TeamMember> $members
 */
class Team extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'organization_id',
        'owner_id',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Domain Logic

    public function getId(): TeamId
    {
        return new TeamId($this->id);
    }

    public function addMember(User $user, Role $role): TeamMember
    {
        // Check if user is already a member
        if ($this->hasMember($user->id)) {
            throw TeamException::userAlreadyMember($this->id, $user->id);
        }

        // Check organization membership
        if ($user->organization_id !== $this->organization_id) {
            throw TeamException::userNotInOrganization($user->id, $this->organization_id);
        }

        $member = new TeamMember([
            'team_id' => $this->id,
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
        ]);

        $member->save();

        return $member;
    }

    public function removeMember(int $userId): void
    {
        if (! $this->hasMember($userId)) {
            throw TeamException::userNotMember($this->id, $userId);
        }

        // Cannot remove owner
        if ($this->owner_id === $userId) {
            throw TeamException::cannotRemoveOwner($this->id);
        }

        $this->members()->where('user_id', $userId)->delete();
    }

    public function changeMemberRole(int $userId, Role $newRole): void
    {
        $member = $this->members()->where('user_id', $userId)->first();

        if (! $member) {
            throw TeamException::userNotMember($this->id, $userId);
        }

        // Cannot change owner role
        if ($this->owner_id === $userId && $newRole !== Role::OWNER) {
            throw TeamException::cannotChangeOwnerRole($this->id);
        }

        $member->role = $newRole;
        $member->save();
    }

    public function hasMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function getMember(int $userId): ?TeamMember
    {
        return $this->members()->where('user_id', $userId)->first();
    }

    public function getMemberRole(int $userId): ?Role
    {
        $member = $this->getMember($userId);

        return $member instanceof TeamMember ? $member->role : null;
    }

    public function canUserManageMembers(int $userId): bool
    {
        $role = $this->getMemberRole($userId);

        return $role && $role->canManageMembers();
    }

    public function canUserManageCampaigns(int $userId): bool
    {
        $role = $this->getMemberRole($userId);

        return $role && $role->canManageCampaigns();
    }

    public function isOwner(int $userId): bool
    {
        return $this->owner_id === $userId;
    }

    public function transferOwnership(int $newOwnerId): void
    {
        if (! $this->hasMember($newOwnerId)) {
            throw TeamException::userNotMember($this->id, $newOwnerId);
        }

        // Change current owner to admin
        if ($this->owner_id) {
            $this->changeMemberRole($this->owner_id, Role::ADMIN);
        }

        // Set new owner
        $this->owner_id = $newOwnerId;
        $this->changeMemberRole($newOwnerId, Role::OWNER);
        $this->save();
    }

    public function activate(): void
    {
        if ($this->is_active) {
            return;
        }

        $this->is_active = true;
        $this->save();
    }

    public function deactivate(): void
    {
        if (! $this->is_active) {
            return;
        }

        $this->is_active = false;
        $this->save();
    }

    public function getMemberCount(): int
    {
        return $this->members()->count();
    }

    public function getActiveMemberCount(): int
    {
        return $this->members()
            ->whereHas('user', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->count();
    }

    // Relationships

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<TeamMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Get active members only
     *
     * @return HasMany<TeamMember, $this>
     */
    public function activeMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class)
            ->whereHas('user', function ($query) {
                $query->whereNull('deleted_at');
            });
    }

    // Validation

    public function validateName(): void
    {
        if (in_array(trim($this->name), ['', '0'], true)) {
            throw TeamException::invalidName('Team name cannot be empty');
        }

        if (strlen($this->name) > 100) {
            throw TeamException::invalidName('Team name cannot exceed 100 characters');
        }
    }

    public function validateSlug(): void
    {
        if (in_array(trim($this->slug), ['', '0'], true)) {
            throw TeamException::invalidSlug('Team slug cannot be empty');
        }

        if (! preg_match('/^[a-z0-9-]+$/', $this->slug)) {
            throw TeamException::invalidSlug('Team slug can only contain lowercase letters, numbers, and hyphens');
        }
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team) {
            $team->validateName();
            $team->validateSlug();
        });

        static::updating(function (Team $team) {
            $team->validateName();
            $team->validateSlug();
        });
    }
}
