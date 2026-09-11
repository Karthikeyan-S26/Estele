<?php

namespace App\Observers;

use App\Models\Review;
use Illuminate\Support\Facades\Cache;

class ReviewObserver
{
    public function saved(Review $review): void
    {
        Cache::tags(['product:' . $review->product_id])->flush();
    }

    public function deleted(Review $review): void
    {
        Cache::tags(['product:' . $review->product_id])->flush();
    }
}
