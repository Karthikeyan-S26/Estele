<?php

namespace App\Jobs;

use App\Models\RewardSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurgeApprovedRewardMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        RewardSubmission::where('status', 'approved')
            ->where('reviewed_at', '<=', now()->subDay())
            ->whereHas('media')
            ->with('media')
            ->lazyById()
            ->each(fn (RewardSubmission $submission) => $submission->media->each->delete());
    }
}
