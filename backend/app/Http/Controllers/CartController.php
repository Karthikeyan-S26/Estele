<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Offer;
use App\Models\Product;
use App\Services\Shipping\ShippingManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function __construct(private readonly ShippingManager $shipping) {}

    public function index(Request $request)
    {
        $cart = $this->currentCart($request);
        $cart->loadMissing('coupon');
        $items = $cart->items()->with(['product.media', 'variant'])->get();

        $subtotal = $items->sum(fn (CartItem $item) => $item->unitPrice() * $item->quantity);
        $discount = $this->currentDiscount($cart);
        $shipping = $this->shipping->quote($subtotal, (int) $items->sum('quantity'));

        return view('cart.index', [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'couponCode' => $cart->coupon?->code,
        ]);
    }

    public function applyCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return $this->respondError($request, $validator->errors()->first());
        }

        $cart = $this->currentCart($request);
        $coupon = Coupon::where('code', strtoupper(trim($validator->validated()['code'])))->first();

        if (! $coupon) {
            return $this->respondError($request, 'Invalid coupon code.');
        }

        $result = $coupon->isValidFor($cart);
        if (! $result['valid']) {
            return $this->respondError($request, $result['message']);
        }

        $cart->update(['coupon_id' => $coupon->id]);

        return $this->respondSuccess($request, $cart, 'Coupon applied.');
    }

    public function removeCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $cart = $this->currentCart($request);
        $cart->update(['coupon_id' => null]);

        return $this->respondSuccess($request, $cart, 'Coupon removed.');
    }

    public function store(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        // Validated manually (not $request->validate()): this app's exception
        // handler only renders JSON for `api/*` paths (see bootstrap/app.php),
        // so letting a ValidationException bubble up here would hand the
        // cart drawer's fetch() call an HTML redirect instead of JSON.
        $validator = Validator::make($request->all(), [
            'quantity' => ['nullable', 'integer', 'min:1'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        if ($validator->fails()) {
            return $this->respondError($request, $validator->errors()->first());
        }

        $validated = $validator->validated();
        $quantity = $validated['quantity'] ?? 1;
        $variantId = $validated['product_variant_id'] ?? null;

        $variant = $variantId ? $product->variants()->find($variantId) : null;
        if ($variantId && ! $variant) {
            return $this->respondError($request, 'Selected option is not available.');
        }

        $availableStock = $variant?->stock_quantity ?? $product->stock_quantity;
        if ($availableStock <= 0) {
            return $this->respondError($request, 'This product is out of stock.');
        }

        $cart = $this->currentCart($request);

        $item = $cart->items()->firstOrNew([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ]);
        $item->quantity = min($availableStock, ($item->exists ? $item->quantity : 0) + $quantity);
        $item->save();

        return $this->respondSuccess($request, $cart, 'Added to cart.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $this->authorizeItem($request, $cartItem);

        $validator = Validator::make($request->all(), [
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->respondError($request, $validator->errors()->first());
        }

        $availableStock = $cartItem->availableStock();
        $cartItem->update([
            'quantity' => min($validator->validated()['quantity'], max($availableStock, 1)),
        ]);

        return $this->respondSuccess($request, $cartItem->cart, 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $this->authorizeItem($request, $cartItem);

        $cart = $cartItem->cart;
        $cartItem->delete();

        return $this->respondSuccess($request, $cart, 'Item removed from cart.');
    }

    private function currentCart(Request $request): Cart
    {
        return Cart::firstOrCreate(['session_id' => $request->session()->getId()]);
    }

    private function currentDiscount(Cart $cart): float
    {
        if (! $cart->coupon) {
            return 0.0;
        }

        $result = $cart->coupon->isValidFor($cart);

        return $result['valid'] ? $result['discount'] : 0.0;
    }

    private function authorizeItem(Request $request, CartItem $cartItem): void
    {
        abort_unless($cartItem->cart->session_id === $request->session()->getId(), 403);
    }

    private function respondSuccess(Request $request, Cart $cart, string $message): RedirectResponse|JsonResponse
    {
        if (! $request->wantsJson()) {
            return back()->with('success', $message);
        }

        $cart->loadMissing('coupon');
        $items = $cart->items()->with(['product.media', 'variant'])->get();
        $subtotal = $items->sum(fn (CartItem $item) => $item->unitPrice() * $item->quantity);

        return response()->json([
            'success' => true,
            'message' => $message,
            'cartCount' => (int) $items->sum('quantity'),
            'html' => view('partials.cart-drawer-items', [
                'items' => $items,
                'subtotal' => $subtotal,
                'discount' => $this->currentDiscount($cart),
                'couponCode' => $cart->coupon?->code,
                'activeOffers' => $this->cachedActiveOffers(),
            ])->render(),
        ]);
    }

    // Same cache keys as SiteDataComposer — this partial is rendered
    // standalone here (fetch responses), bypassing the layout's composer.
    private function cachedActiveOffers(): array
    {
        return Cache::remember(
            'site.active_offers',
            3600,
            fn () => Offer::active()->ordered()->pluck('text')->toArray()
        );
    }

    private function respondError(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if (! $request->wantsJson()) {
            return back()->with('error', $message);
        }

        return response()->json(['success' => false, 'message' => $message], 422);
    }
}
