<?php

namespace App\Mail;

use App\Models\RewardSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewRewardSubmissionNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public RewardSubmission $submission)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('New reward submission awaiting review')
            ->view('emails.new-reward-submission');
    }
}
