<?php

declare(strict_types=1);

namespace Modules\Bookmark\Domain\Exception;

use Exception;
use Modules\Bookmark\Domain\Model\Bookmark;

/**
 * Exception for bookmark domain operations.
 */
final class BookmarkException extends Exception
{
    public static function alreadyExists(int $userId, int $campaignId): self
    {
        return new self("Bookmark already exists for user {$userId} and campaign {$campaignId}");
    }

    public static function notFound(int $userId, int $campaignId): self
    {
        return new self("Bookmark not found for user {$userId} and campaign {$campaignId}");
    }

    public static function bookmarkNotFound(int $bookmarkId): self
    {
        return new self("Bookmark with ID {$bookmarkId} not found");
    }

    public static function cannotBookmarkOwnCampaign(): self
    {
        return new self('Cannot bookmark your own campaign');
    }

    public static function campaignNotActive(int $campaignId): self
    {
        return new self("Cannot bookmark inactive campaign {$campaignId}");
    }

    public static function unauthorizedAccess(int $userId, Bookmark $bookmark): self
    {
        return new self("User {$userId} is not authorized to access bookmark {$bookmark->id}");
    }

    public static function invalidAction(string $action): self
    {
        return new self("Invalid bookmark organization action: {$action}");
    }

    public static function bulkOperationFailed(string $operation, int $expectedCount, int $actualCount): self
    {
        return new self("Bulk {$operation} failed: expected {$expectedCount}, actual {$actualCount}");
    }
}
