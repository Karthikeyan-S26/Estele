<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SeoMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * seo_meta is polymorphic, so there is no FK cascade. Without an explicit
 * cleanup the row outlives its owner and a later record reusing the same id
 * inherits the deleted record's meta.
 */
class VerifySeoMetaCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_product_deletes_its_seo_meta(): void
    {
        $product = Product::create([
            'title' => 'Rose Gold Bracelet',
            'slug' => 'rose-gold-bracelet',
            'sku' => 'RGB-SEO',
            'description' => 'A bracelet.',
            'price' => 999,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
        $product->seoMeta()->create(['title' => 'Doomed']);

        $this->assertSame(1, SeoMeta::count());

        $product->delete();

        $this->assertSame(0, SeoMeta::count());
    }
}
