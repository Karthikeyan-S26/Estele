<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PaymentController::callback() is the client-side redirect Razorpay's
 * checkout.js posts to after a payment attempt. Confirms a genuinely signed
 * payment marks the order paid, and a tampered/invalid signature leaves the
 * order untouched rather than trusting client-supplied data at face value.
 */
class VerifyRazorpayCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_signature_marks_the_order_paid(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config(['services.razorpay.key_secret' => 'fake_secret']);

        $order = $this->makePendingOrder('order_fake123');
        $signature = hash_hmac('sha256', 'order_fake123|pay_fake456', 'fake_secret');

        $response = $this->post(route('payment.callback', $order), [
            'razorpay_order_id' => 'order_fake123',
            'razorpay_payment_id' => 'pay_fake456',
            'razorpay_signature' => $signature,
        ]);

        $response->assertRedirect(route('checkout.confirmation', $order));
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('pay_fake456', $order->payment_reference);
    }

    public function test_invalid_signature_does_not_mark_the_order_paid(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config(['services.razorpay.key_secret' => 'fake_secret']);

        $order = $this->makePendingOrder('order_fake123');

        $response = $this->post(route('payment.callback', $order), [
            'razorpay_order_id' => 'order_fake123',
            'razorpay_payment_id' => 'pay_fake456',
            'razorpay_signature' => 'tampered-signature',
        ]);

        $response->assertRedirect(route('payment.show', $order));
        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->payment_reference);
    }

    public function test_callback_rejects_a_signature_for_a_different_order(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config(['services.razorpay.key_secret' => 'fake_secret']);

        $order = $this->makePendingOrder('order_fake123');
        // A genuinely valid signature, but for a razorpay_order_id that
        // doesn't belong to this order — must not be trusted just because
        // the HMAC checks out for *some* order.
        $signature = hash_hmac('sha256', 'order_other|pay_fake456', 'fake_secret');

        $response = $this->post(route('payment.callback', $order), [
            'razorpay_order_id' => 'order_other',
            'razorpay_payment_id' => 'pay_fake456',
            'razorpay_signature' => $signature,
        ]);

        $response->assertRedirect(route('payment.show', $order));
        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
    }

    private function makePendingOrder(string $razorpayOrderId): Order
    {
        return Order::create([
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
            'payment_method' => 'razorpay',
            'payment_status' => 'pending',
            'razorpay_order_id' => $razorpayOrderId,
            'status' => 'placed',
        ]);
    }
}
