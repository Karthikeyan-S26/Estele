<?php

namespace App\Services\Payment;

use App\Models\Order;

interface PaymentGateway
{
    /**
     * @return array{id: string, amount: int, currency: string}
     */
    public function createOrder(Order $order): array;

    public function verifyPaymentSignature(string $razorpayOrderId, string $razorpayPaymentId, string $signature): bool;

    public function verifyWebhookSignature(string $rawBody, string $signatureHeader): bool;
}
