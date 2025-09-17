<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Event;

use DateTimeImmutable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class AdminSettingsUpdatedEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $changes
     */
    public function __construct(
        public int $settingsId,
        public int $updatedBy,
        public array $changes,
        public DateTimeImmutable $occurredAt = new DateTimeImmutable
    ) {}
}
