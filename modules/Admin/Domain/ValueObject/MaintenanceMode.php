<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\ValueObject;

use InvalidArgumentException;

final readonly class MaintenanceMode
{
    /**
     * @param  array<string>  $allowedIps
     */
    public function __construct(
        public bool $enabled,
        public ?string $message = null,
        /** @var array<string, mixed> */
        public array $allowedIps = []
    ) {
        if ($this->enabled && ($this->message === null || $this->message === '' || $this->message === '0')) {
            throw new InvalidArgumentException('Maintenance message is required when maintenance mode is enabled');
        }
    }

    /**
     * @param  array<string>  $allowedIps
     */
    public static function enabled(string $message, array $allowedIps = []): self
    {
        return new self(true, $message, $allowedIps);
    }

    public static function disabled(): self
    {
        return new self(false);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function canAccess(string $ip): bool
    {
        if (! $this->enabled) {
            return true;
        }

        return in_array($ip, $this->allowedIps);
    }
}
