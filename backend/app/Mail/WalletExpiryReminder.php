<?php

namespace App\Mail;

use App\Models\WalletTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The expiring-credit reminder ladder: stage '3d' (3-day warning), '1d'
 * (final nudge) or 'expired' (post-expiry notice). Mailer-side dedupe is
 * handled by the sell:send-reminders command via the *_sent_at columns.
 */
class WalletExpiryReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public WalletTransaction $credit,
        public string $stage,
    ) {
    }

    public function build(): self
    {
        $subject = match ($this->stage) {
            '3d' => 'Your wallet credit expires in 3 days',
            '1d' => "Your wallet credit expires tomorrow ({$this->credit->expires_at?->format('d M Y')})",
            default => 'Your wallet credit has expired',
        };

        return $this
            ->subject($subject)
            ->view('emails.wallet-expiry-reminder');
    }
}