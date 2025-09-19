<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\ReadModel;

use Modules\Dashboard\Domain\ValueObject\LeaderboardEntry;
use Modules\Shared\Application\ReadModel\AbstractReadModel;

final class OrganizationLeaderboardReadModel extends AbstractReadModel
{
    protected int $cacheTtl = 600; // 10 minutes for leaderboard

    /**
     * @return array<string, mixed>
     */
    public function getEntries(): array
    {
        return $this->get('entries', []);
    }

    public function getLimit(): int
    {
        return $this->get('limit', 5);
    }

    public function getTotalEntries(): int
    {
        return $this->get('total_entries', 0);
    }

    public function isEmpty(): bool
    {
        return $this->getTotalEntries() === 0;
    }

    public function getTopEntry(): mixed
    {
        $entries = $this->getEntries();

        return $entries[0] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'organization_id' => $this->getId(),
            'entries' => array_map(fn (LeaderboardEntry $entry): array => $entry->toArray(), $this->getEntries()),
            'limit' => $this->getLimit(),
            'total_entries' => $this->getTotalEntries(),
            'is_empty' => $this->isEmpty(),
            'top_entry' => $this->getTopEntry()?->toArray(),
            'version' => $this->getVersion(),
        ];
    }
}
