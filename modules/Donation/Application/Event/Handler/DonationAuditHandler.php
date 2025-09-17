<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Event\Handler;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Donation\Application\Event\DonationProcessedEvent;
use Modules\Shared\Infrastructure\Audit\AuditService;

final readonly class DonationAuditHandler implements ShouldQueue
{
    public function __construct(
        private AuditService $auditService,
    ) {}

    public function handle(DonationProcessedEvent $event): void
    {
        // Log to audit trail
        $this->auditService->log(
            action: 'donation.processed',
            entity_type: 'donation',
            entity_id: $event->donationId,
            metadata: $event->getAuditData(),
        );

        // Log for financial tracking
        Log::channel('financial')->info('Donation processed', [
            'donation_id' => $event->donationId,
            'campaign_id' => $event->campaignId,
            'amount' => $event->amount,
            'currency' => $event->currency,
            'transaction_id' => $event->transactionId,
            'payment_gateway' => $event->paymentGateway,
            'anonymous' => $event->anonymous,
            'locale' => $event->locale,
            'processed_at' => $event->occurredAt,
        ]);

        // Send large donation notifications
        if ($event->isLargeDonation()) {
            Log::info('Large donation processed', [
                'donation_id' => $event->donationId,
                'amount' => $event->amount,
                'currency' => $event->currency,
            ]);
        }
    }
}
