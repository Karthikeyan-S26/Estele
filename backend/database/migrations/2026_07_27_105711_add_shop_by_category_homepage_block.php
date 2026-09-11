<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Shop by Category" used to be hardcoded, always first, on the
     * homepage — never reorderable through the Homepage Builder like the
     * other blocks. This backfills it as a real row so existing sites keep
     * the section in its current position (sort_order below every other
     * block) instead of it silently disappearing once the view stops
     * rendering it unconditionally.
     */
    public function up(): void
    {
        $exists = DB::table('homepage_blocks')->where('type', 'shop_by_category')->exists();

        if (! $exists) {
            DB::table('homepage_blocks')->insert([
                'type' => 'shop_by_category',
                'title' => 'Shop by Category',
                'sort_order' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('homepage_blocks')->where('type', 'shop_by_category')->delete();
    }
};
