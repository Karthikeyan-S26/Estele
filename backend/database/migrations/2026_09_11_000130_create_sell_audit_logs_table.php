<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only audit trail for sell requests — every state change and key
     * event (invitation, acceptance, bid, close, valuation, settlement,
     * expiry, cancellation) lands here so the Filament resource and the app's
     * status timeline can show a defensible history.
     */
    public function up(): void
    {
        Schema::create('sell_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sell_request_id')->constrained('sell_requests')->cascadeOnDelete();
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('event');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['sell_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sell_audit_logs');
    }
};