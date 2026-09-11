<?php

namespace App\Observers;

use App\Models\FaqCategory;
use Illuminate\Support\Facades\Cache;

class FaqCategoryObserver
{
    public function saved(FaqCategory $faqCategory): void
    {
        Cache::tags(['faq'])->flush();
    }

    public function deleted(FaqCategory $faqCategory): void
    {
        Cache::tags(['faq'])->flush();
    }
}
