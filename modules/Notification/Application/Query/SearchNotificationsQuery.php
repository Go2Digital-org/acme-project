<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Carbon\CarbonInterface;
use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query for searching notifications with advanced filters.
 */
final readonly class SearchNotificationsQuery implements QueryInterface
{
    /**
     * @param  array<string>  $types
     * @param  array<string>  $channels
     * @param  array<string>  $statuses
     * @param  array<string>  $priorities
     */
    public function __construct(
        public int $userId,
        public ?string $searchTerm = null,
        /** @var array<string, mixed> */
        public array $types = [],
        /** @var array<string, mixed> */
        public array $channels = [],
        /** @var array<string, mixed> */
        public array $statuses = [],
        /** @var array<string, mixed> */
        public array $priorities = [],
        public ?CarbonInterface $startDate = null,
        public ?CarbonInterface $endDate = null,
        public ?int $senderId = null,
        public ?bool $hasAttachments = null,
        public ?bool $isRecurring = null,
        public string $sortBy = 'created_at',
        public string $sortOrder = 'desc',
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
