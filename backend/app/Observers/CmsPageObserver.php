<?php

namespace App\Observers;

use App\Models\CmsPage;
use App\Models\Redirect;
use Illuminate\Support\Facades\Cache;

class CmsPageObserver
{
    public function saved(CmsPage $cmsPage): void
    {
        Cache::tags(['cms'])->flush();
    }

    // See CategoryObserver for why this is a separate updated() hook rather
    // than a wasRecentlyCreated check inside saved().
    public function updated(CmsPage $cmsPage): void
    {
        if ($cmsPage->wasChanged('slug') && $cmsPage->getOriginal('slug')) {
            Redirect::recordSlugChange(
                '/pages/'.$cmsPage->getOriginal('slug'),
                '/pages/'.$cmsPage->slug
            );
        }
    }

    public function deleted(CmsPage $cmsPage): void
    {
        Cache::tags(['cms'])->flush();
    }
}
