<?php

namespace App\Console\Commands;

use App\Mail\WalletExpiryReminder;
use App\Models\WalletTransaction;
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

    public function handle(): int
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
                $credit->update(['reminder_1d_sent_at' => $now]);
                $sent++;

                continue;
            }

            if ($daysLeft <= 3.0 && $credit->reminder_3d_sent_at === null && $daysLeft >= 0.0) {
                Mail::to($user->email)->queue(new WalletExpiryReminder($credit, '3d'));
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
            $credit->update(['expiry_notified_at' => now()]);
            $sent++;
        }

        $this->info("Sent {$sent} expiry reminder(s).");

        return self::SUCCESS;
    }
}