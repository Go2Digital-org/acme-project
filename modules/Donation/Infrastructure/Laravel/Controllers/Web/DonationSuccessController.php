<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Donation\Domain\Model\Donation;
use Modules\Shared\Domain\ValueObject\DonationStatus;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\User\Domain\ValueObject\UserRole;

/**
 * Donation Success Controller.
 *
 * Displays the donation success page after successful payment.
 */
final readonly class DonationSuccessController
{
    use AuthenticatedUserTrait;

    public function __construct() {}

    public function __invoke(Request $request, Donation $donation): View|RedirectResponse
    {
        // Verify payment is actually successful before showing success page
        if (! $donation->isSuccessful()) {
            if ($donation->isFailed() || $donation->status->isOneOf([DonationStatus::CANCELLED, DonationStatus::REFUNDED])) {
                return redirect()->route('donations.failed', $donation);
            }

            // Handle pending/processing - redirect to processing page
            return redirect()->route('donations.processing', $donation);
        }

        // Anonymous donations (user_id = null) should be accessible without authentication
        if ($donation->user_id === null) {
            return view('donations.success', [
                'donation' => $donation,
                'campaign' => $donation->campaign,
            ]);
        }

        // For authenticated donations, verify user owns this donation or is admin
        $user = $this->getAuthenticatedUser($request);

        $userRole = $user->getRole();
        $isAdmin = $userRole !== null && UserRole::tryFrom($userRole)?->isAdmin() === true;

        if ($donation->user_id !== $user->getId() && ! $isAdmin) {
            abort(403, 'Access denied');
        }

        return view('donations.success', [
            'donation' => $donation,
            'campaign' => $donation->campaign,
        ]);
    }
}
