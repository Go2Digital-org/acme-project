<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\User\Infrastructure\Laravel\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Public admin dashboard channel for general admin notifications
Broadcast::channel('admin-dashboard', fn () =>
    // Any authenticated user can listen to public admin notifications
    auth()->check());

// Private user-specific notification channel
Broadcast::channel('user.notifications.{userId}', fn (User $user, int $userId): bool =>
    // User can only listen to their own notifications
    $user->id === $userId);

// Role-specific admin channels
Broadcast::channel('admin-role-{role}', function (User $user, string $role): bool {
    // Check if user has the specified admin role
    $adminRoles = ['super_admin', 'csr_admin', 'finance_admin', 'hr_manager'];

    return in_array($role, $adminRoles, true) && $user->hasRole($role);
});

// Organization-specific notification channel
Broadcast::channel('organization.{organizationId}', fn (User $user, int $organizationId): bool =>
    // Check if user belongs to the organization or is an admin
    $user->organization_id === $organizationId || $user->hasAnyRole(['super_admin', 'csr_admin']));

// Campaign-specific notification channel
Broadcast::channel('campaign.{campaignId}', function (User $user, int $campaignId): bool {
    // Check if user is campaign manager, belongs to the organization, or is an admin
    $campaign = Campaign::find($campaignId);

    if (! $campaign) {
        return false;
    }

    return $user->id === $campaign->user_id
        || $user->organization_id === $campaign->organization_id
        || $user->hasAnyRole(['super_admin', 'csr_admin']);
});

// Security alerts channel - only for high-level admins
Broadcast::channel('security-alerts', fn (User $user): bool => $user->hasAnyRole(['super_admin', 'csr_admin']));

// System maintenance notifications channel
Broadcast::channel('system-maintenance', fn (User $user): bool =>
    // All admin users should receive system maintenance notifications
    $user->hasAnyRole(['super_admin', 'csr_admin', 'finance_admin', 'hr_manager']));

// Compliance notifications channel
Broadcast::channel('compliance-notifications', fn (User $user): bool =>
    // CSR admins and super admins handle compliance
    $user->hasAnyRole(['super_admin', 'csr_admin']));

// Payment-related notifications channel
Broadcast::channel('payment-notifications', fn (User $user): bool =>
    // Finance admins and super admins handle payments
    $user->hasAnyRole(['super_admin', 'finance_admin']));
