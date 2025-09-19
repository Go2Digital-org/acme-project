<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Audit\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Infrastructure\Audit\Models\AuditLog;

/**
 * @extends Factory<AuditLog>
 */
final class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'user_name' => fake()->name(),
            'user_email' => fake()->email(),
            'user_role' => fake()->randomElement(['admin', 'user', 'manager']),
            'action' => fake()->randomElement([
                'campaign.create',
                'campaign.update',
                'donation.create',
                'security.login_success',
            ]),
            'entity_type' => fake()->randomElement(['campaign', 'donation', 'organization']),
            'entity_id' => fake()->numberBetween(1, 1000),
            'old_values' => [],
            'new_values' => ['status' => 'active'],
            'metadata' => [
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'severity' => fake()->randomElement(['low', 'medium', 'high']),
            ],
            'performed_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }
}
