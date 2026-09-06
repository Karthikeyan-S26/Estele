<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RewardSubmission;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RewardSubmissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RewardSubmission');
    }

    public function view(AuthUser $authUser, ?RewardSubmission $rewardSubmission = null): bool
    {
        return $authUser->can('View:RewardSubmission');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RewardSubmission');
    }

    public function update(AuthUser $authUser, ?RewardSubmission $rewardSubmission = null): bool
    {
        return $authUser->can('Update:RewardSubmission');
    }

    public function delete(AuthUser $authUser, ?RewardSubmission $rewardSubmission = null): bool
    {
        return $authUser->can('Delete:RewardSubmission');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RewardSubmission');
    }

    public function restore(AuthUser $authUser, ?RewardSubmission $rewardSubmission = null): bool
    {
        return $authUser->can('Restore:RewardSubmission');
    }

    public function forceDelete(AuthUser $authUser, ?RewardSubmission $rewardSubmission = null): bool
    {
        return $authUser->can('ForceDelete:RewardSubmission');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RewardSubmission');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RewardSubmission');
    }

    public function replicate(AuthUser $authUser, ?RewardSubmission $rewardSubmission = null): bool
    {
        return $authUser->can('Replicate:RewardSubmission');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RewardSubmission');
    }
}
