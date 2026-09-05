<?php

namespace App\Mail;

use App\Models\RewardSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RewardSubmissionRejected extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public RewardSubmission $submission)
    {
    }

    public function build(): self
    {
        return $this
            ->subject("Your reward submission for order {$this->submission->order->order_number} was not approved")
            ->view('emails.reward-submission-rejected');
    }
}
