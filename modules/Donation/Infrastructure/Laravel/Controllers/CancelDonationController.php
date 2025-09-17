<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Donation\Infrastructure\Laravel\Resource\DonationResource;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class CancelDonationController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private DonationRepositoryInterface $donationRepository,
    ) {}

    /**
     * Cancel a donation.
     */
    public function __invoke(Request $request, Donation $donation): JsonResponse
    {
        // Check authorization - only donation owner can cancel
        if ($donation->user_id !== $this->getAuthenticatedUserId($request)) {
            return ApiResponse::forbidden('You can only cancel your own donations.');
        }

        // Check if donation can be cancelled
        if (! $donation->canBeCancelled()) {
            return ApiResponse::error(
                'This donation cannot be cancelled in its current state.',
                statusCode: 409,
            );
        }

        // Update donation status
        $this->donationRepository->updateById($donation->id, [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Refresh the model
        $donation->refresh();

        return ApiResponse::success(
            data: new DonationResource($donation),
            message: 'Donation cancelled successfully.',
        );
    }
}
