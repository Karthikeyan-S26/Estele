<?php

namespace Tests\Feature;

use App\Models\SellAuditLog;
use App\Models\SellInvitation;
use App\Models\SellRequest;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SellTestHelpers;
use Tests\TestCase;

/**
 * Vendor invitation lifecycle for old-jewellery buy-back requests: every
 * vendor-role user is invited on request creation, invitations are
 * idempotent / scoped, and accept/decline transitions are guarded.
 */
class VerifySellVendorInvitationTest extends TestCase
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

    public function test_every_vendor_role_user_is_invited_when_a_request_is_created(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $customer = $this->makeCustomer();

        $sell = $this->createSellRequest($customer);

        $this->assertSame(2, $sell->invitations()->count());
        $this->assertSame([$vendorA->id, $vendorB->id], $sell->invitations()->pluck('vendor_id')->sort()->values()->all());
        $this->assertDatabaseCount('sell_invitations', 2);
    }

    public function test_invitations_belong_to_the_correct_sell_request(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();

        $first = $this->createSellRequest($customer, ['item_type' => 'ring']);
        $second = $this->createSellRequest($customer, ['item_type' => 'bracelet']);

        $invitations = SellInvitation::where('vendor_id', $vendor->id)->get();
        $this->assertCount(2, $invitations);
        $this->assertTrue($invitations->contains('sell_request_id', $first->id));
        $this->assertTrue($invitations->contains('sell_request_id', $second->id));
    }

    public function test_a_vendor_only_receives_their_own_invitation(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $customer = $this->makeCustomer();

        $sell = $this->createSellRequest($customer);

        $invitationA = $this->invitationFor($sell, $vendorA);
        $invitationB = $this->invitationFor($sell, $vendorB);

        $this->assertNotSame($invitationA->id, $invitationB->id);
        $this->assertNotSame($invitationA->token, $invitationB->token);
        $this->assertSame($vendorA->id, $invitationA->vendor_id);
        $this->assertSame($vendorB->id, $invitationB->vendor_id);
    }

    public function test_reinviting_does_not_create_duplicate_invitations(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $customer = $this->makeCustomer();

        $sell = $this->createSellRequest($customer);
        $this->assertSame(2, $sell->invitations()->count());

        $this->sellService()->inviteAllVendors($sell);

        $this->assertSame(2, $sell->invitations()->count());
        $this->assertSame(1, SellInvitation::where('vendor_id', $vendorA->id)->where('sell_request_id', $sell->id)->count());
        $this->assertSame(1, SellInvitation::where('vendor_id', $vendorB->id)->where('sell_request_id', $sell->id)->count());
    }

    public function test_a_non_vendor_user_is_never_invited(): void
    {
        $vendor = $this->makeVendor();
        $plainUser = User::factory()->create(); // no vendor role
        $customer = $this->makeCustomer();

        $sell = $this->createSellRequest($customer);

        $this->assertDatabaseHas('sell_invitations', [
            'sell_request_id' => $sell->id,
            'vendor_id' => $vendor->id,
        ]);
        $this->assertDatabaseMissing('sell_invitations', [
            'sell_request_id' => $sell->id,
            'vendor_id' => $plainUser->id,
        ]);
    }

    public function test_a_customer_cannot_invite_vendors(): void
    {
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);

        // There is no customer-facing invite endpoint — the matching API and
        // vendor routes simply 404 for an unknown invitation token.
        $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests/'.$sell->request_number.'/invite')
            ->assertNotFound();

        $this->get('/sell/vendors/invitations/not-a-real-token')->assertNotFound();
    }

    public function test_duplicate_accept_call_is_a_no_op(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);
        $invitation = $this->invitationFor($sell, $vendor);

        $this->assertTrue($this->sellService()->acceptInvitation($invitation));
        $this->assertSame(SellInvitation::STATUS_ACCEPTED, $invitation->fresh()->status);

        $this->assertFalse($this->sellService()->acceptInvitation($invitation->fresh()));
        $this->assertSame(SellInvitation::STATUS_ACCEPTED, $invitation->fresh()->status);
        $this->assertSame(1, SellAuditLog::where('event', 'vendor_accepted')->count());
    }

    public function test_accepting_an_already_declined_invitation_does_nothing(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);
        $invitation = $this->invitationFor($sell, $vendor);

        $this->sellService()->declineInvitation($invitation);
        $this->assertFalse($this->sellService()->acceptInvitation($invitation->fresh()));
        $this->assertSame(SellInvitation::STATUS_DECLINED, $invitation->fresh()->status);
    }

    public function test_decline_transitions_the_invitation_and_writes_an_audit_entry(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);
        $invitation = $this->invitationFor($sell, $vendor);

        $this->assertTrue($this->sellService()->declineInvitation($invitation));
        $this->assertSame(SellInvitation::STATUS_DECLINED, $invitation->fresh()->status);
        $this->assertNotNull($invitation->fresh()->responded_at);
        $this->assertDatabaseHas('sell_audit_logs', [
            'sell_request_id' => $sell->id,
            'event' => 'vendor_declined',
        ]);
        $this->assertSame(SellRequest::STATUS_PENDING_BIDS, $sell->fresh()->status);
    }

    public function test_first_acceptance_opens_the_bidding_window(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);

        $this->assertSame(SellRequest::STATUS_PENDING_BIDS, $sell->status);
        $this->assertFalse($sell->isBiddingOpen());

        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendorA));

        $this->assertSame(SellRequest::STATUS_BIDDING, $sell->fresh()->status);
        $this->assertTrue($sell->fresh()->isBiddingOpen());
        $this->assertDatabaseHas('sell_audit_logs', [
            'sell_request_id' => $sell->id,
            'event' => 'bidding_opened',
            'from_status' => SellRequest::STATUS_PENDING_BIDS,
            'to_status' => SellRequest::STATUS_BIDDING,
        ]);
    }

    public function test_acceptances_after_opening_do_not_retransition_the_request(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);

        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendorA));
        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendorB));

        $this->assertSame(SellRequest::STATUS_BIDDING, $sell->fresh()->status);
        $this->assertSame(1, SellAuditLog::where('event', 'bidding_opened')->count());
    }

    public function test_each_invitation_acceptance_is_audited_with_the_vendor_as_actor(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);
        $invitation = $this->invitationFor($sell, $vendor);

        $this->sellService()->acceptInvitation($invitation);

        $this->assertDatabaseHas('sell_audit_logs', [
            'sell_request_id' => $sell->id,
            'actor_type' => 'App\\Models\\User',
            'actor_id' => $vendor->id,
            'event' => 'vendor_accepted',
        ]);
        $audit = SellAuditLog::where('event', 'vendor_accepted')->firstOrFail();
        $this->assertSame($vendor->id, $audit->metadata['vendor_id']);
    }
}