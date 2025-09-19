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
     * @param  array<string, mixed>  $preferences
     * @param  array<string, mixed>|null  $quietHours
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $userId,
        /** @var array<string, mixed> */
        public array $preferences,
        public ?string $timezone = null,
        public ?array $quietHours = null,
        public ?int $digestFrequency = null,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}
}
