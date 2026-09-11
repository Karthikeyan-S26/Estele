<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Collection as CollectionModel;
use App\Models\Faq;
use App\Models\HomepageBlock;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * The sections added in the "fill the homepage" pass. Everything here is
 * content an admin edits in Filament afterwards — this seeder only puts the
 * first version of each row in place, and skips any block type that already
 * exists so re-running never duplicates or overwrites their edits.
 */
class HomepageSectionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPriceTiers();
        $this->seedJournal();
        $this->seedShopTheLook();
        $this->seedFaq();
        $this->seedBrandStoryStats();
        $this->seedFooterSettings();
    }

    private function block(string $type, array $attributes): ?HomepageBlock
    {
        if (HomepageBlock::where('type', $type)->exists()) {
            return null;
        }

        return HomepageBlock::create($attributes + ['type' => $type, 'is_active' => true]);
    }

    private function seedPriceTiers(): void
    {
        $block = $this->block('price_tiers', [
            'title' => "Your Budget,\nYour Bling",
            'subtitle' => 'Every price, same sparkle',
            'sort_order' => 22,
        ]);

        if (! $block) {
            return;
        }

        $tiers = [
            ['Under', '₹499', ['max_price' => 499]],
            ['Under', '₹999', ['max_price' => 999]],
            ['Under', '₹1,499', ['max_price' => 1499]],
            ['Under', '₹1,999', ['max_price' => 1999]],
            ['Premium', '₹2,000+', ['min_price' => 2000]],
        ];

        foreach ($tiers as $index => [$label, $amount, $query]) {
            $block->items()->create([
                'title' => $label,
                'body' => $amount,
                'link_url' => route('search', $query),
                'sort_order' => $index,
            ]);
        }
    }

    private function seedJournal(): void
    {
        $block = $this->block('journal', [
            'title' => 'From the Journal',
            'subtitle' => 'Style Notes',
            'cta_label' => 'All stories',
            'cta_url' => route('blogs.index'),
            'sort_order' => 52,
        ]);

        if (! $block) {
            return;
        }

        $index = 0;
        foreach (Blog::published()->orderByDesc('published_at')->take(2)->get() as $blog) {
            $block->items()->create([
                'itemable_type' => Blog::class,
                'itemable_id' => $blog->id,
                'sort_order' => $index++,
            ]);
        }

        // No itemable — the dark "care guide" promo card at the end of the row.
        $block->items()->create([
            'title' => 'How to keep gold-plated jewellery shining for years',
            'body' => 'Store dry, wear after perfume, wipe with a soft cloth. Five habits that make anti-tarnish last.',
            'link_url' => route('faq.index'),
            'sort_order' => $index,
        ]);
    }

    private function seedShopTheLook(): void
    {
        $block = $this->block('shop_the_look', [
            'title' => 'Styled by You #EsteleQueens',
            'subtitle' => '@estele.co',
            'sort_order' => 54,
        ]);

        if (! $block) {
            return;
        }

        $products = Product::where('is_active', true)->where('is_featured', true)->orderBy('id')->take(8)->get();

        foreach ($products as $index => $product) {
            $block->items()->create([
                'itemable_type' => Product::class,
                'itemable_id' => $product->id,
                'sort_order' => $index,
            ]);
        }
    }

    private function seedFaq(): void
    {
        $block = $this->block('faq', [
            'title' => 'Questions? Answered.',
            'subtitle' => 'Help Centre',
            'cta_label' => 'All FAQs',
            'cta_url' => route('faq.index'),
            'sort_order' => 62,
        ]);

        if (! $block) {
            return;
        }

        foreach (Faq::orderBy('sort_order')->take(6)->get() as $index => $faq) {
            $block->items()->create([
                'itemable_type' => Faq::class,
                'itemable_id' => $faq->id,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * brand_story predates this pass, so its block row already exists — only
     * its stat items (which the rewritten Blade now renders) are new.
     */
    private function seedBrandStoryStats(): void
    {
        $block = HomepageBlock::where('type', 'brand_story')->first();

        if (! $block || $block->items()->exists()) {
            return;
        }

        $collections = CollectionModel::active()->first();

        $block->update([
            'cta_label' => $block->cta_label ?: 'Explore collections',
            'cta_url' => $block->cta_url ?: ($collections ? route('collections.index') : route('home')),
        ]);

        $stats = [
            ['1989', 'Founded in Hyderabad'],
            ['5M+', 'Happy customers'],
            ['1,00,000+', 'Designs crafted'],
            ['45+', 'Stores across India'],
        ];

        foreach ($stats as $index => [$value, $label]) {
            $block->items()->create([
                'title' => $value,
                'body' => $label,
                'sort_order' => $index,
            ]);
        }
    }

    private function seedFooterSettings(): void
    {
        $settings = [
            'footer_about' => "India's leading fashion jewellery destination. Over 100,000 anti-tarnish 24K gold plated designs, 45+ stores and 5M+ happy customers across the country.",
            'footer_vip_body' => 'Early access to launches, festive previews and ₹500 off your first order.',
            'footer_company_name' => 'Estele Accessories Pvt. Ltd.',
            'contact_address' => '9-47, Keshav Nagar, Boduppal, Hyderabad, Telangana 500092',
            'contact_hours' => 'Mon–Sat, 10am–7pm IST',
            'footer_usps' => json_encode([
                ['title' => '100% Anti-Tarnish', 'body' => 'Plating that stays bright', 'icon' => 'shield'],
                ['title' => '7-Day Easy Returns', 'body' => 'Hassle-free exchange', 'icon' => 'return'],
                ['title' => 'Free Shipping ₹1,499+', 'body' => 'Pan-India delivery', 'icon' => 'truck'],
                ['title' => 'Secure Payments', 'body' => 'UPI, cards & COD', 'icon' => 'lock'],
            ], JSON_UNESCAPED_UNICODE),
            'footer_popular_searches' => json_encode(['Earrings', 'Necklace Sets', 'Bangles', 'Mangalsutra', 'Maang Tikka', 'Rose Gold', 'Pearl Jewellery', 'Bridal Sets', 'Office Wear', 'Gifts Under ₹999'], JSON_UNESCAPED_UNICODE),
            'footer_payment_badges' => json_encode(['UPI', 'Visa', 'Mastercard', 'RuPay', 'Net Banking', 'COD']),
        ];

        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
