<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Currency\Application\Command\SetUserCurrencyCommand;
use Modules\Currency\Domain\ValueObject\Currency;
use Modules\Currency\Infrastructure\ApiPlatform\Resource\CurrencyResource;
use Modules\Shared\Application\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<object, CurrencyResource>
 */
final readonly class SetCurrencyProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CurrencyResource {
        if (! is_object($data)) {
            throw new InvalidArgumentException('Data must be an object');
        }

        // Get authenticated user from request context
        $request = $context['request'] ?? throw new InvalidArgumentException('Request context is required');

        if (! $request instanceof Request) {
            throw new InvalidArgumentException('Request must be an instance of Laravel Request');
        }

        $user = $request->user();

        if ($user === null) {
            throw new InvalidArgumentException('User is not authenticated');
        }

        // Extract currency code from request data
        $currencyCode = property_exists($data, 'currency') ? $data->currency : null;
        if ($currencyCode === null || ! is_string($currencyCode)) {
            throw new InvalidArgumentException('Currency code is required and must be a string');
        }

        // Validate currency code
        try {
            $currency = Currency::fromString($currencyCode);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException("Invalid currency code: {$currencyCode}");
        }

        // Execute the command to set user currency
        $command = new SetUserCurrencyCommand(
            userId: $user->id,
            currencyCode: $currencyCode,
        );

        $this->commandBus->handle($command);

        // Return CurrencyResource response
        return new CurrencyResource(
            code: $currency->getCode(),
            name: $currency->getName(),
            symbol: $currency->getSymbol(),
        );
    }
}
