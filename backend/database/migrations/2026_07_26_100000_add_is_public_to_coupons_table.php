<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // Public coupons are listed in the storefront's "View all coupons"
            // modal; private ones (e.g. a referral or email-blast code) still
            // work at checkout but aren't advertised to every visitor.
            $table->boolean('is_public')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }
};
