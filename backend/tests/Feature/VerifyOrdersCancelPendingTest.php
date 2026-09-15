<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OrdersCancelPending is the abandoned-cart sweep: orders stuck in
 * status=placed + payment_status=pending past the window get cancelled —
 * restocking product, refunding wallet usage, and freeing the coupon slot.
 * These cases pin the exact selection rule (strict "<" cutoff, only
 * placed+pending), the side effects applied through Order::booted(), and
 * that the sweep never cancels paid/failed/advanced orders or double-restocks.
 */
class VerifyOrdersCancelPendingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancels_a_placed_pending_order_older_than_the_window(): void
    {
        $order = $this->makeOrder();
        $order->forceFill(['created_at' => now()->subHours(5)])->save();

        $this->artisan('orders:cancel-pending')
            ->expectsOutput('Cancelled 1 placed-but-unpaid order(s) older than 4h.')
            ->assertSuccessful();

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_keeps_a_placed_pending_order_younger_than_the_window(): void
    {
        $order = $this->makeOrder();
        $order->forceFill(['created_at' => now()->subHours(1)])->save();

        $this->artisan('orders:cancel-pending')->assertSuccessful();

        $this->assertSame('placed', $order->fresh()->status);
    }

    public function test_keeps_an_order_exactly_at_the_window_boundary(): void
    {
        $order = $this->makeOrder();
        $order->forceFill(['created_at' => now()->subHours(4)])->save();

        $this->artisan('orders:cancel-pending')->assertSuccessful();

        $this->assertSame('placed', $order->fresh()->status, 'Only orders strictly older than the cutoff are swept.');
    }

    public function test_does_not_cancel_a_paid_order(): void
    {
        $order = $this->makeOrder(['payment_status' => 'paid', 'payment_reference' => 'pay_fake456']);
        $order->forceFill(['created_at' => now()->subHours(6)])->save();

        $this->artisan('orders:cancel-pending')->assertSuccessful();

        $this->assertSame('placed', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_does_not_cancel_a_failed_order(): void
    {
        $order = $this->makeOrder(['payment_status' => 'failed']);
        $order->forceFill(['created_at' => now()->subHours(6)])->save();

        $this->artisan('orders:cancel-pending')->assertSuccessful();

        $this->assertSame('placed', $order->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
    }

    public function test_does_not_cancel_a_non_placed_order(): void
    {
        $order = $this->makeOrder(['status' => 'accepted']);
        $order->forceFill(['created_at' => now()->subHours(6)])->save();

        $this->artisan('orders:cancel-pending')->assertSuccessful();

        $this->assertSame('accepted', $order->fresh()->status);
    }

    public function test_restocks_the_products_on_cancellation(): void
    {
        $product = Product::create($this->productAttrs('sweep-plain', 5));
        $order = $this->makeOrder();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_title' => $product->title,
            'sku' => $product->sku,
            'price' => $product->price,
            'quantity' => 3,
            'subtotal' => $product->price * 3,
        ]);
        $order->forceFill(['created_at' => now()->subHours(5)])->save();

        $this->artisan('orders:cancel-pending')->assertSuccessful();

        $this->assertSame(8, $product->fresh()->stock_quantity);
    }

    public function test_does_not_double_restock_on_a_second_sweep(): void
    {
        $product = Product::create($this->productAttrs('sweep-twice', 5));
        $order = $this->makeOrder();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_title' => $product->title,
            'sku' => $product->sku,
            'price' => $product->price,
            'quantity' => 2,
            'subtotal' => $product->price * 2,
        ]);
        $order->forceFill(['created_at' => now()->subHours(5)])->save();

        $this->artisan('orders:cancel-pending')->assertSuccessful();
        $this->artisan('orders:cancel-pending')
            ->expectsOutput('Cancelled 0 placed-but-unpaid order(s) older than 4h.')
            ->assertSuccessful();

        $this->assertSame(7, $product->fresh()->stock_quantity);
    }

    public function test_refunds_the_wallet_amount_used(): void
    {
        $user = User::factory()->create(['wallet_balance' => 1000]);
        $order = $this->makeOrder(['user_id' => $user->id, 'wallet_amount_used' => 200]);
        $order->forceFill(['created_at' => now()->subHours(5)])->save();

        $this->artisan('orders:cancel-pending')->assertSuccessful();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(1200.0, (float) $user->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => 200,
            'reason' => 'order_refund',
            'reference_type' => 'App\Models\Order',
            'reference_id' => $order->id,
        ]);
    }

    public function test_cancels_multiple_eligible_orders(): void
    {
        $first = $this->makeOrder()->forceFill(['created_at' => now()->subHours(5)]);
        $first->save();
        $second = $this->makeOrder()->forceFill(['created_at' => now()->subHours(6)]);
        $second->save();

        $this->artisan('orders:cancel-pending')
            ->expectsOutput('Cancelled 2 placed-but-unpaid order(s) older than 4h.')
            ->assertSuccessful();

        $this->assertSame('cancelled', $first->fresh()->status);
        $this->assertSame('cancelled', $second->fresh()->status);
    }

    public function test_reports_zero_when_no_orders_are_eligible(): void
    {
        $this->artisan('orders:cancel-pending')
            ->expectsOutput('Cancelled 0 placed-but-unpaid order(s) older than 4h.')
            ->assertSuccessful();
    }

    public function test_releases_the_coupon_slot_for_an_unpaid_cancelled_order(): void
    {
        $coupon = $this->makeCoupon(5, 1);
        $order = $this->makeOrder(['coupon_code' => $coupon->code, 'discount_amount' => 20]);
        $order->couponUsages()->create(['coupon_id' => $coupon->id, 'discount_amount' => 20]);
        $order->forceFill(['created_at' => now()->subHours(5)])->save();

        $this->artisan('orders:cancel-pending')->assertSuccessful();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(0, $coupon->fresh()->used_count);
    }

    public function test_hours_option_lengthens_the_window(): void
    {
        $fresh = $this->makeOrder();
        $fresh->forceFill(['created_at' => now()->subHours(12)])->save();

        $this->artisan('orders:cancel-pending', ['--hours' => 24])
            ->expectsOutput('Cancelled 0 placed-but-unpaid order(s) older than 24h.')
            ->assertSuccessful();

        $this->assertSame('placed', $fresh->fresh()->status);
    }

    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-TEST-'.uniqid(),
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
            'wallet_amount_used' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'placed',
        ], $overrides));
    }

    private function productAttrs(string $tag, int $stock): array
    {
        return [
            'title' => 'Product '.$tag,
            'slug' => 'product-'.$tag.'-'.uniqid(),
            'sku' => 'SKU-'.strtoupper($tag).'-'.uniqid(),
            'price' => 500,
            'stock_quantity' => $stock,
            'is_active' => true,
        ];
    }

    private function makeCoupon(int $limit, int $used): Coupon
    {
        return Coupon::create([
            'code' => 'SWEEP'.uniqid(),
            'type' => 'fixed',
            'value' => 20,
            'scope' => 'all',
            'usage_limit' => $limit,
            'used_count' => $used,
            'is_active' => true,
            'is_public' => true,
        ]);
    }
}
