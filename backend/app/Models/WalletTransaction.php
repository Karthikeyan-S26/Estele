<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    public const UPDATED_AT = null;

    /** A credit with a future/known expiry date (Old Jewellery settlement). */
    public const REASON_SELL_SETTLEMENT = 'sell_settlement';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'reason',
        'reference_type',
        'reference_id',
        'expires_at',
        'expired_at',
        'reminder_3d_sent_at',
        'reminder_1d_sent_at',
        'expiry_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'expires_at' => 'datetime',
            'expired_at' => 'datetime',
            'reminder_3d_sent_at' => 'datetime',
            'reminder_1d_sent_at' => 'datetime',
            'expiry_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function isExpiring(): bool
    {
        return $this->expires_at !== null && $this->expired_at === null;
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null;
    }

    /**
     * How this row should read on the wallet screen:
     *  - expired:      value never usable again (expired_at set)
     *  - active:       normal credit/debit, nothing expiring
     *  - expiring:     credit, not yet spent, expires_at in the future
     *  - active-then-expired: credit row whose window has passed
     */
    public function lifetimeStatus(): string
    {
        if ($this->expired_at !== null) {
            return 'expired';
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            // Between expiry-due and the sweep command running.
            return 'expired';
        }

        if ($this->isExpiring()) {
            return 'expiring';
        }

        return 'active';
    }
}
