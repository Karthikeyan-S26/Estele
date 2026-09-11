<?php

namespace App\Observers;

use App\Models\Offer;
use Illuminate\Support\Facades\Cache;

class OfferObserver
{
    public function saved(Offer $offer): void
    {
        Cache::forget('site.active_offers');
    }

    public function deleted(Offer $offer): void
    {
        Cache::forget('site.active_offers');
    }
}
