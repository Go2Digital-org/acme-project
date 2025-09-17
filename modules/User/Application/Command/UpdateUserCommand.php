<?php

declare(strict_types=1);

namespace Modules\User\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class UpdateUserCommand implements CommandInterface
{
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $jobTitle = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $preferredLanguage = null,
        public ?string $timezone = null,
    ) {}
}
