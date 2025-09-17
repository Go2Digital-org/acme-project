<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers\Web;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Donation\Application\Service\DonationService;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Infrastructure\Laravel\Requests\Web\CancelDonationRequest;

final class CancelDonationController extends Controller
{
    public function __construct(
        private readonly DonationService $donationService,
    ) {}

    /**
     * Handle the incoming request to cancel a donation.
     */
    public function __invoke(CancelDonationRequest $request, Donation $donation): RedirectResponse
    {
        try {
            // Check if user can cancel this donation
            if (! $this->donationService->canManageDonation($donation->id)) {
                Log::warning('Unauthorized donation cancellation attempt', [
                    'donation_id' => $donation->id,
                    'user_id' => auth()->id(),
                ]);

                return redirect()
                    ->route('donations.index')
                    ->with('error', __('You are not authorized to cancel this donation.'));
            }

            $cancelledDonation = $this->donationService->cancelDonation(
                $donation->id,
            );

            Log::info('Donation cancelled successfully', [
                'donation_id' => $donation->id,
                'user_id' => auth()->id(),
                'reason' => $request->validated('reason'),
            ]);

            return redirect()
                ->route('donations.index')
                ->with('success', __('Donation cancelled successfully.'));
        } catch (Exception $e) {
            Log::error('Failed to cancel donation', [
                'donation_id' => $donation->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return redirect()
                ->route('donations.index')
                ->with('error', __('Failed to cancel donation. Please try again.'));
        }
    }
}
