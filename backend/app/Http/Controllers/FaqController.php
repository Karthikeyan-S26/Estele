<?php

namespace App\Http\Controllers;

use App\Models\FaqCategory;
use Illuminate\Support\Facades\Cache;

class FaqController extends Controller
{
    public function index()
    {
        $faqCategories = Cache::tags(['faq'])->remember('faq.index', now()->addHour(), function () {
            return FaqCategory::with(['faqs' => fn ($query) => $query->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get()
                ->filter(fn (FaqCategory $category) => $category->faqs->isNotEmpty());
        });

        return view('faq.index', compact('faqCategories'));
    }
}
