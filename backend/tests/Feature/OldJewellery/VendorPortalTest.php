<?php

namespace Tests\Feature\OldJewellery;

use App\Models\OldJewelleryBid;
use App\Models\OldJewelleryRequest;
use App\Models\OldJewelleryVendorInvitation;
use App\Models\OldJewelleryWalletCredit;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * The vendor's own portal at /vendor — a session login entirely separate
 * from the Filament admin panel (see VendorPanelAuthorizationTest for the
 * "never reaches /admin" boundary). Covers login/logout (including sharing
 * the guard slot with a signed-in customer — same trick the admin login
 * uses, see App\Support\PanelSessionParking), the dashboard and bid-history
 * scoping, placing/declining a bid through the portal, and the profile page.
 */
class VendorPortalTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendorWithLogin(string $password = 'vendor-password'): array
    {
        $user = User::factory()->create(['password' => Hash::make($password)]);
        $vendor = Vendor::create([
            'name' => 'Portal Vendor',
            'company_name' => 'Portal Co',
            'mobile' => '9'.random_int(100000000, 999999999),
            'email' => $user->email,
            'is_active' => true,
            'access_role' => Vendor::ACCESS_ROLE_VENDOR,
            'user_id' => $user->id,
        ]);
        $user->assignRole('vendor');

        return [$user, $vendor];
    }

    public function test_a_vendor_can_log_in_and_see_the_dashboard(): void
    {
        [$user] = $this->makeVendorWithLogin('secret-pass');

        $this->post(route('vendor.login.store'), ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertRedirect(route('vendor.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('vendor.dashboard'))->assertOk()->assertSee('Portal Vendor');
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        [$user] = $this->makeVendorWithLogin('secret-pass');

        $this->post(route('vendor.login.store'), ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_a_non_vendor_account_cannot_log_in_here_even_with_the_right_password(): void
    {
        $customer = User::factory()->create(['password' => Hash::make('secret-pass')]);

        $this->post(route('vendor.login.store'), ['email' => $customer->email, 'password' => 'secret-pass'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_visiting_login_while_already_a_vendor_redirects_straight_to_the_dashboard(): void
    {
        [$user] = $this->makeVendorWithLogin();

        $this->actingAs($user)
            ->get(route('vendor.login'))
            ->assertRedirect(route('vendor.dashboard'));
    }

    public function test_opening_vendor_login_parks_a_signed_in_otp_only_customer_without_a_redirect_loop(): void
    {
        $customer = User::factory()->create(['password' => null]);
        $this->actingAs($customer);

        $response = $this->get(route('vendor.login'));

        $response->assertOk();
        $this->assertGuest();
        $this->assertSame($customer->id, session('parked_customer_id'));
    }

    public function test_logout_restores_the_parked_customer(): void
    {
        [$user] = $this->makeVendorWithLogin('secret-pass');
        $customer = User::factory()->create(['password' => null]);

        $this->actingAs($customer);
        $this->get(route('vendor.login')); // parks the customer

        $this->post(route('vendor.login.store'), ['email' => $user->email, 'password' => 'secret-pass']);
        $this->assertAuthenticatedAs($user);

        $this->post(route('vendor.logout'));

        $this->assertAuthenticatedAs($customer->fresh());
        $this->assertNull(session('parked_customer_id'));
    }

    public function test_dashboard_lists_only_this_vendors_open_invitations(): void
    {
        [$user, $vendor] = $this->makeVendorWithLogin();
        $otherVendor = Vendor::create(['name' => 'Other', 'mobile' => '9111111199', 'is_active' => true]);

        $mine = $this->makeRequestInvitedTo($vendor, 'OJ-MINE-1');
        $theirs = $this->makeRequestInvitedTo($otherVendor, 'OJ-THEIRS-1');

        $response = $this->actingAs($user)->get(route('vendor.dashboard'));

        $response->assertOk()
            ->assertSee($mine->request_number)
            ->assertDontSee($theirs->request_number);
    }

    public function test_vendor_can_submit_a_bid_through_the_portal(): void
    {
        [$user, $vendor] = $this->makeVendorWithLogin();
        $request = $this->makeRequestInvitedTo($vendor, 'OJ-BID-1');

        $this->actingAs($user)
            ->post(route('vendor.requests.bid', $request), ['amount' => 15000])
            ->assertRedirect(route('vendor.requests.show', $request));

        $this->assertDatabaseHas('old_jewellery_bids', [
            'old_jewellery_request_id' => $request->id,
            'vendor_id' => $vendor->id,
            'bidder_type' => 'vendor',
            'amount' => 15000,
        ]);
    }

    public function test_vendor_can_decline_through_the_portal(): void
    {
        [$user, $vendor] = $this->makeVendorWithLogin();
        $request = $this->makeRequestInvitedTo($vendor, 'OJ-DECLINE-1');

        $this->actingAs($user)
            ->post(route('vendor.requests.decline', $request), ['reason' => 'Not interested'])
            ->assertRedirect(route('vendor.requests.show', $request));

        $this->assertSame('declined', OldJewelleryVendorInvitation::where('vendor_id', $vendor->id)->first()->response_status);
    }

    public function test_bids_page_shows_won_status_and_the_customers_settlement(): void
    {
        [$user, $vendor] = $this->makeVendorWithLogin();
        $customer = User::factory()->create();

        $request = OldJewelleryRequest::create([
            'user_id' => $customer->id,
            'request_number' => 'OJ-WON-1',
            'status' => 'wallet_credited',
            'bidding_start_at' => now()->subDay(),
            'bidding_end_at' => now()->subHours(20),
            'closed_at' => now()->subHours(19),
            'final_amount' => 20000,
        ]);

        $bid = OldJewelleryBid::create([
            'old_jewellery_request_id' => $request->id,
            'bidder_type' => 'vendor',
            'vendor_id' => $vendor->id,
            'amount' => 20000,
            'submitted_at' => now()->subDay(),
        ]);
        $request->update(['winning_bid_id' => $bid->id]);

        $transaction = WalletTransaction::create([
            'user_id' => $customer->id,
            'type' => 'credit',
            'amount' => 18000,
            'balance_after' => 18000,
            'reason' => 'old_jewellery_wallet_credit',
        ]);
        OldJewelleryWalletCredit::create([
            'user_id' => $customer->id,
            'old_jewellery_request_id' => $request->id,
            'wallet_transaction_id' => $transaction->id,
            'gross_amount' => 20000,
            'deduction_amount' => 2000,
            'credited_amount' => 18000,
            'remaining_amount' => 18000,
            'credited_at' => now()->subHours(19),
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('vendor.bids'));

        $response->assertOk()
            ->assertSee('OJ-WON-1')
            ->assertSee('Won')
            ->assertSee('18,000')
            ->assertSee('Active');
    }

    public function test_profile_shows_vendor_details_and_updates_whatsapp_number(): void
    {
        [$user, $vendor] = $this->makeVendorWithLogin();

        $this->actingAs($user)->get(route('vendor.profile'))->assertOk()->assertSee($vendor->name);

        $this->actingAs($user)
            ->post(route('vendor.profile.contact'), ['whatsapp_number' => '9123456780'])
            ->assertRedirect();

        $this->assertSame('9123456780', $vendor->fresh()->whatsapp_number);
    }

    public function test_profile_password_change_requires_the_correct_current_password(): void
    {
        [$user] = $this->makeVendorWithLogin('old-password');

        $this->actingAs($user)
            ->post(route('vendor.profile.password'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_profile_password_change_succeeds_with_the_correct_current_password(): void
    {
        [$user] = $this->makeVendorWithLogin('old-password');

        $this->actingAs($user)
            ->post(route('vendor.profile.password'), [
                'current_password' => 'old-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_setting_a_password_from_a_vendor_invite_sends_them_to_the_vendor_login(): void
    {
        [$user] = $this->makeVendorWithLogin();
        $user->forceFill(['password' => null])->save();

        $token = Password::broker('panel_invites')->createToken($user);

        $this->post(route('panel.password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect(route('vendor.login'));
    }

    public function test_setting_a_password_from_a_staff_invite_sends_them_to_the_admin_login(): void
    {
        $staff = User::factory()->create(['password' => null]);
        $staff->assignRole('marketing');

        $token = Password::broker('panel_invites')->createToken($staff);

        $this->post(route('panel.password.store'), [
            'token' => $token,
            'email' => $staff->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect('/admin/login');
    }

    private function makeRequestInvitedTo(Vendor $vendor, string $requestNumber): OldJewelleryRequest
    {
        $customer = User::factory()->create();

        $request = OldJewelleryRequest::create([
            'user_id' => $customer->id,
            'request_number' => $requestNumber,
            'status' => 'bidding_active',
            'bidding_start_at' => now(),
            'bidding_end_at' => now()->addHours(3),
        ]);

        OldJewelleryVendorInvitation::create([
            'old_jewellery_request_id' => $request->id,
            'vendor_id' => $vendor->id,
            'token_hash' => hash('sha256', 'token-'.uniqid()),
            'expires_at' => $request->bidding_end_at,
        ]);

        return $request;
    }
}
