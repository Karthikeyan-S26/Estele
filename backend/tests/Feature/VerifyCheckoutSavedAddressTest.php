<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Checkout can be placed either by typing a new address (existing behaviour,
 * unchanged) or by picking one already saved to the account — this covers
 * the address_id path: resolving it server-side, the ownership boundary
 * that stops one shopper from placing against another's saved address, and
 * that the "type a new address" path still works when address_id is absent.
 */
class VerifyCheckoutSavedAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_with_address_id_uses_the_saved_address(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);
        $address = Address::create([
            'user_id' => $user->id,
            'label' => 'Home',
            'line1' => '221B Baker Street',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'postal_code' => '500001',
            'country' => 'India',
            'phone' => '9999999999',
        ]);
        [$sessionCookieName, $sessionCookieValue] = $this->seedCart($user, $this->makeProduct(500));

        $response = $this->withUnencryptedCookie($sessionCookieName, $sessionCookieValue)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post('/checkout', [
                'address_id' => $address->id,
                'customer_email' => 'jane@example.com',
                'payment_method' => 'cod',
            ]);

        $response->assertRedirect();
        $order = Order::where('customer_email', 'jane@example.com')->first();
        $this->assertNotNull($order);
        $this->assertSame('221B Baker Street', $order->shipping_address_line1);
        $this->assertSame('Hyderabad', $order->shipping_city);
        $this->assertSame('Telangana', $order->shipping_state);
        $this->assertSame('500001', $order->shipping_postal_code);
        $this->assertSame('9999999999', $order->customer_phone);
        $this->assertSame('Jane Doe', $order->customer_name);
    }

    public function test_checkout_rejects_an_address_id_belonging_to_another_user(): void
    {
        $owner = User::factory()->create();
        $otherAddress = Address::create([
            'user_id' => $owner->id,
            'label' => 'Home',
            'line1' => 'Someone Else\'s House',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country' => 'India',
            'phone' => '8888888888',
        ]);

        $attacker = User::factory()->create();
        [$sessionCookieName, $sessionCookieValue] = $this->seedCart($attacker, $this->makeProduct(500));

        $response = $this->withUnencryptedCookie($sessionCookieName, $sessionCookieValue)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post('/checkout', [
                'address_id' => $otherAddress->id,
                'customer_email' => 'attacker@example.com',
                'payment_method' => 'cod',
            ]);

        $response->assertRedirect(route('checkout.index'));
        $this->assertNull(Order::where('customer_email', 'attacker@example.com')->first(), 'No order should be created against another user\'s address.');
    }

    public function test_checkout_without_address_id_still_accepts_manual_entry(): void
    {
        $user = User::factory()->create();
        [$sessionCookieName, $sessionCookieValue] = $this->seedCart($user, $this->makeProduct(500));

        $response = $this->withUnencryptedCookie($sessionCookieName, $sessionCookieValue)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post('/checkout', [
                'customer_first_name' => 'Manual',
                'customer_last_name' => 'Entry',
                'customer_email' => 'manual@example.com',
                'customer_phone' => '7777777777',
                'shipping_address_line1' => '1 New St',
                'shipping_city' => 'Pune',
                'shipping_state' => 'Maharashtra',
                'shipping_postal_code' => '411001',
                'payment_method' => 'cod',
            ]);

        $response->assertRedirect();
        $order = Order::where('customer_email', 'manual@example.com')->first();
        $this->assertNotNull($order);
        $this->assertSame('Manual Entry', $order->customer_name);
        $this->assertSame('1 New St', $order->shipping_address_line1);
    }

    public function test_checkout_without_address_id_and_without_manual_fields_fails_validation(): void
    {
        $user = User::factory()->create();
        [$sessionCookieName, $sessionCookieValue] = $this->seedCart($user, $this->makeProduct(500));

        $response = $this->withUnencryptedCookie($sessionCookieName, $sessionCookieValue)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post('/checkout', [
                'customer_email' => 'incomplete@example.com',
                'payment_method' => 'cod',
            ]);

        // assertSessionHasErrors() reads the global session() helper, which
        // this test's manually-swapped database-session cookie (needed to
        // carry seedCart()'s cart across the request) doesn't reliably
        // reload from — asserting on the response's own session avoids that
        // false negative. Confirmed by inspecting $response->getSession()
        // directly: the errors bag is genuinely there with all 7 messages.
        $errors = $response->getSession()->get('errors');
        $this->assertNotNull($errors, 'Expected a validation errors bag in the response session.');
        foreach (['customer_first_name', 'shipping_address_line1', 'shipping_city', 'shipping_postal_code'] as $field) {
            $this->assertTrue($errors->has($field), "Expected a validation error for [{$field}].");
        }
        $this->assertNull(Order::where('customer_email', 'incomplete@example.com')->first());
    }

    private function makeProduct(float $price): Product
    {
        return Product::create([
            'title' => 'Saved Address Test Product',
            'slug' => 'saved-address-test-product-'.uniqid(),
            'sku' => 'SKU-ADDR-'.uniqid(),
            'price' => $price,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: string, 1: string} [sessionCookieName, sessionCookieValue]
     */
    private function seedCart(User $user, Product $product): array
    {
        $this->actingAs($user);
        config(['session.driver' => 'database']);

        $firstResponse = $this->get('/cart');
        $sessionCookieName = config('session.cookie');
        $sessionCookieValue = collect($firstResponse->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $sessionCookieName)
            ->getValue();
        $sessionId = DB::table('sessions')->orderByDesc('last_activity')->value('id');

        $cart = Cart::firstOrCreate(['session_id' => $sessionId]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        return [$sessionCookieName, $sessionCookieValue];
    }
}
