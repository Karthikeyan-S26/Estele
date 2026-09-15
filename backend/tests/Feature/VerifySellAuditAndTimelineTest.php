<?php

namespace Tests\Feature;

use App\Models\SellAuditLog;
use App\Models\SellRequest;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SellTestHelpers;
use Tests\TestCase;

/**
 * The full audit trail: every state-changing action must be recorded with the
 * acting user, and the customer timeline API must be scoped to the owner.
 */
class VerifySellAuditAndTimelineTest extends TestCase
{
    use RefreshDatabase;
    use SellTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
        Mail::fake();
        Storage::fake('public');
    }

    public function test_a_complete_settlement_writes_the_full_event_chain(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor();
        $sell = $this->openedSellRequest($customer, $vendor, 8500);

        $this->sellService()->closeBidding($sell->fresh());
        $this->assertTrue($this->sellService()->settle($sell->fresh(), 8000));

        $events = SellAuditLog::where('sell_request_id', $sell->id)->pluck('event')->sort()->values()->all();
        $expected = [
            'bid_submitted',
            'bidding_opened',
            'bids_closed',
            'created',
            'settlement_completed',
            'vendor_accepted',
            'vendor_invited',
        ];

        $this->assertSame($expected, $events);
    }

    public function test_the_created_event_records_the_customer_as_actor(): void
    {
        $customer = $this->makeCustomer();
        $this->createSellRequest($customer);

        $audit = SellAuditLog::where('event', 'created')->firstOrFail();
        $this->assertSame($customer->getMorphClass(), $audit->actor_type);
        $this->assertSame($customer->id, $audit->actor_id);
        $this->assertNull($audit->from_status);
        $this->assertSame(SellRequest::STATUS_PENDING_BIDS, $audit->to_status);
        $this->assertSame('ring', $audit->metadata['item_type']);
    }

    public function test_every_invitation_and_bid_is_attributable(): void
    {
        $customer = $this->makeCustomer();
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $sell = $this->openedSellRequest($customer, $vendorA, 8500);
        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendorB));
        $this->sellService()->submitBid($this->invitationFor($sell, $vendorB)->fresh(), 8600);

        $accepted = SellAuditLog::where('event', 'vendor_accepted')->orderBy('id')->get();
        $this->assertCount(2, $accepted);
        $this->assertSame([$vendorA->id, $vendorB->id], $accepted->pluck('actor_id')->sort()->values()->all());
        $this->assertSame([$vendorA->id, $vendorB->id], collect($accepted)->map(fn ($a) => $a->metadata['vendor_id'])->sort()->values()->all());

        $submitted = SellAuditLog::where('event', 'bid_submitted')->get();
        $this->assertCount(2, $submitted);
    }

    public function test_the_bids_closed_event_identifies_the_winner(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor();
        $sell = $this->openedSellRequest($customer, $vendor, 8500);
        $this->sellService()->closeBidding($sell->fresh());

        $audit = SellAuditLog::where('event', 'bids_closed')->firstOrFail();
        $this->assertSame(SellRequest::STATUS_BIDDING, $audit->from_status);
        $this->assertSame(SellRequest::STATUS_VALUATION_REVIEW, $audit->to_status);
        $this->assertSame($vendor->id, $audit->metadata['winning_vendor_id']);
        $this->assertSame(8500, $audit->metadata['highest_bid_amount']);
    }

    public function test_the_customer_timeline_endpoint_lists_their_own_request_events(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor();
        $sell = $this->openedSellRequest($customer, $vendor, 8500);
        $this->sellService()->closeBidding($sell->fresh());
        $this->assertTrue($this->sellService()->settle($sell->fresh(), 8000));

        $response = $this->withToken($this->customerApiToken($customer))
            ->getJson('/api/account/sell/requests/'.$sell->request_number)
            ->assertOk();

        $timeline = $response->json('data.timeline');
        $this->assertCount(SellAuditLog::where('sell_request_id', $sell->id)->count(), $timeline);

        $events = collect($timeline)->pluck('event')->sort()->values()->all();
        $this->assertSame([
            'bid_submitted',
            'bidding_opened',
            'bids_closed',
            'created',
            'settlement_completed',
            'vendor_accepted',
            'vendor_invited',
        ], $events);

        $settlement = collect($timeline)->firstWhere('event', 'settlement_completed');
        $this->assertSame('valuation_review', $settlement['from_status']);
        $this->assertSame('completed', $settlement['to_status']);
        $this->assertArrayHasKey('wallet_credit', $settlement['metadata']);
        $this->assertArrayHasKey('wallet_expires_at', $settlement['metadata']);
    }

    public function test_a_stranger_cannot_read_another_customers_timeline(): void
    {
        $owner = $this->makeCustomer();
        $stranger = $this->makeCustomer();
        $sell = $this->createSellRequest($owner);

        $this->withToken($this->customerApiToken($stranger))
            ->getJson('/api/account/sell/requests/'.$sell->request_number)
            ->assertNotFound();
    }

    public function test_the_cancellation_events_carry_the_reason(): void
    {
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);

        $this->assertTrue($this->sellService()->customerCancel($sell, $customer, 'Changed my mind'));

        $audit = SellAuditLog::where('event', 'cancelled_by_customer')->firstOrFail();
        $this->assertSame('Changed my mind', $audit->metadata['reason']);
        $this->assertSame(SellRequest::STATUS_CANCELLED, $audit->to_status);

        $admin = $this->makeCustomer();
        $sell2 = $this->createSellRequest($this->makeCustomer());
        $this->assertTrue($this->sellService()->adminCancel($sell2, 'Suspicious content', $admin));
        $this->assertDatabaseHas('sell_audit_logs', [
            'sell_request_id' => $sell2->id,
            'event' => 'cancelled_by_admin',
            'actor_id' => $admin->id,
        ]);
    }
}