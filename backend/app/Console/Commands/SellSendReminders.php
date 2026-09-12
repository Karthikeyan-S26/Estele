<?php

namespace App\Console\Commands;

use App\Mail\WalletExpiryReminder;
use App\Models\WalletTransaction;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the reminder ladder for expiring wallet credits: a 3-day heads-up,
 * a 1-day final nudge, and a post-expiry notice. Each stage is deduped by its
 * own *_sent_at column so the same email never fires twice.
 */
class SellSendReminders extends Command
{
    protected $signature = 'sell:send-reminders';

    protected $description = 'Send 3-day, 1-day and post-expiry notifications for expiring wallet credits';

    public function handle(WhatsAppManager $whatsapp): int
    {
        $pending = WalletTransaction::where('type', 'credit')
            ->whereNotNull('expires_at')
            ->whereNull('expired_at')
            ->get();

        $sent = 0;
        foreach ($pending as $credit) {
            $user = $credit->user;
            if (! $user || blank($user->email)) {
                continue;
            }

            $now = now();
            $daysLeft = (float) $now->diffInDays($credit->expires_at, false);

            if ($daysLeft <= 1.0 && $credit->reminder_1d_sent_at === null && $daysLeft >= 0.0) {
                Mail::to($user->email)->queue(new WalletExpiryReminder($credit, '1d'));

                if (filled($user->phone)) {
                    $whatsapp->send($user->phone, 'Heads up — your Estele wallet credit of ₹'.number_format((float) $credit->amount, 2).' expires tomorrow. Use it on your next order before it lapses.', ['wallet_transaction_id' => $credit->id]);
                }

                $credit->update(['reminder_1d_sent_at' => $now]);
                $sent++;

                continue;
            }

            if ($daysLeft <= 3.0 && $credit->reminder_3d_sent_at === null && $daysLeft >= 0.0) {
                Mail::to($user->email)->queue(new WalletExpiryReminder($credit, '3d'));

                if (filled($user->phone)) {
                    $whatsapp->send($user->phone, 'Your Estele wallet credit of ₹'.number_format((float) $credit->amount, 2).' expires in 3 days. Use it on your next order before it lapses.', ['wallet_transaction_id' => $credit->id]);
                }

                $credit->update(['reminder_3d_sent_at' => $now]);
                $sent++;
            }
        }

        // Post-expiry notices for credits that were swept but never told.
        $expired = WalletTransaction::where('type', 'credit')
            ->whereNotNull('expired_at')
            ->whereNull('expiry_notified_at')
            ->get();

        foreach ($expired as $credit) {
            $user = $credit->user;
            if (! $user || blank($user->email)) {
                continue;
            }

            Mail::to($user->email)->queue(new WalletExpiryReminder($credit, 'expired'));

            if (filled($user->phone)) {
                $whatsapp->send($user->phone, 'Your Estele wallet credit of ₹'.number_format((float) $credit->amount, 2).' has now expired, as it was due to. Reach out if you need anything.', ['wallet_transaction_id' => $credit->id]);
            }

            $credit->update(['expiry_notified_at' => now()]);
            $sent++;
        }

        $this->info("Sent {$sent} expiry reminder(s).");

        return self::SUCCESS;
    }
}