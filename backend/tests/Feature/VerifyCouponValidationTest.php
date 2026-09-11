<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coupon::isValidFor() is the single gate both the storefront "apply coupon"
 * endpoint and CheckoutController::store() call — the latter never trusts a
 * discount computed earlier in the request, it always re-derives it from
 * this method under a fresh DB lock. Locks down every rejection path plus
 * the discount math (scope matching, percent-with-cap, flat-never-exceeds-
 * subtotal), none of which had any coverage before.
 */
class VerifyCouponValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_coupon_is_rejected(): void
    {
        $coupon = $this->makeCoupon(['is_active' => false]);
        $cart = $this->cartWithProduct(500);

        $result = $coupon->isValidFor($cart);

        $this->assertFalse($result['valid']);
        $this->assertSame('This coupon is not active.', $result['message']);
    }

    public function test_coupon_not_yet_started_is_rejected(): void
    {
        $coupon = $this->makeCoupon(['starts_at' => now()->addDay()]);
        $cart = $this->cartWithProduct(500);

        $result = $coupon->isValidFor($cart);

        $this->assertFalse($result['valid']);
        $this->assertSame('This coupon is not active yet.', $result['message']);
    }

    public function test_expired_coupon_is_rejected(): void
    {
        $coupon = $this->makeCoupon(['expires_at' => now()->subDay()]);
        $cart = $this->cartWithProduct(500);

        $result = $coupon->isValidFor($cart);

        $this->assertFalse($result['valid']);
        $this->assertSame('This coupon has expired.', $result['message']);
    }

    public function test_coupon_at_usage_limit_is_rejected(): void
    {
        $coupon = $this->makeCoupon(['usage_limit' => 5, 'used_count' => 5]);
        $cart = $this->cartWithProduct(500);

        $result = $coupon->isValidFor($cart);

        $this->assertFalse($result['valid']);
        $this->assertSame('This coupon has reached its usage limit.', $result['message']);
    }

    public function test_empty_cart_is_rejected(): void
    {
        $coupon = $this->makeCoupon();
        $cart = Cart::create(['session_id' => 'empty-cart']);

        $result = $coupon->isValidFor($cart);

        $this->assertFalse($result['valid']);
        $this->assertSame('Your cart is empty.', $result['message']);
    }

    public function test_order_below_minimum_value_is_rejected(): void
    {
        $coupon = $this->makeCoupon(['min_order_value' => 1000]);
        $cart = $this->cartWithProduct(500);

        $result = $coupon->isValidFor($cart);

        $this->assertFalse($result['valid']);
        $this->assertSame('Your order does not meet the minimum value for this coupon.', $result['message']);
    }

    public function test_flat_discount_on_all_products_scope(): void
    {
        $coupon = $this->makeCoupon(['type' => 'flat', 'value' => 100, 'scope' => 'all']);
        $cart = $this->cartWithProduct(500);

        $result = $coupon->isValidFor($cart);

        $this->assertTrue($result['valid']);
        $this->assertSame(100.0, $result['discount']);
    }

    public function test_flat_discount_never_exceeds_the_applicable_subtotal(): void
    {
        $coupon = $this->makeCoupon(['type' => 'flat', 'value' => 1000, 'scope' => 'all']);
        $cart = $this->cartWithProduct(500);

        $result = $coupon->isValidFor($cart);

        $this->assertTrue($result['valid']);
        $this->assertSame(500.0, $result['discount'], 'A flat discount larger than the cart must be capped at the subtotal.');
    }

    public function test_percent_discount_is_capped_by_max_discount_amount(): void
    {
        $coupon = $this->makeCoupon([
            'type' => 'percent',
            'value' => 50,
            'max_discount_amount' => 100,
            'scope' => 'all',
        ]);
        $cart = $this->cartWithProduct(1000);

        $result = $coupon->isValidFor($cart);

        $this->assertTrue($result['valid']);
        $this->assertSame(100.0, $result['discount'], '50% of 1000 is 500, but the max_discount_amount cap of 100 must win.');
    }

    public function test_percent_discount_without_cap_applies_in_full(): void
    {
        $coupon = $this->makeCoupon(['type' => 'percent', 'value' => 10, 'scope' => 'all']);
        $cart = $this->cartWithProduct(1000);

        $result = $coupon->isValidFor($cart);

        $this->assertTrue($result['valid']);
        $this->assertSame(100.0, $result['discount']);
    }

    public function test_product_scoped_coupon_only_discounts_matching_items(): void
    {
        $matchingProduct = Product::create($this->productAttrs('match', 300));
        $otherProduct = Product::create($this->productAttrs('other', 700));

        $coupon = $this->makeCoupon(['type' => 'percent', 'value' => 10, 'scope' => 'products']);
        $coupon->products()->attach($matchingProduct->id);

        $cart = Cart::create(['session_id' => 'scoped-cart']);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $matchingProduct->id, 'quantity' => 1]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $otherProduct->id, 'quantity' => 1]);

        $result = $coupon->isValidFor($cart);

        $this->assertTrue($result['valid']);
        $this->assertSame(30.0, $result['discount'], '10% of the matching 300 item only — the 700 item must not count.');
    }

    public function test_category_scoped_coupon_only_discounts_matching_items(): void
    {
        $category = Category::create(['name' => 'Earrings', 'slug' => 'earrings-'.uniqid()]);
        $matchingProduct = Product::create($this->productAttrs('cat-match', 400));
        $matchingProduct->categories()->attach($category->id);
        $otherProduct = Product::create($this->productAttrs('cat-other', 600));

        $coupon = $this->makeCoupon(['type' => 'flat', 'value' => 1000, 'scope' => 'categories']);
        $coupon->categories()->attach($category->id);

        $cart = Cart::create(['session_id' => 'cat-scoped-cart']);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $matchingProduct->id, 'quantity' => 1]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $otherProduct->id, 'quantity' => 1]);

        $result = $coupon->isValidFor($cart);

        $this->assertTrue($result['valid']);
        $this->assertSame(400.0, $result['discount'], 'Flat discount is capped at the matching category items only (400), never the full cart (1000).');
    }

    public function test_scoped_coupon_with_no_matching_items_is_rejected(): void
    {
        $matchingProduct = Product::create($this->productAttrs('never-in-cart', 300));
        $otherProduct = Product::create($this->productAttrs('in-cart', 300));

        $coupon = $this->makeCoupon(['type' => 'flat', 'value' => 50, 'scope' => 'products']);
        $coupon->products()->attach($matchingProduct->id);

        $cart = Cart::create(['session_id' => 'no-match-cart']);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $otherProduct->id, 'quantity' => 1]);

        $result = $coupon->isValidFor($cart);

        $this->assertFalse($result['valid']);
        $this->assertSame('This coupon does not apply to the items in your cart.', $result['message']);
    }

    private function makeCoupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'TEST'.uniqid(),
            'type' => 'flat',
            'value' => 50,
            'scope' => 'all',
            'is_active' => true,
            'is_public' => true,
        ], $overrides));
    }

    private function cartWithProduct(float $price): Cart
    {
        $product = Product::create($this->productAttrs('cart-product', $price));

        $cart = Cart::create(['session_id' => 'cart-'.uniqid()]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        return $cart;
    }

    private function productAttrs(string $tag, float $price): array
    {
        return [
            'title' => 'Product '.$tag,
            'slug' => 'product-'.$tag.'-'.uniqid(),
            'sku' => 'SKU-'.strtoupper($tag).'-'.uniqid(),
            'price' => $price,
            'stock_quantity' => 10,
            'is_active' => true,
        ];
    }
}
