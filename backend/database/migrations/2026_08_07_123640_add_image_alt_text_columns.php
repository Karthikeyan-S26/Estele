<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mandatory alt-text field on image upload (spec §3.1/§4.1 — "reject the
     * upload/save if missing"), for the single-image models. Product gallery
     * (multiple images per record) is handled separately at render time —
     * see Product::galleryAlt() — since a true per-image admin editor is a
     * much bigger Filament/Livewire undertaking than this column approach.
     *
     * Columns are added nullable, backfilled from each model's existing
     * name/title field so current rows don't suddenly fail validation, then
     * the Filament form makes the field required going forward.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('image_alt_text')->nullable()->after('description');
        });
        Schema::table('banners', function (Blueprint $table) {
            $table->string('image_alt_text')->nullable()->after('title');
        });
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('featured_image_alt_text')->nullable()->after('title');
        });
        Schema::table('popups', function (Blueprint $table) {
            $table->string('image_alt_text')->nullable()->after('title');
        });

        DB::table('categories')->whereNull('image_alt_text')->update(['image_alt_text' => DB::raw('name')]);
        DB::table('banners')->whereNull('image_alt_text')->update(['image_alt_text' => DB::raw('title')]);
        DB::table('blogs')->whereNull('featured_image_alt_text')->update(['featured_image_alt_text' => DB::raw('title')]);
        DB::table('popups')->whereNull('image_alt_text')->update(['image_alt_text' => DB::raw('title')]);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('image_alt_text');
        });
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('image_alt_text');
        });
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn('featured_image_alt_text');
        });
        Schema::table('popups', function (Blueprint $table) {
            $table->dropColumn('image_alt_text');
        });
    }
};
