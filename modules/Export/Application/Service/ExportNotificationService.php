<?php

declare(strict_types=1);

namespace Modules\Export\Application\Service;

use Exception;
use Illuminate\Support\Facades\Mail;
use Log;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\User\Domain\Model\User;
use Modules\User\Domain\Repository\UserRepositoryInterface;

class ExportNotificationService
{
    public function __construct(
        private readonly ExportJobRepositoryInterface $exportRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function notifyExportCompleted(ExportId $exportId): void
    {
        $exportJob = $this->exportRepository->findByExportId($exportId);
        if (! $exportJob instanceof ExportJob || ! $exportJob->getStatusValueObject()->isCompleted()) {
            return;
        }

        $user = $this->userRepository->findById($exportJob->user_id);
        if (! $user instanceof User) {
            return;
        }

        try {
            Mail::send('exports.email.completed', [
                'user' => $user,
                'export' => $exportJob,
                'downloadUrl' => $this->generateDownloadUrl($exportJob),
                'expiresAt' => $exportJob->expires_at,
            ], function ($message) use ($user): void {
                $message->to($user->getEmailString(), $user->getFullName())
                    ->subject('Your export is ready for download')
                    ->tag('export-completed');
            });

        } catch (Exception $e) {
            Log::error('Failed to send export completion email', [
                'export_id' => $exportId->toString(),
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyExportFailed(ExportId $exportId): void
    {
        $exportJob = $this->exportRepository->findByExportId($exportId);
        if (! $exportJob instanceof ExportJob || ! $exportJob->getStatusValueObject()->isFailed()) {
            return;
        }

        $user = $this->userRepository->findById($exportJob->user_id);
        if (! $user instanceof User) {
            return;
        }

        try {
            Mail::send('exports.email.failed', [
                'user' => $user,
                'export' => $exportJob,
                'errorMessage' => $exportJob->error_message,
                'supportUrl' => config('app.support_url'),
            ], function ($message) use ($user, $exportJob): void {
                $message->to($user->getEmailString(), $user->getFullName())
                    ->subject('Export failed - ' . ucfirst($exportJob->resource_type))
                    ->tag('export-failed');
            });

        } catch (Exception $e) {
            Log::error('Failed to send export failure email', [
                'export_id' => $exportId->toString(),
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyExportExpiring(ExportId $exportId): void
    {
        $exportJob = $this->exportRepository->findByExportId($exportId);
        if (! $exportJob instanceof ExportJob || ! $exportJob->canBeDownloaded()) {
            return;
        }

        $hoursRemaining = $exportJob->getExpiresInHours();
        if ($hoursRemaining === null || $hoursRemaining > 24) {
            return; // Only notify when < 24 hours remaining
        }

        $user = $this->userRepository->findById($exportJob->user_id);
        if (! $user instanceof User) {
            return;
        }

        try {
            Mail::send('exports.email.expiring', [
                'user' => $user,
                'export' => $exportJob,
                'downloadUrl' => $this->generateDownloadUrl($exportJob),
                'hoursRemaining' => $hoursRemaining,
                'expiresAt' => $exportJob->expires_at,
            ], function ($message) use ($user, $hoursRemaining): void {
                $message->to($user->getEmailString(), $user->getFullName())
                    ->subject("Your export expires in {$hoursRemaining} hours")
                    ->tag('export-expiring');
            });

        } catch (Exception $e) {
            Log::error('Failed to send export expiring email', [
                'export_id' => $exportId->toString(),
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyLargeExportStarted(ExportId $exportId): void
    {
        $exportJob = $this->exportRepository->findByExportId($exportId);
        if (! $exportJob instanceof ExportJob || ! $exportJob->getStatusValueObject()->isProcessing()) {
            return;
        }

        // Only notify for large exports (> 10k records)
        if ($exportJob->total_records < 10000) {
            return;
        }

        $user = $this->userRepository->findById($exportJob->user_id);
        if (! $user instanceof User) {
            return;
        }

        try {
            Mail::send('exports.email.large_export_started', [
                'user' => $user,
                'export' => $exportJob,
                'estimatedTime' => $this->estimateProcessingTime($exportJob->total_records),
            ], function ($message) use ($user): void {
                $message->to($user->getEmailString(), $user->getFullName())
                    ->subject('Large export started - We\'ll notify you when ready')
                    ->tag('export-large-started');
            });

        } catch (Exception $e) {
            Log::error('Failed to send large export started email', [
                'export_id' => $exportId->toString(),
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendBrowserNotification(ExportId $exportId, string $type): void
    {
        $exportJob = $this->exportRepository->findByExportId($exportId);
        if (! $exportJob instanceof ExportJob) {
            return;
        }

        $user = $this->userRepository->findById($exportJob->user_id);
        if (! $user instanceof User) {
            return;
        }

        $notificationData = match ($type) {
            'completed' => [
                'title' => 'Export Completed',
                'message' => 'Your export is ready for download',
                'icon' => 'success',
                'action_url' => route('exports.download', $exportJob->export_id),
            ],
            'failed' => [
                'title' => 'Export Failed',
                'message' => 'There was an issue with your export',
                'icon' => 'error',
                'action_url' => route('exports.index'),
            ],
            'expiring' => [
                'title' => 'Export Expiring Soon',
                'message' => "Your export expires in {$exportJob->getExpiresInHours()} hours",
                'icon' => 'warning',
                'action_url' => route('exports.download', $exportJob->export_id),
            ],
            default => null,
        };

        if ($notificationData) {
            // TODO: Implement browser notification broadcast when ExportNotification event is created
            // For now, just log the notification data
            Log::info('Browser notification data prepared', [
                'export_id' => $exportId->toString(),
                'user_id' => $user->getId(),
                'type' => $type,
                'data' => $notificationData,
            ]);
        }
    }

    private function generateDownloadUrl(ExportJob $exportJob): string
    {
        return route('exports.download', [
            'exportId' => $exportJob->export_id,
            'signature' => hash_hmac('sha256', $exportJob->export_id, (string) config('app.key')),
        ]);
    }

    private function estimateProcessingTime(int $totalRecords): string
    {
        // Rough estimation: ~1000 records per minute
        $estimatedMinutes = ceil($totalRecords / 1000);

        if ($estimatedMinutes < 60) {
            return "{$estimatedMinutes} minutes";
        }

        $hours = floor($estimatedMinutes / 60);
        $remainingMinutes = $estimatedMinutes % 60;

        return $remainingMinutes > 0
            ? "{$hours}h {$remainingMinutes}m"
            : "{$hours} hours";
    }

    /**
     * Send completion notification - interface method for service provider
     */
    public function sendCompletionNotification(
        ExportId $exportId,
        string $filePath,
        int $fileSize,
        int $recordsExported
    ): void {
        $this->notifyExportCompleted($exportId);
        $this->sendBrowserNotification($exportId, 'completed');
    }

    /**
     * Send failure notification - interface method for service provider
     */
    public function sendFailureNotification(
        ExportId $exportId,
        string $errorMessage,
        int $processedRecords
    ): void {
        $this->notifyExportFailed($exportId);
        $this->sendBrowserNotification($exportId, 'failed');
    }
}
