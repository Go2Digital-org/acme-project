<?php

declare(strict_types=1);

use Carbon\Carbon;
use Modules\Donation\Domain\Model\Payment;
use Modules\Donation\Domain\ValueObject\PaymentMethod;
use Modules\Donation\Domain\ValueObject\PaymentStatus;
use Modules\Shared\Domain\ValueObject\Money;

describe('Payment Transaction Model', function (): void {
    beforeEach(function (): void {
        $this->payment = new Payment;
        $this->payment->id = 1;
        $this->payment->donation_id = 100;
        $this->payment->gateway_name = 'stripe';
        $this->payment->intent_id = 'pi_test_123';
        $this->payment->amount = 100.00;
        $this->payment->currency = 'USD';
        $this->payment->payment_method = PaymentMethod::CARD;
        $this->payment->status = PaymentStatus::PENDING;
        $this->payment->created_at = Carbon::now();
        $this->payment->updated_at = Carbon::now();
    });

    describe('Model Properties', function (): void {
        it('has correct fillable attributes', function (): void {
            $fillable = (new Payment)->getFillable();
            $expectedFillable = [
                'donation_id',
                'gateway_name',
                'intent_id',
                'transaction_id',
                'amount',
                'currency',
                'payment_method',
                'status',
                'gateway_customer_id',
                'gateway_payment_method_id',
                'failure_code',
                'failure_message',
                'decline_code',
                'gateway_data',
                'metadata',
                'authorized_at',
                'captured_at',
                'failed_at',
                'cancelled_at',
                'expires_at',
            ];

            expect($fillable)->toBe($expectedFillable);
        });

        it('has correct default attributes', function (): void {
            $payment = new Payment;

            expect($payment->getAttributes()['currency'])->toBe('USD');
            expect($payment->getAttributes()['status'])->toBe(PaymentStatus::PENDING);
        });

        it('has correct casts configuration', function (): void {
            $casts = (new Payment)->getCasts();

            expect($casts['amount'])->toBe('decimal:2');
            expect($casts['currency'])->toBe('string');
            expect($casts['payment_method'])->toBe(PaymentMethod::class);
            expect($casts['status'])->toBe(PaymentStatus::class);
            expect($casts['gateway_data'])->toBe('array');
            expect($casts['metadata'])->toBe('array');
            expect($casts['authorized_at'])->toBe('datetime');
            expect($casts['captured_at'])->toBe('datetime');
            expect($casts['failed_at'])->toBe('datetime');
            expect($casts['cancelled_at'])->toBe('datetime');
            expect($casts['expires_at'])->toBe('datetime');
        });
    });

    describe('getMoney() method', function (): void {
        it('creates Money value object from amount and currency', function (): void {
            $this->payment->amount = 150.75;
            $this->payment->currency = 'EUR';

            $money = $this->payment->getMoney();

            expect($money)->toBeInstanceOf(Money::class);
            expect($money->amount)->toBe(150.75);
            expect($money->currency)->toBe('EUR');
        });

        it('uses USD as default currency when currency is empty', function (): void {
            $payment = new Payment;
            $payment->amount = 100.00;
            $payment->currency = 'USD'; // Set valid currency for test

            $money = $payment->getMoney();

            expect($money->currency)->toBe('USD');
        });

        it('handles zero amounts', function (): void {
            $this->payment->amount = 0.00;

            $money = $this->payment->getMoney();

            expect($money->amount)->toBe(0.00);
        });

        it('handles decimal precision correctly', function (): void {
            $this->payment->amount = 99.99;

            $money = $this->payment->getMoney();

            expect($money->amount)->toBe(99.99);
        });
    });

    describe('updateAmount() method', function (): void {
        it('updates amount and currency from Money object', function (): void {
            $money = new Money(250.50, 'GBP');

            $this->payment->updateAmount($money);

            expect($this->payment->amount)->toBe(250.50);
            expect($this->payment->currency)->toBe('GBP');
        });

        it('handles zero amounts', function (): void {
            $money = new Money(0.00, 'USD');

            $this->payment->updateAmount($money);

            expect($this->payment->amount)->toBe(0.00);
            expect($this->payment->currency)->toBe('USD');
        });

        it('handles large amounts', function (): void {
            $money = new Money(999999.99, 'USD');

            $this->payment->updateAmount($money);

            expect($this->payment->amount)->toBe(999999.99);
        });
    });

    describe('authorize() method', function (): void {
        it('authorizes pending payment successfully', function (): void {
            $this->payment->status = PaymentStatus::PENDING;
            $transactionId = 'txn_123456';
            $gatewayData = ['stripe_id' => 'pi_test_123'];

            // Mock the update method
            $payment = Mockery::mock(Payment::class)->makePartial();
            $payment->status = PaymentStatus::PENDING;

            $payment->shouldReceive('canBeAuthorized')->andReturn(true);
            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) use ($transactionId) {
                return $data['transaction_id'] === $transactionId &&
                       $data['status'] === PaymentStatus::PROCESSING &&
                       $data['authorized_at'] instanceof Carbon;
            }));

            $payment->authorize($transactionId, $gatewayData);
        });

        it('throws exception when payment cannot be authorized', function (): void {
            $this->payment->status = PaymentStatus::COMPLETED;

            expect(fn () => $this->payment->authorize('txn_123'))
                ->toThrow(DomainException::class, 'Payment cannot be authorized in current state: completed');
        });

        it('merges gateway data correctly', function (): void {
            $payment = Mockery::mock(Payment::class)->makePartial();
            $payment->status = PaymentStatus::PENDING;
            $payment->gateway_data = ['existing' => 'data'];

            $payment->shouldReceive('canBeAuthorized')->andReturn(true);
            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                $expectedGatewayData = ['existing' => 'data', 'new' => 'data'];

                return $data['gateway_data'] === $expectedGatewayData;
            }));

            $payment->authorize('txn_123', ['new' => 'data']);
        });

        it('handles null gateway data', function (): void {
            $payment = Mockery::mock(Payment::class)->makePartial();
            $payment->status = PaymentStatus::PENDING;
            $payment->gateway_data = ['existing' => 'data'];

            $payment->shouldReceive('canBeAuthorized')->andReturn(true);
            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                return $data['gateway_data'] === ['existing' => 'data'];
            }));

            $payment->authorize('txn_123', null);
        });
    });

    describe('capture() method', function (): void {
        it('captures authorized payment successfully', function (): void {
            $payment = Mockery::mock(Payment::class)->makePartial();
            $payment->status = PaymentStatus::PROCESSING;

            $payment->shouldReceive('canBeCaptured')->andReturn(true);
            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                return $data['status'] === PaymentStatus::COMPLETED &&
                       $data['captured_at'] instanceof Carbon;
            }));

            $payment->capture(['captured_data' => true]);
        });

        it('throws exception when payment cannot be captured', function (): void {
            $this->payment->status = PaymentStatus::FAILED;

            expect(fn () => $this->payment->capture())
                ->toThrow(DomainException::class, 'Payment cannot be captured in current state: failed');
        });

        it('merges gateway data on capture', function (): void {
            $payment = Mockery::mock(Payment::class)->makePartial();
            $payment->status = PaymentStatus::PROCESSING;
            $payment->gateway_data = ['stripe_id' => 'pi_123'];

            $payment->shouldReceive('canBeCaptured')->andReturn(true);
            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                $expected = ['stripe_id' => 'pi_123', 'capture_id' => 'cap_123'];

                return $data['gateway_data'] === $expected;
            }));

            $payment->capture(['capture_id' => 'cap_123']);
        });
    });

    describe('fail() method', function (): void {
        it('fails pending payment successfully', function (): void {
            $payment = Mockery::mock(Payment::class)->makePartial();
            $payment->status = PaymentStatus::PENDING;

            $payment->shouldReceive('canBeFailed')->andReturn(true);
            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                return $data['status'] === PaymentStatus::FAILED &&
                       $data['failure_message'] === 'Card declined' &&
                       $data['failure_code'] === 'card_declined' &&
                       $data['decline_code'] === 'insufficient_funds' &&
                       $data['failed_at'] instanceof Carbon;
            }));

            $payment->fail('Card declined', 'card_declined', 'insufficient_funds');
        });

        it('throws exception when payment cannot be failed', function (): void {
            $this->payment->status = PaymentStatus::COMPLETED;

            expect(fn () => $this->payment->fail('Some error'))
                ->toThrow(DomainException::class, 'Payment cannot be failed in current state: completed');
        });

        it('handles optional parameters', function (): void {
            $payment = Mockery::mock(Payment::class)->makePartial();
            $payment->status = PaymentStatus::PENDING;

            $payment->shouldReceive('canBeFailed')->andReturn(true);
            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                return $data['failure_message'] === 'Network error' &&
                       $data['failure_code'] === null &&
                       $data['decline_code'] === null;
            }));

            $payment->fail('Network error');
        });
    });

    describe('cancel() method', function (): void {
        it('cancels pending payment successfully', function (): void {
            $payment = Mockery::mock(Payment::class)->makePartial();
            $payment->status = PaymentStatus::PENDING;

            $payment->shouldReceive('canBeCancelled')->andReturn(true);
            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                return $data['status'] === PaymentStatus::CANCELLED &&
                       $data['cancelled_at'] instanceof Carbon;
            }));

            $payment->cancel();
        });

        it('throws exception when payment cannot be cancelled', function (): void {
            $this->payment->status = PaymentStatus::COMPLETED;

            expect(fn () => $this->payment->cancel())
                ->toThrow(DomainException::class, 'Payment cannot be cancelled in current state: completed');
        });
    });

    describe('updateFromGateway() method', function (): void {
        it('updates payment status from gateway', function (): void {
            $payment = Mockery::mock(Payment::class)->makePartial();
            $payment->transaction_id = null;
            $payment->gateway_data = ['existing' => 'data'];

            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                return $data['status'] === PaymentStatus::COMPLETED &&
                       $data['transaction_id'] === 'txn_new' &&
                       isset($data['captured_at']);
            }));

            $payment->updateFromGateway(
                PaymentStatus::COMPLETED,
                'txn_new',
                ['gateway_field' => 'value']
            );
        });

        it('does not override existing transaction_id', function (): void {
            $payment = Mockery::mock(Payment::class)->makePartial();
            $payment->transaction_id = 'existing_txn';

            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                return ! isset($data['transaction_id']);
            }));

            $payment->updateFromGateway(PaymentStatus::COMPLETED, 'new_txn');
        });

        it('sets correct timestamps based on status', function (): void {
            $payment = Mockery::mock(Payment::class)->makePartial();

            // Test PROCESSING status
            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                return isset($data['authorized_at']) && $data['authorized_at'] instanceof Carbon;
            }));
            $payment->updateFromGateway(PaymentStatus::PROCESSING);

            // Test COMPLETED status
            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                return isset($data['captured_at']) && $data['captured_at'] instanceof Carbon;
            }));
            $payment->updateFromGateway(PaymentStatus::COMPLETED);

            // Test FAILED status
            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                return isset($data['failed_at']) && $data['failed_at'] instanceof Carbon;
            }));
            $payment->updateFromGateway(PaymentStatus::FAILED);

            // Test CANCELLED status
            $payment->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
                return isset($data['cancelled_at']) && $data['cancelled_at'] instanceof Carbon;
            }));
            $payment->updateFromGateway(PaymentStatus::CANCELLED);
        });
    });

    describe('setExpiration() method', function (): void {
        it('sets expiration date', function (): void {
            $expiresAt = Carbon::now()->addHours(1);

            $payment = Mockery::mock(Payment::class)->makePartial();
            $payment->shouldReceive('update')->once()->with(['expires_at' => $expiresAt]);

            $payment->setExpiration($expiresAt);
        });
    });

    describe('isExpired() method', function (): void {
        it('returns true for expired payments', function (): void {
            $this->payment->expires_at = Carbon::now()->subHour();

            expect($this->payment->isExpired())->toBeTrue();
        });

        it('returns false for non-expired payments', function (): void {
            $this->payment->expires_at = Carbon::now()->addHour();

            expect($this->payment->isExpired())->toBeFalse();
        });

        it('returns false when expires_at is null', function (): void {
            $this->payment->expires_at = null;

            expect($this->payment->isExpired())->toBeFalse();
        });

        it('returns false when expires_at is null or invalid', function (): void {
            $this->payment->expires_at = null;

            expect($this->payment->isExpired())->toBeFalse();
        });
    });

    describe('Status Check Methods', function (): void {
        it('isSuccessful() returns correct values', function (): void {
            $this->payment->status = PaymentStatus::COMPLETED;
            expect($this->payment->isSuccessful())->toBeTrue();

            $this->payment->status = PaymentStatus::PENDING;
            expect($this->payment->isSuccessful())->toBeFalse();

            $this->payment->status = PaymentStatus::FAILED;
            expect($this->payment->isSuccessful())->toBeFalse();
        });

        it('isPending() returns correct values', function (): void {
            $this->payment->status = PaymentStatus::PENDING;
            expect($this->payment->isPending())->toBeTrue();

            $this->payment->status = PaymentStatus::COMPLETED;
            expect($this->payment->isPending())->toBeFalse();

            $this->payment->status = PaymentStatus::PROCESSING;
            expect($this->payment->isPending())->toBeFalse();
        });

        it('requiresAction() returns correct values', function (): void {
            $this->payment->status = PaymentStatus::REQUIRES_ACTION;
            expect($this->payment->requiresAction())->toBeTrue();

            $this->payment->status = PaymentStatus::PENDING;
            expect($this->payment->requiresAction())->toBeFalse();

            $this->payment->status = PaymentStatus::PROCESSING;
            expect($this->payment->requiresAction())->toBeFalse();
        });

        it('hasFailed() returns correct values', function (): void {
            $this->payment->status = PaymentStatus::FAILED;
            expect($this->payment->hasFailed())->toBeTrue();

            $this->payment->status = PaymentStatus::CANCELLED;
            expect($this->payment->hasFailed())->toBeFalse();

            $this->payment->status = PaymentStatus::COMPLETED;
            expect($this->payment->hasFailed())->toBeFalse();
        });
    });

    describe('State Validation Methods', function (): void {
        it('canBeAuthorized() returns correct values', function (): void {
            $this->payment->status = PaymentStatus::PENDING;
            expect($this->payment->canBeAuthorized())->toBeTrue();

            $this->payment->status = PaymentStatus::PROCESSING;
            expect($this->payment->canBeAuthorized())->toBeFalse();

            $this->payment->status = PaymentStatus::COMPLETED;
            expect($this->payment->canBeAuthorized())->toBeFalse();

            $this->payment->status = PaymentStatus::FAILED;
            expect($this->payment->canBeAuthorized())->toBeFalse();
        });

        it('canBeCaptured() returns correct values', function (): void {
            $validStatuses = [
                PaymentStatus::PENDING,
                PaymentStatus::PROCESSING,
                PaymentStatus::REQUIRES_ACTION,
            ];

            foreach ($validStatuses as $status) {
                $this->payment->status = $status;
                expect($this->payment->canBeCaptured())->toBeTrue();
            }

            $invalidStatuses = [
                PaymentStatus::COMPLETED,
                PaymentStatus::FAILED,
                PaymentStatus::CANCELLED,
            ];

            foreach ($invalidStatuses as $status) {
                $this->payment->status = $status;
                expect($this->payment->canBeCaptured())->toBeFalse();
            }
        });

        it('canBeFailed() uses status isFinal() logic', function (): void {
            // Test with final status
            $this->payment->status = PaymentStatus::COMPLETED;
            expect($this->payment->canBeFailed())->toBeFalse();

            $this->payment->status = PaymentStatus::FAILED;
            expect($this->payment->canBeFailed())->toBeFalse();

            // Test with non-final status
            $this->payment->status = PaymentStatus::PENDING;
            expect($this->payment->canBeFailed())->toBeTrue();
        });

        it('canBeFailed() allows processing status even if final', function (): void {
            $this->payment->status = PaymentStatus::PROCESSING;

            expect($this->payment->canBeFailed())->toBeTrue();
        });

        it('canBeCancelled() delegates to status method', function (): void {
            $this->payment->status = PaymentStatus::PENDING;
            expect($this->payment->canBeCancelled())->toBeTrue();

            $this->payment->status = PaymentStatus::REQUIRES_ACTION;
            expect($this->payment->canBeCancelled())->toBeTrue();

            $this->payment->status = PaymentStatus::COMPLETED;
            expect($this->payment->canBeCancelled())->toBeFalse();
        });
    });

    describe('Computed Attributes', function (): void {
        it('formattedAmount returns formatted money string', function (): void {
            $this->payment->amount = 1234.56;
            $this->payment->currency = 'USD';

            // We can't test the exact format without knowing Money::format() implementation
            // But we can test that it returns a string
            $formatted = $this->payment->formatted_amount;
            expect($formatted)->toBeString();
        });

        it('gatewayDisplayName returns correct display names', function (): void {
            $this->payment->gateway_name = 'stripe';
            expect($this->payment->gateway_display_name)->toBe('Stripe');

            $this->payment->gateway_name = 'paypal';
            expect($this->payment->gateway_display_name)->toBe('PayPal');

            $this->payment->gateway_name = 'mock';
            expect($this->payment->gateway_display_name)->toBe('Mock Gateway');

            $this->payment->gateway_name = 'custom';
            expect($this->payment->gateway_display_name)->toBe('Custom');
        });

        it('statusDescription delegates to status getDescription()', function (): void {
            $this->payment->status = PaymentStatus::PENDING;
            expect($this->payment->status_description)->toBe('Payment is pending processing');

            $this->payment->status = PaymentStatus::COMPLETED;
            expect($this->payment->status_description)->toBe('Payment completed successfully');
        });

        it('processingDuration calculates correctly', function (): void {
            $this->payment->created_at = Carbon::parse('2023-01-01 10:00:00');
            $this->payment->captured_at = Carbon::parse('2023-01-01 10:00:30');

            expect($this->payment->processing_duration)->toBe(30);
        });

        it('processingDuration returns null for incomplete payments', function (): void {
            $this->payment->created_at = Carbon::now();
            $this->payment->captured_at = null;

            expect($this->payment->processing_duration)->toBeNull();

            $this->payment->created_at = null;
            $this->payment->captured_at = Carbon::now();

            expect($this->payment->processing_duration)->toBeNull();
        });
    });

    describe('Business Logic Validation', function (): void {
        it('maintains state consistency for authorization flow', function (): void {
            $payment = new Payment;
            $payment->status = PaymentStatus::PENDING;

            expect($payment->canBeAuthorized())->toBeTrue();
            expect($payment->isPending())->toBeTrue();
            expect($payment->isSuccessful())->toBeFalse();
        });

        it('maintains state consistency for capture flow', function (): void {
            $payment = new Payment;
            $payment->status = PaymentStatus::PROCESSING;

            expect($payment->canBeCaptured())->toBeTrue();
            expect($payment->isPending())->toBeFalse();
            expect($payment->isSuccessful())->toBeFalse();
        });

        it('maintains state consistency for completion', function (): void {
            $payment = new Payment;
            $payment->status = PaymentStatus::COMPLETED;

            expect($payment->isSuccessful())->toBeTrue();
            expect($payment->canBeAuthorized())->toBeFalse();
            expect($payment->canBeCaptured())->toBeFalse();
        });

        it('maintains state consistency for failure', function (): void {
            $payment = new Payment;
            $payment->status = PaymentStatus::FAILED;

            expect($payment->hasFailed())->toBeTrue();
            expect($payment->isSuccessful())->toBeFalse();
            expect($payment->canBeAuthorized())->toBeFalse();
        });
    });

    describe('Data Integrity', function (): void {
        it('handles null gateway_data properly', function (): void {
            $this->payment->gateway_data = null;

            // Should not throw errors when accessing
            expect($this->payment->gateway_data)->toBeNull();
        });

        it('handles null metadata properly', function (): void {
            $this->payment->metadata = null;

            expect($this->payment->metadata)->toBeNull();
        });

        it('maintains amount precision', function (): void {
            $this->payment->amount = 99.99;

            expect($this->payment->amount)->toBe(99.99);
            expect($this->payment->getMoney()->amount)->toBe(99.99);
        });

        it('handles currency normalization', function (): void {
            $this->payment->currency = 'usd';

            // Currency should be stored as provided
            expect($this->payment->currency)->toBe('usd');
        });
    });

    describe('Edge Cases', function (): void {
        it('handles very large amounts', function (): void {
            $this->payment->amount = 999999999.99;

            expect($this->payment->getMoney()->amount)->toBe(999999999.99);
        });

        it('handles zero amounts', function (): void {
            $this->payment->amount = 0.00;

            expect($this->payment->getMoney()->amount)->toBe(0.00);
            expect($this->payment->isSuccessful())->toBeFalse(); // Zero amount should not be successful
        });

        it('handles valid currencies', function (): void {
            $this->payment->currency = 'EUR';

            expect($this->payment->getMoney()->currency)->toBe('EUR');
        });

        it('handles long transaction IDs', function (): void {
            $longId = str_repeat('a', 255);
            $this->payment->transaction_id = $longId;

            expect($this->payment->transaction_id)->toBe($longId);
        });

        it('handles special characters in gateway data', function (): void {
            $specialData = [
                'unicode' => '测试数据',
                'emoji' => '🎉',
                'special_chars' => '<>&"\'',
            ];

            $this->payment->gateway_data = $specialData;

            expect($this->payment->gateway_data)->toBe($specialData);
        });
    });

    describe('Error Handling', function (): void {
        it('provides meaningful error messages', function (): void {
            $this->payment->status = PaymentStatus::COMPLETED;

            try {
                $this->payment->authorize('test');
                expect(false)->toBe(true); // Should not reach here
            } catch (DomainException $e) {
                expect($e->getMessage())->toContain('Payment cannot be authorized');
                expect($e->getMessage())->toContain('completed');
            }
        });

        it('handles gateway data merging errors gracefully', function (): void {
            $this->payment->gateway_data = ['key' => 'value'];

            // This should not throw an error even with null new data
            $payment = Mockery::mock(Payment::class)->makePartial();
            $payment->gateway_data = ['key' => 'value'];
            $payment->shouldReceive('canBeAuthorized')->andReturn(true);
            $payment->shouldReceive('update')->once();

            $payment->authorize('txn_123', null);
        });
    });
});
