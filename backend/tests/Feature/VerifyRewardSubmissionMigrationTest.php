<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VerifyRewardSubmissionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('reward_submissions'));
        $this->assertTrue(Schema::hasColumns('reward_submissions', [
            'user_id', 'order_id', 'status', 'reward_amount', 'rejection_reason', 'reviewed_at',
        ]));
        $this->assertTrue(Schema::hasColumn('reward_submissions', 'reviewed_by'));

        $this->assertTrue(Schema::hasTable('wallet_transactions'));
        $this->assertTrue(Schema::hasColumns('wallet_transactions', [
            'user_id', 'type', 'amount', 'balance_after', 'reason', 'reference_type', 'reference_id',
        ]));

        $this->assertTrue(Schema::hasColumn('users', 'wallet_balance'));
        $this->assertTrue(Schema::hasColumn('orders', 'wallet_amount_used'));
    }
}
