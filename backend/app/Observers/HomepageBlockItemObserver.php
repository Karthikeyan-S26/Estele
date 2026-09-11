<?php

namespace App\Observers;

use App\Models\HomepageBlockItem;
use Illuminate\Support\Facades\Cache;

class HomepageBlockItemObserver
{
    public function saved(HomepageBlockItem $item): void
    {
        Cache::tags(['home'])->flush();
    }

    public function deleted(HomepageBlockItem $item): void
    {
        Cache::tags(['home'])->flush();
    }
}
