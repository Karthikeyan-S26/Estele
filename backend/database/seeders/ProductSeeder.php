<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalog = [
            'Necklace Sets' => ['Peacock', 'Halo Blossom', 'Aurora Bloom', 'Prism Petal', 'Crystal Aura'],
            'Pendant Sets' => ['Gold AD', 'Square Solitaire', 'Rhodium CZ', 'Maple Leaf', 'Emerald Drop'],
            'Earrings' => ['Pearl Drop Hoop', 'Rosegold Floral', 'Circular Stud', 'Kundan Jhumka', 'Charm Hanging'],
            'Rings' => ['Halo Crystal', 'White Crystal', 'Baguette', 'Round Stone', 'Twisted Chain'],
            'Bracelets' => ['Classic Center Stone', 'Rectangular Crystal', 'Premium Cuff', 'Single Row', 'Tennis'],
            'Bangles' => ['Resplendent Ruby', 'Daisy Flower', 'Openable Festive', 'Blossom', 'Alluring Crystal'],
            'Brooch' => ['Morbagh Ruby', 'Morbagh Green', 'Blue Green', 'White CZ', 'Feather'],
            'Choker Sets' => ['Peacock Bridal', 'Rose Motif', 'Beaded Peacock', 'Floral Rose', 'Classic Floral'],
            'Maang Tikka' => ['Fascinating CZ', 'Kundan', 'Timeless Drop', 'Lotus Pearl', 'Dazzling CZ'],
            'Mangalsutra' => ['Heavenly Crystal', 'Valley', 'Flower Double Line', 'Designer Crystal', 'Drop Flower'],
        ];

        // Estele photography, stored locally in storage/app/seed-images —
        // admin can replace via Filament.
        $imagesDir = storage_path('app/seed-images/products');

        $images = [
            'Necklace Sets' => [
                "{$imagesDir}/peacock-necklace-sets.png",
                "{$imagesDir}/halo-blossom-necklace-sets.png",
                "{$imagesDir}/aurora-bloom-necklace-sets.png",
                "{$imagesDir}/prism-petal-necklace-sets.png",
                "{$imagesDir}/crystal-aura-necklace-sets.png",
            ],
            'Pendant Sets' => [
                "{$imagesDir}/gold-ad-pendant-sets.png",
                "{$imagesDir}/square-solitaire-pendant-sets.png",
                "{$imagesDir}/rhodium-cz-pendant-sets.png",
                "{$imagesDir}/maple-leaf-pendant-sets.png",
                "{$imagesDir}/emerald-drop-pendant-sets.png",
            ],
            'Earrings' => [
                "{$imagesDir}/pearl-drop-hoop-earrings.png",
                "{$imagesDir}/rosegold-floral-earrings.png",
                "{$imagesDir}/circular-stud-earrings.png",
                "{$imagesDir}/kundan-jhumka-earrings.png",
                "{$imagesDir}/charm-hanging-earrings.png",
            ],
            'Rings' => [
                "{$imagesDir}/halo-crystal-rings.png",
                "{$imagesDir}/white-crystal-rings.png",
                "{$imagesDir}/baguette-rings.png",
                "{$imagesDir}/round-stone-rings.png",
                "{$imagesDir}/twisted-chain-rings.png",
            ],
            'Bracelets' => [
                "{$imagesDir}/classic-center-stone-bracelets.png",
                "{$imagesDir}/rectangular-crystal-bracelets.png",
                "{$imagesDir}/premium-cuff-bracelets.png",
                "{$imagesDir}/single-row-bracelets.png",
                "{$imagesDir}/tennis-bracelets.png",
            ],
            'Bangles' => [
                "{$imagesDir}/resplendent-ruby-bangles.png",
                "{$imagesDir}/daisy-flower-bangles.png",
                "{$imagesDir}/openable-festive-bangles.png",
                "{$imagesDir}/blossom-bangles.png",
                "{$imagesDir}/alluring-crystal-bangles.png",
            ],
            'Brooch' => [
                "{$imagesDir}/morbagh-ruby-brooch.png",
                "{$imagesDir}/morbagh-green-brooch.png",
                "{$imagesDir}/blue-green-brooch.png",
                "{$imagesDir}/white-cz-brooch.png",
                "{$imagesDir}/feather-brooch.png",
            ],
            'Choker Sets' => [
                "{$imagesDir}/peacock-bridal-choker-sets.png",
                "{$imagesDir}/rose-motif-choker-sets.png",
                "{$imagesDir}/beaded-peacock-choker-sets.png",
                "{$imagesDir}/floral-rose-choker-sets.png",
                "{$imagesDir}/classic-floral-choker-sets.png",
            ],
            'Maang Tikka' => [
                "{$imagesDir}/fascinating-cz-maang-tikka.png",
                "{$imagesDir}/kundan-maang-tikka.png",
                "{$imagesDir}/timeless-drop-maang-tikka.png",
                "{$imagesDir}/lotus-pearl-maang-tikka.png",
                "{$imagesDir}/dazzling-cz-maang-tikka.png",
            ],
            'Mangalsutra' => [
                "{$imagesDir}/heavenly-crystal-mangalsutra.png",
                "{$imagesDir}/valley-mangalsutra.png",
                "{$imagesDir}/flower-double-line-mangalsutra.png",
                "{$imagesDir}/designer-crystal-mangalsutra.png",
                "{$imagesDir}/drop-flower-mangalsutra.png",
            ],
        ];

        // Second-angle photos for the hover-swap effect on product cards, matching
        // the original site's real "primary photo + hover photo" pattern.
        $hoverImages = [
            'Necklace Sets' => [
                "{$imagesDir}/peacock-necklace-sets-hover.png",
                "{$imagesDir}/halo-blossom-necklace-sets-hover.png",
                "{$imagesDir}/aurora-bloom-necklace-sets-hover.png",
                "{$imagesDir}/prism-petal-necklace-sets-hover.png",
                "{$imagesDir}/crystal-aura-necklace-sets-hover.png",
            ],
            'Pendant Sets' => [
                "{$imagesDir}/gold-ad-pendant-sets-hover.png",
                "{$imagesDir}/square-solitaire-pendant-sets-hover.png",
                "{$imagesDir}/rhodium-cz-pendant-sets-hover.png",
                "{$imagesDir}/maple-leaf-pendant-sets-hover.png",
                "{$imagesDir}/emerald-drop-pendant-sets-hover.png",
            ],
            'Earrings' => [
                "{$imagesDir}/pearl-drop-hoop-earrings-hover.png",
                "{$imagesDir}/rosegold-floral-earrings-hover.png",
                "{$imagesDir}/circular-stud-earrings-hover.png",
                "{$imagesDir}/kundan-jhumka-earrings-hover.png",
                "{$imagesDir}/charm-hanging-earrings-hover.png",
            ],
            'Rings' => [
                "{$imagesDir}/halo-crystal-rings-hover.png",
                "{$imagesDir}/white-crystal-rings-hover.png",
                "{$imagesDir}/baguette-rings-hover.png",
                "{$imagesDir}/round-stone-rings-hover.png",
                "{$imagesDir}/twisted-chain-rings-hover.png",
            ],
            'Bracelets' => [
                "{$imagesDir}/classic-center-stone-bracelets-hover.png",
                "{$imagesDir}/rectangular-crystal-bracelets-hover.png",
                "{$imagesDir}/premium-cuff-bracelets-hover.png",
                "{$imagesDir}/single-row-bracelets-hover.png",
                "{$imagesDir}/tennis-bracelets-hover.png",
            ],
            'Bangles' => [
                "{$imagesDir}/resplendent-ruby-bangles-hover.png",
                "{$imagesDir}/daisy-flower-bangles-hover.png",
                "{$imagesDir}/openable-festive-bangles-hover.png",
                "{$imagesDir}/blossom-bangles-hover.png",
                "{$imagesDir}/alluring-crystal-bangles-hover.png",
            ],
            'Brooch' => [
                "{$imagesDir}/morbagh-ruby-brooch-hover.png",
                "{$imagesDir}/morbagh-green-brooch-hover.png",
                "{$imagesDir}/blue-green-brooch-hover.png",
                "{$imagesDir}/white-cz-brooch-hover.png",
                "{$imagesDir}/feather-brooch-hover.png",
            ],
            'Choker Sets' => [
                "{$imagesDir}/peacock-bridal-choker-sets-hover.png",
                "{$imagesDir}/rose-motif-choker-sets-hover.png",
                "{$imagesDir}/beaded-peacock-choker-sets-hover.png",
                "{$imagesDir}/floral-rose-choker-sets-hover.png",
                "{$imagesDir}/classic-floral-choker-sets-hover.png",
            ],
            'Maang Tikka' => [
                "{$imagesDir}/fascinating-cz-maang-tikka-hover.png",
                "{$imagesDir}/kundan-maang-tikka-hover.png",
                "{$imagesDir}/timeless-drop-maang-tikka-hover.png",
                "{$imagesDir}/lotus-pearl-maang-tikka-hover.png",
                "{$imagesDir}/dazzling-cz-maang-tikka-hover.png",
            ],
            'Mangalsutra' => [
                "{$imagesDir}/heavenly-crystal-mangalsutra-hover.png",
                "{$imagesDir}/valley-mangalsutra-hover.png",
                "{$imagesDir}/flower-double-line-mangalsutra-hover.png",
                "{$imagesDir}/designer-crystal-mangalsutra-hover.png",
                "{$imagesDir}/drop-flower-mangalsutra-hover.png",
            ],
        ];

        $sku = 1000;

        foreach ($catalog as $categoryName => $variants) {
            $category = Category::where('slug', Str::slug($categoryName))->first();

            if (! $category) {
                continue;
            }

            foreach ($variants as $index => $variant) {
                $title = "{$variant} {$categoryName}";
                $price = random_int(4, 40) * 100 + 99;
                $compareAt = (bool) random_int(0, 1) ? $price + random_int(200, 2000) : null;
                $sku++;

                $product = Product::updateOrCreate(
                    ['slug' => Str::slug($title)],
                    [
                        'title' => $title,
                        'sku' => 'EST-'.$sku,
                        'description' => "A handcrafted {$variant} design from our {$categoryName} collection, finished with anti-tarnish plating.",
                        'price' => $price,
                        'compare_at_price' => $compareAt,
                        'stock_quantity' => random_int(0, 50),
                        'is_active' => true,
                        'is_featured' => $index === 0,
                    ]
                );

                $product->categories()->syncWithoutDetaching([$category->id]);

                if (! $product->hasMedia('gallery') && isset($images[$categoryName][$index])) {
                    try {
                        $product->addMedia($images[$categoryName][$index])
                            ->preservingOriginal()
                            ->usingFileName(Str::slug($title).'.png')
                            ->toMediaCollection('gallery');
                    } catch (\Throwable $e) {
                        $this->command?->warn("Could not fetch demo image for {$title}: {$e->getMessage()}");
                    }
                }

                if ($product->getMedia('gallery')->count() < 2 && isset($hoverImages[$categoryName][$index])) {
                    try {
                        $product->addMedia($hoverImages[$categoryName][$index])
                            ->preservingOriginal()
                            ->usingFileName(Str::slug($title).'-hover.png')
                            ->toMediaCollection('gallery');
                    } catch (\Throwable $e) {
                        $this->command?->warn("Could not fetch hover demo image for {$title}: {$e->getMessage()}");
                    }
                }
            }
        }
    }
}
