<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Estele photography, stored locally in storage/app/seed-images —
        // one representative image per category.
        $imagesDir = storage_path('app/seed-images/categories');

        $categories = [
            ['name' => 'Necklace Sets', 'description' => 'Statement necklace sets for every occasion.', 'image' => "{$imagesDir}/necklace-sets.png"],
            ['name' => 'Pendant Sets', 'description' => 'Delicate pendant sets for everyday wear.', 'image' => "{$imagesDir}/pendant-sets.png"],
            ['name' => 'Earrings', 'description' => 'Studs, hoops and jhumkas.', 'image' => "{$imagesDir}/earrings.png"],
            ['name' => 'Rings', 'description' => 'Adjustable and statement rings.', 'image' => "{$imagesDir}/rings.png"],
            ['name' => 'Bracelets', 'description' => 'Tennis, cuff and chain bracelets.', 'image' => "{$imagesDir}/bracelets.png"],
            ['name' => 'Bangles', 'description' => 'Traditional and contemporary bangles.', 'image' => "{$imagesDir}/bangles.png"],
            ['name' => 'Brooch', 'description' => 'Brooch pins for sarees and blazers.', 'image' => "{$imagesDir}/brooch.png"],
            ['name' => 'Choker Sets', 'description' => 'Bridal and party choker sets.', 'image' => "{$imagesDir}/choker-sets.png"],
            ['name' => 'Maang Tikka', 'description' => 'Maang tikka for festive and bridal looks.', 'image' => "{$imagesDir}/maang-tikka.png"],
            ['name' => 'Mangalsutra', 'description' => 'Traditional and modern mangalsutra designs.', 'image' => "{$imagesDir}/mangalsutra.png"],
        ];

        foreach ($categories as $index => $category) {
            $model = Category::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'sort_order' => $index,
                ]
            );

            if (! $model->hasMedia('image')) {
                try {
                    $model->addMedia($category['image'])
                        ->preservingOriginal()
                        ->usingFileName($model->slug.'.png')
                        ->toMediaCollection('image');
                } catch (\Throwable $e) {
                    $this->command?->warn("Could not fetch demo image for {$category['name']}: {$e->getMessage()}");
                }
            }
        }
    }
}
