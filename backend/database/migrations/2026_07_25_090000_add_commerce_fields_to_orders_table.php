<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('payment_method');
            $table->string('payment_reference')->nullable()->after('payment_status');
            $table->decimal('refunded_amount', 10, 2)->nullable()->after('total');
            $table->text('refund_reason')->nullable()->after('refunded_amount');
            $table->string('tracking_number')->nullable()->after('status');
            $table->string('carrier')->nullable()->after('tracking_number');
            $table->text('admin_notes')->nullable()->after('carrier');
            $table->string('coupon_code')->nullable()->after('subtotal');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('coupon_code');

            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn([
                'payment_status',
                'payment_reference',
                'refunded_amount',
                'refund_reason',
                'tracking_number',
                'carrier',
                'admin_notes',
                'coupon_code',
                'discount_amount',
            ]);
        });
    }
};
