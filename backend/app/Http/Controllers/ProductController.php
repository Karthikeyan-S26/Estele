<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        $relatedProducts = Cache::tags(['product:' . $product->id])->remember(
            "product.{$product->id}.show",
            now()->addMinutes(15),
            function () use ($product) {
                $product->load('variants', 'categories');

                return Product::where('is_active', true)
                    ->where('id', '!=', $product->id)
                    ->with('media')
                    ->whereHas('categories', function ($query) use ($product) {
                        $query->whereIn('categories.id', $product->categories->pluck('id'));
                    })
                    ->take(8)
                    ->get();
            }
        );

        $product->loadMissing('variants', 'categories');

        $reviews = $product->approvedReviews()
            ->with('media')
            ->latest()
            ->paginate(10, ['*'], 'reviews_page');

        return view('products.show', compact('product', 'relatedProducts', 'reviews'));
    }
}
