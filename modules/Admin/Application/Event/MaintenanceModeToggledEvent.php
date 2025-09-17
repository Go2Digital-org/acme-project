<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Event;

use DateTimeImmutable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class MaintenanceModeToggledEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $settingsId,
        public bool $enabled,
        public int $triggeredBy,
        public ?string $message = null,
        public DateTimeImmutable $occurredAt = new DateTimeImmutable
    ) {}
}
