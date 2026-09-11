<?php

namespace App\Observers;

use App\Models\Coupon;
use Illuminate\Support\Facades\Cache;

class CouponObserver
{
    public function saved(Coupon $coupon): void
    {
        Cache::forget('site.public_coupons');
    }

    public function deleted(Coupon $coupon): void
    {
        Cache::forget('site.public_coupons');
    }
}
