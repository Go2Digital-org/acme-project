<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Donation\Application\Request\CreateDonationRequest;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Donation\Infrastructure\Laravel\Resource\DonationResource;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class CreateDonationController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private DonationRepositoryInterface $donationRepository,
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Create a new donation to a campaign.
     */
    public function __invoke(CreateDonationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Verify campaign exists and can accept donations
        $campaign = $this->campaignRepository->findById((int) $validated['campaign_id']);

        if (! $campaign instanceof Campaign) {
            return ApiResponse::notFound('Campaign not found.');
        }

        if (! $campaign->canAcceptDonation()) {
            return ApiResponse::error(
                'This campaign is no longer accepting donations.',
                statusCode: 409,
            );
        }

        // Create donation record
        $donationData = [
            'campaign_id' => $validated['campaign_id'],
            'user_id' => $this->getAuthenticatedUserId($request),
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'USD',
            'payment_method' => $validated['payment_method'],
            'anonymous' => $validated['anonymous'] ?? false,
            'recurring' => $validated['recurring'] ?? false,
            'recurring_frequency' => $validated['recurring_frequency'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'donated_at' => now(),
        ];

        $donation = $this->donationRepository->create($donationData);

        // In a real implementation, here you would:
        // 1. Process payment with payment gateway
        // 2. Update campaign amount if successful
        // 3. Send confirmation email
        // 4. Create receipt

        return ApiResponse::created(
            data: new DonationResource($donation),
            message: 'Donation created successfully. Processing payment...',
        );
    }
}
