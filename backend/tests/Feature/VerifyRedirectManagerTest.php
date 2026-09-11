<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5's Redirect Manager (spec §5/§6 — "old URLs never 404 when slug
 * changes"). Covers both halves: auto-creation on a slug change (via the
 * model observers) and the actual HTTP 301 (via bootstrap/app.php's
 * exception-render hook — a plain Route::fallback() doesn't work here, see
 * the comment in routes/web.php, so this locks down the real mechanism).
 */
class VerifyRedirectManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_slug_change_auto_creates_a_redirect(): void
    {
        $product = Product::create($this->productAttrs('redirect-me'));
        $originalSlug = $product->slug;

        $product->slug = $originalSlug.'-renamed';
        $product->save();

        $this->assertDatabaseHas('redirects', [
            'old_path' => '/products/'.$originalSlug,
            'new_path' => '/products/'.$originalSlug.'-renamed',
            'status_code' => 301,
            'source' => 'auto',
        ]);
    }

    public function test_stale_product_slug_returns_a_real_301_that_lands_on_the_live_page(): void
    {
        $product = Product::create($this->productAttrs('old-slug-http'));
        $originalSlug = $product->slug;

        $product->slug = $originalSlug.'-new';
        $product->save();

        $response = $this->get('/products/'.$originalSlug);
        $response->assertRedirect('/products/'.$originalSlug.'-new');

        $this->followingRedirects()->get('/products/'.$originalSlug)->assertOk();
    }

    public function test_renaming_a_slug_twice_then_reverting_leaves_no_self_redirect_loop(): void
    {
        // Regression test for a real bug found during Phase 5 build: naive
        // chain-collapsing rewrote a redirect's new_path to equal its own
        // old_path (X -> X) when a slug was renamed away and then back to
        // its original value.
        $category = Category::create(['name' => 'Loop Test', 'slug' => 'loop-test']);

        $category->slug = 'loop-test-v2';
        $category->save();
        $category->slug = 'loop-test-v3';
        $category->save();
        $category->slug = 'loop-test'; // back to the original
        $category->save();

        $this->assertDatabaseMissing('redirects', ['old_path' => '/categories/loop-test']);

        $this->assertDatabaseHas('redirects', [
            'old_path' => '/categories/loop-test-v2',
            'new_path' => '/categories/loop-test',
        ]);
        $this->assertDatabaseHas('redirects', [
            'old_path' => '/categories/loop-test-v3',
            'new_path' => '/categories/loop-test',
        ]);

        foreach (Redirect::all() as $redirect) {
            $this->assertNotSame($redirect->old_path, $redirect->new_path, 'No redirect should point at itself.');
        }

        $this->get('/categories/loop-test')->assertOk();
    }

    public function test_manually_created_redirect_301s(): void
    {
        Redirect::create([
            'old_path' => '/old-campaign-page',
            'new_path' => '/collections',
            'status_code' => 301,
            'is_active' => true,
            'source' => 'manual',
        ]);

        $this->get('/old-campaign-page')->assertRedirect('/collections');
    }

    public function test_inactive_redirect_does_not_fire(): void
    {
        Redirect::create([
            'old_path' => '/disabled-redirect',
            'new_path' => '/collections',
            'status_code' => 301,
            'is_active' => false,
            'source' => 'manual',
        ]);

        $this->get('/disabled-redirect')->assertNotFound();
    }

    public function test_genuinely_unmapped_path_still_404s(): void
    {
        $this->get('/this-path-truly-does-not-exist')->assertNotFound();
    }

    private function productAttrs(string $tag): array
    {
        return [
            'title' => 'Product '.$tag,
            'slug' => $tag,
            'sku' => 'SKU-'.strtoupper($tag).'-'.uniqid(),
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
        ];
    }
}
