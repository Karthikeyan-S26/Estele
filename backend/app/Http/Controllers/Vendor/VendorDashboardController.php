<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\View\View;

class VendorDashboardController extends Controller
{
    public function index(): View
    {
        /** @var Vendor $vendor */
        $vendor = Vendor::current();

        $openInvitations = $vendor->invitations()
            ->with('request')
            ->where('response_status', 'pending')
            ->where('expires_at', '>', now())
            ->whereHas('request', fn ($query) => $query->where('status', 'bidding_active'))
            ->orderBy('expires_at')
            ->get();

        $bids = $vendor->bids()->with('request')->get();

        return view('vendor.portal.dashboard', [
            'vendor' => $vendor,
            'openInvitations' => $openInvitations,
            'stats' => [
                'open' => $openInvitations->count(),
                'won' => $bids->filter(fn ($bid) => $bid->request?->winning_bid_id === $bid->id)->count(),
                'submitted' => $bids->count(),
            ],
        ]);
    }
}
