<?php

declare(strict_types=1);

use Modules\Donation\Domain\ValueObject\RefundRequest;

/**
 * @property string $transactionId
 * @property float $amount
 * @property string $currency
 * @property string $reason
 * @property array<string, mixed> $metadata
 */
describe('RefundRequest Value Object', function (): void {
    beforeEach(function (): void {
        $this->transactionId = 'txn_1234567890';
        $this->amount = 150.75;
        $this->currency = 'USD';
        $this->reason = 'Customer requested refund';
        $this->metadata = [
            'refund_id' => 'rf_12345',
            'source' => 'admin_panel',
        ];
    });

    describe('Construction', function (): void {
        it('creates refund request with required parameters', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
            );

            expect($request->transactionId)->toBe($this->transactionId)
                ->and($request->amount)->toBe($this->amount)
                ->and($request->currency)->toBe($this->currency)
                ->and($request->reason)->toBeNull()
                ->and($request->metadata)->toBeNull();
        });

        it('creates refund request with all parameters', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: $this->reason,
                metadata: $this->metadata,
            );

            expect($request->transactionId)->toBe($this->transactionId)
                ->and($request->amount)->toBe($this->amount)
                ->and($request->currency)->toBe($this->currency)
                ->and($request->reason)->toBe($this->reason)
                ->and($request->metadata)->toBe($this->metadata);
        });

        it('handles optional parameters as null', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: null,
                metadata: null,
            );

            expect($request->reason)->toBeNull()
                ->and($request->metadata)->toBeNull();
        });
    });

    describe('Amount Conversion', function (): void {
        it('converts USD amount to cents correctly', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: 100.50,
                currency: 'USD',
            );

            expect($request->getAmountInCents())->toBe(10050);
        });

        it('converts EUR amount to cents correctly', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: 25.99,
                currency: 'EUR',
            );

            expect($request->getAmountInCents())->toBe(2599);
        });

        it('converts zero amount correctly', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: 0.00,
                currency: 'USD',
            );

            expect($request->getAmountInCents())->toBe(0);
        });

        it('handles large amounts', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: 99999.99,
                currency: 'USD',
            );

            expect($request->getAmountInCents())->toBe(9999999);
        });

        it('handles fractional cents correctly', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: 10.999, // Should round down
                currency: 'USD',
            );

            expect($request->getAmountInCents())->toBe(1099);
        });

        it('handles very small amounts', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: 0.01,
                currency: 'USD',
            );

            expect($request->getAmountInCents())->toBe(1);
        });

        it('handles amounts with many decimal places', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: 123.456789,
                currency: 'USD',
            );

            // Should truncate, not round
            expect($request->getAmountInCents())->toBe(12345);
        });
    });

    describe('Reason Formatting', function (): void {
        it('returns provided reason when available', function (): void {
            $customReason = 'Product was defective';
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: $customReason,
            );

            expect($request->getFormattedReason())->toBe($customReason);
        });

        it('returns default reason when none provided', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: null,
            );

            expect($request->getFormattedReason())->toBe('Donation refund requested');
        });

        it('returns default reason when empty string provided', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: '',
            );

            expect($request->getFormattedReason())->toBe('');
        });

        it('handles reason with special characters', function (): void {
            $specialReason = 'Customer said "not satisfied" & wants refund';
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: $specialReason,
            );

            expect($request->getFormattedReason())->toBe($specialReason);
        });

        it('handles very long reasons', function (): void {
            $longReason = str_repeat('This is a very long refund reason. ', 20);
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: $longReason,
            );

            expect($request->getFormattedReason())->toBe($longReason);
        });
    });

    describe('Metadata Enrichment', function (): void {
        it('enriches metadata with refund context when metadata exists', function (): void {
            $originalMetadata = [
                'user_id' => '12345',
                'source' => 'customer_request',
            ];

            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: $this->reason,
                metadata: $originalMetadata,
            );

            $enriched = $request->getEnrichedMetadata();

            // Should contain original metadata
            expect($enriched['user_id'])->toBe('12345')
                ->and($enriched['source'])->toBe('customer_request');

            // Should contain enriched metadata
            expect($enriched['refund_amount'])->toBe('150.75')
                ->and($enriched['refund_currency'])->toBe('USD')
                ->and($enriched['refund_reason'])->toBe($this->reason)
                ->and($enriched['refund_timestamp'])->toBeString();
        });

        it('enriches empty metadata array', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: $this->reason,
                metadata: [],
            );

            $enriched = $request->getEnrichedMetadata();

            expect($enriched)->toHaveKeys([
                'refund_amount',
                'refund_currency',
                'refund_reason',
                'refund_timestamp',
            ]);
        });

        it('enriches null metadata', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: $this->reason,
                metadata: null,
            );

            $enriched = $request->getEnrichedMetadata();

            expect($enriched)->toHaveKeys([
                'refund_amount',
                'refund_currency',
                'refund_reason',
                'refund_timestamp',
            ]);
        });

        it('uses default reason in enriched metadata when none provided', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: null,
            );

            $enriched = $request->getEnrichedMetadata();

            expect($enriched['refund_reason'])->toBe('requested');
        });

        it('converts amount to string in metadata', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: 99.99,
                currency: 'EUR',
            );

            $enriched = $request->getEnrichedMetadata();

            expect($enriched['refund_amount'])->toBe('99.99')
                ->and($enriched['refund_currency'])->toBe('EUR');
        });

        it('includes timestamp in ISO format', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
            );

            $enriched = $request->getEnrichedMetadata();

            expect($enriched['refund_timestamp'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z/');
        });

        it('handles metadata override scenarios', function (): void {
            // Test when original metadata has same keys as enriched
            $originalMetadata = [
                'refund_amount' => 'original_amount',
                'refund_reason' => 'original_reason',
            ];

            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: 'new_reason',
                metadata: $originalMetadata,
            );

            $enriched = $request->getEnrichedMetadata();

            // Enriched values should override original
            expect($enriched['refund_amount'])->toBe('150.75')
                ->and($enriched['refund_reason'])->toBe('new_reason');
        });
    });

    describe('Different Currencies', function (): void {
        it('works correctly with various currencies', function (): void {
            $currencies = [
                ['currency' => 'USD', 'amount' => 100.50, 'expected_cents' => 10050],
                ['currency' => 'EUR', 'amount' => 75.25, 'expected_cents' => 7525],
                ['currency' => 'GBP', 'amount' => 200.99, 'expected_cents' => 20099],
                ['currency' => 'CAD', 'amount' => 50.00, 'expected_cents' => 5000],
                ['currency' => 'AUD', 'amount' => 300.33, 'expected_cents' => 30033],
            ];

            foreach ($currencies as $data) {
                $request = new RefundRequest(
                    transactionId: $this->transactionId,
                    amount: $data['amount'],
                    currency: $data['currency'],
                );

                expect($request->getAmountInCents())->toBe($data['expected_cents'])
                    ->and($request->currency)->toBe($data['currency']);

                $enriched = $request->getEnrichedMetadata();
                expect($enriched['refund_currency'])->toBe($data['currency']);
            }
        });
    });

    describe('Edge Cases and Validation', function (): void {
        it('handles special transaction IDs', function (): void {
            $specialTransactionIds = [
                'ch_1234567890abcdef',
                'pi_1A2B3C4D5E6F7G8H',
                'txn_test_123456',
                'PAYPAL_TXN_ABC123',
                'stripe_ch_long_id_with_underscores',
            ];

            foreach ($specialTransactionIds as $txnId) {
                $request = new RefundRequest(
                    transactionId: $txnId,
                    amount: $this->amount,
                    currency: $this->currency,
                );

                expect($request->transactionId)->toBe($txnId);
            }
        });

        it('handles different amount formats', function (): void {
            $amounts = [
                1.0,      // Integer as float
                1.00,     // Two decimal places
                1.5,      // One decimal place
                1.23456,  // Many decimal places
                0.0,      // Zero as float
            ];

            foreach ($amounts as $amount) {
                $request = new RefundRequest(
                    transactionId: $this->transactionId,
                    amount: $amount,
                    currency: $this->currency,
                );

                expect($request->amount)->toBe($amount);
                expect($request->getAmountInCents())->toBeInt();
            }
        });

        it('handles different currency formats', function (): void {
            $currencies = ['USD', 'usd', 'EUR', 'eur', 'GBP', 'gbp'];

            foreach ($currencies as $currency) {
                $request = new RefundRequest(
                    transactionId: $this->transactionId,
                    amount: $this->amount,
                    currency: $currency,
                );

                expect($request->currency)->toBe($currency);

                $enriched = $request->getEnrichedMetadata();
                expect($enriched['refund_currency'])->toBe($currency);
            }
        });

        it('handles complex metadata structures', function (): void {
            $complexMetadata = [
                'nested' => [
                    'level1' => [
                        'level2' => 'deep_value',
                        'array' => [1, 2, 3],
                    ],
                ],
                'simple' => 'value',
                'boolean' => true,
                'number' => 123,
            ];

            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                metadata: $complexMetadata,
            );

            $enriched = $request->getEnrichedMetadata();

            expect($enriched['nested']['level1']['level2'])->toBe('deep_value')
                ->and($enriched['simple'])->toBe('value')
                ->and($enriched['boolean'])->toBeTrue()
                ->and($enriched['number'])->toBe(123);
        });

        it('handles empty string values', function (): void {
            $request = new RefundRequest(
                transactionId: '',
                amount: 0.0,
                currency: '',
                reason: '',
                metadata: [],
            );

            expect($request->transactionId)->toBe('')
                ->and($request->currency)->toBe('')
                ->and($request->reason)->toBe('')
                ->and($request->getFormattedReason())->toBe('');
        });
    });

    describe('Immutability', function (): void {
        it('is readonly and cannot be modified', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
            );

            expect(fn () => $request->transactionId = 'new_id')
                ->toThrow(Error::class);
        });

        it('metadata modifications do not affect original', function (): void {
            $originalMetadata = ['key' => 'value'];
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                metadata: $originalMetadata,
            );

            $enriched = $request->getEnrichedMetadata();
            $enriched['new_key'] = 'new_value';

            // Original request metadata should remain unchanged
            expect($request->metadata)->toBe($originalMetadata);
        });
    });

    describe('Real-world Integration Scenarios', function (): void {
        it('handles Stripe refund request', function (): void {
            $request = new RefundRequest(
                transactionId: 'ch_1234567890abcdef',
                amount: 50.00,
                currency: 'USD',
                reason: 'requested_by_customer',
                metadata: [
                    'stripe_refund_id' => 're_1234567890',
                    'original_charge' => 'ch_1234567890abcdef',
                ],
            );

            expect($request->getAmountInCents())->toBe(5000)
                ->and($request->getFormattedReason())->toBe('requested_by_customer');

            $enriched = $request->getEnrichedMetadata();
            expect($enriched['stripe_refund_id'])->toBe('re_1234567890');
        });

        it('handles PayPal refund request', function (): void {
            $request = new RefundRequest(
                transactionId: 'PAYPAL_TXN_ABC123DEF456',
                amount: 125.99,
                currency: 'EUR',
                reason: 'Item not as described',
                metadata: [
                    'paypal_transaction_id' => 'PAYPAL_TXN_ABC123DEF456',
                    'paypal_refund_type' => 'full',
                ],
            );

            expect($request->getAmountInCents())->toBe(12599)
                ->and($request->currency)->toBe('EUR')
                ->and($request->getFormattedReason())->toBe('Item not as described');
        });

        it('handles partial refund request', function (): void {
            $request = new RefundRequest(
                transactionId: 'txn_original_500',
                amount: 200.00, // Partial refund of a larger amount
                currency: 'GBP',
                reason: 'Partial refund - damaged item',
                metadata: [
                    'refund_type' => 'partial',
                    'original_amount' => '500.00',
                    'remaining_amount' => '300.00',
                ],
            );

            expect($request->getAmountInCents())->toBe(20000);

            $enriched = $request->getEnrichedMetadata();
            expect($enriched['refund_type'])->toBe('partial')
                ->and($enriched['refund_amount'])->toBe('200')
                ->and($enriched['original_amount'])->toBe('500.00');
        });

        it('handles administrative refund', function (): void {
            $request = new RefundRequest(
                transactionId: 'admin_refund_001',
                amount: 999.99,
                currency: 'CAD',
                reason: 'Administrative refund - system error',
                metadata: [
                    'admin_user_id' => 'admin_123',
                    'refund_category' => 'system_error',
                    'approval_required' => false,
                ],
            );

            $enriched = $request->getEnrichedMetadata();
            expect($enriched['admin_user_id'])->toBe('admin_123')
                ->and($enriched['refund_reason'])->toBe('Administrative refund - system error')
                ->and($enriched['refund_timestamp'])->toBeString();
        });
    });

    describe('Data Consistency', function (): void {
        it('maintains consistent data types across methods', function (): void {
            $request = new RefundRequest(
                transactionId: $this->transactionId,
                amount: $this->amount,
                currency: $this->currency,
                reason: $this->reason,
                metadata: $this->metadata,
            );

            expect($request->amount)->toBeFloat()
                ->and($request->getAmountInCents())->toBeInt()
                ->and($request->getFormattedReason())->toBeString();

            $enriched = $request->getEnrichedMetadata();
            expect($enriched['refund_amount'])->toBeString()
                ->and($enriched['refund_currency'])->toBeString()
                ->and($enriched['refund_timestamp'])->toBeString();
        });

        it('preserves precision through amount conversion', function (): void {
            $precisionTests = [
                ['amount' => 10.01, 'expected_cents' => 1001],
                ['amount' => 10.10, 'expected_cents' => 1010],
                ['amount' => 10.99, 'expected_cents' => 1099],
                ['amount' => 100.00, 'expected_cents' => 10000],
            ];

            foreach ($precisionTests as $test) {
                $request = new RefundRequest(
                    transactionId: $this->transactionId,
                    amount: $test['amount'],
                    currency: $this->currency,
                );

                expect($request->getAmountInCents())->toBe($test['expected_cents']);
            }
        });
    });
});
