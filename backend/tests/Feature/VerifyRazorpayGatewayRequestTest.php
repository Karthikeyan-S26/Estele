<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\Payment\RazorpayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Locks down the exact request Razorpay's Orders API receives, and the paise
 * conversion in particular — Order::total is a decimal:2 numeric string, and
 * a naive float multiply can drift a value like "10.10" to 1009 instead of
 * the correct 1010.
 */
class VerifyRazorpayGatewayRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_sends_amount_in_paise_and_parses_the_response(): void
    {
        config(['services.razorpay.key_id' => 'rzp_test_fake', 'services.razorpay.key_secret' => 'fake_secret']);

        Http::fake([
            '*/v1/orders' => Http::response(['id' => 'order_fake123', 'amount' => 49900, 'currency' => 'INR'], 200),
        ]);

        $order = $this->makeOrder('499.00');

        $result = app(RazorpayGateway::class)->createOrder($order);

        $this->assertSame('order_fake123', $result['id']);

        Http::assertSent(function ($request) use ($order) {
            return $request['amount'] === 49900
                && $request['currency'] === 'INR'
                && $request['receipt'] === $order->order_number;
        });
    }

    public function test_rupees_to_paise_rounds_instead_of_truncating(): void
    {
        config(['services.razorpay.key_id' => 'rzp_test_fake', 'services.razorpay.key_secret' => 'fake_secret']);

        Http::fake(['*/v1/orders' => Http::response(['id' => 'order_fake456'], 200)]);

        $order = $this->makeOrder('10.10');

        app(RazorpayGateway::class)->createOrder($order);

        Http::assertSent(fn ($request) => $request['amount'] === 1010);
    }

    /**
     * A transient 5xx (Razorpay's own infrastructure hiccuping, or the kind
     * of network blip seen against the real API during manual testing) must
     * not surface "payment unavailable" to the customer on the first try —
     * confirms the retry actually recovers instead of just being configured.
     */
    public function test_transient_server_error_is_retried_and_recovers(): void
    {
        config(['services.razorpay.key_id' => 'rzp_test_fake', 'services.razorpay.key_secret' => 'fake_secret']);

        Http::fake([
            '*/v1/orders' => Http::sequence()
                ->push(['error' => ['description' => 'temporary outage']], 500)
                ->push(['id' => 'order_recovered'], 200),
        ]);

        $order = $this->makeOrder('499.00');

        $result = app(RazorpayGateway::class)->createOrder($order);

        $this->assertSame('order_recovered', $result['id']);
    }

    /**
     * A 4xx (bad credentials, malformed request) is deterministic — retrying
     * wastes time and risks creating a duplicate Razorpay order for no
     * benefit, so it must fail immediately rather than exhausting retries.
     */
    public function test_client_error_is_not_retried(): void
    {
        config(['services.razorpay.key_id' => 'rzp_test_fake', 'services.razorpay.key_secret' => 'fake_secret']);

        Http::fake([
            '*/v1/orders' => Http::sequence()
                ->push(['error' => ['description' => 'invalid key']], 401)
                ->push(['id' => 'should_never_be_reached'], 200),
        ]);

        $order = $this->makeOrder('499.00');

        $this->expectException(\Illuminate\Http\Client\RequestException::class);

        app(RazorpayGateway::class)->createOrder($order);
    }

    private function makeOrder(string $total): Order
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
            'subtotal' => $total,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => $total,
            'payment_method' => 'razorpay',
            'payment_status' => 'pending',
            'status' => 'placed',
        ]);
    }
}
