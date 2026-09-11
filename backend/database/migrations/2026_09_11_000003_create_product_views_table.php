<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Raw product-detail page views, recorded by GET /api/products/{slug}.
     * Aggregated per product over rolling windows to drive the real
     * "Trending" collection (top-viewed in the last 30 days) instead of a
     * hand-picked carousel.
     */
    public function up(): void
    {
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_views');
    }
};