<?php

namespace App\Observers;

use App\Models\Faq;
use Illuminate\Support\Facades\Cache;

class FaqObserver
{
    public function saved(Faq $faq): void
    {
        Cache::tags(['faq'])->flush();
    }

    public function deleted(Faq $faq): void
    {
        Cache::tags(['faq'])->flush();
    }
}
