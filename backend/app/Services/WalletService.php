<?php

namespace App\Services;

use App\Mail\WalletCredited;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * The only code path allowed to change users.wallet_balance or write a
 * wallet_transactions row. Every caller (reward approval, checkout debit,
 * cancel/return refund) goes through here so balance math never happens
 * twice in two different places.
 */
class WalletService
{
    public function credit(User $user, float $amount, string $reason, ?Model $reference = null): WalletTransaction
    {
        $transaction = DB::transaction(function () use ($user, $amount, $reason, $reference) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();

            $newBalance = (float) $locked->wallet_balance + $amount;
            $locked->update(['wallet_balance' => $newBalance]);

            return WalletTransaction::create([
                'user_id' => $locked->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reason' => $reason,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);
        });

        Mail::to($user->email)->queue(new WalletCredited($transaction->fresh(['user'])));

        return $transaction;
    }

    /**
     * @throws \DomainException if $amount exceeds the user's current balance.
     */
    public function debit(User $user, float $amount, string $reason, ?Model $reference = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $reason, $reference) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();

            if ($amount > (float) $locked->wallet_balance) {
                throw new \DomainException('Wallet balance is insufficient for this debit.');
            }

            $newBalance = (float) $locked->wallet_balance - $amount;
            $locked->update(['wallet_balance' => $newBalance]);

            return WalletTransaction::create([
                'user_id' => $locked->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reason' => $reason,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);
        });
    }
}
