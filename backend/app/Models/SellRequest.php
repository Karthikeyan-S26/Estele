<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer's "sell your old jewellery" request. The status constants and the
 * guarded transitions below are the single source of truth — every script
 * (bidding close), controller, and Filament action performs its move through
 * transitionTo() so the audit trail is never bypassed.
 */
class SellRequest extends Model
{
    public const STATUS_PENDING_BIDS = 'pending_bids';
    public const STATUS_BIDDING = 'bidding';
    public const STATUS_VALUATION_REVIEW = 'valuation_review';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Valid forward moves. No move may skip a stage or reverse direction.
     */
    public const ALLOWED_TRANSITIONS = [
        'pending_bids' => ['bidding', 'cancelled'],
        'bidding' => ['valuation_review', 'expired', 'cancelled'],
        'valuation_review' => ['completed', 'expired'],
        'completed' => [],
        'expired' => [],
        'cancelled' => [],
    ];

    public const BID_DURATION_HOURS = 3;

    public const SETTLEMENT_CREDIT_RATE = 0.9;

    public const SETTLEMENT_DEDUCTION_RATE = 0.1;

    public const WALLET_CREDIT_VALID_DAYS = 10;

    protected $fillable = [
        'user_id',
        'request_number',
        'item_type',
        'description',
        'city',
        'contact_phone',
        'image_path',
        'video_path',
        'status',
        'bids_start_at',
        'bids_end_at',
        'highest_bid_amount',
        'winning_bid_id',
        'admin_valuation',
        'deduction_amount',
        'wallet_credit',
        'wallet_transaction_id',
        'settlement_at',
        'result_selected_at',
        'cancelled_by',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'bids_start_at' => 'datetime',
            'bids_end_at' => 'datetime',
            'highest_bid_amount' => 'decimal:2',
            'admin_valuation' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'wallet_credit' => 'decimal:2',
            'settlement_at' => 'datetime',
            'result_selected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(SellInvitation::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(SellBid::class);
    }

    public function activeBids()
    {
        return $this->bids()->where('status', 'active');
    }

    public function walletCredit(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(SellAuditLog::class);
    }

    public function getRouteKeyName(): string
    {
        return 'request_number';
    }

    public function transitionTo(string $to, ?string $event = null, ?array $metadata = null, ?Model $actor = null): bool
    {
        if (! in_array($to, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true)) {
            return false;
        }

        $from = $this->status;

        // Guarded UPDATE — atomically wins only while the row is still in the
        // expected state. Two concurrent closers can never double-transition
        // or double-audit the same request.
        $moved = static::whereKey($this->id)
            ->where('status', $from)
            ->update(['status' => $to]);

        if ($moved === 0) {
            return false;
        }

        $this->audit($event ?? "status_{$from}_to_{$to}", $from, $to, $metadata, $actor);

        return true;
    }

    public function audit(string $event, ?string $fromStatus = null, ?string $toStatus = null, ?array $metadata = null, ?Model $actor = null): void
    {
        $this->auditLogs()->create([
            'actor_type' => $actor ? $actor->getMorphClass() : null,
            'actor_id' => $actor?->getKey(),
            'event' => $event,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'metadata' => $metadata,
        ]);
    }

    public function isBiddingOpen(): bool
    {
        return $this->status === self::STATUS_BIDDING
            && $this->bids_end_at !== null
            && now()->lessThan($this->bids_end_at);
    }

    public function isSettled(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->wallet_transaction_id !== null;
    }

    /**
     * Deterministic winner: highest amount, then earliest submitted_at, then
     * lowest vendor id — never ambiguous, so closing is idempotent-safe.
     */
    public function resolvedWinner(): ?SellBid
    {
        return $this->activeBids()
            ->orderByDesc('amount')
            ->orderBy('submitted_at')
            ->orderBy('vendor_id')
            ->first();
    }

    public static function nextRequestNumber(): string
    {
        do {
            $number = 'SRF-'.now()->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(4));
        } while (static::where('request_number', $number)->exists());

        return $number;
    }
}