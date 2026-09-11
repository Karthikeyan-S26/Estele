<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Admin-authored reviews (see ReviewResource::canCreate()) have no
            // real customer behind them, so there's no email to collect.
            $table->string('customer_email')->nullable()->change();

            // Lets an admin backdate a review (e.g. to seed a product's
            // reviews before launch) — falls back to created_at when unset,
            // see Review::displayDate().
            $table->date('review_date')->nullable()->after('customer_email');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('review_date');
            $table->string('customer_email')->nullable(false)->change();
        });
    }
};
