<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\SellRequestService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Mail;

/**
 * Two demo buy-back requests so the whole lifecycle is visible in /admin on
 * day one:
 *
 *   1. "Live bidding"   — bidding is open: three vendor invitations, two
 *                          accepted, an active three-way bid battle.
 *   2. "Settled"        — already completed: a ₹5,000 valuation settled into
 *                          a ₹4,500 wallet credit expiring in 2 days, so the
 *                          sell:send-reminders / sell:wallet-expire ladder can
 *                          be exercised (and the Flutter wallet screen shows
 *                          an expiring credit) without waiting 10 days.
 *
 * Everything is created through SellRequestService so state transitions, the
 * audit trail and the wallet writes follow the exact production paths.
 */
class SellRequestSeeder extends Seeder
{
    /** A ~25x25 transparent PNG. */
    private const DEMO_IMAGE = 'iVBORw0KGgoAAAANSUhEUgAAABkAAAAZCAIAAABLixI0AAAAFElEQVR4nGP8z8AARLJgQEGMUQwAAEsBAWaoVv9uAAAAAElFTkSuQmCC';

    public function run(): void
    {
        if (! User::role('vendor')->exists()) {
            $this->call(VendorSeeder::class);
        }

        $customer = User::where('email', 'demo@estele.in')->firstOrFail();

        Mail::fake();

        $service = app(SellRequestService::class);
        $vendors = User::role('vendor')->orderBy('id')->get();

        // ---------- Request 1: live bidding ---------------------------------
        $open = $service->createForCustomer(
            $customer,
            [
                'item_type' => 'chain',
                'description' => '22K gold chain, approx 18 g, worn twice. Looking for a quick valuation.',
                'city' => 'Jaipur',
                'contact_phone' => $customer->phone,
            ],
            self::DEMO_IMAGE,
            'image/png',
            null,
            null,
        );

        foreach ($vendors as $vendor) {
            $service->acceptInvitation($open->invitations()->where('vendor_id', $vendor->id)->first());
        }

        $service->submitBid($open->invitations()->where('vendor_id', $vendors[0]->id)->first(), 8500);
        $service->submitBid($open->invitations()->where('vendor_id', $vendors[1]->id)->first(), 9000);
        $service->submitBid($open->invitations()->where('vendor_id', $vendors[2]->id)->first(), 8600);
        // First vendor chases the lead — re-submission overwrite path.
        $service->submitBid($open->invitations()->where('vendor_id', $vendors[0]->id)->first(), 9500);

        // ---------- Request 2: settled ---------------------------------------
        $settled = $service->createForCustomer(
            $customer,
            [
                'item_type' => 'ring',
                'description' => 'Diamond engagement ring, 0.5 ct, gold band.',
                'city' => 'Delhi',
            ],
            null,
            null,
            null,
            null,
        );

        $winnerVendor = $vendors[1];
        $service->acceptInvitation($settled->invitations()->where('vendor_id', $winnerVendor->id)->first());

        $service->submitBid($settled->invitations()->where('vendor_id', $winnerVendor->id)->first(), 5000);

        $service->closeBidding($settled);

        // Complete the sale immediately — a ₹5,000 valuation becomes a ₹4,500
        // wallet credit (10% deduction).
        $service->settle($settled, 5000);

        // Pull the credit's expiry 8 days closer (10 -> 2 days) so the
        // reminder/expiry commands can actually be demoed. The settle path
        // updated a *locked* instance, so refresh before reading the relation.
        $walletTx = $settled->refresh()->walletCredit;
        if ($walletTx) {
            $walletTx->update(['expires_at' => now()->addDays(2)]);
        }
    }
}