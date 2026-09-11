<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Offer;
use Illuminate\Database\Seeder;

class CouponOfferSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'type' => 'percent',
                'value' => 10,
                'max_discount_amount' => 300,
                'min_order_value' => 999,
                'is_public' => true,
            ],
            [
                'code' => 'FLAT100',
                'type' => 'flat',
                'value' => 100,
                'min_order_value' => 799,
                'is_public' => true,
            ],
            [
                'code' => 'FESTIVE20',
                'type' => 'percent',
                'value' => 20,
                'max_discount_amount' => 500,
                'min_order_value' => 1499,
                'usage_limit' => 100,
                'is_public' => true,
            ],
            [
                'code' => 'VIP50',
                'type' => 'flat',
                'value' => 50,
                'is_public' => false,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::firstOrCreate(
                ['code' => $coupon['code']],
                $coupon + ['scope' => 'all', 'is_active' => true]
            );
        }

        $offers = [
            ['text' => 'Free shipping on all prepaid orders', 'sort_order' => 1],
            ['text' => 'Use code WELCOME10 for 10% off your first order', 'sort_order' => 2],
            ['text' => 'Cash on delivery available on all orders', 'sort_order' => 3],
        ];

        foreach ($offers as $offer) {
            Offer::firstOrCreate(
                ['text' => $offer['text']],
                $offer + ['is_active' => true]
            );
        }
    }
}
