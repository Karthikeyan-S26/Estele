<?php

namespace App\Console\Commands;

use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Console\Command;

/**
 * Sweeps expired wallet credits (Old Jewellery settlements / any credit with an
 * expires_at): whatever remains of each expiring credit is struck from the
 * user's balance at that moment. Runs hourly — a pending order placed seconds
 * before expiry still benefits right up until the sweep fires.
 */
class SellWalletExpire extends Command
{
    protected $signature = 'sell:wallet-expire';

    protected $description = 'Strike off wallet credits that have passed their expiry window';

    public function handle(WalletService $wallet): int
    {
        $due = WalletTransaction::where('type', 'credit')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNull('expired_at')
            ->get();

        $expired = 0;
        foreach ($due as $credit) {
            $wallet->expireCredit($credit);
            $expired++;
        }

        $this->info("Expired {$expired} wallet credit(s).");

        return self::SUCCESS;
    }
}