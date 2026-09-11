<?php

namespace Database\Seeders;

use App\Models\Popup;
use Illuminate\Database\Seeder;

/**
 * Seeded copy is taken verbatim from the reference brand's (Estele.co) real,
 * live on-site email-capture section — their own popup section exists in
 * their theme but wasn't actually active/configured when checked, so there
 * was no live popup to copy pixel-for-pixel. This uses their real footer
 * copy as an overlay instead of inventing new marketing copy.
 */
class PopupSeeder extends Seeder
{
    public function run(): void
    {
        Popup::firstOrCreate(
            ['name' => 'Welcome newsletter discount'],
            [
                'type' => 'newsletter',
                'trigger' => 'delay',
                'delay_seconds' => 5,
                'title' => 'Get the Glow – Exclusive Access Awaits',
                'body' => 'Subscribe to our emailer and get 5% off your first purchase.',
                'cta_label' => 'Subscribe',
                'show_email_field' => true,
                'is_active' => true,
                'target_new_visitors_only' => true,
                'sort_order' => 0,
            ]
        );
    }
}
