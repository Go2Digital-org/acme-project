<?php

declare(strict_types=1);

use Modules\Donation\Domain\ValueObject\PaymentMethod;

describe('PaymentMethod Value Object', function (): void {
    describe('Enum Cases', function (): void {
        it('has all expected payment method cases', function (): void {
            $cases = PaymentMethod::cases();
            $expectedCases = [
                'CARD',
                'CREDIT_CARD',
                'IDEAL',
                'BANCONTACT',
                'SOFORT',
                'STRIPE',
                'PAYPAL',
                'BANK_TRANSFER',
                'CORPORATE_ACCOUNT',
            ];

            expect($cases)->toHaveCount(9);
            $caseNames = array_map(fn ($case) => $case->name, $cases);
            foreach ($expectedCases as $case) {
                expect($caseNames)->toContain($case);
            }
        });

        it('has correct string values for each case', function (): void {
            expect(PaymentMethod::CARD->value)->toBe('card');
            expect(PaymentMethod::CREDIT_CARD->value)->toBe('credit_card');
            expect(PaymentMethod::IDEAL->value)->toBe('ideal');
            expect(PaymentMethod::BANCONTACT->value)->toBe('bancontact');
            expect(PaymentMethod::SOFORT->value)->toBe('sofort');
            expect(PaymentMethod::STRIPE->value)->toBe('stripe');
            expect(PaymentMethod::PAYPAL->value)->toBe('paypal');
            expect(PaymentMethod::BANK_TRANSFER->value)->toBe('bank_transfer');
            expect(PaymentMethod::CORPORATE_ACCOUNT->value)->toBe('corporate_account');
        });
    });

    describe('getType() method', function (): void {
        it('returns the correct type value for each payment method', function (): void {
            expect(PaymentMethod::CARD->getType())->toBe('card');
            expect(PaymentMethod::CREDIT_CARD->getType())->toBe('credit_card');
            expect(PaymentMethod::IDEAL->getType())->toBe('ideal');
            expect(PaymentMethod::BANCONTACT->getType())->toBe('bancontact');
            expect(PaymentMethod::SOFORT->getType())->toBe('sofort');
            expect(PaymentMethod::STRIPE->getType())->toBe('stripe');
            expect(PaymentMethod::PAYPAL->getType())->toBe('paypal');
            expect(PaymentMethod::BANK_TRANSFER->getType())->toBe('bank_transfer');
            expect(PaymentMethod::CORPORATE_ACCOUNT->getType())->toBe('corporate_account');
        });

        it('returns same value as enum value property', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                expect($method->getType())->toBe($method->value);
            }
        });
    });

    describe('getLabel() method', function (): void {
        it('returns correct human-readable labels', function (): void {
            expect(PaymentMethod::CARD->getLabel())->toBe('Credit/Debit Card');
            expect(PaymentMethod::CREDIT_CARD->getLabel())->toBe('Credit/Debit Card');
            expect(PaymentMethod::IDEAL->getLabel())->toBe('iDEAL');
            expect(PaymentMethod::BANCONTACT->getLabel())->toBe('Bancontact');
            expect(PaymentMethod::SOFORT->getLabel())->toBe('Sofort');
            expect(PaymentMethod::STRIPE->getLabel())->toBe('Credit/Debit Card (Stripe)');
            expect(PaymentMethod::PAYPAL->getLabel())->toBe('PayPal');
            expect(PaymentMethod::BANK_TRANSFER->getLabel())->toBe('Bank Transfer');
            expect(PaymentMethod::CORPORATE_ACCOUNT->getLabel())->toBe('Corporate Account');
        });

        it('has non-empty labels for all payment methods', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                expect($method->getLabel())->not->toBeEmpty();
                expect($method->getLabel())->toBeString();
            }
        });
    });

    describe('getIcon() method', function (): void {
        it('returns correct heroicon classes', function (): void {
            expect(PaymentMethod::CARD->getIcon())->toBe('heroicon-o-credit-card');
            expect(PaymentMethod::CREDIT_CARD->getIcon())->toBe('heroicon-o-credit-card');
            expect(PaymentMethod::IDEAL->getIcon())->toBe('heroicon-o-credit-card');
            expect(PaymentMethod::BANCONTACT->getIcon())->toBe('heroicon-o-credit-card');
            expect(PaymentMethod::SOFORT->getIcon())->toBe('heroicon-o-credit-card');
            expect(PaymentMethod::STRIPE->getIcon())->toBe('heroicon-o-credit-card');
            expect(PaymentMethod::PAYPAL->getIcon())->toBe('heroicon-o-banknotes');
            expect(PaymentMethod::BANK_TRANSFER->getIcon())->toBe('heroicon-o-building-library');
            expect(PaymentMethod::CORPORATE_ACCOUNT->getIcon())->toBe('heroicon-o-building-office-2');
        });

        it('has valid heroicon format for all icons', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                $icon = $method->getIcon();
                expect($icon)->toStartWith('heroicon-');
                expect($icon)->toMatch('/^heroicon-[os]-[\w-]+$/');
            }
        });
    });

    describe('requiresProcessing() method', function (): void {
        it('returns true for online payment methods', function (): void {
            $onlineMethods = [
                PaymentMethod::CARD,
                PaymentMethod::CREDIT_CARD,
                PaymentMethod::IDEAL,
                PaymentMethod::BANCONTACT,
                PaymentMethod::SOFORT,
                PaymentMethod::STRIPE,
                PaymentMethod::PAYPAL,
            ];

            foreach ($onlineMethods as $method) {
                expect($method->requiresProcessing())->toBeTrue();
            }
        });

        it('returns false for manual payment methods', function (): void {
            $manualMethods = [
                PaymentMethod::BANK_TRANSFER,
                PaymentMethod::CORPORATE_ACCOUNT,
            ];

            foreach ($manualMethods as $method) {
                expect($method->requiresProcessing())->toBeFalse();
            }
        });
    });

    describe('getGateway() method', function (): void {
        it('returns correct gateway names', function (): void {
            expect(PaymentMethod::CARD->getGateway())->toBe('mollie');
            expect(PaymentMethod::CREDIT_CARD->getGateway())->toBe('mollie');
            expect(PaymentMethod::IDEAL->getGateway())->toBe('mollie');
            expect(PaymentMethod::BANCONTACT->getGateway())->toBe('mollie');
            expect(PaymentMethod::SOFORT->getGateway())->toBe('mollie');
            expect(PaymentMethod::STRIPE->getGateway())->toBe('stripe');
            expect(PaymentMethod::PAYPAL->getGateway())->toBe('paypal');
        });

        it('returns null for manual payment methods', function (): void {
            expect(PaymentMethod::BANK_TRANSFER->getGateway())->toBeNull();
            expect(PaymentMethod::CORPORATE_ACCOUNT->getGateway())->toBeNull();
        });

        it('has gateway assignment for all online payment methods', function (): void {
            $onlineMethods = [
                PaymentMethod::CARD,
                PaymentMethod::CREDIT_CARD,
                PaymentMethod::IDEAL,
                PaymentMethod::BANCONTACT,
                PaymentMethod::SOFORT,
                PaymentMethod::STRIPE,
                PaymentMethod::PAYPAL,
            ];

            foreach ($onlineMethods as $method) {
                expect($method->getGateway())->not->toBeNull();
                expect($method->getGateway())->toBeString();
            }
        });
    });

    describe('getColor() method', function (): void {
        it('returns valid color names', function (): void {
            $validColors = ['primary', 'success', 'warning', 'info', 'gray', 'secondary'];

            foreach (PaymentMethod::cases() as $method) {
                expect($method->getColor())->toBeIn($validColors);
            }
        });

        it('returns specific colors for payment methods', function (): void {
            expect(PaymentMethod::CARD->getColor())->toBe('primary');
            expect(PaymentMethod::CREDIT_CARD->getColor())->toBe('primary');
            expect(PaymentMethod::IDEAL->getColor())->toBe('success');
            expect(PaymentMethod::BANCONTACT->getColor())->toBe('warning');
            expect(PaymentMethod::SOFORT->getColor())->toBe('info');
            expect(PaymentMethod::STRIPE->getColor())->toBe('primary');
            expect(PaymentMethod::PAYPAL->getColor())->toBe('warning');
            expect(PaymentMethod::BANK_TRANSFER->getColor())->toBe('gray');
            expect(PaymentMethod::CORPORATE_ACCOUNT->getColor())->toBe('secondary');
        });
    });

    describe('getTailwindBadgeClasses() method', function (): void {
        it('returns valid Tailwind CSS classes', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                $classes = $method->getTailwindBadgeClasses();

                expect($classes)->toBeString();
                expect($classes)->toContain('bg-');
                expect($classes)->toContain('text-');
                expect($classes)->toContain('dark:');
            }
        });

        it('has consistent format for all badge classes', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                $classes = $method->getTailwindBadgeClasses();

                // Should contain light and dark variants
                expect($classes)->toMatch('/bg-\w+-\d+/');
                expect($classes)->toMatch('/text-\w+-\d+/');
                expect($classes)->toMatch('/dark:bg-\w+-\d+/');
                expect($classes)->toMatch('/dark:text-\w+-\d+/');
            }
        });
    });

    describe('isInstant() method', function (): void {
        it('returns true for instant payment methods', function (): void {
            $instantMethods = [
                PaymentMethod::CARD,
                PaymentMethod::CREDIT_CARD,
                PaymentMethod::IDEAL,
                PaymentMethod::BANCONTACT,
                PaymentMethod::SOFORT,
                PaymentMethod::STRIPE,
                PaymentMethod::PAYPAL,
            ];

            foreach ($instantMethods as $method) {
                expect($method->isInstant())->toBeTrue();
            }
        });

        it('returns false for non-instant payment methods', function (): void {
            $nonInstantMethods = [
                PaymentMethod::BANK_TRANSFER,
                PaymentMethod::CORPORATE_ACCOUNT,
            ];

            foreach ($nonInstantMethods as $method) {
                expect($method->isInstant())->toBeFalse();
            }
        });
    });

    describe('isOnline() method', function (): void {
        it('returns true for online payment methods', function (): void {
            $onlineMethods = [
                PaymentMethod::CARD,
                PaymentMethod::CREDIT_CARD,
                PaymentMethod::IDEAL,
                PaymentMethod::BANCONTACT,
                PaymentMethod::SOFORT,
                PaymentMethod::STRIPE,
                PaymentMethod::PAYPAL,
            ];

            foreach ($onlineMethods as $method) {
                expect($method->isOnline())->toBeTrue();
            }
        });

        it('returns false for offline payment methods', function (): void {
            $offlineMethods = [
                PaymentMethod::BANK_TRANSFER,
                PaymentMethod::CORPORATE_ACCOUNT,
            ];

            foreach ($offlineMethods as $method) {
                expect($method->isOnline())->toBeFalse();
            }
        });
    });

    describe('requiresWebhook() method', function (): void {
        it('returns true for online payment methods that require processing', function (): void {
            $webhookMethods = [
                PaymentMethod::CARD,
                PaymentMethod::CREDIT_CARD,
                PaymentMethod::IDEAL,
                PaymentMethod::BANCONTACT,
                PaymentMethod::SOFORT,
                PaymentMethod::STRIPE,
                PaymentMethod::PAYPAL,
            ];

            foreach ($webhookMethods as $method) {
                expect($method->requiresWebhook())->toBeTrue();
            }
        });

        it('returns false for manual payment methods', function (): void {
            $noWebhookMethods = [
                PaymentMethod::BANK_TRANSFER,
                PaymentMethod::CORPORATE_ACCOUNT,
            ];

            foreach ($noWebhookMethods as $method) {
                expect($method->requiresWebhook())->toBeFalse();
            }
        });

        it('is consistent with requiresProcessing and isOnline', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                $expectedWebhook = $method->requiresProcessing() && $method->isOnline();
                expect($method->requiresWebhook())->toBe($expectedWebhook);
            }
        });
    });

    describe('getDescription() method', function (): void {
        it('returns descriptive text for all payment methods', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                $description = $method->getDescription();

                expect($description)->toBeString();
                expect($description)->not->toBeEmpty();
                expect(strlen($description))->toBeGreaterThan(10);
            }
        });

        it('has meaningful descriptions for specific methods', function (): void {
            expect(PaymentMethod::CARD->getDescription())->toContain('credit or debit card');
            expect(PaymentMethod::IDEAL->getDescription())->toContain('iDEAL');
            expect(PaymentMethod::IDEAL->getDescription())->toContain('Netherlands');
            expect(PaymentMethod::BANCONTACT->getDescription())->toContain('Bancontact');
            expect(PaymentMethod::BANCONTACT->getDescription())->toContain('Belgium');
            expect(PaymentMethod::PAYPAL->getDescription())->toContain('PayPal');
        });
    });

    describe('getProcessingTime() method', function (): void {
        it('returns processing time strings', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                $processingTime = $method->getProcessingTime();

                expect($processingTime)->toBeString();
                expect($processingTime)->not->toBeEmpty();
            }
        });

        it('returns instant for online methods', function (): void {
            $instantMethods = [
                PaymentMethod::CARD,
                PaymentMethod::CREDIT_CARD,
                PaymentMethod::IDEAL,
                PaymentMethod::BANCONTACT,
                PaymentMethod::SOFORT,
                PaymentMethod::STRIPE,
                PaymentMethod::PAYPAL,
            ];

            foreach ($instantMethods as $method) {
                expect($method->getProcessingTime())->toBe('Instant');
            }
        });

        it('returns specific times for manual methods', function (): void {
            expect(PaymentMethod::BANK_TRANSFER->getProcessingTime())->toBe('1-3 business days');
            expect(PaymentMethod::CORPORATE_ACCOUNT->getProcessingTime())->toBe('Next business day');
        });
    });

    describe('supportsCurrency() method', function (): void {
        it('returns boolean for currency support', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                expect($method->supportsCurrency('USD'))->toBeBool();
                expect($method->supportsCurrency('EUR'))->toBeBool();
                expect($method->supportsCurrency('GBP'))->toBeBool();
            }
        });

        it('supports major currencies for card methods', function (): void {
            $cardMethods = [PaymentMethod::CARD, PaymentMethod::CREDIT_CARD, PaymentMethod::STRIPE];
            $majorCurrencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD'];

            foreach ($cardMethods as $method) {
                foreach ($majorCurrencies as $currency) {
                    expect($method->supportsCurrency($currency))->toBeTrue();
                }
            }
        });

        it('supports only EUR for European methods', function (): void {
            $europeanMethods = [PaymentMethod::IDEAL, PaymentMethod::BANCONTACT];

            foreach ($europeanMethods as $method) {
                expect($method->supportsCurrency('EUR'))->toBeTrue();
                expect($method->supportsCurrency('USD'))->toBeFalse();
                expect($method->supportsCurrency('GBP'))->toBeFalse();
            }
        });

        it('supports all currencies for manual methods', function (): void {
            $manualMethods = [PaymentMethod::BANK_TRANSFER, PaymentMethod::CORPORATE_ACCOUNT];
            $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF'];

            foreach ($manualMethods as $method) {
                foreach ($currencies as $currency) {
                    expect($method->supportsCurrency($currency))->toBeTrue();
                }
            }
        });

        it('is case-insensitive for currency codes', function (): void {
            expect(PaymentMethod::CARD->supportsCurrency('usd'))->toBeTrue();
            expect(PaymentMethod::CARD->supportsCurrency('eur'))->toBeTrue();
            expect(PaymentMethod::CARD->supportsCurrency('gbp'))->toBeTrue();
        });
    });

    describe('getMinimumAmount() method', function (): void {
        it('returns positive minimum amounts', function (): void {
            $currencies = ['USD', 'EUR', 'GBP'];

            foreach (PaymentMethod::cases() as $method) {
                foreach ($currencies as $currency) {
                    $minimum = $method->getMinimumAmount($currency);
                    expect($minimum)->toBeFloat();
                    expect($minimum)->toBeGreaterThan(0);
                }
            }
        });

        it('has reasonable minimums for card methods', function (): void {
            expect(PaymentMethod::CARD->getMinimumAmount('USD'))->toBe(0.50);
            expect(PaymentMethod::CREDIT_CARD->getMinimumAmount('USD'))->toBe(0.50);
            expect(PaymentMethod::CARD->getMinimumAmount('EUR'))->toBe(0.50);
            expect(PaymentMethod::CREDIT_CARD->getMinimumAmount('EUR'))->toBe(0.50);
            expect(PaymentMethod::CARD->getMinimumAmount('GBP'))->toBe(0.30);
            expect(PaymentMethod::CREDIT_CARD->getMinimumAmount('GBP'))->toBe(0.30);
        });

        it('has low minimums for European methods', function (): void {
            expect(PaymentMethod::IDEAL->getMinimumAmount('EUR'))->toBe(0.01);
            expect(PaymentMethod::BANCONTACT->getMinimumAmount('EUR'))->toBe(0.01);
        });

        it('has higher minimums for PayPal', function (): void {
            expect(PaymentMethod::PAYPAL->getMinimumAmount('USD'))->toBe(1.00);
            expect(PaymentMethod::PAYPAL->getMinimumAmount('EUR'))->toBe(1.00);
            expect(PaymentMethod::PAYPAL->getMinimumAmount('GBP'))->toBe(1.00);
        });

        it('defaults to 0.01 for unsupported currencies', function (): void {
            expect(PaymentMethod::CARD->getMinimumAmount('JPY'))->toBe(0.01);
            expect(PaymentMethod::PAYPAL->getMinimumAmount('CHF'))->toBe(0.01);
        });

        it('uses USD as default currency', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                $defaultMinimum = $method->getMinimumAmount();
                $usdMinimum = $method->getMinimumAmount('USD');

                expect($defaultMinimum)->toBe($usdMinimum);
            }
        });
    });

    describe('getAvailableForCurrency() static method', function (): void {
        it('returns array of payment methods', function (): void {
            $available = PaymentMethod::getAvailableForCurrency('USD');

            expect($available)->toBeArray();
            expect($available)->not->toBeEmpty();

            foreach ($available as $method) {
                expect($method)->toBeInstanceOf(PaymentMethod::class);
            }
        });

        it('filters correctly for USD', function (): void {
            $available = PaymentMethod::getAvailableForCurrency('USD');
            $availableTypes = array_map(fn ($method) => $method->value, $available);

            expect($availableTypes)->toContain('card');
            expect($availableTypes)->toContain('credit_card');
            expect($availableTypes)->toContain('stripe');
            expect($availableTypes)->toContain('paypal');
            expect($availableTypes)->toContain('bank_transfer');
            expect($availableTypes)->toContain('corporate_account');

            expect($availableTypes)->not->toContain('ideal');
            expect($availableTypes)->not->toContain('bancontact');
        });

        it('filters correctly for EUR', function (): void {
            $available = PaymentMethod::getAvailableForCurrency('EUR');

            expect(count($available))->toBeGreaterThan(6);

            $availableTypes = array_map(fn ($method) => $method->value, $available);
            expect($availableTypes)->toContain('ideal');
            expect($availableTypes)->toContain('bancontact');
        });

        it('is case-insensitive', function (): void {
            $upperCase = PaymentMethod::getAvailableForCurrency('USD');
            $lowerCase = PaymentMethod::getAvailableForCurrency('usd');

            expect(count($upperCase))->toBe(count($lowerCase));
        });
    });

    describe('fromString() static method', function (): void {
        it('creates payment method from valid string', function (): void {
            expect(PaymentMethod::fromString('card'))->toBe(PaymentMethod::CARD);
            expect(PaymentMethod::fromString('credit_card'))->toBe(PaymentMethod::CREDIT_CARD);
            expect(PaymentMethod::fromString('ideal'))->toBe(PaymentMethod::IDEAL);
            expect(PaymentMethod::fromString('paypal'))->toBe(PaymentMethod::PAYPAL);
        });

        it('throws exception for invalid string', function (): void {
            expect(fn () => PaymentMethod::fromString('invalid'))
                ->toThrow(ValueError::class);
        });
    });

    describe('tryFromString() static method', function (): void {
        it('creates payment method from valid string', function (): void {
            expect(PaymentMethod::tryFromString('card'))->toBe(PaymentMethod::CARD);
            expect(PaymentMethod::tryFromString('credit_card'))->toBe(PaymentMethod::CREDIT_CARD);
            expect(PaymentMethod::tryFromString('ideal'))->toBe(PaymentMethod::IDEAL);
            expect(PaymentMethod::tryFromString('paypal'))->toBe(PaymentMethod::PAYPAL);
        });

        it('returns null for invalid string', function (): void {
            expect(PaymentMethod::tryFromString('invalid'))->toBeNull();
            expect(PaymentMethod::tryFromString(''))->toBeNull();
            expect(PaymentMethod::tryFromString('CARD'))->toBeNull(); // Case sensitive
        });
    });

    describe('Business Logic Consistency', function (): void {
        it('has consistent online and instant behavior', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                if ($method->isOnline()) {
                    expect($method->isInstant())->toBeTrue();
                }
            }
        });

        it('has consistent processing and webhook requirements', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                if ($method->requiresProcessing() && $method->isOnline()) {
                    expect($method->requiresWebhook())->toBeTrue();
                }
            }
        });

        it('has gateway assignment for processing methods', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                if ($method->requiresProcessing()) {
                    expect($method->getGateway())->not->toBeNull();
                } else {
                    expect($method->getGateway())->toBeNull();
                }
            }
        });

        it('has appropriate minimum amounts for gateways', function (): void {
            // Stripe and regular card methods should have reasonable minimums
            expect(PaymentMethod::STRIPE->getMinimumAmount('USD'))->toBeGreaterThanOrEqual(0.50);
            expect(PaymentMethod::CARD->getMinimumAmount('USD'))->toBeGreaterThanOrEqual(0.50);
            expect(PaymentMethod::CREDIT_CARD->getMinimumAmount('USD'))->toBeGreaterThanOrEqual(0.50);

            // PayPal should have higher minimums
            expect(PaymentMethod::PAYPAL->getMinimumAmount('USD'))->toBeGreaterThanOrEqual(1.00);

            // Manual methods should be flexible
            expect(PaymentMethod::BANK_TRANSFER->getMinimumAmount('USD'))->toBeLessThanOrEqual(0.01);
        });
    });

    describe('Edge Cases and Validation', function (): void {
        it('handles empty currency strings', function (): void {
            $manualMethods = [PaymentMethod::BANK_TRANSFER, PaymentMethod::CORPORATE_ACCOUNT];
            $onlineMethods = [
                PaymentMethod::CARD, PaymentMethod::CREDIT_CARD, PaymentMethod::IDEAL, PaymentMethod::BANCONTACT,
                PaymentMethod::SOFORT, PaymentMethod::STRIPE, PaymentMethod::PAYPAL,
            ];

            foreach ($manualMethods as $method) {
                expect($method->supportsCurrency(''))->toBeTrue(); // Manual methods support any currency
            }

            foreach ($onlineMethods as $method) {
                expect($method->supportsCurrency(''))->toBeFalse(); // Online methods validate currency
            }
        });

        it('handles special characters in currency', function (): void {
            $manualMethods = [PaymentMethod::BANK_TRANSFER, PaymentMethod::CORPORATE_ACCOUNT];
            $onlineMethods = [
                PaymentMethod::CARD, PaymentMethod::CREDIT_CARD, PaymentMethod::IDEAL, PaymentMethod::BANCONTACT,
                PaymentMethod::SOFORT, PaymentMethod::STRIPE, PaymentMethod::PAYPAL,
            ];

            foreach ($manualMethods as $method) {
                expect($method->supportsCurrency('US$'))->toBeTrue(); // Manual methods support any currency
                expect($method->supportsCurrency('EUR€'))->toBeTrue();
            }

            foreach ($onlineMethods as $method) {
                expect($method->supportsCurrency('US$'))->toBeFalse(); // Online methods validate currency
                expect($method->supportsCurrency('EUR€'))->toBeFalse();
            }
        });

        it('maintains immutability', function (): void {
            $original = PaymentMethod::CARD;
            $copy = PaymentMethod::CARD;

            expect($original)->toBe($copy);
            expect($original === $copy)->toBeTrue();
        });
    });
});
