<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Campaign\Domain\Model\Bookmark;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @extends Factory<Bookmark>
 */
class BookmarkFactory extends Factory
{
    protected $model = Bookmark::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'campaign_id' => Campaign::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    public function forCampaign(Campaign $campaign): self
    {
        return $this->state(fn (array $attributes): array => [
            'campaign_id' => $campaign->id,
        ]);
    }

    public function recent(): self
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'updated_at' => fn ($attrs) => $attrs['created_at'],
        ]);
    }

    public function old(): self
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => fake()->dateTimeBetween('-1 year', '-1 month'),
            'updated_at' => fn ($attrs) => $attrs['created_at'],
        ]);
    }
}
