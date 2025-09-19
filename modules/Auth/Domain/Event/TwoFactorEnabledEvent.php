<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Event;

use DateTimeImmutable;
use Modules\Shared\Domain\Event\DomainEventInterface;

class TwoFactorEnabledEvent implements DomainEventInterface
{
    private readonly DateTimeImmutable $occurredAt;

    public function __construct(
        private readonly int $userId,
        private readonly string $ipAddress,
        private readonly string $userAgent,
        private readonly bool $backupCodesGenerated = true,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function isBackupCodesGenerated(): bool
    {
        return $this->backupCodesGenerated;
    }

    public function getEventName(): string
    {
        return 'auth.two_factor.enabled';
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'user_id' => $this->userId,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'backup_codes_generated' => $this->backupCodesGenerated,
            'occurred_at' => $this->occurredAt->format(DateTimeImmutable::ATOM),
        ];
    }

    public function getAggregateId(): string|int|null
    {
        return $this->userId;
    }

    public function getEventVersion(): int
    {
        return 1;
    }

    public function getContext(): string
    {
        return 'Auth';
    }

    public function isAsync(): bool
    {
        return false;
    }
}
