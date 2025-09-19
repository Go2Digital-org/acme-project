<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Service;

use Carbon\Carbon;
use Modules\Donation\Domain\Model\Donation;
use Modules\Shared\Domain\ValueObject\DonationStatus;

class DonationStatusService
{
    /**
     * @return array<string, mixed>
     */
    public function getStatusMetrics(Donation $donation): array
    {
        return [
            'status' => $donation->status,
            'paymentMethod' => $donation->payment_method,
            'processingTime' => $this->getProcessingTime($donation),
            'isActionRequired' => $this->isActionRequired($donation),
            'canRefund' => $this->canRefund($donation),
            'canCancel' => $this->canCancel($donation),
        ];
    }

    public function isActionRequired(Donation $donation): bool
    {
        $status = $donation->status->value;

        return in_array($status, ['pending', 'processing', 'failed'], true);
    }

    public function canRefund(Donation $donation): bool
    {
        $status = $donation->status->value;

        if ($status !== 'completed') {
            return false;
        }

        // Check if within refund window (e.g., 30 days)
        $refundWindow = 30; // days
        $donationDate = Carbon::parse($donation->created_at);

        return $donationDate->diffInDays(Carbon::now()) <= $refundWindow;
    }

    public function canCancel(Donation $donation): bool
    {
        return in_array($donation->status->value, ['pending', 'processing'], true);
    }

    public function getProcessingTime(Donation $donation): string
    {
        if ($donation->status->isFinal()) {
            return 'Completed';
        }

        return $donation->payment_method?->getProcessingTime() ?? 'Unknown';
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidTransitions(Donation $donation): array
    {
        $currentStatus = $donation->status->value;

        $transitions = [
            'pending' => [
                'processing' => 'Start Processing',
                'cancelled' => 'Cancel Donation',
                'failed' => 'Mark as Failed',
            ],
            'processing' => [
                'completed' => 'Complete Payment',
                'failed' => 'Mark as Failed',
                'cancelled' => 'Cancel Donation',
            ],
            'completed' => [
                'refunded' => 'Process Refund',
            ],
            'failed' => [
                'processing' => 'Retry Processing',
                'cancelled' => 'Cancel Donation',
            ],
            'cancelled' => [],
            'refunded' => [],
        ];

        $validTransitions = $transitions[$currentStatus];

        // Apply business rules
        if ($currentStatus === DonationStatus::COMPLETED->value && ! $this->canRefund($donation)) {
            unset($validTransitions['refunded']);
        }

        return $validTransitions;
    }

    public function shouldShowInList(Donation $donation, string $filter = 'all'): bool
    {
        $status = $donation->status->value;

        return match ($filter) {
            'pending' => in_array($status, ['pending', 'processing'], true),
            'completed' => $status === 'completed',
            'failed' => $status === DonationStatus::FAILED->value,
            'cancelled' => $status === DonationStatus::CANCELLED->value,
            'refunded' => $status === DonationStatus::REFUNDED->value,
            'action_required' => $this->isActionRequired($donation),
            'all' => true,
            default => true,
        };
    }

    /**
     * @return list<array<string, bool|\Illuminate\Support\Carbon|string|null>>
     */
    public function getTimelineEvents(Donation $donation): array
    {
        $events = [];

        // Base timeline events based on status
        if ($donation->status->hasTimeline()) {
            $events[] = [
                'status' => $donation->status->value,
                'label' => $donation->status->getLabel(),
                'description' => $donation->status->getDescription(),
                'color' => $donation->status->getColor(),
                'icon' => $donation->status->getIcon(),
                'timestamp' => $donation->created_at,
                'isCurrent' => true,
            ];
        }

        // Add historical events if they exist
        // This would be expanded based on your audit/history tracking system

        return $events;
    }

    /**
     * @return array<string, mixed>
     */
    public function getListingData(Donation $donation): array
    {
        $metrics = $this->getStatusMetrics($donation);

        return [
            'id' => $donation->id,
            'amount' => $donation->amount,
            'currency' => $donation->currency,
            'status' => $metrics['status'],
            'paymentMethod' => $metrics['paymentMethod'],
            'donorName' => $donation->anonymous ? 'Anonymous' : ($donation->user->name ?? 'Unknown'),
            'donorEmail' => $donation->anonymous ? null : ($donation->user->email ?? null),
            'campaignTitle' => $donation->campaign->title ?? null,
            'createdAt' => $donation->created_at,
            'processingTime' => $metrics['processingTime'],
            'isActionRequired' => $metrics['isActionRequired'],
            'canRefund' => $metrics['canRefund'],
            'canCancel' => $metrics['canCancel'],
        ];
    }

    /**
     * @param  iterable<Donation>  $donations
     * @return array<string, mixed>
     */
    public function getDashboardMetrics(iterable $donations): array
    {
        $metrics = [
            'total' => 0,
            'confirmed' => 0,
            'pending' => 0,
            'failed' => 0,
            'refunded' => 0,
            'totalAmount' => 0.0,
            'confirmedAmount' => 0.0,
            'pendingAmount' => 0.0,
            'actionRequired' => 0,
        ];

        foreach ($donations as $donation) {
            $metrics['total']++;
            $amount = $donation->amount;
            $metrics['totalAmount'] += $amount;

            $status = $donation->status->value;

            match ($status) {
                'completed' => [
                    $metrics['confirmed']++,
                    $metrics['confirmedAmount'] += $amount,
                ],
                'pending', 'processing' => [
                    $metrics['pending']++,
                    $metrics['pendingAmount'] += $amount,
                ],
                'failed' => $metrics['failed']++,
                'refunded' => $metrics['refunded']++,
                default => null,
            };

            if ($this->isActionRequired($donation)) {
                $metrics['actionRequired']++;
            }
        }

        return $metrics;
    }
}
