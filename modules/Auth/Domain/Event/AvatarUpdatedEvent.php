<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Event;

use DateTimeImmutable;
use Modules\Shared\Domain\Event\DomainEventInterface;

class AvatarUpdatedEvent implements DomainEventInterface
{
    private readonly DateTimeImmutable $occurredAt;

    public function __construct(
        private readonly int $userId,
        private readonly ?string $photoUrl,
        private readonly ?string $photoPath,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function getPhotoPath(): ?string
    {
        return $this->photoPath;
    }

    public function getEventName(): string
    {
        return 'auth.avatar.updated';
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /** @return array<string, mixed> */
    public function getEventData(): array
    {
        return [
            'user_id' => $this->userId,
            'photo_url' => $this->photoUrl,
            'photo_path' => $this->photoPath,
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
