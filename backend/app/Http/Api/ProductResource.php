<?php

namespace App\Http\Api;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Normalizes a Product (or product grid row) into the JSON shape the Flutter
 * app renders ProductCards from. Single source of truth for card + detail
 * serialization — keep the fields here in lockstep with lib/models/Product.dart.
 */
class ProductResource
{
    public static function card(Product $product): array
    {
        $product->loadMissing('media');

        $imageUrl = $product->getFirstMediaUrl('gallery', 'card');
        $tabletUrl = $product->getFirstMediaUrl('gallery', 'tablet');
        $detailUrl = $product->getFirstMediaUrl('gallery', 'detail');

        $hasVariants = $product->variants->isNotEmpty();
        $inStock = $hasVariants
            ? $product->variants->contains(fn (ProductVariant $v) => $v->stock_quantity > 0)
            : $product->stock_quantity > 0;

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'price' => (float) $product->price,
            'compare_at_price' => $product->compare_at_price !== null ? (float) $product->compare_at_price : null,
            'in_stock' => $inStock,
            'is_active' => $product->is_active,
            'is_new' => $product->created_at?->gte(now()->subDays(30)) ?? false,
            'discount_percent' => self::discountPercent($product),
            'rating' => $product->reviewsAverageRating(),
            'review_count' => $product->reviewsCount(),
            'image' => $imageUrl ?: null,
            'images' => [
                'card' => $imageUrl ?: null,
                'tablet' => $tabletUrl ?: null,
                'detail' => $detailUrl ?: null,
            ],
            'created_at' => $product->created_at?->toIso8601String(),
        ];
    }

    public static function detail(Product $product): array
    {
        $product->loadMissing('variants', 'categories', 'collections', 'media');

        $galleryImages = $product->getMedia('gallery')
            ->map(fn ($media) => [
                'id' => $media->id,
                'card' => $media->getUrl('card'),
                'tablet' => $media->getUrl('tablet'),
                'detail' => $media->getUrl('detail'),
                'original' => $media->getUrl(),
                'alt' => $media->getCustomProperty('alt') ?? $product->title,
            ])
            ->values()
            ->all();

        $hasVariants = $product->variants->isNotEmpty();
        $inStock = $hasVariants
            ? $product->variants->contains(fn (ProductVariant $v) => $v->stock_quantity > 0)
            : $product->stock_quantity > 0;

        return [
            ...self::card($product),
            'sku' => $product->sku,
            'is_featured' => $product->is_featured,
            'description' => $product->description,
            'has_variants' => $hasVariants,
            'stock_quantity' => (int) $product->stock_quantity,
            'gallery' => $galleryImages,
            'variants' => $product->variants->sortBy('price')->map(function (ProductVariant $variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => (float) $variant->price,
                    'stock_quantity' => (int) $variant->stock_quantity,
                    'in_stock' => $variant->stock_quantity > 0,
                    'attributes' => $variant->attributes,
                ];
            })->values()->all(),
            'categories' => $product->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])->values()->all(),
            'collections' => $product->collections->map(fn ($collection) => [
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $collection->slug,
            ])->values()->all(),
        ];
    }

    public static function discountPercent(Product $product): ?int
    {
        $compare = $product->compare_at_price;

        if (! $compare || (float) $compare <= (float) $product->price) {
            return null;
        }

        return (int) round(((float) $compare - (float) $product->price) / (float) $compare * 100);
    }
}