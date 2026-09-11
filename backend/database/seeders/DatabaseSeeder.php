<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([ShieldSeeder::class]);

        // Not User::factory(): fakerphp/faker is a require-dev package, unavailable
        // in a `composer install --no-dev` production build (e.g. the Railway deploy).
        $admin = User::firstOrCreate(
            ['email' => 'lavanyagarg500@gmail.com'],
            ['name' => 'Admin', 'password' => 'changeme123']
        );
        $admin->assignRole('super_admin');

        // Demo customer for the mobile app — email+password login works
        // against these credentials in the Flutter app out of the box.
        User::firstOrCreate(
            ['email' => 'demo@estele.in'],
            [
                'name' => 'Demo Customer',
                'phone' => '9876543210',
                'password' => 'password123',
            ]
        );

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            // Collections must be seeded before HomepageContentSeeder: several
            // homepage sections (the Rose Gold collection banner, the Trending
            // row and the New Arrivals / Bestsellers carousels) resolve their
            // products through the collections created here. Running this seeder
            // first used to silently skip those sections and fall Trending back
            // to `is_featured` — which duplicated the Bestsellers row.
            CollectionSeeder::class,
            SettingSeeder::class,
            HomepageContentSeeder::class,
            CouponOfferSeeder::class,
            ContentSeeder::class,
            PopupSeeder::class,
            // Last: its journal/FAQ blocks link to rows ContentSeeder creates.
            HomepageSectionsSeeder::class,
            // Old Jewellery buy-back demo data — needs the demo customer created
            // above and the vendors seeded by VendorSeeder.
            SellRequestSeeder::class,
        ]);
    }
}
