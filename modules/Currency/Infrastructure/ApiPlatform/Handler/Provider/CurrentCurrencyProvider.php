<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Modules\Currency\Application\Query\GetUserCurrencyQuery;
use Modules\Currency\Infrastructure\ApiPlatform\Resource\CurrencyResource;
use Modules\Shared\Application\Query\QueryBusInterface;

/**
 * @implements ProviderInterface<CurrencyResource>
 */
final readonly class CurrentCurrencyProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): array {
        // Get authenticated user from request context
        $request = $context['request'] ?? throw new InvalidArgumentException('Request context is required');
        $user = $request->user();

        if ($user === null) {
            throw new InvalidArgumentException('User is not authenticated');
        }

        // Get user's current currency preference
        $currency = $this->queryBus->ask(new GetUserCurrencyQuery(userId: $user->id));

        if ($currency === null) {
            throw new InvalidArgumentException('User currency not found');
        }

        return [
            'data' => [
                'currency' => [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                ],
            ],
        ];
    }
}
