<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wallet credits from Old Jewellery settlements carry a 10-day validity.
     * `expires_at` = when the credit runs out; `expired_at` = when the expiry
     * command actually struck it off; the *_sent_at columns dedupe the
     * 3-day / 1-day / expiry notification emails.
     */
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('reminder_3d_sent_at')->nullable();
            $table->timestamp('reminder_1d_sent_at')->nullable();
            $table->timestamp('expiry_notified_at')->nullable();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'expires_at',
                'expired_at',
                'reminder_3d_sent_at',
                'reminder_1d_sent_at',
                'expiry_notified_at',
            ]);
        });
    }
};