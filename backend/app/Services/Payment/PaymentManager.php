<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Single entry point checkout/webhook code calls for online payment. Which
 * provider is active is a Setting (payment_provider), not code — same
 * config-change-not-a-rewrite approach as ShippingManager.
 */
class PaymentManager
{
    /**
     * payment_status values that must never be overwritten by a late/replayed
     * gateway event — a captured-after-refund or failed-after-paid race must
     * not downgrade a settled order.
     */
    private const SETTLED_STATUSES = ['paid', 'refunded', 'partially_refunded'];

    public function __construct(private readonly RazorpayGateway $razorpay) {}

    public function isOnlinePaymentEnabled(): bool
    {
        return $this->activeProvider() === 'razorpay' && $this->razorpay->isConfigured();
    }

    /**
     * @return array{id: string, amount: int, currency: string}
     */
    public function createOrder(Order $order): array
    {
        return $this->razorpay->createOrder($order);
    }

    public function keyId(): ?string
    {
        return config('services.razorpay.key_id');
    }

    public function amountInPaise(Order $order): int
    {
        return $this->razorpay->rupeesToPaise($order->total);
    }

    public function verifyPaymentSignature(string $razorpayOrderId, string $razorpayPaymentId, string $signature): bool
    {
        return $this->razorpay->verifyPaymentSignature($razorpayOrderId, $razorpayPaymentId, $signature);
    }

    public function verifyWebhookSignature(string $rawBody, string $signatureHeader): bool
    {
        return $this->razorpay->verifyWebhookSignature($rawBody, $signatureHeader);
    }

    public function markPaid(Order $order, string $razorpayPaymentId): void
    {
        if (in_array($order->payment_status, self::SETTLED_STATUSES, true)) {
            Log::info('Ignoring repeat "paid" signal for an already-settled order.', ['order_number' => $order->order_number]);

            return;
        }

        $order->update([
            'payment_status' => 'paid',
            'payment_reference' => $razorpayPaymentId,
        ]);
    }

    public function markFailed(Order $order): void
    {
        if (in_array($order->payment_status, self::SETTLED_STATUSES, true)) {
            Log::warning('Ignoring "failed" signal for an already-settled order — a captured event likely raced this one.', ['order_number' => $order->order_number]);

            return;
        }

        $order->update(['payment_status' => 'failed']);
    }

    private function activeProvider(): string
    {
        return $this->settings()['payment_provider'] ?? 'cod';
    }

    private function settings(): array
    {
        return Cache::remember('site.settings', 3600, fn () => Setting::pluck('value', 'key')->toArray());
    }
}
