<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\Payment\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The abandoned-order sweep (and any other cancellation) races a late
 * Razorpay payment: the SDK callback, the API retry, and the durable webhook
 * can all arrive after the order was cancelled. These cases pin the state
 * machine so a cancelled order is terminal — a captured payment is recorded
 * as an audit trail (payment_reference + admin_notes) and never flips the
 * order back to paid or re-restocks it, an already-settled order is never
 * downgraded, and every success path through PaymentManager honours the same
 * guard.
 */
class VerifyCancelledOrderPaymentGuardTest extends TestCase
{
    use RefreshDatabase;

    private const KEY_SECRET = 'fake_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.razorpay.key_id' => 'rzp_test_fake', 'services.razorpay.key_secret' => self::KEY_SECRET]);
    }

    public function test_mark_paid_on_a_cancelled_order_does_not_resurrect_it(): void
    {
        $order = $this->makeOrder(['status' => 'cancelled']);

        app(PaymentManager::class)->markPaid($order, 'pay_race1');

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('pending', $order->payment_status, 'A captured payment must never flip a cancelled order to paid.');
        $this->assertSame('pay_race1', $order->payment_reference);
        $this->assertStringContainsString('pay_race1', $order->admin_notes);
    }

    public function test_mark_paid_duplicate_capture_is_idempotent(): void
    {
        $order = $this->makeOrder(['status' => 'cancelled']);

        app(PaymentManager::class)->markPaid($order, 'pay_dup');
        $note = $order->fresh()->admin_notes;
        app(PaymentManager::class)->markPaid($order, 'pay_dup');

        $this->assertSame($note, $order->fresh()->admin_notes, 'A replayed capture must not append a second audit note.');
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_a_second_distinct_capture_keeps_the_first_reference_and_appends_a_note(): void
    {
        $order = $this->makeOrder(['status' => 'cancelled']);

        app(PaymentManager::class)->markPaid($order, 'pay_first');
        app(PaymentManager::class)->markPaid($order, 'pay_second');

        $order->refresh();
        $this->assertSame('pay_first', $order->payment_reference);
        $this->assertStringContainsString('pay_first', $order->admin_notes);
        $this->assertStringContainsString('pay_second', $order->admin_notes);
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_mark_failed_does_not_touch_a_cancelled_order(): void
    {
        $order = $this->makeOrder(['status' => 'cancelled']);

        app(PaymentManager::class)->markFailed($order);

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->payment_reference);
    }

    public function test_mark_paid_never_downgrades_an_already_refunded_order(): void
    {
        $order = $this->makeOrder([
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'payment_reference' => 'pay_orig',
            'refunded_amount' => 500,
        ]);

        app(PaymentManager::class)->markPaid($order, 'pay_late');

        $order->refresh();
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame('pay_orig', $order->payment_reference);
    }

    public function test_webhook_captured_for_a_cancelled_order_is_acknowledged_but_not_paid(): void
    {
        config(['services.razorpay.webhook_secret' => 'whsec_fake']);
        $order = $this->makeOrder(['status' => 'cancelled']);

        $response = $this->postSignedWebhook([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_wh1', 'order_id' => 'order_fake123']]],
        ]);

        $response->assertOk();
        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pay_wh1', $order->payment_reference);
        $this->assertStringContainsString('pay_wh1', $order->admin_notes);
    }

    public function test_webhook_failed_for_a_cancelled_order_leaves_payment_status_untouched(): void
    {
        config(['services.razorpay.webhook_secret' => 'whsec_fake']);
        $order = $this->makeOrder(['status' => 'cancelled']);

        $response = $this->postSignedWebhook([
            'event' => 'payment.failed',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_wh1', 'order_id' => 'order_fake123']]],
        ]);

        $response->assertOk();
        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->payment_reference);
    }

    public function test_api_payment_callback_for_a_cancelled_order_acknowledges_without_paying(): void
    {
        $order = $this->makeOrder(['status' => 'cancelled']);
        $paymentId = 'pay_api1';
        $signature = hash_hmac('sha256', "order_fake123|{$paymentId}", self::KEY_SECRET);

        $response = $this->postJson("/api/payment/callback/{$order->order_number}", [
            'razorpay_order_id' => 'order_fake123',
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.order.status', 'cancelled');
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame($paymentId, $order->fresh()->payment_reference);
    }

    public function test_api_retry_for_a_cancelled_order_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = PersonalAccessToken::issue($user);
        $order = $this->makeOrder(['status' => 'cancelled', 'user_id' => $user->id, 'razorpay_order_id' => null]);

        $response = $this->withToken($token)
            ->postJson("/api/payment/{$order->order_number}/retry");

        $response->assertStatus(422);
        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertNull($order->razorpay_order_id, 'A rejected retry must not create a Razorpay order.');
    }

    public function test_api_retry_rejects_any_order_that_is_no_longer_placed(): void
    {
        $user = User::factory()->create();
        $token = PersonalAccessToken::issue($user);
        $order = $this->makeOrder(['status' => 'shipped', 'user_id' => $user->id]);

        $response = $this->withToken($token)
            ->postJson("/api/payment/{$order->order_number}/retry");

        $response->assertStatus(422);
        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_web_payment_show_for_a_cancelled_order_redirects_home_without_mutation(): void
    {
        $order = $this->makeOrder(['status' => 'cancelled', 'razorpay_order_id' => null]);

        $response = $this->get(route('payment.show', $order));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error', 'This order was cancelled before payment completed.');
        $order->refresh();
        $this->assertNull($order->razorpay_order_id, 'The payment page must not mint a new gateway order for a cancelled order.');
    }

    public function test_web_payment_callback_for_a_cancelled_order_redirects_home_and_records_the_capture(): void
    {
        $order = $this->makeOrder(['status' => 'cancelled']);
        $paymentId = 'pay_web1';
        $signature = hash_hmac('sha256', "order_fake123|{$paymentId}", self::KEY_SECRET);

        $response = $this->post(route('payment.callback', $order), [
            'razorpay_order_id' => 'order_fake123',
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error', 'This order was cancelled before your payment completed. Your payment has been recorded; please contact support for a refund.');
        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame($paymentId, $order->payment_reference);
        $this->assertStringContainsString($paymentId, $order->admin_notes);
    }

    private function postSignedWebhook(array $payload)
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'whsec_fake');

        return $this->call('POST', route('webhooks.razorpay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Razorpay-Signature' => $signature,
        ], $body);
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
            'payment_method' => 'razorpay',
            'payment_status' => 'pending',
            'razorpay_order_id' => 'order_fake123',
            'status' => 'placed',
        ], $overrides));
    }
}
