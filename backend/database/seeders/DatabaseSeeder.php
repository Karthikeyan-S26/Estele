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

        // No credential is ever hardcoded here — both come from the
        // environment (see .env.example: SEED_ADMIN_EMAIL/SEED_ADMIN_PASSWORD),
        // so a seed never creates a login with a password already sitting in
        // git history. Skipped entirely if unset, rather than falling back to
        // any built-in default.
        $adminEmail = env('SEED_ADMIN_EMAIL');
        $adminPassword = env('SEED_ADMIN_PASSWORD');

        if ($adminEmail && $adminPassword) {
            // Not User::factory(): fakerphp/faker is a require-dev package,
            // unavailable in a `composer install --no-dev` production build.
            $admin = User::firstOrCreate(
                ['email' => $adminEmail],
                ['name' => 'Admin', 'password' => $adminPassword]
            );
            $admin->assignRole('super_admin');
        } else {
            $this->command?->warn('SEED_ADMIN_EMAIL/SEED_ADMIN_PASSWORD not set — skipping admin user creation.');
        }

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
