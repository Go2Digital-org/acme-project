<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Factory;

use DateTime;
use Faker\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Category\Domain\Model\Category;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Campaign Factory for Hexagonal Architecture.
 *
 * @extends Factory<Campaign>
 *
 * @property Generator $faker
 *
 * @method static static new(array<string, mixed> $attributes = [])
 */
final class CampaignFactory extends Factory
{
    /** @var class-string<Campaign> */
    protected $model = Campaign::class;

    /** @return array<array-key, mixed> */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-3 months', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, '+6 months');
        $goalAmount = fake()->randomFloat(2, 1000, 100000);

        // Determine status based on dates and random factors
        $status = $this->determineStatus($startDate, $endDate);
        $currentAmount = $this->calculateCurrentAmount($goalAmount, $status);

        // Generate title and description
        $englishTitle = $this->generateCampaignTitle();
        $englishDescription = $this->generateCampaignDescription();

        return [
            'uuid' => (string) Str::uuid(),
            'title' => ['en' => $englishTitle],
            'description' => ['en' => $englishDescription],
            'goal_amount' => $goalAmount,
            'current_amount' => $currentAmount,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'category_id' => function () {
                // Try to use existing category first to avoid circular dependencies
                $existing = Category::inRandomOrder()->first();
                if ($existing) {
                    return $existing->id;
                }

                // Create a simple category without using factory to avoid circular deps
                $slugs = ['education', 'health', 'environment', 'community'];
                $slug = fake()->randomElement($slugs);

                return Category::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => ['en' => ucfirst($slug)],
                        'description' => ['en' => "Category for {$slug} campaigns"],
                        'slug' => $slug,
                    ]
                )->id;
            },
            'organization_id' => function () {
                // Use existing organization first to avoid circular dependencies
                $existing = Organization::inRandomOrder()->first();
                if ($existing) {
                    return $existing->id;
                }

                // Create minimal organization without factory
                return Organization::create([
                    'name' => fake()->company(),
                    'registration_number' => fake()->uuid(),
                    'tax_id' => fake()->uuid(),
                    'category' => 'environmental',
                    'email' => fake()->companyEmail(),
                    'phone' => fake()->phoneNumber(),
                    'address' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'country' => fake()->country(),
                    'is_verified' => true,
                ])->id;
            },
            'user_id' => function () {
                // Use existing user first to avoid circular dependencies
                $existing = User::inRandomOrder()->first();
                if ($existing) {
                    return $existing->id;
                }

                // Create minimal user without factory
                return User::create([
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ])->id;
            },
            'completed_at' => $status === CampaignStatus::COMPLETED ?
                fake()->dateTimeBetween($startDate, $endDate) : null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::ACTIVE,
            'start_date' => fake()->dateTimeBetween('-1 month', '-1 day'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        $goalAmount = fake()->randomFloat(2, 1000, 50000);
        // Avoid DST issues by using safe hours (12:00 PM)
        $completedAt = fake()->dateTimeBetween('-6 months', '-1 week');
        $completedAt->setTime(12, 0, 0);

        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::COMPLETED,
            'goal_amount' => $goalAmount,
            // Ensure current_amount doesn't exceed goal_amount to prevent percentage overflow
            'current_amount' => $goalAmount,
            'start_date' => fake()->dateTimeBetween('-8 months', '-2 months'),
            'end_date' => fake()->dateTimeBetween($completedAt, '+1 month'),
            'completed_at' => $completedAt,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::CANCELLED,
            'start_date' => fake()->dateTimeBetween('-6 months', '-3 months'),
            'end_date' => fake()->dateTimeBetween('-2 months', '-1 week'),
            'completed_at' => null,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::DRAFT,
            'start_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+2 months', '+8 months'),
            'current_amount' => 0,
            'completed_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::CANCELLED,
            'start_date' => fake()->dateTimeBetween('-3 months', '-1 month'),
            'end_date' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'completed_at' => null,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::PAUSED,
            'start_date' => fake()->dateTimeBetween('-2 months', '-1 week'),
            'end_date' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'completed_at' => null,
        ]);
    }

    public function nearingGoal(): static
    {
        $goalAmount = fake()->randomFloat(2, 10000, 50000);
        // Ensure percentage doesn't exceed 95% to prevent overflow in goal_percentage calculation
        $currentAmount = $goalAmount * fake()->randomFloat(2, 0.8, 0.95);

        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::ACTIVE,
            'goal_amount' => $goalAmount,
            'current_amount' => $currentAmount,
            'start_date' => fake()->dateTimeBetween('-2 months', '-1 month'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+3 months'),
        ]);
    }

    public function withPartialProgress(): static
    {
        $goalAmount = fake()->randomFloat(2, 10000, 100000);
        $currentAmount = $goalAmount * fake()->randomFloat(2, 0.1, 0.6);

        return $this->state(fn (array $attributes): array => [
            'status' => CampaignStatus::ACTIVE,
            'goal_amount' => $goalAmount,
            'current_amount' => $currentAmount,
        ]);
    }

    public function education(): static
    {
        $titles = [
            'Books for Rural Schools',
            'Computer Lab Upgrade',
            'Scholarship Fund for Students',
            'STEM Education Initiative',
            'Adult Literacy Program',
        ];

        $selectedTitle = fake()->randomElement($titles);

        return $this->state(fn (array $attributes): array => [
            'title' => ['en' => $selectedTitle],
            'description' => ['en' => 'Supporting education initiatives that transform lives and communities.'],
            'category_id' => Category::where('slug', 'education')->first()?->id,
        ]);
    }

    public function healthcare(): static
    {
        $titles = [
            'Mobile Health Clinic',
            'Medical Equipment Fund',
            'Mental Health Support',
        ];

        $selectedTitle = fake()->randomElement($titles);

        return $this->state(fn (array $attributes): array => [
            'title' => ['en' => $selectedTitle],
            'description' => ['en' => 'Improving healthcare access for underserved communities.'],
            'category_id' => Category::where('slug', 'health')->first()?->id,
        ]);
    }

    public function environment(): static
    {
        $titles = [
            'Clean Ocean Initiative',
            'Renewable Energy for Schools',
            'Plant a Million Trees',
        ];

        $selectedTitle = fake()->randomElement($titles);

        return $this->state(fn (array $attributes): array => [
            'title' => ['en' => $selectedTitle],
            'description' => ['en' => 'Protecting our environment for future generations.'],
            'category_id' => Category::where('slug', 'environment')->first()?->id,
        ]);
    }

    public function community(): static
    {
        $titles = [
            'Community Center Renovation',
            'Youth Development Program',
            'Community Garden Project',
        ];

        $selectedTitle = fake()->randomElement($titles);

        return $this->state(fn (array $attributes): array => [
            'title' => ['en' => $selectedTitle],
            'description' => ['en' => 'Strengthening communities through local development.'],
            'category_id' => Category::where('slug', 'community')->first()?->id,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_date' => fake()->dateTimeBetween('-1 week', '-1 day'),
            'end_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'status' => CampaignStatus::ACTIVE,
        ]);
    }

    public function longTerm(): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_date' => fake()->dateTimeBetween('-1 month', '-1 day'),
            'end_date' => fake()->dateTimeBetween('+6 months', '+2 years'),
            'status' => CampaignStatus::ACTIVE,
        ]);
    }

    public function highGoal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'goal_amount' => fake()->randomFloat(2, 100000, 1000000),
        ]);
    }

    public function lowGoal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'goal_amount' => fake()->randomFloat(2, 500, 5000),
        ]);
    }

    private function determineStatus(DateTime $startDate, DateTime $endDate): CampaignStatus
    {
        $now = new DateTime;

        // 10% chance of being draft
        if (fake()->boolean(10)) {
            return CampaignStatus::DRAFT;
        }

        // 5% chance of being cancelled
        if (fake()->boolean(5)) {
            return CampaignStatus::CANCELLED;
        }

        // Date-based logic
        if ($startDate > $now) {
            return CampaignStatus::DRAFT;
        }

        if ($endDate < $now) {
            // 70% chance of being completed if ended, 30% expired
            return fake()->boolean(70) ? CampaignStatus::COMPLETED : CampaignStatus::CANCELLED;
        }

        // Currently running - 90% active, 10% chance of early completion
        return fake()->boolean(90) ? CampaignStatus::ACTIVE : CampaignStatus::COMPLETED;
    }

    private function calculateCurrentAmount(float $goalAmount, CampaignStatus $status): float
    {
        return match ($status) {
            // Completed campaigns should exactly meet their goal amount
            CampaignStatus::COMPLETED => $goalAmount,
            // Active campaigns start at 0 - donations will determine actual amount
            CampaignStatus::ACTIVE => 0,
            // Cancelled campaigns might have some donations before cancellation (max 30% of goal)
            CampaignStatus::CANCELLED => $goalAmount * fake()->randomFloat(2, 0, 0.3),
            // Draft campaigns have no donations
            CampaignStatus::DRAFT => 0,
            default => 0,
        };
    }

    private function generateCampaignTitle(): string
    {
        $verbs = ['Support', 'Fund', 'Build', 'Create', 'Establish', 'Launch', 'Develop', 'Improve'];
        $subjects = [
            'Education for All', 'Community Health Center', 'Clean Water Project',
            'Youth Empowerment Program', 'Environmental Conservation', 'Medical Equipment Fund',
            'School Infrastructure', 'Job Training Center', 'Food Security Initiative',
            'Digital Learning Platform', 'Healthcare Access Program', 'Community Garden',
        ];

        $verb = fake()->randomElement($verbs);
        $subject = fake()->randomElement($subjects);

        return "{$verb} {$subject}";
    }

    private function generateCampaignDescription(): string
    {
        $templates = [
            'Join us in making a lasting impact on communities through this vital initiative. Your contribution will help us create positive change and improve lives.',
            'Together, we can build a better future for those who need it most. Every donation brings us closer to achieving our goal and creating meaningful change.',
            'This campaign addresses critical needs in our community and provides sustainable solutions for long-term impact. Your support makes all the difference.',
            'Help us transform lives and strengthen communities through this important project. With your support, we can create lasting positive change.',
            'Be part of a movement that creates real impact. Your contribution will help us reach our goal and make a meaningful difference in people\'s lives.',
        ];

        return fake()->randomElement($templates);
    }
}
