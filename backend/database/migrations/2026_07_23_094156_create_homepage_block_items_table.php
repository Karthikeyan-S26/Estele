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
        Schema::create('homepage_block_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homepage_block_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('link_url')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->nullableMorphs('itemable');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_block_items');
    }
};
