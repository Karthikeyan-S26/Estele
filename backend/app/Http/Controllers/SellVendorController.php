<?php

namespace App\Http\Controllers;

use App\Models\SellInvitation;
use App\Models\SellRequest;
use App\Services\SellRequestService;
use Illuminate\Http\Request;

/**
 * Tokenized web flow for buy-back vendors — no account sessions needed, the
 * emailed invitation token IS the authorization. A vendor can view the request,
 * accept/decline the invitation, and (after accepting, while bidding is open)
 * place/update their bid from the same landing page.
 */
class SellVendorController extends Controller
{
    public function __construct(private readonly SellRequestService $service) {}

    public function invitation(string $token)
    {
        $invitation = SellInvitation::where('token', $token)->with(['sellRequest', 'vendor'])->firstOrFail();

        $otherOpen = SellInvitation::where('vendor_id', $invitation->vendor_id)
            ->where('status', 'pending')
            ->where('id', '!=', $invitation->id)
            ->with('sellRequest')
            ->get();

        return view('sell.vendor.invitation', [
            'invitation' => $invitation,
            'sell' => $invitation->sellRequest,
            'vendor' => $invitation->vendor,
            'myBid' => $invitation->sellRequest->bids()->where('vendor_id', $invitation->vendor_id)->first(),
            'otherOpen' => $otherOpen,
        ]);
    }

    public function accept(string $token)
    {
        $invitation = SellInvitation::where('token', $token)->firstOrFail();

        $this->service->acceptInvitation($invitation);

        return redirect()->route('sell.vendor.invitation', $token)->with('status', 'Invitation accepted — you can now bid.');
    }

    public function decline(string $token)
    {
        $invitation = SellInvitation::where('token', $token)->firstOrFail();

        $this->service->declineInvitation($invitation);

        return redirect()->route('sell.vendor.invitation', $token)->with('status', 'Invitation declined. No hard feelings.');
    }

    public function storeBid(Request $request)
    {
        $validated = $request->validate([
            'invitation_token' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:100', 'max:10000000'],
        ]);

        $invitation = SellInvitation::where('token', $validated['invitation_token'])->firstOrFail();

        try {
            $this->service->submitBid($invitation, (float) $validated['amount']);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('sell.vendor.invitation', $invitation->token)
            ->with('status', 'Bid submitted. You can update it until the window closes.');
    }
}