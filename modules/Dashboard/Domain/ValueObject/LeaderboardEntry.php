<?php

declare(strict_types=1);

namespace Modules\Dashboard\Domain\ValueObject;

use Modules\Shared\Domain\ValueObject\Money;

final readonly class LeaderboardEntry
{
    public function __construct(
        public int $rank,
        public string $name,
        public Money $totalDonations,
        public int $campaignsSupported,
        public float $impactScore,
        public bool $isCurrentUser = false,
    ) {}

    public function getRankDisplay(): string
    {
        return match ($this->rank) {
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            default => $this->rank . 'th',
        };
    }

    public function getRankIcon(): ?string
    {
        return match ($this->rank) {
            1 => 'fas fa-crown',
            2 => 'fas fa-medal',
            3 => 'fas fa-award',
            default => null,
        };
    }

    public function getRankColor(): string
    {
        return match ($this->rank) {
            1 => 'yellow',
            2 => 'gray',
            3 => 'orange',
            default => 'gray',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rank' => $this->rank,
            'rankDisplay' => $this->getRankDisplay(),
            'name' => $this->name,
            'totalDonations' => $this->totalDonations->amount,
            'totalDonationsFormatted' => $this->totalDonations->format(),
            'campaignsSupported' => $this->campaignsSupported,
            'impactScore' => $this->impactScore,
            'isCurrentUser' => $this->isCurrentUser,
            'rankIcon' => $this->getRankIcon(),
            'rankColor' => $this->getRankColor(),
        ];
    }
}
