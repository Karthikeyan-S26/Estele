<?php

namespace Tests\Feature;

use App\Models\SellBid;
use App\Models\SellRequest;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SellTestHelpers;
use Tests\TestCase;

/**
 * Winner selection at close — strictly highest active bid, ties broken by the
 * EARLIEST submission, withdrawn bids excluded, no-bid requests expire.
 */
class VerifySellWinnerSelectionTest extends TestCase
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

    public function test_the_highest_bid_wins(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $vendorC = $this->makeVendor();
        $customer = $this->makeCustomer();

        $sell = $this->openedSellRequest($customer, $vendorA, 8500);
        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendorB)->fresh());
        $this->sellService()->submitBid($this->invitationFor($sell, $vendorB)->fresh(), 9000);
        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendorC)->fresh());
        $this->sellService()->submitBid($this->invitationFor($sell, $vendorC)->fresh(), 8800);

        $this->travelTo($sell->bids_end_at->copy()->addSecond());
        $this->artisan('sell:bids-close')->assertSuccessful();

        $sell = $sell->fresh();
        $this->assertSame(SellRequest::STATUS_VALUATION_REVIEW, $sell->status);
        $this->assertSame($vendorB->id, SellBid::findOrFail($sell->winning_bid_id)->vendor_id);
        $this->assertSame('9000.00', $sell->highest_bid_amount);
    }

    public function test_a_tied_bid_is_broken_by_earliest_submission(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $customer = $this->makeCustomer();

        $sell = $this->openedSellRequest($customer, $vendorA, 8000);
        $this->travelTo($sell->bids_end_at->copy()->subMinutes(10));
        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendorB)->fresh());
        $this->sellService()->submitBid($this->invitationFor($sell, $vendorB)->fresh(), 8000);

        $this->travelTo($sell->bids_end_at->copy()->addSecond());
        $this->artisan('sell:bids-close')->assertSuccessful();

        $this->assertSame($vendorA->id, SellBid::findOrFail($sell->fresh()->winning_bid_id)->vendor_id);
    }

    public function test_a_withdrawn_bid_is_not_selected(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $customer = $this->makeCustomer();

        $sell = $this->openedSellRequest($customer, $vendorA, 8000);
        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendorB)->fresh());
        $this->sellService()->submitBid($this->invitationFor($sell, $vendorB)->fresh(), 9000);
        $sell->bids()->where('vendor_id', $vendorB->id)->update(['status' => SellBid::STATUS_WITHDRAWN]);

        $this->travelTo($sell->bids_end_at->copy()->addSecond());
        $this->artisan('sell:bids-close')->assertSuccessful();

        $sell = $sell->fresh();
        $this->assertSame($vendorA->id, SellBid::findOrFail($sell->winning_bid_id)->vendor_id);
        $this->assertSame('8000.00', $sell->highest_bid_amount);
    }

    public function test_without_any_bids_the_request_expires(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);

        $this->travelTo($sell->bids_end_at->copy()->addSecond());
        $this->artisan('sell:bids-close')->assertSuccessful();

        $this->assertSame(SellRequest::STATUS_EXPIRED, $sell->fresh()->status);
        $this->assertNull($sell->fresh()->winner_id);
        $this->assertNull($sell->fresh()->highest_bid_amount);
        $this->assertDatabaseMissing('sell_audit_logs', ['event' => 'bids_closed']);
        $this->assertDatabaseHas('sell_audit_logs', ['event' => 'bids_closed_no_bids']);
    }

    public function test_each_bid_audit_entry_carries_the_actor_and_amount_context(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $customer = $this->makeCustomer();

        $sell = $this->openedSellRequest($customer, $vendorA, 8500);
        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendorB)->fresh());
        $this->sellService()->submitBid($this->invitationFor($sell, $vendorB)->fresh(), 9500);
        $this->sellService()->submitBid($this->invitationFor($sell, $vendorA)->fresh(), 8800);

        $audit = \App\Models\SellAuditLog::where('event', 'bid_updated')
            ->where('metadata->vendor_id', '!=', null)->get();

        $this->assertCount(1, $audit);
        $this->assertSame($vendorA->id, (int) $audit->first()->metadata['vendor_id']);
        $this->assertSame(8800.0, (float) $audit->first()->metadata['amount']);
        $this->assertSame(9500.0, (float) $audit->first()->metadata['current_lead']);
    }

    public function test_reopening_is_impossible_after_a_winning_selection(): void
    {
        $vendorA = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendorA, 8000);

        $this->travelTo($sell->bids_end_at->copy()->addSecond());
        $this->artisan('sell:bids-close')->assertSuccessful();

        $this->expectException(\DomainException::class);
        $this->sellService()->submitBid($this->invitationFor($sell, $vendorA)->fresh(), 9999);
    }
}