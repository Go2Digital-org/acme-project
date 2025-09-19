<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Infrastructure\ApiPlatform\Handler\Processor\FormatCurrencyProcessor;
use Modules\Currency\Infrastructure\ApiPlatform\Handler\Processor\SetCurrencyProcessor;
use Modules\Currency\Infrastructure\ApiPlatform\Handler\Provider\CurrencyCollectionProvider;
use Modules\Currency\Infrastructure\ApiPlatform\Handler\Provider\CurrentCurrencyProvider;

#[ApiResource(
    shortName: 'Currency',
    operations: [
        new GetCollection(
            uriTemplate: '/currencies',
            description: 'Get available currencies',
            provider: CurrencyCollectionProvider::class,
            middleware: ['auth:sanctum', 'api'],
        ),
        new Get(
            uriTemplate: '/currencies/current',
            description: 'Get current user currency preference',
            provider: CurrentCurrencyProvider::class,
            middleware: ['auth:sanctum', 'api'],
        ),
        new Post(
            uriTemplate: '/currencies/set',
            description: 'Set user currency preference',
            processor: SetCurrencyProcessor::class,
            middleware: ['auth:sanctum', 'api'],
        ),
        new Post(
            uriTemplate: '/currencies/format',
            description: 'Format an amount in currency',
            processor: FormatCurrencyProcessor::class,
            middleware: ['auth:sanctum', 'api'],
        ),
    ],
)]
class CurrencyResource
{
    public function __construct(
        public ?int $id = null,
        public ?string $code = null,
        public ?string $name = null,
        public ?string $symbol = null,
        public ?string $flag = null,
        public ?int $decimal_places = null,
        public ?string $decimal_separator = null,
        public ?string $thousands_separator = null,
        public ?string $symbol_position = null,
        public ?bool $is_active = null,
        public ?bool $is_default = null,
        public ?float $exchange_rate = null,
        public ?int $sort_order = null,
        public ?string $created_at = null,
        public ?string $updated_at = null,
        // For format response
        public ?float $amount = null,
        public ?string $formatted = null,
        // For request data
        public ?string $currency = null,
    ) {}

    public static function fromModel(Currency $currency): self
    {
        return new self(
            id: $currency->id,
            code: $currency->code,
            name: $currency->name,
            symbol: $currency->symbol,
            flag: $currency->flag,
            decimal_places: $currency->decimal_places,
            decimal_separator: $currency->decimal_separator,
            thousands_separator: $currency->thousands_separator,
            symbol_position: $currency->symbol_position,
            is_active: $currency->is_active,
            is_default: $currency->is_default,
            exchange_rate: $currency->exchange_rate,
            sort_order: $currency->sort_order,
            created_at: $currency->created_at?->toDateTimeString(),
            updated_at: $currency->updated_at?->toDateTimeString(),
        );
    }
}
