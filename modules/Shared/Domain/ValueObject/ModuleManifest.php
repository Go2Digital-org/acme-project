<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\ValueObject;

final readonly class ModuleManifest
{
    /**
     * @param  array<string>  $serviceProviders  List of service provider class names
     * @param  array<string>  $apiProcessors  List of API processor class names
     * @param  array<string>  $apiProviders  List of API provider class names
     */
    public function __construct(
        private array $serviceProviders,
        private array $apiProcessors,
        private array $apiProviders,
    ) {}

    /**
     * @return array<string>
     */
    public function getServiceProviders(): array
    {
        return $this->serviceProviders;
    }

    /**
     * @return array<string>
     */
    public function getApiProcessors(): array
    {
        return $this->apiProcessors;
    }

    /**
     * @return array<string>
     */
    public function getApiProviders(): array
    {
        return $this->apiProviders;
    }

    /**
     * Convert to array for caching.
     *
     * @return array<string, array<string>>
     */
    public function toArray(): array
    {
        return [
            'service_providers' => $this->serviceProviders,
            'api_processors' => $this->apiProcessors,
            'api_providers' => $this->apiProviders,
        ];
    }

    /**
     * Create from cached array.
     *
     * @param  array<string, array<string>>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['service_providers'] ?? [],
            $data['api_processors'] ?? [],
            $data['api_providers'] ?? [],
        );
    }
}
