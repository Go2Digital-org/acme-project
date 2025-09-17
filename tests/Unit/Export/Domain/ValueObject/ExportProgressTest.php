<?php

declare(strict_types=1);

use Modules\Export\Domain\ValueObject\ExportProgress;

describe('ExportProgress', function () {
    describe('constructor validation', function () {
        it('creates valid progress object', function () {
            $progress = ExportProgress::create(50, 'Processing...', 100, 200);

            expect($progress->percentage)->toBe(50)
                ->and($progress->message)->toBe('Processing...')
                ->and($progress->processedRecords)->toBe(100)
                ->and($progress->totalRecords)->toBe(200);
        });

        it('throws exception for invalid percentage range', function () {
            expect(fn () => ExportProgress::create(-1, 'Test'))
                ->toThrow(InvalidArgumentException::class, 'Percentage must be between 0 and 100');

            expect(fn () => ExportProgress::create(101, 'Test'))
                ->toThrow(InvalidArgumentException::class, 'Percentage must be between 0 and 100');
        });

        it('throws exception for negative processed records', function () {
            expect(fn () => ExportProgress::create(50, 'Test', -1, 100))
                ->toThrow(InvalidArgumentException::class, 'Processed records cannot be negative');
        });

        it('throws exception for negative total records', function () {
            expect(fn () => ExportProgress::create(50, 'Test', 50, -1))
                ->toThrow(InvalidArgumentException::class, 'Total records cannot be negative');
        });

        it('throws exception when processed exceeds total', function () {
            expect(fn () => ExportProgress::create(50, 'Test', 200, 100))
                ->toThrow(InvalidArgumentException::class, 'Processed records cannot exceed total records');
        });

        it('allows processed to equal total', function () {
            $progress = ExportProgress::create(100, 'Complete', 100, 100);

            expect($progress->processedRecords)->toBe(100)
                ->and($progress->totalRecords)->toBe(100);
        });

        it('allows processed to exceed total when total is zero', function () {
            $progress = ExportProgress::create(50, 'Processing', 100, 0);

            expect($progress->processedRecords)->toBe(100)
                ->and($progress->totalRecords)->toBe(0);
        });
    });

    describe('static factory methods', function () {
        describe('start', function () {
            it('creates initial progress state', function () {
                $progress = ExportProgress::start();

                expect($progress->percentage)->toBe(0)
                    ->and($progress->message)->toBe('Starting export...')
                    ->and($progress->processedRecords)->toBe(0)
                    ->and($progress->totalRecords)->toBe(0);
            });

            it('accepts custom message and total', function () {
                $progress = ExportProgress::start('Initializing...', 500);

                expect($progress->percentage)->toBe(0)
                    ->and($progress->message)->toBe('Initializing...')
                    ->and($progress->processedRecords)->toBe(0)
                    ->and($progress->totalRecords)->toBe(500);
            });
        });

        describe('fromRecords', function () {
            it('calculates percentage from records', function () {
                $progress = ExportProgress::fromRecords(25, 100);

                expect($progress->percentage)->toBe(25)
                    ->and($progress->processedRecords)->toBe(25)
                    ->and($progress->totalRecords)->toBe(100);
            });

            it('handles zero total records', function () {
                $progress = ExportProgress::fromRecords(0, 0);

                expect($progress->percentage)->toBe(0);
            });

            it('generates default message', function () {
                $progress = ExportProgress::fromRecords(75, 150);

                expect($progress->message)->toBe('Processing 75 of 150 records');
            });

            it('accepts custom message', function () {
                $progress = ExportProgress::fromRecords(10, 20, 'Custom message');

                expect($progress->message)->toBe('Custom message');
            });

            it('rounds percentage correctly', function () {
                $progress = ExportProgress::fromRecords(33, 100);
                expect($progress->percentage)->toBe(33);

                $progress = ExportProgress::fromRecords(666, 1000);
                expect($progress->percentage)->toBe(67); // 66.6 rounded to 67

                $progress = ExportProgress::fromRecords(1, 3);
                expect($progress->percentage)->toBe(33); // 33.33 rounded to 33
            });
        });

        describe('completed', function () {
            it('creates 100% complete progress', function () {
                $progress = ExportProgress::completed();

                expect($progress->percentage)->toBe(100)
                    ->and($progress->message)->toBe('Export completed successfully');
            });

            it('accepts custom completion message', function () {
                $progress = ExportProgress::completed('All done!');

                expect($progress->percentage)->toBe(100)
                    ->and($progress->message)->toBe('All done!');
            });
        });
    });

    describe('state checking methods', function () {
        it('isStarted returns true when percentage > 0', function () {
            expect(ExportProgress::create(0, 'Start')->isStarted())->toBeFalse()
                ->and(ExportProgress::create(1, 'Started')->isStarted())->toBeTrue()
                ->and(ExportProgress::create(50, 'Progress')->isStarted())->toBeTrue()
                ->and(ExportProgress::create(100, 'Done')->isStarted())->toBeTrue();
        });

        it('isCompleted returns true when percentage is 100', function () {
            expect(ExportProgress::create(0, 'Start')->isCompleted())->toBeFalse()
                ->and(ExportProgress::create(50, 'Progress')->isCompleted())->toBeFalse()
                ->and(ExportProgress::create(99, 'Almost')->isCompleted())->toBeFalse()
                ->and(ExportProgress::create(100, 'Done')->isCompleted())->toBeTrue();
        });
    });

    describe('manipulation methods', function () {
        describe('advance', function () {
            it('advances processed records by specified amount', function () {
                $initial = ExportProgress::create(25, 'Progress', 25, 100);
                $advanced = $initial->advance(25);

                expect($advanced->processedRecords)->toBe(50)
                    ->and($advanced->totalRecords)->toBe(100)
                    ->and($advanced->percentage)->toBe(50);
            });

            it('keeps original message when no new message provided', function () {
                $initial = ExportProgress::create(25, 'Original message', 25, 100);
                $advanced = $initial->advance(25);

                expect($advanced->message)->toBe('Original message');
            });

            it('updates message when provided', function () {
                $initial = ExportProgress::create(25, 'Old message', 25, 100);
                $advanced = $initial->advance(25, 'New message');

                expect($advanced->message)->toBe('New message');
            });

            it('returns new instance (immutable)', function () {
                $initial = ExportProgress::create(25, 'Progress', 25, 100);
                $advanced = $initial->advance(25);

                expect($initial->processedRecords)->toBe(25)
                    ->and($advanced->processedRecords)->toBe(50)
                    ->and($initial)->not->toBe($advanced);
            });
        });

        describe('withMessage', function () {
            it('returns new instance with updated message', function () {
                $initial = ExportProgress::create(50, 'Old message', 50, 100);
                $updated = $initial->withMessage('New message');

                expect($updated->message)->toBe('New message')
                    ->and($updated->percentage)->toBe(50)
                    ->and($updated->processedRecords)->toBe(50)
                    ->and($updated->totalRecords)->toBe(100)
                    ->and($initial)->not->toBe($updated);
            });

            it('preserves all other properties', function () {
                $initial = ExportProgress::create(75, 'Original', 150, 200);
                $updated = $initial->withMessage('Updated');

                expect($updated->percentage)->toBe($initial->percentage)
                    ->and($updated->processedRecords)->toBe($initial->processedRecords)
                    ->and($updated->totalRecords)->toBe($initial->totalRecords);
            });
        });
    });

    describe('utility methods', function () {
        describe('getRemainingRecords', function () {
            it('calculates remaining records correctly', function () {
                $progress = ExportProgress::create(50, 'Progress', 50, 100);

                expect($progress->getRemainingRecords())->toBe(50);
            });

            it('returns zero when all records processed', function () {
                $progress = ExportProgress::create(100, 'Done', 100, 100);

                expect($progress->getRemainingRecords())->toBe(0);
            });

            it('returns zero when processed exceeds total', function () {
                // Use totalRecords = 0 to allow processed > total as per constructor logic
                $progress = ExportProgress::create(100, 'Overflow', 150, 0);

                expect($progress->getRemainingRecords())->toBe(0);
            });

            it('handles zero total records', function () {
                $progress = ExportProgress::create(0, 'No records', 0, 0);

                expect($progress->getRemainingRecords())->toBe(0);
            });
        });

        describe('getProgressBar', function () {
            it('generates progress bar with default width', function () {
                $progress = ExportProgress::create(50, 'Progress');
                $bar = $progress->getProgressBar();

                expect($bar)->toHaveLength(22) // [ + 20 chars + ]
                    ->and($bar)->toStartWith('[')
                    ->and($bar)->toEndWith(']');
            });

            it('generates progress bar with custom width', function () {
                $progress = ExportProgress::create(50, 'Progress');
                $bar = $progress->getProgressBar(10);

                expect($bar)->toHaveLength(12) // [ + 10 chars + ]
                    ->and($bar)->toBe('[=====-----]');
            });

            it('handles 0% progress', function () {
                $progress = ExportProgress::create(0, 'Start');
                $bar = $progress->getProgressBar(10);

                expect($bar)->toBe('[----------]');
            });

            it('handles 100% progress', function () {
                $progress = ExportProgress::create(100, 'Done');
                $bar = $progress->getProgressBar(10);

                expect($bar)->toBe('[' . str_repeat('=', 10) . ']');
            });

            it('handles various percentages correctly', function () {
                // 25% of 20 = 5 filled, 15 empty
                $progress = ExportProgress::create(25, 'Quarter');
                $bar = $progress->getProgressBar(20);
                expect($bar)->toBe('[' . str_repeat('=', 5) . str_repeat('-', 15) . ']');

                // 75% of 20 = 15 filled, 5 empty
                $progress = ExportProgress::create(75, 'Three quarters');
                $bar = $progress->getProgressBar(20);
                expect($bar)->toBe('[' . str_repeat('=', 15) . str_repeat('-', 5) . ']');
            });
        });

        describe('toArray', function () {
            it('converts to array with all properties', function () {
                $progress = ExportProgress::create(60, 'Processing...', 120, 200);
                $array = $progress->toArray();

                expect($array)->toBe([
                    'percentage' => 60,
                    'message' => 'Processing...',
                    'processed_records' => 120,
                    'total_records' => 200,
                    'remaining_records' => 80,
                ]);
            });

            it('includes calculated remaining records', function () {
                $progress = ExportProgress::create(75, 'Almost done', 75, 100);
                $array = $progress->toArray();

                expect($array['remaining_records'])->toBe(25);
            });

            it('has consistent array structure', function () {
                $progress = ExportProgress::start();
                $array = $progress->toArray();

                expect($array)->toHaveKeys([
                    'percentage',
                    'message',
                    'processed_records',
                    'total_records',
                    'remaining_records',
                ]);
            });
        });
    });

    describe('readonly behavior', function () {
        it('is readonly class', function () {
            $reflection = new ReflectionClass(ExportProgress::class);

            expect($reflection->isReadOnly())->toBeTrue();
        });

        it('maintains property integrity', function () {
            $progress = ExportProgress::create(50, 'Test', 50, 100);

            expect($progress->percentage)->toBe(50)
                ->and($progress->message)->toBe('Test')
                ->and($progress->processedRecords)->toBe(50)
                ->and($progress->totalRecords)->toBe(100);

            // Properties should remain unchanged
            expect($progress->percentage)->toBe(50);
        });
    });

    describe('edge cases and boundaries', function () {
        it('handles edge percentage values', function () {
            $progress1 = ExportProgress::create(0, 'Start');
            $progress2 = ExportProgress::create(100, 'End');

            expect($progress1->percentage)->toBe(0)
                ->and($progress2->percentage)->toBe(100);
        });

        it('handles large record counts', function () {
            $progress = ExportProgress::create(50, 'Large dataset', 1000000, 2000000);

            expect($progress->processedRecords)->toBe(1000000)
                ->and($progress->totalRecords)->toBe(2000000)
                ->and($progress->getRemainingRecords())->toBe(1000000);
        });

        it('handles empty strings in messages', function () {
            $progress = ExportProgress::create(50, '');

            expect($progress->message)->toBe('');
        });

        it('calculates percentage accurately for various ratios', function () {
            // Test various fractions
            expect(ExportProgress::fromRecords(1, 4)->percentage)->toBe(25);
            expect(ExportProgress::fromRecords(1, 3)->percentage)->toBe(33);
            expect(ExportProgress::fromRecords(2, 3)->percentage)->toBe(67);
            expect(ExportProgress::fromRecords(3, 7)->percentage)->toBe(43);
        });
    });

    describe('immutability', function () {
        it('advance creates new instance', function () {
            $original = ExportProgress::create(25, 'Original', 25, 100);
            $advanced = $original->advance(25);

            expect($original->processedRecords)->toBe(25)
                ->and($advanced->processedRecords)->toBe(50);
        });

        it('withMessage creates new instance', function () {
            $original = ExportProgress::create(50, 'Original message');
            $updated = $original->withMessage('New message');

            expect($original->message)->toBe('Original message')
                ->and($updated->message)->toBe('New message');
        });

        it('factory methods create independent instances', function () {
            $progress1 = ExportProgress::start('Message 1');
            $progress2 = ExportProgress::start('Message 2');

            expect($progress1->message)->toBe('Message 1')
                ->and($progress2->message)->toBe('Message 2');
        });
    });
});
