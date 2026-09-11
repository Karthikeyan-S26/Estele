<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentManager $payments) {}

    public function show(Order $order)
    {
        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.confirmation', $order);
        }

        if (blank($order->razorpay_order_id)) {
            // createOrder() at checkout time failed (transient gateway
            // outage) — the order itself is safe (stock/coupon already
            // committed), so retry once here rather than stranding it.
            try {
                $razorpayOrder = $this->payments->createOrder($order);
                $order->update(['razorpay_order_id' => $razorpayOrder['id']]);
            } catch (\Throwable $e) {
                report($e);

                return view('payment.unavailable', compact('order'));
            }
        }

        return view('payment.show', [
            'order' => $order,
            'razorpayKeyId' => $this->payments->keyId(),
            'amountPaise' => $this->payments->amountInPaise($order),
        ]);
    }

    public function callback(Order $order, Request $request)
    {
        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        // Defends against a tampered form re-targeting a different order —
        // the signature alone proves the payment is genuine, not that it was
        // ever meant for *this* order.
        if ($validated['razorpay_order_id'] !== $order->razorpay_order_id) {
            return redirect()->route('payment.show', $order)
                ->with('error', 'This payment does not match the order. Please try again.');
        }

        $valid = $this->payments->verifyPaymentSignature(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature'],
        );

        if (! $valid) {
            Log::warning('Razorpay payment signature verification failed.', ['order_number' => $order->order_number]);

            return redirect()->route('payment.show', $order)
                ->with('error', 'We couldn\'t verify that payment. Please try again.');
        }

        $this->payments->markPaid($order, $validated['razorpay_payment_id']);

        return redirect()->route('checkout.confirmation', $order)->with('success', 'Order placed successfully.');
    }

    public function webhook(Request $request)
    {
        $signature = (string) $request->header('X-Razorpay-Signature');

        if (! $this->payments->verifyWebhookSignature($request->getContent(), $signature)) {
            Log::warning('Razorpay webhook signature verification failed.');

            return response()->json(['status' => 'invalid signature'], 400);
        }

        $event = $request->input('event');
        $razorpayOrderId = $request->input('payload.payment.entity.order_id')
            ?? $request->input('payload.order.entity.id');

        $order = Order::where('razorpay_order_id', $razorpayOrderId)->first();

        // An unknown order (deleted/never existed) or an event type we don't
        // act on isn't an error worth a retry storm — Razorpay retries hard
        // on non-2xx, so acknowledge and move on.
        if (! $order) {
            return response()->json(['status' => 'ok']);
        }

        match ($event) {
            'payment.captured' => $this->payments->markPaid($order, (string) $request->input('payload.payment.entity.id')),
            'payment.failed' => $this->payments->markFailed($order),
            default => null,
        };

        return response()->json(['status' => 'ok']);
    }
}
