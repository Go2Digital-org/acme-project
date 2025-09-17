<?php

declare(strict_types=1);

use Modules\Import\Domain\ValueObject\ImportStatus;

describe('ImportStatus', function () {
    describe('static constructors', function () {
        it('creates pending status', function () {
            $status = ImportStatus::pending();

            expect($status)->toBe(ImportStatus::PENDING)
                ->and($status->value)->toBe('pending');
        });

        it('creates processing status', function () {
            $status = ImportStatus::processing();

            expect($status)->toBe(ImportStatus::PROCESSING)
                ->and($status->value)->toBe('processing');
        });

        it('creates completed status', function () {
            $status = ImportStatus::completed();

            expect($status)->toBe(ImportStatus::COMPLETED)
                ->and($status->value)->toBe('completed');
        });

        it('creates failed status', function () {
            $status = ImportStatus::failed();

            expect($status)->toBe(ImportStatus::FAILED)
                ->and($status->value)->toBe('failed');
        });

        it('creates cancelled status', function () {
            $status = ImportStatus::cancelled();

            expect($status)->toBe(ImportStatus::CANCELLED)
                ->and($status->value)->toBe('cancelled');
        });
    });

    describe('case values', function () {
        it('has correct enum values', function () {
            expect(ImportStatus::PENDING->value)->toBe('pending')
                ->and(ImportStatus::PROCESSING->value)->toBe('processing')
                ->and(ImportStatus::COMPLETED->value)->toBe('completed')
                ->and(ImportStatus::FAILED->value)->toBe('failed')
                ->and(ImportStatus::CANCELLED->value)->toBe('cancelled');
        });

        it('can be instantiated from string values', function () {
            expect(ImportStatus::from('pending'))->toBe(ImportStatus::PENDING)
                ->and(ImportStatus::from('processing'))->toBe(ImportStatus::PROCESSING)
                ->and(ImportStatus::from('completed'))->toBe(ImportStatus::COMPLETED)
                ->and(ImportStatus::from('failed'))->toBe(ImportStatus::FAILED)
                ->and(ImportStatus::from('cancelled'))->toBe(ImportStatus::CANCELLED);
        });

        it('lists all cases', function () {
            $cases = ImportStatus::cases();

            expect($cases)->toHaveCount(5)
                ->and($cases)->toContain(ImportStatus::PENDING)
                ->and($cases)->toContain(ImportStatus::PROCESSING)
                ->and($cases)->toContain(ImportStatus::COMPLETED)
                ->and($cases)->toContain(ImportStatus::FAILED)
                ->and($cases)->toContain(ImportStatus::CANCELLED);
        });
    });

    describe('isActive', function () {
        it('returns true only for processing status', function () {
            expect(ImportStatus::PENDING->isActive())->toBeFalse()
                ->and(ImportStatus::PROCESSING->isActive())->toBeTrue()
                ->and(ImportStatus::COMPLETED->isActive())->toBeFalse()
                ->and(ImportStatus::FAILED->isActive())->toBeFalse()
                ->and(ImportStatus::CANCELLED->isActive())->toBeFalse();
        });

        it('returns true for processing status created via static method', function () {
            expect(ImportStatus::processing()->isActive())->toBeTrue();
        });
    });

    describe('isFinished', function () {
        it('returns false for pending and processing', function () {
            expect(ImportStatus::PENDING->isFinished())->toBeFalse()
                ->and(ImportStatus::PROCESSING->isFinished())->toBeFalse();
        });

        it('returns true for completed, failed, and cancelled', function () {
            expect(ImportStatus::COMPLETED->isFinished())->toBeTrue()
                ->and(ImportStatus::FAILED->isFinished())->toBeTrue()
                ->and(ImportStatus::CANCELLED->isFinished())->toBeTrue();
        });

        it('returns correct values for static constructors', function () {
            expect(ImportStatus::pending()->isFinished())->toBeFalse()
                ->and(ImportStatus::processing()->isFinished())->toBeFalse()
                ->and(ImportStatus::completed()->isFinished())->toBeTrue()
                ->and(ImportStatus::failed()->isFinished())->toBeTrue()
                ->and(ImportStatus::cancelled()->isFinished())->toBeTrue();
        });
    });

    describe('isSuccessful', function () {
        it('returns true only for completed status', function () {
            expect(ImportStatus::PENDING->isSuccessful())->toBeFalse()
                ->and(ImportStatus::PROCESSING->isSuccessful())->toBeFalse()
                ->and(ImportStatus::COMPLETED->isSuccessful())->toBeTrue()
                ->and(ImportStatus::FAILED->isSuccessful())->toBeFalse()
                ->and(ImportStatus::CANCELLED->isSuccessful())->toBeFalse();
        });

        it('returns true for completed status created via static method', function () {
            expect(ImportStatus::completed()->isSuccessful())->toBeTrue();
        });
    });

    describe('enum behavior', function () {
        it('supports strict comparison', function () {
            $status1 = ImportStatus::PENDING;
            $status2 = ImportStatus::PENDING;
            $status3 = ImportStatus::PROCESSING;

            expect($status1 === $status2)->toBeTrue()
                ->and($status1 === $status3)->toBeFalse();
        });

        it('works with switch statements', function () {
            $getDescription = function (ImportStatus $status): string {
                return match ($status) {
                    ImportStatus::PENDING => 'Import is pending',
                    ImportStatus::PROCESSING => 'Import is being processed',
                    ImportStatus::COMPLETED => 'Import completed successfully',
                    ImportStatus::FAILED => 'Import failed',
                    ImportStatus::CANCELLED => 'Import was cancelled',
                };
            };

            expect($getDescription(ImportStatus::PENDING))->toBe('Import is pending')
                ->and($getDescription(ImportStatus::PROCESSING))->toBe('Import is being processed')
                ->and($getDescription(ImportStatus::COMPLETED))->toBe('Import completed successfully')
                ->and($getDescription(ImportStatus::FAILED))->toBe('Import failed')
                ->and($getDescription(ImportStatus::CANCELLED))->toBe('Import was cancelled');
        });

        it('can be serialized to string', function () {
            expect(ImportStatus::PENDING->value)->toBe('pending')
                ->and(ImportStatus::PROCESSING->value)->toBe('processing')
                ->and(ImportStatus::COMPLETED->value)->toBe('completed')
                ->and(ImportStatus::FAILED->value)->toBe('failed')
                ->and(ImportStatus::CANCELLED->value)->toBe('cancelled');
        });
    });

    describe('edge cases', function () {
        it('throws exception for invalid string values', function () {
            expect(fn () => ImportStatus::from('invalid'))
                ->toThrow(ValueError::class);
        });

        it('handles tryFrom with invalid values', function () {
            expect(ImportStatus::tryFrom('invalid'))->toBeNull()
                ->and(ImportStatus::tryFrom('pending'))->toBe(ImportStatus::PENDING);
        });
    });
});
