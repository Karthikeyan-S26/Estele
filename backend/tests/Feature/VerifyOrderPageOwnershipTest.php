<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The confirmation and payment pages are both reachable by order_number alone
 * and both render the customer's name and shipping/contact details, so they
 * must refuse anyone who does not own the order.
 */
class VerifyOrderPageOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_page_is_hidden_from_a_stranger(): void
    {
        $order = $this->makeOrder(User::factory()->create()->id);

        $this->actingAs(User::factory()->create())
            ->get(route('checkout.confirmation', $order))
            ->assertNotFound();

        $this->get(route('checkout.confirmation', $order))->assertNotFound();
    }

    public function test_confirmation_page_is_visible_to_the_owner(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('checkout.confirmation', $this->makeOrder($owner->id)))
            ->assertOk();
    }

    public function test_payment_page_is_hidden_from_a_stranger(): void
    {
        $order = $this->makeOrder(User::factory()->create()->id, 'razorpay');

        $this->actingAs(User::factory()->create())
            ->get(route('payment.show', $order))
            ->assertNotFound();

        $this->get(route('payment.show', $order))->assertNotFound();
    }

    /**
     * user_id is nullable for pre-auth-checkout legacy orders. A logged-out
     * visitor must not inherit those via a null === null match.
     */
    public function test_legacy_order_without_a_user_is_not_public(): void
    {
        $order = $this->makeOrder(null);

        $this->get(route('checkout.confirmation', $order))->assertNotFound();
    }

    private function makeOrder(?int $userId, string $method = 'cod'): Order
    {
        return Order::create([
            'user_id' => $userId,
            'order_number' => 'ORD-OWN-'.uniqid(),
            'customer_name' => 'Owner Test',
            'customer_email' => 'owner@example.com',
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
            'payment_method' => $method,
            'payment_status' => $method === 'razorpay' ? 'pending' : 'paid',
            'status' => 'placed',
        ]);
    }
}
