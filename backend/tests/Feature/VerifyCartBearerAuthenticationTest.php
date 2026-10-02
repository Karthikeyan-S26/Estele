<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Regression cover for the cart-auth fix in Api\CartController::currentCart().
 *
 * The mobile cart routes are deliberately NOT wrapped in the auth:api-token
 * middleware - guests must still reach them with an X-Cart-Token header. That
 * means $request->user() resolves through the *web* (session) guard and is
 * always null here, so an authenticated mobile caller used to fall through to
 * the guest branch and read a phantom empty session cart while checkout was
 * already using the merged user cart. currentCart() therefore resolves the user
 * explicitly through auth('api-token')->user().
 *
 * These tests pin that behaviour: the bearer token must select the signed-in
 * user's own cart, never a session cart, and one user's token must never expose
 * another user's cart.
 */
class VerifyCartBearerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

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

    /** Seed a cart that belongs to the user, holding one product. */
    private function cartWithProduct(User $user, Product $product, int $quantity = 2): Cart
    {
        $cart = Cart::create(['user_id' => $user->id]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);

        return $cart;
    }

    public function test_bearer_token_authenticates_as_that_exact_user_and_not_the_web_guard(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken();

        $this->withToken($token)
            ->getJson('/api/cart')
            ->assertOk();

        // The api-token guard is what currentCart() consults, and it must be the
        // bearer holder. $request->user() is the web guard and is null on this
        // route - the whole reason the fix exists.
        $this->assertTrue(Auth::guard('api-token')->check());
        $this->assertTrue(Auth::guard('api-token')->user()->is($user));
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_bearer_token_reads_the_users_cart_instead_of_a_phantom_session_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::create($this->productAttrs('bearer', 450));
        $cart = $this->cartWithProduct($user, $product, 2);

        $response = $this->withToken($user->createApiToken())
            ->getJson('/api/cart')
            ->assertOk();

        // The user's real item is present - an empty session cart would show 0.
        $response->assertJsonPath('data.cart_count', 2)
            ->assertJsonPath('data.items.0.product.id', $product->id)
            ->assertJsonPath('data.totals.subtotal', 900);

        // No guest/session cart may be created or read for a bearer caller.
        $this->assertSame(0, Cart::whereNotNull('session_id')->count());
        $this->assertNull(
            Cart::where('user_id', $user->id)->whereNotNull('session_id')->first()
        );
        $this->assertTrue($cart->items()->exists());
    }

    public function test_another_users_token_cannot_see_the_first_users_cart(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $product = Product::create($this->productAttrs('owned', 300));
        $this->cartWithProduct($owner, $product, 1);

        $response = $this->withToken($intruder->createApiToken())
            ->getJson('/api/cart')
            ->assertOk();

        // The second user gets their own empty cart, never the owner's items.
        $response->assertJsonPath('data.cart_count', 0)
            ->assertJsonPath('data.items', []);

        $this->assertTrue(Auth::guard('api-token')->user()->is($intruder));

        $intruderCart = Cart::where('user_id', $intruder->id)->first();
        $this->assertNotNull($intruderCart);
        $this->assertNotSame(
            Cart::where('user_id', $owner->id)->first()->id,
            $intruderCart->id
        );
        $this->assertSame(0, $intruderCart->items()->count());

        // The owner's cart is untouched by the intruder's read.
        $this->assertSame(1, Cart::where('user_id', $owner->id)->first()->items()->count());
    }

    public function test_bearer_token_wins_over_the_apps_cart_token_header(): void
    {
        // This is the exact shape the Flutter app sends once logged in: a bearer
        // token *and* the persisted device X-Cart-Token. Pre-fix this silently
        // fell through to the guest branch and answered 200 with the device's
        // empty session cart, so the bug showed up as lost carts, not an error.
        $user = User::factory()->create();
        $product = Product::create($this->productAttrs('both', 500));
        $this->cartWithProduct($user, $product, 1);

        $response = $this->withToken($user->createApiToken())
            ->withHeader('X-Cart-Token', 'device-uuid-1234')
            ->getJson('/api/cart')
            ->assertOk();

        $response->assertJsonPath('data.cart_count', 1)
            ->assertJsonPath('data.items.0.product.id', $product->id);

        // The authenticated caller must never have the device session cart built
        // for them, and must not have written into one.
        $this->assertSame(0, Cart::where('session_id', 'device-uuid-1234')->count());
        $this->assertSame(0, Cart::whereNotNull('session_id')->count());
    }

    public function test_a_bearer_caller_cannot_write_into_another_users_cart(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $product = Product::create($this->productAttrs('guard', 250));
        $ownerCart = $this->cartWithProduct($owner, $product, 1);
        $ownerItem = $ownerCart->items()->first();

        // PATCH /cart/items/{id} resolves the cart from the bearer token, so the
        // owner item is out of the intruder's cart and must be refused with 403.
        $this->withToken($intruder->createApiToken())
            ->patchJson("/api/cart/items/{$ownerItem->id}", ['quantity' => 99])
            ->assertForbidden();

        $this->assertDatabaseHas('cart_items', [
            'id' => $ownerItem->id,
            'quantity' => 1,
        ]);
    }

    public function test_guest_without_a_cart_token_is_still_rejected(): void
    {
        // The fix must not weaken the guest path: no bearer token and no
        // X-Cart-Token still fails loudly rather than inventing a cart.
        $this->getJson('/api/cart')->assertStatus(422);
    }

    public function test_guest_with_a_cart_token_gets_a_session_cart_not_a_user_cart(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeader('X-Cart-Token', 'device-uuid-1234')
            ->getJson('/api/cart')
            ->assertOk();

        $response->assertJsonPath('data.cart_count', 0);

        $this->assertNotNull(Cart::where('session_id', 'device-uuid-1234')->first());
        $this->assertSame(0, Cart::where('user_id', $user->id)->count());
    }
}