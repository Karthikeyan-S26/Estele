<?php

namespace App\Observers;

use App\Models\Collection;
use App\Models\Redirect;
use Illuminate\Support\Facades\Cache;

class CollectionObserver
{
    public function saved(Collection $collection): void
    {
        Cache::forget('site.nav_collections');
        Cache::tags(['home'])->flush();
    }

    // See CategoryObserver for why this is a separate updated() hook rather
    // than a wasRecentlyCreated check inside saved().
    public function updated(Collection $collection): void
    {
        if ($collection->wasChanged('slug') && $collection->getOriginal('slug')) {
            Redirect::recordSlugChange(
                '/collections/'.$collection->getOriginal('slug'),
                '/collections/'.$collection->slug
            );
        }
    }

    public function deleted(Collection $collection): void
    {
        Cache::forget('site.nav_collections');
        Cache::tags(['home'])->flush();
    }
}
