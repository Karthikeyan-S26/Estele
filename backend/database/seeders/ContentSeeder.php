<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\CmsPage;
use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    /**
     * Like updateOrCreate(), but for rows whose own key column is being
     * renamed (e.g. a slug or question text changing to the real Estele
     * copy). Matching on the old key alone breaks on a second run once the
     * row has already been renamed - this checks the new key first (already
     * migrated), falls back to the old key (first-time migration), and
     * only creates fresh if neither exists.
     */
    private function migrateOrCreate(string $model, string $keyColumn, string $newKey, string $oldKey, array $attributes): mixed
    {
        $record = $model::where($keyColumn, $newKey)->first()
            ?? $model::where($keyColumn, $oldKey)->first()
            ?? new $model;

        $record->fill($attributes);
        $record->save();

        return $record;
    }

    public function run(): void
    {
        $pages = [
            ['title' => 'About Us', 'slug' => 'about-us', 'content' => '<p>Welcome to our story — a jewellery brand built on craftsmanship and care.</p>'],
            ['title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'content' => '<p>We respect your privacy. This policy explains what data we collect and how we use it.</p>'],
            ['title' => 'Shipping Policy', 'slug' => 'shipping-policy', 'content' => '<p>Orders are processed within 1-2 business days and shipped via our courier partners.</p>'],
            ['title' => 'Return Policy', 'slug' => 'return-policy', 'content' => '<p>Not happy with your order? Returns are accepted within 7 days of delivery.</p>'],
            ['title' => 'Franchise', 'slug' => 'franchise', 'content' => '<p>Interested in partnering with us? Reach out to our franchise team.</p>'],
            ['title' => 'Terms & Conditions', 'slug' => 'terms-and-conditions', 'content' => '<p>These terms and conditions govern your use of our website. By accessing the site you agree to these terms. Prices, offers and availability are subject to change without prior notice. All content, branding and product imagery are the property of Estele and may not be reproduced without permission.</p>'],
        ];

        foreach ($pages as $page) {
            CmsPage::firstOrCreate(
                ['slug' => $page['slug']],
                ['title' => $page['title'], 'content' => $page['content'], 'status' => 'published']
            );
        }

        $styleGuide = BlogCategory::firstOrCreate(['slug' => 'style-guide'], ['name' => 'Style Guide', 'sort_order' => 1]);
        $giftGuides = $this->migrateOrCreate(BlogCategory::class, 'slug', 'gift-guides', 'care-tips', ['name' => 'Gift Guides', 'slug' => 'gift-guides', 'sort_order' => 2]);

        // Real posts from estele.co/blogs/blog (title/excerpt/content/image all
        // sourced from the live site, not invented) — matched via the old
        // placeholder slugs so re-running this seeder updates existing rows
        // instead of creating duplicates.
        $necklaceSets = $this->migrateOrCreate(
            Blog::class,
            'slug',
            'necklace-sets-that-steal-the-spotlight',
            'how-to-choose-your-first-necklace',
            [
                'blog_category_id' => $styleGuide->id,
                'slug' => 'necklace-sets-that-steal-the-spotlight',
                'title' => 'Necklace Sets That Steal the Spotlight—Because You Deserve Nothing Less',
                'excerpt' => 'A well-chosen necklace set has the magic to transform an outfit, enhance your aura, and make a statement without saying a word.',
                'content' => <<<'HTML'
                    <p>A well-chosen necklace set has the magic to transform an outfit, enhance your aura, and make a statement without saying a word. At Estele, we believe that every woman deserves jewelry that feels as extraordinary as she is. And when it comes to curating the perfect necklace set for women, it's all about pieces that exude confidence, charm, and a touch of effortless glam.</p>
                    <p>So, whether you're dressing up for a big event, or just elevating your everyday look—this guide will walk you through the most dazzling necklace sets designed to make heads turn.</p>
                    <h3>The Power of a Stunning Necklace Set</h3>
                    <p>A necklace set is more than just an accessory—it's a storyteller. It frames your face, highlights your neckline, and adds that finishing touch to your ensemble. Whether it's delicate gold, shimmering silver, or jewel-encrusted brilliance, a necklace set for women is the one thing that can tie an entire look together.</p>
                    <p>But how do you choose the right one? Let's break it down.</p>
                    <h3>Spotlight-Worthy Necklace Sets for Every Occasion</h3>
                    <p>No two moments are the same, and neither should your jewelry be. Here's how to pick the perfect necklace set based on where you're headed:</p>
                    <h4>For the Grand Celebrations – Statement Necklace Sets That Dazzle</h4>
                    <p>Weddings, gala nights, festive gatherings—these are the moments to go bold. Think intricately crafted necklace sets with a regal feel, dripping in elegance. Opt for crystal-encrusted designs, layered pearls, or ornate gold pieces that turn heads from across the room.</p>
                    <p><em>Style Tip:</em> Pair a statement necklace set with an off-shoulder or deep-neckline dress for maximum impact.</p>
                    <h4>For the Chic Everyday Look – Minimal Yet Mesmerizing</h4>
                    <p>Who says everyday jewelry has to be boring? A sleek necklace set for women with dainty chains and subtle embellishments can add a polished finish to any outfit—be it workwear, casual brunch looks, or even a classic white shirt moment.</p>
                    <p><em>Style Tip:</em> Layer a delicate necklace set over a monochrome outfit to keep it effortlessly stylish.</p>
                    <h4>For the Romantic Evenings – Soft, Feminine, and Timeless</h4>
                    <p>When the night calls for elegance, a soft and sophisticated necklace set is your best companion. Rose gold, soft pastels, and pearl-draped pieces are the ultimate choice for candlelit dinners, anniversary celebrations, or a dreamy date night.</p>
                    <p><em>Style Tip:</em> A sweetheart neckline or a silky slip dress pairs beautifully with a romantic necklace set.</p>
                    <h4>For the Trendsetter – Bold, Edgy, and Unapologetically Chic</h4>
                    <p>If your jewelry mantra is 'go big or go home,' then statement necklace sets with chunky designs, geometric patterns, or layered chains are for you. These pieces are meant to be the highlight of your outfit, turning even the simplest looks into a fashion moment.</p>
                    <p><em>Style Tip:</em> A structured blazer + a bold necklace set = the ultimate power look.</p>
                    <h3>Material Matters – Choosing the Right Necklace Set for You</h3>
                    <p>Jewelry is all about personal style, but knowing your materials helps you make a choice that lasts. Here's a quick guide to what's trending:</p>
                    <h4>Gold-Plated Necklace Sets – Timeless Glam</h4>
                    <p>A gold-plated necklace set brings in an old-world charm while keeping things modern. Ideal for festive wear and traditional outfits, these sets never fail to impress.</p>
                    <h4>Silver-Toned Necklace Sets – Cool, Contemporary, and Chic</h4>
                    <p>Perfect for those who love an understated yet luxe vibe, silver-toned necklace sets are versatile and effortlessly stylish.</p>
                    <h4>Pearl Necklace Sets – Classic with a Twist</h4>
                    <p>Whether vintage or modern, pearls add a soft glow to any look. A pearl necklace set for women is a must-have in every jewelry box.</p>
                    <h3>Ready to Steal the Spotlight?</h3>
                    <p>Your perfect necklace set is waiting for you. Whether you're looking for an everyday essential or a show-stopping statement piece, Estele has something that speaks your style. Because jewelry isn't just about accessorizing—it's about owning the moment.</p>
                    HTML,
                'author_name' => 'Team Estele',
                'status' => 'published',
                'published_at' => now()->subDays(5),
            ]
        );

        if (! $necklaceSets->hasMedia('featured_image')) {
            try {
                $necklaceSets->addMedia(storage_path('app/seed-images/homepage/necklace-sets-that-steal-the-spotlight.png'))
                    ->preservingOriginal()
                    ->usingFileName($necklaceSets->slug.'.png')
                    ->toMediaCollection('featured_image');
            } catch (\Throwable $e) {
                $this->command?->warn("Could not fetch blog image for {$necklaceSets->title}: {$e->getMessage()}");
            }
        }

        $braceletsGuide = $this->migrateOrCreate(
            Blog::class,
            'slug',
            'valentines-day-gift-guide-bracelets-for-women',
            'caring-for-anti-tarnish-jewellery',
            [
                'blog_category_id' => $giftGuides->id,
                'slug' => 'valentines-day-gift-guide-bracelets-for-women',
                'title' => "Valentine's Day Gift Guide: Bold & Beautiful Bracelets for Women",
                'excerpt' => "When it comes to expressing love, actions may speak louder than words—but a thoughtful gift goes a long way in celebrating connection.",
                'content' => <<<'HTML'
                    <p>When it comes to expressing love, actions may speak louder than words—but a thoughtful gift goes a long way in celebrating connection. This Valentine's Day, consider bracelets as a timeless gift from Estele's curated collection.</p>
                    <p>Bracelets hold a unique charm—they're versatile, wearable for any occasion, and effortlessly elegant. That's exactly why they make such a thoughtful gift: every woman deserves jewelry that reflects her bold personality and beautiful spirit.</p>
                    <h3>Styles to Choose From</h3>
                    <p>The collection covers bangle bracelets, described as sleek & stylish; cuff bracelets, for statement-making sophistication; and beaded bracelets, characterized as fun & playful.</p>
                    <h3>How to Choose the Right One</h3>
                    <p>When picking a bracelet as a gift, consider three things: her personal style preferences, whether the piece is for everyday wear or a special occasion, and whether to add a personal touch through engravings or charms.</p>
                    <h3>Why Estele</h3>
                    <p>Estele's bracelets bring together exclusive designs, quality you can trust, and a hassle-free shopping experience with secure online payment options.</p>
                    <p>A bracelet from Estele is a gift that says, "You deserve the best." More than an accessory, it's a keepsake carrying emotional significance beyond its material value.</p>
                    HTML,
                'author_name' => 'Team Estele',
                'status' => 'published',
                'published_at' => now()->subDays(2),
            ]
        );

        if (! $braceletsGuide->hasMedia('featured_image')) {
            try {
                $braceletsGuide->addMedia(storage_path('app/seed-images/homepage/valentines-day-gift-guide-bracelets-for-women.png'))
                    ->preservingOriginal()
                    ->usingFileName($braceletsGuide->slug.'.png')
                    ->toMediaCollection('featured_image');
            } catch (\Throwable $e) {
                $this->command?->warn("Could not fetch blog image for {$braceletsGuide->title}: {$e->getMessage()}");
            }
        }

        // Real FAQs from estele.co/pages/faq-s (question/answer/category all
        // sourced from the live site, not invented) — matched via the old
        // placeholder questions so re-running this seeder updates existing
        // rows instead of creating duplicates.
        $shippingDelivery = $this->migrateOrCreate(FaqCategory::class, 'name', 'Shipping & Delivery', 'Ordering', ['name' => 'Shipping & Delivery', 'sort_order' => 1]);
        $returnsExchange = $this->migrateOrCreate(FaqCategory::class, 'name', 'Returns & Exchange', 'Shipping', ['name' => 'Returns & Exchange', 'sort_order' => 2]);
        $payment = FaqCategory::firstOrCreate(['name' => 'Payment'], ['sort_order' => 3]);

        $this->migrateOrCreate(
            Faq::class,
            'question',
            'When will my order ship?',
            'How do I place an order?',
            [
                'faq_category_id' => $shippingDelivery->id,
                'question' => 'When will my order ship?',
                'answer' => 'Orders are usually processed within 1-3 business days (Monday-Friday), with expedited 24-hour processing for orders shipping to Hyderabad. Customized or pre-order items may require additional time. For specific delivery requests, contact us at info@estele.co or +91 9121022888.',
                'sort_order' => 1,
            ]
        );
        $this->migrateOrCreate(
            Faq::class,
            'question',
            'Through what carrier do you ship?',
            'Can I modify my order after placing it?',
            [
                'faq_category_id' => $shippingDelivery->id,
                'question' => 'Through what carrier do you ship?',
                'answer' => "We ship via Delhivery. You'll receive a tracking number once your order is dispatched, along with a notification when your parcel is out for local delivery.",
                'sort_order' => 2,
            ]
        );
        $this->migrateOrCreate(
            Faq::class,
            'question',
            'Do you ship internationally?',
            'How long does delivery take?',
            [
                'faq_category_id' => $shippingDelivery->id,
                'question' => 'Do you ship internationally?',
                'answer' => 'Unfortunately, we currently process orders only in India.',
                'sort_order' => 3,
            ]
        );

        Faq::firstOrCreate(
            ['question' => 'What is the return procedure?'],
            [
                'faq_category_id' => $returnsExchange->id,
                'answer' => 'Items can be returned within 7 days of delivery (excluding sale items). Products must be undamaged, in their original packaging, with the invoice included. Please allow up to two weeks from when you send the parcel for us to process your refund. Contact info@estele.co or +91 9121022888 for pickup instructions. Returns address: Estele Accessories Pvt. Ltd., 9-12/1, Sri Sai Nilayam, BSNL Building, Hanuman Nagar, Boduppal, Hyderabad - 500092.',
                'sort_order' => 1,
            ]
        );
        Faq::firstOrCreate(
            ['question' => 'Can items be exchanged?'],
            [
                'faq_category_id' => $returnsExchange->id,
                'answer' => "Currently, we are not able to process any exchanges. If you're looking for a replacement, please contact us at info@estele.co or +91 9121022888.",
                'sort_order' => 2,
            ]
        );

        Faq::firstOrCreate(
            ['question' => 'What payment methods are accepted?'],
            [
                'faq_category_id' => $payment->id,
                'answer' => 'We accept Visa, MasterCard, AMEX, NetBanking, Cash on Delivery, and PayU wallet.',
                'sort_order' => 1,
            ]
        );
    }
}
