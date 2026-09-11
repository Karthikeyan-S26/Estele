<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Redirect;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    public function saved(Product $product): void
    {
        $this->flush($product);
    }

    public function updated(Product $product): void
    {
        $this->flush($product);

        if ($product->wasChanged('slug') && $product->getOriginal('slug')) {
            Redirect::recordSlugChange(
                '/products/'.$product->getOriginal('slug'),
                '/products/'.$product->slug
            );
        }
    }

    public function deleted(Product $product): void
    {
        $this->flush($product);
    }

    private function flush(Product $product): void
    {
        Cache::tags(['product:' . $product->id])->flush();

        foreach ($product->categories()->pluck('categories.id') as $categoryId) {
            Cache::tags(['category:' . $categoryId])->flush();
        }

        if ($product->is_featured) {
            Cache::tags(['home'])->flush();
        }
    }
}
