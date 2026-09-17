<?php

namespace Tests\Feature\OldJewellery;

use App\Models\OldJewelleryBid;
use App\Models\OldJewelleryRequest;
use App\Models\OldJewelleryVendorInvitation;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A vendor contact never reaches the Filament admin panel at all — see
 * User::canAccessPanel() — its whole world is the /vendor portal
 * (App\Http\Controllers\Vendor\*), scoped to exactly the requests it was
 * invited to. This file covers both boundaries: /admin staying shut, and
 * the portal never leaking another vendor's bid, the admin's bid, or a
 * customer's contact details.
 */
class VendorPanelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsVendor(): array
    {
        $this->seed(ShieldSeeder::class);

        $vendor = Vendor::create([
            'name' => 'Invited Vendor',
            'mobile' => '9000000090',
            'is_active' => true,
            'access_role' => Vendor::ACCESS_ROLE_VENDOR,
        ]);
        $user = User::factory()->create();
        $vendor->update(['user_id' => $user->id]);
        $user->assignRole('vendor');
        $this->actingAs($user);

        return [$user, $vendor];
    }

    private function makeInvitedRequest(Vendor $vendor, string $status = 'bidding_active'): OldJewelleryRequest
    {
        $customer = User::factory()->create();
        $otherVendor = Vendor::create(['name' => 'Other Vendor', 'mobile' => '9000000091', 'is_active' => true]);

        $request = OldJewelleryRequest::create([
            'user_id' => $customer->id,
            'request_number' => 'OJ-AUTHZ-1',
            'status' => $status,
            'bidding_start_at' => now(),
            'bidding_end_at' => now()->addHours(3),
        ]);

        OldJewelleryVendorInvitation::create([
            'old_jewellery_request_id' => $request->id,
            'vendor_id' => $vendor->id,
            'token_hash' => hash('sha256', 'mine'),
            'expires_at' => $request->bidding_end_at,
        ]);
        OldJewelleryVendorInvitation::create([
            'old_jewellery_request_id' => $request->id,
            'vendor_id' => $otherVendor->id,
            'token_hash' => hash('sha256', 'theirs'),
            'expires_at' => $request->bidding_end_at,
        ]);
        OldJewelleryBid::create([
            'old_jewellery_request_id' => $request->id,
            'bidder_type' => 'vendor',
            'vendor_id' => $otherVendor->id,
            'amount' => 5000,
            'submitted_at' => now(),
        ]);
        OldJewelleryBid::create([
            'old_jewellery_request_id' => $request->id,
            'bidder_type' => 'admin',
            'admin_user_id' => User::factory()->create()->id,
            'amount' => 9999,
            'submitted_at' => now(),
        ]);

        return $request;
    }

    public function test_a_vendor_login_cannot_reach_the_admin_panel_at_all(): void
    {
        [, $vendor] = $this->actingAsVendor();
        $request = $this->makeInvitedRequest($vendor);

        $this->get('/admin')->assertForbidden();
        $this->get('/admin/old-jewellery-requests')->assertForbidden();
        $this->get("/admin/old-jewellery-requests/{$request->request_number}")->assertForbidden();
    }

    public function test_a_vendor_portal_page_does_not_see_other_bids_admin_bid_or_customer_contact_info(): void
    {
        [, $vendor] = $this->actingAsVendor();
        $request = $this->makeInvitedRequest($vendor);
        $request->load('user');

        $response = $this->get(route('vendor.requests.show', $request));

        $response->assertOk()
            ->assertDontSee('Other Vendor')
            ->assertDontSee('9,999')   // admin bid
            ->assertDontSee('5,000')   // other vendor's bid
            ->assertDontSee($request->user->phone)
            ->assertDontSee($request->user->email ?? 'no-such-marker-xyz');
    }

    public function test_a_vendor_cannot_open_a_request_it_was_never_invited_to(): void
    {
        [, $vendor] = $this->actingAsVendor();

        $customer = User::factory()->create();
        $request = OldJewelleryRequest::create([
            'user_id' => $customer->id,
            'request_number' => 'OJ-AUTHZ-2',
            'status' => 'bidding_active',
            'bidding_start_at' => now(),
            'bidding_end_at' => now()->addHours(3),
        ]);
        // Note: this vendor was never invited to OJ-AUTHZ-2.

        $this->get(route('vendor.requests.show', $request))->assertNotFound();

        $this->post(route('vendor.requests.bid', $request), ['amount' => 1000])->assertNotFound();
        $this->assertDatabaseMissing('old_jewellery_bids', ['old_jewellery_request_id' => $request->id, 'vendor_id' => $vendor->id]);
    }

    public function test_the_vendor_portal_media_route_refuses_a_vendor_not_invited_to_the_request(): void
    {
        [, $vendor] = $this->actingAsVendor();

        $customer = User::factory()->create();
        $request = OldJewelleryRequest::create([
            'user_id' => $customer->id,
            'request_number' => 'OJ-AUTHZ-3',
            'status' => 'bidding_active',
            'bidding_start_at' => now(),
            'bidding_end_at' => now()->addHours(3),
        ]);

        $this->get(route('vendor.requests.video', $request))->assertForbidden();
        $this->get(route('vendor.requests.image', $request))->assertForbidden();
    }

    public function test_a_signed_in_customer_visiting_the_portal_is_sent_to_the_vendor_login(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('vendor.dashboard'))
            ->assertRedirect(route('vendor.login'));
    }

    public function test_a_super_admin_still_sees_full_detail_on_the_admin_side(): void
    {
        $admin = User::factory()->create();
        $this->seed(ShieldSeeder::class);
        $admin->assignRole('super_admin');

        $vendor = Vendor::create(['name' => 'Some Vendor', 'mobile' => '9000000092', 'is_active' => true]);
        $request = $this->makeInvitedRequest($vendor);

        $this->actingAs($admin)
            ->get("/admin/old-jewellery-requests/{$request->request_number}")
            ->assertOk()
            ->assertSee('Other Vendor')
            ->assertSee('₹9,999.00');
    }
}
