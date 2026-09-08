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

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            SettingSeeder::class,
            HomepageContentSeeder::class,
            CollectionSeeder::class,
            CouponOfferSeeder::class,
            ContentSeeder::class,
            PopupSeeder::class,
            // Last: its journal/FAQ blocks link to rows ContentSeeder creates.
            HomepageSectionsSeeder::class,
        ]);
    }
}
