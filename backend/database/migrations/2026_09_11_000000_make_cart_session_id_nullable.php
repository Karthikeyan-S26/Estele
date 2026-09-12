<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Carts are keyed by EITHER session_id (guest) or user_id (signed in),
     * never both. The original carts table made session_id a NOT NULL unique
     * string, so `Cart::firstOrCreate(['user_id' => ...])` (CheckoutController,
     * AuthController cart merge, CartController::currentCart) failed with
     * "Field 'session_id' doesn't have a default value". Allow a signed-in
     * cart to have a NULL session_id.
     */
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->string('session_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->string('session_id')->nullable(false)->change();
        });
    }
};