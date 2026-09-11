<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Superseded by Spatie Media Library's own `gallery` collection on Product;
        // this table was created but never actually used.
        Schema::dropIfExists('product_images');
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
