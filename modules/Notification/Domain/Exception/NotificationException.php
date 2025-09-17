<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Exception;

use Exception;
use Modules\Notification\Domain\Model\Notification;
use Throwable;

/**
 * Base exception for all notification-related domain errors.
 */
class NotificationException extends Exception
{
    /**
     * Create exception for invalid notification data.
     */
    public static function invalidData(string $field, string $reason): self
    {
        return new self("Invalid notification data for field '{$field}': {$reason}");
    }

    /**
     * Create exception when notification cannot be found.
     */
    public static function notFound(string $id): self
    {
        return new self("Notification with ID '{$id}' was not found.");
    }

    /**
     * Create exception when notification cannot be sent.
     */
    public static function cannotSend(Notification $notification, string $reason): self
    {
        return new self(
            "Cannot send notification '{$notification->id}': {$reason}",
        );
    }

    /**
     * Create exception when notification channel is not supported.
     */
    public static function unsupportedChannel(string $channel): self
    {
        return new self("Notification channel '{$channel}' is not supported.");
    }

    /**
     * Create exception when notification type is invalid.
     */
    public static function invalidType(string $type): self
    {
        return new self("Notification type '{$type}' is not valid.");
    }

    /**
     * Create exception when notification priority is invalid.
     */
    public static function invalidPriority(string $priority): self
    {
        return new self("Notification priority '{$priority}' is not valid.");
    }

    /**
     * Create exception when recipient is invalid.
     */
    public static function invalidRecipient(string $recipientId): self
    {
        return new self("Invalid recipient ID: '{$recipientId}'");
    }

    /**
     * Create exception when notification is already processed.
     */
    public static function alreadyProcessed(Notification $notification): self
    {
        return new self(
            "Notification '{$notification->id}' has already been processed (status: {$notification->status}).",
        );
    }

    /**
     * Create exception when notification delivery fails.
     */
    public static function deliveryFailed(Notification $notification, string $reason, ?Throwable $previous = null): self
    {
        return new self(
            "Failed to deliver notification '{$notification->id}' via '{$notification->channel}': {$reason}",
            0,
            $previous,
        );
    }

    /**
     * Create exception for configuration errors.
     */
    public static function configurationError(string $message): self
    {
        return new self("Notification configuration error: {$message}");
    }

    /**
     * Create exception when rate limit is exceeded.
     */
    public static function rateLimitExceeded(string $recipientId, string $period): self
    {
        return new self(
            "Rate limit exceeded for recipient '{$recipientId}' in period '{$period}'.",
        );
    }

    /**
     * Create exception when notification is not found (by ID).
     */
    public static function notificationNotFound(string $id): self
    {
        return new self("Notification with ID '{$id}' was not found.");
    }

    /**
     * Create exception for access denied errors.
     */
    public static function accessDenied(string $message): self
    {
        return new self("Access denied: {$message}");
    }

    /**
     * Create exception for invalid notification status.
     */
    public static function invalidStatus(string $message): self
    {
        return new self("Invalid notification status: {$message}");
    }

    /**
     * Create exception for bulk creation failures.
     */
    public static function bulkCreationFailed(string $message): self
    {
        return new self("Bulk notification creation failed: {$message}");
    }

    /**
     * Create exception for deletion failures.
     */
    public static function deletionFailed(string $message): self
    {
        return new self("Notification deletion failed: {$message}");
    }

    /**
     * Create exception for preference update failures.
     */
    public static function preferenceUpdateFailed(string $message): self
    {
        return new self("Notification preference update failed: {$message}");
    }

    /**
     * Create exception for scheduling failures.
     */
    public static function schedulingFailed(string $message): self
    {
        return new self("Notification scheduling failed: {$message}");
    }

    /**
     * Create exception for cancellation failures.
     */
    public static function cancellationFailed(string $message): self
    {
        return new self("Notification cancellation failed: {$message}");
    }

    /**
     * Create exception for query failures.
     */
    public static function queryFailed(string $message): self
    {
        return new self("Notification query failed: {$message}");
    }

    /**
     * Create exception for digest generation failures.
     */
    public static function digestGenerationFailed(string $message): self
    {
        return new self("Notification digest generation failed: {$message}");
    }

    /**
     * Create exception when notification is not allowed for user.
     */
    public static function notificationNotAllowed(string $message = 'Notification not allowed'): self
    {
        return new self($message);
    }

    /**
     * Create exception when access is denied to notification.
     */
    public static function notificationAccessDenied(string $message = 'Access denied to notification'): self
    {
        return new self($message);
    }
}
