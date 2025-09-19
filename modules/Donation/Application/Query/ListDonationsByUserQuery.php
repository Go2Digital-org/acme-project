<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class ListDonationsByUserQuery implements QueryInterface
{
    public function __construct(
        public int $userId,
    ) {}
}
