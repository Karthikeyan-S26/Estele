<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('reminder_3d_sent_at')->nullable();
            $table->timestamp('reminder_1d_sent_at')->nullable();
            $table->timestamp('expiry_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'expired_at', 'reminder_3d_sent_at', 'reminder_1d_sent_at', 'expiry_notified_at']);
        });
    }
};