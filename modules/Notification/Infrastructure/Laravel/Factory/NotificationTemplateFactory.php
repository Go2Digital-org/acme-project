<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationPriority;
use Modules\Notification\Domain\Enum\NotificationType;
use Modules\Notification\Domain\Model\NotificationTemplate;
use Modules\Organization\Domain\Model\Organization;

/**
 * @extends Factory<NotificationTemplate>
 */
class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([
            NotificationType::CAMPAIGN_CREATED,
            NotificationType::DONATION_RECEIVED,
            NotificationType::ORGANIZATION_VERIFIED,
            NotificationType::SYSTEM_ALERT,
        ]);

        $channel = fake()->randomElement([
            NotificationChannel::EMAIL,
            NotificationChannel::IN_APP,
            NotificationChannel::SMS,
        ]);

        return [
            'name' => $this->generateTemplateName($type),
            'type' => $type,
            'channel' => $channel,
            'subject_template' => $this->generateSubjectTemplate($type),
            'body_template' => $this->generateBodyTemplate($type),
            'html_body_template' => $channel === NotificationChannel::EMAIL ? $this->generateHtmlTemplate($type) : null,
            'priority' => fake()->randomElement([
                NotificationPriority::LOW,
                NotificationPriority::NORMAL,
                NotificationPriority::MEDIUM,
                NotificationPriority::HIGH,
            ]),
            'is_active' => true,
            'metadata' => $this->generateMetadata(),
            'organization_id' => fake()->optional(0.7)->randomElement([null, Organization::factory()]),
        ];
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => NotificationChannel::EMAIL,
            'html_body_template' => $this->generateHtmlTemplate($attributes['type'] ?? NotificationType::GENERIC),
        ]);
    }

    public function inApp(): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => NotificationChannel::IN_APP,
            'html_body_template' => null,
        ]);
    }

    public function sms(): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => NotificationChannel::SMS,
            'subject_template' => '',
            'html_body_template' => null,
            'body_template' => 'SMS: {{message}}',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function campaignCreated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Campaign Created Notification',
            'type' => NotificationType::CAMPAIGN_CREATED,
            'subject_template' => 'New Campaign: {{campaign.title}}',
            'body_template' => 'A new campaign "{{campaign.title}}" has been created by {{user.name}}.',
        ]);
    }

    public function donationReceived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Donation Received Notification',
            'type' => NotificationType::DONATION_RECEIVED,
            'subject_template' => 'Donation Received: {{donation.amount}} {{donation.currency}}',
            'body_template' => 'Thank you for your donation of {{donation.amount}} {{donation.currency}} to {{campaign.title}}.',
        ]);
    }

    public function organizationVerified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Organization Verified Notification',
            'type' => NotificationType::ORGANIZATION_VERIFIED,
            'subject_template' => 'Organization Verified: {{organization.name}}',
            'body_template' => 'Congratulations! {{organization.name}} has been successfully verified.',
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => NotificationPriority::HIGH,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => NotificationPriority::URGENT,
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $organization->id,
        ]);
    }

    public function global(): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => null,
        ]);
    }

    private function generateTemplateName(NotificationType $type): string
    {
        $prefixes = ['Standard', 'Default', 'System', 'Custom'];
        $prefix = fake()->randomElement($prefixes);

        return "{$prefix} {$type->getLabel()} Template";
    }

    private function generateSubjectTemplate(NotificationType $type): string
    {
        return match ($type) {
            NotificationType::CAMPAIGN_CREATED => 'New Campaign: {{campaign.title}}',
            NotificationType::DONATION_RECEIVED => 'Donation Received: {{donation.amount}} {{donation.currency}}',
            NotificationType::ORGANIZATION_VERIFIED => 'Organization Verified: {{organization.name}}',
            NotificationType::SYSTEM_ALERT => 'System Alert: {{alert.title}}',
            default => '{{title}} - {{app.name}}',
        };
    }

    private function generateBodyTemplate(NotificationType $type): string
    {
        return match ($type) {
            NotificationType::CAMPAIGN_CREATED => 'Hello {{user.name}},\n\nA new campaign "{{campaign.title}}" has been created and is now available for donations.\n\nBest regards,\nThe ACME Corp Team',
            NotificationType::DONATION_RECEIVED => 'Dear {{user.name}},\n\nThank you for your generous donation of {{donation.amount}} {{donation.currency}} to the campaign "{{campaign.title}}".\n\nYour support makes a real difference!\n\nBest regards,\nThe ACME Corp Team',
            NotificationType::ORGANIZATION_VERIFIED => 'Dear {{organization.contact_name}},\n\nCongratulations! Your organization "{{organization.name}}" has been successfully verified and can now create campaigns.\n\nWelcome to the ACME Corp CSR platform!\n\nBest regards,\nThe ACME Corp Team',
            NotificationType::SYSTEM_ALERT => 'System Alert: {{alert.title}}\n\n{{alert.message}}\n\nTime: {{alert.timestamp}}\n\nPlease take appropriate action if required.',
            default => 'Hello {{user.name}},\n\n{{message}}\n\nBest regards,\nThe ACME Corp Team',
        };
    }

    private function generateHtmlTemplate(NotificationType $type): string
    {
        return match ($type) {
            NotificationType::CAMPAIGN_CREATED => '
                <div style="font-family: Arial, sans-serif; max-width: 600px;">
                    <h2>New Campaign Created</h2>
                    <p>Hello {{user.name}},</p>
                    <p>A new campaign <strong>"{{campaign.title}}"</strong> has been created and is now available for donations.</p>
                    <a href="{{campaign.url}}" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View Campaign</a>
                    <p>Best regards,<br>The ACME Corp Team</p>
                </div>',
            NotificationType::DONATION_RECEIVED => '
                <div style="font-family: Arial, sans-serif; max-width: 600px;">
                    <h2>Thank You for Your Donation!</h2>
                    <p>Dear {{user.name}},</p>
                    <p>Thank you for your generous donation of <strong>{{donation.amount}} {{donation.currency}}</strong> to the campaign <strong>"{{campaign.title}}"</strong>.</p>
                    <p>Your support makes a real difference!</p>
                    <p>Best regards,<br>The ACME Corp Team</p>
                </div>',
            default => '
                <div style="font-family: Arial, sans-serif; max-width: 600px;">
                    <h2>{{title}}</h2>
                    <p>Hello {{user.name}},</p>
                    <p>{{message}}</p>
                    <p>Best regards,<br>The ACME Corp Team</p>
                </div>',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function generateMetadata(): array
    {
        return [
            'version' => '1.0',
            'created_by' => 'system',
            'last_modified' => now()->toISOString(),
            'tags' => fake()->words(3),
        ];
    }
}
