<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Model;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\User\Infrastructure\Laravel\Models\User;

class PaymentGatewayPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payment::gateway');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PaymentGateway $paymentGateway): bool
    {
        return $user->can('view_payment::gateway');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_payment::gateway');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PaymentGateway $paymentGateway): bool
    {
        return $user->can('update_payment::gateway');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PaymentGateway $paymentGateway): bool
    {
        return $user->can('delete_payment::gateway');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_payment::gateway');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, PaymentGateway $paymentGateway): bool
    {
        return $user->can('force_delete_payment::gateway');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_payment::gateway');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, PaymentGateway $paymentGateway): bool
    {
        return $user->can('restore_payment::gateway');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_payment::gateway');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, PaymentGateway $paymentGateway): bool
    {
        return $user->can('replicate_payment::gateway');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_payment::gateway');
    }
}
