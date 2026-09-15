<?php

namespace Tests\Feature;

use App\Models\SellRequest;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SellTestHelpers;
use Tests\TestCase;

/**
 * The 3-hour live bidding window — open edge, exact-deadline edge,
 * post-deadline lock, close/expire handling and idempotent closures.
 */
class VerifySellBiddingWindowTest extends TestCase
{
    use RefreshDatabase;
    use SellTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
        Mail::fake();
        Queue::fake();
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_bidding_is_open_shortly_before_the_deadline(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);

        $this->travelTo($sell->bids_end_at->copy()->subSecond());

        $this->assertTrue($sell->fresh()->isBiddingOpen());
        $this->assertSame(SellRequest::STATUS_BIDDING, $sell->fresh()->status);
    }

    public function test_bidding_is_closed_at_the_exact_deadline(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);

        $this->travelTo($sell->bids_end_at->copy());

        $this->assertFalse($sell->fresh()->isBiddingOpen());

        $this->expectException(\DomainException::class);
        $this->sellService()->submitBid($this->invitationFor($sell, $vendor)->fresh(), 1000);
    }

    public function test_no_bid_can_be_placed_after_the_deadline(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);

        $this->travelTo($sell->bids_end_at->copy()->addMinute());

        $this->expectException(\DomainException::class);
        $this->sellService()->submitBid($this->invitationFor($sell, $vendor)->fresh(), 1000);
    }

    public function test_a_bid_cannot_be_updated_after_the_deadline(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor, 8000);

        $this->travelTo($sell->bids_end_at->copy()->addMinute());

        try {
            $this->sellService()->submitBid($this->invitationFor($sell, $vendor)->fresh(), 9999);
            $this->fail('Expected a DomainException for a post-deadline update.');
        } catch (\DomainException) {
            // expected
        }

        $this->assertSame('8000.00', $this->invitationFor($sell, $vendor)->fresh()->sellRequest->bids()->where('vendor_id', $vendor->id)->firstOrFail()->amount);
    }

    public function test_the_sell_close_bids_command_closes_requests_at_the_deadline(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor, 8000);

        $this->travelTo($sell->bids_end_at->copy()->addSecond());
        $this->artisan('sell:bids-close')->assertSuccessful();

        $this->assertSame(SellRequest::STATUS_VALUATION_REVIEW, $sell->fresh()->status);
        $this->assertSame('8000.00', $sell->fresh()->highest_bid_amount);
        $this->assertSame($vendor->id, \App\Models\SellBid::findOrFail($sell->fresh()->winning_bid_id)->vendor_id);
    }

    public function test_the_sell_close_bids_command_idempotently_closes_only_once(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor, 8000);

        $this->travelTo($sell->bids_end_at->copy()->addSecond());
        $this->artisan('sell:bids-close')->assertSuccessful();
        $this->artisan('sell:bids-close')->assertSuccessful();

        $this->assertSame(SellRequest::STATUS_VALUATION_REVIEW, $sell->fresh()->status);
        $this->assertSame(1, SellRequest::where('status', SellRequest::STATUS_VALUATION_REVIEW)->count());
    }

    public function test_a_request_with_no_bids_expires_without_a_winner(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor); // accepted, never bid

        $this->travelTo($sell->bids_end_at->copy()->addSecond());
        $this->artisan('sell:bids-close')->assertSuccessful();

        $this->assertSame(SellRequest::STATUS_EXPIRED, $sell->fresh()->status);
        $this->assertNull($sell->fresh()->winning_bid_id);
        $this->assertNull($sell->fresh()->highest_bid_amount);
        $this->assertDatabaseHas('sell_audit_logs', [
            'sell_request_id' => $sell->id,
            'event' => 'bids_closed_no_bids',
            'from_status' => SellRequest::STATUS_BIDDING,
            'to_status' => SellRequest::STATUS_EXPIRED,
        ]);
    }

    public function test_a_vendor_cannot_reopen_a_closed_request_by_accepting_later(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);
        $invitation = $this->invitationFor($sell, $vendor);

        $this->travelTo($sell->bids_end_at->copy()->addHour());
        $this->sellService()->acceptInvitation($invitation->fresh());

        // The window slice itself can never re-open: accepting late may move
        // the state label but isBiddingOpen() honours the deadline strictly.
        $this->assertFalse($sell->fresh()->isBiddingOpen());
        $this->expectException(\DomainException::class);
        $this->sellService()->submitBid($this->invitationFor($sell->fresh(), $vendor)->fresh(), 1000);
    }

    public function test_a_pending_request_cannot_be_closed_early(): void
    {
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);

        $this->travelTo($sell->bids_end_at->copy()->addSecond());
        $this->artisan('sell:bids-close')->assertSuccessful();

        // No vendor ever accepted, so the request was never bidable and the
        // command must not have moved it anywhere.
        $this->assertSame(SellRequest::STATUS_PENDING_BIDS, $sell->fresh()->status);
    }
}