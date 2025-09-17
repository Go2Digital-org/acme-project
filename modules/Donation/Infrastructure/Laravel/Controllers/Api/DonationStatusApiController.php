<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Donation\Domain\Model\Donation;
use Modules\Shared\Domain\ValueObject\DonationStatus;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\User\Domain\ValueObject\UserRole;

/**
 * Donation Status API Controller.
 *
 * Provides donation status checks via API for AJAX polling.
 */
final readonly class DonationStatusApiController
{
    use AuthenticatedUserTrait;

    public function __invoke(Request $request, Donation $donation): JsonResponse
    {
        // Verify user owns this donation or is admin
        $user = $this->getAuthenticatedUser($request);

        $userRole = $user->getRole();
        $isAdmin = $userRole !== null && UserRole::tryFrom($userRole)?->isAdmin() === true;

        if ($donation->user_id !== $user->getId() && ! $isAdmin) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Determine the appropriate redirect URL based on status
        $redirectUrl = null;

        if ($donation->isSuccessful()) {
            $redirectUrl = route('donations.success', $donation);
        }

        if ($donation->isFailed() || $donation->status->isOneOf([DonationStatus::CANCELLED, DonationStatus::REFUNDED])) {
            $redirectUrl = route('donations.failed', $donation);
        }

        // Return status information
        return response()->json([
            'status' => $donation->status->value,
            'is_successful' => $donation->isSuccessful(),
            'is_failed' => $donation->isFailed(),
            'is_pending' => $donation->status->value === 'pending' || $donation->status->value === 'processing',
            'redirect_url' => $redirectUrl,
            'amount' => $donation->amount,
            'created_at' => $donation->created_at?->toIso8601String(),
            'updated_at' => $donation->updated_at?->toIso8601String(),
        ]);
    }
}
