<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\View\View;

/**
 * Every bid this vendor has ever placed, and — for the ones it won — the
 * settlement record: the agreed amount and whether the customer's wallet
 * credit for it is still active, already spent, or expired. There is no
 * separate vendor payout ledger in this app; this is the closest thing to
 * "how much, for what, and what happened to it" a vendor contact can see.
 */
class VendorBidHistoryController extends Controller
{
    public function index(): View
    {
        /** @var Vendor $vendor */
        $vendor = Vendor::current();

        $bids = $vendor->bids()
            ->with(['request.walletCredit'])
            ->orderByDesc('submitted_at')
            ->get();

        return view('vendor.portal.bids', [
            'vendor' => $vendor,
            'bids' => $bids,
        ]);
    }
}
