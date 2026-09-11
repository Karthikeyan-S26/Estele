<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('newsletter'); // newsletter | free_gift | exit_intent
            $table->string('trigger')->default('delay'); // delay | exit_intent
            $table->unsignedInteger('delay_seconds')->default(4);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('discount_code')->nullable();
            $table->boolean('show_email_field')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('target_new_visitors_only')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popups');
    }
};
