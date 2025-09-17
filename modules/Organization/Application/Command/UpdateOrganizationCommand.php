<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class UpdateOrganizationCommand implements CommandInterface
{
    public function __construct(
        public int $organizationId,
        public string $name,
        public ?string $registrationNumber = null,
        public ?string $taxId = null,
        public ?string $category = null,
        public ?string $website = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $country = null,
    ) {}
}
