<?php

namespace Tests\Feature;

use App\Filament\Resources\RewardSubmissions\RewardSubmissionResource;
use App\Models\Order;
use App\Models\RewardSubmission;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyRewardSubmissionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_can_view_and_update_reward_submissions(): void
    {
        $this->seed(ShieldSeeder::class);
        $marketer = User::factory()->create();
        $marketer->assignRole('marketing');

        $this->assertTrue($marketer->can('ViewAny', RewardSubmission::class));
        $this->assertTrue($marketer->can('View', RewardSubmission::class));
        $this->assertTrue($marketer->can('Update', RewardSubmission::class));
    }

    /**
     * The old-jewellery bidding marketplace contact (App\Models\Vendor) —
     * unrelated to reward submissions, which 'marketing' reviews instead.
     * See the docblock on ShieldSeeder's vendor block for why this needs a
     * regression test: the two used to share this role name by accident.
     */
    public function test_old_jewellery_vendor_cannot_view_reward_submissions(): void
    {
        $this->seed(ShieldSeeder::class);
        $vendor = User::factory()->create();
        $vendor->assignRole('vendor');

        $this->assertFalse($vendor->can('ViewAny', RewardSubmission::class));
    }

    public function test_super_admin_can_view_and_update_reward_submissions(): void
    {
        $this->seed(ShieldSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->assertTrue($admin->can('ViewAny', RewardSubmission::class));
        $this->assertTrue($admin->can('Update', RewardSubmission::class));
    }

    public function test_user_with_no_role_cannot_view_reward_submissions(): void
    {
        $this->seed(ShieldSeeder::class);
        $nobody = User::factory()->create();

        $this->assertFalse($nobody->can('ViewAny', RewardSubmission::class));
    }

    public function test_marketing_cannot_delete_a_reward_submission(): void
    {
        $this->seed(ShieldSeeder::class);
        $marketer = User::factory()->create();
        $marketer->assignRole('marketing');

        $customer = User::factory()->create();
        $order = Order::create([
            'user_id' => $customer->id,
            'order_number' => 'ORD-POL-'.uniqid(),
            'customer_name' => 'Policy Test',
            'customer_email' => 'policy@example.com',
            'customer_phone' => '9999999999',
            'shipping_address_line1' => 'Test St',
            'shipping_city' => 'Hyderabad',
            'shipping_state' => 'Telangana',
            'shipping_postal_code' => '500001',
            'shipping_country' => 'India',
            'subtotal' => 500,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => 500,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'delivered',
        ]);
        $submission = RewardSubmission::create(['user_id' => $customer->id, 'order_id' => $order->id, 'status' => 'pending']);

        $this->actingAs($marketer);

        $this->assertFalse($marketer->can('Delete', $submission));
        $this->assertFalse(RewardSubmissionResource::canDelete($submission));
    }

    public function test_super_admin_can_delete_a_reward_submission(): void
    {
        $this->seed(ShieldSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $customer = User::factory()->create();
        $order = Order::create([
            'user_id' => $customer->id,
            'order_number' => 'ORD-POL-'.uniqid(),
            'customer_name' => 'Policy Test',
            'customer_email' => 'policy@example.com',
            'customer_phone' => '9999999999',
            'shipping_address_line1' => 'Test St',
            'shipping_city' => 'Hyderabad',
            'shipping_state' => 'Telangana',
            'shipping_postal_code' => '500001',
            'shipping_country' => 'India',
            'subtotal' => 500,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => 500,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'delivered',
        ]);
        $submission = RewardSubmission::create(['user_id' => $customer->id, 'order_id' => $order->id, 'status' => 'pending']);

        $this->actingAs($admin);

        $this->assertTrue($admin->can('Delete', $submission));
        $this->assertTrue(RewardSubmissionResource::canDelete($submission));
    }
}
