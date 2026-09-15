<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Coupons consume a usage slot at checkout (used_count) and that slot is only
 * worth keeping while a real sale can still happen. When an order is cancelled
 * before any money landed (payment_status pending/failed) the slot is freed —
 * otherwise a few abandoned carts could exhaust a limited-use promo the store
 * never profited from, and an auto-cancelled customer reordering would find
 * the code "maxed out". Paid cancellations and returns keep their usage: the
 * coupon genuinely helped close that sale. The release is idempotent and
 * floor-safe (used_count never goes negative).
 */
class VerifyCouponReleaseOnCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_an_unpaid_order_releases_the_coupon_usage(): void
    {
        $coupon = $this->makeCoupon(['usage_limit' => 2, 'used_count' => 1]);
        $order = $this->makeOrder(['coupon_code' => $coupon->code]);
        $order->couponUsages()->create(['coupon_id' => $coupon->id, 'discount_amount' => 20]);

        $order->update(['status' => 'cancelled']);

        $this->assertSame(0, $coupon->fresh()->used_count);
    }

    public function test_cancelling_a_couponed_order_marks_the_coupon_usable_again(): void
    {
        $product = Product::create($this->productAttrs('coupon-relax', 5));
        $coupon = $this->makeCoupon(['usage_limit' => 1, 'used_count' => 1]);
        $cart = Cart::create(['session_id' => 'sess-coupon-relax']);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->assertFalse($coupon->isValidFor($cart)['valid']);

        $order = $this->makeOrder(['coupon_code' => $coupon->code]);
        $order->couponUsages()->create(['coupon_id' => $coupon->id, 'discount_amount' => 20]);
        $order->update(['status' => 'cancelled']);

        $this->assertTrue($coupon->fresh()->isValidFor($cart)['valid']);
    }

    public function test_cancelling_an_order_whose_payment_failed_releases_the_usage(): void
    {
        $coupon = $this->makeCoupon(['usage_limit' => 2, 'used_count' => 1]);
        $order = $this->makeOrder(['coupon_code' => $coupon->code, 'payment_status' => 'failed']);
        $order->couponUsages()->create(['coupon_id' => $coupon->id, 'discount_amount' => 20]);

        $order->update(['status' => 'cancelled']);

        $this->assertSame(0, $coupon->fresh()->used_count);
    }

    public function test_cancelling_a_paid_order_keeps_the_coupon_usage(): void
    {
        $coupon = $this->makeCoupon(['usage_limit' => 2, 'used_count' => 1]);
        $order = $this->makeOrder(['coupon_code' => $coupon->code, 'payment_status' => 'paid']);
        $order->couponUsages()->create(['coupon_id' => $coupon->id, 'discount_amount' => 20]);

        $order->update(['status' => 'cancelled']);

        $this->assertSame(1, $coupon->fresh()->used_count, 'Money landed before the cancel — the coupon sale stands.');
    }

    public function test_returning_a_delivered_order_keeps_the_coupon_usage(): void
    {
        $coupon = $this->makeCoupon(['usage_limit' => 2, 'used_count' => 1]);
        $order = $this->makeOrder(['coupon_code' => $coupon->code, 'status' => 'delivered', 'payment_status' => 'paid']);
        $order->couponUsages()->create(['coupon_id' => $coupon->id, 'discount_amount' => 20]);

        $order->update(['status' => 'returned']);

        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_release_never_drives_used_count_below_zero(): void
    {
        $coupon = $this->makeCoupon(['usage_limit' => 1, 'used_count' => 0]);
        $order = $this->makeOrder(['coupon_code' => $coupon->code]);
        $order->couponUsages()->create(['coupon_id' => $coupon->id, 'discount_amount' => 20]);

        $order->update(['status' => 'cancelled']);

        $this->assertSame(0, $coupon->fresh()->used_count);
    }

    public function test_cancelling_without_a_coupon_usage_row_is_a_no_op(): void
    {
        $order = $this->makeOrder();

        $order->update(['status' => 'cancelled']);

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_release_clears_the_public_coupon_cache(): void
    {
        Cache::put('site.public_coupons', ['cached'], 3600);
        $coupon = $this->makeCoupon(['usage_limit' => 2, 'used_count' => 1]);
        $order = $this->makeOrder(['coupon_code' => $coupon->code]);
        $order->couponUsages()->create(['coupon_id' => $coupon->id, 'discount_amount' => 20]);

        $order->update(['status' => 'cancelled']);

        $this->assertNull(Cache::get('site.public_coupons'));
    }

    public function test_release_is_not_repeated_by_a_later_non_status_update(): void
    {
        $coupon = $this->makeCoupon(['usage_limit' => 2, 'used_count' => 1]);
        $order = $this->makeOrder(['coupon_code' => $coupon->code]);
        $order->couponUsages()->create(['coupon_id' => $coupon->id, 'discount_amount' => 20]);
        $order->update(['status' => 'cancelled']);
        $this->assertSame(0, $coupon->fresh()->used_count);

        $order->update(['admin_notes' => 'Some admin note after cancellation.']);

        $this->assertSame(0, $coupon->fresh()->used_count, 'A later non-status update must not release a second time.');
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

    private function makeCoupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'COUPON'.uniqid(),
            'type' => 'fixed',
            'value' => 20,
            'scope' => 'all',
            'is_active' => true,
            'is_public' => true,
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
}
