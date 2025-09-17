<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Donation\Domain\Model\Donation;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\User\Domain\ValueObject\UserRole;

/**
 * Donation Cancel Controller.
 *
 * Displays the donation cancelled page when user cancels payment.
 */
final readonly class DonationCancelController
{
    use AuthenticatedUserTrait;

    public function __construct() {}

    public function __invoke(Request $request, Donation $donation): View
    {
        // Anonymous donations (user_id = null) should be accessible without authentication
        if ($donation->user_id === null) {
            return view('donations.cancelled', [
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

        return view('donations.cancelled', [
            'donation' => $donation,
            'campaign' => $donation->campaign,
        ]);
    }
}
