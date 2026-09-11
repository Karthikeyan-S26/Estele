<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VerifyCustomerResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_list_shows_only_users_without_a_panel_role(): void
    {
        $this->seed(ShieldSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $customer = User::factory()->create(['name' => 'Priya Customer']);

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords([$customer])
            ->assertCanNotSeeTableRecords([$admin]);
    }

    public function test_customer_list_shows_order_count(): void
    {
        $this->seed(ShieldSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $customer = User::factory()->create();
        $this->makeOrder($customer);
        $this->makeOrder($customer);

        Livewire::test(ListCustomers::class)
            ->assertTableColumnStateSet('orders_count', 2, $customer);
    }

    public function test_marketing_role_cannot_access_the_customer_list(): void
    {
        $this->seed(ShieldSeeder::class);
        $marketer = User::factory()->create();
        $marketer->assignRole('marketing');

        $this->assertFalse($marketer->can('ViewAny', User::class));
        $this->assertFalse(CustomerResource::canViewAny());
    }

    public function test_super_admin_can_access_the_customer_list(): void
    {
        $this->seed(ShieldSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $this->assertTrue($admin->can('ViewAny', User::class));
        $this->assertTrue(CustomerResource::canViewAny());
    }

    public function test_user_with_no_role_cannot_access_the_customer_list(): void
    {
        $this->seed(ShieldSeeder::class);
        $nobody = User::factory()->create();

        $this->assertFalse($nobody->can('ViewAny', User::class));
    }

    private function makeOrder(User $user): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-CUST-'.uniqid(),
            'customer_name' => $user->name,
            'customer_email' => $user->email,
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
            'status' => 'placed',
        ]);
    }
}
