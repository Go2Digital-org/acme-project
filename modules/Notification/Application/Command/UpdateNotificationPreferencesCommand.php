<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command for updating user notification preferences.
 */
final readonly class UpdateNotificationPreferencesCommand implements CommandInterface
{
    /**
     * @param  array<string, array<string, bool>>  $preferences
     * @param  ?array<string, mixed>  $quietHours
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $userId,
        public array $preferences,
        public ?string $timezone = null,
        public ?array $quietHours = null,
        public ?int $digestFrequency = null,
        public array $metadata = [],
    ) {}
}
