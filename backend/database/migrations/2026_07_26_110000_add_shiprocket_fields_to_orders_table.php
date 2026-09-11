<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shiprocket_shipment_id')->nullable()->after('carrier');
            $table->string('shiprocket_awb_code')->nullable()->after('shiprocket_shipment_id');
            $table->string('tracking_status')->nullable()->after('shiprocket_awb_code');
            $table->timestamp('tracking_synced_at')->nullable()->after('tracking_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shiprocket_shipment_id',
                'shiprocket_awb_code',
                'tracking_status',
                'tracking_synced_at',
            ]);
        });
    }
};
