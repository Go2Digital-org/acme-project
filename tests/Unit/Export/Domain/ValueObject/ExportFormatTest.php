<?php

declare(strict_types=1);

use Modules\Export\Domain\ValueObject\ExportFormat;

describe('ExportFormat', function () {
    describe('enum cases', function () {
        it('has correct enum values', function () {
            expect(ExportFormat::CSV->value)->toBe('csv')
                ->and(ExportFormat::EXCEL->value)->toBe('excel')
                ->and(ExportFormat::PDF->value)->toBe('pdf');
        });

        it('can be instantiated from string values', function () {
            expect(ExportFormat::from('csv'))->toBe(ExportFormat::CSV)
                ->and(ExportFormat::from('excel'))->toBe(ExportFormat::EXCEL)
                ->and(ExportFormat::from('pdf'))->toBe(ExportFormat::PDF);
        });

        it('lists all cases', function () {
            $cases = ExportFormat::cases();

            expect($cases)->toHaveCount(3)
                ->and($cases)->toContain(ExportFormat::CSV)
                ->and($cases)->toContain(ExportFormat::EXCEL)
                ->and($cases)->toContain(ExportFormat::PDF);
        });
    });

    describe('getExtension method', function () {
        it('returns correct extensions', function () {
            expect(ExportFormat::CSV->getExtension())->toBe('csv')
                ->and(ExportFormat::EXCEL->getExtension())->toBe('xlsx')
                ->and(ExportFormat::PDF->getExtension())->toBe('pdf');
        });

        it('matches getFileExtension method', function () {
            foreach (ExportFormat::cases() as $format) {
                expect($format->getExtension())->toBe($format->getFileExtension());
            }
        });
    });

    describe('getFileExtension method', function () {
        it('returns correct file extensions', function () {
            expect(ExportFormat::CSV->getFileExtension())->toBe('csv')
                ->and(ExportFormat::EXCEL->getFileExtension())->toBe('xlsx')
                ->and(ExportFormat::PDF->getFileExtension())->toBe('pdf');
        });

        it('is alias for getExtension', function () {
            $csv = ExportFormat::CSV;
            expect($csv->getFileExtension())->toBe($csv->getExtension());
        });
    });

    describe('getMimeType method', function () {
        it('returns correct MIME types', function () {
            expect(ExportFormat::CSV->getMimeType())->toBe('text/csv')
                ->and(ExportFormat::EXCEL->getMimeType())->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->and(ExportFormat::PDF->getMimeType())->toBe('application/pdf');
        });

        it('returns valid MIME type format', function () {
            foreach (ExportFormat::cases() as $format) {
                $mimeType = $format->getMimeType();
                expect($mimeType)->toMatch('/^[a-z]+\/[a-z0-9\-\.]+$/');
            }
        });
    });

    describe('getLabel method', function () {
        it('returns human-readable labels', function () {
            expect(ExportFormat::CSV->getLabel())->toBe('CSV')
                ->and(ExportFormat::EXCEL->getLabel())->toBe('Excel')
                ->and(ExportFormat::PDF->getLabel())->toBe('PDF');
        });

        it('returns non-empty strings', function () {
            foreach (ExportFormat::cases() as $format) {
                expect($format->getLabel())->not->toBeEmpty();
            }
        });
    });

    describe('getIcon method', function () {
        it('returns heroicon identifiers', function () {
            expect(ExportFormat::CSV->getIcon())->toBe('heroicon-o-table-cells')
                ->and(ExportFormat::EXCEL->getIcon())->toBe('heroicon-o-document-chart-bar')
                ->and(ExportFormat::PDF->getIcon())->toBe('heroicon-o-document-text');
        });

        it('returns valid heroicon format', function () {
            foreach (ExportFormat::cases() as $format) {
                $icon = $format->getIcon();
                expect($icon)->toStartWith('heroicon-')
                    ->and($icon)->toMatch('/^heroicon-[os]-[\w\-]+$/');
            }
        });
    });

    describe('supportsMultipleSheets method', function () {
        it('only Excel supports multiple sheets', function () {
            expect(ExportFormat::CSV->supportsMultipleSheets())->toBeFalse()
                ->and(ExportFormat::EXCEL->supportsMultipleSheets())->toBeTrue()
                ->and(ExportFormat::PDF->supportsMultipleSheets())->toBeFalse();
        });

        it('returns boolean for all formats', function () {
            foreach (ExportFormat::cases() as $format) {
                expect($format->supportsMultipleSheets())->toBeIn([true, false]);
            }
        });
    });

    describe('supportsImages method', function () {
        it('Excel and PDF support images', function () {
            expect(ExportFormat::CSV->supportsImages())->toBeFalse()
                ->and(ExportFormat::EXCEL->supportsImages())->toBeTrue()
                ->and(ExportFormat::PDF->supportsImages())->toBeTrue();
        });

        it('returns boolean for all formats', function () {
            foreach (ExportFormat::cases() as $format) {
                expect($format->supportsImages())->toBeIn([true, false]);
            }
        });
    });

    describe('getMaxFileSizeMB method', function () {
        it('returns correct size limits', function () {
            expect(ExportFormat::CSV->getMaxFileSizeMB())->toBe(50)
                ->and(ExportFormat::EXCEL->getMaxFileSizeMB())->toBe(100)
                ->and(ExportFormat::PDF->getMaxFileSizeMB())->toBe(25);
        });

        it('returns positive integers', function () {
            foreach (ExportFormat::cases() as $format) {
                expect($format->getMaxFileSizeMB())->toBeGreaterThan(0)
                    ->and($format->getMaxFileSizeMB())->toBeInt();
            }
        });

        it('Excel has largest size limit', function () {
            $sizes = [];
            foreach (ExportFormat::cases() as $format) {
                $sizes[$format->value] = $format->getMaxFileSizeMB();
            }

            expect(max($sizes))->toBe(100)
                ->and(array_search(max($sizes), $sizes))->toBe('excel');
        });
    });

    describe('enum behavior', function () {
        it('supports strict comparison', function () {
            $format1 = ExportFormat::CSV;
            $format2 = ExportFormat::CSV;
            $format3 = ExportFormat::EXCEL;

            expect($format1 === $format2)->toBeTrue()
                ->and($format1 === $format3)->toBeFalse();
        });

        it('works with switch statements', function () {
            $getDescription = function (ExportFormat $format): string {
                return match ($format) {
                    ExportFormat::CSV => 'Comma-separated values format',
                    ExportFormat::EXCEL => 'Microsoft Excel spreadsheet',
                    ExportFormat::PDF => 'Portable Document Format',
                };
            };

            expect($getDescription(ExportFormat::CSV))->toBe('Comma-separated values format')
                ->and($getDescription(ExportFormat::EXCEL))->toBe('Microsoft Excel spreadsheet')
                ->and($getDescription(ExportFormat::PDF))->toBe('Portable Document Format');
        });

        it('can be serialized to string', function () {
            expect(ExportFormat::CSV->value)->toBe('csv')
                ->and(ExportFormat::EXCEL->value)->toBe('excel')
                ->and(ExportFormat::PDF->value)->toBe('pdf');
        });

        it('works in arrays', function () {
            $formats = [ExportFormat::CSV, ExportFormat::EXCEL, ExportFormat::PDF];

            expect($formats)->toHaveCount(3)
                ->and(in_array(ExportFormat::CSV, $formats, true))->toBeTrue()
                ->and(in_array(ExportFormat::EXCEL, $formats, true))->toBeTrue();
        });
    });

    describe('edge cases', function () {
        it('throws exception for invalid string values', function () {
            expect(fn () => ExportFormat::from('invalid'))
                ->toThrow(ValueError::class);
        });

        it('handles tryFrom with invalid values', function () {
            expect(ExportFormat::tryFrom('invalid'))->toBeNull()
                ->and(ExportFormat::tryFrom('csv'))->toBe(ExportFormat::CSV);
        });

        it('handles tryFrom with all valid values', function () {
            expect(ExportFormat::tryFrom('csv'))->toBe(ExportFormat::CSV)
                ->and(ExportFormat::tryFrom('excel'))->toBe(ExportFormat::EXCEL)
                ->and(ExportFormat::tryFrom('pdf'))->toBe(ExportFormat::PDF);
        });
    });

    describe('feature compatibility matrix', function () {
        it('correctly maps features to formats', function () {
            // Multiple sheets: Only Excel
            $multiSheetFormats = array_values(array_filter(
                ExportFormat::cases(),
                fn (ExportFormat $format) => $format->supportsMultipleSheets()
            ));
            expect($multiSheetFormats)->toHaveCount(1)
                ->and($multiSheetFormats[0])->toBe(ExportFormat::EXCEL);

            // Images: Excel and PDF
            $imageFormats = array_filter(
                ExportFormat::cases(),
                fn (ExportFormat $format) => $format->supportsImages()
            );
            expect($imageFormats)->toHaveCount(2)
                ->and($imageFormats)->toContain(ExportFormat::EXCEL)
                ->and($imageFormats)->toContain(ExportFormat::PDF);

            // No images: CSV only
            $noImageFormats = array_values(array_filter(
                ExportFormat::cases(),
                fn (ExportFormat $format) => ! $format->supportsImages()
            ));
            expect($noImageFormats)->toHaveCount(1)
                ->and($noImageFormats[0])->toBe(ExportFormat::CSV);
        });
    });

    describe('consistency checks', function () {
        it('has consistent method return types', function () {
            foreach (ExportFormat::cases() as $format) {
                expect($format->getExtension())->toBeString();
                expect($format->getFileExtension())->toBeString();
                expect($format->getMimeType())->toBeString();
                expect($format->getLabel())->toBeString();
                expect($format->getIcon())->toBeString();
                expect($format->supportsMultipleSheets())->toBeBool();
                expect($format->supportsImages())->toBeBool();
                expect($format->getMaxFileSizeMB())->toBeInt();
            }
        });

        it('has non-empty string methods', function () {
            foreach (ExportFormat::cases() as $format) {
                expect($format->getExtension())->not->toBeEmpty();
                expect($format->getFileExtension())->not->toBeEmpty();
                expect($format->getMimeType())->not->toBeEmpty();
                expect($format->getLabel())->not->toBeEmpty();
                expect($format->getIcon())->not->toBeEmpty();
            }
        });
    });
});
