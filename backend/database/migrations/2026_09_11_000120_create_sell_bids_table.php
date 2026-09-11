<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bids on a sell request. One active bid per vendor per request
     * (re-submitting updates the existing row). The 3-hour window is the
     * customer's `bids_end_at`, enforced by both the bidding command and the
     * vendor bid endpoint. Winner selection (sell:bids-close) favours the
     * highest amount, then the earliest submitted_at — deterministic ties.
     */
    public function up(): void
    {
        Schema::create('sell_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sell_request_id')->constrained('sell_requests')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('active'); // active | withdrawn
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['sell_request_id', 'vendor_id']);
            $table->index(['sell_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sell_bids');
    }
};