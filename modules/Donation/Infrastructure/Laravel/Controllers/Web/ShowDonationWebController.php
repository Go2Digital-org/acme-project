<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Donation\Domain\Service\DonationStatusService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ShowDonationWebController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private DonationRepositoryInterface $donationRepository,
        private DonationStatusService $donationStatusService,
    ) {}

    public function __invoke(Request $request, int $donation): View
    {
        $user = $this->getAuthenticatedUser($request);

        // User is already authenticated by the trait method

        $donationModel = $this->donationRepository->findById($donation);

        if (! $donationModel instanceof Donation) {
            throw new NotFoundHttpException("Donation with ID {$donation} not found");
        }

        // Check authorization - user should only be able to view their own donations
        if ($donationModel->user_id !== $user->getId()) {
            abort(403, 'Unauthorized to view this donation');
        }

        // Transform donation using status service for rich view data
        $donationData = $this->donationStatusService->getListingData($donationModel);
        $statusMetrics = $this->donationStatusService->getStatusMetrics($donationModel);
        $timelineEvents = $this->donationStatusService->getTimelineEvents($donationModel);

        return view('donations.show', [
            'donation' => $donationModel,
            'donationData' => $donationData,
            'statusMetrics' => $statusMetrics,
            'timelineEvents' => $timelineEvents,
            'validTransitions' => $this->donationStatusService->getValidTransitions($donationModel),
        ]);
    }
}
