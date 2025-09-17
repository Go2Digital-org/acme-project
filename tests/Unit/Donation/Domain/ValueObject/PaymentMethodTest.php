<?php

declare(strict_types=1);

use Modules\Donation\Domain\ValueObject\PaymentMethod;

describe('PaymentMethod Value Object', function (): void {
    it('has all expected payment method cases', function (): void {
        $cases = PaymentMethod::cases();
        $expectedValues = [
            'card',
            'ideal',
            'bancontact',
            'sofort',
            'stripe',
            'paypal',
            'bank_transfer',
            'corporate_account',
            'credit_card',
        ];

        expect($cases)->toHaveCount(9);

        foreach ($cases as $case) {
            expect($expectedValues)->toContain($case->value);
        }
    });

    describe('Labels and Display', function (): void {
        it('returns correct labels for all payment methods', function (): void {
            expect(PaymentMethod::CARD->getLabel())->toBe('Credit/Debit Card')
                ->and(PaymentMethod::CREDIT_CARD->getLabel())->toBe('Credit/Debit Card')
                ->and(PaymentMethod::IDEAL->getLabel())->toBe('iDEAL')
                ->and(PaymentMethod::BANCONTACT->getLabel())->toBe('Bancontact')
                ->and(PaymentMethod::SOFORT->getLabel())->toBe('Sofort')
                ->and(PaymentMethod::STRIPE->getLabel())->toBe('Credit/Debit Card (Stripe)')
                ->and(PaymentMethod::PAYPAL->getLabel())->toBe('PayPal')
                ->and(PaymentMethod::BANK_TRANSFER->getLabel())->toBe('Bank Transfer')
                ->and(PaymentMethod::CORPORATE_ACCOUNT->getLabel())->toBe('Corporate Account');
        });

        it('returns correct icons for all payment methods', function (): void {
            expect(PaymentMethod::CARD->getIcon())->toBe('heroicon-o-credit-card')
                ->and(PaymentMethod::CREDIT_CARD->getIcon())->toBe('heroicon-o-credit-card')
                ->and(PaymentMethod::IDEAL->getIcon())->toBe('heroicon-o-credit-card')
                ->and(PaymentMethod::BANCONTACT->getIcon())->toBe('heroicon-o-credit-card')
                ->and(PaymentMethod::SOFORT->getIcon())->toBe('heroicon-o-credit-card')
                ->and(PaymentMethod::STRIPE->getIcon())->toBe('heroicon-o-credit-card')
                ->and(PaymentMethod::PAYPAL->getIcon())->toBe('heroicon-o-banknotes')
                ->and(PaymentMethod::BANK_TRANSFER->getIcon())->toBe('heroicon-o-building-library')
                ->and(PaymentMethod::CORPORATE_ACCOUNT->getIcon())->toBe('heroicon-o-building-office-2');
        });

        it('returns correct colors for all payment methods', function (): void {
            expect(PaymentMethod::CARD->getColor())->toBe('primary')
                ->and(PaymentMethod::CREDIT_CARD->getColor())->toBe('primary')
                ->and(PaymentMethod::IDEAL->getColor())->toBe('success')
                ->and(PaymentMethod::BANCONTACT->getColor())->toBe('warning')
                ->and(PaymentMethod::SOFORT->getColor())->toBe('info')
                ->and(PaymentMethod::STRIPE->getColor())->toBe('primary')
                ->and(PaymentMethod::PAYPAL->getColor())->toBe('warning')
                ->and(PaymentMethod::BANK_TRANSFER->getColor())->toBe('gray')
                ->and(PaymentMethod::CORPORATE_ACCOUNT->getColor())->toBe('secondary');
        });

        it('returns tailwind badge classes for all payment methods', function (): void {
            expect(PaymentMethod::CARD->getTailwindBadgeClasses())
                ->toContain('bg-blue-100 text-blue-800')
                ->and(PaymentMethod::CREDIT_CARD->getTailwindBadgeClasses())
                ->toContain('bg-blue-100 text-blue-800')
                ->and(PaymentMethod::IDEAL->getTailwindBadgeClasses())
                ->toContain('bg-green-100 text-green-800')
                ->and(PaymentMethod::BANCONTACT->getTailwindBadgeClasses())
                ->toContain('bg-yellow-100 text-yellow-800')
                ->and(PaymentMethod::SOFORT->getTailwindBadgeClasses())
                ->toContain('bg-cyan-100 text-cyan-800')
                ->and(PaymentMethod::STRIPE->getTailwindBadgeClasses())
                ->toContain('bg-blue-100 text-blue-800')
                ->and(PaymentMethod::PAYPAL->getTailwindBadgeClasses())
                ->toContain('bg-yellow-100 text-yellow-800')
                ->and(PaymentMethod::BANK_TRANSFER->getTailwindBadgeClasses())
                ->toContain('bg-gray-100 text-gray-800')
                ->and(PaymentMethod::CORPORATE_ACCOUNT->getTailwindBadgeClasses())
                ->toContain('bg-purple-100 text-purple-800');
        });

        it('returns descriptive text for all payment methods', function (): void {
            expect(PaymentMethod::CARD->getDescription())
                ->toContain('Pay securely with your credit or debit card')
                ->and(PaymentMethod::CREDIT_CARD->getDescription())
                ->toContain('Pay securely with your credit or debit card')
                ->and(PaymentMethod::IDEAL->getDescription())
                ->toContain('iDEAL (Netherlands)')
                ->and(PaymentMethod::BANCONTACT->getDescription())
                ->toContain('Bancontact (Belgium)')
                ->and(PaymentMethod::SOFORT->getDescription())
                ->toContain('Instant bank transfer via Sofort')
                ->and(PaymentMethod::STRIPE->getDescription())
                ->toContain('credit or debit card via Stripe')
                ->and(PaymentMethod::PAYPAL->getDescription())
                ->toContain('PayPal account')
                ->and(PaymentMethod::BANK_TRANSFER->getDescription())
                ->toContain('directly from your bank account')
                ->and(PaymentMethod::CORPORATE_ACCOUNT->getDescription())
                ->toContain('corporate account');
        });
    });

    describe('Processing Characteristics', function (): void {
        it('identifies methods that require processing', function (): void {
            expect(PaymentMethod::CARD->requiresProcessing())->toBeTrue()
                ->and(PaymentMethod::CREDIT_CARD->requiresProcessing())->toBeTrue()
                ->and(PaymentMethod::IDEAL->requiresProcessing())->toBeTrue()
                ->and(PaymentMethod::BANCONTACT->requiresProcessing())->toBeTrue()
                ->and(PaymentMethod::SOFORT->requiresProcessing())->toBeTrue()
                ->and(PaymentMethod::STRIPE->requiresProcessing())->toBeTrue()
                ->and(PaymentMethod::PAYPAL->requiresProcessing())->toBeTrue()
                ->and(PaymentMethod::BANK_TRANSFER->requiresProcessing())->toBeFalse()
                ->and(PaymentMethod::CORPORATE_ACCOUNT->requiresProcessing())->toBeFalse();
        });

        it('identifies instant payment methods', function (): void {
            expect(PaymentMethod::CARD->isInstant())->toBeTrue()
                ->and(PaymentMethod::CREDIT_CARD->isInstant())->toBeTrue()
                ->and(PaymentMethod::IDEAL->isInstant())->toBeTrue()
                ->and(PaymentMethod::BANCONTACT->isInstant())->toBeTrue()
                ->and(PaymentMethod::SOFORT->isInstant())->toBeTrue()
                ->and(PaymentMethod::STRIPE->isInstant())->toBeTrue()
                ->and(PaymentMethod::PAYPAL->isInstant())->toBeTrue()
                ->and(PaymentMethod::BANK_TRANSFER->isInstant())->toBeFalse()
                ->and(PaymentMethod::CORPORATE_ACCOUNT->isInstant())->toBeFalse();
        });

        it('identifies online payment methods', function (): void {
            expect(PaymentMethod::CARD->isOnline())->toBeTrue()
                ->and(PaymentMethod::CREDIT_CARD->isOnline())->toBeTrue()
                ->and(PaymentMethod::IDEAL->isOnline())->toBeTrue()
                ->and(PaymentMethod::BANCONTACT->isOnline())->toBeTrue()
                ->and(PaymentMethod::SOFORT->isOnline())->toBeTrue()
                ->and(PaymentMethod::STRIPE->isOnline())->toBeTrue()
                ->and(PaymentMethod::PAYPAL->isOnline())->toBeTrue()
                ->and(PaymentMethod::BANK_TRANSFER->isOnline())->toBeFalse()
                ->and(PaymentMethod::CORPORATE_ACCOUNT->isOnline())->toBeFalse();
        });

        it('identifies methods that require webhooks', function (): void {
            expect(PaymentMethod::CARD->requiresWebhook())->toBeTrue()
                ->and(PaymentMethod::CREDIT_CARD->requiresWebhook())->toBeTrue()
                ->and(PaymentMethod::IDEAL->requiresWebhook())->toBeTrue()
                ->and(PaymentMethod::BANCONTACT->requiresWebhook())->toBeTrue()
                ->and(PaymentMethod::SOFORT->requiresWebhook())->toBeTrue()
                ->and(PaymentMethod::STRIPE->requiresWebhook())->toBeTrue()
                ->and(PaymentMethod::PAYPAL->requiresWebhook())->toBeTrue()
                ->and(PaymentMethod::BANK_TRANSFER->requiresWebhook())->toBeFalse()
                ->and(PaymentMethod::CORPORATE_ACCOUNT->requiresWebhook())->toBeFalse();
        });
    });

    describe('Gateway Assignment', function (): void {
        it('returns correct gateways for payment methods', function (): void {
            expect(PaymentMethod::CARD->getGateway())->toBe('mollie')
                ->and(PaymentMethod::CREDIT_CARD->getGateway())->toBe('mollie')
                ->and(PaymentMethod::IDEAL->getGateway())->toBe('mollie')
                ->and(PaymentMethod::BANCONTACT->getGateway())->toBe('mollie')
                ->and(PaymentMethod::SOFORT->getGateway())->toBe('mollie')
                ->and(PaymentMethod::STRIPE->getGateway())->toBe('stripe')
                ->and(PaymentMethod::PAYPAL->getGateway())->toBe('paypal')
                ->and(PaymentMethod::BANK_TRANSFER->getGateway())->toBeNull()
                ->and(PaymentMethod::CORPORATE_ACCOUNT->getGateway())->toBeNull();
        });
    });

    describe('Processing Times', function (): void {
        it('returns processing times for all payment methods', function (): void {
            expect(PaymentMethod::CARD->getProcessingTime())->toBe('Instant')
                ->and(PaymentMethod::CREDIT_CARD->getProcessingTime())->toBe('Instant')
                ->and(PaymentMethod::IDEAL->getProcessingTime())->toBe('Instant')
                ->and(PaymentMethod::BANCONTACT->getProcessingTime())->toBe('Instant')
                ->and(PaymentMethod::SOFORT->getProcessingTime())->toBe('Instant')
                ->and(PaymentMethod::STRIPE->getProcessingTime())->toBe('Instant')
                ->and(PaymentMethod::PAYPAL->getProcessingTime())->toBe('Instant')
                ->and(PaymentMethod::BANK_TRANSFER->getProcessingTime())->toBe('1-3 business days')
                ->and(PaymentMethod::CORPORATE_ACCOUNT->getProcessingTime())->toBe('Next business day');
        });
    });

    describe('Currency Support', function (): void {
        it('validates USD support correctly', function (): void {
            expect(PaymentMethod::CARD->supportsCurrency('USD'))->toBeTrue()
                ->and(PaymentMethod::CREDIT_CARD->supportsCurrency('USD'))->toBeTrue()
                ->and(PaymentMethod::STRIPE->supportsCurrency('USD'))->toBeTrue()
                ->and(PaymentMethod::PAYPAL->supportsCurrency('USD'))->toBeTrue()
                ->and(PaymentMethod::BANK_TRANSFER->supportsCurrency('USD'))->toBeTrue()
                ->and(PaymentMethod::CORPORATE_ACCOUNT->supportsCurrency('USD'))->toBeTrue()
                ->and(PaymentMethod::IDEAL->supportsCurrency('USD'))->toBeFalse()
                ->and(PaymentMethod::BANCONTACT->supportsCurrency('USD'))->toBeFalse()
                ->and(PaymentMethod::SOFORT->supportsCurrency('USD'))->toBeFalse();
        });

        it('validates EUR support correctly', function (): void {
            expect(PaymentMethod::CARD->supportsCurrency('EUR'))->toBeTrue()
                ->and(PaymentMethod::CREDIT_CARD->supportsCurrency('EUR'))->toBeTrue()
                ->and(PaymentMethod::IDEAL->supportsCurrency('EUR'))->toBeTrue()
                ->and(PaymentMethod::BANCONTACT->supportsCurrency('EUR'))->toBeTrue()
                ->and(PaymentMethod::SOFORT->supportsCurrency('EUR'))->toBeTrue()
                ->and(PaymentMethod::STRIPE->supportsCurrency('EUR'))->toBeTrue()
                ->and(PaymentMethod::PAYPAL->supportsCurrency('EUR'))->toBeTrue()
                ->and(PaymentMethod::BANK_TRANSFER->supportsCurrency('EUR'))->toBeTrue()
                ->and(PaymentMethod::CORPORATE_ACCOUNT->supportsCurrency('EUR'))->toBeTrue();
        });

        it('validates GBP support correctly', function (): void {
            expect(PaymentMethod::CARD->supportsCurrency('GBP'))->toBeTrue()
                ->and(PaymentMethod::CREDIT_CARD->supportsCurrency('GBP'))->toBeTrue()
                ->and(PaymentMethod::SOFORT->supportsCurrency('GBP'))->toBeTrue()
                ->and(PaymentMethod::STRIPE->supportsCurrency('GBP'))->toBeTrue()
                ->and(PaymentMethod::PAYPAL->supportsCurrency('GBP'))->toBeTrue()
                ->and(PaymentMethod::BANK_TRANSFER->supportsCurrency('GBP'))->toBeTrue()
                ->and(PaymentMethod::CORPORATE_ACCOUNT->supportsCurrency('GBP'))->toBeTrue()
                ->and(PaymentMethod::IDEAL->supportsCurrency('GBP'))->toBeFalse()
                ->and(PaymentMethod::BANCONTACT->supportsCurrency('GBP'))->toBeFalse();
        });

        it('validates other currency support', function (): void {
            expect(PaymentMethod::CARD->supportsCurrency('CAD'))->toBeTrue()
                ->and(PaymentMethod::CARD->supportsCurrency('AUD'))->toBeTrue()
                ->and(PaymentMethod::CREDIT_CARD->supportsCurrency('CAD'))->toBeTrue()
                ->and(PaymentMethod::CREDIT_CARD->supportsCurrency('AUD'))->toBeTrue()
                ->and(PaymentMethod::STRIPE->supportsCurrency('CAD'))->toBeTrue()
                ->and(PaymentMethod::STRIPE->supportsCurrency('AUD'))->toBeTrue()
                ->and(PaymentMethod::PAYPAL->supportsCurrency('CAD'))->toBeTrue()
                ->and(PaymentMethod::PAYPAL->supportsCurrency('AUD'))->toBeTrue()
                ->and(PaymentMethod::IDEAL->supportsCurrency('CAD'))->toBeFalse()
                ->and(PaymentMethod::BANCONTACT->supportsCurrency('JPY'))->toBeFalse();
        });

        it('handles case insensitive currency checks', function (): void {
            expect(PaymentMethod::CARD->supportsCurrency('usd'))->toBeTrue()
                ->and(PaymentMethod::CARD->supportsCurrency('eur'))->toBeTrue()
                ->and(PaymentMethod::CREDIT_CARD->supportsCurrency('usd'))->toBeTrue()
                ->and(PaymentMethod::CREDIT_CARD->supportsCurrency('eur'))->toBeTrue()
                ->and(PaymentMethod::IDEAL->supportsCurrency('eur'))->toBeTrue()
                ->and(PaymentMethod::IDEAL->supportsCurrency('Eur'))->toBeTrue();
        });
    });

    describe('Minimum Amounts', function (): void {
        it('returns correct minimum amounts for USD', function (): void {
            expect(PaymentMethod::CARD->getMinimumAmount('USD'))->toBe(0.50)
                ->and(PaymentMethod::CREDIT_CARD->getMinimumAmount('USD'))->toBe(0.50)
                ->and(PaymentMethod::STRIPE->getMinimumAmount('USD'))->toBe(0.50)
                ->and(PaymentMethod::PAYPAL->getMinimumAmount('USD'))->toBe(1.00)
                ->and(PaymentMethod::BANK_TRANSFER->getMinimumAmount('USD'))->toBe(0.01)
                ->and(PaymentMethod::CORPORATE_ACCOUNT->getMinimumAmount('USD'))->toBe(0.01);
        });

        it('returns correct minimum amounts for EUR', function (): void {
            expect(PaymentMethod::CARD->getMinimumAmount('EUR'))->toBe(0.50)
                ->and(PaymentMethod::CREDIT_CARD->getMinimumAmount('EUR'))->toBe(0.50)
                ->and(PaymentMethod::IDEAL->getMinimumAmount('EUR'))->toBe(0.01)
                ->and(PaymentMethod::BANCONTACT->getMinimumAmount('EUR'))->toBe(0.01)
                ->and(PaymentMethod::SOFORT->getMinimumAmount('EUR'))->toBe(0.50)
                ->and(PaymentMethod::STRIPE->getMinimumAmount('EUR'))->toBe(0.50)
                ->and(PaymentMethod::PAYPAL->getMinimumAmount('EUR'))->toBe(1.00)
                ->and(PaymentMethod::BANK_TRANSFER->getMinimumAmount('EUR'))->toBe(0.01)
                ->and(PaymentMethod::CORPORATE_ACCOUNT->getMinimumAmount('EUR'))->toBe(0.01);
        });

        it('returns correct minimum amounts for GBP', function (): void {
            expect(PaymentMethod::CARD->getMinimumAmount('GBP'))->toBe(0.30)
                ->and(PaymentMethod::CREDIT_CARD->getMinimumAmount('GBP'))->toBe(0.30)
                ->and(PaymentMethod::SOFORT->getMinimumAmount('GBP'))->toBe(0.50)
                ->and(PaymentMethod::STRIPE->getMinimumAmount('GBP'))->toBe(0.30)
                ->and(PaymentMethod::PAYPAL->getMinimumAmount('GBP'))->toBe(1.00)
                ->and(PaymentMethod::BANK_TRANSFER->getMinimumAmount('GBP'))->toBe(0.01)
                ->and(PaymentMethod::CORPORATE_ACCOUNT->getMinimumAmount('GBP'))->toBe(0.01);
        });

        it('returns default minimum for unsupported currencies', function (): void {
            expect(PaymentMethod::CARD->getMinimumAmount('JPY'))->toBe(0.01)
                ->and(PaymentMethod::CREDIT_CARD->getMinimumAmount('JPY'))->toBe(0.01)
                ->and(PaymentMethod::IDEAL->getMinimumAmount('USD'))->toBe(0.01)
                ->and(PaymentMethod::BANCONTACT->getMinimumAmount('GBP'))->toBe(0.01);
        });

        it('handles case insensitive currency for minimum amounts', function (): void {
            expect(PaymentMethod::CARD->getMinimumAmount('usd'))->toBe(0.50)
                ->and(PaymentMethod::CARD->getMinimumAmount('Usd'))->toBe(0.50)
                ->and(PaymentMethod::CREDIT_CARD->getMinimumAmount('usd'))->toBe(0.50)
                ->and(PaymentMethod::CREDIT_CARD->getMinimumAmount('Usd'))->toBe(0.50)
                ->and(PaymentMethod::IDEAL->getMinimumAmount('eur'))->toBe(0.01);
        });
    });

    describe('Available Methods for Currency', function (): void {
        it('returns available methods for USD', function (): void {
            $methods = PaymentMethod::getAvailableForCurrency('USD');

            expect($methods)->toContain(PaymentMethod::CARD)
                ->and($methods)->toContain(PaymentMethod::CREDIT_CARD)
                ->and($methods)->toContain(PaymentMethod::STRIPE)
                ->and($methods)->toContain(PaymentMethod::PAYPAL)
                ->and($methods)->toContain(PaymentMethod::BANK_TRANSFER)
                ->and($methods)->toContain(PaymentMethod::CORPORATE_ACCOUNT)
                ->and($methods)->not->toContain(PaymentMethod::IDEAL)
                ->and($methods)->not->toContain(PaymentMethod::BANCONTACT);
        });

        it('returns available methods for EUR', function (): void {
            $methods = PaymentMethod::getAvailableForCurrency('EUR');

            // All methods support EUR
            expect($methods)->toHaveCount(9);

            foreach (PaymentMethod::cases() as $method) {
                expect($methods)->toContain($method);
            }
        });

        it('returns available methods for GBP', function (): void {
            $methods = PaymentMethod::getAvailableForCurrency('GBP');

            expect($methods)->toContain(PaymentMethod::CARD)
                ->and($methods)->toContain(PaymentMethod::CREDIT_CARD)
                ->and($methods)->toContain(PaymentMethod::SOFORT)
                ->and($methods)->toContain(PaymentMethod::STRIPE)
                ->and($methods)->toContain(PaymentMethod::PAYPAL)
                ->and($methods)->toContain(PaymentMethod::BANK_TRANSFER)
                ->and($methods)->toContain(PaymentMethod::CORPORATE_ACCOUNT)
                ->and($methods)->not->toContain(PaymentMethod::IDEAL)
                ->and($methods)->not->toContain(PaymentMethod::BANCONTACT);
        });

        it('handles case insensitive currency for available methods', function (): void {
            $methodsUpper = PaymentMethod::getAvailableForCurrency('USD');
            $methodsLower = PaymentMethod::getAvailableForCurrency('usd');

            expect($methodsUpper)->toEqual($methodsLower);
        });
    });

    describe('String Conversion', function (): void {
        it('creates payment method from string', function (): void {
            expect(PaymentMethod::fromString('card'))->toBe(PaymentMethod::CARD)
                ->and(PaymentMethod::fromString('credit_card'))->toBe(PaymentMethod::CREDIT_CARD)
                ->and(PaymentMethod::fromString('paypal'))->toBe(PaymentMethod::PAYPAL)
                ->and(PaymentMethod::fromString('bank_transfer'))->toBe(PaymentMethod::BANK_TRANSFER);
        });

        it('throws exception for invalid string', function (): void {
            expect(fn () => PaymentMethod::fromString('invalid'))
                ->toThrow(\ValueError::class);
        });

        it('tries to create payment method from string safely', function (): void {
            expect(PaymentMethod::tryFromString('card'))->toBe(PaymentMethod::CARD)
                ->and(PaymentMethod::tryFromString('credit_card'))->toBe(PaymentMethod::CREDIT_CARD)
                ->and(PaymentMethod::tryFromString('invalid'))->toBeNull()
                ->and(PaymentMethod::tryFromString('paypal'))->toBe(PaymentMethod::PAYPAL);
        });
    });

    describe('Consistency and Logic Validation', function (): void {
        it('ensures instant methods are online', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                if ($method->isInstant()) {
                    expect($method->isOnline())->toBeTrue();
                }
            }
        });

        it('ensures webhook requirements match processing and online status', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                if ($method->requiresWebhook()) {
                    expect($method->requiresProcessing())->toBeTrue();
                    expect($method->isOnline())->toBeTrue();
                }
            }
        });

        it('ensures manual methods do not require processing', function (): void {
            $manualMethods = [PaymentMethod::BANK_TRANSFER, PaymentMethod::CORPORATE_ACCOUNT];

            foreach ($manualMethods as $method) {
                expect($method->requiresProcessing())->toBeFalse()
                    ->and($method->isOnline())->toBeFalse()
                    ->and($method->requiresWebhook())->toBeFalse()
                    ->and($method->getGateway())->toBeNull();
            }
        });

        it('ensures all methods have required display properties', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                expect($method->getLabel())->toBeString()->not()->toBeEmpty()
                    ->and($method->getIcon())->toBeString()->not()->toBeEmpty()
                    ->and($method->getColor())->toBeString()->not()->toBeEmpty()
                    ->and($method->getDescription())->toBeString()->not()->toBeEmpty()
                    ->and($method->getTailwindBadgeClasses())->toBeString()->not()->toBeEmpty()
                    ->and($method->getProcessingTime())->toBeString()->not()->toBeEmpty();
            }
        });
    });

    describe('Edge Cases', function (): void {
        it('handles empty currency gracefully', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                expect(fn () => $method->supportsCurrency(''))->not->toThrow(\Error::class);
                expect(fn () => $method->getMinimumAmount(''))->not->toThrow(\Error::class);
            }
        });

        it('maintains consistency between instant and processing time', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                if ($method->isInstant()) {
                    expect($method->getProcessingTime())->toBe('Instant');
                } else {
                    expect($method->getProcessingTime())->not->toBe('Instant');
                }
            }
        });

        it('validates minimum amounts are positive', function (): void {
            $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD'];

            foreach (PaymentMethod::cases() as $method) {
                foreach ($currencies as $currency) {
                    expect($method->getMinimumAmount($currency))->toBeGreaterThan(0);
                }
            }
        });

        it('ensures currency support consistency with minimum amounts', function (): void {
            foreach (PaymentMethod::cases() as $method) {
                // If method doesn't support currency, minimum should be default (0.01)
                if (! $method->supportsCurrency('JPY')) {
                    expect($method->getMinimumAmount('JPY'))->toBe(0.01);
                }
            }
        });
    });
});
