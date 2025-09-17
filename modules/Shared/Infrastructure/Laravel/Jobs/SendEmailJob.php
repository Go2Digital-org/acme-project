<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 5;

    public int $maxExceptions = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 180, 300, 600]; // Progressive backoff up to 10 minutes

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        /** @var array<string, mixed> */
        private readonly array $emailData,
        private readonly ?string $locale = null,
        private readonly int $priority = 5, // 1-10, 10 being highest priority
    ) {
        // Set queue based on priority
        $queueName = match (true) {
            $priority >= 8 => 'notifications', // High priority
            $priority >= 5 => 'notifications', // Medium priority
            default => 'bulk',                  // Low priority
        };

        $this->onQueue($queueName);
    }

    public function handle(): void
    {
        $emailId = $this->emailData['id'] ?? uniqid('email_', true);

        Log::info('Processing email job', [
            'email_id' => $emailId,
            'to' => $this->getRecipientString(),
            'subject' => $this->emailData['subject'] ?? 'No Subject',
            'priority' => $this->priority,
            'job_id' => $this->job?->getJobId(),
        ]);

        // Set locale if provided
        $originalLocale = app()->getLocale();

        if ($this->locale) {
            app()->setLocale($this->locale);
        }

        try {
            $this->validateEmailData();
            $this->sendEmail($emailId);

            Log::info('Email sent successfully', [
                'email_id' => $emailId,
                'to' => $this->getRecipientString(),
                'subject' => $this->emailData['subject'],
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send email', [
                'email_id' => $emailId,
                'to' => $this->getRecipientString(),
                'subject' => $this->emailData['subject'] ?? 'No Subject',
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        } finally {
            // Restore original locale
            app()->setLocale($originalLocale);
        }
    }

    public function failed(Exception $exception): void
    {
        $emailId = $this->emailData['id'] ?? 'unknown';

        Log::error('Email job failed permanently', [
            'email_id' => $emailId,
            'to' => $this->getRecipientString(),
            'subject' => $this->emailData['subject'] ?? 'No Subject',
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Store failed email for potential manual retry
        try {
            cache()->put(
                "failed_email_{$emailId}",
                [
                    'email_data' => $this->emailData,
                    'locale' => $this->locale,
                    'priority' => $this->priority,
                    'failed_at' => now()->toISOString(),
                    'error' => $exception->getMessage(),
                    'attempts' => $this->attempts(),
                ],
                now()->addDays(7), // Keep failed emails for 7 days
            );
        } catch (Exception $e) {
            Log::error('Failed to store failed email data', [
                'email_id' => $emailId,
                'error' => $e->getMessage(),
            ]);
        }

        // Dispatch comprehensive failure notification
        try {
            JobFailureNotificationJob::dispatch(
                failedJobId: $this->job?->getJobId() ?? $emailId,
                jobClass: self::class,
                queueName: $this->job?->getQueue() ?? 'notifications',
                errorMessage: $exception->getMessage(),
                jobPayload: [
                    'email_id' => $emailId,
                    'to' => $this->getRecipientString(),
                    'subject' => $this->emailData['subject'] ?? 'No Subject',
                    'priority' => $this->priority,
                ],
                metadata: [
                    'job_type' => 'email',
                    'priority' => $this->priority,
                    'attempts' => $this->attempts(),
                    'failed_at' => now()->toISOString(),
                ]
            );
        } catch (Exception $e) {
            Log::error('Failed to dispatch job failure notification', [
                'email_id' => $emailId,
                'error' => $e->getMessage(),
            ]);

            // Fallback to simple admin notification for critical emails
            if ($this->priority >= 8) {
                $this->sendFallbackAdminNotification($exception);
            }
        }
    }

    private function sendFallbackAdminNotification(Exception $exception): void
    {
        try {
            Mail::raw(
                "Critical email failed to send:\n\nRecipient: {$this->getRecipientString()}\nSubject: {$this->emailData['subject']}\nError: {$exception->getMessage()}",
                function (Message $message): void {
                    $message->to(config('mail.admin_notifications', 'admin@acme-corp.com'))
                        ->subject('Critical Email Delivery Failure');
                },
            );
        } catch (Exception $e) {
            Log::error('Failed to send fallback admin notification about email failure', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Static helper methods for common email types.
     */
    /** @param array<string, mixed> $data */
    public static function confirmation(string $to, string $subject, array $data, string $view = 'emails.confirmation'): self
    {
        return new self([
            'to' => $to,
            'subject' => $subject,
            'view' => $view,
            'data' => $data,
        ], priority: 8);
    }

    /** @param array<string, mixed> $data */
    public static function notification(string $to, string $subject, array $data, string $view = 'emails.notification'): self
    {
        return new self([
            'to' => $to,
            'subject' => $subject,
            'view' => $view,
            'data' => $data,
        ], priority: 6);
    }

    /**
     * @param  array<int|string, string>  $recipients
     * @param  array<string, mixed>  $data
     */
    public static function bulk(array $recipients, string $subject, array $data, string $view = 'emails.bulk'): self
    {
        return new self([
            'to' => $recipients,
            'subject' => $subject,
            'view' => $view,
            'data' => $data,
        ], priority: 2);
    }

    /**
     * @param  array<int|string, string>  $recipients
     * @param  array<string, mixed>  $data
     */
    public static function marketing(array $recipients, string $subject, array $data, string $view = 'emails.marketing'): self
    {
        return new self([
            'to' => $recipients,
            'subject' => $subject,
            'view' => $view,
            'data' => $data,
        ], priority: 1);
    }

    private function validateEmailData(): void
    {
        $required = ['to', 'subject'];

        foreach ($required as $field) {
            if (empty($this->emailData[$field])) {
                throw new Exception("Missing required email field: {$field}");
            }
        }

        // Validate email addresses
        $recipients = $this->normalizeRecipients($this->emailData['to']);

        foreach ($recipients as $recipient) {
            if (! filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address: {$recipient['email']}");
            }
        }

        // Must have either view or html content
        if (empty($this->emailData['view']) && empty($this->emailData['html'])) {
            throw new Exception('Email must have either view or html content');
        }
    }

    private function sendEmail(string $emailId): void
    {
        // Determine email type and send accordingly
        if (isset($this->emailData['mailable']) && $this->emailData['mailable'] instanceof Mailable) {
            $this->sendMailable();
        } else {
            $this->sendRawEmail($emailId);
        }
    }

    private function sendMailable(): void
    {
        $recipients = $this->normalizeRecipients($this->emailData['to']);

        foreach ($recipients as $recipient) {
            Mail::to($recipient['email'], $recipient['name'])
                ->send($this->emailData['mailable']);
        }
    }

    private function sendRawEmail(string $emailId): void
    {
        Mail::send(
            $this->emailData['view'] ?? [],
            $this->emailData['data'] ?? [],
            function (Message $message) use ($emailId): void {
                $this->buildMessage($message, $emailId);
            },
        );
    }

    private function buildMessage(Message $message, string $emailId): void
    {
        // Set recipients
        $toRecipients = $this->normalizeRecipients($this->emailData['to']);

        foreach ($toRecipients as $recipient) {
            $message->to($recipient['email'], $recipient['name']);
        }

        // Set CC recipients
        if (! empty($this->emailData['cc'])) {
            $ccRecipients = $this->normalizeRecipients($this->emailData['cc']);

            foreach ($ccRecipients as $recipient) {
                $message->cc($recipient['email'], $recipient['name']);
            }
        }

        // Set BCC recipients
        if (! empty($this->emailData['bcc'])) {
            $bccRecipients = $this->normalizeRecipients($this->emailData['bcc']);

            foreach ($bccRecipients as $recipient) {
                $message->bcc($recipient['email'], $recipient['name']);
            }
        }

        // Set from address
        if (! empty($this->emailData['from'])) {
            $from = $this->emailData['from'];

            if (is_array($from)) {
                $message->from($from['email'], $from['name'] ?? '');
            } else {
                $message->from($from);
            }
        }

        // Set reply-to
        if (! empty($this->emailData['reply_to'])) {
            $replyTo = $this->emailData['reply_to'];

            if (is_array($replyTo)) {
                $message->replyTo($replyTo['email'], $replyTo['name'] ?? '');
            } else {
                $message->replyTo($replyTo);
            }
        }

        // Set subject
        $message->subject($this->emailData['subject']);

        // Set priority
        $this->setMessagePriority($message);

        // Add custom headers
        if (! empty($this->emailData['headers'])) {
            foreach ($this->emailData['headers'] as $name => $value) {
                $message->getHeaders()->addTextHeader($name, $value);
            }
        }

        // Add email ID for tracking
        $message->getHeaders()->addTextHeader('X-Email-ID', $emailId);
        $message->getHeaders()->addTextHeader('X-Mailer', 'ACME Corp CSR Platform');

        // Add HTML content if provided
        if (! empty($this->emailData['html'])) {
            $message->html($this->emailData['html']);
        }

        // Add text content if provided
        if (! empty($this->emailData['text'])) {
            $message->text($this->emailData['text']);
        }

        // Add attachments
        if (! empty($this->emailData['attachments'])) {
            foreach ($this->emailData['attachments'] as $attachment) {
                if (is_string($attachment)) {
                    $message->attach($attachment);
                } elseif (is_array($attachment)) {
                    $path = $attachment['path'] ?? '';
                    $name = $attachment['name'] ?? basename((string) $path);
                    $mime = $attachment['mime'] ?? null;

                    if ($path) {
                        $message->attach($path, [
                            'as' => $name,
                            'mime' => $mime,
                        ]);
                    }
                }
            }
        }

        // Add embedded images
        if (! empty($this->emailData['embeds'])) {
            foreach ($this->emailData['embeds'] as $path) {
                $message->embed($path);
            }
        }
    }

    /** @return array<int, array{email: string, name: string}> */
    private function normalizeRecipients(mixed $recipients): array
    {
        if (is_string($recipients)) {
            return [['email' => $recipients, 'name' => '']];
        }

        if (is_array($recipients)) {
            $normalized = [];

            foreach ($recipients as $key => $value) {
                if (is_numeric($key)) {
                    // Indexed array: ['email@example.com']
                    $normalized[] = ['email' => $value, 'name' => ''];
                } else {
                    // Associative array: ['email@example.com' => 'Name']
                    $normalized[] = ['email' => $key, 'name' => $value];
                }
            }

            return $normalized;
        }

        throw new Exception('Invalid recipients format');
    }

    private function setMessagePriority(Message $message): void
    {
        // Set X-Priority header based on priority level
        $xPriority = match (true) {
            $this->priority >= 9 => '1', // Highest
            $this->priority >= 7 => '2', // High
            $this->priority >= 4 => '3', // Normal
            $this->priority >= 2 => '4', // Low
            default => '5',              // Lowest
        };

        $message->getHeaders()->addTextHeader('X-Priority', $xPriority);

        // Also set Importance header for better client support
        $importance = match (true) {
            $this->priority >= 8 => 'high',
            $this->priority >= 3 => 'normal',
            default => 'low',
        };

        $message->getHeaders()->addTextHeader('Importance', $importance);
    }

    private function getRecipientString(): string
    {
        try {
            $recipients = $this->normalizeRecipients($this->emailData['to']);
            $emails = array_column($recipients, 'email');

            return implode(', ', array_slice($emails, 0, 3)) . (count($emails) > 3 ? ' and ' . (count($emails) - 3) . ' others' : '');
        } catch (Exception) {
            return 'Invalid recipients';
        }
    }
}
