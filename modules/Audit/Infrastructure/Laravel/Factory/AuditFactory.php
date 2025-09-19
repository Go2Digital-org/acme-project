<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Audit\Domain\Model\Audit;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @extends Factory<Audit>
 */
class AuditFactory extends Factory
{
    protected $model = Audit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $events = ['created', 'updated', 'deleted', 'restored'];
        $auditableTypes = [
            Campaign::class,
            Donation::class,
            Organization::class,
            User::class,
        ];

        $event = fake()->randomElement($events);
        $auditableType = fake()->randomElement($auditableTypes);

        return [
            'user_type' => User::class,
            'user_id' => User::factory(),
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => fake()->numberBetween(1, 1000),
            'old_values' => $this->generateOldValues($event),
            'new_values' => $this->generateNewValues($event),
            'url' => fake()->optional(0.8)->url(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'tags' => fake()->optional(0.3)->word(),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fn ($attributes) => $attributes['created_at'],
        ];
    }

    public function created(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event' => 'created',
            'old_values' => null,
            'new_values' => $this->generateNewValues('created'),
        ]);
    }

    public function updated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event' => 'updated',
            'old_values' => $this->generateOldValues('updated'),
            'new_values' => $this->generateNewValues('updated'),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event' => 'deleted',
            'old_values' => $this->generateOldValues('deleted'),
            'new_values' => null,
        ]);
    }

    public function campaign(): static
    {
        return $this->state(fn (array $attributes): array => [
            'auditable_type' => Campaign::class,
            'auditable_id' => Campaign::factory(),
        ]);
    }

    public function donation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'auditable_type' => Donation::class,
            'auditable_id' => Donation::factory(),
        ]);
    }

    public function organization(): static
    {
        return $this->state(fn (array $attributes): array => [
            'auditable_type' => Organization::class,
            'auditable_id' => Organization::factory(),
        ]);
    }

    public function user(): static
    {
        return $this->state(fn (array $attributes): array => [
            'auditable_type' => User::class,
            'auditable_id' => User::factory(),
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function generateOldValues(string $event): ?array
    {
        if ($event === 'created') {
            return null;
        }

        return [
            'title' => fake()->sentence(),
            'status' => 'active',
            'amount' => fake()->randomFloat(2, 10, 1000),
            'updated_at' => fake()->dateTimeBetween('-2 months', '-1 month')->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function generateNewValues(string $event): ?array
    {
        if ($event === 'deleted') {
            return null;
        }

        return [
            'title' => fake()->sentence(),
            'status' => fake()->randomElement(['active', 'inactive', 'pending']),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
