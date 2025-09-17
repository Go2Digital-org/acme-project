<?php

declare(strict_types=1);

namespace Modules\Export\Domain\Exception;

use DomainException;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Export\Domain\ValueObject\ExportStatus;

class ExportException extends DomainException
{
    public static function invalidStatusTransition(ExportStatus $from, ExportStatus $to): self
    {
        return new self("Cannot transition export status from {$from->value} to {$to->value}");
    }

    public static function exportNotFound(ExportId $exportId): self
    {
        return new self("Export job not found: {$exportId->toString()}");
    }

    public static function exportAlreadyProcessing(ExportId $exportId): self
    {
        return new self("Export job is already processing: {$exportId->toString()}");
    }

    public static function exportAlreadyFinished(ExportId $exportId): self
    {
        return new self("Export job is already finished: {$exportId->toString()}");
    }

    public static function cannotCancelFinishedExport(ExportId $exportId): self
    {
        return new self("Cannot cancel finished export job: {$exportId->toString()}");
    }

    public static function invalidExportConfiguration(string $reason): self
    {
        return new self("Invalid export configuration: {$reason}");
    }

    public static function exportFileTooLarge(int $fileSizeMB, int $maxSizeMB): self
    {
        return new self("Export file size ({$fileSizeMB}MB) exceeds maximum allowed size ({$maxSizeMB}MB)");
    }

    public static function exportTimeout(ExportId $exportId, int $timeoutSeconds): self
    {
        return new self("Export job timed out after {$timeoutSeconds} seconds: {$exportId->toString()}");
    }

    public static function exportProcessingFailed(ExportId $exportId, string $reason): self
    {
        return new self("Export job processing failed: {$reason} (ID: {$exportId->toString()})");
    }

    public static function unauthorizedAccess(): self
    {
        return new self('Unauthorized access to export resource');
    }

    public static function cancellationFailed(string $reason): self
    {
        return new self("Export cancellation failed: {$reason}");
    }

    public static function tooManyPendingExports(int $pendingCount): self
    {
        return new self("Too many pending exports ({$pendingCount}). Please wait for existing exports to complete.");
    }

    public static function dailyLimitExceeded(int $todayCount): self
    {
        return new self("Daily export limit exceeded ({$todayCount} exports today). Please try again tomorrow.");
    }

    public static function invalidDateRange(string $from, string $to): self
    {
        return new self("Invalid date range: {$from} to {$to}. Start date must be before end date.");
    }

    public static function dateRangeTooLarge(string $from, string $to): self
    {
        return new self("Date range too large: {$from} to {$to}. Maximum allowed range is 2 years.");
    }

    public static function exportExpired(ExportId $exportId): self
    {
        return new self("Export has expired: {$exportId->toString()}");
    }

    public static function exportNotReady(ExportId $exportId, ExportStatus $status): self
    {
        return new self("Export is not ready for download (status: {$status->value}): {$exportId->toString()}");
    }

    public static function exportFileNotAvailable(ExportId $exportId): self
    {
        return new self("Export file is not available: {$exportId->toString()}");
    }

    public static function exportFileNotFound(ExportId $exportId, ?string $filePath = null): self
    {
        $pathInfo = $filePath ? " (path: {$filePath})" : '';

        return new self("Export file not found: {$exportId->toString()}{$pathInfo}");
    }

    public static function accessDenied(): self
    {
        return new self('Access denied to the requested export');
    }

    public static function cannotRetryNonFailedExport(): self
    {
        return new self('Only failed exports can be retried');
    }

    public static function cannotDeleteProcessingExport(): self
    {
        return new self('Cannot delete an export that is still processing');
    }
}
