<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Factory;

use Faker\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Laravel\Traits\SafeDateGeneration;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @extends Factory<User>
 *
 * @property Generator $faker
 *
 * @method static static new(array<string, mixed> $attributes = [])
 */
class UserFactory extends Factory
{
    use SafeDateGeneration;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<User>
     */
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $email = $this->generateACMEEmail($firstName, $lastName);

        return [
            'name' => "{$firstName} {$lastName}",
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'email_verified_at' => fake()->optional(0.95)->dateTimeBetween('-2 years', '-2 days') ? $this->safeDateTimeBetween('-2 years', '-2 days')->format('Y-m-d H:i:s') : null,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'organization_id' => 1, // Default to ACME Corp
            'status' => 'active',
            'role' => 'employee',
            'job_title' => fake()->randomElement([
                'Software Engineer',
                'Marketing Manager',
                'Senior Developer',
                'Product Manager',
                'Sales Representative',
                'UX Designer',
                'Data Analyst',
                'DevOps Engineer',
                'Project Manager',
                'HR Specialist',
            ]),
            'department' => fake()->randomElement([
                'Engineering',
                'Marketing',
                'Sales',
                'Human Resources',
                'Finance',
                'Product',
                'Customer Success',
                'Operations',
                'Legal',
                'Design',
            ]),
            'manager_email' => fake()->optional(0.7)->email(),
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'address' => fake()->optional(0.6)->address(),
            'hire_date' => fake()->optional(0.9)->dateTimeBetween('-5 years', '-1 month') ? $this->safeDateTimeBetween('-5 years', '-1 month')->format('Y-m-d H:i:s') : null,
            'preferred_language' => 'en',
            'timezone' => fake()->randomElement(['UTC', 'America/New_York', 'America/Los_Angeles', 'Europe/London']),
            'employee_id' => 'EMP' . str_pad((string) fake()->unique()->numberBetween(1, 9999), 3, '0', STR_PAD_LEFT),
            'created_at' => $this->safeDateTimeBetween('-2 years', '-2 days')->format('Y-m-d H:i:s'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'] ?? now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => $this->safeDateTimeBetween('-2 years', '-2 days'),
        ]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => fake()->name() . ' (Manager)',
        ]);
    }

    public function employee(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => fake()->name() . ' (Employee)',
        ]);
    }

    private function generateACMEEmail(string $firstName, string $lastName): string
    {
        $emailFormats = [
            strtolower($firstName) . '.' . strtolower($lastName),
            strtolower($firstName) . strtolower(substr($lastName, 0, 1)),
            strtolower(substr($firstName, 0, 1)) . '.' . strtolower($lastName),
            strtolower($firstName) . fake()->numberBetween(1, 99),
        ];

        $username = (string) fake()->randomElement($emailFormats);
        // Add a random suffix to ensure uniqueness
        $username .= '.' . fake()->numberBetween(100, 999);

        return $username . '@acme-corp.com';
    }
}
