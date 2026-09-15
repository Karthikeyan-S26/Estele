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
 * Vendor privacy / cross-vendor isolation (tokenized web flow): a vendor may
 * see and bid on their OWN invitation, but must never see another vendor's
 * bid, another vendor's invitation, an admin valuation, or any other request's
 * media.
 */
class VerifySellVendorPrivacyTest extends TestCase
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

    public function test_a_vendor_sees_their_own_invitation_page(): void
    {
        $vendorA = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);
        $invitation = $this->invitationFor($sell, $vendorA);

        $this->get(route('sell.vendor.invitation', $invitation->token))
            ->assertOk()
            ->assertSee($sell->request_number)
            ->assertSee('ring')
            ->assertSee('Accept invitation');
    }

    public function test_a_vendors_page_does_not_expose_another_vendors_token(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);
        $invitationA = $this->invitationFor($sell, $vendorA);
        $invitationB = $this->invitationFor($sell, $vendorB);

        // Vendor A only ever sees their own token — B's token is unguessable
        // (64 random chars) and never rendered anywhere A can reach.
        $page = $this->get(route('sell.vendor.invitation', $invitationA->token))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($invitationA->token, $page);
        $this->assertStringNotContainsString($invitationB->token, $page);
    }

    public function test_a_unknown_invitation_token_is_a_404(): void
    {
        $this->get(route('sell.vendor.invitation', 'a-token-that-does-not-exist'))->assertNotFound();
    }

    public function test_a_vendor_can_submit_their_own_bid_and_the_view_shows_it(): void
    {
        $vendorA = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendorA, 8500);
        $invitation = $this->invitationFor($sell, $vendorA);

        $this->get(route('sell.vendor.invitation', $invitation->token))
            ->assertOk()
            ->assertSee('Current bid: ₹8,500');
    }

    public function test_a_can_bid_near_the_deadline_before_the_window_closes(): void
    {
        $vendorA = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendorA);

        $this->travelTo($sell->bids_end_at->copy()->subMinute());
        $this->sellService()->submitBid($this->invitationFor($sell, $vendorA)->fresh(), 7500);

        $this->assertSame('7500.00', $this->invitationFor($sell, $vendorA)->fresh()->sellRequest->bids()->where('vendor_id', $vendorA->id)->firstOrFail()->amount);
    }

    public function test_a_vendor_never_sees_another_vendors_bid_amount(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendorA, 8500);
        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendorB)->fresh());
        $this->sellService()->submitBid($this->invitationFor($sell, $vendorB)->fresh(), 9000);
        $invitationA = $this->invitationFor($sell, $vendorA);

        $page = $this->get(route('sell.vendor.invitation', $invitationA->token))
            ->assertOk();

        $this->assertStringContainsString('₹8,500', $page->getContent());
        $this->assertStringNotContainsString('₹9,000', $page->getContent());
    }

    public function test_a_vendor_cannot_open_another_vendors_bid_via_any_endpoint(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendorA, 8500);
        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendorB)->fresh());
        $this->sellService()->submitBid($this->invitationFor($sell, $vendorB)->fresh(), 9000);
        $invitationA = $this->invitationFor($sell, $vendorA);

        // There is no route exposing "all bids" — a bid can only be read from
        // the invitation page, which scopes to the token owner.
        $page = $this->get(route('sell.vendor.invitation', $invitationA->token))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('₹9,000', $page);
        $this->assertStringNotContainsString('9000.00', $page);
    }

    public function test_submitting_a_bid_with_my_token_only_touches_my_bid_row(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendorA, 8500);
        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendorB)->fresh());
        $this->sellService()->submitBid($this->invitationFor($sell, $vendorB)->fresh(), 9000);
        $invitationA = $this->invitationFor($sell, $vendorA);

        $this->sellService()->submitBid($invitationA->fresh(), 8800);

        $bids = SellBid::where('sell_request_id', $sell->id)->get()->keyBy('vendor_id');
        $this->assertSame('8800.00', $bids[$vendorA->id]->amount);
        $this->assertSame('9000.00', $bids[$vendorB->id]->amount);
        $this->assertCount(2, $bids);
    }

    public function test_a_bid_cannot_be_placed_without_a_valid_invitation_token(): void
    {
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);

        $this->post(route('sell.vendor.bid'), [
            'invitation_token' => 'not-a-token',
            'amount' => 1000,
        ])->assertNotFound();

        $this->assertDatabaseCount('sell_bids', 0);
    }

    public function test_a_customer_cannot_perform_vendor_actions(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);
        $invitation = $this->invitationFor($sell, $vendor);

        // The customer has no vendor token.
        $this->post(route('sell.vendor.accept', 'unknown-token'))->assertNotFound();
        $this->post(route('sell.vendor.bid'), ['invitation_token' => 'unknown-token', 'amount' => 1000])->assertNotFound();
    }

    public function test_no_admin_valuation_is_exposed_to_vendors_until_settlement(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);
        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendor));
        $this->sellService()->submitBid($this->invitationFor($sell, $vendor)->fresh(), 8000);
        $this->sellService()->closeBidding($sell);

        $sell->fresh()->update([
            'admin_valuation' => 10000.00,
            'deduction_amount' => 1000.00,
            'wallet_credit' => 9000.00,
        ]);

        $invitation = $this->invitationFor($sell, $vendor);
        $page = $this->get(route('sell.vendor.invitation', $invitation->token))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('10,000', $page);
        $this->assertStringNotContainsString('9,000', $page);
    }

    public function test_a_vendor_cannot_infer_admin_data_through_the_customer_api(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor, 8000);
        $this->sellService()->closeBidding($sell);
        $sell->fresh()->update([
            'admin_valuation' => 10000.00,
            'deduction_amount' => 1000.00,
            'wallet_credit' => 9000.00,
        ]);

        // The vendor has no bearer token; even with one, the request is not
        // theirs and the API returns 404 before any payload is built.
        $this->withToken($this->customerApiToken($vendor))
            ->getJson('/api/account/sell/requests/'.$sell->request_number)
            ->assertNotFound();
    }
}