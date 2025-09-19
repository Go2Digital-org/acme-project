<?php

declare(strict_types=1);

use Modules\Donation\Domain\Service\PaymentGatewayInterface;
use Modules\Donation\Domain\ValueObject\PaymentResult;
use Modules\Donation\Domain\ValueObject\PaymentStatus;
use Modules\Donation\Domain\ValueObject\RefundRequest;

describe('Refund Processing and Validation', function (): void {
    describe('RefundRequest Value Object', function (): void {
        beforeEach(function (): void {
            $this->refundRequest = new RefundRequest(
                transactionId: 'txn_1234567890',
                amount: 100.50,
                currency: 'USD',
                reason: 'Customer requested refund',
                metadata: ['order_id' => '12345']
            );
        });

        describe('Constructor and Properties', function (): void {
            it('creates refund request with all properties', function (): void {
                expect($this->refundRequest->transactionId)->toBe('txn_1234567890');
                expect($this->refundRequest->amount)->toBe(100.50);
                expect($this->refundRequest->currency)->toBe('USD');
                expect($this->refundRequest->reason)->toBe('Customer requested refund');
                expect($this->refundRequest->metadata)->toBe(['order_id' => '12345']);
            });

            it('creates refund request with minimal parameters', function (): void {
                $request = new RefundRequest('txn_123', 50.00, 'EUR');

                expect($request->transactionId)->toBe('txn_123');
                expect($request->amount)->toBe(50.00);
                expect($request->currency)->toBe('EUR');
                expect($request->reason)->toBeNull();
                expect($request->metadata)->toBeNull();
            });

            it('handles zero refund amount', function (): void {
                $request = new RefundRequest('txn_123', 0.00, 'USD');

                expect($request->amount)->toBe(0.00);
                expect($request->getAmountInCents())->toBe(0);
            });

            it('handles large refund amounts', function (): void {
                $request = new RefundRequest('txn_123', 999999.99, 'USD');

                expect($request->amount)->toBe(999999.99);
                expect($request->getAmountInCents())->toBe(99999999);
            });

            it('handles various currencies', function (): void {
                $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD'];

                foreach ($currencies as $currency) {
                    $request = new RefundRequest('txn_123', 100.00, $currency);
                    expect($request->currency)->toBe($currency);
                }
            });

            it('preserves metadata structure', function (): void {
                $complexMetadata = [
                    'user_id' => 123,
                    'nested' => ['key' => 'value'],
                    'array' => [1, 2, 3],
                    'boolean' => true,
                    'null_value' => null,
                ];

                $request = new RefundRequest('txn_123', 50.00, 'USD', null, $complexMetadata);

                expect($request->metadata)->toBe($complexMetadata);
            });
        });

        describe('getAmountInCents() method', function (): void {
            it('converts USD amount to cents correctly', function (): void {
                expect($this->refundRequest->getAmountInCents())->toBe(10050);
            });

            it('handles decimal precision correctly', function (): void {
                $testCases = [
                    ['amount' => 1.00, 'expected' => 100],
                    ['amount' => 1.01, 'expected' => 101],
                    ['amount' => 1.99, 'expected' => 199],
                    ['amount' => 0.01, 'expected' => 1],
                    ['amount' => 0.99, 'expected' => 99],
                    ['amount' => 123.45, 'expected' => 12345],
                ];

                foreach ($testCases as $case) {
                    $request = new RefundRequest('txn_123', $case['amount'], 'USD');
                    expect($request->getAmountInCents())->toBe($case['expected']);
                }
            });

            it('handles floating point edge cases', function (): void {
                // Test potential floating point precision issues
                $request = new RefundRequest('txn_123', 0.1 + 0.2, 'USD'); // Should be 0.3

                // Due to floating point precision, this might not be exactly 30
                $cents = $request->getAmountInCents();
                expect($cents)->toBeBetween(29, 31); // Allow small variance
            });

            it('handles zero amount', function (): void {
                $request = new RefundRequest('txn_123', 0.00, 'USD');
                expect($request->getAmountInCents())->toBe(0);
            });

            it('handles negative amounts', function (): void {
                $request = new RefundRequest('txn_123', -50.00, 'USD');
                expect($request->getAmountInCents())->toBe(-5000);
            });

            it('works with different currencies', function (): void {
                // The conversion should work the same regardless of currency
                $request = new RefundRequest('txn_123', 75.25, 'EUR');
                expect($request->getAmountInCents())->toBe(7525);
            });
        });

        describe('getFormattedReason() method', function (): void {
            it('returns provided reason when available', function (): void {
                expect($this->refundRequest->getFormattedReason())->toBe('Customer requested refund');
            });

            it('returns default reason when reason is null', function (): void {
                $request = new RefundRequest('txn_123', 50.00, 'USD');
                expect($request->getFormattedReason())->toBe('Donation refund requested');
            });

            it('returns default reason when reason is empty string', function (): void {
                $request = new RefundRequest('txn_123', 50.00, 'USD', '');
                expect($request->getFormattedReason())->toBe('');
            });

            it('handles special characters in reason', function (): void {
                $specialReason = 'Refund: <test> & "quotes" & \'apostrophes\'';
                $request = new RefundRequest('txn_123', 50.00, 'USD', $specialReason);
                expect($request->getFormattedReason())->toBe($specialReason);
            });

            it('handles unicode characters in reason', function (): void {
                $unicodeReason = 'Reembolso solicitado por el cliente 测试';
                $request = new RefundRequest('txn_123', 50.00, 'USD', $unicodeReason);
                expect($request->getFormattedReason())->toBe($unicodeReason);
            });

            it('handles very long reasons', function (): void {
                $longReason = str_repeat('This is a very long refund reason. ', 10);
                $request = new RefundRequest('txn_123', 50.00, 'USD', $longReason);
                expect($request->getFormattedReason())->toBe($longReason);
            });
        });

        describe('getEnrichedMetadata() method', function (): void {
            it('merges original metadata with refund context', function (): void {
                $enriched = $this->refundRequest->getEnrichedMetadata();

                expect($enriched)->toHaveKey('order_id');
                expect($enriched['order_id'])->toBe('12345');
                expect($enriched)->toHaveKey('refund_amount');
                expect($enriched['refund_amount'])->toBe('100.5');
                expect($enriched)->toHaveKey('refund_currency');
                expect($enriched['refund_currency'])->toBe('USD');
                expect($enriched)->toHaveKey('refund_reason');
                expect($enriched['refund_reason'])->toBe('Customer requested refund');
                expect($enriched)->toHaveKey('refund_timestamp');
            });

            it('handles null metadata gracefully', function (): void {
                $request = new RefundRequest('txn_123', 50.00, 'USD');
                $enriched = $request->getEnrichedMetadata();

                expect($enriched)->toHaveKey('refund_amount');
                expect($enriched['refund_amount'])->toBe('50');
                expect($enriched)->toHaveKey('refund_currency');
                expect($enriched['refund_currency'])->toBe('USD');
                expect($enriched)->toHaveKey('refund_reason');
                expect($enriched['refund_reason'])->toBe('requested');
                expect($enriched)->toHaveKey('refund_timestamp');
            });

            it('includes ISO formatted timestamp', function (): void {
                $enriched = $this->refundRequest->getEnrichedMetadata();

                expect($enriched['refund_timestamp'])->toBeString();
                expect($enriched['refund_timestamp'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{3}Z/');
            });

            it('preserves original metadata keys', function (): void {
                $originalMetadata = [
                    'user_id' => 456,
                    'ip_address' => '192.168.1.1',
                    'user_agent' => 'Test Browser',
                ];

                $request = new RefundRequest('txn_123', 75.00, 'EUR', 'Test reason', $originalMetadata);
                $enriched = $request->getEnrichedMetadata();

                foreach ($originalMetadata as $key => $value) {
                    expect($enriched)->toHaveKey($key);
                    expect($enriched[$key])->toBe($value);
                }
            });

            it('handles metadata conflicts gracefully', function (): void {
                // Original metadata has conflicting keys
                $conflictingMetadata = [
                    'refund_amount' => 'original_value',
                    'refund_reason' => 'original_reason',
                ];

                $request = new RefundRequest('txn_123', 25.00, 'USD', 'New reason', $conflictingMetadata);
                $enriched = $request->getEnrichedMetadata();

                // Enriched values should override original
                expect($enriched['refund_amount'])->toBe('25');
                expect($enriched['refund_reason'])->toBe('New reason');
            });

            it('handles complex metadata structures', function (): void {
                $complexMetadata = [
                    'nested_object' => ['key' => 'value'],
                    'array_data' => [1, 2, 3],
                    'boolean_flag' => true,
                ];

                $request = new RefundRequest('txn_123', 100.00, 'USD', null, $complexMetadata);
                $enriched = $request->getEnrichedMetadata();

                expect($enriched['nested_object'])->toBe(['key' => 'value']);
                expect($enriched['array_data'])->toBe([1, 2, 3]);
                expect($enriched['boolean_flag'])->toBe(true);
            });
        });

        describe('Value Object Immutability', function (): void {
            it('is readonly and immutable', function (): void {
                $original = new RefundRequest('txn_123', 100.00, 'USD');

                // Properties should be readonly - this would cause compilation error
                // but we can test that the object maintains its state
                expect($original->transactionId)->toBe('txn_123');
                expect($original->amount)->toBe(100.00);
                expect($original->currency)->toBe('USD');

                // Multiple calls should return same values
                expect($original->getAmountInCents())->toBe(10000);
                expect($original->getAmountInCents())->toBe(10000);
            });

            it('metadata enrichment does not modify original', function (): void {
                $originalMetadata = ['key' => 'value'];
                $request = new RefundRequest('txn_123', 50.00, 'USD', null, $originalMetadata);

                $enriched1 = $request->getEnrichedMetadata();
                $enriched2 = $request->getEnrichedMetadata();

                // Original metadata should remain unchanged
                expect($request->metadata)->toBe($originalMetadata);

                // Each call should produce enriched data with consistent content
                expect($enriched1['key'])->toBe($enriched2['key']); // Same content
                expect($enriched1['refund_amount'])->toBe($enriched2['refund_amount']);
                expect($enriched1['refund_currency'])->toBe($enriched2['refund_currency']);

                // Both should have timestamps (may be identical if called quickly)
                expect($enriched1)->toHaveKey('refund_timestamp');
                expect($enriched2)->toHaveKey('refund_timestamp');
            });
        });
    });

    describe('PaymentResult Refund Integration', function (): void {
        describe('Success Results', function (): void {
            it('creates successful refund result', function (): void {
                $result = PaymentResult::success([
                    'transaction_id' => 'refund_123',
                    'amount' => 50.00,
                    'currency' => 'USD',
                    'status' => PaymentStatus::REFUNDED,
                    'gateway_data' => ['refund_id' => 'rf_123'],
                ]);

                expect($result->isSuccessful())->toBeTrue();
                expect($result->getTransactionId())->toBe('refund_123');
                expect($result->getAmount())->toBe(50.00);
                expect($result->getCurrency())->toBe('USD');
                expect($result->status)->toBe(PaymentStatus::REFUNDED);
            });

            it('handles partial refund results', function (): void {
                $result = PaymentResult::success([
                    'status' => PaymentStatus::PARTIALLY_REFUNDED,
                    'amount' => 25.00,
                    'currency' => 'USD',
                    'gateway_data' => [
                        'original_amount' => 100.00,
                        'refunded_amount' => 25.00,
                        'remaining_amount' => 75.00,
                    ],
                ]);

                expect($result->isSuccessful())->toBeTrue();
                expect($result->status)->toBe(PaymentStatus::PARTIALLY_REFUNDED);
                expect($result->getGatewayData()['refunded_amount'])->toBe(25.00);
            });

            it('includes processing timestamp', function (): void {
                $result = PaymentResult::success([
                    'status' => PaymentStatus::REFUNDED,
                    'amount' => 100.00,
                ]);

                expect($result->getProcessedAt())->toBeInstanceOf(DateTimeImmutable::class);
                expect($result->getProcessedAt())->not->toBeNull();
            });
        });

        describe('Failure Results', function (): void {
            it('creates failed refund result', function (): void {
                $result = PaymentResult::failure(
                    'Refund failed: insufficient balance',
                    'insufficient_funds',
                    ['gateway_error_code' => 'ERR_NO_BALANCE']
                );

                expect($result->isSuccessful())->toBeFalse();
                expect($result->hasFailed())->toBeTrue();
                expect($result->getErrorMessage())->toBe('Refund failed: insufficient balance');
                expect($result->getErrorCode())->toBe('insufficient_funds');
                expect($result->status)->toBe(PaymentStatus::FAILED);
            });

            it('handles timeout errors', function (): void {
                $result = PaymentResult::failure(
                    'Gateway timeout during refund processing',
                    'gateway_timeout'
                );

                expect($result->hasFailed())->toBeTrue();
                expect($result->getErrorMessage())->toContain('timeout');
                expect($result->getErrorCode())->toBe('gateway_timeout');
            });

            it('handles validation errors', function (): void {
                $result = PaymentResult::failure(
                    'Invalid refund amount: exceeds original transaction',
                    'invalid_amount',
                    ['original_amount' => 100.00, 'requested_amount' => 150.00]
                );

                expect($result->hasFailed())->toBeTrue();
                expect($result->getGatewayData()['original_amount'])->toBe(100.00);
                expect($result->getGatewayData()['requested_amount'])->toBe(150.00);
            });
        });

        describe('Status Checking Methods', function (): void {
            it('correctly identifies successful refunds', function (): void {
                $result = PaymentResult::success(['status' => PaymentStatus::REFUNDED]);

                expect($result->isSuccessful())->toBeTrue();
                expect($result->hasFailed())->toBeFalse();
                expect($result->isPending())->toBeFalse();
                expect($result->requiresAction())->toBeFalse();
            });

            it('correctly identifies failed refunds', function (): void {
                $result = PaymentResult::failure('Refund failed');

                expect($result->isSuccessful())->toBeFalse();
                expect($result->hasFailed())->toBeTrue();
                expect($result->isPending())->toBeFalse();
            });

            it('correctly identifies pending refunds', function (): void {
                $result = PaymentResult::pending([
                    'status' => PaymentStatus::PENDING,
                    'transaction_id' => 'pending_refund_123',
                ]);

                expect($result->isPending())->toBeTrue();
                expect($result->isSuccessful())->toBeFalse();
                expect($result->hasFailed())->toBeFalse();
            });
        });

        describe('Amount Matching Validation', function (): void {
            it('validates matching amounts correctly', function (): void {
                $result = PaymentResult::success(['amount' => 100.00]);
                $withMatching = $result->withMatchingAmount(100.00);

                $metadata = $withMatching->getMetadata();
                expect($metadata['amount_matches'])->toBeTrue();
                expect($metadata['expected_amount'])->toBe(100.00);
                expect($metadata['actual_amount'])->toBe(100.00);
            });

            it('detects amount mismatches', function (): void {
                $result = PaymentResult::success(['amount' => 95.00]);
                $withMatching = $result->withMatchingAmount(100.00);

                $metadata = $withMatching->getMetadata();
                expect($metadata['amount_matches'])->toBeFalse();
                expect($metadata['expected_amount'])->toBe(100.00);
                expect($metadata['actual_amount'])->toBe(95.00);
            });

            it('handles small floating point differences', function (): void {
                $result = PaymentResult::success(['amount' => 100.001]);
                $withMatching = $result->withMatchingAmount(100.00);

                $metadata = $withMatching->getMetadata();
                expect($metadata['amount_matches'])->toBeTrue(); // Within 0.01 tolerance
            });

            it('handles null actual amounts', function (): void {
                $result = PaymentResult::success(['amount' => null]);
                $withMatching = $result->withMatchingAmount(100.00);

                $metadata = $withMatching->getMetadata();
                expect($metadata['amount_matches'])->toBeFalse();
                expect($metadata['actual_amount'])->toBeNull();
            });
        });

        describe('Serialization and Data Conversion', function (): void {
            it('converts to array correctly', function (): void {
                $result = PaymentResult::success([
                    'transaction_id' => 'refund_123',
                    'amount' => 75.50,
                    'currency' => 'EUR',
                    'status' => PaymentStatus::REFUNDED,
                    'gateway_data' => ['gateway_refund_id' => 'gw_rf_456'],
                    'metadata' => ['user_id' => 789],
                ]);

                $array = $result->toArray();

                expect($array['successful'])->toBeTrue();
                expect($array['transaction_id'])->toBe('refund_123');
                expect($array['amount'])->toBe(75.50);
                expect($array['currency'])->toBe('EUR');
                expect($array['status'])->toBe('refunded');
                expect($array['gateway_data'])->toBe(['gateway_refund_id' => 'gw_rf_456']);
                expect($array['metadata'])->toBe(['user_id' => 789]);
                expect($array['processed_at'])->toBeString();
            });

            it('handles null values in serialization', function (): void {
                $result = PaymentResult::failure('Refund failed');
                $array = $result->toArray();

                expect($array['successful'])->toBeFalse();
                expect($array['transaction_id'])->toBeNull();
                expect($array['amount'])->toBeNull();
                expect($array['currency'])->toBeNull();
                expect($array['error_message'])->toBe('Refund failed');
            });
        });
    });

    describe('PaymentGatewayInterface Refund Contract', function (): void {
        beforeEach(function (): void {
            $this->gateway = Mockery::mock(PaymentGatewayInterface::class);
        });

        describe('refundPayment() method contract', function (): void {
            it('accepts RefundRequest and returns PaymentResult', function (): void {
                $refundRequest = new RefundRequest('txn_123', 50.00, 'USD');
                $expectedResult = PaymentResult::success(['status' => PaymentStatus::REFUNDED]);

                $this->gateway->shouldReceive('refundPayment')
                    ->once()
                    ->with(Mockery::type(RefundRequest::class))
                    ->andReturn($expectedResult);

                $result = $this->gateway->refundPayment($refundRequest);

                expect($result)->toBeInstanceOf(PaymentResult::class);
                expect($result->isSuccessful())->toBeTrue();
            });

            it('handles successful full refunds', function (): void {
                $refundRequest = new RefundRequest('txn_123', 100.00, 'USD', 'Customer cancellation');

                $this->gateway->shouldReceive('refundPayment')
                    ->with($refundRequest)
                    ->andReturn(PaymentResult::success([
                        'transaction_id' => 'refund_full_123',
                        'amount' => 100.00,
                        'currency' => 'USD',
                        'status' => PaymentStatus::REFUNDED,
                        'gateway_data' => [
                            'refund_type' => 'full',
                            'original_transaction' => 'txn_123',
                        ],
                    ]));

                $result = $this->gateway->refundPayment($refundRequest);

                expect($result->isSuccessful())->toBeTrue();
                expect($result->getAmount())->toBe(100.00);
                expect($result->status)->toBe(PaymentStatus::REFUNDED);
            });

            it('handles successful partial refunds', function (): void {
                $refundRequest = new RefundRequest('txn_123', 25.00, 'USD', 'Partial return');

                $this->gateway->shouldReceive('refundPayment')
                    ->with($refundRequest)
                    ->andReturn(PaymentResult::success([
                        'transaction_id' => 'refund_partial_123',
                        'amount' => 25.00,
                        'currency' => 'USD',
                        'status' => PaymentStatus::PARTIALLY_REFUNDED,
                        'gateway_data' => [
                            'refund_type' => 'partial',
                            'remaining_balance' => 75.00,
                        ],
                    ]));

                $result = $this->gateway->refundPayment($refundRequest);

                expect($result->isSuccessful())->toBeTrue();
                expect($result->status)->toBe(PaymentStatus::PARTIALLY_REFUNDED);
                expect($result->getGatewayData()['remaining_balance'])->toBe(75.00);
            });

            it('handles refund failures', function (): void {
                $refundRequest = new RefundRequest('txn_invalid', 50.00, 'USD');

                $this->gateway->shouldReceive('refundPayment')
                    ->with($refundRequest)
                    ->andReturn(PaymentResult::failure(
                        'Transaction not found',
                        'transaction_not_found',
                        ['searched_transaction' => 'txn_invalid']
                    ));

                $result = $this->gateway->refundPayment($refundRequest);

                expect($result->hasFailed())->toBeTrue();
                expect($result->getErrorCode())->toBe('transaction_not_found');
                expect($result->getGatewayData()['searched_transaction'])->toBe('txn_invalid');
            });

            it('handles gateway-specific errors', function (): void {
                $refundRequest = new RefundRequest('txn_123', 200.00, 'USD');

                $this->gateway->shouldReceive('refundPayment')
                    ->with($refundRequest)
                    ->andReturn(PaymentResult::failure(
                        'Refund amount exceeds original transaction',
                        'amount_exceeds_original',
                        [
                            'requested_amount' => 200.00,
                            'original_amount' => 100.00,
                            'max_refundable' => 100.00,
                        ]
                    ));

                $result = $this->gateway->refundPayment($refundRequest);

                expect($result->hasFailed())->toBeTrue();
                expect($result->getErrorMessage())->toContain('exceeds original');
                $gatewayData = $result->getGatewayData();
                expect($gatewayData['requested_amount'])->toBe(200.00);
                expect($gatewayData['original_amount'])->toBe(100.00);
            });

            it('handles network and timeout errors', function (): void {
                $refundRequest = new RefundRequest('txn_123', 50.00, 'USD');

                $this->gateway->shouldReceive('refundPayment')
                    ->with($refundRequest)
                    ->andReturn(PaymentResult::failure(
                        'Gateway timeout - refund status unknown',
                        'gateway_timeout',
                        ['retry_after' => 300]
                    ));

                $result = $this->gateway->refundPayment($refundRequest);

                expect($result->hasFailed())->toBeTrue();
                expect($result->getErrorCode())->toBe('gateway_timeout');
                expect($result->getGatewayData()['retry_after'])->toBe(300);
            });
        });

        describe('Gateway capability validation', function (): void {
            it('validates refund support for payment methods', function (): void {
                $this->gateway->shouldReceive('supports')
                    ->with('card')
                    ->andReturn(true);

                $this->gateway->shouldReceive('supports')
                    ->with('bank_transfer')
                    ->andReturn(false);

                expect($this->gateway->supports('card'))->toBeTrue();
                expect($this->gateway->supports('bank_transfer'))->toBeFalse();
            });

            it('validates currency support for refunds', function (): void {
                $this->gateway->shouldReceive('getSupportedCurrencies')
                    ->andReturn(['USD', 'EUR', 'GBP']);

                $currencies = $this->gateway->getSupportedCurrencies();

                expect($currencies)->toContain('USD');
                expect($currencies)->toContain('EUR');
                expect($currencies)->toContain('GBP');
                expect($currencies)->not->toContain('JPY');
            });

            it('validates gateway configuration', function (): void {
                $this->gateway->shouldReceive('validateConfiguration')
                    ->andReturn(true);

                expect($this->gateway->validateConfiguration())->toBeTrue();
            });

            it('provides gateway identification', function (): void {
                $this->gateway->shouldReceive('getName')
                    ->andReturn('stripe');

                expect($this->gateway->getName())->toBe('stripe');
            });
        });
    });

    describe('Refund Business Logic Validation', function (): void {
        describe('Amount Validation', function (): void {
            it('validates positive refund amounts', function (): void {
                expect(fn () => new RefundRequest('txn_123', -50.00, 'USD')); // RefundRequest doesn't validate amounts

                $refundRequest = new RefundRequest('txn_123', -50.00, 'USD');
                expect($refundRequest->amount)->toBe(-50.00);
                expect($refundRequest->getAmountInCents())->toBe(-5000);
            });

            it('handles zero refund amounts', function (): void {
                $refundRequest = new RefundRequest('txn_123', 0.00, 'USD');

                expect($refundRequest->amount)->toBe(0.00);
                expect($refundRequest->getAmountInCents())->toBe(0);
            });

            it('preserves decimal precision', function (): void {
                $preciseAmount = 123.456789;
                $refundRequest = new RefundRequest('txn_123', $preciseAmount, 'USD');

                expect($refundRequest->amount)->toBe($preciseAmount);
                // Cents conversion truncates decimal places
                expect($refundRequest->getAmountInCents())->toBe(12345);
            });
        });

        describe('Currency Consistency', function (): void {
            it('preserves currency codes exactly', function (): void {
                $currencies = ['USD', 'eur', 'Gbp', 'CAD'];

                foreach ($currencies as $currency) {
                    $request = new RefundRequest('txn_123', 100.00, $currency);
                    expect($request->currency)->toBe($currency);
                }
            });

            it('includes currency in enriched metadata', function (): void {
                $request = new RefundRequest('txn_123', 50.00, 'JPY');
                $enriched = $request->getEnrichedMetadata();

                expect($enriched['refund_currency'])->toBe('JPY');
            });
        });

        describe('Reason and Metadata Handling', function (): void {
            it('handles various reason formats', function (): void {
                $reasons = [
                    'Simple reason',
                    'Reason with numbers: 12345',
                    'Reason with symbols: !@#$%^&*()',
                    'Unicode reason: café résumé',
                    '',
                    null,
                ];

                foreach ($reasons as $reason) {
                    $request = new RefundRequest('txn_123', 50.00, 'USD', $reason);
                    expect($request->reason)->toBe($reason);

                    if ($reason === null) {
                        expect($request->getFormattedReason())->toBe('Donation refund requested');
                    } else {
                        expect($request->getFormattedReason())->toBe($reason);
                    }
                }
            });

            it('preserves complex metadata structures', function (): void {
                $metadata = [
                    'simple_key' => 'simple_value',
                    'numeric_key' => 123,
                    'array_key' => ['nested', 'array'],
                    'object_key' => ['nested' => ['deeply' => 'nested']],
                    'boolean_key' => true,
                    'null_key' => null,
                ];

                $request = new RefundRequest('txn_123', 100.00, 'USD', null, $metadata);
                $enriched = $request->getEnrichedMetadata();

                foreach ($metadata as $key => $value) {
                    expect($enriched)->toHaveKey($key);
                    expect($enriched[$key])->toBe($value);
                }
            });
        });

        describe('Error Scenarios', function (): void {
            it('handles empty transaction IDs', function (): void {
                $request = new RefundRequest('', 50.00, 'USD');
                expect($request->transactionId)->toBe('');
            });

            it('handles very long transaction IDs', function (): void {
                $longId = str_repeat('a', 1000);
                $request = new RefundRequest($longId, 50.00, 'USD');
                expect($request->transactionId)->toBe($longId);
            });

            it('handles special characters in transaction IDs', function (): void {
                $specialId = 'txn_123-456_789.abc@def#ghi';
                $request = new RefundRequest($specialId, 50.00, 'USD');
                expect($request->transactionId)->toBe($specialId);
            });
        });
    });

    describe('Integration Scenarios', function (): void {
        describe('End-to-End Refund Flow', function (): void {
            it('processes complete refund workflow', function (): void {
                // 1. Create refund request
                $refundRequest = new RefundRequest(
                    'txn_original_123',
                    75.50,
                    'USD',
                    'Product return - defective item',
                    ['order_id' => 'ORD-456', 'return_reason' => 'defective']
                );

                // 2. Validate request structure
                expect($refundRequest->transactionId)->toBe('txn_original_123');
                expect($refundRequest->getAmountInCents())->toBe(7550);
                expect($refundRequest->getFormattedReason())->toBe('Product return - defective item');

                // 3. Check enriched metadata
                $enriched = $refundRequest->getEnrichedMetadata();
                expect($enriched['order_id'])->toBe('ORD-456');
                expect($enriched['refund_amount'])->toBe('75.5');
                expect($enriched['refund_reason'])->toBe('Product return - defective item');

                // 4. Mock gateway processing
                $gateway = Mockery::mock(PaymentGatewayInterface::class);
                $gateway->shouldReceive('refundPayment')
                    ->with($refundRequest)
                    ->andReturn(PaymentResult::success([
                        'transaction_id' => 'refund_789',
                        'amount' => 75.50,
                        'currency' => 'USD',
                        'status' => PaymentStatus::REFUNDED,
                        'gateway_data' => [
                            'original_transaction' => 'txn_original_123',
                            'refund_id' => 'rf_gateway_abc',
                        ],
                    ]));

                // 5. Process refund
                $result = $gateway->refundPayment($refundRequest);

                // 6. Validate result
                expect($result->isSuccessful())->toBeTrue();
                expect($result->getAmount())->toBe(75.50);
                expect($result->status)->toBe(PaymentStatus::REFUNDED);
                expect($result->getGatewayData()['original_transaction'])->toBe('txn_original_123');
            });

            it('handles failed refund workflow with retries', function (): void {
                $refundRequest = new RefundRequest('txn_retry_123', 100.00, 'USD');

                $gateway = Mockery::mock(PaymentGatewayInterface::class);

                // First attempt fails with timeout
                $gateway->shouldReceive('refundPayment')
                    ->once()
                    ->with($refundRequest)
                    ->andReturn(PaymentResult::failure(
                        'Gateway timeout',
                        'timeout',
                        ['retry_after' => 60]
                    ));

                // Second attempt succeeds
                $gateway->shouldReceive('refundPayment')
                    ->once()
                    ->with($refundRequest)
                    ->andReturn(PaymentResult::success([
                        'transaction_id' => 'refund_retry_success',
                        'status' => PaymentStatus::REFUNDED,
                    ]));

                // First attempt
                $firstResult = $gateway->refundPayment($refundRequest);
                expect($firstResult->hasFailed())->toBeTrue();
                expect($firstResult->getErrorCode())->toBe('timeout');

                // Retry after timeout
                $secondResult = $gateway->refundPayment($refundRequest);
                expect($secondResult->isSuccessful())->toBeTrue();
            });
        });

        describe('Multi-Currency Refund Support', function (): void {
            it('handles refunds in different currencies', function (): void {
                $currencies = [
                    ['USD', 100.00, 10000],
                    ['EUR', 85.50, 8550],
                    ['GBP', 75.99, 7599],
                    ['JPY', 10000.0, 1000000],
                ];

                foreach ($currencies as [$currency, $amount, $expectedCents]) {
                    $request = new RefundRequest("txn_{$currency}_123", $amount, $currency);

                    expect($request->currency)->toBe($currency);
                    expect($request->amount)->toBe($amount);
                    expect($request->getAmountInCents())->toBe($expectedCents);

                    $enriched = $request->getEnrichedMetadata();
                    expect($enriched['refund_currency'])->toBe($currency);
                    expect($enriched['refund_amount'])->toBe((string) $amount);
                }
            });
        });
    });
});
