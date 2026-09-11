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
        Schema::table('orders', function (Blueprint $table) {
            // A customer-initiated request, not a status change — Order::booted()'s
            // ALLOWED_TRANSITIONS guard is the only thing allowed to actually move
            // `status`, so this just flags the order for an admin to action (cancel
            // it via the existing admin flow, which does the real transition +
            // restock) rather than letting the customer bypass that guard.
            $table->timestamp('cancellation_requested_at')->nullable()->after('admin_notes');
            $table->string('cancellation_reason')->nullable()->after('cancellation_requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancellation_requested_at', 'cancellation_reason']);
        });
    }
};
