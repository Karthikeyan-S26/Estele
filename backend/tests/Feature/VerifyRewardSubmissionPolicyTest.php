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

    public function test_vendor_can_view_and_update_reward_submissions(): void
    {
        $this->seed(ShieldSeeder::class);
        $vendor = User::factory()->create();
        $vendor->assignRole('vendor');

        $this->assertTrue($vendor->can('ViewAny', RewardSubmission::class));
        $this->assertTrue($vendor->can('View', RewardSubmission::class));
        $this->assertTrue($vendor->can('Update', RewardSubmission::class));
    }

    public function test_marketing_role_cannot_view_reward_submissions(): void
    {
        $this->seed(ShieldSeeder::class);
        $marketer = User::factory()->create();
        $marketer->assignRole('marketing');

        $this->assertFalse($marketer->can('ViewAny', RewardSubmission::class));
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

    public function test_vendor_cannot_delete_a_reward_submission(): void
    {
        $this->seed(ShieldSeeder::class);
        $vendor = User::factory()->create();
        $vendor->assignRole('vendor');

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

        $this->actingAs($vendor);

        $this->assertFalse($vendor->can('Delete', $submission));
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
