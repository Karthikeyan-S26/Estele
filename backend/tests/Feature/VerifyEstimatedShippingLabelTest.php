<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Confirms the "(estimated)" label actually reaches the rendered cart HTML
 * when the active provider is pincode-dependent and no pincode is known
 * yet — not just that ShippingManager::quote() returns estimated: true
 * in isolation.
 */
class VerifyEstimatedShippingLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_page_shows_estimated_label_when_shiprocket_active_without_pincode(): void
    {
        config(['session.driver' => 'database']);
        Setting::updateOrCreate(['key' => 'shipping_provider'], ['value' => 'shiprocket']);
        Setting::updateOrCreate(['key' => 'shipping_free_threshold'], ['value' => '999']);
        Setting::updateOrCreate(['key' => 'shipping_flat_rate'], ['value' => '79']);
        config(['services.shiprocket.email' => 'fake@example.com', 'services.shiprocket.password' => 'fake']);

        $response = $this->requestCartWithItem();

        $response->assertOk();
        $response->assertSee('(estimated)', false);
    }

    public function test_cart_page_does_not_show_estimated_label_under_flat_provider(): void
    {
        config(['session.driver' => 'database']);
        Setting::updateOrCreate(['key' => 'shipping_provider'], ['value' => 'flat']);
        Setting::updateOrCreate(['key' => 'shipping_free_threshold'], ['value' => '999']);
        Setting::updateOrCreate(['key' => 'shipping_flat_rate'], ['value' => '79']);

        $response = $this->requestCartWithItem();

        $response->assertOk();
        $response->assertDontSee('(estimated)', false);
    }

    /**
     * Adds one item to a real cart, tied to the same session across both
     * calls, and returns the second GET /cart response. The test client
     * doesn't carry cookies between separate calls automatically, and
     * getCookies()[0] isn't reliable (Laravel also sets an XSRF-TOKEN
     * cookie) — grab the session cookie by name and replay it explicitly.
     */
    private function requestCartWithItem()
    {
        $firstResponse = $this->get('/cart');
        $cookieName = config('session.cookie');
        $cookieValue = collect($firstResponse->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $cookieName)
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

        return $this->withUnencryptedCookie($cookieName, $cookieValue)->get('/cart');
    }
}
