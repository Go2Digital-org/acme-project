<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Service;

use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationPriority;
use Modules\Notification\Domain\Enum\NotificationType;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Model\NotificationPreferences;
use Modules\Notification\Domain\Model\NotificationTemplate;
use Modules\Notification\Domain\Repository\NotificationPreferencesRepositoryInterface;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Notification\Domain\ValueObject\Message;
use Modules\Notification\Domain\ValueObject\Recipient;
use Modules\Shared\Domain\Contract\UserInterface;

/**
 * Core notification domain service
 */
class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly NotificationPreferencesRepositoryInterface $preferencesRepository
    ) {}

    /**
     * Send notification to user
     *
     * @param  array<string, mixed>  $templateVariables
     */
    public function sendNotification(
        UserInterface $user,
        NotificationType $type,
        NotificationChannel $channel,
        NotificationTemplate $template,
        array $templateVariables = [],
        NotificationPriority $priority = NotificationPriority::NORMAL
    ): Notification {
        // Check user preferences
        $preferences = $this->preferencesRepository->findByUserId((string) $user->getId());

        if (! $this->canSendNotification($type, $channel, $preferences)) {
            throw NotificationException::notificationNotAllowed('User notification preferences block this notification type');
        }

        // Render message from template
        $message = $template->render($templateVariables);

        // Create recipient
        $recipient = Recipient::fromUser(
            $user->getId(),
            $user->getEmail(),
            $user->getName()
        );

        // Create and save notification
        $notification = $this->createNotification(
            $recipient,
            $message,
            $type,
            $channel,
            $priority,
            $template->id
        );

        return $notification;
    }

    /**
     * Send bulk notifications
     *
     * @param  UserInterface[]  $users
     * @param  array<string, mixed>  $templateVariables
     * @return Notification[]
     */
    public function sendBulkNotifications(
        array $users,
        NotificationType $type,
        NotificationChannel $channel,
        NotificationTemplate $template,
        array $templateVariables = [],
        NotificationPriority $priority = NotificationPriority::NORMAL
    ): array {
        $notifications = [];

        foreach ($users as $user) {
            try {
                $notifications[] = $this->sendNotification(
                    $user,
                    $type,
                    $channel,
                    $template,
                    $templateVariables,
                    $priority
                );
            } catch (NotificationException) {
                // Log and continue with other users
                continue;
            }
        }

        return $notifications;
    }

    /**
     * Send notification to external email
     */
    public function sendExternalNotification(
        string $email,
        ?string $name,
        Message $message,
        NotificationChannel $channel = NotificationChannel::EMAIL,
        NotificationPriority $priority = NotificationPriority::NORMAL
    ): Notification {
        $recipient = Recipient::fromEmail($email, $name);

        return $this->createNotification(
            $recipient,
            $message,
            NotificationType::SYSTEM,
            $channel,
            $priority
        );
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): void
    {
        $notification = $this->notificationRepository->findById((string) $notificationId);

        if (! $notification instanceof Notification) {
            throw NotificationException::notificationNotFound((string) $notificationId);
        }

        // Verify ownership
        if ($notification->user_id !== $userId) {
            throw NotificationException::notificationAccessDenied('Access denied to this notification');
        }

        $this->notificationRepository->markAsRead((string) $notificationId);
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->notificationRepository->markAllAsReadForUser((string) $userId);
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepository->getUnreadCount((string) $userId);
    }

    /**
     * Check if notification can be sent based on user preferences
     */
    private function canSendNotification(
        NotificationType $type,
        NotificationChannel $channel,
        ?NotificationPreferences $preferences
    ): bool {
        if (! $preferences instanceof NotificationPreferences) {
            // Default behavior: allow all notifications
            return true;
        }

        return $preferences->isChannelEnabledForType($channel->value, $type->value);
    }

    /**
     * Create and persist notification
     */
    private function createNotification(
        Recipient $recipient,
        Message $message,
        NotificationType $type,
        NotificationChannel $channel,
        NotificationPriority $priority,
        ?int $templateId = null
    ): Notification {
        return $this->notificationRepository->create([
            'user_id' => $recipient->userId,
            'email' => (string) $recipient->email,
            'name' => $recipient->name,
            'type' => $type,
            'channel' => $channel,
            'priority' => $priority,
            'subject' => $message->subject,
            'body' => $message->body,
            'html_body' => $message->htmlBody,
            'template_id' => $templateId,
            'status' => 'pending',
            'scheduled_at' => now(),
        ]);
    }
}
