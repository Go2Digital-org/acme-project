<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Service\DonationStatusService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class ShowDonationController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private DonationStatusService $donationStatusService,
    ) {}

    /**
     * Get a specific donation by ID.
     */
    public function __invoke(Request $request, Donation $donation): JsonResponse
    {
        // Check authorization - only donation owner can view
        if ($donation->user_id !== $this->getAuthenticatedUserId($request)) {
            return ApiResponse::forbidden('You can only view your own donations.');
        }

        $donationData = $this->donationStatusService->getListingData($donation);

        return ApiResponse::success(
            data: $donationData,
            message: 'Donation retrieved successfully.',
        );
    }
}
