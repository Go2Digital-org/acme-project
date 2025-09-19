<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\ValueObject;

final readonly class ModuleManifest
{
    /**
     * @param  list<string>  $serviceProviders  List of service provider class names
     * @param  list<string>  $apiProcessors  List of API processor class names
     * @param  list<string>  $apiProviders  List of API provider class names
     */
    public function __construct(
        /** @var list<string> */
        private array $serviceProviders,
        /** @var list<string> */
        private array $apiProcessors,
        /** @var list<string> */
        private array $apiProviders,
    ) {}

    /**
     * @return list<string>
     */
    public function getServiceProviders(): array
    {
        return $this->serviceProviders;
    }

    /**
     * @return list<string>
     */
    public function getApiProcessors(): array
    {
        return $this->apiProcessors;
    }

    /**
     * @return list<string>
     */
    public function getApiProviders(): array
    {
        return $this->apiProviders;
    }

    /**
     * Convert to array for caching.
     *
     * @return array<string, mixed>
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
     * @param  array<string, mixed>  $data
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
