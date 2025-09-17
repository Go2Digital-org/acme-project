<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Exception;

use Exception;

class ResourceNotFoundException extends Exception
{
    public function __construct(string $message, private readonly int $statusCode = 404)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
