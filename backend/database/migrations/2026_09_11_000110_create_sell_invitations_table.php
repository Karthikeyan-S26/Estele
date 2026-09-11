<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vendor invitations for a sell request. Each invited vendor gets a unique
     * bearer token (emailed with a link) that identifies BOTH the invitation
     * and — after acceptance — lets them place bids from that same landing page
     * without an account-backed session.
     */
    public function up(): void
    {
        Schema::create('sell_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sell_request_id')->constrained('sell_requests')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('status')->default('pending'); // pending | accepted | declined | expired
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['sell_request_id', 'vendor_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sell_invitations');
    }
};