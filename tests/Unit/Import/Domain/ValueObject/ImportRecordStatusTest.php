<?php

declare(strict_types=1);

use Modules\Import\Domain\ValueObject\ImportRecordStatus;

describe('ImportRecordStatus', function (): void {
    describe('enum cases', function (): void {
        it('has correct enum values', function (): void {
            expect(ImportRecordStatus::PENDING->value)->toBe('pending')
                ->and(ImportRecordStatus::SUCCESS->value)->toBe('success')
                ->and(ImportRecordStatus::FAILED->value)->toBe('failed')
                ->and(ImportRecordStatus::SKIPPED->value)->toBe('skipped');
        });

        it('can be instantiated from string values', function (): void {
            expect(ImportRecordStatus::from('pending'))->toBe(ImportRecordStatus::PENDING)
                ->and(ImportRecordStatus::from('success'))->toBe(ImportRecordStatus::SUCCESS)
                ->and(ImportRecordStatus::from('failed'))->toBe(ImportRecordStatus::FAILED)
                ->and(ImportRecordStatus::from('skipped'))->toBe(ImportRecordStatus::SKIPPED);
        });

        it('lists all cases', function (): void {
            $cases = ImportRecordStatus::cases();

            expect($cases)->toHaveCount(4)
                ->and($cases)->toContain(ImportRecordStatus::PENDING)
                ->and($cases)->toContain(ImportRecordStatus::SUCCESS)
                ->and($cases)->toContain(ImportRecordStatus::FAILED)
                ->and($cases)->toContain(ImportRecordStatus::SKIPPED);
        });
    });

    describe('isProcessed method', function (): void {
        it('returns false for pending status', function (): void {
            expect(ImportRecordStatus::PENDING->isProcessed())->toBeFalse();
        });

        it('returns true for all other statuses', function (): void {
            expect(ImportRecordStatus::SUCCESS->isProcessed())->toBeTrue()
                ->and(ImportRecordStatus::FAILED->isProcessed())->toBeTrue()
                ->and(ImportRecordStatus::SKIPPED->isProcessed())->toBeTrue();
        });

        it('works with all enum cases', function (): void {
            $processedStatuses = [];
            $unprocessedStatuses = [];

            foreach (ImportRecordStatus::cases() as $status) {
                if ($status->isProcessed()) {
                    $processedStatuses[] = $status;
                } else {
                    $unprocessedStatuses[] = $status;
                }
            }

            expect($processedStatuses)->toHaveCount(3)
                ->and($unprocessedStatuses)->toHaveCount(1)
                ->and($unprocessedStatuses[0])->toBe(ImportRecordStatus::PENDING);
        });
    });

    describe('isSuccessful method', function (): void {
        it('returns true only for success status', function (): void {
            expect(ImportRecordStatus::SUCCESS->isSuccessful())->toBeTrue();
        });

        it('returns false for all other statuses', function (): void {
            expect(ImportRecordStatus::PENDING->isSuccessful())->toBeFalse()
                ->and(ImportRecordStatus::FAILED->isSuccessful())->toBeFalse()
                ->and(ImportRecordStatus::SKIPPED->isSuccessful())->toBeFalse();
        });

        it('works with all enum cases', function (): void {
            $successfulStatuses = [];
            $unsuccessfulStatuses = [];

            foreach (ImportRecordStatus::cases() as $status) {
                if ($status->isSuccessful()) {
                    $successfulStatuses[] = $status;
                } else {
                    $unsuccessfulStatuses[] = $status;
                }
            }

            expect($successfulStatuses)->toHaveCount(1)
                ->and($unsuccessfulStatuses)->toHaveCount(3)
                ->and($successfulStatuses[0])->toBe(ImportRecordStatus::SUCCESS);
        });
    });

    describe('combined behavior', function (): void {
        it('pending is not processed and not successful', function (): void {
            $status = ImportRecordStatus::PENDING;

            expect($status->isProcessed())->toBeFalse()
                ->and($status->isSuccessful())->toBeFalse();
        });

        it('success is processed and successful', function (): void {
            $status = ImportRecordStatus::SUCCESS;

            expect($status->isProcessed())->toBeTrue()
                ->and($status->isSuccessful())->toBeTrue();
        });

        it('failed is processed but not successful', function (): void {
            $status = ImportRecordStatus::FAILED;

            expect($status->isProcessed())->toBeTrue()
                ->and($status->isSuccessful())->toBeFalse();
        });

        it('skipped is processed but not successful', function (): void {
            $status = ImportRecordStatus::SKIPPED;

            expect($status->isProcessed())->toBeTrue()
                ->and($status->isSuccessful())->toBeFalse();
        });
    });

    describe('enum behavior', function (): void {
        it('supports strict comparison', function (): void {
            $status1 = ImportRecordStatus::PENDING;
            $status2 = ImportRecordStatus::PENDING;
            $status3 = ImportRecordStatus::SUCCESS;

            expect($status1 === $status2)->toBeTrue()
                ->and($status1 === $status3)->toBeFalse();
        });

        it('works with switch statements', function (): void {
            $getDescription = function (ImportRecordStatus $status): string {
                return match ($status) {
                    ImportRecordStatus::PENDING => 'Record is waiting to be processed',
                    ImportRecordStatus::SUCCESS => 'Record was processed successfully',
                    ImportRecordStatus::FAILED => 'Record processing failed',
                    ImportRecordStatus::SKIPPED => 'Record was skipped',
                };
            };

            expect($getDescription(ImportRecordStatus::PENDING))->toBe('Record is waiting to be processed')
                ->and($getDescription(ImportRecordStatus::SUCCESS))->toBe('Record was processed successfully')
                ->and($getDescription(ImportRecordStatus::FAILED))->toBe('Record processing failed')
                ->and($getDescription(ImportRecordStatus::SKIPPED))->toBe('Record was skipped');
        });

        it('can be serialized to string', function (): void {
            expect(ImportRecordStatus::PENDING->value)->toBe('pending')
                ->and(ImportRecordStatus::SUCCESS->value)->toBe('success')
                ->and(ImportRecordStatus::FAILED->value)->toBe('failed')
                ->and(ImportRecordStatus::SKIPPED->value)->toBe('skipped');
        });

        it('works in arrays', function (): void {
            $statuses = [
                ImportRecordStatus::PENDING,
                ImportRecordStatus::SUCCESS,
                ImportRecordStatus::FAILED,
                ImportRecordStatus::SKIPPED,
            ];

            expect($statuses)->toHaveCount(4)
                ->and(in_array(ImportRecordStatus::PENDING, $statuses, true))->toBeTrue()
                ->and(in_array(ImportRecordStatus::SUCCESS, $statuses, true))->toBeTrue();
        });
    });

    describe('edge cases', function (): void {
        it('throws exception for invalid string values', function (): void {
            expect(fn () => ImportRecordStatus::from('invalid'))
                ->toThrow(ValueError::class);
        });

        it('handles tryFrom with invalid values', function (): void {
            expect(ImportRecordStatus::tryFrom('invalid'))->toBeNull()
                ->and(ImportRecordStatus::tryFrom('pending'))->toBe(ImportRecordStatus::PENDING);
        });

        it('handles tryFrom with all valid values', function (): void {
            expect(ImportRecordStatus::tryFrom('pending'))->toBe(ImportRecordStatus::PENDING)
                ->and(ImportRecordStatus::tryFrom('success'))->toBe(ImportRecordStatus::SUCCESS)
                ->and(ImportRecordStatus::tryFrom('failed'))->toBe(ImportRecordStatus::FAILED)
                ->and(ImportRecordStatus::tryFrom('skipped'))->toBe(ImportRecordStatus::SKIPPED);
        });
    });

    describe('logical groupings', function (): void {
        it('can group statuses by processing state', function (): void {
            $processed = [];
            $unprocessed = [];

            foreach (ImportRecordStatus::cases() as $status) {
                if ($status->isProcessed()) {
                    $processed[] = $status;
                } else {
                    $unprocessed[] = $status;
                }
            }

            expect($processed)->toEqual([
                ImportRecordStatus::SUCCESS,
                ImportRecordStatus::FAILED,
                ImportRecordStatus::SKIPPED,
            ])
                ->and($unprocessed)->toEqual([ImportRecordStatus::PENDING]);
        });

        it('can group statuses by success state', function (): void {
            $successful = [];
            $unsuccessful = [];

            foreach (ImportRecordStatus::cases() as $status) {
                if ($status->isSuccessful()) {
                    $successful[] = $status;
                } else {
                    $unsuccessful[] = $status;
                }
            }

            expect($successful)->toEqual([ImportRecordStatus::SUCCESS])
                ->and($unsuccessful)->toEqual([
                    ImportRecordStatus::PENDING,
                    ImportRecordStatus::FAILED,
                    ImportRecordStatus::SKIPPED,
                ]);
        });
    });
});
