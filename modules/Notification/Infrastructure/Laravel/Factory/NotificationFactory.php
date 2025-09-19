<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\ValueObject\NotificationChannel;
use Modules\Notification\Domain\ValueObject\NotificationPriority;
use Modules\Notification\Domain\ValueObject\NotificationStatus;
use Modules\Notification\Domain\ValueObject\NotificationType;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Factory for creating test notification instances.
 *
 * @extends Factory<Notification>
 */
final class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     */
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = NotificationType::all();
        $channels = NotificationChannel::all();
        $priorities = NotificationPriority::all();
        $statuses = NotificationStatus::all();

        return [
            'notifiable_type' => User::class,
            'notifiable_id' => User::factory(),
            'type' => fake()->randomElement($types),
            'data' => array_merge($this->generateNotificationData(), [
                'title' => fake()->sentence(4),
                'message' => fake()->sentence(10),
                'channel' => fake()->randomElement($channels),
                'priority' => fake()->randomElement($priorities),
                'status' => fake()->randomElement($statuses),
                'metadata' => $this->generateMetadata(),
                'sender_id' => fake()->boolean(70) ? User::factory()->create()->id : null,
                'scheduled_for' => fake()->boolean(30) ? fake()->dateTimeBetween('now', '+1 week')->format('Y-m-d H:i:s') : null,
                'sent_at' => fake()->boolean(60) ? fake()->dateTimeBetween('-1 week', 'now')->format('Y-m-d H:i:s') : null,
                'clicked_at' => fake()->boolean(20) ? fake()->dateTimeBetween('-1 week', 'now')->format('Y-m-d H:i:s') : null,
            ]),
            'read_at' => fake()->boolean(40) ? fake()->dateTimeBetween('-1 week', 'now')->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Create a notification for campaign milestone.
     */
    public function campaignMilestone(): self
    {
        return $this->state(function (array $attributes): array {
            $campaignId = fake()->randomNumber(5);

            return [
                'type' => NotificationType::CAMPAIGN_MILESTONE,
                'data' => [
                    'title' => 'Campaign Milestone Reached!',
                    'message' => 'Your campaign has reached 50% of its funding goal.',
                    'priority' => NotificationPriority::HIGH,
                    'campaign_id' => $campaignId,
                    'campaign_title' => fake()->company . ' CSR Initiative',
                    'milestone' => '50',
                    'current_amount' => fake()->numberBetween(5000, 15000),
                    'goal_amount' => fake()->numberBetween(20000, 50000),
                    'campaign_url' => '/campaigns/' . $campaignId,
                    'metadata' => [
                        'campaign_id' => $campaignId,
                        'source' => 'milestone_tracker',
                        'created_by' => 'system',
                    ],
                ],
            ];
        });
    }

    /**
     * Create a notification for large donation.
     */
    public function largeDonation(): self
    {
        return $this->state(function (array $attributes): array {
            $donationId = fake()->randomNumber(5);

            return [
                'type' => NotificationType::LARGE_DONATION,
                'data' => [
                    'title' => 'Large Donation Received!',
                    'message' => 'A large donation of $5,000 has been received for your campaign.',
                    'priority' => NotificationPriority::HIGH,
                    'channel' => NotificationChannel::REALTIME,
                    'donation_id' => $donationId,
                    'amount' => fake()->numberBetween(1000, 10000),
                    'donor_name' => fake()->boolean(70) ? fake()->name : 'Anonymous',
                    'campaign_id' => fake()->randomNumber(5),
                    'donation_url' => '/donations/' . $donationId,
                    'metadata' => [
                        'donation_id' => $donationId,
                        'source' => 'donation_processor',
                        'created_by' => 'system',
                    ],
                ],
            ];
        });
    }

    /**
     * Create a notification that requires approval.
     */
    public function approvalNeeded(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NotificationType::APPROVAL_NEEDED,
            'data' => [
                'title' => 'Campaign Needs Approval',
                'message' => 'A new campaign is awaiting your approval.',
                'priority' => NotificationPriority::URGENT,
                'campaign_id' => fake()->randomNumber(5),
                'campaign_title' => fake()->company . ' Sustainability Project',
                'submitter_name' => fake()->name,
                'review_url' => '/admin/campaigns/' . fake()->randomNumber(5),
                'metadata' => [
                    'actions' => [
                        [
                            'label' => 'Review Now',
                            'action' => 'review',
                            'url' => '/admin/campaigns/' . fake()->randomNumber(5),
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Create a security alert notification.
     */
    public function securityAlert(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NotificationType::SECURITY_ALERT,
            'data' => [
                'title' => 'Security Alert',
                'message' => 'Unusual activity detected on your account.',
                'priority' => NotificationPriority::CRITICAL,
                'channel' => NotificationChannel::REALTIME,
                'alert_type' => 'unusual_login',
                'ip_address' => fake()->ipv4,
                'location' => fake()->city . ', ' . fake()->country,
                'user_agent' => fake()->userAgent,
                'metadata' => [
                    'persistent' => true,
                    'severity' => 'high',
                ],
            ],
        ]);
    }

    /**
     * Create an unread notification.
     */
    public function unread(): self
    {
        return $this->state(fn (array $attributes): array => [
            'data' => array_merge($attributes['data'] ?? [], [
                'status' => NotificationStatus::SENT,
                'sent_at' => fake()->dateTimeBetween('-1 week', 'now')->format('Y-m-d H:i:s'),
                'clicked_at' => null,
            ]),
            'read_at' => null,
        ]);
    }

    /**
     * Create a read notification.
     */
    public function read(): self
    {
        return $this->state(function (array $attributes): array {
            $sentAt = fake()->dateTimeBetween('-1 week', 'now');
            $readAt = fake()->dateTimeBetween($sentAt, 'now');

            return [
                'data' => array_merge($attributes['data'] ?? [], [
                    'status' => NotificationStatus::SENT,
                    'sent_at' => $sentAt->format('Y-m-d H:i:s'),
                    'clicked_at' => fake()->boolean(30) ? fake()->dateTimeBetween($readAt, 'now')->format('Y-m-d H:i:s') : null,
                ]),
                'read_at' => $readAt->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * Create a failed notification.
     */
    public function failed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'data' => array_merge($attributes['data'] ?? [], [
                'status' => NotificationStatus::FAILED,
                'sent_at' => null,
                'metadata' => [
                    'error_message' => fake()->sentence,
                    'failed_at' => fake()->dateTimeBetween('-1 day', 'now')->format('c'),
                    'retry_count' => fake()->numberBetween(1, 3),
                ],
            ]),
            'read_at' => null,
        ]);
    }

    /**
     * Generate realistic notification data based on type.
     */
    /**
     * @return array<string, mixed>
     */
    private function generateNotificationData(): array
    {
        $dataTypes = [
            'campaign' => [
                'campaign_id' => fake()->randomNumber(5),
                'campaign_title' => fake()->company . ' Initiative',
                'campaign_url' => '/campaigns/' . fake()->randomNumber(5),
            ],
            'donation' => [
                'donation_id' => fake()->randomNumber(5),
                'amount' => fake()->numberBetween(25, 1000),
                'donor_name' => fake()->boolean(80) ? fake()->name : 'Anonymous',
                'donation_url' => '/donations/' . fake()->randomNumber(5),
            ],
            'system' => [
                'action' => fake()->randomElement(['login', 'password_change', 'profile_update']),
                'ip_address' => fake()->ipv4,
                'timestamp' => fake()->dateTimeThisMonth->format('c'),
            ],
        ];

        return fake()->randomElement($dataTypes);
    }

    /**
     * Generate notification metadata.
     */
    /**
     * @return array<string, mixed>
     */
    private function generateMetadata(): array
    {
        $hasActions = fake()->boolean(30);
        $isPersistent = fake()->boolean(10);

        $metadata = [];

        if ($hasActions) {
            $metadata['actions'] = [
                [
                    'label' => fake()->randomElement(['View', 'Review', 'Approve', 'Dismiss']),
                    'action' => fake()->randomElement(['view', 'review', 'approve', 'dismiss']),
                    'url' => '/' . fake()->randomElement(['campaigns', 'donations', 'admin']) . '/' . fake()->randomNumber(5),
                ],
            ];
        }

        if ($isPersistent) {
            $metadata['persistent'] = true;
        }

        // Add source tracking
        $metadata['source'] = fake()->randomElement(['system', 'api', 'webhook', 'manual']);
        $metadata['created_by'] = 'factory';

        return $metadata;
    }
}
