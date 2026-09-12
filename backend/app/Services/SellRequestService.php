<?php

namespace App\Services;

use App\Mail\NewSellRequestAdminNotification;
use App\Mail\SellInvitationMail;
use App\Models\SellBid;
use App\Models\SellInvitation;
use App\Models\SellRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The only code path that creates/mutates SellRequests and SellBids. State
 * changes always flow through SellRequest::transitionTo() so the audit trail
 * is complete, and settlement uses WalletService (the single wallet writer).
 */
class SellRequestService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly WhatsAppManager $whatsapp,
    ) {}

    public function createForCustomer(User $user, array $data, ?string $imageBase64, ?string $imageMime, ?string $videoBase64, ?string $videoMime): SellRequest
    {
        $request = DB::transaction(function () use ($user, $data, $imageBase64, $imageMime, $videoBase64, $videoMime) {
            $sell = SellRequest::create([
                'user_id' => $user->id,
                'request_number' => SellRequest::nextRequestNumber(),
                'item_type' => $data['item_type'],
                'description' => $data['description'] ?? null,
                'city' => $data['city'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? $user->phone,
                'status' => SellRequest::STATUS_PENDING_BIDS,
                'bids_start_at' => now(),
                'bids_end_at' => now()->addHours(SellRequest::BID_DURATION_HOURS),
            ]);

            if ($imageBase64) {
                $path = "sell-requests/{$sell->id}/image.".Str::lower(Str::afterLast($imageMime ?? 'image/jpeg', '/'));
                Storage::disk('public')->put($path, base64_decode($imageBase64));
                $sell->update(['image_path' => $path]);
            }

            if ($videoBase64) {
                $path = "sell-requests/{$sell->id}/video.".Str::lower(Str::afterLast($videoMime ?? 'video/mp4', '/'));
                Storage::disk('public')->put($path, base64_decode($videoBase64));
                $sell->update(['video_path' => $path]);
            }

            $sell->audit('created', null, SellRequest::STATUS_PENDING_BIDS, ['item_type' => $sell->item_type], $user);

            return $sell;
        });

        $this->inviteAllVendors($request);

        // Alert the back-office too — the first bidder typically arrives fast.
        // Guard pinned to 'web': under the mobile api-token guard Spatie would
        // otherwise look up the role on guard 'api-token' and find nothing.
        $admins = User::role(['super_admin', 'marketing'], 'web')->whereNotNull('email')->get();
        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(new NewSellRequestAdminNotification($request));

            if (filled($admin->phone)) {
                $this->whatsapp->send(
                    $admin->phone,
                    'New old-jewellery buy-back request '.$request->request_number.' ('.$request->item_type.($request->city ? ', '.$request->city : '').') is open for vendor bids — review and settle it in the admin panel.',
                    ['sell_request_id' => $request->id, 'request_number' => $request->request_number],
                );
            }
        }

        return $request->fresh();
    }

    /**
     * Invite every user holding the vendor role. Tokens are emailed (the vendor
     * never needs an account-backed session to bid — the token IS the auth).
     */
    public function inviteAllVendors(SellRequest $sell): void
    {
        $vendors = User::role('vendor', 'web')->get();

        foreach ($vendors as $vendor) {
            if ($sell->invitations()->where('vendor_id', $vendor->id)->exists()) {
                continue;
            }

            $invitation = $sell->invitations()->create([
                'vendor_id' => $vendor->id,
                'token' => Str::random(64),
                'status' => SellInvitation::STATUS_PENDING,
                'sent_at' => now(),
            ]);

            $sell->audit('vendor_invited', null, null, ['vendor_id' => $vendor->id, 'invitation_id' => $invitation->id]);

            if (filled($vendor->email)) {
                Mail::to($vendor->email)->queue(new SellInvitationMail($invitation));
            }

            if (filled($vendor->phone)) {
                $this->whatsapp->send(
                    $vendor->phone,
                    "You're invited to bid on Estele buy-back request {$sell->request_number} ({$sell->item_type}). Bidding closes ".$sell->bids_end_at?->format('d M H:i').'. '.route('sell.vendor.invitation', $invitation->token),
                    ['sell_request_id' => $sell->id, 'request_number' => $sell->request_number],
                );
            }
        }
    }

    public function acceptInvitation(SellInvitation $invitation): bool
    {
        if (! $invitation->isPending()) {
            return false;
        }

        DB::transaction(function () use ($invitation) {
            $invitation->update([
                'status' => SellInvitation::STATUS_ACCEPTED,
                'responded_at' => now(),
            ]);

            $sell = $invitation->sellRequest;
            $sell->audit('vendor_accepted', $sell->status, null, ['vendor_id' => $invitation->vendor_id], $invitation->vendor);

            // First acceptance opens the bidding window.
            if ($sell->status === SellRequest::STATUS_PENDING_BIDS) {
                $sell->transitionTo(SellRequest::STATUS_BIDDING, 'bidding_opened', null, $invitation->vendor);
            }
        });

        return true;
    }

    public function declineInvitation(SellInvitation $invitation): bool
    {
        if (! $invitation->isPending()) {
            return false;
        }

        DB::transaction(function () use ($invitation) {
            $invitation->update([
                'status' => SellInvitation::STATUS_DECLINED,
                'responded_at' => now(),
            ]);

            $invitation->sellRequest->audit('vendor_declined', null, null, ['vendor_id' => $invitation->vendor_id], $invitation->vendor);
        });

        return true;
    }

    /**
     * Vendor places (or re-places) a bid. One active bid per vendor per request
     * — re-submitting overwrites the amount/timestamp, so a vendor can always
     * chase the lead until the window closes.
     *
     * @throws \DomainException on a request outside the valid bidding window.
     */
    public function submitBid(SellInvitation $invitation, float $amount): SellBid
    {
        $sell = $invitation->sellRequest;

        if (! $invitation->isAccepted()) {
            throw new \DomainException('You need to accept the invitation before bidding.');
        }

        if (! $sell->isBiddingOpen()) {
            throw new \DomainException('The bidding window for this request is closed.');
        }

        if ($amount < 100) {
            throw new \DomainException('Bids must be at least ₹100.');
        }

        return DB::transaction(function () use ($invitation, $sell, $amount) {
            $bid = SellBid::updateOrCreate(
                ['sell_request_id' => $sell->id, 'vendor_id' => $invitation->vendor_id],
                ['amount' => $amount, 'status' => SellBid::STATUS_ACTIVE, 'submitted_at' => now()],
            );

            $sell->refresh();
            $leading = $sell->activeBids()->max('amount');

            $sell->audit($bid->wasRecentlyCreated ? 'bid_submitted' : 'bid_updated', null, null, [
                'vendor_id' => $invitation->vendor_id,
                'amount' => $amount,
                'current_lead' => $leading,
            ], $invitation->vendor);

            return $bid;
        });
    }

    /**
     * Close the bidding window: resolve the deterministic winner (highest
     * amount, earliest submitted_at), move to valuation_review so the admin can
     * price it. With zero bids the request expires instead. Idempotent: running
     * again after the first close is a no-op.
     */
    public function closeBidding(SellRequest $sell): void
    {
        // Never trust the caller's in-memory status (a stale instance is the
        // norm in batch scripts) — re-read the row before the state guard.
        $sell->refresh();

        if ($sell->status !== SellRequest::STATUS_BIDDING) {
            return;
        }

        $winner = $sell->resolvedWinner();

        DB::transaction(function () use ($sell, $winner) {
            if (! $winner) {
                $sell->transitionTo(SellRequest::STATUS_EXPIRED, 'bids_closed_no_bids', null);

                return;
            }

            $sell->update([
                'highest_bid_amount' => $winner->amount,
                'winning_bid_id' => $winner->id,
                'result_selected_at' => now(),
            ]);

            $sell->transitionTo(SellRequest::STATUS_VALUATION_REVIEW, 'bids_closed', [
                'winning_vendor_id' => $winner->vendor_id,
                'highest_bid_amount' => (float) $winner->amount,
            ]);
        });
    }

    /**
     * Admin sets the valuation and settles: 90% is credited to the customer's
     * wallet (10% platform deduction), valid for WALLET_CREDIT_VALID_DAYS.
     *
     * Idempotent by construction — settlement only ever fires from
     * valuation_review, and the completed state has no outgoing transitions.
     */
    public function settle(SellRequest $sell, float $valuation): bool
    {
        $valuation = round(max(0.0, $valuation), 2);
        $deduction = round($valuation * SellRequest::SETTLEMENT_DEDUCTION_RATE, 2);
        $credit = round($valuation * SellRequest::SETTLEMENT_CREDIT_RATE, 2);

        $settled = DB::transaction(function () use ($sell, $valuation, $deduction, $credit) {
            // Row-lock + re-read: two tabs settling at once must not
            // double-credit, and a stale in-memory instance must not block
            // a valid settlement.
            $locked = SellRequest::whereKey($sell->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== SellRequest::STATUS_VALUATION_REVIEW) {
                return false;
            }

            $locked->update([
                'admin_valuation' => $valuation,
                'deduction_amount' => $deduction,
                'wallet_credit' => $credit,
                'settlement_at' => now(),
            ]);

            // Nested through WalletService's own transaction (savepoint under
            // MySQL) — the row-locked credit keeps the wallet math in one place.
            $tx = $this->wallet->credit(
                $locked->user,
                $credit,
                WalletTransaction::REASON_SELL_SETTLEMENT,
                $locked,
                now()->addDays(SellRequest::WALLET_CREDIT_VALID_DAYS),
            );

            $locked->update(['wallet_transaction_id' => $tx->id]);

            $locked->transitionTo(SellRequest::STATUS_COMPLETED, 'settlement_completed', [
                'valuation' => $valuation,
                'deduction_amount' => $deduction,
                'wallet_credit' => $credit,
                'wallet_expires_at' => $tx->expires_at?->toIso8601String(),
            ]);

            return true;
        });

        if ($settled) {
            $sell->refresh();

            if (filled($sell->user?->phone)) {
                $this->whatsapp->send(
                    $sell->user->phone,
                    "Your old-jewellery buy-back request {$sell->request_number} was settled — ₹".number_format((float) $sell->wallet_credit, 2).' credited to your Estele wallet (valid '.SellRequest::WALLET_CREDIT_VALID_DAYS.' days from settlement).',
                    ['sell_request_id' => $sell->id, 'request_number' => $sell->request_number],
                );
            }
        }

        return $settled;
    }

    public function customerCancel(SellRequest $sell, User $user, string $reason): bool
    {
        abort_unless($sell->user_id === $user->id, 403);

        $sell->refresh();

        if ($sell->status !== SellRequest::STATUS_PENDING_BIDS && $sell->status !== SellRequest::STATUS_BIDDING) {
            return false;
        }

        DB::transaction(function () use ($sell, $reason) {
            $sell->update([
                'cancelled_by' => 'customer',
                'cancel_reason' => $reason,
            ]);

            $sell->transitionTo(SellRequest::STATUS_CANCELLED, 'cancelled_by_customer', ['reason' => $reason]);
        });

        return true;
    }

    public function adminCancel(SellRequest $sell, string $reason, ?User $admin = null): bool
    {
        $sell->refresh();

        if ($sell->status !== SellRequest::STATUS_PENDING_BIDS && $sell->status !== SellRequest::STATUS_BIDDING) {
            return false;
        }

        DB::transaction(function () use ($sell, $reason, $admin) {
            $sell->update([
                'cancelled_by' => 'admin',
                'cancel_reason' => $reason,
            ]);

            $sell->transitionTo(SellRequest::STATUS_CANCELLED, 'cancelled_by_admin', ['reason' => $reason], $admin);
        });

        return true;
    }
}