<?php

namespace App\Mail;

use App\Models\WalletTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WalletCredited extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public WalletTransaction $transaction)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Wallet Updated')
            ->view('emails.wallet-credited');
    }
}
