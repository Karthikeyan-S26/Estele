<?php

namespace App\Observers;

use App\Models\Popup;
use Illuminate\Support\Facades\Cache;

class PopupObserver
{
    public function saved(Popup $popup): void
    {
        Cache::forget('site.active_popup');
    }

    public function deleted(Popup $popup): void
    {
        Cache::forget('site.active_popup');
    }
}
