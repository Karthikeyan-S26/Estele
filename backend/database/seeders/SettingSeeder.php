<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'site_name' => 'Estele',
            'site_tagline' => 'Fashion Jewellery Online India — Trusted Since 1989',
            'announcement_messages' => json_encode([
                'Free gift on orders above ₹1,499',
                '7-Day Return & Exchange',
                'Trusted since 1989 · 5M+ happy customers',
            ]),
            'contact_email' => 'support@example.com',
            'contact_phone' => '+91 00000 00000',
            'footer_copyright' => '© '.date('Y').' Estele. All rights reserved.',

            // Shipping — see App\Services\Shipping. 'flat' needs no external
            // account; set to 'shiprocket' once SHIPROCKET_EMAIL/PASSWORD
            // are configured to switch to live courier rate checks.
            'shipping_provider' => 'flat',
            'shipping_free_threshold' => '999',
            'shipping_flat_rate' => '79',
            'shipping_pickup_pincode' => '500001',
            'shipping_avg_item_weight_grams' => '100',

            // Payment — see App\Services\Payment. 'cod' needs no external
            // account; set to 'razorpay' once RAZORPAY_KEY_ID/KEY_SECRET
            // are configured to accept online payments at checkout.
            'payment_provider' => 'cod',

            // SEO Global Settings (Phase 5, spec §5's "SEO Settings" row) —
            // admin-editable robots.txt (see the /robots.txt route in
            // web.php) and raw script injection for GA4/GSC/Meta Pixel etc,
            // rendered verbatim near </head> and </body> in layouts/app —
            // deliberately blank by default, nothing tracked until an admin
            // pastes real tracking snippets in.
            'robots_txt' => "User-agent: *\nAllow: /\nDisallow: /cart\nDisallow: /checkout\nDisallow: /account\nDisallow: /login\nDisallow: /register\nDisallow: /payment\nDisallow: /search\nDisallow: /*?q=\nDisallow: /*?*sort=\nDisallow: /*?*filter=\n\nSitemap: ".url('/sitemap.xml'),
            'tracking_head_scripts' => '',
            'tracking_body_scripts' => '',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
