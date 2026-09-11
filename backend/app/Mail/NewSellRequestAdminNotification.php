<?php

namespace App\Mail;

use App\Models\SellRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewSellRequestAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SellRequest $sellRequest)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('New old-jewellery request '.$this->sellRequest->request_number)
            ->view('emails.sell-request-submitted');
    }
}