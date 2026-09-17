<?php

namespace Tests\Feature\OldJewellery;

use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentVendorOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_mobile_action_issues_a_code_as_soon_as_it_opens(): void
    {
        $this->actingAsSuperAdmin();
        $vendor = Vendor::create(['name' => 'Acme Gold', 'mobile' => '9111111111', 'is_active' => true]);

        // A single "Verify mobile" click sends the code and opens straight
        // on the code field — mounting the action is enough, no separate
        // "send" action to call first.
        Livewire::test(EditVendor::class, ['record' => $vendor->id])
            ->mountAction('verify_otp');

        $this->assertDatabaseHas('otp_codes', ['phone' => '9111111111']);
    }

    public function test_resend_otp_action_issues_another_code(): void
    {
        $this->actingAsSuperAdmin();
        $vendor = Vendor::create(['name' => 'Acme Gold', 'mobile' => '9111111111', 'is_active' => true]);

        Livewire::test(EditVendor::class, ['record' => $vendor->id])
            ->callAction(['verify_otp', 'resend_otp'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('otp_codes', ['phone' => '9111111111']);
    }

    public function test_verify_otp_action_marks_mobile_verified_on_correct_code(): void
    {
        $this->actingAsSuperAdmin();
        $vendor = Vendor::create(['name' => 'Acme Gold', 'mobile' => '9111111111', 'is_active' => true]);

        // mountUsing() issues a fresh (randomly-coded) OTP the instant the
        // modal opens, so — rather than pre-seeding a known code that
        // mounting would immediately consume anyway — mount once, overwrite
        // the hash of the row it just created with a known code, then submit
        // the already-mounted action directly (callAction() would re-mount
        // and re-issue a new OTP, consuming this known code first).
        $component = Livewire::test(EditVendor::class, ['record' => $vendor->id])
            ->mountAction('verify_otp');

        OtpCode::where('phone', '9111111111')->whereNull('consumed_at')
            ->latest('id')->first()
            ->update(['code_hash' => Hash::make('123456')]);

        $component->fillForm(['code' => '654321'])
            ->callMountedAction()
            ->assertHasNoActionErrors();
        $this->assertNull($vendor->fresh()->mobile_verified_at);

        $component->fillForm(['code' => '123456'])
            ->callMountedAction()
            ->assertHasNoActionErrors();
        $this->assertNotNull($vendor->fresh()->mobile_verified_at);
    }

    public function test_changing_mobile_resets_verification(): void
    {
        $this->actingAsSuperAdmin();
        $vendor = Vendor::create(['name' => 'Acme Gold', 'mobile' => '9111111111', 'is_active' => true, 'mobile_verified_at' => now()]);

        Livewire::test(EditVendor::class, ['record' => $vendor->id])
            ->fillForm(['mobile' => '9999999999'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($vendor->fresh()->mobile_verified_at);
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
