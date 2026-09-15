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
 * Bid amount validation at the service layer and across the HTTP surface:
 * the ₹100 floor, ceiling, malformed input, invitation-state guards and
 * re-submission semantics.
 */
class VerifySellBidValidationTest extends TestCase
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

    public function test_a_bid_of_exactly_100_is_accepted(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);

        $this->sellService()->submitBid($this->invitationFor($sell, $vendor)->fresh(), 100);

        $this->assertDatabaseHas('sell_bids', [
            'sell_request_id' => $sell->id,
            'vendor_id' => $vendor->id,
            'amount' => '100.00',
        ]);
    }

    public function test_a_zero_bid_is_rejected(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Bids must be at least ₹100.');
        $this->sellService()->submitBid($this->invitationFor($sell, $vendor)->fresh(), 0);

        $this->assertDatabaseCount('sell_bids', 0);
    }

    public function test_a_negative_bid_is_rejected(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);

        try {
            $this->sellService()->submitBid($this->invitationFor($sell, $vendor)->fresh(), -5);
            $this->fail('Expected a DomainException.');
        } catch (\DomainException $e) {
            $this->assertSame('Bids must be at least ₹100.', $e->getMessage());
        }

        $this->assertDatabaseCount('sell_bids', 0);
    }

    public function test_a_bid_below_one_hundred_is_rejected(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Bids must be at least ₹100.');
        $this->sellService()->submitBid($this->invitationFor($sell, $vendor)->fresh(), 99);

        $this->assertDatabaseCount('sell_bids', 0);
    }

    public function test_a_bid_cannot_be_placed_without_accepted_invitation(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);
        $invitation = $this->invitationFor($sell, $vendor); // status: pending

        try {
            $this->sellService()->submitBid($invitation, 500);
            $this->fail('Expected a DomainException.');
        } catch (\DomainException) {
            // expected
        }

        $this->assertSame(SellRequest::STATUS_PENDING_BIDS, $sell->fresh()->status);
        $this->assertDatabaseCount('sell_bids', 0);
    }

    public function test_an_amount_above_the_ceiling_is_rejected_by_the_http_layer(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);
        $invitation = $this->invitationFor($sell, $vendor);

        $this->post(route('sell.vendor.bid'), [
            'invitation_token' => $invitation->token,
            'amount' => 20000000,
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('sell_bids', 0);
    }

    public function test_a_malformed_amount_is_rejected_by_the_http_layer(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);
        $invitation = $this->invitationFor($sell, $vendor);

        $this->post(route('sell.vendor.bid'), [
            'invitation_token' => $invitation->token,
            'amount' => 'not-a-number',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('sell_bids', 0);
    }

    public function test_a_valid_http_bid_is_recorded_as_an_audit_bid_submitted(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);
        $invitation = $this->invitationFor($sell, $vendor);

        $this->post(route('sell.vendor.bid'), [
            'invitation_token' => $invitation->token,
            'amount' => 1200,
        ])->assertRedirect();

        $this->assertDatabaseHas('sell_bids', [
            'sell_request_id' => $sell->id,
            'vendor_id' => $vendor->id,
            'amount' => '1200.00',
        ]);
        $this->assertDatabaseHas('sell_audit_logs', [
            'sell_request_id' => $sell->id,
            'event' => 'bid_submitted',
        ]);
    }

    public function test_resubmitting_replaces_the_vendor_previous_bid(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor, 8000);

        $this->sellService()->submitBid($this->invitationFor($sell, $vendor)->fresh(), 9500);

        $this->assertSame(1, SellRequest::where('id', $sell->id)->first()->bids()->count());
        $bid = $sell->bids()->where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertSame('9500.00', $bid->amount);
        $this->assertDatabaseHas('sell_audit_logs', [
            'sell_request_id' => $sell->id,
            'event' => 'bid_updated',
        ]);
    }

    public function test_a_duplicate_amount_still_updates_the_submission_time(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor, 8000);

        $this->travelTo($sell->bids_end_at->copy()->subMinutes(5));
        $this->sellService()->submitBid($this->invitationFor($sell, $vendor)->fresh(), 8000);

        $bid = $sell->bids()->where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertSame('8000.00', $bid->amount);
        $this->assertNotNull($bid->updated_at);
    }
}