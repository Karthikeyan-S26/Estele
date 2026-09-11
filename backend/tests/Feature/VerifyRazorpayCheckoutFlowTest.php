<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Confirms CheckoutController::store() actually creates a "razorpay" order
 * (stock decremented, payment_status pending, razorpay_order_id populated)
 * and redirects to the payment page — through the real route/controller/
 * session stack, not just PaymentManager in isolation.
 */
class VerifyRazorpayCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression test: CheckoutController::index() must pass
     * onlinePaymentEnabled directly rather than relying on SiteDataComposer
     * (bound to the 'layouts.app' view) — @extends only renders the parent
     * layout at the very end of the compiled child template, so composer
     * data is never visible inside checkout/index.blade.php's own
     * @section('content') body. This slipped past every other test here
     * because none of them render the checkout GET page, only POST to it.
     */
    public function test_checkout_page_renders_with_online_payment_enabled(): void
    {
        // /checkout now requires login (Buy It Now / ZappDeal-parity ask) —
        // actingAs() authenticates the guard for every request this test
        // makes without needing its own cookie replay, independent of the
        // manual session-cookie dance below (that's purely for the
        // session_id-keyed cart, a separate mechanism from auth).
        $this->actingAs(User::factory()->create());
        config(['session.driver' => 'database']);
        Setting::updateOrCreate(['key' => 'payment_provider'], ['value' => 'razorpay']);
        config(['services.razorpay.key_id' => 'rzp_test_fake', 'services.razorpay.key_secret' => 'fake_secret']);

        $firstResponse = $this->get('/cart');
        $sessionCookieName = config('session.cookie');
        $sessionCookieValue = collect($firstResponse->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $sessionCookieName)
            ->getValue();
        $sessionId = DB::table('sessions')->orderByDesc('last_activity')->value('id');

        $product = Product::create([
            'title' => 'Test Product',
            'slug' => 'test-product-'.uniqid(),
            'sku' => 'TEST-'.uniqid(),
            'price' => 500,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
        $cart = Cart::firstOrCreate(['session_id' => $sessionId]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->withUnencryptedCookie($sessionCookieName, $sessionCookieValue)->get('/checkout');

        $response->assertOk();
        $response->assertSee('name="payment_method" value="razorpay"', false);
        $response->assertDontSee('Online payment is coming soon.');
    }

    public function test_checkout_with_razorpay_creates_a_pending_order_and_redirects_to_payment(): void
    {
        $this->actingAs(User::factory()->create());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config(['session.driver' => 'database']);

        Setting::updateOrCreate(['key' => 'payment_provider'], ['value' => 'razorpay']);
        config(['services.razorpay.key_id' => 'rzp_test_fake', 'services.razorpay.key_secret' => 'fake_secret']);

        Http::fake([
            '*/v1/orders' => Http::response(['id' => 'order_fake123', 'amount' => 50000, 'currency' => 'INR'], 200),
        ]);

        $firstResponse = $this->get('/cart');
        $sessionCookieName = config('session.cookie');
        $sessionCookieValue = collect($firstResponse->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $sessionCookieName)
            ->getValue();
        $sessionId = DB::table('sessions')->orderByDesc('last_activity')->value('id');

        $product = Product::create([
            'title' => 'Test Product',
            'slug' => 'test-product-'.uniqid(),
            'sku' => 'TEST-'.uniqid(),
            'price' => 500,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
        $cart = Cart::firstOrCreate(['session_id' => $sessionId]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $response = $this
            ->withUnencryptedCookie($sessionCookieName, $sessionCookieValue)
            ->post('/checkout', [
                'customer_first_name' => 'Verify',
                'customer_last_name' => 'Test',
                'customer_email' => 'verify@example.com',
                'customer_phone' => '9999999999',
                'shipping_address_line1' => 'Test St',
                'shipping_city' => 'Hyderabad',
                'shipping_state' => 'Telangana',
                'shipping_postal_code' => '500001',
                'payment_method' => 'razorpay',
            ]);

        $order = Order::where('customer_email', 'verify@example.com')->first();

        $this->assertNotNull($order, 'Order should have been created for the razorpay checkout.');
        $response->assertRedirect(route('payment.show', $order));
        $this->assertSame('razorpay', $order->payment_method);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('order_fake123', $order->razorpay_order_id);
        $this->assertSame(4, $product->fresh()->stock_quantity, 'Stock should be decremented immediately, same as the COD path.');
    }

    public function test_checkout_rejects_razorpay_when_online_payment_is_disabled(): void
    {
        $this->actingAs(User::factory()->create());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config(['session.driver' => 'database']);

        Setting::updateOrCreate(['key' => 'payment_provider'], ['value' => 'cod']);

        $firstResponse = $this->get('/cart');
        $sessionCookieName = config('session.cookie');
        $sessionCookieValue = collect($firstResponse->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $sessionCookieName)
            ->getValue();
        $sessionId = DB::table('sessions')->orderByDesc('last_activity')->value('id');

        $product = Product::create([
            'title' => 'Test Product',
            'slug' => 'test-product-'.uniqid(),
            'sku' => 'TEST-'.uniqid(),
            'price' => 500,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
        $cart = Cart::firstOrCreate(['session_id' => $sessionId]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $response = $this
            ->withUnencryptedCookie($sessionCookieName, $sessionCookieValue)
            ->post('/checkout', [
                'customer_first_name' => 'Verify',
                'customer_last_name' => 'Test',
                'customer_email' => 'verify2@example.com',
                'customer_phone' => '9999999999',
                'shipping_address_line1' => 'Test St',
                'shipping_city' => 'Hyderabad',
                'shipping_state' => 'Telangana',
                'shipping_postal_code' => '500001',
                'payment_method' => 'razorpay',
            ]);

        $response->assertRedirect(route('checkout.index'));
        $response->assertSessionHas('error');
        $this->assertSame(0, Order::where('customer_email', 'verify2@example.com')->count());
    }
}
