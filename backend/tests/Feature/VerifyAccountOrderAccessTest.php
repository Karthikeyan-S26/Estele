<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * My Account > order detail/invoice/cancellation-request (Phase 7). Two things
 * matter most here: one customer can never view/action another customer's
 * order, and a cancellation request never touches `status` directly — that
 * stays guarded by Order::ALLOWED_TRANSITIONS (see VerifyOrderStatusPipelineTest),
 * this only sets a review flag.
 */
class VerifyAccountOrderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_their_order(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $user->id, 'status' => 'placed']);

        $this->actingAs($user)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_a_user_cannot_view_another_users_order(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $owner->id]);

        $this->actingAs($stranger)
            ->get(route('account.orders.show', $order))
            ->assertNotFound();
    }

    public function test_a_guest_order_with_no_user_id_is_not_viewable_via_account(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(['user_id' => null]);

        $this->actingAs($user)
            ->get(route('account.orders.show', $order))
            ->assertNotFound();
    }

    public function test_owner_can_download_the_invoice(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('account.orders.invoice', $order))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_a_user_cannot_download_another_users_invoice(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $owner->id]);

        $this->actingAs($stranger)
            ->get(route('account.orders.invoice', $order))
            ->assertNotFound();
    }

    public function test_a_placed_order_can_have_cancellation_requested(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $user->id, 'status' => 'placed']);

        $this->actingAs($user)
            ->post(route('account.orders.cancellation-request', $order), ['reason' => 'Changed my mind'])
            ->assertRedirect(route('account.orders.show', $order));

        $order->refresh();
        $this->assertNotNull($order->cancellation_requested_at);
        $this->assertSame('Changed my mind', $order->cancellation_reason);
        // Confirms the request never touched status — still guarded exclusively
        // by Order::ALLOWED_TRANSITIONS / the admin flow.
        $this->assertSame('placed', $order->status);
    }

    public function test_a_shipped_order_cannot_have_cancellation_requested(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $user->id, 'status' => 'shipped']);

        $this->actingAs($user)
            ->post(route('account.orders.cancellation-request', $order), ['reason' => 'Too late'])
            ->assertStatus(422);

        $this->assertNull($order->fresh()->cancellation_requested_at);
    }

    public function test_a_delivered_order_can_have_a_return_requested(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $user->id, 'status' => 'delivered']);

        $this->actingAs($user)
            ->post(route('account.orders.cancellation-request', $order), ['reason' => 'Damaged item'])
            ->assertRedirect(route('account.orders.show', $order));

        $this->assertNotNull($order->fresh()->cancellation_requested_at);
    }

    public function test_a_second_cancellation_request_is_rejected_once_one_is_pending(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $user->id, 'status' => 'placed', 'cancellation_requested_at' => now()]);

        $this->actingAs($user)
            ->post(route('account.orders.cancellation-request', $order), ['reason' => 'Again'])
            ->assertStatus(422);
    }

    public function test_a_user_cannot_request_cancellation_on_another_users_order(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->makeOrder(['user_id' => $owner->id, 'status' => 'placed']);

        $this->actingAs($stranger)
            ->post(route('account.orders.cancellation-request', $order), ['reason' => 'Not mine'])
            ->assertNotFound();
    }

    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-ACCT-'.uniqid(),
            'customer_name' => 'Verify Test',
            'customer_email' => 'verify@example.com',
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
        ], $overrides));
    }
}
