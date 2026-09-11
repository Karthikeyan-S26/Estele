<?php

namespace App\Observers;

use App\Models\HomepageBlock;
use Illuminate\Support\Facades\Cache;

class HomepageBlockObserver
{
    public function saved(HomepageBlock $block): void
    {
        Cache::tags(['home'])->flush();
    }

    public function deleted(HomepageBlock $block): void
    {
        Cache::tags(['home'])->flush();
    }
}
