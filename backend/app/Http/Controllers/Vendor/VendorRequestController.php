<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\OldJewelleryRequest;
use App\Models\OldJewelleryVendorInvitation;
use App\Models\Vendor;
use App\Services\OldJewellery\OldJewelleryBiddingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A single request from the vendor's own side: the same photo/video/bid
 * form as the no-login signed-link page (resources/views/vendor/
 * old-jewellery-bid.blade.php), but reached through the portal instead of a
 * one-off emailed token, and reusing the exact same OldJewelleryBiddingService
 * so both paths share one source of truth for what a bid is allowed to do.
 */
class VendorRequestController extends Controller
{
    public function __construct(private readonly OldJewelleryBiddingService $biddingService) {}

    public function show(OldJewelleryRequest $oldJewelleryRequest): View
    {
        $invitation = $this->invitationFor($oldJewelleryRequest);
        abort_unless($invitation, 404);

        return view('vendor.portal.request-show', [
            'vendor' => Vendor::current(),
            'request' => $oldJewelleryRequest,
            'invitation' => $invitation,
        ]);
    }

    public function bid(Request $request, OldJewelleryRequest $oldJewelleryRequest): RedirectResponse
    {
        $invitation = $this->invitationFor($oldJewelleryRequest);
        abort_unless($invitation, 404);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:'.OldJewelleryBiddingService::MAX_BID_AMOUNT],
        ]);

        try {
            $this->biddingService->accept($invitation, (float) $validated['amount']);
        } catch (\DomainException $e) {
            return redirect()->route('vendor.requests.show', $oldJewelleryRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('vendor.requests.show', $oldJewelleryRequest)->with('success', 'Your bid has been submitted.');
    }

    public function decline(Request $request, OldJewelleryRequest $oldJewelleryRequest): RedirectResponse
    {
        $invitation = $this->invitationFor($oldJewelleryRequest);
        abort_unless($invitation, 404);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->biddingService->decline($invitation, $validated['reason'] ?? null);
        } catch (\DomainException $e) {
            return redirect()->route('vendor.requests.show', $oldJewelleryRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('vendor.requests.show', $oldJewelleryRequest)->with('success', 'Your response has been recorded.');
    }

    /**
     * The one thing that proves this request is any of this vendor's
     * business at all — every other query and view in this controller is
     * scoped through it, never through the request alone.
     */
    private function invitationFor(OldJewelleryRequest $oldJewelleryRequest): ?OldJewelleryVendorInvitation
    {
        $vendor = Vendor::current();

        return $vendor?->invitations()
            ->where('old_jewellery_request_id', $oldJewelleryRequest->id)
            ->first();
    }
}
