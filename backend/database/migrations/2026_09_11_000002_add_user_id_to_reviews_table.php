<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * API reviews are attributed to the authenticated user (no spoofable
     * customer_email), so review rows need a user_id for ownership checks
     * (per-user dedupe, verified-purchase lookup, future edit/delete).
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('product_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};