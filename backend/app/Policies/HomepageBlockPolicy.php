<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HomepageBlock;
use Illuminate\Auth\Access\HandlesAuthorization;

class HomepageBlockPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HomepageBlock');
    }

    public function view(AuthUser $authUser, HomepageBlock $homepageBlock): bool
    {
        return $authUser->can('View:HomepageBlock');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HomepageBlock');
    }

    public function update(AuthUser $authUser, HomepageBlock $homepageBlock): bool
    {
        return $authUser->can('Update:HomepageBlock');
    }

    public function delete(AuthUser $authUser, HomepageBlock $homepageBlock): bool
    {
        return $authUser->can('Delete:HomepageBlock');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HomepageBlock');
    }

    public function restore(AuthUser $authUser, HomepageBlock $homepageBlock): bool
    {
        return $authUser->can('Restore:HomepageBlock');
    }

    public function forceDelete(AuthUser $authUser, HomepageBlock $homepageBlock): bool
    {
        return $authUser->can('ForceDelete:HomepageBlock');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HomepageBlock');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HomepageBlock');
    }

    public function replicate(AuthUser $authUser, HomepageBlock $homepageBlock): bool
    {
        return $authUser->can('Replicate:HomepageBlock');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HomepageBlock');
    }

}