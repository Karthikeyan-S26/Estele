<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds an EMAIL channel to the OTP registry so a single OtpCode row can
     * be keyed by phone (channel 'sms') OR email (channel 'email'). phone
     * stays NOT NULL for SMS rows but becomes nullable so an email-only OTP
     * (registration/login without a phone number involved) can exist.
     */
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone')->index();
            $table->string('channel')->default('sms')->after('email')->index();
            $table->string('phone', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropColumn(['email', 'channel']);
            $table->string('phone', 20)->nullable(false)->change();
        });
    }
};