<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the client-supplied X-Phone-Verified header (which anyone could
     * set) with a server-issued single-use nonce: verify-register-otp stamps
     * `verified_token` + `verified_at` onto the freshly-consumed OtpCode row,
     * and register() validates + consumes that token server-side.
     */
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('verified_token')->nullable()->index();
            $table->timestamp('verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropColumn(['verified_token', 'verified_at']);
        });
    }
};