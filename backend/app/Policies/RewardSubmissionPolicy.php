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
}
