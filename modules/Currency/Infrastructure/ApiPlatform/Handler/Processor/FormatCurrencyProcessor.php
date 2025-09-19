<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Modules\Currency\Application\Service\CurrencyPreferenceService;
use Modules\Currency\Domain\ValueObject\Currency;
use Modules\Currency\Infrastructure\ApiPlatform\Resource\CurrencyResource;

/**
 * @implements ProcessorInterface<object, CurrencyResource>
 */
final readonly class FormatCurrencyProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrencyPreferenceService $currencyPreferenceService,
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

        // Extract amount from request data
        $amount = property_exists($data, 'amount') ? $data->amount : null;
        if ($amount === null || ! is_numeric($amount)) {
            throw new InvalidArgumentException('Amount is required and must be numeric');
        }

        $amount = (float) $amount;

        // Extract currency code from request data (optional)
        $currencyCode = property_exists($data, 'currency') ? $data->currency : null;
        $currency = null;

        if ($currencyCode !== null) {
            if (! is_string($currencyCode)) {
                throw new InvalidArgumentException('Currency must be a string');
            }

            try {
                $currency = Currency::fromString($currencyCode);
            } catch (InvalidArgumentException) {
                throw new InvalidArgumentException("Invalid currency code: {$currencyCode}");
            }
        }

        // Format the amount using the currency preference service
        $formattedAmount = $this->currencyPreferenceService->formatAmount($amount, $currency);

        // Get the currency that was used for formatting
        $usedCurrency = $currency ?? $this->currencyPreferenceService->getCurrentCurrency();

        return new CurrencyResource(
            code: $usedCurrency->getCode(),
            name: $usedCurrency->getName(),
            symbol: $usedCurrency->getSymbol(),
            amount: $amount,
            formatted: $formattedAmount,
        );
    }
}
