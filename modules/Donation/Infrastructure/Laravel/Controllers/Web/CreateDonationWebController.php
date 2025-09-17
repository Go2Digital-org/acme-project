<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Repository\PaymentGatewayRepositoryInterface;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class CreateDonationWebController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private PaymentGatewayRepositoryInterface $paymentGatewayRepository,
    ) {}

    public function __invoke(Request $request, Campaign $campaign): View|RedirectResponse
    {
        $user = $this->getAuthenticatedUser($request);

        // User is already authenticated by the trait method

        // Campaign is automatically resolved by Laravel's route model binding

        // Check if campaign can accept donations
        if (! $campaign->canAcceptDonation()) {
            return redirect()
                ->route('campaigns.show', $campaign->slug ?? $campaign->id)
                ->with('error', 'This campaign is no longer accepting donations.');
        }

        // Calculate progress percentage
        $progressPercentage = $campaign->goal_amount > 0
            ? min(100, ($campaign->current_amount / $campaign->goal_amount) * 100)
            : 0;

        // Calculate days left (as integer)
        $daysLeft = $campaign->end_date !== null ? (int) now()->diffInDays($campaign->end_date, false) : -1;

        // Get recent donations for this campaign
        $recentDonations = collect(); // TODO: Implement when donation repository is available

        // Get active and configured payment gateways
        $activeGateways = $this->paymentGatewayRepository->findActiveAndConfigured();

        // Extract gateway names for display
        $gatewayNames = collect($activeGateways)->map(fn ($gateway): string => match ($gateway->provider) {
            'mollie' => 'Mollie',
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            default => ucfirst($gateway->provider),
        })->unique()->values()->all();

        return view('campaigns.donate', [
            'campaign' => $campaign,
            'progressPercentage' => $progressPercentage,
            'daysLeft' => $daysLeft,
            'recentDonations' => $recentDonations,
            'user' => $user,
            'activeGateways' => $activeGateways,
            'gatewayNames' => $gatewayNames,
        ]);
    }
}
