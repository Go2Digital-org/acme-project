<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\User\Infrastructure\Laravel\Models\User;
use Psr\Log\LoggerInterface;

final readonly class SendCampaignNotificationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof SendCampaignNotificationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        // Send to campaign creator if different from current user
        if ($command->creatorId && $command->creatorId !== $command->userId) {
            $this->sendToCreator($command);
        }

        // Send to the user performing the action (if not the creator)
        $this->sendToUser($command);

        return null;
    }

    private function sendToUser(SendCampaignNotificationCommand $command): void
    {
        $user = User::find($command->userId);

        if (! $user) {
            $this->logger->warning('User not found for campaign notification', [
                'user_id' => $command->userId,
                'campaign_id' => $command->campaignId,
            ]);

            return;
        }

        $notificationData = $this->buildUserNotificationData($command);

        $notification = new DatabaseNotification;
        $notification->id = Str::uuid()->toString();
        $notification->type = 'App\Notifications\CampaignNotification';
        $notification->notifiable_type = $user::class;
        $notification->notifiable_id = (string) $user->id;
        $notification->data = $notificationData;
        $notification->created_at = now();
        $notification->save();

        $this->logger->info('Campaign notification sent to user', [
            'user_id' => $command->userId,
            'campaign_id' => $command->campaignId,
            'event_type' => $command->eventType,
        ]);
    }

    private function sendToCreator(SendCampaignNotificationCommand $command): void
    {
        if (! $command->creatorId) {
            return;
        }

        $creator = User::find($command->creatorId);

        if (! $creator) {
            $this->logger->warning('Campaign creator not found for notification', [
                'creator_id' => $command->creatorId,
                'campaign_id' => $command->campaignId,
            ]);

            return;
        }

        $notificationData = $this->buildCreatorNotificationData($command);

        $notification = new DatabaseNotification;
        $notification->id = Str::uuid()->toString();
        $notification->type = 'App\Notifications\CampaignNotification';
        $notification->notifiable_type = $creator::class;
        $notification->notifiable_id = (string) $creator->id;
        $notification->data = $notificationData;
        $notification->created_at = now();
        $notification->save();

        $this->logger->info('Campaign notification sent to creator', [
            'creator_id' => $command->creatorId,
            'campaign_id' => $command->campaignId,
            'event_type' => $command->eventType,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUserNotificationData(SendCampaignNotificationCommand $command): array
    {
        return match ($command->eventType) {
            SendCampaignNotificationCommand::EVENT_CREATED => [
                'title' => 'Campaign Created',
                'message' => sprintf('Your campaign "%s" has been created successfully.', $command->campaignTitle),
                'type' => 'success',
                'action_url' => "/campaigns/{$command->campaignId}",
                'campaign_id' => $command->campaignId,
                'campaign_title' => $command->campaignTitle,
                'event_type' => $command->eventType,
            ],
            SendCampaignNotificationCommand::EVENT_UPDATED => [
                'title' => 'Campaign Updated',
                'message' => sprintf('Your campaign "%s" has been updated.', $command->campaignTitle),
                'type' => 'info',
                'action_url' => "/campaigns/{$command->campaignId}",
                'campaign_id' => $command->campaignId,
                'campaign_title' => $command->campaignTitle,
                'event_type' => $command->eventType,
            ],
            SendCampaignNotificationCommand::EVENT_PUBLISHED => [
                'title' => 'Campaign Published',
                'message' => sprintf('Your campaign "%s" is now live and accepting donations!', $command->campaignTitle),
                'type' => 'success',
                'action_url' => "/campaigns/{$command->campaignId}",
                'campaign_id' => $command->campaignId,
                'campaign_title' => $command->campaignTitle,
                'event_type' => $command->eventType,
            ],
            SendCampaignNotificationCommand::EVENT_COMPLETED => [
                'title' => 'Campaign Completed',
                'message' => sprintf('Congratulations! Your campaign "%s" has reached its goal.', $command->campaignTitle),
                'type' => 'success',
                'action_url' => "/campaigns/{$command->campaignId}/report",
                'campaign_id' => $command->campaignId,
                'campaign_title' => $command->campaignTitle,
                'event_type' => $command->eventType,
            ],
            default => [
                'title' => 'Campaign Update',
                'message' => sprintf('Campaign "%s" status: %s', $command->campaignTitle, $command->eventType),
                'type' => 'info',
                'action_url' => "/campaigns/{$command->campaignId}",
                'campaign_id' => $command->campaignId,
                'campaign_title' => $command->campaignTitle,
                'event_type' => $command->eventType,
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCreatorNotificationData(SendCampaignNotificationCommand $command): array
    {
        return match ($command->eventType) {
            SendCampaignNotificationCommand::EVENT_APPROVED => [
                'title' => 'Campaign Approved',
                'message' => sprintf('Your campaign "%s" has been approved by an administrator.', $command->campaignTitle),
                'type' => 'success',
                'action_url' => "/campaigns/{$command->campaignId}",
                'campaign_id' => $command->campaignId,
                'campaign_title' => $command->campaignTitle,
                'event_type' => $command->eventType,
            ],
            SendCampaignNotificationCommand::EVENT_REJECTED => [
                'title' => 'Campaign Rejected',
                'message' => sprintf(
                    'Your campaign "%s" was not approved. %s',
                    $command->campaignTitle,
                    $command->reason ?? 'Please review and resubmit.'
                ),
                'type' => 'warning',
                'action_url' => "/campaigns/{$command->campaignId}/edit",
                'campaign_id' => $command->campaignId,
                'campaign_title' => $command->campaignTitle,
                'reason' => $command->reason,
                'event_type' => $command->eventType,
            ],
            default => [
                'title' => 'Campaign Activity',
                'message' => sprintf('Activity on your campaign "%s"', $command->campaignTitle),
                'type' => 'info',
                'action_url' => "/campaigns/{$command->campaignId}",
                'campaign_id' => $command->campaignId,
                'campaign_title' => $command->campaignTitle,
                'event_type' => $command->eventType,
            ],
        };
    }
}
