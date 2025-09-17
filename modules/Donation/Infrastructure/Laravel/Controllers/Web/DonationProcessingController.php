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
 * Donation Processing Controller.
 *
 * Displays a processing page for pending donations.
 */
final readonly class DonationProcessingController
{
    use AuthenticatedUserTrait;

    public function __invoke(Request $request, Donation $donation): View|RedirectResponse
    {
        // Check donation status and redirect accordingly
        if ($donation->isSuccessful()) {
            return redirect()->route('donations.success', $donation);
        }

        if ($donation->isFailed() || $donation->status->isOneOf([DonationStatus::CANCELLED, DonationStatus::REFUNDED])) {
            return redirect()->route('donations.failed', $donation);
        }

        // Anonymous donations (user_id = null) should be accessible without authentication
        if ($donation->user_id === null) {
            return view('donations.processing', [
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

        // Show processing page for pending donations
        return view('donations.processing', [
            'donation' => $donation,
            'campaign' => $donation->campaign,
        ]);
    }
}
