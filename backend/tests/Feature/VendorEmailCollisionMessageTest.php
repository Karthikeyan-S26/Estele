<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Services\Vendors\PanelAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Locks in that a blocked vendor-email save names WHAT it collided with
 * (staff login / another vendor / customer) instead of the old generic
 * "a different account" — the vague version left admins guessing whether
 * the block was even legitimate.
 */
class VendorEmailCollisionMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_names_a_staff_login_collision(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole($this->role('marketing'));

        $this->assertSame(
            "That email already belongs to a staff member's login. Use a different address for this vendor.",
            PanelAccessService::collisionMessage($staff)
        );
    }

    public function test_names_a_vendor_login_collision(): void
    {
        $vendorUser = User::factory()->create();
        Vendor::create(['name' => 'Acme', 'mobile' => '9444444444', 'is_active' => true, 'user_id' => $vendorUser->id, 'access_role' => 'vendor']);

        $this->assertSame(
            "That email already belongs to another vendor's login. Use a different address for this vendor.",
            PanelAccessService::collisionMessage($vendorUser)
        );
    }

    public function test_names_a_plain_customer_collision(): void
    {
        $customer = User::factory()->create();

        $this->assertSame(
            'That email already belongs to a customer account. Use a different address for this vendor.',
            PanelAccessService::collisionMessage($customer)
        );
    }

    private function role(string $name): string
    {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        return $name;
    }
}
