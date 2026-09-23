<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Superseded by Spatie Media Library's own `gallery` collection on Product;
        // this table was created but never actually used (no model, controller,
        // or test in this codebase references it).
        //
        // DEPLOY-SAFETY (mobile API shares the production database): drop the
        // table ONLY when it exists and holds zero rows. If any rows are ever
        // present, the table is left completely untouched — this migration can
        // never delete production data.
        if (Schema::hasTable('product_images')
            && DB::table('product_images')->count() === 0) {
            Schema::dropIfExists('product_images');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('alt_text');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
};
