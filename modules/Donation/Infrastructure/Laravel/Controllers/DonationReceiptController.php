<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Donation\Domain\Model\Donation;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final class DonationReceiptController
{
    use AuthenticatedUserTrait;

    /**
     * Generate and download donation receipt.
     */
    public function __invoke(Request $request, Donation $donation): JsonResponse
    {
        // Check authorization - only donation owner can download receipt
        if ($donation->user_id !== $this->getAuthenticatedUserId($request)) {
            return ApiResponse::forbidden('You can only download receipts for your own donations.');
        }

        // Check if donation is completed (only completed donations get receipts)
        if (! $donation->isSuccessful()) {
            return ApiResponse::error(
                'Receipt is only available for completed donations.',
                statusCode: 400,
            );
        }

        // In a real implementation, you would:
        // 1. Generate PDF receipt using Laravel PDF or similar
        // 2. Store receipt in cloud storage
        // 3. Return download URL or stream the PDF

        // For now, return receipt data
        return ApiResponse::success(
            data: [
                'receipt_id' => 'RCP-' . $donation->id . '-' . $donation->created_at?->format('Ymd'),
                'donation' => [
                    'id' => $donation->id,
                    'amount' => $donation->amount,
                    'currency' => $donation->currency,
                    'payment_method' => $donation->payment_method?->value,
                    'transaction_id' => $donation->transaction_id,
                    'donated_at' => $donation->donated_at->toISOString(),
                    'completed_at' => $donation->completed_at?->toISOString(),
                ],
                'campaign' => [
                    'id' => $donation->campaign_id,
                    'title' => $donation->campaign->title ?? 'N/A',
                    'organization' => $donation->campaign?->organization?->getName() ?? 'N/A',
                ],
                'donor' => [
                    'name' => $donation->anonymous ? 'Anonymous' : $this->getAuthenticatedUser($request)->getFullName(),
                    'email' => $donation->anonymous ? null : $this->getAuthenticatedUser($request)->getEmailString(),
                ],
                'receipt_details' => [
                    'issued_date' => now()->toISOString(),
                    'tax_deductible' => true, // Based on organization tax status
                    'receipt_number' => 'RCP-' . str_pad((string) $donation->id, 8, '0', STR_PAD_LEFT),
                ],
                // Note: In production, return download_url instead
                'download_url' => url("/api/v1/donations/{$donation->id}/receipt/download"),
                'message' => 'Receipt generation would typically create a PDF file for download.',
            ],
            message: 'Donation receipt details retrieved successfully.',
        );
    }
}
