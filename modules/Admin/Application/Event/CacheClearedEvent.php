<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Event;

use DateTimeImmutable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class CacheClearedEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string>  $cacheTypes
     * @param  array<string, array<string, bool|string|int>>  $results
     */
    public function __construct(
        public array $cacheTypes,
        public int $triggeredBy,
        public array $results,
        public DateTimeImmutable $occurredAt = new DateTimeImmutable
    ) {}
}
