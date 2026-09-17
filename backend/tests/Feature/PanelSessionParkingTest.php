<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Covers the "one browser, two sessions" flow: a customer logged in on the
 * storefront opens /admin/login, signs in as an admin, then signs out of
 * the admin panel — the customer should land back exactly where they were,
 * with no separate login step. See App\Support\AdminSessionParking for why
 * this needs its own park/restore trick rather than a second auth guard.
 */
class AdminSessionParkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_admin_login_while_a_customer_is_signed_in_parks_them_without_a_redirect_loop(): void
    {
        $this->seed(ShieldSeeder::class);
        $customer = User::factory()->create(['password' => null]);
        $admin = User::factory()->create(['password' => Hash::make('secret-password')]);
        $admin->assignRole('super_admin');

        $this->actingAs($customer);

        $response = $this->get('/admin/login');

        // The customer's own guard slot is now empty (parked, not merely
        // logged out) — but the login FORM must render, not a redirect
        // straight into the panel (Filament's base mount() redirects
        // whenever Auth::check() is already true, which would happen here
        // if parking didn't sign the customer out first).
        $response->assertOk();
        $this->assertGuest();
        $this->assertSame($customer->id, session('parked_customer_id'));
    }

    public function test_admin_logout_restores_the_parked_customer_session(): void
    {
        $this->seed(ShieldSeeder::class);
        $customer = User::factory()->create(['password' => null]);
        $admin = User::factory()->create(['password' => Hash::make('secret-password')]);
        $admin->assignRole('super_admin');

        $this->actingAs($customer);
        $this->get('/admin/login'); // parks $customer, logs the guard out

        $this->actingAs($admin);
        $this->assertAuthenticatedAs($admin);

        $this->post('/admin/logout');

        $this->assertAuthenticatedAs($customer->fresh());
        $this->assertNull(session('parked_customer_id'));
    }

    public function test_admin_logout_with_no_parked_customer_simply_signs_out(): void
    {
        $this->seed(ShieldSeeder::class);
        $admin = User::factory()->create(['password' => Hash::make('secret-password')]);
        $admin->assignRole('super_admin');

        $this->actingAs($admin);
        $this->post('/admin/logout');

        $this->assertGuest();
    }

    public function test_admin_login_page_does_not_park_another_admin(): void
    {
        $this->seed(ShieldSeeder::class);
        $firstAdmin = User::factory()->create(['password' => Hash::make('secret-password')]);
        $firstAdmin->assignRole('super_admin');

        $this->actingAs($firstAdmin);

        // Already-authenticated admin visiting /admin/login is bounced
        // straight into the panel by Filament's own mount() — nothing here
        // should have been parked, since $firstAdmin has a password (not an
        // OTP-only customer).
        $this->get('/admin/login');

        $this->assertAuthenticatedAs($firstAdmin);
        $this->assertNull(session('parked_customer_id'));
    }
}
