<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shop by Category page (Phase 8): breadcrumb-only header (no <h1> page
 * title), plus the new price/in-stock/subcategory filter panel. Filtering
 * here is a plain Eloquent query (not Scout/Meilisearch), so it's fully
 * testable without a live search index.
 */
class VerifyCategoryFilterPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_title_h1_is_removed_but_breadcrumb_remains(): void
    {
        $category = Category::create(['name' => 'Necklaces', 'slug' => 'necklaces', 'sort_order' => 0]);

        $response = $this->get(route('categories.show', $category));

        $response->assertOk();
        $response->assertDontSee('<h1', false);
        $response->assertSee('Necklaces'); // still present via the breadcrumb
    }

    public function test_price_filter_narrows_results(): void
    {
        $category = Category::create(['name' => 'Necklaces', 'slug' => 'necklaces', 'sort_order' => 0]);
        $cheap = $this->makeProduct('cheap', 500, $category);
        $expensive = $this->makeProduct('expensive', 5000, $category);

        $response = $this->get(route('categories.show', $category).'?max_price=1000');

        $response->assertOk();
        $response->assertSee($cheap->title);
        $response->assertDontSee($expensive->title);
    }

    public function test_in_stock_filter_excludes_out_of_stock_products(): void
    {
        $category = Category::create(['name' => 'Necklaces', 'slug' => 'necklaces', 'sort_order' => 0]);
        $inStock = $this->makeProduct('in-stock', 500, $category, 5);
        $outOfStock = $this->makeProduct('out-of-stock', 500, $category, 0);

        $response = $this->get(route('categories.show', $category).'?in_stock=1');

        $response->assertOk();
        $response->assertSee($inStock->title);
        $response->assertDontSee($outOfStock->title);
    }

    public function test_a_parent_category_without_a_subcategory_filter_includes_child_products(): void
    {
        $parent = Category::create(['name' => 'Necklaces', 'slug' => 'necklaces', 'sort_order' => 0]);
        $child = Category::create(['name' => 'Necklace Sets', 'slug' => 'necklace-sets', 'parent_id' => $parent->id, 'sort_order' => 0]);
        $childProduct = $this->makeProduct('child-product', 500, $child);

        $response = $this->get(route('categories.show', $parent));

        $response->assertOk()->assertSee($childProduct->title);
    }

    public function test_selecting_a_subcategory_narrows_to_just_that_subcategory(): void
    {
        $parent = Category::create(['name' => 'Necklaces', 'slug' => 'necklaces', 'sort_order' => 0]);
        $childA = Category::create(['name' => 'Necklace Sets', 'slug' => 'necklace-sets', 'parent_id' => $parent->id, 'sort_order' => 0]);
        $childB = Category::create(['name' => 'Choker Sets', 'slug' => 'choker-sets', 'parent_id' => $parent->id, 'sort_order' => 1]);
        $productA = $this->makeProduct('product-a', 500, $childA);
        $productB = $this->makeProduct('product-b', 500, $childB);

        $response = $this->get(route('categories.show', $parent).'?subcategory[]=necklace-sets');

        $response->assertOk();
        $response->assertSee($productA->title);
        $response->assertDontSee($productB->title);
    }

    private function makeProduct(string $tag, float $price, Category $category, int $stock = 10): Product
    {
        // Category filtering (CategoryController::show) is a plain Eloquent
        // query — no Scout/Meilisearch involved at all. Creating the fixture
        // product without syncing to search keeps that true in these tests
        // too, instead of requiring a live Meilisearch just to set up data.
        $product = Product::withoutSyncingToSearch(function () use ($tag, $price, $stock) {
            return Product::create([
                'title' => 'Product '.$tag,
                'slug' => 'product-'.$tag.'-'.uniqid(),
                'sku' => 'SKU-'.strtoupper($tag).'-'.uniqid(),
                'price' => $price,
                'stock_quantity' => $stock,
                'is_active' => true,
            ]);
        });
        $product->categories()->attach($category->id);

        return $product;
    }
}
