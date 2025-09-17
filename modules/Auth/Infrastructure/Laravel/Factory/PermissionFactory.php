<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Permission;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $resources = ['campaign', 'donation', 'organization', 'user', 'role', 'page', 'social_media', 'payment_gateway'];
        $actions = ['view', 'view_any', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'replicate', 'reorder'];

        $resource = fake()->randomElement($resources);
        $action = fake()->randomElement($actions);

        return [
            'name' => $action . '_' . $resource,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forCampaigns(): static
    {
        return $this->state(function (array $attributes): array {
            $actions = ['view_any_campaign', 'view_campaign', 'create_campaign', 'update_campaign', 'delete_campaign', 'delete_any_campaign'];

            return [
                'name' => fake()->randomElement($actions),
            ];
        });
    }

    public function forDonations(): static
    {
        return $this->state(function (array $attributes): array {
            $actions = ['view_any_donation', 'view_donation', 'create_donation', 'update_donation', 'delete_donation', 'delete_any_donation'];

            return [
                'name' => fake()->randomElement($actions),
            ];
        });
    }

    public function forOrganizations(): static
    {
        return $this->state(function (array $attributes): array {
            $actions = ['view_any_organization', 'view_organization', 'create_organization', 'update_organization', 'delete_organization', 'delete_any_organization'];

            return [
                'name' => fake()->randomElement($actions),
            ];
        });
    }

    public function forUsers(): static
    {
        return $this->state(function (array $attributes): array {
            $actions = ['view_any_user', 'view_user', 'create_user', 'update_user', 'delete_user', 'delete_any_user'];

            return [
                'name' => fake()->randomElement($actions),
            ];
        });
    }

    public function forRoles(): static
    {
        return $this->state(function (array $attributes): array {
            $actions = ['view_any_role', 'view_role', 'create_role', 'update_role', 'delete_role', 'delete_any_role'];

            return [
                'name' => fake()->randomElement($actions),
            ];
        });
    }

    public function forPages(): static
    {
        return $this->state(function (array $attributes): array {
            $actions = ['view_any_page', 'view_page', 'create_page', 'update_page', 'delete_page', 'delete_any_page'];

            return [
                'name' => fake()->randomElement($actions),
            ];
        });
    }

    public function viewOnly(): static
    {
        return $this->state(function (array $attributes): array {
            $resources = ['campaign', 'donation', 'organization', 'user', 'page'];
            $resource = fake()->randomElement($resources);
            $action = fake()->randomElement(['view', 'view_any']);

            return [
                'name' => $action . '_' . $resource,
            ];
        });
    }

    public function crud(): static
    {
        return $this->state(function (array $attributes): array {
            $resources = ['campaign', 'donation', 'organization', 'user'];
            $resource = fake()->randomElement($resources);
            $action = fake()->randomElement(['create', 'update', 'delete']);

            return [
                'name' => $action . '_' . $resource,
            ];
        });
    }

    public function withApiGuard(): static
    {
        return $this->state(fn (array $attributes): array => [
            'guard_name' => 'api',
        ]);
    }

    public function withSanctumGuard(): static
    {
        return $this->state(fn (array $attributes): array => [
            'guard_name' => 'sanctum',
        ]);
    }
}
