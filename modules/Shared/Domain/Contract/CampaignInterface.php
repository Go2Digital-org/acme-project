<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Contract;

interface CampaignInterface
{
    public function getId(): int;

    public function getTitle(): string;

    public function getDescription(): ?string;

    public function getUrl(): string;
}
