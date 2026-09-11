<?php

namespace App\Observers;

use App\Models\Blog;
use App\Models\Redirect;
use Illuminate\Support\Facades\Cache;

class BlogObserver
{
    public function saved(Blog $blog): void
    {
        Cache::tags(['blog'])->flush();
    }

    // See CategoryObserver for why this is a separate updated() hook rather
    // than a wasRecentlyCreated check inside saved().
    public function updated(Blog $blog): void
    {
        if ($blog->wasChanged('slug') && $blog->getOriginal('slug')) {
            Redirect::recordSlugChange(
                '/blogs/'.$blog->getOriginal('slug'),
                '/blogs/'.$blog->slug
            );
        }
    }

    public function deleted(Blog $blog): void
    {
        Cache::tags(['blog'])->flush();
    }
}
