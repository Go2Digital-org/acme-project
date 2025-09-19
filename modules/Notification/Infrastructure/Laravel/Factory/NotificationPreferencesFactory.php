<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Modules\Notification\Domain\Model\NotificationPreferences;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @extends Factory<NotificationPreferences>
 */
class NotificationPreferencesFactory extends Factory
{
    protected $model = NotificationPreferences::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel_preferences' => $this->generateChannelPreferences(),
            'frequency_preferences' => $this->generateFrequencyPreferences(),
            'type_preferences' => $this->generateTypePreferences(),
            'preferences' => $this->generateGeneralPreferences(),
            'quiet_hours' => $this->generateQuietHours(),
            'timezone' => fake()->timezone(),
            'digest_frequency' => fake()->numberBetween(1, 7), // Daily to weekly
            'global_email_enabled' => true,
            'global_sms_enabled' => fake()->boolean(70), // 70% chance enabled
            'global_push_enabled' => true,
            'metadata' => $this->generateMetadata(),
        ];
    }

    public function allChannelsEnabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'global_email_enabled' => true,
            'global_sms_enabled' => true,
            'global_push_enabled' => true,
            'channel_preferences' => [
                'campaign.created' => [
                    'email' => true,
                    'sms' => true,
                    'push' => true,
                    'database' => true,
                ],
                'donation.received' => [
                    'email' => true,
                    'sms' => false,
                    'push' => true,
                    'database' => true,
                ],
            ],
        ]);
    }

    public function emailOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'global_email_enabled' => true,
            'global_sms_enabled' => false,
            'global_push_enabled' => false,
            'channel_preferences' => [
                'campaign.created' => [
                    'email' => true,
                    'sms' => false,
                    'push' => false,
                    'database' => true,
                ],
                'donation.received' => [
                    'email' => true,
                    'sms' => false,
                    'push' => false,
                    'database' => true,
                ],
            ],
        ]);
    }

    public function withQuietHours(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quiet_hours' => [
                'enabled' => true,
                'start_time' => '22:00',
                'end_time' => '07:00',
            ],
        ]);
    }

    public function withoutQuietHours(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quiet_hours' => [
                'enabled' => false,
            ],
        ]);
    }

    public function dailyDigest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'digest_frequency' => 1,
            'frequency_preferences' => [
                'campaign.created' => 'daily',
                'donation.received' => 'daily',
                'system.alert' => 'immediate',
            ],
        ]);
    }

    public function weeklyDigest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'digest_frequency' => 7,
            'frequency_preferences' => [
                'campaign.created' => 'weekly',
                'donation.received' => 'weekly',
                'system.alert' => 'immediate',
            ],
        ]);
    }

    public function immediateOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'digest_frequency' => 0,
            'frequency_preferences' => [
                'campaign.created' => 'immediate',
                'donation.received' => 'immediate',
                'system.alert' => 'immediate',
                'organization.verified' => 'immediate',
            ],
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
            'timezone' => $user->timezone ?? 'UTC',
        ]);
    }

    public function timezone(string $timezone): static
    {
        return $this->state(fn (array $attributes): array => [
            'timezone' => $timezone,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function generateChannelPreferences(): array
    {
        return [
            'campaign.created' => [
                'email' => true,
                'sms' => fake()->boolean(30),
                'push' => true,
                'database' => true,
            ],
            'campaign.goal_reached' => [
                'email' => true,
                'sms' => fake()->boolean(50),
                'push' => true,
                'database' => true,
            ],
            'donation.received' => [
                'email' => true,
                'sms' => false,
                'push' => true,
                'database' => true,
            ],
            'donation.confirmation' => [
                'email' => true,
                'sms' => fake()->boolean(20),
                'push' => false,
                'database' => true,
            ],
            'system.alert' => [
                'email' => true,
                'sms' => fake()->boolean(10),
                'push' => true,
                'database' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateFrequencyPreferences(): array
    {
        return [
            'campaign.created' => fake()->randomElement(['immediate', 'hourly', 'daily']),
            'campaign.milestone' => 'immediate',
            'donation.received' => 'immediate',
            'system.alert' => 'immediate',
            'organization.verified' => 'immediate',
            'admin.alert' => fake()->randomElement(['immediate', 'daily']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateTypePreferences(): array
    {
        return [
            'campaign.created' => [
                'enabled' => true,
            ],
            'campaign.milestone' => [
                'enabled' => true,
            ],
            'donation.received' => [
                'enabled' => true,
            ],
            'marketing.newsletter' => [
                'enabled' => fake()->boolean(60),
                'opted_out_at' => fake()->boolean(40) ? Carbon::instance(fake()->dateTimeThisYear())->toISOString() : null,
            ],
            'system.maintenance' => [
                'enabled' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateGeneralPreferences(): array
    {
        return [
            'language' => fake()->randomElement(['en', 'nl', 'fr', 'de']),
            'time_format' => fake()->randomElement(['12h', '24h']),
            'date_format' => fake()->randomElement(['MM/DD/YYYY', 'DD/MM/YYYY', 'YYYY-MM-DD']),
            'sound_enabled' => fake()->boolean(80),
            'vibration_enabled' => fake()->boolean(70),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateQuietHours(): array
    {
        $hasQuietHours = fake()->boolean(70);

        if (! $hasQuietHours) {
            return ['enabled' => false];
        }

        $startHour = fake()->numberBetween(20, 23);
        $endHour = fake()->numberBetween(6, 9);

        return [
            'enabled' => true,
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $endHour),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateMetadata(): array
    {
        return [
            'version' => '1.0',
            'last_updated_by' => 'user',
            'preferences_source' => fake()->randomElement(['web', 'mobile', 'api']),
            'onboarding_completed' => fake()->boolean(90),
            'preferences_migrated' => fake()->boolean(80),
        ];
    }
}
