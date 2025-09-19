<?php

declare(strict_types=1);

namespace Modules\Team\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Team\Domain\Model\Team;
use Modules\Team\Domain\Model\TeamMember;
use Modules\Team\Domain\ValueObject\Role;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    protected $model = TeamMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $joinedAt = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement([
                Role::MEMBER,
                Role::MANAGER,
                Role::ADMIN,
                Role::VIEWER,
            ]),
            'joined_at' => $joinedAt,
            'left_at' => null,
            'invited_by' => null,
            'invited_at' => null,
            'invitation_accepted_at' => null,
            'created_at' => $joinedAt,
            'updated_at' => $joinedAt,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::OWNER,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::ADMIN,
        ]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::MANAGER,
        ]);
    }

    public function member(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::MEMBER,
        ]);
    }

    public function viewer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::VIEWER,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'left_at' => null,
        ]);
    }

    public function left(): static
    {
        $joinedAt = fake()->dateTimeBetween('-1 year', '-1 month');
        $leftAt = fake()->dateTimeBetween($joinedAt, 'now');

        return $this->state(fn (array $attributes): array => [
            'joined_at' => $joinedAt,
            'left_at' => $leftAt,
        ]);
    }

    public function invited(): static
    {
        $invitedAt = fake()->dateTimeBetween('-30 days', 'now');

        return $this->state(fn (array $attributes): array => [
            'invited_by' => User::factory(),
            'invited_at' => $invitedAt,
            'invitation_accepted_at' => null,
            'joined_at' => null,
        ]);
    }

    public function invitationAccepted(): static
    {
        $invitedAt = fake()->dateTimeBetween('-30 days', '-1 day');
        $acceptedAt = fake()->dateTimeBetween($invitedAt, 'now');

        return $this->state(fn (array $attributes): array => [
            'invited_by' => User::factory(),
            'invited_at' => $invitedAt,
            'invitation_accepted_at' => $acceptedAt,
            'joined_at' => $acceptedAt,
        ]);
    }

    public function forTeam(Team $team): static
    {
        return $this->state(fn (array $attributes): array => [
            'team_id' => $team->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    public function invitedBy(User $inviter): static
    {
        $invitedAt = fake()->dateTimeBetween('-30 days', 'now');

        return $this->state(fn (array $attributes): array => [
            'invited_by' => $inviter->id,
            'invited_at' => $invitedAt,
        ]);
    }

    public function recent(): static
    {
        $joinedAt = fake()->dateTimeBetween('-30 days', 'now');

        return $this->state(fn (array $attributes): array => [
            'joined_at' => $joinedAt,
            'created_at' => $joinedAt,
        ]);
    }

    public function longTerm(): static
    {
        $joinedAt = fake()->dateTimeBetween('-2 years', '-6 months');

        return $this->state(fn (array $attributes): array => [
            'joined_at' => $joinedAt,
            'created_at' => $joinedAt,
        ]);
    }

    public function withRole(Role $role): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => $role,
        ]);
    }
}
