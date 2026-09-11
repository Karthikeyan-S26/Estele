<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Governs the read-only "Customers" admin list — a separate permission
 * namespace (Customer) from the User model's other uses (panel-staff auth,
 * WalletManagement) so granting/revoking visibility into the customer list
 * never accidentally touches anything else keyed off App\Models\User.
 */
class CustomerPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Customer');
    }

    public function view(AuthUser $authUser, User $customer): bool
    {
        return $authUser->can('View:Customer');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Customer');
    }

    public function update(AuthUser $authUser, User $customer): bool
    {
        return $authUser->can('Update:Customer');
    }

    public function delete(AuthUser $authUser, User $customer): bool
    {
        return $authUser->can('Delete:Customer');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Customer');
    }

    public function restore(AuthUser $authUser, User $customer): bool
    {
        return $authUser->can('Restore:Customer');
    }

    public function forceDelete(AuthUser $authUser, User $customer): bool
    {
        return $authUser->can('ForceDelete:Customer');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Customer');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Customer');
    }

    public function replicate(AuthUser $authUser, User $customer): bool
    {
        return $authUser->can('Replicate:Customer');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Customer');
    }
}
