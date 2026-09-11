<?php

namespace App\Mail;

use App\Models\SellInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SellInvitation $invitation)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('New old-jewellery buy-back request — bid by '.$this->invitation->sellRequest->bids_end_at?->format('d M, H:i'))
            ->view('emails.sell-invitation');
    }
}