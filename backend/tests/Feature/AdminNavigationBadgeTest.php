<?php

namespace Tests\Feature;

use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Banner;
use App\Models\OldJewelleryBid;
use App\Models\OldJewelleryRequest;
use App\Models\User;
use App\Models\Vendor;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNavigationBadgeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_vendor_badge_counts_new_vendors_and_vendor_bids_after_first_look(): void
    {
        $this->assertNull(VendorResource::getNavigationBadge());

        Carbon::setTestNow(now()->addMinute());

        $vendor = Vendor::create(['name' => 'V', 'mobile' => '9000000001', 'is_active' => true]);
        $request = OldJewelleryRequest::create([
            'user_id' => User::factory()->create()->id,
            'request_number' => 'OJ-TEST-1',
            'status' => 'pending',
        ]);
        OldJewelleryBid::create([
            'old_jewellery_request_id' => $request->id,
            'bidder_type' => 'vendor',
            'vendor_id' => $vendor->id,
            'amount' => 1000,
            'is_valid' => true,
            'submitted_at' => now(),
        ]);

        $this->assertSame('2', VendorResource::getNavigationBadge());
    }

    public function test_opening_the_list_page_clears_the_badge(): void
    {
        $this->assertNull(BannerResource::getNavigationBadge());

        Carbon::setTestNow(now()->addMinute());
        Banner::forceCreate(['title' => 'New', 'is_active' => true]);

        $this->assertSame('1', BannerResource::getNavigationBadge());

        Carbon::setTestNow(now()->addMinute());
        $this->get(BannerResource::getUrl('index'))->assertOk();

        $this->assertNull(BannerResource::getNavigationBadge());
    }

    public function test_panel_pages_include_the_live_badge_poller(): void
    {
        $this->get(BannerResource::getUrl('index'))
            ->assertOk()
            ->assertSee("Livewire.dispatch('refresh-sidebar')", false);
    }
}
