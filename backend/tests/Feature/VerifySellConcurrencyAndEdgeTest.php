<?php

namespace Tests\Feature;

use App\Models\SellAuditLog;
use App\Models\SellRequest;
use App\Models\WalletTransaction;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SellTestHelpers;
use Tests\TestCase;

/**
 * Concurrency-style guards and state-machine edges: idempotent closures,
 * stale-instance safety, double actions never double-commit, and cancellation
 * semantics across the service and HTTP layers.
 */
class VerifySellConcurrencyAndEdgeTest extends TestCase
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

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_closing_a_stale_instance_when_another_process_has_already_moved_it(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor, 8000);

        // Another "process" settles first while this literal instance is stale.
        $this->sellService()->closeBidding($sell->fresh());
        $this->assertTrue($this->sellService()->settle($sell->fresh(), 8000));

        // The stale instance still points at the old in-memory status but the
        // service re-reads the row and must refuse to touch it.
        $this->sellService()->closeBidding($sell);
        $this->sellService()->settle($sell, 9000);

        $fresh = $sell->fresh();
        $this->assertSame(SellRequest::STATUS_COMPLETED, $fresh->status);
        $this->assertSame('8000.00', $fresh->admin_valuation);
        $this->assertSame(1, WalletTransaction::count());
        $this->assertSame('7200.00', $customer->fresh()->wallet_balance);
        $this->assertSame(1, SellAuditLog::where('event', 'settlement_completed')->count());
    }

    public function test_double_close_never_double_transitions(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor, 8000);

        $this->sellService()->closeBidding($sell->fresh());
        $this->sellService()->closeBidding($sell->fresh());

        $this->assertSame(SellRequest::STATUS_VALUATION_REVIEW, $sell->fresh()->status);
        $this->assertSame(1, SellAuditLog::where('event', 'bids_closed')->count());
        $this->assertSame(1, SellAuditLog::where('from_status', SellRequest::STATUS_BIDDING)->where('to_status', SellRequest::STATUS_VALUATION_REVIEW)->count());
    }

    public function test_a_customer_can_cancel_while_bidding_is_still_open(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor, 8000);

        $this->assertTrue($this->sellService()->customerCancel($sell->fresh(), $customer, 'Too slow, going to a jeweller'));

        $fresh = $sell->fresh();
        $this->assertSame(SellRequest::STATUS_CANCELLED, $fresh->status);
        $this->assertSame('customer', $fresh->cancelled_by);
        $this->assertSame('Too slow, going to a jeweller', $fresh->cancel_reason);
        $this->assertFalse($fresh->isBiddingOpen());
    }

    public function test_a_cancelled_request_cannot_be_cancelled_again(): void
    {
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);

        $this->assertTrue($this->sellService()->customerCancel($sell, $customer, 'First'));
        $this->assertFalse($this->sellService()->customerCancel($sell->fresh(), $customer, 'Again'));

        $this->assertSame('First', $sell->fresh()->cancel_reason);
        $this->assertSame(1, SellAuditLog::where('event', 'cancelled_by_customer')->count());
    }

    public function test_an_admin_can_cancel_a_pending_request(): void
    {
        $customer = $this->makeCustomer();
        $admin = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);

        $this->assertTrue($this->sellService()->adminCancel($sell, 'Duplicate of existing request', $admin));

        $fresh = $sell->fresh();
        $this->assertSame(SellRequest::STATUS_CANCELLED, $fresh->status);
        $this->assertSame('admin', $fresh->cancelled_by);
        $this->assertDatabaseHas('sell_audit_logs', [
            'sell_request_id' => $sell->id,
            'event' => 'cancelled_by_admin',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_a_non_owner_cannot_cancel_someone_elses_request(): void
    {
        $owner = $this->makeCustomer();
        $stranger = $this->makeCustomer();
        $sell = $this->createSellRequest($owner);

        try {
            $this->sellService()->customerCancel($sell, $stranger, 'no');
            $this->fail('Expected an HttpException 403.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame(SellRequest::STATUS_PENDING_BIDS, $sell->fresh()->status);
    }

    public function test_the_cancel_api_rejects_a_second_cancellation(): void
    {
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);
        $token = $this->customerApiToken($customer);

        $this->withToken($token)
            ->postJson('/api/account/sell/requests/'.$sell->request_number.'/cancel', ['reason' => 'No longer interested'])
            ->assertOk()
            ->assertJsonPath('data.status', SellRequest::STATUS_CANCELLED);

        $this->withToken($token)
            ->postJson('/api/account/sell/requests/'.$sell->request_number.'/cancel', ['reason' => 'Again'])
            ->assertStatus(422)
            ->assertJson(['message' => 'This request can no longer be cancelled.']);
    }

    public function test_a_cancelled_request_accepts_no_further_bids(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);
        $this->sellService()->customerCancel($sell->fresh(), $customer, 'Cancelled');

        $this->assertFalse($sell->fresh()->isBiddingOpen());

        try {
            $this->sellService()->submitBid($this->invitationFor($sell->fresh(), $vendor)->fresh(), 900);
            $this->fail('Expected a DomainException.');
        } catch (\DomainException) {
            // expected
        }

        $this->assertDatabaseCount('sell_bids', 0);
    }

    public function test_an_expired_request_cannot_be_settled_or_reopened(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor);

        $this->travelTo($sell->bids_end_at->copy()->addSecond());
        $this->artisan('sell:bids-close')->assertSuccessful();
        $this->assertSame(SellRequest::STATUS_EXPIRED, $sell->fresh()->status);

        $this->assertFalse($this->sellService()->settle($sell->fresh(), 5000));
        $this->assertSame(0, WalletTransaction::count());

        try {
            $this->sellService()->submitBid($this->invitationFor($sell->fresh(), $vendor)->fresh(), 800);
            $this->fail('Expected a DomainException.');
        } catch (\DomainException) {
            // expected
        }
    }

    public function test_a_settlement_never_double_credits_even_with_a_stale_instance(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor, 8000);
        $this->sellService()->closeBidding($sell->fresh());

        $stale = $sell->fresh();
        $winner = $this->sellService()->settle($stale, 8000);
        $second = $this->sellService()->settle($stale, 8000); // same stale instance

        $this->assertTrue($winner);
        $this->assertFalse($second);
        $this->assertSame('7200.00', $customer->fresh()->wallet_balance);
        $this->assertCount(1, WalletTransaction::where('reason', 'sell_settlement')->get());
    }
}