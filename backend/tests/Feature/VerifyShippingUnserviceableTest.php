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
 * Confirms CheckoutController::store() actually blocks the order (not just
 * that ShiprocketService throws) when Shiprocket reports no courier is
 * serviceable for the address — runs against the real route/controller/
 * session stack, in-process, via Http::fake().
 */
class VerifyShippingUnserviceableTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_is_blocked_when_no_courier_is_serviceable(): void
    {
        // /checkout now requires login (Buy It Now / ZappDeal-parity ask).
        $this->actingAs(User::factory()->create());
        $this->withoutMiddleware(ValidateCsrfToken::class);

        // phpunit.xml forces SESSION_DRIVER=array, which doesn't persist
        // between separate test HTTP calls — the cart lookup below needs to
        // survive from the GET to the POST, so use the real 'database'
        // driver (a `sessions` table migration already exists) for this test.
        config(['session.driver' => 'database']);

        Setting::updateOrCreate(['key' => 'shipping_provider'], ['value' => 'shiprocket']);
        config(['services.shiprocket.email' => 'fake@example.com', 'services.shiprocket.password' => 'fake']);

        Http::fake([
            '*/auth/login' => Http::response(['token' => 'faketoken'], 200),
            '*/courier/serviceability/*' => Http::response(['data' => ['available_courier_companies' => []]], 200),
        ]);

        // The test client doesn't carry cookies between separate
        // $this->get()/$this->post() calls automatically — replay the
        // session cookie from the first response on the next request.
        // getCookies()[0] is NOT reliable here: Laravel also sets an
        // XSRF-TOKEN cookie, and it isn't guaranteed to come second.
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
                'shipping_postal_code' => '999999',
                'payment_method' => 'cod',
            ]);

        $response->assertRedirect(route('checkout.index'));
        $response->assertSessionHas('error');
        $this->assertSame(0, Order::count(), 'No order should have been created for an unserviceable address.');
    }
}
