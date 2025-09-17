<?php

declare(strict_types=1);

use Modules\Donation\Domain\ValueObject\PaymentStatus;

describe('PaymentStatus Value Object', function (): void {
    it('has all expected status cases', function (): void {
        $cases = PaymentStatus::cases();
        $expectedValues = [
            'pending',
            'processing',
            'requires_action',
            'completed',
            'failed',
            'cancelled',
            'refunded',
            'partially_refunded',
            'succeeded',
        ];

        expect($cases)->toHaveCount(9);

        foreach ($cases as $case) {
            expect($expectedValues)->toContain($case->value);
        }
    });

    describe('Status Validation', function (): void {
        it('identifies successful payments', function (): void {
            expect(PaymentStatus::PENDING->isSuccessful())->toBeFalse()
                ->and(PaymentStatus::PROCESSING->isSuccessful())->toBeFalse()
                ->and(PaymentStatus::REQUIRES_ACTION->isSuccessful())->toBeFalse()
                ->and(PaymentStatus::COMPLETED->isSuccessful())->toBeTrue()
                ->and(PaymentStatus::FAILED->isSuccessful())->toBeFalse()
                ->and(PaymentStatus::CANCELLED->isSuccessful())->toBeFalse()
                ->and(PaymentStatus::REFUNDED->isSuccessful())->toBeFalse()
                ->and(PaymentStatus::PARTIALLY_REFUNDED->isSuccessful())->toBeFalse()
                ->and(PaymentStatus::SUCCEEDED->isSuccessful())->toBeFalse();
        });

        it('identifies final payment states', function (): void {
            expect(PaymentStatus::PENDING->isFinal())->toBeFalse()
                ->and(PaymentStatus::PROCESSING->isFinal())->toBeFalse()
                ->and(PaymentStatus::REQUIRES_ACTION->isFinal())->toBeFalse()
                ->and(PaymentStatus::COMPLETED->isFinal())->toBeTrue()
                ->and(PaymentStatus::FAILED->isFinal())->toBeTrue()
                ->and(PaymentStatus::CANCELLED->isFinal())->toBeTrue()
                ->and(PaymentStatus::REFUNDED->isFinal())->toBeTrue()
                ->and(PaymentStatus::PARTIALLY_REFUNDED->isFinal())->toBeFalse()
                ->and(PaymentStatus::SUCCEEDED->isFinal())->toBeFalse();
        });

        it('identifies payments that can be cancelled', function (): void {
            expect(PaymentStatus::PENDING->canBeCancelled())->toBeTrue()
                ->and(PaymentStatus::PROCESSING->canBeCancelled())->toBeFalse()
                ->and(PaymentStatus::REQUIRES_ACTION->canBeCancelled())->toBeTrue()
                ->and(PaymentStatus::COMPLETED->canBeCancelled())->toBeFalse()
                ->and(PaymentStatus::FAILED->canBeCancelled())->toBeFalse()
                ->and(PaymentStatus::CANCELLED->canBeCancelled())->toBeFalse()
                ->and(PaymentStatus::REFUNDED->canBeCancelled())->toBeFalse()
                ->and(PaymentStatus::PARTIALLY_REFUNDED->canBeCancelled())->toBeFalse()
                ->and(PaymentStatus::SUCCEEDED->canBeCancelled())->toBeFalse();
        });

        it('identifies payments that can be refunded', function (): void {
            expect(PaymentStatus::PENDING->canBeRefunded())->toBeFalse()
                ->and(PaymentStatus::PROCESSING->canBeRefunded())->toBeFalse()
                ->and(PaymentStatus::REQUIRES_ACTION->canBeRefunded())->toBeFalse()
                ->and(PaymentStatus::COMPLETED->canBeRefunded())->toBeTrue()
                ->and(PaymentStatus::FAILED->canBeRefunded())->toBeFalse()
                ->and(PaymentStatus::CANCELLED->canBeRefunded())->toBeFalse()
                ->and(PaymentStatus::REFUNDED->canBeRefunded())->toBeFalse()
                ->and(PaymentStatus::PARTIALLY_REFUNDED->canBeRefunded())->toBeTrue()
                ->and(PaymentStatus::SUCCEEDED->canBeRefunded())->toBeFalse();
        });

        it('identifies payments that require user action', function (): void {
            expect(PaymentStatus::PENDING->requiresUserAction())->toBeFalse()
                ->and(PaymentStatus::PROCESSING->requiresUserAction())->toBeFalse()
                ->and(PaymentStatus::REQUIRES_ACTION->requiresUserAction())->toBeTrue()
                ->and(PaymentStatus::COMPLETED->requiresUserAction())->toBeFalse()
                ->and(PaymentStatus::FAILED->requiresUserAction())->toBeFalse()
                ->and(PaymentStatus::CANCELLED->requiresUserAction())->toBeFalse()
                ->and(PaymentStatus::REFUNDED->requiresUserAction())->toBeFalse()
                ->and(PaymentStatus::PARTIALLY_REFUNDED->requiresUserAction())->toBeFalse()
                ->and(PaymentStatus::SUCCEEDED->requiresUserAction())->toBeFalse();
        });
    });

    describe('Descriptions', function (): void {
        it('returns descriptive text for all statuses', function (): void {
            expect(PaymentStatus::PENDING->getDescription())
                ->toBe('Payment is pending processing')
                ->and(PaymentStatus::PROCESSING->getDescription())
                ->toBe('Payment is being processed')
                ->and(PaymentStatus::REQUIRES_ACTION->getDescription())
                ->toBe('Payment requires additional action')
                ->and(PaymentStatus::COMPLETED->getDescription())
                ->toBe('Payment completed successfully')
                ->and(PaymentStatus::FAILED->getDescription())
                ->toBe('Payment failed')
                ->and(PaymentStatus::CANCELLED->getDescription())
                ->toBe('Payment was cancelled')
                ->and(PaymentStatus::REFUNDED->getDescription())
                ->toBe('Payment was refunded')
                ->and(PaymentStatus::PARTIALLY_REFUNDED->getDescription())
                ->toBe('Payment was partially refunded')
                ->and(PaymentStatus::SUCCEEDED->getDescription())
                ->toBe('Payment completed successfully');
        });
    });

    describe('Gateway Mapping - Stripe', function (): void {
        it('maps Stripe statuses to payment statuses correctly', function (): void {
            expect(PaymentStatus::fromStripeStatus('requires_payment_method'))
                ->toBe(PaymentStatus::PENDING)
                ->and(PaymentStatus::fromStripeStatus('requires_confirmation'))
                ->toBe(PaymentStatus::PENDING)
                ->and(PaymentStatus::fromStripeStatus('requires_action'))
                ->toBe(PaymentStatus::REQUIRES_ACTION)
                ->and(PaymentStatus::fromStripeStatus('processing'))
                ->toBe(PaymentStatus::PROCESSING)
                ->and(PaymentStatus::fromStripeStatus('requires_capture'))
                ->toBe(PaymentStatus::PROCESSING)
                ->and(PaymentStatus::fromStripeStatus('succeeded'))
                ->toBe(PaymentStatus::COMPLETED)
                ->and(PaymentStatus::fromStripeStatus('canceled'))
                ->toBe(PaymentStatus::CANCELLED);
        });

        it('maps unknown Stripe status to failed', function (): void {
            expect(PaymentStatus::fromStripeStatus('unknown_status'))
                ->toBe(PaymentStatus::FAILED)
                ->and(PaymentStatus::fromStripeStatus(''))
                ->toBe(PaymentStatus::FAILED)
                ->and(PaymentStatus::fromStripeStatus('invalid'))
                ->toBe(PaymentStatus::FAILED);
        });

        it('handles Stripe status edge cases', function (): void {
            expect(PaymentStatus::fromStripeStatus('SUCCEEDED'))
                ->toBe(PaymentStatus::FAILED) // Case sensitive
                ->and(PaymentStatus::fromStripeStatus(' succeeded '))
                ->toBe(PaymentStatus::FAILED); // No trimming
        });
    });

    describe('Gateway Mapping - PayPal', function (): void {
        it('maps PayPal statuses to payment statuses correctly', function (): void {
            expect(PaymentStatus::fromPayPalStatus('CREATED'))
                ->toBe(PaymentStatus::PENDING)
                ->and(PaymentStatus::fromPayPalStatus('SAVED'))
                ->toBe(PaymentStatus::PENDING)
                ->and(PaymentStatus::fromPayPalStatus('APPROVED'))
                ->toBe(PaymentStatus::REQUIRES_ACTION)
                ->and(PaymentStatus::fromPayPalStatus('COMPLETED'))
                ->toBe(PaymentStatus::COMPLETED)
                ->and(PaymentStatus::fromPayPalStatus('CANCELLED'))
                ->toBe(PaymentStatus::CANCELLED)
                ->and(PaymentStatus::fromPayPalStatus('VOIDED'))
                ->toBe(PaymentStatus::CANCELLED)
                ->and(PaymentStatus::fromPayPalStatus('FAILED'))
                ->toBe(PaymentStatus::FAILED)
                ->and(PaymentStatus::fromPayPalStatus('DENIED'))
                ->toBe(PaymentStatus::FAILED);
        });

        it('maps unknown PayPal status to failed', function (): void {
            expect(PaymentStatus::fromPayPalStatus('unknown_status'))
                ->toBe(PaymentStatus::FAILED)
                ->and(PaymentStatus::fromPayPalStatus(''))
                ->toBe(PaymentStatus::FAILED)
                ->and(PaymentStatus::fromPayPalStatus('invalid'))
                ->toBe(PaymentStatus::FAILED);
        });

        it('handles PayPal status case insensitivity', function (): void {
            expect(PaymentStatus::fromPayPalStatus('created'))
                ->toBe(PaymentStatus::PENDING)
                ->and(PaymentStatus::fromPayPalStatus('Created'))
                ->toBe(PaymentStatus::PENDING)
                ->and(PaymentStatus::fromPayPalStatus('CREATED'))
                ->toBe(PaymentStatus::PENDING);
        });

        it('handles PayPal status with whitespace', function (): void {
            expect(PaymentStatus::fromPayPalStatus(' COMPLETED '))
                ->toBe(PaymentStatus::FAILED); // No trimming in implementation
        });
    });

    describe('State Logic Consistency', function (): void {
        it('ensures final states cannot transition', function (): void {
            $finalStatuses = [
                PaymentStatus::COMPLETED,
                PaymentStatus::FAILED,
                PaymentStatus::CANCELLED,
                PaymentStatus::REFUNDED,
            ];

            foreach ($finalStatuses as $status) {
                expect($status->isFinal())->toBeTrue();

                // Final states shouldn't require user action (except completed might need refund action)
                if ($status !== PaymentStatus::COMPLETED) {
                    expect($status->requiresUserAction())->toBeFalse();
                }
            }
        });

        it('ensures only appropriate statuses can be cancelled', function (): void {
            $cancellableStatuses = [PaymentStatus::PENDING, PaymentStatus::REQUIRES_ACTION];

            foreach (PaymentStatus::cases() as $status) {
                if (in_array($status, $cancellableStatuses, true)) {
                    expect($status->canBeCancelled())->toBeTrue();
                } else {
                    expect($status->canBeCancelled())->toBeFalse();
                }
            }
        });

        it('ensures only appropriate statuses can be refunded', function (): void {
            $refundableStatuses = [PaymentStatus::COMPLETED, PaymentStatus::PARTIALLY_REFUNDED];

            foreach (PaymentStatus::cases() as $status) {
                if (in_array($status, $refundableStatuses, true)) {
                    expect($status->canBeRefunded())->toBeTrue();
                } else {
                    expect($status->canBeRefunded())->toBeFalse();
                }
            }
        });

        it('ensures only completed status is successful', function (): void {
            foreach (PaymentStatus::cases() as $status) {
                if ($status === PaymentStatus::COMPLETED) {
                    expect($status->isSuccessful())->toBeTrue();
                } else {
                    expect($status->isSuccessful())->toBeFalse();
                }
            }
        });
    });

    describe('Gateway Integration Testing', function (): void {
        it('covers all common Stripe payment intent statuses', function (): void {
            $stripeStatuses = [
                'requires_payment_method',
                'requires_confirmation',
                'requires_action',
                'processing',
                'requires_capture',
                'succeeded',
                'canceled',
            ];

            foreach ($stripeStatuses as $stripeStatus) {
                $paymentStatus = PaymentStatus::fromStripeStatus($stripeStatus);
                expect($paymentStatus)->toBeInstanceOf(PaymentStatus::class);
            }
        });

        it('covers all common PayPal order statuses', function (): void {
            $paypalStatuses = [
                'CREATED',
                'SAVED',
                'APPROVED',
                'COMPLETED',
                'CANCELLED',
                'VOIDED',
                'FAILED',
                'DENIED',
            ];

            foreach ($paypalStatuses as $paypalStatus) {
                $paymentStatus = PaymentStatus::fromPayPalStatus($paypalStatus);
                expect($paymentStatus)->toBeInstanceOf(PaymentStatus::class);
            }
        });
    });

    describe('Edge Cases and Error Handling', function (): void {
        it('handles all enum values in all methods', function (): void {
            foreach (PaymentStatus::cases() as $status) {
                expect($status->getDescription())->toBeString()
                    ->and($status->isSuccessful())->toBeBool()
                    ->and($status->isFinal())->toBeBool()
                    ->and($status->canBeCancelled())->toBeBool()
                    ->and($status->canBeRefunded())->toBeBool()
                    ->and($status->requiresUserAction())->toBeBool();
            }
        });

        it('maintains consistent behavior across gateway mappings', function (): void {
            // Test that equivalent statuses map to the same PaymentStatus
            expect(PaymentStatus::fromStripeStatus('succeeded'))
                ->toBe(PaymentStatus::COMPLETED)
                ->and(PaymentStatus::fromPayPalStatus('COMPLETED'))
                ->toBe(PaymentStatus::COMPLETED);

            expect(PaymentStatus::fromStripeStatus('canceled'))
                ->toBe(PaymentStatus::CANCELLED)
                ->and(PaymentStatus::fromPayPalStatus('CANCELLED'))
                ->toBe(PaymentStatus::CANCELLED);
        });

        it('handles empty and null-like inputs for gateway mapping', function (): void {
            expect(PaymentStatus::fromStripeStatus(''))
                ->toBe(PaymentStatus::FAILED)
                ->and(PaymentStatus::fromPayPalStatus(''))
                ->toBe(PaymentStatus::FAILED);
        });
    });

    describe('Enum Serialization and Deserialization', function (): void {
        it('can be serialized to string values', function (): void {
            expect(PaymentStatus::PENDING->value)->toBe('pending')
                ->and(PaymentStatus::PROCESSING->value)->toBe('processing')
                ->and(PaymentStatus::REQUIRES_ACTION->value)->toBe('requires_action')
                ->and(PaymentStatus::COMPLETED->value)->toBe('completed')
                ->and(PaymentStatus::FAILED->value)->toBe('failed')
                ->and(PaymentStatus::CANCELLED->value)->toBe('cancelled')
                ->and(PaymentStatus::REFUNDED->value)->toBe('refunded')
                ->and(PaymentStatus::PARTIALLY_REFUNDED->value)->toBe('partially_refunded')
                ->and(PaymentStatus::SUCCEEDED->value)->toBe('succeeded');
        });

        it('can be created from string values using from()', function (): void {
            expect(PaymentStatus::from('pending'))->toBe(PaymentStatus::PENDING)
                ->and(PaymentStatus::from('processing'))->toBe(PaymentStatus::PROCESSING)
                ->and(PaymentStatus::from('requires_action'))->toBe(PaymentStatus::REQUIRES_ACTION)
                ->and(PaymentStatus::from('completed'))->toBe(PaymentStatus::COMPLETED)
                ->and(PaymentStatus::from('failed'))->toBe(PaymentStatus::FAILED)
                ->and(PaymentStatus::from('cancelled'))->toBe(PaymentStatus::CANCELLED)
                ->and(PaymentStatus::from('refunded'))->toBe(PaymentStatus::REFUNDED)
                ->and(PaymentStatus::from('partially_refunded'))->toBe(PaymentStatus::PARTIALLY_REFUNDED)
                ->and(PaymentStatus::from('succeeded'))->toBe(PaymentStatus::SUCCEEDED);
        });

        it('throws ValueError for invalid from() values', function (): void {
            expect(fn () => PaymentStatus::from('invalid_status'))
                ->toThrow(ValueError::class);

            expect(fn () => PaymentStatus::from(''))
                ->toThrow(ValueError::class);

            expect(fn () => PaymentStatus::from('PENDING'))
                ->toThrow(ValueError::class); // Case sensitive
        });

        it('returns null for invalid tryFrom() values', function (): void {
            expect(PaymentStatus::tryFrom('invalid_status'))->toBeNull()
                ->and(PaymentStatus::tryFrom(''))->toBeNull()
                ->and(PaymentStatus::tryFrom('PENDING'))->toBeNull(); // Case sensitive
        });

        it('returns correct enum for valid tryFrom() values', function (): void {
            expect(PaymentStatus::tryFrom('pending'))->toBe(PaymentStatus::PENDING)
                ->and(PaymentStatus::tryFrom('completed'))->toBe(PaymentStatus::COMPLETED)
                ->and(PaymentStatus::tryFrom('failed'))->toBe(PaymentStatus::FAILED);
        });
    });

    describe('Business Logic Validation', function (): void {
        it('validates logical state transitions', function (): void {
            // Pending payments should not be final or successful
            expect(PaymentStatus::PENDING->isFinal())->toBeFalse()
                ->and(PaymentStatus::PENDING->isSuccessful())->toBeFalse()
                ->and(PaymentStatus::PENDING->canBeCancelled())->toBeTrue();

            // Processing payments should not be final, successful, or cancellable
            expect(PaymentStatus::PROCESSING->isFinal())->toBeFalse()
                ->and(PaymentStatus::PROCESSING->isSuccessful())->toBeFalse()
                ->and(PaymentStatus::PROCESSING->canBeCancelled())->toBeFalse();

            // Completed payments should be final, successful, and refundable
            expect(PaymentStatus::COMPLETED->isFinal())->toBeTrue()
                ->and(PaymentStatus::COMPLETED->isSuccessful())->toBeTrue()
                ->and(PaymentStatus::COMPLETED->canBeRefunded())->toBeTrue()
                ->and(PaymentStatus::COMPLETED->canBeCancelled())->toBeFalse();
        });

        it('validates refund state logic', function (): void {
            // Only completed and partially refunded can be refunded further
            expect(PaymentStatus::COMPLETED->canBeRefunded())->toBeTrue()
                ->and(PaymentStatus::PARTIALLY_REFUNDED->canBeRefunded())->toBeTrue();

            // Refunded payments cannot be refunded again
            expect(PaymentStatus::REFUNDED->canBeRefunded())->toBeFalse();

            // Partially refunded is not a final state (can be refunded further)
            expect(PaymentStatus::PARTIALLY_REFUNDED->isFinal())->toBeFalse();
        });

        it('validates action requirement logic', function (): void {
            // Only REQUIRES_ACTION status should require user action
            foreach (PaymentStatus::cases() as $status) {
                if ($status === PaymentStatus::REQUIRES_ACTION) {
                    expect($status->requiresUserAction())->toBeTrue();
                } else {
                    expect($status->requiresUserAction())->toBeFalse();
                }
            }
        });
    });

    describe('String Representation and JSON', function (): void {
        it('can be converted to string via value property', function (): void {
            foreach (PaymentStatus::cases() as $status) {
                expect($status->value)->toBeString()->not()->toBeEmpty();
            }
        });

        it('maintains consistent naming convention', function (): void {
            // All values should be lowercase with underscores
            foreach (PaymentStatus::cases() as $status) {
                expect($status->value)->toMatch('/^[a-z_]+$/');
            }
        });

        it('has unique values for all cases', function (): void {
            $values = array_map(fn ($case) => $case->value, PaymentStatus::cases());
            $uniqueValues = array_unique($values);

            expect(count($values))->toBe(count($uniqueValues));
        });

        it('can be JSON serialized and maintains value', function (): void {
            foreach (PaymentStatus::cases() as $status) {
                $json = json_encode($status);
                expect($json)->toBe('"' . $status->value . '"');
            }
        });
    });
});
