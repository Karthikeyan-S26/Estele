<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer "sell your old jewellery" requests. Lifecycle:
     *   pending_bids -> bidding -> valuation_review -> completed | expired | cancelled
     * A request is auto-invited to vendor-role users at creation; bidding opens
     * immediately and closes 3 hours later (sell:bids-close command). After
     * valuation_review, the admin sets a valuation; settlement credits 90% of it
     * to the customer's wallet (10% platform deduction) with a 10-day expiry.
     */
    public function up(): void
    {
        Schema::create('sell_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('request_number')->unique();
            $table->string('item_type'); // ring | chain | necklace | earrings | bracelet | other
            $table->text('description')->nullable();
            $table->string('city')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('status')->default('pending_bids');
            $table->timestamp('bids_start_at')->nullable();
            $table->timestamp('bids_end_at')->nullable();

            $table->decimal('highest_bid_amount', 10, 2)->nullable();
            $table->unsignedBigInteger('winning_bid_id')->nullable();

            $table->decimal('admin_valuation', 10, 2)->nullable();
            $table->decimal('deduction_amount', 10, 2)->nullable();   // 10% platform fee
            $table->decimal('wallet_credit', 10, 2)->nullable();      // 90% credited
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->timestamp('settlement_at')->nullable();
            $table->timestamp('result_selected_at')->nullable();

            $table->string('cancelled_by')->nullable(); // customer | admin
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sell_requests');
    }
};