<?php

namespace App\Observers;

use App\Models\Banner;
use Illuminate\Support\Facades\Cache;

class BannerObserver
{
    public function saved(Banner $banner): void
    {
        Cache::tags(['home'])->flush();
    }

    public function deleted(Banner $banner): void
    {
        Cache::tags(['home'])->flush();
    }
}
