<?php

declare(strict_types=1);

namespace Modules\Team\Domain\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Modules\Team\Domain\ValueObject\Role;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * TeamMember Domain Model
 *
 * Represents the membership relationship between a user and a team
 *
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property Role $role
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property int|null $invited_by
 * @property Carbon|null $invited_at
 * @property Carbon|null $invitation_accepted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Team $team
 * @property User $user
 * @property User|null $inviter
 */
class TeamMember extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
        'invited_by',
        'invited_at',
        'invitation_accepted_at',
    ];

    protected $casts = [
        'role' => Role::class,
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'invited_at' => 'datetime',
        'invitation_accepted_at' => 'datetime',
    ];

    // Domain Logic

    public function hasPermission(string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    public function canManageMembers(): bool
    {
        return $this->role->canManageMembers();
    }

    public function canManageCampaigns(): bool
    {
        return $this->role->canManageCampaigns();
    }

    public function canViewAnalytics(): bool
    {
        return $this->role->canViewAnalytics();
    }

    public function isOwner(): bool
    {
        return $this->role === Role::OWNER;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN;
    }

    public function canPromoteTo(Role $newRole): bool
    {
        // Can only promote to lower or equal hierarchy
        return $newRole->getHierarchyLevel() <= $this->role->getHierarchyLevel();
    }

    public function canDemoteTo(Role $newRole): bool
    {
        // Can demote to any role lower than current
        return $newRole->getHierarchyLevel() < $this->role->getHierarchyLevel();
    }

    public function getDaysActive(): int
    {
        $endDate = $this->left_at ?? now();

        return (int) $this->joined_at->diffInDays($endDate);
    }

    public function isActive(): bool
    {
        return $this->left_at === null;
    }

    public function leave(): void
    {
        if ($this->isOwner()) {
            throw new InvalidArgumentException('Owner cannot leave team without transferring ownership');
        }

        $this->left_at = now();
        $this->save();
    }

    // Relationships

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // Scopes
    /**
     * @param  Builder<TeamMember>  $query
     * @return Builder<TeamMember>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('left_at');
    }

    /**
     * @param  Builder<TeamMember>  $query
     * @return Builder<TeamMember>
     */
    public function scopeByRole(Builder $query, Role $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * @param  Builder<TeamMember>  $query
     * @return Builder<TeamMember>
     */
    public function scopeOwners(Builder $query): Builder
    {
        return $query->where('role', Role::OWNER);
    }

    /**
     * @param  Builder<TeamMember>  $query
     * @return Builder<TeamMember>
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', Role::ADMIN);
    }
}
