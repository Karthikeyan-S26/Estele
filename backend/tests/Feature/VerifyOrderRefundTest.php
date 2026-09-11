<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Order::applyRefund() backs EditOrder's "Refund" admin action — it's
 * bookkeeping-only (doesn't call Razorpay's Refund API, see EditOrder's
 * doc-comment), but the accumulation and full-vs-partial status flip is
 * real logic worth locking down: a second partial refund must add to the
 * first, not replace it, and crossing the order total must flip to
 * "refunded" rather than staying "partially_refunded" forever.
 */
class VerifyOrderRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_refund_marks_the_order_partially_refunded(): void
    {
        $order = $this->makePaidOrder(1000);

        $order->applyRefund(300, 'Customer requested a partial refund.');

        $order->refresh();
        $this->assertSame('partially_refunded', $order->payment_status);
        $this->assertSame('300.00', $order->refunded_amount);
        $this->assertSame('Customer requested a partial refund.', $order->refund_reason);
    }

    public function test_refund_covering_the_full_total_marks_the_order_refunded(): void
    {
        $order = $this->makePaidOrder(1000);

        $order->applyRefund(1000, 'Order cancelled by customer.');

        $this->assertSame('refunded', $order->fresh()->payment_status);
    }

    public function test_a_second_partial_refund_accumulates_on_top_of_the_first(): void
    {
        $order = $this->makePaidOrder(1000);

        $order->applyRefund(300, 'First partial refund.');
        $order->applyRefund(400, 'Second partial refund.');

        $order->refresh();
        $this->assertSame('700.00', $order->refunded_amount);
        $this->assertSame('partially_refunded', $order->payment_status, 'Still short of the 1000 total.');
    }

    public function test_accumulated_refunds_crossing_the_total_flip_to_fully_refunded(): void
    {
        $order = $this->makePaidOrder(1000);

        $order->applyRefund(600, 'First partial refund.');
        $order->applyRefund(400, 'Final refund covering the rest.');

        $this->assertSame('refunded', $order->fresh()->payment_status);
    }

    private function makePaidOrder(float $total): Order
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
            'payment_status' => 'paid',
            'status' => 'placed',
        ]);
    }
}
