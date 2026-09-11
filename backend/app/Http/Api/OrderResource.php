<?php

namespace App\Http\Api;

use App\Models\Order;

/**
 * Normalizes an Order into the JSON shape the Flutter app renders order lists,
 * order detail, and the confirmation screen from. Shared by the Checkout and
 * Account API controllers (no controller-instantiation tricks).
 */
class OrderResource
{
    public static function payload(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'shipping_address' => [
                'line1' => $order->shipping_address_line1,
                'line2' => $order->shipping_address_line2,
                'city' => $order->shipping_city,
                'state' => $order->shipping_state,
                'postal_code' => $order->shipping_postal_code,
                'country' => $order->shipping_country,
            ],
            'order_note' => $order->order_note,
            'subtotal' => (float) $order->subtotal,
            'coupon_code' => $order->coupon_code,
            'discount_amount' => (float) $order->discount_amount,
            'shipping_fee' => (float) $order->shipping_fee,
            'total' => (float) $order->total,
            'wallet_amount_used' => (float) $order->wallet_amount_used,
            'payment_method' => strtoupper($order->payment_method),
            'payment_status' => $order->payment_status,
            'status' => $order->status,
            'tracking_number' => $order->tracking_number,
            'carrier' => $order->carrier,
            'cancellation_requested' => $order->cancellation_requested_at !== null,
            'cancellation_reason' => $order->cancellation_reason,
            'placed_at' => $order->created_at?->toIso8601String(),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_title' => $item->product_title,
                'sku' => $item->sku,
                'price' => (float) $item->price,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
                'variant_attributes' => $item->variant?->attributes,
            ])->values(),
        ];
    }
}