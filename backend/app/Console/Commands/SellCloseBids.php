<?php

namespace App\Console\Commands;

use App\Models\SellRequest;
use App\Services\SellRequestService;
use Illuminate\Console\Command;

/**
 * Closes the 3-hour bidding window for any sell request that has passed its
 * bids_end_at while still open. Winner = highest bid, then earliest
 * submitted_at (deterministic). With zero bids the request expires. Safe to
 * run frequently — already-closed requests are no-ops.
 */
class SellCloseBids extends Command
{
    protected $signature = 'sell:bids-close';

    protected $description = 'Close expired sell bidding windows and resolve the winning vendor bid';

    public function handle(SellRequestService $service): int
    {
        $due = SellRequest::where('status', SellRequest::STATUS_BIDDING)
            ->whereNotNull('bids_end_at')
            ->where('bids_end_at', '<=', now())
            ->get();

        $closed = 0;
        foreach ($due as $sell) {
            $service->closeBidding($sell);
            $closed++;
        }

        $this->info("Closed {$closed} bidding window(s).");

        return self::SUCCESS;
    }
}