<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Exception;

/**
 * Exception thrown when a specific notification cannot be found.
 */
class NotificationNotFoundException extends NotificationException
{
    /**
     * Create exception for notification not found by ID.
     */
    public static function forId(string $id): self
    {
        return new self("Notification with ID '{$id}' was not found.");
    }

    /**
     * Create exception for notification not found by user and ID.
     */
    public static function forUserAndId(string $userId, string $id): self
    {
        return new self("Notification with ID '{$id}' was not found for user '{$userId}'.");
    }
}
