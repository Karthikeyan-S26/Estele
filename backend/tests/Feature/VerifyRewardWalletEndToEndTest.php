<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RewardSubmission;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Exercises the whole customer journey across the reward-submission and
 * wallet features in one pass, rather than each piece in isolation:
 * a reward gets approved and credited, the resulting wallet balance is
 * spent at checkout on a brand-new order (through the real checkout HTTP
 * flow, not a direct WalletService call), and cancelling that order refunds
 * the wallet back through the Order::booted() `updated` hook. Finally, the
 * full chain of wallet_transactions rows is checked for running-balance
 * consistency, since a bug in any one of the three write paths (credit,
 * checkout debit, cancel-refund credit) would otherwise leave the wallet
 * balance right by coincidence while the ledger itself was wrong.
 */
class VerifyRewardWalletEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_reward_to_wallet_to_checkout_to_refund_journey_is_consistent(): void
    {
        $user = User::factory()->create(['wallet_balance' => 0]);

        // 1. A delivered order the reward submission is claimed against.
        $deliveredOrder = $this->makeOrder($user, ['status' => 'delivered']);

        // 2. Reward submission approved, wallet credited with a known amount.
        $submission = RewardSubmission::create([
            'user_id' => $user->id,
            'order_id' => $deliveredOrder->id,
            'status' => 'pending',
        ]);

        app(WalletService::class)->credit($user, 200.00, 'reward_submission', $submission);

        $user->refresh();
        $this->assertSame('200.00', $user->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => '200.00',
            'reason' => 'reward_submission',
            'balance_after' => '200.00',
        ]);

        // 3. Place a NEW order for the same user, spending part of that
        // wallet balance through the real checkout flow (not a direct
        // WalletService call) — same session-cookie cart-seeding pattern as
        // VerifyCheckoutWalletUsageTest.
        $product = $this->makeProduct(500);
        [$sessionCookieName, $sessionCookieValue] = $this->seedCart($user, $product);

        $response = $this->withUnencryptedCookie($sessionCookieName, $sessionCookieValue)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post('/checkout', array_merge($this->checkoutPayload(), ['wallet_amount' => 120]));

        $response->assertRedirect();

        $newOrder = Order::where('customer_email', 'jane@example.com')->first();
        $this->assertNotNull($newOrder);
        $this->assertSame('120.00', $newOrder->wallet_amount_used);

        $user->refresh();
        $this->assertSame('80.00', $user->wallet_balance, 'Wallet must be debited by exactly the amount used at checkout.');
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => '120.00',
            'reason' => 'order_payment',
            'balance_after' => '80.00',
        ]);

        // 4. Cancel that new order — the wallet must be refunded back to what
        // it was immediately before this checkout (200.00).
        $newOrder->update(['status' => 'cancelled']);

        $user->refresh();
        $this->assertSame('200.00', $user->wallet_balance, 'Cancelling must refund the wallet back to its pre-checkout balance.');
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => '120.00',
            'reason' => 'order_refund',
            'balance_after' => '200.00',
        ]);

        // 5. The full ledger must be internally consistent: each row's
        // balance_after must equal a running sum of that user's prior rows.
        $transactions = WalletTransaction::where('user_id', $user->id)->orderBy('id')->get();
        $this->assertCount(3, $transactions, 'Expected exactly reward-credit, checkout-debit, cancel-refund-credit.');

        $running = 0.0;
        foreach ($transactions as $transaction) {
            $running += $transaction->type === 'credit' ? (float) $transaction->amount : -(float) $transaction->amount;
            $this->assertSame(
                round($running, 2),
                (float) $transaction->balance_after,
                "balance_after on transaction #{$transaction->id} must match the running sum of amounts up to it."
            );
        }

        $this->assertSame(200.00, $running, 'Final running balance must match the wallet balance ending state.');
    }

    private function checkoutPayload(): array
    {
        return [
            'customer_first_name' => 'Jane',
            'customer_last_name' => 'Doe',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '9999999999',
            'shipping_address_line1' => '1 Main St',
            'shipping_city' => 'Hyderabad',
            'shipping_state' => 'Telangana',
            'shipping_postal_code' => '500001',
            'payment_method' => 'cod',
        ];
    }

    private function makeOrder(User $user, array $overrides = []): Order
    {
        $order = Order::create(array_merge([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_name' => $user->name,
            'customer_email' => $user->email,
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
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'status' => 'placed',
        ], $overrides));

        $product = $this->makeProduct(500);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_title' => $product->title,
            'sku' => $product->sku,
            'price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);

        return $order;
    }

    private function makeProduct(float $price): Product
    {
        return Product::create([
            'title' => 'Reward Wallet E2E Product',
            'slug' => 'reward-wallet-e2e-product-'.uniqid(),
            'sku' => 'SKU-E2E-'.uniqid(),
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
