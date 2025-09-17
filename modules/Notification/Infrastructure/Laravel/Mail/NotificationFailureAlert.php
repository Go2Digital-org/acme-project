<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Notification\Domain\Event\NotificationFailedEvent;

/**
 * Mailable for notification failure alerts sent to administrators.
 *
 * This email alerts administrators when critical notification deliveries fail,
 * providing detailed information to help diagnose and resolve issues.
 */
final class NotificationFailureAlert extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly NotificationFailedEvent $failureEvent
    ) {
        $this->onQueue('high-priority');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ALERT] Notification Delivery Failure - ' . $this->failureEvent->notification->type,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'notification::mail.failure-alert',
            with: [
                'notification' => $this->failureEvent->notification,
                'failureReason' => $this->failureEvent->failureReason,
                'failureContext' => $this->failureEvent->failureContext,
                'occurredAt' => $this->failureEvent->getOccurredAt(),
                'attemptCount' => $this->failureEvent->notification->attempt_count ?? 1,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
