<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\User\Infrastructure\Laravel\Models\User;

class CampaignStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Campaign $campaign,
        private readonly CampaignStatus $previousStatus,
        private readonly CampaignStatus $newStatus,
        private readonly User $changedByUser,
        private readonly ?string $reason = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $statusName = $this->getStatusDisplayName($this->newStatus);

        $message = (new MailMessage)
            ->subject("Campaign Status Changed: {$this->campaign->getTitle()}")
            ->greeting('Hello!')
            ->line('Your campaign status has been updated.')
            ->line("**Campaign:** {$this->campaign->getTitle()}")
            ->line("**Previous Status:** {$this->getStatusDisplayName($this->previousStatus)}")
            ->line("**New Status:** {$statusName}")
            ->line("**Changed by:** {$this->changedByUser->name}");

        if ($this->reason) {
            $message->line("**Reason:** {$this->reason}");
        }

        $actionUrl = $this->getActionUrl();
        $actionText = $this->getActionText();

        if ($actionUrl && $actionText) {
            $message->action($actionText, $actionUrl);
        }

        return $message
            ->line('Thank you for participating in our CSR initiatives!')
            ->salutation('Best regards, ACME Corp CSR Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        $statusName = $this->getStatusDisplayName($this->newStatus);

        return [
            'type' => 'campaign_status_changed',
            'campaign_id' => $this->campaign->id,
            'campaign_title' => $this->campaign->getTitle(),
            'previous_status' => $this->previousStatus->value,
            'new_status' => $this->newStatus->value,
            'changed_by_user_id' => $this->changedByUser->id,
            'changed_by_name' => $this->changedByUser->name,
            'reason' => $this->reason,
            'message' => "Your campaign '{$this->campaign->getTitle()}' status changed to {$statusName}",
            'action_url' => $this->getActionUrl(),
            'action_text' => $this->getActionText(),
        ];
    }

    private function getStatusDisplayName(CampaignStatus $status): string
    {
        return match ($status) {
            CampaignStatus::DRAFT => __('campaigns.status_draft'),
            CampaignStatus::PENDING_APPROVAL => __('campaigns.status_pending_approval'),
            CampaignStatus::REJECTED => __('campaigns.status_rejected'),
            CampaignStatus::ACTIVE => __('campaigns.status_active'),
            CampaignStatus::PAUSED => __('campaigns.status_paused'),
            CampaignStatus::COMPLETED => __('campaigns.status_completed'),
            CampaignStatus::CANCELLED => __('campaigns.status_cancelled'),
            CampaignStatus::EXPIRED => __('campaigns.status_expired'),
        };
    }

    private function getActionUrl(): string
    {
        return match ($this->newStatus) {
            CampaignStatus::ACTIVE => route('campaigns.show', $this->campaign),
            CampaignStatus::REJECTED => route('campaigns.edit', $this->campaign),
            CampaignStatus::PENDING_APPROVAL => route('filament.admin.resources.campaigns.view', $this->campaign),
            default => route('campaigns.show', $this->campaign),
        };
    }

    private function getActionText(): string
    {
        return match ($this->newStatus) {
            CampaignStatus::ACTIVE => __('campaigns.view_campaign'),
            CampaignStatus::REJECTED => __('campaigns.edit_campaign_action'),
            CampaignStatus::PENDING_APPROVAL => __('campaigns.review_campaign'),
            CampaignStatus::COMPLETED => __('campaigns.view_results'),
            default => __('campaigns.view_campaign'),
        };
    }
}
