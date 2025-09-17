<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\ValueObject;

use InvalidArgumentException;

final readonly class SystemConfiguration
{
    /**
     * @param  array<string, mixed>  $emailSettings
     * @param  array<string, mixed>  $notificationSettings
     */
    public function __construct(
        public string $siteName,
        public string $siteDescription,
        public bool $debugMode,
        public array $emailSettings,
        public array $notificationSettings
    ) {
        if ($this->siteName === '' || $this->siteName === '0') {
            throw new InvalidArgumentException('Site name cannot be empty');
        }

        if ($this->siteDescription === '' || $this->siteDescription === '0') {
            throw new InvalidArgumentException('Site description cannot be empty');
        }

        $this->validateEmailSettings($this->emailSettings);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateEmailSettings(array $settings): void
    {
        $requiredKeys = ['driver', 'host', 'port', 'username', 'from_address'];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $settings)) {
                throw new InvalidArgumentException("Email setting '{$key}' is required");
            }
        }
    }

    public function withDebugMode(bool $enabled): self
    {
        return new self(
            $this->siteName,
            $this->siteDescription,
            $enabled,
            $this->emailSettings,
            $this->notificationSettings
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function withEmailSettings(array $settings): self
    {
        return new self(
            $this->siteName,
            $this->siteDescription,
            $this->debugMode,
            array_merge($this->emailSettings, $settings),
            $this->notificationSettings
        );
    }
}
