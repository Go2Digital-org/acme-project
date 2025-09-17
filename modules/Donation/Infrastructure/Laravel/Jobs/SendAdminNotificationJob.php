<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Infrastructure\Notifications\AdminNotificationService;

final class SendAdminNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 60;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly string $subject,
        /** @var array<string, mixed> */
        private readonly array $data,
        private readonly string $template = 'admin.notification',
        /** @var array<int, string> */
        private readonly array $recipients = [],
    ) {
        $this->onQueue('notifications');
    }

    public function handle(AdminNotificationService $notificationService): void
    {
        try {
            Log::info('Sending admin notification', [
                'subject' => $this->subject,
                'data_keys' => array_keys($this->data),
                'template' => $this->template,
                'recipients_count' => count($this->recipients),
            ]);

            $notificationService->sendToAdmins(
                $this->subject,
                $this->data,
                $this->template,
                $this->recipients,
            );

            Log::info('Admin notification sent successfully', [
                'subject' => $this->subject,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send admin notification', [
                'subject' => $this->subject,
                'error' => $exception->getMessage(),
                'data' => $this->data,
            ]);

            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Admin notification job failed permanently', [
            'subject' => $this->subject,
            'data' => $this->data,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
