<?php

namespace Tests\Feature;

use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\StaffAccessInvitation;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VerifyStaffResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_list_shows_panel_users_but_not_vendor_contacts_or_customers(): void
    {
        $this->actingAsSuperAdmin();

        $staff = User::factory()->create(['name' => 'Priya Staff']);
        $staff->assignRole('marketing');

        $vendorUser = User::factory()->create(['name' => 'Vendor Login']);
        $vendorUser->assignRole('vendor');
        Vendor::create(['name' => 'Acme', 'mobile' => '9111111111', 'is_active' => true, 'user_id' => $vendorUser->id, 'access_role' => 'vendor']);

        User::factory()->create(['name' => 'Plain Customer']);

        $this->get('/admin/staff')
            ->assertOk()
            ->assertSee('Priya Staff')
            ->assertDontSee('Vendor Login')
            ->assertDontSee('Plain Customer');
    }

    public function test_staff_list_shows_a_person_who_is_both_staff_and_a_vendor_contact(): void
    {
        $this->actingAsSuperAdmin();

        $both = User::factory()->create(['name' => 'Dual Role']);
        $both->assignRole(['marketing', 'vendor']);
        Vendor::create(['name' => 'Acme', 'mobile' => '9222222222', 'is_active' => true, 'user_id' => $both->id, 'access_role' => 'vendor']);

        // Gaining a Vendor row must not make an existing staff member vanish
        // from this list — that silent disappearance (fixed here) is what
        // made admins think the vendor screen had "turned off" their staff
        // access.
        $this->get('/admin/staff')->assertOk()->assertSee('Dual Role');
    }

    public function test_change_role_and_remove_access_never_touch_a_linked_vendor_role(): void
    {
        $this->actingAsSuperAdmin();
        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        $both = User::factory()->create();
        $both->assignRole(['marketing', 'vendor']);
        Vendor::create(['name' => 'Acme', 'mobile' => '9333333333', 'is_active' => true, 'user_id' => $both->id, 'access_role' => 'vendor']);

        Livewire::test(ListStaff::class)
            ->callTableAction('change_role', $both, ['role' => 'editor'])
            ->assertHasNoTableActionErrors();
        $this->assertEqualsCanonicalizing(['editor', 'vendor'], $both->fresh()->roles->pluck('name')->all());

        Livewire::test(ListStaff::class)
            ->callTableAction('remove_access', $both)
            ->assertHasNoTableActionErrors();
        $fresh = $both->fresh();
        // The staff role is gone but the vendor role — and the password
        // that vendor login needs — must survive; only the Vendors screen
        // is allowed to take that away.
        $this->assertSame(['vendor'], $fresh->roles->pluck('name')->all());
        $this->assertNotNull($fresh->password);
    }

    public function test_add_staff_invites_with_role_and_no_password(): void
    {
        Notification::fake();
        $this->actingAsSuperAdmin();

        Livewire::test(ListStaff::class)
            ->callAction('add_staff', ['name' => 'New Person', 'email' => 'new@example.com', 'role' => 'marketing'])
            ->assertHasNoActionErrors();

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertNull($user->password);
        $this->assertTrue($user->hasRole('marketing'));
        Notification::assertSentTo($user, StaffAccessInvitation::class);
    }

    public function test_change_role_and_remove_access(): void
    {
        $this->actingAsSuperAdmin();
        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        $staff = User::factory()->create();
        $staff->assignRole('marketing');

        Livewire::test(ListStaff::class)
            ->callTableAction('change_role', $staff, ['role' => 'editor'])
            ->assertHasNoTableActionErrors();
        $this->assertSame(['editor'], $staff->fresh()->roles->pluck('name')->all());

        Livewire::test(ListStaff::class)
            ->callTableAction('remove_access', $staff)
            ->assertHasNoTableActionErrors();
        $fresh = $staff->fresh();
        $this->assertSame([], $fresh->roles->pluck('name')->all());
        $this->assertNull($fresh->password);
    }

    public function test_user_without_staff_permission_is_forbidden(): void
    {
        $this->seed(ShieldSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('marketing');

        $this->actingAs($user)->get('/admin/staff')->assertForbidden();
        $this->actingAs($user)->get('/admin/shield/roles')->assertForbidden();
    }

    private function actingAsSuperAdmin(): User
    {
        $this->seed(ShieldSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        return $admin;
    }
}
