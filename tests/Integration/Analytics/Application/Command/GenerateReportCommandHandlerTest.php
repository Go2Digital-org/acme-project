<?php

declare(strict_types=1);

use Modules\Analytics\Application\Command\GenerateReportCommand;
use Modules\Analytics\Application\Command\GenerateReportCommandHandler;
use Modules\Analytics\Application\Service\WidgetDataAggregationService;
use Psr\Log\NullLogger;

describe('GenerateReportCommandHandler Integration Tests', function (): void {

    beforeEach(function (): void {
        $this->handler = new GenerateReportCommandHandler(
            new WidgetDataAggregationService(new NullLogger),
            new NullLogger
        );
    });

    describe('Basic Report Generation', function (): void {
        it('generates simple donation analytics report', function (): void {
            $command = new GenerateReportCommand(
                reportType: 'donation_analytics',
                reportName: 'Test Report',
                parameters: [],
                format: 'json',
                timeRange: 'today'
            );

            $result = $this->handler->handle($command);

            expect($result)->not->toBeNull()
                ->and($result)->toHaveKeys(['report_info', 'data'])
                ->and($result['report_info']['name'])->toBe('Test Report')
                ->and($result['report_info']['type'])->toBe('donation_analytics')
                ->and($result['report_info']['format'])->toBe('json');
        });
    });

    describe('Error Handling', function (): void {
        it('handles invalid report type gracefully', function (): void {
            $command = new GenerateReportCommand(
                reportType: 'invalid_type',
                reportName: 'Invalid Report',
                parameters: [],
                format: 'json',
                timeRange: 'today'
            );

            $result = $this->handler->handle($command);

            expect($result)->toBeNull();
        });
    });
});
