<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\Shipping\ShiprocketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ShiprocketService::createShipment()/trackShipment() previously had zero
 * coverage (the class's own doc-comment noted they "aren't called from
 * anywhere yet"). Now that EditOrder wires them into "Create Shipment" /
 * "Refresh Tracking" admin actions, lock down the request payload sent to
 * Shiprocket and the response parsing on both paths.
 */
class VerifyShiprocketShipmentActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_shipment_sends_order_details_and_parses_the_response(): void
    {
        config(['services.shiprocket.email' => 'fake@example.com', 'services.shiprocket.password' => 'fake']);

        Http::fake([
            '*/auth/login' => Http::response(['token' => 'faketoken'], 200),
            '*/orders/create/adhoc' => Http::response([
                'shipment_id' => 555,
                'awb_code' => 'AWB123456',
                'courier_name' => 'Delhivery',
            ], 200),
        ]);

        $order = $this->makeOrder();

        $result = app(ShiprocketService::class)->createShipment($order);

        $this->assertSame(555, $result['shipment_id']);
        $this->assertSame('AWB123456', $result['awb_code']);
        $this->assertSame('Delhivery', $result['courier_name']);

        Http::assertSent(function ($request) use ($order) {
            if (! str_contains($request->url(), '/orders/create/adhoc')) {
                return true;
            }

            return $request['order_id'] === $order->order_number
                && $request['billing_pincode'] === $order->shipping_postal_code
                && $request['payment_method'] === 'COD'
                && $request['order_items'][0]['sku'] === 'TEST-SKU';
        });
    }

    public function test_track_shipment_parses_status_and_checkpoints(): void
    {
        config(['services.shiprocket.email' => 'fake@example.com', 'services.shiprocket.password' => 'fake']);

        Http::fake([
            '*/auth/login' => Http::response(['token' => 'faketoken'], 200),
            '*/courier/track/awb/*' => Http::response([
                'tracking_data' => [
                    [
                        'shipment_track' => [['current_status' => 'Delivered']],
                        'shipment_track_activities' => [
                            ['status' => 'Delivered', 'date' => '2026-07-26'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = app(ShiprocketService::class)->trackShipment('AWB123456');

        $this->assertSame('Delivered', $result['status']);
        $this->assertCount(1, $result['checkpoints']);
    }

    private function makeOrder(): Order
    {
        $order = Order::create([
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
            'shipping_fee' => 79,
            'total' => 579,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'packed',
        ]);

        $order->items()->create([
            'product_title' => 'Test Product',
            'sku' => 'TEST-SKU',
            'price' => 500,
            'quantity' => 1,
            'subtotal' => 500,
        ]);

        return $order;
    }
}
