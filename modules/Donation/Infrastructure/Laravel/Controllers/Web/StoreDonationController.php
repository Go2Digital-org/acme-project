<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers\Web;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Donation\Application\Service\DonationService;
use Modules\Donation\Infrastructure\Laravel\Requests\Web\StoreDonationRequest;

final class StoreDonationController extends Controller
{
    public function __construct(
        private readonly DonationService $donationService,
    ) {}

    /**
     * Handle the incoming request to store a new donation.
     */
    public function __invoke(StoreDonationRequest $request): RedirectResponse
    {
        try {
            $donation = $this->donationService->storeDonation($request->validated());

            Log::info('Donation created successfully', [
                'donation_id' => $donation->id,
                'campaign_id' => $donation->campaign_id,
                'user_id' => auth()->id(),
                'amount' => $donation->amount,
                'currency' => $donation->currency,
            ]);

            // Redirect to payment processing or success page based on payment method
            if ($donation->payment_method && $donation->payment_method->requiresProcessing()) {
                return redirect()
                    ->route('donations.processing', $donation)
                    ->with('success', __('Donation created! Please complete the payment process.'));
            }

            return redirect()
                ->route('donations.show', $donation)
                ->with('success', __('Donation created successfully! Thank you for your contribution.'));
        } catch (Exception $e) {
            Log::error('Failed to create donation', [
                'user_id' => auth()->id(),
                'campaign_id' => $request->validated('campaign_id'),
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('Failed to create donation. Please try again.'));
        }
    }
}
