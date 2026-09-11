<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponses;
use App\Http\Api\ProductResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class CartController
{
    use ApiResponses;

    /**
     * GET /api/cart — full cart payload: items, coupon, totals (subtotal,
     * discount, shipping, total with the free-above-₹999 rule applied).
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->currentCart($request);
        $cart->loadMissing('coupon');
        $items = $cart->items()->with(['product.media', 'variant'])->get();

        $subtotal = $items->sum(fn (CartItem $item) => $item->unitPrice() * $item->quantity);
        $discount = $this->currentDiscount($cart);
        $shipping = $this->shippingQuote($subtotal, (int) $items->sum('quantity'));

        return $this->ok([
            'items' => $items->map(fn (CartItem $item) => $this->cartItemPayload($item))->values(),
            'coupon' => $cart->coupon ? ['code' => $cart->coupon->code, 'summary' => $cart->coupon->summary()] : null,
            'totals' => [
                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
                'shipping' => round($shipping['fee'], 2),
                'total' => round($subtotal - $discount + $shipping['fee'], 2),
                'shipping_is_free' => $shipping['is_free'],
            ],
            'cart_count' => (int) $items->sum('quantity'),
        ]);
    }

    /**
     * DELETE /api/cart — empty the cart (all line items + any applied coupon).
     */
    public function clear(Request $request): JsonResponse
    {
        $cart = $this->currentCart($request);

        $cart->items()->delete();
        $cart->update(['coupon_id' => null]);

        return $this->cartResponse($cart, 'Cart cleared.');
    }

    /**
     * POST /api/cart/{product_slug} — add to cart. Optional `product_variant_id`
     * and `quantity` (default 1). Clamps to available stock like the web cart.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $validator = Validator::make($request->all(), [
            'quantity' => ['nullable', 'integer', 'min:1'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $quantity = $validated['quantity'] ?? 1;
        $variantId = $validated['product_variant_id'] ?? null;

        $variant = $variantId ? $product->variants()->find($variantId) : null;
        if ($variantId && ! $variant) {
            return $this->error('Selected option is not available.', 422);
        }

        $availableStock = $variant?->stock_quantity ?? $product->stock_quantity;
        if ($availableStock <= 0) {
            return $this->error('This product is out of stock.', 422);
        }

        $cart = $this->currentCart($request);

        $item = $cart->items()->firstOrNew([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ]);
        $item->quantity = min($availableStock, ($item->exists ? $item->quantity : 0) + $quantity);
        $item->save();

        return $this->cartResponse($cart, 'Added to cart.');
    }

    /**
     * PATCH /api/cart/items/{id} — update quantity of one line item.
     */
    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->assertOwnsItem($request, $cartItem);

        $validator = Validator::make($request->all(), [
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        $availableStock = $cartItem->availableStock();
        $cartItem->update([
            'quantity' => min($validator->validated()['quantity'], max($availableStock, 1)),
        ]);

        return $this->cartResponse($cart, 'Cart updated.');
    }

    /**
     * DELETE /api/cart/items/{id} — remove a line item.
     */
    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->assertOwnsItem($request, $cartItem);

        $cartItem->delete();

        return $this->cartResponse($cart, 'Item removed from cart.');
    }

    /**
     * POST /api/cart/coupon — apply a coupon code.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        $cart = $this->currentCart($request);
        $coupon = Coupon::where('code', strtoupper(trim($validator->validated()['code'])))->first();

        if (! $coupon) {
            return $this->error('Invalid coupon code.', 422);
        }

        $result = $coupon->isValidFor($cart);
        if (! $result['valid']) {
            return $this->error($result['message'], 422);
        }

        $cart->update(['coupon_id' => $coupon->id]);

        return $this->cartResponse($cart, 'Coupon applied.');
    }

    /**
     * DELETE /api/cart/coupon — remove the applied coupon.
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->currentCart($request);
        $cart->update(['coupon_id' => null]);

        return $this->cartResponse($cart, 'Coupon removed.');
    }

    /* ---------------------------------------------------------------- */

    private function currentCart(Request $request): Cart
    {
        // Authenticated users get a cart bound to their user_id...
        if ($user = $request->user()) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        }

        // ...guests get one keyed by a device-generated cart token sent in
        // the X-Cart-Token header (UUID persisted by the Flutter app).
        $cartToken = $request->header('X-Cart-Token') ?? $request->input('cart_token');
        abort_unless($cartToken, 422); // Must have sent a cart token OR be logged in.

        return Cart::firstOrCreate(['session_id' => $cartToken]);
    }

    private function assertOwnsItem(Request $request, CartItem $cartItem): Cart
    {
        $cart = $this->currentCart($request);

        if ($cartItem->cart_id !== $cart->id) {
            abort(403, 'This cart item belongs to a different cart.');
        }

        return $cart;
    }

    private function cartItemPayload(CartItem $item): array
    {
        $product = $item->product;

        return [
            'id' => $item->id,
            'product' => ProductResource::card($product),
            'variant' => $item->variant ? [
                'id' => $item->variant->id,
                'sku' => $item->variant->sku,
                'price' => (float) $item->variant->price,
                'attributes' => $item->variant->attributes,
            ] : null,
            'unit_price' => (float) $item->unitPrice(),
            'quantity' => $item->quantity,
            'line_total' => round((float) $item->unitPrice() * $item->quantity, 2),
            'available_stock' => $item->availableStock(),
        ];
    }

    private function currentDiscount(Cart $cart): float
    {
        if (! $cart->coupon) {
            return 0.0;
        }

        $result = $cart->coupon->isValidFor($cart);

        return $result['valid'] ? $result['discount'] : 0.0;
    }

    private function shippingQuote(float $subtotal, int $quantity): array
    {
        // Free shipping above ₹999 — hardcoded mirror of the FlatRateCalculator
        // default so the API response stays correct without a DB config look-up
        // per request.
        $freeAbove = 999;
        $isFree = $subtotal >= $freeAbove || $subtotal <= 0.0;

        return [
            'fee' => $isFree ? 0.0 : 49.0,
            'is_free' => $isFree,
            'free_above' => $freeAbove,
        ];
    }

    private function cartResponse(Cart $cart, string $message): JsonResponse
    {
        $cart->loadMissing('coupon');
        $items = $cart->items()->with(['product.media', 'variant'])->get();
        $subtotal = $items->sum(fn (CartItem $item) => $item->unitPrice() * $item->quantity);
        $discount = $this->currentDiscount($cart);
        $shipping = $this->shippingQuote($subtotal, (int) $items->sum('quantity'));

        return $this->ok([
            'message' => $message,
            'items' => $items->map(fn (CartItem $item) => $this->cartItemPayload($item))->values(),
            'coupon' => $cart->coupon ? ['code' => $cart->coupon->code, 'summary' => $cart->coupon->summary()] : null,
            'totals' => [
                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
                'shipping' => round($shipping['fee'], 2),
                'total' => round($subtotal - $discount + $shipping['fee'], 2),
                'shipping_is_free' => $shipping['is_free'],
            ],
            'cart_count' => (int) $items->sum('quantity'),
        ]);
    }
}